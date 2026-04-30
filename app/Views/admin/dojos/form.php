<?php
use Helpers\Auth;

$pageTitle = isset($dojo) ? 'Editar Dojo' : 'Novo Dojo';
$isEdit = isset($dojo) && $dojo;
require_once __DIR__ . '/../layout/header.php';
?>

<!-- Page Header -->
<div class="mb-6">
    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900">
        <?= $pageTitle ?>
    </h1>
</div>

<!-- Dojo Form -->
<div class="bg-white rounded-lg shadow p-6 sm:p-8 max-w-2xl">
    <form action="<?= $isEdit ? "/fgkirs-admin/dojos/update/{$dojo['id']}" : '/fgkirs-admin/dojos/store' ?>"
        method="POST"
        enctype="multipart/form-data">

        <!-- Nome do Dojo -->
        <div class="mb-4">
            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Nome do Dojo *</label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($dojo['name'] ?? '') ?>" required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
        </div>

        <!-- Endereço -->
        <div class="mb-4">
            <label for="address" class="block text-sm font-medium text-gray-700 mb-2">Endereço</label>
            <input type="text" id="address" name="address" value="<?= htmlspecialchars($dojo['address'] ?? '') ?>"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
        </div>

        <!-- Cidade e Estado -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
            <div>
                <label for="city" class="block text-sm font-medium text-gray-700 mb-2">Cidade</label>
                <input type="text" id="city" name="city" value="<?= htmlspecialchars($dojo['city'] ?? '') ?>"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
            </div>
            <div>
                <label for="state" class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                <input type="text" id="state" name="state" value="<?= htmlspecialchars($dojo['state'] ?? '') ?>"
                    placeholder="RS" maxlength="2"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
            </div>
        </div>

        <!-- Sensei Responsável (somente admin) -->
        <?php if (Auth::isAdmin() && !empty($senseis)): ?>
            <div class="mb-4">
                <label for="sensei_id" class="block text-sm font-medium text-gray-700 mb-2">Sensei Responsável</label>
                <select id="sensei_id" name="sensei_id"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
                    <option value="">Nenhum</option>
                    <?php foreach ($senseis as $sensei): ?>
                        <option value="<?= $sensei['id'] ?>" <?= ($dojo['sensei_id'] ?? '') == $sensei['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($sensei['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <!-- Logo -->
        <div class="mb-6">
            <label for="logo" class="block text-sm font-medium text-gray-700 mb-2">Logo do Dojo</label>

            <!-- Preview atual -->
            <?php if ($isEdit && !empty($dojo['logo'])): ?>
                <div class="mb-2 bg-slate-900 p-4 rounded-lg inline-block">
                    <img src="/uploads/dojos/<?= htmlspecialchars($dojo['logo']) ?>" alt="Logo atual"
                        class="h-24 object-contain">
                </div>
            <?php endif; ?>

            <input type="file" id="logo" name="logo" accept="image/*"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
            <p class="text-xs text-gray-500 mt-1">Será convertida para JPG (1200px, qualidade 55)</p>
        </div>

        <!-- Buttons -->
        <div class="flex flex-col sm:flex-row gap-3">
            <button type="submit"
                class="bg-red-700 hover:bg-red-800 text-white font-semibold py-2 px-6 rounded-lg transition">
                <?= $isEdit ? 'Atualizar' : 'Cadastrar' ?>
            </button>
            <a href="/fgkirs-admin/dojos"
                class="text-center bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-6 rounded-lg transition">
                Cancelar
            </a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>