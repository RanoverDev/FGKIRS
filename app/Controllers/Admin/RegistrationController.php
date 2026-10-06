<?php

namespace Controllers\Admin;

use Controllers\Controller;
use Core\Database;
use Helpers\Auth;
use Helpers\Csrf;
use Helpers\DojoScope;
use Models\Championship;
use Models\FederationProfile;
use Models\ChampionshipReferee;
use PDO;
use Repositories\EventTeamRepository;
use Repositories\RegistrationRepository;
use Services\RegistrationException;
use Services\RegistrationService;
use Services\TeamService;

/**
 * Inscrição do dojo no modelo v2: grade do sensei, equipes e resumo. O dojo vem sempre da sessão
 * (DojoScope); prazo e escopo são aplicados nos serviços, este controller só traduz HTTP.
 */
class RegistrationController extends Controller
{
    private RegistrationService $registrations;
    private TeamService $teamService;

    public function __construct()
    {
        $this->registrations = new RegistrationService();
        $this->teamService   = new TeamService();
    }

    // ───────────── Telas (chamadas pelas abas do ChampionshipRegistrationController) ─────────────

    public function gridPage(array $championship, ?int $dojoId): void
    {
        $this->render('grid', $championship, $dojoId, 'athletes');
    }

    public function teamsPage(array $championship, ?int $dojoId): void
    {
        $teams = $dojoId ? (new EventTeamRepository())->forChampionship((int) $championship['id'], $dojoId) : [];

        // Categorias abertas (para montar equipe) e as que já têm equipe do dojo, mesmo fechadas ou de evento migrado
        $withTeams  = array_flip(array_map(static fn ($t) => (int) $t['event_category_id'], $teams));
        $categories = array_values(array_filter(
            $this->teamService->teamCategories((int) $championship['id'], true),
            static fn ($c) => $c->status === 'open' || isset($withTeams[$c->id])
        ));

        $eligible = [];
        if ($dojoId) {
            foreach ($categories as $category) {
                if ($category->status === 'open') {
                    $eligible[$category->id] = $this->teamService->eligibleMembers($championship, $dojoId, $category);
                }
            }
        }

        $this->render('teams_v2', $championship, $dojoId, 'teams', [
            'categories' => $categories,
            'teams'      => $teams,
            'eligible'   => $eligible,
        ]);
    }

    public function summaryPage(array $championship, ?int $dojoId): void
    {
        $repo = new RegistrationRepository();

        $this->render('summary_v2', $championship, $dojoId, 'summary', [
            'registrations' => $dojoId ? $repo->activeForDojo((int) $championship['id'], $dojoId) : [],
            'teams'         => $dojoId ? (new EventTeamRepository())->forChampionship((int) $championship['id'], $dojoId) : [],
        ]);
    }

    /** Ficha do dojo para impressão: o mesmo documento do modelo antigo, alimentado pelas inscrições novas. */
    public function printPage(array $championship, ?int $dojoId): void
    {
        if (!$dojoId) {
            DojoScope::deny('Selecione um dojo para gerar a versão para impressão.');
        }

        $registrations = (new RegistrationRepository())->forChampionship((int) $championship['id'], ['dojo_id' => $dojoId]);
        $registrations = array_values(array_filter($registrations, static fn (array $r) => $r['status'] !== 'cancelled'));

        $this->view('admin/championships/print', [
            'championship' => $championship,
            'profile'      => FederationProfile::get(),
            'scope'        => 'dojo',
            'dojo'         => $this->dojo($dojoId),
            'athletes'     => RegistrationPanelController::printAthletes($registrations),
            'teams'        => RegistrationPanelController::printTeams((new EventTeamRepository())->forChampionship((int) $championship['id'], $dojoId)),
            'referees'     => ChampionshipReferee::forDojo((int) $championship['id'], $dojoId),
        ]);
    }

    // ───────────────────────────── JSON da grade ─────────────────────────────

    /** GET /championships/{id}/grid */
    public function grid(int $championshipId): void
    {
        [$championship, $dojoId] = $this->context($championshipId);

        $this->guarded(fn () => $this->json($this->registrations->grid($championship, $this->requireDojo($dojoId))));
    }

    /** GET /championships/{id}/grid/suggest?athlete_id=&weight= */
    public function suggest(int $championshipId): void
    {
        [$championship, $dojoId] = $this->context($championshipId);

        $weight = trim((string) ($_GET['weight'] ?? ''));
        $weight = $weight === '' ? null : (float) str_replace(',', '.', $weight);

        $this->guarded(fn () => $this->json([
            'cells' => $this->registrations->suggestFor(
                $championship, $this->requireDojo($dojoId), (int) ($_GET['athlete_id'] ?? 0), $weight
            ),
        ]));
    }

    // ───────────────────────────── Escrita ─────────────────────────────

    /** POST /championships/{id}/grid/save: lote com as marcações da grade. */
    public function save(int $championshipId): void
    {
        [$championship, $dojoId] = $this->writeContext($championshipId);

        $this->guarded(function () use ($championship, $dojoId) {
            $result = $this->registrations->saveDraft(
                $championship, $this->requireDojo($dojoId), (array) ($_POST['selections'] ?? []), (int) Auth::id()
            );

            $this->json(['ok' => true] + $result);
        });
    }

