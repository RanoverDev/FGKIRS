<?php
use Helpers\Auth;

$pageTitle = 'Estilos';
require_once __DIR__ . '/../layout/header.php';
?>

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900">Estilos Marciais</h1>
    <?php if (Auth::isAdmin()): ?>
        <a href="/fgkirs-admin/styles/create"
           class="bg-red-700 hover:bg-red-800 text-white font-semibold py-2 px-6 rounded-lg transition">
            + Novo Estilo
        </a>
    <?php endif; ?>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-slate-900 text-white">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Símbolo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Nome</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider hidden md:table-cell">Descrição</th>
                    <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider">Faixas</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($styles)): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                            Nenhum estilo cadastrado.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($styles as $style): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if (!empty($style['symbol'])): ?>
                                    <img src="/uploads/styles/<?= htmlspecialchars($style['symbol']) ?>"
                                         alt="<?= htmlspecialchars($style['name']) ?>"
                                         class="w-10 h-10 object-contain rounded">
                                <?php else: ?>
                                    <div class="w-10 h-10 rounded-full bg-slate-900 flex items-center justify-center text-white font-bold text-sm">
                                        <?= strtoupper(substr($style['name'], 0, 2)) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-semibold text-gray-900">
                                    <?= htmlspecialchars($style['name']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 hidden md:table-cell">
                                <span class="text-sm text-gray-600">
                                    <?= htmlspecialchars($style['description'] ?? '-') ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-slate-100 text-slate-800">
                                    <?= (int) $style['graduation_count'] ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                <a href="/fgkirs-admin/styles/edit/<?= $style['id'] ?>"
                                   class="text-blue-600 hover:text-blue-900">Editar</a>
                                <a href="/fgkirs-admin/styles/delete/<?= $style['id'] ?>"
                                   data-confirm-delete
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
