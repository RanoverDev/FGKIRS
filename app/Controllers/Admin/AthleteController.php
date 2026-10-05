<?php

namespace Controllers\Admin;

use Controllers\Controller;
use Core\Database;
use Helpers\Auth;
use Helpers\Csrf;
use Helpers\DojoScope;
use Models\Athlete;
use PDO;

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
        if ($error !== null) {
            $this->failBack($error, '/fgkirs-admin/athletes/create');
        }

        try {
            Athlete::create($dojoId, (int) Auth::id(), $data);
        } catch (\PDOException $e) {
            error_log('AthleteController::store error: ' . $e->getMessage());
            $this->failBack('Não foi possível salvar o atleta. Tente novamente.', '/fgkirs-admin/athletes/create');
        }

        $_SESSION['success'] = 'Atleta cadastrado com sucesso!';
        $this->redirect('/fgkirs-admin/athletes');
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
            'pageTitle'   => 'Editar Atleta',
        ]);
        unset($_SESSION['athlete_old']);
    }

    public function update(int $id): void
    {
        $this->guard();
        $this->guardCsrf("/fgkirs-admin/athletes/edit/$id");
        $this->findScoped($id);

        $dojoId = Auth::isAdmin() ? $this->dojoIdFromRequest("/fgkirs-admin/athletes/edit/$id") : null;

        [$data, $error] = Athlete::validate($_POST, $id);
        if ($error !== null) {
            $this->failBack($error, "/fgkirs-admin/athletes/edit/$id", ['id' => $id]);
        }

        try {
            Athlete::update($id, $dojoId, $data);
        } catch (\PDOException $e) {
            error_log('AthleteController::update error: ' . $e->getMessage());
            $this->failBack('Não foi possível salvar o atleta. Tente novamente.', "/fgkirs-admin/athletes/edit/$id", ['id' => $id]);
        }

        $_SESSION['success'] = 'Atleta atualizado com sucesso!';
        $this->redirect('/fgkirs-admin/athletes');
    }

    /** Atleta não é excluído: sai de circulação mudando o status. Para reativar, editar e escolher "Ativo". */
    public function deactivate(int $id): void
    {
        $this->guard();

        if (!Csrf::validate($_GET['token'] ?? null)) {
            $_SESSION['error'] = 'Link inválido ou expirado.';
            $this->redirect('/fgkirs-admin/athletes');
        }

        $this->findScoped($id);
        Athlete::setStatus($id, 'inactive');

        $_SESSION['success'] = 'Atleta desativado.';
        $this->redirect('/fgkirs-admin/athletes');
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
            $_SESSION['error'] = 'Sessão expirada. Tente novamente.';
            $this->redirect($back);
        }
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
            if (Auth::isAdmin()) {
                $_SESSION['error'] = 'Atleta não encontrado.';
                $this->redirect('/fgkirs-admin/athletes');
            }
            DojoScope::deny();
        }

        DojoScope::assertDojo((int) $athlete['dojo_id']);

        return $athlete;
    }

    /** Volta ao formulário preservando o que o usuário digitou. */
    private function failBack(string $message, string $back, array $extra = []): void
    {
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
