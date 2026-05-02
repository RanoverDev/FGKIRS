<?php
$isEdit    = isset($style) && $style;
$pageTitle = $isEdit ? 'Editar Estilo' : 'Novo Estilo';
require_once __DIR__ . '/../layout/header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900"><?= $pageTitle ?></h1>
</div>

<div class="bg-white rounded-lg shadow p-6 sm:p-8 max-w-2xl">
    <form action="<?= $isEdit ? "/fgkirs-admin/styles/update/{$style['id']}" : '/fgkirs-admin/styles/store' ?>"
          method="POST"
          enctype="multipart/form-data">

        <div class="mb-4">
            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Nome *</label>
            <input type="text" id="name" name="name" required
                   value="<?= htmlspecialchars($style['name'] ?? '') ?>"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
        </div>

        <div class="mb-4">
            <label for="symbol" class="block text-sm font-medium text-gray-700 mb-2">Símbolo (imagem)</label>

            <?php if ($isEdit && !empty($style['symbol'])): ?>
                <div class="mb-2">
                    <img src="/uploads/styles/<?= htmlspecialchars($style['symbol']) ?>"
                         alt="Símbolo atual" class="w-20 h-20 object-contain rounded border border-gray-200">
                </div>
            <?php endif; ?>

            <input type="file" id="symbol" name="symbol" accept=".jpg,.jpeg,image/jpeg"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
            <p class="text-xs text-gray-500 mt-1">Apenas JPG. Será redimensionada para 500px, qualidade 65%.</p>
        </div>

        <div class="mb-6">
            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Descrição</label>
            <textarea id="description" name="description" rows="4"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent"><?= htmlspecialchars($style['description'] ?? '') ?></textarea>
        </div>

        <div class="flex flex-col sm:flex-row gap-3">
            <button type="submit"
                    class="bg-red-700 hover:bg-red-800 text-white font-semibold py-2 px-6 rounded-lg transition">
                <?= $isEdit ? 'Atualizar' : 'Cadastrar' ?>
            </button>
            <a href="/fgkirs-admin/styles"
               class="text-center bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-6 rounded-lg transition">
                Cancelar
            </a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
