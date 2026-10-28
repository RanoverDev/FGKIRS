<?php

namespace Controllers\Admin;

use Controllers\Controller;
use Core\Database;
use Helpers\Auth;
use Helpers\Csrf;
use Models\Championship;
use Models\ChampionshipReferee;
use Models\FederationProfile;
use PDO;
use Repositories\EventCategoryRepository;
use Repositories\EventTeamRepository;
use Repositories\RegistrationRepository;
use Services\RegistrationException;
use Services\RegistrationService;

/** Painel do Presidente para eventos v2: inscritos por categoria e dojo, confirmação em lote, CSV e impressão. */
class RegistrationPanelController extends Controller
{
    private RegistrationRepository $registrations;

    public function __construct()
    {
        $this->registrations = new RegistrationRepository();
    }

    public function index(int $id): void
    {
        $championship = $this->championship($id);
        $filters      = $this->filters();

        $this->view('admin/championships/registrations', [
            'championship'  => $championship,
            'filters'       => $filters,
            'registrations' => $this->registrations->forChampionship($id, $filters),
            'teams'         => $this->filteredTeams($id, $filters),
            'categories'    => (new EventCategoryRepository())->rows($id),
            'dojos'         => $this->dojos(),
            'totals'        => $this->registrations->totals($id),
            'referees'      => Championship::refereeCount($id),
            'pageTitle'     => 'Inscritos – ' . $championship['title'],
        ]);
    }

    /** As equipes seguem os filtros de dojo e de status; por categoria individual não se aplica. */
    private function filteredTeams(int $id, array $filters): array
    {
        $teams = (new EventTeamRepository())->forChampionship($id, $filters['dojo_id'] ?: null);

        if ($filters['category_id']) {
            return [];
        }

        return $filters['status']
            ? array_values(array_filter($teams, static fn (array $t) => $t['status'] === $filters['status']))
            : $teams;
    }

    public function printSheet(int $id): void
    {
        $championship = $this->championship($id);

        $registrations = array_values(array_filter(
            $this->registrations->forChampionship($id),
            static fn (array $r) => $r['status'] !== 'cancelled'
        ));

        $this->view('admin/championships/print', [
            'championship' => $championship,
            'profile'      => FederationProfile::get(),
            'scope'        => 'federation',
            'dojo'         => null,
            'athletes'     => self::printAthletes($registrations),
            'teams'        => self::printTeams((new EventTeamRepository())->forChampionship($id)),
            'referees'     => ChampionshipReferee::forChampionship($id),
        ]);
    }

