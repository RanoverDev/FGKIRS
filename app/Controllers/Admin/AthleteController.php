<?php

namespace Controllers\Admin;

use Controllers\Controller;
use Core\Database;
use Helpers\Auth;
use Helpers\AthletePhoto;
use Helpers\Csrf;
use Helpers\DojoScope;
use Models\Athlete;
use PDO;
use Repositories\CertificateRepository;

/**
 * Cadastro permanente de atletas do dojo. O sensei só enxerga e edita os do próprio
 * dojo (resolvido pela sessão); o admin vê todos e escolhe o dojo.
 */
class AthleteController extends Controller
{
    public function index(): void
    {
        $this->guard();

        $filters = [
            'q'             => trim($_GET['q'] ?? ''),
            'graduation_id' => (int) ($_GET['graduation_id'] ?? 0),
            'status'        => $_GET['status'] ?? '',
            'sort'          => in_array($_GET['sort'] ?? '', Athlete::SORTABLE, true) ? $_GET['sort'] : 'name',
            'dir'           => ($_GET['dir'] ?? '') === 'desc' ? 'desc' : 'asc',
        ];

        $filters['dojo_id'] = Auth::isAdmin()
            ? (int) ($_GET['dojo_id'] ?? 0)
            : $this->dojoIdOrDeny();

        $this->view('admin/athletes/index', [
            'athletes'    => Athlete::search($filters),
            'filters'     => $filters,
            'dojos'       => Auth::isAdmin() ? $this->dojos() : [],
            'graduations' => $this->graduations(),
            'pageTitle'   => 'Atletas',
        ]);
    }

    public function create(): void
    {
        $this->guard();

        $this->view('admin/athletes/form', [
            'athlete'     => $_SESSION['athlete_old'] ?? null,
            'dojos'       => Auth::isAdmin() ? $this->dojos() : [],
            'styles'      => $this->styles(),
            'graduations' => $this->graduations(),
            'pageTitle'   => 'Novo Atleta',
        ]);
        unset($_SESSION['athlete_old']);
    }

    public function store(): void
    {
        $this->guard();
        $this->guardCsrf('/fgkirs-admin/athletes/create');

        $dojoId = $this->dojoIdFromRequest('/fgkirs-admin/athletes/create');

        [$data, $error] = Athlete::validate($_POST);
        $error ??= AthletePhoto::validate($_FILES['photo'] ?? null);
        if ($error !== null) {
            $this->failBack($error, '/fgkirs-admin/athletes/create');
        }

        try {
            $id = Athlete::create($dojoId, (int) Auth::id(), $data);
        } catch (\PDOException $e) {
            error_log('AthleteController::store error: ' . $e->getMessage());
            $this->failBack('Não foi possível salvar o atleta. Tente novamente.', '/fgkirs-admin/athletes/create');
        }

        $this->succeed('Atleta cadastrado com sucesso!', '/fgkirs-admin/athletes', $this->applyPhoto($id, null), ['id' => $id]);
    }

    public function edit(int $id): void
    {
        $this->guard();
        $athlete = $this->findScoped($id);

        $this->view('admin/athletes/form', [
            'athlete'     => $_SESSION['athlete_old'] ?? $athlete,
            'dojos'       => Auth::isAdmin() ? $this->dojos() : [],
            'styles'      => $this->styles(),
            'graduations' => $this->graduations(),
            'certificates' => (new CertificateRepository())->forAthlete($id),
            'pageTitle'   => 'Editar Atleta',
        ]);
        unset($_SESSION['athlete_old']);
    }

    public function update(int $id): void
    {
        $this->guard();
        $this->guardCsrf("/fgkirs-admin/athletes/edit/$id");
        $current = $this->findScoped($id);

        $dojoId = Auth::isAdmin() ? $this->dojoIdFromRequest("/fgkirs-admin/athletes/edit/$id") : null;

        [$data, $error] = Athlete::validate($_POST, $id);
        $error ??= AthletePhoto::validate($_FILES['photo'] ?? null);
        if ($error !== null) {
            $this->failBack($error, "/fgkirs-admin/athletes/edit/$id", ['id' => $id]);
        }

        try {
            Athlete::update($id, $dojoId, $data);
        } catch (\PDOException $e) {
            error_log('AthleteController::update error: ' . $e->getMessage());
            $this->failBack('Não foi possível salvar o atleta. Tente novamente.', "/fgkirs-admin/athletes/edit/$id", ['id' => $id]);
        }

        $warning = $this->applyPhoto($id, $current['photo'] ?? null, ($_POST['remove_photo'] ?? '') === '1');

        $this->succeed('Atleta atualizado com sucesso!', '/fgkirs-admin/athletes', $warning, ['id' => $id]);
    }

