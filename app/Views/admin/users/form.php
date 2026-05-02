<?php
use Helpers\Auth;

$pageTitle = isset($user) ? 'Editar Usuário' : 'Novo Usuário';
$isEdit = isset($user) && $user;
require_once __DIR__ . '/../layout/header.php';
?>

<!-- Page Header -->
<div class="mb-6">
    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900">
        <?= $pageTitle ?>
    </h1>
</div>

<!-- User Form -->
<div class="bg-white rounded-lg shadow p-6 sm:p-8 max-w-2xl">
    <form action="<?= $isEdit ? "/fgkirs-admin/users/update/{$user['id']}" : '/fgkirs-admin/users/store' ?>"
        method="POST"
        enctype="multipart/form-data">

        <!-- Nome -->
        <div class="mb-4">
            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Nome Completo *</label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
        </div>

        <!-- Email -->
        <div class="mb-4">
            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
        </div>

        <!-- Senha -->
        <div class="mb-4">
            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                Senha
                <?= $isEdit ? '(deixe em branco para não alterar)' : '*' ?>
            </label>
            <input type="password" id="password" name="password" <?= !$isEdit ? 'required' : '' ?>
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700
            focus:border-transparent">
        </div>

        <!-- Role -->
        <div class="mb-4">
            <label for="role" class="block text-sm font-medium text-gray-700 mb-2">Perfil *</label>
            <select id="role" name="role" required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
                <option value="">Selecione...</option>
                <option value="admin" <?= ($user['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Administrador</option>
                <option value="sensei" <?= ($user['role'] ?? '') === 'sensei' ? 'selected' : '' ?>>Sensei</option>
                <option value="aluno-colaborador" <?= ($user['role'] ?? '') === 'aluno-colaborador' ? 'selected' : '' ?>
                    >Aluno Colaborador</option>
                <option value="aluno" <?= ($user['role'] ?? '') === 'aluno' ? 'selected' : '' ?>>Aluno</option>
            </select>
        </div>

        <!-- Dojo (somente admin pode escolher) -->
        <?php if (Auth::isAdmin() && !empty($dojos)): ?>
            <div class="mb-4">
                <label for="dojo_id" class="block text-sm font-medium text-gray-700 mb-2">Dojo</label>
                <select id="dojo_id" name="dojo_id"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
                    <option value="">Nenhum</option>
                    <?php foreach ($dojos as $dojo): ?>
                        <option value="<?= $dojo['id'] ?>" <?= ($user['dojo_id'] ?? '') == $dojo['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dojo['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <!-- Foto -->
        <div class="mb-6">
            <label for="photo" class="block text-sm font-medium text-gray-700 mb-2">Foto</label>

            <!-- Preview atual -->
            <?php if ($isEdit && !empty($user['photo'])): ?>
                <div class="mb-2">
                    <img src="/uploads/users/<?= htmlspecialchars($user['photo']) ?>" alt="Foto atual"
                        class="w-24 h-24 rounded-full object-cover">
                </div>
            <?php endif; ?>

            <input type="file" id="photo" name="photo" accept=".jpg,.jpeg,image/jpeg"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
            <p class="text-xs text-gray-500 mt-1">Será convertida para JPG (1200px, qualidade 55)</p>
        </div>

        <!-- Buttons -->
        <div class="flex flex-col sm:flex-row gap-3">
            <button type="submit"
                class="bg-red-700 hover:bg-red-800 text-white font-semibold py-2 px-6 rounded-lg transition">
                <?= $isEdit ? 'Atualizar' : 'Cadastrar' ?>
            </button>
            <a href="/fgkirs-admin/users"
                class="text-center bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-6 rounded-lg transition">
                Cancelar
            </a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>