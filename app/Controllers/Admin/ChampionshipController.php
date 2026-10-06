<?php

namespace Controllers\Admin;

use Controllers\Controller;
use Helpers\Auth;
use Helpers\Csrf;
use Models\Championship;
use Repositories\EventCategoryRepository;
use Repositories\EventTeamRepository;
use Repositories\RegistrationRepository;
use Repositories\RulesetRepository;

/**
 * Campeonatos: criação e edição pelo Presidente. A inscrição fica em RegistrationController e o
 * consolidado em RegistrationPanelController.
 *
 * A criação é sempre centralizada no Presidente, mesmo quando o evento é organizado por um dojo
 * de outra cidade. Dojos não criam eventos.
 */
class ChampionshipController extends Controller
{
    /** Lista visível aos dois perfis: o sensei só enxerga eventos publicados. */
    public function index(): void
    {
        if (!Auth::authorize(['admin', 'sensei'])) {
            $this->redirect('/login');
        }

        $this->view('admin/championships/index', [
            'championships' => Championship::all(onlyVisible: !Auth::isAdmin()),
            'pageTitle'     => 'Eventos',
        ]);
    }

    public function create(): void
    {
        $this->guardAdmin();

        $rulesets = array_values(array_filter((new RulesetRepository())->all(), static fn (array $r) => $r['is_active']));

        $this->view('admin/championships/form', [
            'championship'     => null,
            'agendaPosts'      => Championship::agendaPosts(),
            'rulesets'         => $rulesets,
            'defaultRulesetId' => array_values(array_column(array_filter($rulesets, static fn (array $r) => $r['is_default']), 'id'))[0] ?? null,
            'pageTitle'        => 'Novo Evento',
        ]);
    }

    public function store(): void
    {
        $this->guardAdmin();
        $this->guardCsrf('/fgkirs-admin/championships/create');

        if ($error = $this->validate($_POST, null)) {
            $_SESSION['error'] = $error;
            $this->redirect('/fgkirs-admin/championships/create');
        }

        $id = Championship::create($_POST, (int) Auth::id());

        $_SESSION['success'] = 'Evento criado! Agora gere as categorias a partir do regulamento.';
        $this->redirect("/fgkirs-admin/championships/$id/categories");
    }

    public function edit(int $id): void
    {
        $this->guardAdmin();
        $championship = $this->findOrRedirect($id);

        $this->view('admin/championships/form', [
            'championship'  => $championship,
            'agendaPosts'   => Championship::agendaPosts(),
            'rulesets'      => array_values(array_filter(
                (new RulesetRepository())->all(),
                static fn (array $r) => $r['is_active'] || (int) $r['id'] === (int) $championship['ruleset_id']
            )),
            'categoryCount' => (new EventCategoryRepository())->count($id),
            'totals'        => $this->totals($id),
            'pageTitle'     => 'Editar Evento',
        ]);
    }

    public function update(int $id): void
    {
        $this->guardAdmin();
        $this->guardCsrf("/fgkirs-admin/championships/edit/$id");
        $championship = $this->findOrRedirect($id);

        if ($error = $this->validate($_POST, $championship)) {
            $_SESSION['error'] = $error;
            $this->redirect("/fgkirs-admin/championships/edit/$id");
        }

        Championship::update($id, $_POST);

        $_SESSION['success'] = 'Evento atualizado com sucesso!';
        $this->redirect("/fgkirs-admin/championships/edit/$id");
    }

    public function delete(int $id): void
    {
        $this->guardAdmin();

        if (!Csrf::validate($_GET['token'] ?? null)) {
            $_SESSION['error'] = 'Link de exclusão inválido ou expirado.';
            $this->redirect('/fgkirs-admin/championships');
        }

        try {
            if (Championship::hasRegistrations($id)) {
                $_SESSION['error'] = 'Este evento tem inscrições e não pode ser excluído. Altere o status para Encerrado.';
                $this->redirect('/fgkirs-admin/championships');
            }

            Championship::delete($id);
        } catch (\PDOException $e) {
            error_log('ChampionshipController::delete error: ' . $e->getMessage());
            $_SESSION['error'] = 'Não foi possível excluir o evento. Ele possui dados vinculados.';
            $this->redirect('/fgkirs-admin/championships');
        }

        $_SESSION['success'] = 'Evento excluído com sucesso!';
        $this->redirect('/fgkirs-admin/championships');
    }

    private function totals(int $id): array
    {
        $totals = (new RegistrationRepository())->totals($id);

        return [
            'dojos'    => $totals['dojos'],
            'athletes' => $totals['athletes'],
            'teams'    => count((new EventTeamRepository())->forChampionship($id)),
            'referees' => Championship::refereeCount($id),
        ];
    }

    private function validate(array $data, ?array $championship): ?string
    {
        if (trim($data['title'] ?? '') === '') {
            return 'Informe o título do evento.';
        }

        if (empty($data['event_date'])) {
            return 'Informe a data da competição — ela define a idade dos atletas nas categorias.';
        }

        if (empty($data['registration_start']) || empty($data['registration_end'])) {
            return 'Informe o período de inscrições (início e fim).';
        }

        if (strtotime($data['registration_end']) <= strtotime($data['registration_start'])) {
            return 'O fim das inscrições precisa ser depois do início.';
        }

        if (empty($data['ruleset_id'])) {
            return 'Escolha o regulamento que gera as categorias do evento.';
        }

        if (($data['status'] ?? 'draft') !== 'draft') {
            if ($championship === null) {
                return 'Salve o evento como rascunho, gere as categorias e só então publique.';
            }

            if ((new EventCategoryRepository())->count((int) $championship['id']) === 0) {
                return 'Gere as categorias do evento antes de publicá-lo.';
            }
        }

        return null;
    }

    private function findOrRedirect(int $id): array
    {
        $championship = Championship::find($id);

        if (!$championship) {
            $_SESSION['error'] = 'Evento não encontrado.';
            $this->redirect('/fgkirs-admin/championships');
        }

        return $championship;
    }

    private function guardAdmin(): void
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
