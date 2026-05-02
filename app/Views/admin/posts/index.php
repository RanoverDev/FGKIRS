<?php
use Helpers\Auth;

$pageTitle = 'Notícias e Eventos';
require_once __DIR__ . '/../layout/header.php';
?>

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900">Notícias e Eventos</h1>
    <?php if (Auth::authorize(['admin', 'sensei', 'colaborador'])): ?>
        <a href="/fgkirs-admin/posts/create"
           class="bg-red-700 hover:bg-red-800 text-white font-semibold py-2 px-6 rounded-lg transition">
            + Nova Postagem
        </a>
    <?php endif; ?>
</div>

<?php if (empty($posts)): ?>
    <div class="bg-white rounded-lg shadow p-8 text-center text-gray-500">
        Nenhuma postagem cadastrada.
    </div>
<?php else: ?>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-slate-900 text-white">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Imagem</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Título</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider hidden sm:table-cell">Tipo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider hidden md:table-cell">Autor</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider hidden md:table-cell">Data</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($posts as $post): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if (!empty($post['featured_image'])): ?>
                                    <img src="/uploads/posts/<?= htmlspecialchars($post['featured_image']) ?>"
                                         alt="<?= htmlspecialchars($post['title']) ?>"
                                         class="w-14 h-14 object-cover rounded">
                                <?php else: ?>
                                    <div class="w-14 h-14 rounded bg-slate-100 flex items-center justify-center">
                                        <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-semibold text-gray-900 line-clamp-2">
                                    <?= htmlspecialchars($post['title']) ?>
                                </div>
                                <?php if ($post['type'] === 'event' && !empty($post['event_date'])): ?>
                                    <div class="text-xs text-gray-500 mt-1">
                                        <?= date('d/m/Y', strtotime($post['event_date'])) ?>
                                        <?php if (!empty($post['event_location'])): ?>
                                            · <?= htmlspecialchars($post['event_location']) ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap hidden sm:table-cell">
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                                    <?= $post['type'] === 'event' ? 'bg-blue-100 text-blue-800' : 'bg-slate-100 text-slate-800' ?>">
                                    <?= $post['type'] === 'event' ? 'Evento' : 'Notícia' ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap hidden md:table-cell">
                                <div class="text-sm text-gray-600">
                                    <?= htmlspecialchars($post['author_name'] ?? '-') ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap hidden md:table-cell">
                                <div class="text-sm text-gray-600">
                                    <?= date('d/m/Y', strtotime($post['created_at'])) ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                <?php if ($post['author_id'] === Auth::id() || Auth::authorize(['admin', 'sensei'])): ?>
                                    <a href="/fgkirs-admin/posts/edit/<?= $post['id'] ?>"
                                       class="text-blue-600 hover:text-blue-900">Editar</a>
                                <?php endif; ?>
                                <?php if (Auth::authorize(['admin', 'sensei']) || $post['author_id'] === Auth::id()): ?>
                                    <a href="/fgkirs-admin/posts/delete/<?= $post['id'] ?>"
                                       data-confirm-delete
                                       class="text-red-600 hover:text-red-900">Excluir</a>
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