    public function confirm(int $championshipId): void
    {
        $championship = $this->championship($championshipId);
        $this->guardCsrf($championshipId);

        try {
            $count = (new RegistrationService())->confirm(
                $championship, (array) ($_POST['registrations'] ?? []), (array) ($_POST['teams'] ?? []), (int) Auth::id()
            );
            $_SESSION['success'] = $count . ' inscrição(ões) confirmada(s).';
        } catch (RegistrationException $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        $this->redirect($this->back($championshipId));
    }

    public function override(int $championshipId, int $registrationId): void
    {
        $championship = $this->championship($championshipId);
        $this->guardCsrf($championshipId);

        try {
            (new RegistrationService())->override(
                $championship, $registrationId, (int) ($_POST['category_id'] ?? 0), (string) ($_POST['reason'] ?? ''), (int) Auth::id()
            );
            $_SESSION['success'] = 'Exceção registrada.';
        } catch (RegistrationException $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        $this->redirect($this->back($championshipId));
    }

    public function log(int $championshipId, int $registrationId): void
    {
        $this->championship($championshipId);

        $registration = $this->registrations->find($registrationId);
        if (!$registration || (int) $registration['championship_id'] !== $championshipId) {
            $this->json(['error' => 'Inscrição não encontrada.'], 404);
        }

        $this->json(['log' => $this->registrations->logFor($registrationId)]);
    }

    /** CSV para Excel brasileiro: separador ";" e BOM UTF-8. */
    public function export(int $championshipId): void
    {
        $championship = $this->championship($championshipId);

        $rows = $this->registrations->forChampionship($championshipId, $this->filters());

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="inscritos-' . (int) $championship['id'] . '.csv"');
        echo "\xEF\xBB\xBF";

        $out = fopen('php://output', 'w');
        fputcsv($out, ['Dojo', 'Atleta', 'Nascimento', 'Idade no evento', 'Sexo', 'Graduação', 'Peso (kg)', 'Altura (cm)', 'Categorias', 'Status', 'Taxa (R$)', 'Pago em'], ';');

        $labels = ['draft' => 'Rascunho', 'submitted' => 'Enviada', 'confirmed' => 'Confirmada', 'cancelled' => 'Cancelada'];

        foreach ($rows as $r) {
            fputcsv($out, [
                $r['dojo_name'],
                self::csvSafe($r['full_name']),
                date('d/m/Y', strtotime($r['birth_date'])),
                $r['age_at_event'],
                $r['gender'],
                $r['belt_name'] ?? '',
                $r['declared_weight'] !== null ? number_format((float) $r['declared_weight'], 1, ',', '') : '',
                $r['declared_height'] ?? '',
                self::csvSafe(implode(' | ', array_column($r['entries'], 'category_name'))),
                $labels[$r['status']] ?? $r['status'],
                $r['fee_total'] !== null ? number_format((float) $r['fee_total'], 2, ',', '') : '',
                $r['paid_at'] ? date('d/m/Y H:i', strtotime($r['paid_at'])) : '',
            ], ';');
        }

        fclose($out);
        exit;
    }

    /** Adapta as inscrições ao formato que a ficha impressa (print.php) já espera. */
    public static function printAthletes(array $registrations): array
    {
        return array_map(static fn (array $r) => [
            'name'           => $r['full_name'],
            'age'            => $r['age_at_event'],
            'birth_date'     => $r['birth_date'],
            'gender'         => $r['gender'],
            'is_para_karate' => $r['is_para_karate'],
            'is_guest'       => $r['is_guest'] ?? 0,
            'guest_origin'   => $r['guest_origin'] ?? null,
            'belt_name'      => $r['belt_name'] ?? null,
            'weight'         => $r['declared_weight'],
            'height'         => $r['declared_height'] ?? null,
            'dojo_name'      => $r['dojo_name'] ?? '',
            'entries'        => array_map(static fn (array $e) => ['category_name' => $e['category_name']], $r['entries']),
        ], $registrations);
    }

    public static function printTeams(array $teams): array
    {
        return array_map(static fn (array $t) => [
            'name'          => $t['name'],
            'category_name' => $t['category_name'],
            'dojo_name'     => $t['dojo_name'],
            'members'       => array_map(static fn (array $m) => ['name' => $m['name']], $t['members']),
        ], $teams);
    }

    /** Nome ou categoria começando com =, +, - ou @ viraria fórmula no Excel. */
    private static function csvSafe(string $value): string
    {
        return preg_match('/^[=+\-@\t\r]/', $value) ? "'" . $value : $value;
    }

    private function filters(): array
    {
        $status = $_GET['status'] ?? '';

        return [
            'status'      => in_array($status, ['draft', 'submitted', 'confirmed', 'cancelled'], true) ? $status : '',
            'dojo_id'     => (int) ($_GET['dojo_id'] ?? 0),
            'category_id' => (int) ($_GET['category_id'] ?? 0),
        ];
    }

    private function championship(int $id): array
    {
        $this->guard();
        $championship = Championship::find($id);

        if (!$championship) {
            $_SESSION['error'] = 'Evento não encontrado.';
            $this->redirect('/fgkirs-admin/championships');
        }

        return $championship;
    }

    private function back(int $championshipId): string
    {
        return "/fgkirs-admin/championships/$championshipId/registrations";
    }

    private function guard(): void
    {
        if (!Auth::isAdmin()) {
            $this->redirect('/fgkirs-admin');
        }
    }

    private function guardCsrf(int $championshipId): void
    {
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $_SESSION['error'] = 'Sessão expirada. Tente novamente.';
            $this->redirect($this->back($championshipId));
        }
    }

    private function dojos(): array
    {
        return Database::getInstance()->query("SELECT id, name FROM dojos ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    }
}