    /** POST /championships/{id}/grid/submit */
    public function submit(int $championshipId): void
    {
        [$championship, $dojoId] = $this->writeContext($championshipId);

        $this->guarded(function () use ($championship, $dojoId) {
            $count = $this->registrations->submit($championship, $this->requireDojo($dojoId), (int) Auth::id());

            $this->json(['ok' => true, 'submitted' => $count]);
        });
    }

    /** POST /championships/{id}/registrations/{registrationId}/cancel */
    public function cancel(int $championshipId, int $registrationId): void
    {
        [$championship, $dojoId] = $this->writeContext($championshipId);

        try {
            $this->registrations->cancel($championship, $registrationId, (int) Auth::id());
        } catch (RegistrationException $e) {
            if ($this->wantsJson()) {
                $this->json(['ok' => false, 'errors' => $e->messages], $e->httpStatus);
            }

            $_SESSION['error'] = $e->getMessage();
            $this->redirect($this->tab($championshipId, 'summary', $dojoId));
        }

        if ($this->wantsJson()) {
            $this->json(['ok' => true]);
        }

        $_SESSION['success'] = 'Inscrição cancelada.';
        $this->redirect($this->tab($championshipId, 'summary', $dojoId));
    }

    public function storeTeam(int $championshipId): void
    {
        [$championship, $dojoId] = $this->writeContext($championshipId);

        try {
            $this->teamService->create(
                $championship,
                $this->requireDojo($dojoId),
                (int) ($_POST['category_id'] ?? 0),
                $_POST['name'] ?? null,
                (array) ($_POST['starters'] ?? []),
                (array) ($_POST['reserves'] ?? []),
                (int) Auth::id()
            );
            $_SESSION['success'] = 'Equipe criada com sucesso!';
        } catch (RegistrationException $e) {
            $_SESSION['error'] = implode(' ', $e->messages);
        }

        $this->redirect($this->tab($championshipId, 'teams', $dojoId));
    }

    public function cancelTeam(int $championshipId, int $teamId): void
    {
        [$championship, $dojoId] = $this->writeContext($championshipId);

        try {
            $this->teamService->cancel($championship, $teamId, (int) Auth::id());
            $_SESSION['success'] = 'Equipe cancelada.';
        } catch (RegistrationException $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        $this->redirect($this->tab($championshipId, 'teams', $dojoId));
    }

    // ───────────────────────────── Internos ─────────────────────────────

    private function context(int $championshipId): array
    {
        if (!Auth::authorize(['admin', 'sensei'])) {
            $this->redirect('/login');
        }

        $championship = Championship::findForCurrentUser($championshipId);

        if (!$championship || !Championship::isV2($championship)) {
            $_SESSION['error'] = 'Evento não encontrado.';
            $this->redirect('/fgkirs-admin/championships');
        }

        $requested = isset($_REQUEST['dojo_id']) && $_REQUEST['dojo_id'] !== '' ? (int) $_REQUEST['dojo_id'] : null;
        $dojoId    = DojoScope::resolveDojoId($requested);

        if ($dojoId === null && !Auth::isAdmin()) {
            DojoScope::deny('Seu usuário não está vinculado a nenhum dojo. Fale com a federação.');
        }

        return [$championship, $dojoId];
    }

    /** Escrita exige CSRF. Prazo e escopo são conferidos pelos serviços. */
    private function writeContext(int $championshipId): array
    {
        [$championship, $dojoId] = $this->context($championshipId);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::validate($_POST['csrf_token'] ?? null)) {
            if ($this->wantsJson()) {
                $this->json(['ok' => false, 'errors' => ['Sessão expirada. Recarregue a página e tente de novo.']], 419);
            }

            $_SESSION['error'] = 'Sessão expirada. Tente novamente.';
            $this->redirect($this->tab($championshipId, 'athletes', $dojoId));
        }

        return [$championship, $dojoId];
    }

    private function requireDojo(?int $dojoId): int
    {
        if ($dojoId === null) {
            throw RegistrationException::single('Selecione um dojo antes de continuar.');
        }

        return $dojoId;
    }

    /** Traduz a falha de regra em resposta JSON (a grade usa fetch). */
    private function guarded(callable $work): void
    {
        try {
            $work();
        } catch (RegistrationException $e) {
            $this->json(['ok' => false, 'errors' => $e->messages], $e->httpStatus);
        }
    }

    private function wantsJson(): bool
    {
        return str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
            || ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    }

    private function render(string $view, array $championship, ?int $dojoId, string $tab, array $data = []): void
    {
        $this->view("admin/championships/$view", $data + [
            'championship' => $championship,
            'dojoId'       => $dojoId,
            'dojo'         => $dojoId ? $this->dojo($dojoId) : null,
            'dojos'        => Auth::isAdmin() ? $this->dojos() : [],
            'activeTab'    => $tab,
            'canWrite'     => Auth::isAdmin() || Championship::isRegistrationOpen($championship),
            'pageTitle'    => $championship['title'],
        ]);
    }

    private function tab(int $championshipId, string $tab, ?int $dojoId): string
    {
        $url = "/fgkirs-admin/championships/$championshipId/$tab";

        return $dojoId && Auth::isAdmin() ? "$url?dojo_id=$dojoId" : $url;
    }

    private function dojo(int $dojoId): ?array
    {
        $row = Database::getInstance()->query("SELECT id, name, city FROM dojos WHERE id = :id", ['id' => $dojoId])->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    private function dojos(): array
    {
        return Database::getInstance()->query("SELECT id, name, city FROM dojos ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    }
}
