<?php

namespace Controllers\Admin;

use Controllers\Controller;
use Domain\Championship\CategoryGenerator;
use Domain\Championship\GenerationRequest;
use Helpers\Auth;
use Helpers\Csrf;
use Repositories\RulesetRepository;

/**
 * Regulamento do Presidente: divisões de idade, faixa e peso, e as disciplinas. Só admin.
 * Nesta fase o gerador apenas pré-visualiza; as categorias do evento são gravadas na fase 4.
 */
class RulesetController extends Controller
{
    private RulesetRepository $rulesets;

    public function __construct()
    {
        $this->rulesets = new RulesetRepository();
    }

    public function index(): void
    {
        $this->guard();

        $this->view('admin/rulesets/index', [
            'rulesets'  => $this->rulesets->all(),
            'pageTitle' => 'Regulamentos',
        ]);
    }

    public function create(): void
    {
        $this->guard();

        $this->view('admin/rulesets/form', ['pageTitle' => 'Novo Regulamento']);
    }

    public function store(): void
    {
        $this->guard();
        $this->guardCsrf('/fgkirs-admin/rulesets/create');

        try {
            $id = $this->rulesets->create($_POST['name'] ?? '', $_POST['age_policy'] ?? '');
        } catch (\InvalidArgumentException $e) {
            $_SESSION['error'] = $e->getMessage();
            $this->redirect('/fgkirs-admin/rulesets/create');
        }

        $_SESSION['success'] = 'Regulamento criado. Cadastre as divisões e as disciplinas.';
        $this->redirect("/fgkirs-admin/rulesets/edit/$id");
    }

    public function edit(int $id): void
    {
        $this->guard();
        $meta = $this->findOrRedirect($id);

        $this->view('admin/rulesets/edit', [
            'meta'      => $meta,
            'ruleset'   => $this->rulesets->load($id),
            'pageTitle' => 'Regulamento — ' . $meta['name'],
        ]);
    }

    public function update(int $id): void
    {
        $this->guard();
        $back = "/fgkirs-admin/rulesets/edit/$id";
        $this->guardCsrf($back);
        $this->findOrRedirect($id);

        $this->save($back, fn () => $this->rulesets->updateMeta(
            $id,
            $_POST['name'] ?? '',
            $_POST['age_policy'] ?? '',
            !empty($_POST['is_active']),
            !empty($_POST['is_default'])
        ), 'Dados do regulamento atualizados.');
    }

    public function saveAges(int $id): void
    {
        $this->saveBlock($id, 'ages', fn () => $this->rulesets->saveAgeDivisions($id, $_POST['rows'] ?? []), 'Divisões etárias salvas.');
    }

    public function saveBelts(int $id): void
    {
        $this->saveBlock($id, 'belts', fn () => $this->rulesets->saveBeltDivisions($id, $_POST['rows'] ?? []), 'Divisões de faixa salvas.');
    }

    public function saveWeights(int $id): void
    {
        $this->saveBlock($id, 'weights', fn () => $this->rulesets->saveWeightClasses($id, $_POST['rows'] ?? []), 'Divisões de peso salvas.');
    }

    public function saveDisciplines(int $id): void
    {
        $this->saveBlock($id, 'disciplines', fn () => $this->rulesets->saveDisciplines($id, $_POST['rows'] ?? []), 'Disciplinas salvas.');
    }

    public function duplicate(int $id): void
    {
        $this->guard();
        $this->guardCsrf('/fgkirs-admin/rulesets');
        $this->findOrRedirect($id);

        $newId = $this->rulesets->duplicate($id);

        $_SESSION['success'] = 'Regulamento duplicado. Renomeie a cópia e ajuste o que mudou.';
        $this->redirect("/fgkirs-admin/rulesets/edit/$newId");
    }

    /** Só leitura: mostra o que o gerador produziria, sem gravar nada. */
    public function preview(int $id): void
    {
        $this->guard();
        $meta    = $this->findOrRedirect($id);
        $ruleset = $this->rulesets->load($id);

        // Sem o formulário enviado, a prévia nasce com tudo marcado
        $submitted = isset($_GET['go']);
        $picked    = static fn (string $key): array => array_map('intval', (array) ($_GET[$key] ?? []));

        $request = $submitted
            ? new GenerationRequest(
                array_values(array_filter($ruleset->disciplines, fn ($d) => in_array($d->id, $picked('disciplines'), true))),
                array_values(array_filter($ruleset->ageDivisions, fn ($a) => in_array($a->id, $picked('ages'), true))),
                array_values(array_filter($ruleset->beltDivisions, fn ($b) => in_array($b->id, $picked('belts'), true)))
            )
            : GenerationRequest::everything($ruleset);

        $error  = null;
        $drafts = [];

        try {
            $drafts = (new CategoryGenerator())->generate($ruleset, $request);
        } catch (\DomainException $e) {
            $error = $e->getMessage();
        }

        $groups = [];
        foreach ($drafts as $draft) {
            $groups[$draft->disciplineName][$draft->ageLabel][] = $draft;
        }

        $this->view('admin/rulesets/preview', [
            'meta'      => $meta,
            'ruleset'   => $ruleset,
            'request'   => $request,
            'groups'    => $groups,
            'total'     => count($drafts),
            'error'     => $error,
            'pageTitle' => 'Pré-visualizar categorias',
        ]);
    }

    private function saveBlock(int $id, string $anchor, callable $work, string $success): void
    {
        $this->guard();
        $back = "/fgkirs-admin/rulesets/edit/$id#$anchor";
        $this->guardCsrf($back);
        $this->findOrRedirect($id);

        $this->save($back, $work, $success);
    }

    private function save(string $back, callable $work, string $success): void
    {
        try {
            $work();
            $_SESSION['success'] = $success;
        } catch (\InvalidArgumentException $e) {
            $_SESSION['error'] = $e->getMessage();
        } catch (\PDOException $e) {
            error_log('RulesetController save error: ' . $e->getMessage());
            $_SESSION['error'] = 'Não foi possível salvar. Confira se há códigos repetidos.';
        }

        $this->redirect($back);
    }

    private function findOrRedirect(int $id): array
    {
        $meta = $this->rulesets->find($id);

        if (!$meta) {
            $_SESSION['error'] = 'Regulamento não encontrado.';
            $this->redirect('/fgkirs-admin/rulesets');
        }

        return $meta;
    }

    private function guard(): void
    {
        if (!Auth::isAdmin()) {
            $this->redirect('/fgkirs-admin');
        }
    }

    private function guardCsrf(string $back): void
    {
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $_SESSION['error'] = 'Sessão expirada. Tente novamente.';
            $this->redirect($back);
        }
    }
}