    /** HTML do formulário de atleta para o modal (cadastro sem id, edição com id). */
    public function fragmentForm(?int $id = null): void
    {
        $this->guard();

        $athlete = $id === null ? null : $this->findScopedFragment($id);

        // Cadastro vindo da busca da grade: já traz o nome digitado e, se for o caso, a marca de convidado
        if ($id === null && (($_GET['name'] ?? '') !== '' || ($_GET['guest'] ?? '') === '1')) {
            $athlete = [
                'full_name' => mb_substr(trim((string) ($_GET['name'] ?? '')), 0, 255),
                'is_guest'  => ($_GET['guest'] ?? '') === '1' ? 1 : 0,
            ];
        }

        $this->view('admin/athletes/fragments/form', [
            'athlete'        => $athlete,
            // Cadastro dentro de um evento: o Presidente já está operando como um dojo, que vem pré-selecionado
            'defaultDojoId'  => Auth::isAdmin() ? (int) ($_GET['dojo_id'] ?? 0) : null,
            'dojos'       => Auth::isAdmin() ? $this->dojos() : [],
            'styles'      => $this->styles(),
            'graduations' => $this->graduations(),
        ]);
    }

    /** HTML da ficha completa do atleta (dados, status e certificados) para o modal. */
    public function fragmentView(int $id): void
    {
        $this->guard();
        $athlete = $this->findScopedFragment($id);

        $this->view('admin/athletes/fragments/view', [
            'athlete'      => $athlete,
            'certificates' => (new CertificateRepository())->forAthlete($id),
        ]);
    }

    /**
     * Atleta não é excluído: o selo de status liga e desliga. Inativo vira ativo; ativo e pendente
     * seguem o caminho contrário do selo (ativo → inativo, pendente → ativo).
     */
    public function toggleStatus(int $id): void
    {
        $this->guard();

        $back = $this->listUrl((string) ($_POST['query'] ?? ''));
        $this->guardCsrf($back);

        $athlete = $this->findScoped($id);
        $next    = $athlete['status'] === 'active' ? 'inactive' : 'active';
        Athlete::setStatus($id, $next);

        $this->succeed($next === 'active' ? 'Atleta ativado.' : 'Atleta desativado.', $back);
    }

    /** Volta para a lista mantendo filtros e ordenação, aceitando só as chaves conhecidas. */
    private function listUrl(string $rawQuery): string
    {
        parse_str($rawQuery, $query);
        $query = array_intersect_key($query, array_flip(['q', 'graduation_id', 'status', 'dojo_id', 'sort', 'dir']));
        $query = array_filter($query, fn ($v) => is_scalar($v) && $v !== '');

        return '/fgkirs-admin/athletes' . ($query ? '?' . http_build_query($query) : '');
    }

    private function guard(): void
    {
        if (!Auth::authorize(['admin', 'sensei'])) {
            $this->redirect('/fgkirs-admin');
        }
    }

