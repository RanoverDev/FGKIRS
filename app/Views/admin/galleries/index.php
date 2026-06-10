<?php
use Helpers\Auth;

$pageTitle = 'Galerias de Imagens';
require_once __DIR__ . '/../layout/header.php';
?>

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900">Galerias de Imagens</h1>
    <?php if (Auth::authorize(['admin', 'sensei', 'aluno-colaborador'])): ?>
        <a href="/fgkirs-admin/galleries/create"
            class="bg-red-700 hover:bg-red-800 text-white font-semibold py-2 px-6 rounded-lg transition">
            + Nova Galeria
        </a>
    <?php endif; ?>
</div>

<?php if (empty($galleries)): ?>
    <div class="bg-white rounded-lg shadow p-8 text-center text-gray-500">
        Nenhuma galeria cadastrada.
    </div>
<?php else: ?>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-slate-900 text-white">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Capa</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Título</th>
                        <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider hidden sm:table-cell">
                            Imagens</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider hidden md:table-cell">
                            Autor</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider hidden md:table-cell">
                            Data do Evento</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider hidden lg:table-cell">
                            Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($galleries as $gallery): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if (!empty($gallery['cover_image'])): ?>
                                    <img src="/uploads/galleries/<?= htmlspecialchars($gallery['cover_image']) ?>"
                                        alt="<?= htmlspecialchars($gallery['title']) ?>" class="w-16 h-16 object-cover rounded-lg">
                                <?php else: ?>
                                    <div class="w-16 h-16 rounded-lg bg-slate-100 flex items-center justify-center">
                                        <svg class="w-7 h-7 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td class="px-6 py-4">
                                <div class="text-sm font-semibold text-gray-900 line-clamp-2">
                                    <?= htmlspecialchars($gallery['title']) ?>
                                </div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-center hidden sm:table-cell">
                                <span class="bg-slate-100 text-slate-800 text-xs font-bold px-3 py-1 rounded-full">
                                    <?= $gallery['image_count'] ?>
                                </span>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap hidden md:table-cell">
                                <div class="text-sm text-gray-600">
                                    <?= htmlspecialchars($gallery['author_display'] ?? '-') ?>
                                </div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap hidden md:table-cell">
                                <div class="text-sm text-gray-600">
                                    <?= date('d/m/Y', strtotime($gallery['event_date'])) ?>
                                </div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap hidden lg:table-cell">
                                <?php if ($gallery['status'] === 'published'): ?>
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                        Publicado
                                    </span>
                                <?php else: ?>
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                        Rascunho
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-3">
                                <?php if ($gallery['author_id'] === Auth::id() || Auth::authorize(['admin', 'sensei'])): ?>
                                    <a href="/fgkirs-admin/galleries/edit/<?= $gallery['id'] ?>" title="Gerenciar"
                                        class="inline-flex text-slate-600 hover:text-slate-900 transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536M9 13l6.768-6.768a2 2 0 112.828 2.828L11.828 15.828a2 2 0 01-1.414.586H8v-2.414a2 2 0 01.586-1.414z"/></svg>
                                    </a>
                                <?php endif; ?>
                                <?php if (Auth::authorize(['admin', 'sensei']) || $gallery['author_id'] === Auth::id()): ?>
                                    <a href="/fgkirs-admin/galleries/delete/<?= $gallery['id'] ?>" data-confirm-delete title="Excluir"
                                        class="inline-flex text-red-500 hover:text-red-700 transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m-10 0h14"/></svg>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>