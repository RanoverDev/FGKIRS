<?php

namespace Controllers\Admin;

use Controllers\Controller;
use Helpers\Auth;
use Helpers\Csrf;
use Models\Championship;
use Repositories\BracketRepository;
use Services\BracketService;
use Services\RegistrationException;

/**
 * Chaves e resultados do evento. Ver é liberado para quem enxerga o evento (admin e sensei); sortear,
 * trocar atletas e lançar resultado é só do Presidente, conferido no BracketService e não só na tela.
 * As telas conversam por JSON: cada ação devolve o estado novo da chave.
 */
class BracketController extends Controller
{
    private BracketService $service;
    private BracketRepository $repository;

    public function __construct()
    {
        $this->service    = new BracketService();
        $this->repository = new BracketRepository();
    }

    // ───────────────────────────── Telas ─────────────────────────────

    /** Aba Chaves: todas as categorias com o andamento de cada uma. */
    public function index(int $championshipId): void
    {
        $championship = $this->championship($championshipId);
        $ready        = $this->repository->tablesExist();

        $this->view('admin/brackets/index', [
            'championship' => $championship,
            'activeTab'    => 'brackets',
            'eventLevel'   => true,
            'ready'        => $ready,
            'overview'     => $ready ? $this->service->overview($championshipId) : [],
            'canEdit'      => Auth::isAdmin(),
            'pageTitle'    => $championship['title'] . ' – Chaves',
        ]);
    }

    /** A chave de uma categoria (a tela busca os dados em /data e reage por JSON). */
    public function show(int $championshipId, int $categoryId): void
    {
        $championship = $this->championship($championshipId);

        if (!$this->repository->tablesExist()) {
            $this->redirect("/fgkirs-admin/championships/$championshipId/brackets");
        }

        try {
            $state = $this->service->state($championship, $categoryId);
        } catch (\DomainException $e) {
            $_SESSION['error'] = $e->getMessage();
            $this->redirect("/fgkirs-admin/championships/$championshipId/brackets");
        }

        $this->view('admin/brackets/show', [
            'championship' => $championship,
            'activeTab'    => 'brackets',
            'eventLevel'   => true,
            'bracketState' => $state,
            'canEdit'      => Auth::isAdmin(),
            'pageTitle'    => $state['category']['shortName'] . ' – Chave',
        ]);
    }

    /** Aba Resultados: o pódio de cada categoria do evento. */
    public function results(int $championshipId): void
    {
        $championship = $this->championship($championshipId);
        $ready        = $this->repository->tablesExist();

        $this->view('admin/brackets/results', [
            'championship' => $championship,
            'activeTab'    => 'results',
            'eventLevel'   => true,
            'ready'        => $ready,
            'results'      => $ready ? $this->repository->resultsForChampionship($championshipId) : [],
            'pageTitle'    => $championship['title'] . ' – Resultados',
        ]);
    }

    // ───────────────────────────── JSON ─────────────────────────────

    public function data(int $championshipId, int $categoryId): void
    {
        $championship = $this->championship($championshipId);

        $this->respond(fn () => ['state' => $this->service->state($championship, $categoryId)], $championship, $categoryId);
    }

    public function generate(int $championshipId, int $categoryId): void
    {
        $championship = $this->writeContext($championshipId);

        $this->respond(function () use ($championship, $categoryId) {
            $this->service->generate($championship, $categoryId, (int) Auth::id(), $this->flag('reset'));
        }, $championship, $categoryId);
    }

    public function generateAll(int $championshipId): void
    {
        $championship = $this->writeContext($championshipId);

        try {
            $count = $this->service->generateAll($championship, (int) Auth::id());
        } catch (\DomainException | RegistrationException $e) {
            $this->json(['ok' => false, 'message' => $e->getMessage()], $e instanceof RegistrationException ? $e->httpStatus : 422);
        }

        $_SESSION['success'] = $count > 0 ? "$count chave(s) sorteada(s)." : 'Nenhuma categoria nova para sortear.';
        $this->json(['ok' => true, 'created' => $count]);
    }

    public function swap(int $championshipId, int $categoryId): void
    {
        $championship = $this->writeContext($championshipId);

        $this->respond(fn () => $this->service->swap(
            $championship, $categoryId, (int) ($_POST['slot_a'] ?? -1), (int) ($_POST['slot_b'] ?? -1), (int) Auth::id()
        ), $championship, $categoryId);
    }