    private function guardCsrf(string $back): void
    {
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            if ($this->wantsJson()) {
                $this->json(['ok' => false, 'message' => 'Sessão expirada. Recarregue a página e tente de novo.'], 419);
            }

            $_SESSION['error'] = 'Sessão expirada. Tente novamente.';
            $this->redirect($back);
        }
    }

    /** Modais chamam via fetch pedindo JSON; o fluxo de página inteira continua redirecionando. */
    private function wantsJson(): bool
    {
        return str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
    }

    /** Guarda a mensagem para a próxima tela (que o modal recarrega) e responde. */
    private function succeed(string $message, string $to, ?string $warning = null, array $payload = []): void
    {
        $_SESSION['success'] = $message;
        if ($warning !== null) {
            $_SESSION['error'] = $warning;
        }

        if ($this->wantsJson()) {
            $this->json(['ok' => true] + $payload);
        }

        $this->redirect($to);
    }

    /** Grava, troca ou remove a foto depois de o cadastro estar salvo. Devolve um aviso se a foto falhar. */
    private function applyPhoto(int $id, ?string $current, bool $remove = false): ?string
    {
        $file = $_FILES['photo'] ?? null;

        try {
            if (AthletePhoto::hasUpload($file)) {
                $filename = AthletePhoto::store($file);
                if ($filename === null) {
                    return 'Os dados foram salvos, mas não foi possível processar a foto.';
                }

                Athlete::setPhoto($id, $filename);
                AthletePhoto::delete($current);
            } elseif ($remove && $current) {
                Athlete::setPhoto($id, null);
                AthletePhoto::delete($current);
            }
        } catch (\PDOException $e) {
            error_log('AthleteController::applyPhoto error: ' . $e->getMessage());

            return 'Os dados foram salvos, mas não foi possível gravar a foto.';
        }

        return null;
    }

    private function dojoIdOrDeny(): int
    {
        $dojoId = DojoScope::resolveDojoId();

        if ($dojoId === null) {
            DojoScope::deny('Seu usuário não está vinculado a nenhum dojo.');
        }

        return $dojoId;
    }

    /** Sensei: sempre o dojo da sessão. Admin: o dojo escolhido, que precisa existir. */
    private function dojoIdFromRequest(string $back): int
    {
        if (!Auth::isAdmin()) {
            return $this->dojoIdOrDeny();
        }

        $dojoId = (int) ($_POST['dojo_id'] ?? 0);
        $exists = $dojoId > 0 && Database::getInstance()
            ->query('SELECT 1 FROM dojos WHERE id = :id', ['id' => $dojoId])->fetchColumn();

        if (!$exists) {
            $this->failBack('Selecione o dojo do atleta.', $back);
        }

        return $dojoId;
    }

    /**
     * Atleta de outro dojo (ou inexistente, para o sensei) recebe 403 sem confirmar
     * que o registro existe.
     */
    private function findScoped(int $id): array
    {
        $athlete = Athlete::find($id);

        if (!$athlete) {
            if (Auth::isAdmin() && !$this->wantsJson()) {
                $_SESSION['error'] = 'Atleta não encontrado.';
                $this->redirect('/fgkirs-admin/athletes');
            }
            DojoScope::deny();
        }

        DojoScope::assertDojo((int) $athlete['dojo_id']);

        return $athlete;
    }

    /** Mesma regra do findScoped, mas a recusa é um trecho de HTML pequeno (cabe dentro do modal). */
    private function findScopedFragment(int $id): array
    {
        $athlete = Athlete::find($id);
        $dojoId  = DojoScope::senseiDojoId();

        if (!$athlete || (!Auth::isAdmin() && ($dojoId === null || (int) $athlete['dojo_id'] !== $dojoId))) {
            http_response_code(Auth::isAdmin() ? 404 : 403);
            echo '<p class="p-6 text-sm text-red-700">Você não tem permissão para acessar este atleta.</p>';
            exit;
        }

        return $athlete;
    }

    /** Volta ao formulário preservando o que o usuário digitou. */
    private function failBack(string $message, string $back, array $extra = []): void
    {
        if ($this->wantsJson()) {
            $this->json(['ok' => false, 'message' => $message], 422);
        }

        $_SESSION['error']       = $message;
        $_SESSION['athlete_old'] = array_merge($_POST, $extra);
        $this->redirect($back);
    }

    private function dojos(): array
    {
        return Database::getInstance()->query('SELECT id, name FROM dojos ORDER BY name ASC')
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    private function styles(): array
    {
        return Database::getInstance()->query('SELECT id, name FROM martial_arts_styles ORDER BY name ASC')
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    private function graduations(): array
    {
        return Database::getInstance()->query(
            "SELECT g.id, g.belt_name, g.style_id, s.name AS style_name
             FROM graduations g
             JOIN martial_arts_styles s ON s.id = g.style_id
             ORDER BY s.name ASC, g.order_rank ASC"
        )->fetchAll(PDO::FETCH_ASSOC);
    }
}
