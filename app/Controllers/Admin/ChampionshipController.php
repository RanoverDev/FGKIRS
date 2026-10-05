<?php

namespace Controllers\Admin;

use Controllers\Controller;
use Helpers\Auth;
use Helpers\Csrf;
use Models\Championship;
use Models\ChampionshipAthlete;
use Models\ChampionshipReferee;
use Models\ChampionshipTeam;
use Models\CompetitionCategory;
use Models\FederationProfile;
use Repositories\EventCategoryRepository;
use Repositories\EventTeamRepository;
use Repositories\RegistrationRepository;
use Repositories\RulesetRepository;

/**
 * Campeonatos: criacao pelo Presidente e painel consolidado de inscritos.
 *
 * A criacao e sempre centralizada no Presidente, mesmo quando o evento e
 * organizado por um dojo de outra cidade. Dojos nao criam eventos.
 */
class ChampionshipController extends Controller
{
    /** Lista visivel aos dois perfis: o sensei so enxerga eventos publicados. */
    public function index(): void
    {
        if (!Auth::authorize(['admin', 'sensei'])) {
            $this->redirect('/login');
        }

        $championships = Championship::all(onlyVisible: !Auth::isAdmin());

        $this->view('admin/championships/index', [
            'championships' => $championships,
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

        // Evento novo nasce no modelo v2: as categorias vêm do regulamento, na tela de categorias
        $id = Championship::create($_POST, (int) Auth::id());

        $_SESSION['success'] = 'Evento criado! Agora gere as categorias a partir do regulamento.';
        $this->redirect("/fgkirs-admin/championships/$id/categories");
    }

    public function edit(int $id): void
    {
        $this->guardAdmin();

        $championship = Championship::find($id);

        if (!$championship) {
            $_SESSION['error'] = 'Evento não encontrado.';
            $this->redirect('/fgkirs-admin/championships');
        }

        $data = [
            'championship' => $championship,
            'agendaPosts'  => Championship::agendaPosts(),
            'pageTitle'    => 'Editar Evento',
        ];

        if (Championship::isV2($championship)) {
            $data += [
                'rulesets'      => array_values(array_filter((new RulesetRepository())->all(), static fn (array $r) => $r['is_active'] || (int) $r['id'] === (int) $championship['ruleset_id'])),
                'categoryCount' => (new EventCategoryRepository())->count($id),
                'totals'        => $this->totalsV2($id),
            ];
        } else {
            $data += [
                'catalog'    => CompetitionCategory::all(['is_active' => 1]),
                'enabledIds' => Championship::categoryIds($id),
                'totals'     => Championship::totals($id),
            ];
        }

        $this->view('admin/championships/form', $data);
    }

    public function update(int $id): void
    {
        $this->guardAdmin();
        $this->guardCsrf("/fgkirs-admin/championships/edit/$id");

        $championship = Championship::find($id);

        if (!$championship) {
            $_SESSION['error'] = 'Evento não encontrado.';
            $this->redirect('/fgkirs-admin/championships');
        }

        if ($error = $this->validate($_POST, $championship)) {
            $_SESSION['error'] = $error;
            $this->redirect("/fgkirs-admin/championships/edit/$id");
        }

        Championship::update($id, $_POST);

        $_SESSION['success'] = 'Evento atualizado com sucesso!';

        if (Championship::isV2($championship)) {
            $this->redirect("/fgkirs-admin/championships/edit/$id");
        }

        $kept = Championship::syncCategories($id, (array) ($_POST['category_ids'] ?? []));

        if ($kept) {
            $_SESSION['error'] = 'Estas categorias continuam liberadas porque já têm inscritos: '
                . implode('; ', $kept) . '. Remova as inscrições antes de retirá-las.';
        }

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

    /** Painel do Presidente: todos os inscritos, agrupados por dojo. */
    public function registrations(int $id): void
    {
        $this->guardAdmin();

        $championship = Championship::find($id);

        if (!$championship) {
            $_SESSION['error'] = 'Evento não encontrado.';
            $this->redirect('/fgkirs-admin/championships');
        }

        if (Championship::isV2($championship)) {
            (new RegistrationPanelController())->index($championship);
            return;
        }

        $this->view('admin/championships/registrations', [
            'championship' => $championship,
            'totals'       => Championship::totals($id),
            'dojoTotals'   => Championship::totalsByDojo($id),
            'athletes'     => ChampionshipAthlete::forChampionship($id),
            'teams'        => ChampionshipTeam::forChampionship($id),
            'referees'     => ChampionshipReferee::forChampionship($id),
            'styles'       => ChampionshipAthlete::styleOptions(),
            'graduations'  => ChampionshipAthlete::graduationOptions(),
            'pageTitle'    => 'Inscritos – ' . $championship['title'],
        ]);
    }

    /** Versao para impressao do consolidado geral. */
    public function registrationsPrint(int $id): void
    {
        $this->guardAdmin();

        $championship = Championship::find($id);

        if (!$championship) {
            $this->redirect('/fgkirs-admin/championships');
        }

        if (Championship::isV2($championship)) {
            (new RegistrationPanelController())->printSheet($championship);
            return;
        }

        $this->view('admin/championships/print', [
            'championship' => $championship,
            'profile'      => FederationProfile::get(),
            'scope'        => 'federation',
            'dojo'         => null,
            'athletes'     => ChampionshipAthlete::forChampionship($id),
            'teams'        => ChampionshipTeam::forChampionship($id),
            'referees'     => ChampionshipReferee::forChampionship($id),
        ]);
    }

    private function totalsV2(int $id): array
    {
        $totals = (new RegistrationRepository())->totals($id);

        return [
            'dojos'    => $totals['dojos'],
            'athletes' => $totals['athletes'],
            'teams'    => count((new EventTeamRepository())->forChampionship($id)),
            'referees' => Championship::totals($id)['referees'],
        ];
    }

    /** @param array|null $championship evento já existente (null na criação, que sempre é v2) */
    private function validate(array $data, ?array $championship): ?string
    {
        $isV2 = $championship === null || Championship::isV2($championship);

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

        if ($isV2 && empty($data['ruleset_id'])) {
            return 'Escolha o regulamento que gera as categorias do evento.';
        }

        $published = ($data['status'] ?? 'draft') !== 'draft';

        if ($published && !$isV2 && empty($data['category_ids'])) {
            return 'Libere ao menos uma categoria para publicar o evento.';
        }

        if ($published && $isV2 && $championship !== null
            && (new EventCategoryRepository())->count((int) $championship['id']) === 0) {
            return 'Gere as categorias do evento antes de publicá-lo.';
        }

        if ($published && $isV2 && $championship === null) {
            return 'Salve o evento como rascunho, gere as categorias e só então publique.';
        }

        return null;
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