    public function winner(int $championshipId, int $categoryId): void
    {
        $championship = $this->writeContext($championshipId);

        $this->respond(fn () => $this->service->setWinner(
            $championship, $categoryId, (int) ($_POST['round'] ?? 0), (int) ($_POST['position'] ?? -1),
            (int) ($_POST['winner'] ?? 0), $_POST['score_a'] ?? null, $_POST['score_b'] ?? null, (int) Auth::id()
        ), $championship, $categoryId);
    }

    public function clearWinner(int $championshipId, int $categoryId): void
    {
        $championship = $this->writeContext($championshipId);

        $this->respond(fn () => $this->service->clearWinner(
            $championship, $categoryId, (int) ($_POST['round'] ?? 0), (int) ($_POST['position'] ?? -1)
        ), $championship, $categoryId);
    }

    public function clearResults(int $championshipId, int $categoryId): void
    {
        $championship = $this->writeContext($championshipId);

        $this->respond(fn () => $this->service->clearResults($championship, $categoryId), $championship, $categoryId);
    }

    public function sync(int $championshipId, int $categoryId): void
    {
        $championship = $this->writeContext($championshipId);

        $this->respond(fn () => $this->service->sync($championship, $categoryId), $championship, $categoryId);
    }

    public function delete(int $championshipId, int $categoryId): void
    {
        $championship = $this->writeContext($championshipId);

        $this->respond(function () use ($championship, $categoryId) {
            $this->service->delete($championship, $categoryId, $this->flag('reset'));
        }, $championship, $categoryId);
    }

    public function move(int $championshipId, int $categoryId): void
    {
        $championship = $this->writeContext($championshipId);
        $warnings     = [];

        $this->respond(function () use ($championship, $categoryId, &$warnings) {
            $warnings = $this->service->moveParticipant(
                $championship, $categoryId, (int) ($_POST['registration_id'] ?? 0), (int) ($_POST['to_category_id'] ?? 0),
                (string) ($_POST['reason'] ?? ''), (int) Auth::id()
            );
        }, $championship, $categoryId, static function () use (&$warnings) {
            return ['warnings' => $warnings];
        });
    }

    // ───────────────────────────── Internos ─────────────────────────────

    /**
     * Executa a ação e devolve o estado novo da chave. Falha de regra vira mensagem 422 (sem gravar nada).
     *
     * @param callable(): mixed $work
     * @param (callable(): array)|null $extra campos a mais na resposta, avaliados depois da ação
     */
    private function respond(callable $work, array $championship, int $categoryId, ?callable $extra = null): void
    {
        try {
            $result = $work();
            $out    = is_array($result) && isset($result['state']) ? $result : ['state' => $this->service->state($championship, $categoryId)];
        } catch (RegistrationException $e) {
            $this->json(['ok' => false, 'message' => $e->getMessage()], $e->httpStatus);
        } catch (\DomainException $e) {
            $this->json(['ok' => false, 'message' => $e->getMessage()], 422);
        } catch (\PDOException $e) {
            error_log('BracketController error: ' . $e->getMessage());
            $this->json(['ok' => false, 'message' => 'Não foi possível concluir. Tente novamente.'], 500);
        }

        $this->json(['ok' => true] + $out + ($extra ? $extra() : []));
    }

    private function flag(string $name): bool
    {
        return ($_POST[$name] ?? '') === '1';
    }

    private function championship(int $id): array
    {
        if (!Auth::authorize(['admin', 'sensei'])) {
            $this->redirect('/login');
        }

        $championship = Championship::findForCurrentUser($id);

        if (!$championship) {
            $_SESSION['error'] = 'Evento não encontrado.';
            $this->redirect('/fgkirs-admin/championships');
        }

        return $championship;
    }

    /** Escrita: só POST com CSRF. A permissão de Presidente é conferida no serviço. */
    private function writeContext(int $championshipId): array
    {
        if (!Auth::authorize(['admin', 'sensei'])) {
            $this->json(['ok' => false, 'message' => 'Acesso negado.'], 403);
        }

        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $this->json(['ok' => false, 'message' => 'Sessão expirada. Recarregue a página e tente de novo.'], 419);
        }

        $championship = Championship::findForCurrentUser($championshipId);

        if (!$championship) {
            $this->json(['ok' => false, 'message' => 'Evento não encontrado.'], 404);
        }

        if (!$this->repository->tablesExist()) {
            $this->json(['ok' => false, 'message' => 'As tabelas de chaves ainda não existem. Rode o SQL 08_chaves.sql no phpMyAdmin.'], 503);
        }

        return $championship;
    }
}
