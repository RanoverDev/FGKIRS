<?php
use Helpers\Auth;

$pageTitle = 'Usuários';
require_once __DIR__ . '/../layout/header.php';
?>

<!-- Page Header -->
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900">Gerenciar Usuários</h1>
    <?php if (Auth::authorize(['admin', 'sensei'])): ?>
        <a href="/admin/users/create"
            class="bg-red-700 hover:bg-red-800 text-white font-semibold py-2 px-6 rounded-lg transition">
            + Novo Usuário
        </a>
    <?php endif; ?>
</div>

<!-- Users Table -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-slate-900 text-white">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Foto</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Nome</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider hidden sm:table-cell">
                        Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Perfil</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider hidden md:table-cell">
                        Dojo</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            Nenhum usuário encontrado.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if (!empty($user['photo'])): ?>
                                    <img src="/uploads/users/<?= htmlspecialchars($user['photo']) ?>"
                                        alt="<?= htmlspecialchars($user['name']) ?>" class="w-10 h-10 rounded-full object-cover">
                                <?php else: ?>
                                    <div
                                        class="w-10 h-10 rounded-full bg-red-700 flex items-center justify-center text-white font-semibold">
                                        <?= strtoupper(substr($user['name'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    <?= htmlspecialchars($user['name']) ?>
                                </div>
                                <div class="text-sm text-gray-500 sm:hidden">
                                    <?= htmlspecialchars($user['email']) ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap hidden sm:table-cell">
                                <div class="text-sm text-gray-900">
                                    <?= htmlspecialchars($user['email']) ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                            <?= $user['role'] === 'admin' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800' ?>">
                                    <?= ucfirst($user['role']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 hidden md:table-cell">
                                <?= htmlspecialchars($user['dojo_name'] ?? '-') ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                <a href="/admin/users/edit/<?= $user['id'] ?>"
                                    class="text-blue-600 hover:text-blue-900">Editar</a>
                                <a href="/admin/users/delete/<?= $user['id'] ?>" data-confirm-delete
                                    class="text-red-600 hover:text-red-900">Excluir</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>