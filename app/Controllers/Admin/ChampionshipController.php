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

        $this->view('admin/championships/form', [
            'championship' => null,
            'agendaPosts'  => Championship::agendaPosts(),
            'catalog'      => CompetitionCategory::all(['is_active' => 1]),
            'enabledIds'   => null,
            'pageTitle'    => 'Novo Evento',
        ]);
    }

    public function store(): void
    {
        $this->guardAdmin();
        $this->guardCsrf('/fgkirs-admin/championships/create');

        if ($error = $this->validate($_POST)) {
            $_SESSION['error'] = $error;
            $this->redirect('/fgkirs-admin/championships/create');
        }

        $id = Championship::create($_POST, (int) Auth::id());
        Championship::syncCategories($id, (array) ($_POST['category_ids'] ?? []));

        $_SESSION['success'] = 'Evento criado com sucesso!';
        $this->redirect("/fgkirs-admin/championships/edit/$id");
    }

    public function edit(int $id): void
    {
        $this->guardAdmin();

        $championship = Championship::find($id);

        if (!$championship) {
            $_SESSION['error'] = 'Evento não encontrado.';
            $this->redirect('/fgkirs-admin/championships');
        }

        $this->view('admin/championships/form', [
            'championship' => $championship,
            'agendaPosts'  => Championship::agendaPosts(),
            'catalog'      => CompetitionCategory::all(['is_active' => 1]),
            'enabledIds'   => Championship::categoryIds($id),
            'totals'       => Championship::totals($id),
            'pageTitle'    => 'Editar Evento',
        ]);
    }

    public function update(int $id): void
    {
        $this->guardAdmin();
        $this->guardCsrf("/fgkirs-admin/championships/edit/$id");

        if (!Championship::find($id)) {
            $_SESSION['error'] = 'Evento não encontrado.';
            $this->redirect('/fgkirs-admin/championships');
        }

        if ($error = $this->validate($_POST)) {
            $_SESSION['error'] = $error;
            $this->redirect("/fgkirs-admin/championships/edit/$id");
        }

        Championship::update($id, $_POST);
        $kept = Championship::syncCategories($id, (array) ($_POST['category_ids'] ?? []));

        $_SESSION['success'] = 'Evento atualizado com sucesso!';

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

        Championship::delete($id);

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

    private function validate(array $data): ?string
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

        if (($data['status'] ?? 'draft') !== 'draft' && empty($data['category_ids'])) {
            return 'Libere ao menos uma categoria para publicar o evento.';
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
