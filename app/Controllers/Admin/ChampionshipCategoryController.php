<?php

namespace Controllers\Admin;

use Controllers\Controller;
use Helpers\Auth;
use Helpers\Csrf;
use Models\Championship;
use Repositories\EventCategoryRepository;
use Repositories\RulesetRepository;
use Services\ChampionshipSetupService;
use Services\RegistrationException;

/** Categorias do evento (modelo v2): gerar do regulamento, fechar e juntar. Só Presidente. */
class ChampionshipCategoryController extends Controller
{
    private EventCategoryRepository $categories;
    private ChampionshipSetupService $setup;

    public function __construct()
    {
        $this->categories = new EventCategoryRepository();
        $this->setup      = new ChampionshipSetupService();
    }

    public function index(int $id): void
    {
        $championship = $this->championship($id);
        $ruleset      = $this->rulesetOf($championship);

        $this->view('admin/championships/categories', [
            'championship' => $championship,
            'ruleset'      => $ruleset,
            'rows'         => $this->categories->rows($id),
            'pageTitle'    => 'Categorias – ' . $championship['title'],
        ]);
    }

    /** Prévia da geração, com caixas para desmarcar. Só leitura. */
    public function preview(int $id): void
    {
        $championship = $this->championship($id);
        $ruleset      = $this->rulesetOf($championship);

        $picked = static fn (string $key): array => array_map('intval', (array) ($_GET[$key] ?? []));
        $input  = ['disciplines' => $picked('disciplines'), 'ages' => $picked('ages'), 'belts' => $picked('belts')];

        try {
            $drafts = $this->setup->preview((int) $ruleset->id, $input['disciplines'], $input['ages'], $input['belts']);
        } catch (\DomainException $e) {
            $_SESSION['error'] = $e->getMessage();
            $this->redirect("/fgkirs-admin/championships/$id/categories");
        }

        if ($drafts === []) {
            $_SESSION['error'] = 'A escolha atual não gera nenhuma categoria. Marque ao menos uma disciplina e uma divisão etária.';
            $this->redirect("/fgkirs-admin/championships/$id/categories");
        }

        $existing = array_flip(array_column($this->categories->rows($id), 'code'));
        $groups   = [];
        foreach ($drafts as $draft) {
            $groups[$draft->disciplineName][$draft->ageLabel][] = $draft;
        }

        $this->view('admin/championships/categories_preview', [
            'championship' => $championship,
            'ruleset'      => $ruleset,
            'groups'       => $groups,
            'existing'     => $existing,
            'total'        => count($drafts),
            'input'        => $input,
            'pageTitle'    => 'Prévia das categorias',
        ]);
    }

    public function store(int $id): void
    {
        $championship = $this->championship($id);
        $this->guardCsrf("/fgkirs-admin/championships/$id/categories");
        $ruleset = $this->rulesetOf($championship);

        $picked = static fn (string $key): array => array_map('intval', (array) ($_POST[$key] ?? []));

        try {
            $result = $this->setup->apply(
                $id, (int) $ruleset->id, $picked('disciplines'), $picked('ages'), $picked('belts'),
                (array) ($_POST['codes'] ?? [])
            );
        } catch (RegistrationException|\DomainException $e) {
            $_SESSION['error'] = $e->getMessage();
            $this->redirect("/fgkirs-admin/championships/$id/categories");
        }

        $_SESSION['success'] = $result['inserted'] . ' categoria(s) criada(s)'
            . ($result['skipped'] ? ', ' . $result['skipped'] . ' já existiam e foram mantidas.' : '.');
        $this->redirect("/fgkirs-admin/championships/$id/categories");
    }

    public function status(int $id, int $categoryId): void
    {
        $this->championship($id);
        $this->guardCsrf("/fgkirs-admin/championships/$id/categories");

        $this->attempt($id, function () use ($id, $categoryId) {
            $this->setup->setOpen($id, $categoryId, ($_POST['action'] ?? '') === 'open');
            $_SESSION['success'] = 'Categoria atualizada.';
        });
    }

    public function merge(int $id): void
    {
        $this->championship($id);
        $this->guardCsrf("/fgkirs-admin/championships/$id/categories");

        $this->attempt($id, function () use ($id) {
            $moved = $this->setup->merge($id, (int) ($_POST['from_id'] ?? 0), (int) ($_POST['to_id'] ?? 0), (int) Auth::id());
            $_SESSION['success'] = "Categorias juntadas. $moved inscrição(ões) movida(s) para o destino.";
        });
    }

    private function attempt(int $id, callable $work): void
    {
        try {
            $work();
        } catch (RegistrationException $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        $this->redirect("/fgkirs-admin/championships/$id/categories");
    }

    private function championship(int $id): array
    {
        if (!Auth::isAdmin()) {
            $this->redirect('/fgkirs-admin');
        }

        $championship = Championship::find($id);

        if (!$championship || !Championship::isV2($championship)) {
            $_SESSION['error'] = 'Evento não encontrado ou no modelo antigo de categorias.';
            $this->redirect('/fgkirs-admin/championships');
        }

        return $championship;
    }

    private function rulesetOf(array $championship): \Domain\Championship\Ruleset
    {
        if (empty($championship['ruleset_id'])) {
            $_SESSION['error'] = 'Escolha o regulamento do evento antes de gerar categorias.';
            $this->redirect('/fgkirs-admin/championships/edit/' . (int) $championship['id']);
        }

        return (new RulesetRepository())->load((int) $championship['ruleset_id']);
    }

    private function guardCsrf(string $back): void
    {
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $_SESSION['error'] = 'Sessão expirada. Tente novamente.';
            $this->redirect($back);
        }
    }
}
