<?php

namespace Controllers\Admin;

use Controllers\Controller;
use Helpers\Auth;
use Helpers\Csrf;
use Models\CompetitionCategory;

/**
 * Catalogo de categorias de disputa. Exclusivo do Presidente: e ele quem
 * define quais categorias existem nos campeonatos da federacao.
 */
class CompetitionCategoryController extends Controller
{
    public function index(): void
    {
        $this->guard();

        $filters = [
            'entry_type' => $_GET['entry_type'] ?? null,
            'modality'   => $_GET['modality'] ?? null,
        ];

        $this->view('admin/categories/index', [
            'categories' => CompetitionCategory::all(array_filter($filters)),
            'filters'    => $filters,
            'pageTitle'  => 'Categorias de Disputa',
        ]);
    }

    public function create(): void
    {
        $this->guard();

        $this->view('admin/categories/form', [
            'category'  => null,
            'pageTitle' => 'Nova Categoria',
        ]);
    }

    public function store(): void
    {
        $this->guard();
        $this->guardCsrf('/fgkirs-admin/categories/create');

        if (trim($_POST['name'] ?? '') === '') {
            $_SESSION['error'] = 'Informe o nome da categoria.';
            $this->redirect('/fgkirs-admin/categories/create');
        }

        CompetitionCategory::create($_POST);

        $_SESSION['success'] = 'Categoria criada com sucesso!';
        $this->redirect('/fgkirs-admin/categories');
    }

    public function edit(int $id): void
    {
        $this->guard();

        $category = CompetitionCategory::find($id);

        if (!$category) {
            $_SESSION['error'] = 'Categoria não encontrada.';
            $this->redirect('/fgkirs-admin/categories');
        }

        $this->view('admin/categories/form', [
            'category'  => $category,
            'pageTitle' => 'Editar Categoria',
        ]);
    }

    public function update(int $id): void
    {
        $this->guard();
        $this->guardCsrf("/fgkirs-admin/categories/edit/$id");

        if (!CompetitionCategory::find($id)) {
            $_SESSION['error'] = 'Categoria não encontrada.';
            $this->redirect('/fgkirs-admin/categories');
        }

        CompetitionCategory::update($id, $_POST);

        $_SESSION['success'] = 'Categoria atualizada com sucesso!';
        $this->redirect('/fgkirs-admin/categories');
    }

    public function restoreDefaults(): void
    {
        $this->guard();
        $this->guardCsrf('/fgkirs-admin/categories');

        $inserted = CompetitionCategory::restoreDefaults();

        $_SESSION['success'] = $inserted > 0
            ? "$inserted categoria(s) do catálogo padrão foram adicionadas."
            : 'O catálogo padrão já está completo. Nada foi alterado.';
        $this->redirect('/fgkirs-admin/categories');
    }

    public function delete(int $id): void
    {
        $this->guard();

        if (!Csrf::validate($_GET['token'] ?? null)) {
            $_SESSION['error'] = 'Link de exclusão inválido ou expirado.';
            $this->redirect('/fgkirs-admin/categories');
        }

        $usage = CompetitionCategory::usageCount($id);

        if ($usage > 0) {
            $_SESSION['error'] = "Esta categoria já tem $usage inscrição(ões) e não pode ser excluída. "
                . 'Desative-a para que ela deixe de aparecer em novos eventos.';
            $this->redirect('/fgkirs-admin/categories');
        }

        CompetitionCategory::delete($id);

        $_SESSION['success'] = 'Categoria excluída com sucesso!';
        $this->redirect('/fgkirs-admin/categories');
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
