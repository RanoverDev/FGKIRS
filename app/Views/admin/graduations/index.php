<?php
$pageTitle = 'Graduações';
use Helpers\Auth;
require_once __DIR__ . '/../layout/header.php';

$currentStyle = $_GET['style'] ?? '';
$byStyle = [];
foreach ($graduations as $g) {
    $byStyle[$g['style_name']][] = $g;
}
?>

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900">Graduações / Faixas</h1>
    <?php if (Auth::isAdmin()): ?>
        <a href="/fgkirs-admin/graduations/create"
           class="bg-red-700 hover:bg-red-800 text-white font-semibold py-2 px-6 rounded-lg transition">
            + Nova Faixa
        </a>
    <?php endif; ?>
</div>

<?php if (empty($graduations)): ?>
    <div class="bg-white rounded-lg shadow p-8 text-center text-gray-500">
        Nenhuma faixa cadastrada.
        <?php if (Auth::isAdmin()): ?>
            <a href="/fgkirs-admin/graduations/create" class="text-red-600 hover:underline ml-1">Cadastrar agora</a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <?php foreach ($byStyle as $styleName => $faixas): ?>
        <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
            <div class="bg-slate-900 text-white px-6 py-3 flex items-center justify-between">
                <h2 class="font-bold text-base uppercase tracking-wide"><?= htmlspecialchars($styleName) ?></h2>
                <span class="text-xs text-slate-400"><?= count($faixas) ?> faixa(s)</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Ordem</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Cor</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nome da Faixa</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase hidden md:table-cell">Descrição</th>
                            <?php if (Auth::isAdmin()): ?>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($faixas as $faixa): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                    <?= (int) $faixa['order_rank'] + 1 ?>º
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="w-8 h-8 rounded-full border-2 border-gray-300"
                                         style="background-color: <?= htmlspecialchars($faixa['belt_color']) ?>"
                                         title="<?= htmlspecialchars($faixa['belt_color']) ?>"></div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-semibold text-gray-900">
                                    <?= htmlspecialchars($faixa['belt_name']) ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 hidden md:table-cell">
                                    <?= htmlspecialchars($faixa['requirements'] ?? '-') ?>
                                </td>
                                <?php if (Auth::isAdmin()): ?>
                                    <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium space-x-3">
                                        <a href="/fgkirs-admin/graduations/edit/<?= $faixa['id'] ?>" title="Editar"
                                           class="inline-flex text-slate-600 hover:text-slate-900 transition">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536M9 13l6.768-6.768a2 2 0 112.828 2.828L11.828 15.828a2 2 0 01-1.414.586H8v-2.414a2 2 0 01.586-1.414z"/></svg>
                                        </a>
                                        <a href="/fgkirs-admin/graduations/delete/<?= $faixa['id'] ?>" data-confirm-delete title="Excluir"
                                           class="inline-flex text-red-500 hover:text-red-700 transition">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m-10 0h14"/></svg>
                                        </a>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
