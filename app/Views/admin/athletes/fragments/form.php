<?php
/**
 * Formulário de atleta dentro do modal (cadastro quando $athlete é null).
 *
 * @var array|null $athlete
 */
use Helpers\Csrf;

$isEdit = !empty($athlete['id']);
$action = $isEdit ? '/fgkirs-admin/athletes/update/' . (int) $athlete['id'] : '/fgkirs-admin/athletes/store';
?>
<form method="POST" action="<?= $action ?>" enctype="multipart/form-data" data-athlete-form>
    <div class="px-6 pt-5 pb-1 flex items-start justify-between gap-4">
        <h2 class="text-xl font-bold text-slate-900"><?= $isEdit ? 'Editar atleta' : 'Novo atleta' ?></h2>
        <button type="button" data-modal-close class="text-slate-400 hover:text-slate-700 text-2xl leading-none" aria-label="Fechar">&times;</button>
    </div>

    <div class="px-6 py-4">
        <div data-form-error class="hidden rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm mb-4"></div>
        <?= Csrf::field() ?>
        <?php require __DIR__ . '/../partials/form_fields.php'; ?>
    </div>

    <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 px-6 pb-5">
        <button type="button" data-modal-close class="text-center bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-6 rounded-lg transition">Cancelar</button>
        <button type="submit" class="bg-red-700 hover:bg-red-800 disabled:opacity-50 text-white font-semibold py-2 px-6 rounded-lg transition">
            <?= $isEdit ? 'Salvar' : 'Cadastrar' ?>
        </button>
    </div>
</form>
