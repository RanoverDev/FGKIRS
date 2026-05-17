<?php
use Helpers\Auth;

$pageTitle = 'Notícias e Publicações';
$activeType = $activeType ?? '';

$filters = [
    '' => 'Todos',
    'news' => 'Notícia',
    'event' => 'Evento',
    'video' => 'Vídeo',
    'live' => 'Ao Vivo',
];

$badgeMap = [
    'news' => ['bg-slate-100 text-slate-800', 'Notícia'],
    'event' => ['bg-blue-100 text-blue-800', 'Evento'],
    'video' => ['bg-purple-100 text-purple-800', 'Vídeo'],
    'live' => ['bg-red-100 text-red-700', 'Ao Vivo'],
];

require_once __DIR__ . '/../layout/header.php';
?>

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 gap-4">
    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900">Notícias e Publicações</h1>
    <?php if (Auth::authorize(['admin', 'sensei', 'aluno-colaborador'])): ?>
        <a href="/fgkirs-admin/posts/create"
            class="bg-red-700 hover:bg-red-800 text-white font-semibold py-2 px-6 rounded-lg transition">
            + Nova Postagem
        </a>
    <?php endif; ?>
</div>

<!-- Filtros por categoria -->
<div class="flex flex-wrap gap-2 mb-6">
    <?php foreach ($filters as $type => $label): ?>
        <?php $isActive = ($activeType === $type); ?>
        <a href="/fgkirs-admin/posts<?= $type ? "?type=$type" : '' ?>" class="px-4 py-1.5 rounded-full text-sm font-semibold border transition
                   <?= $isActive
                       ? 'bg-slate-900 text-white border-slate-900'
                       : 'bg-white text-slate-600 border-slate-300 hover:border-slate-500' ?>">
            <?= $label ?>
        </a>
    <?php endforeach; ?>
</div>

<?php if (empty($posts)): ?>
    <div class="bg-white rounded-lg shadow p-8 text-center text-gray-500">
        Nenhuma postagem encontrada<?= $activeType ? ' para esta categoria' : '' ?>.
    </div>
<?php else: ?>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-slate-900 text-white">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Imagem</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Título</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider hidden sm:table-cell">
                            Tipo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider hidden md:table-cell">
                            Autor</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider hidden md:table-cell">
                            Data</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($posts as $post): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if (!empty($post['featured_image'])): ?>
                                    <img src="/uploads/posts/<?= htmlspecialchars($post['featured_image']) ?>"
                                        alt="<?= htmlspecialchars($post['title']) ?>" class="w-14 h-14 object-cover rounded">
                                <?php elseif (!empty($post['video_url'])): ?>
                                    <!-- Thumbnail do YouTube -->
                                    <?php
                                    preg_match('/(?:youtube\.com\/(?:watch\?.*v=|live\/|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $post['video_url'], $ym);
                                    $ytThumb = isset($ym[1]) ? "https://img.youtube.com/vi/{$ym[1]}/mqdefault.jpg" : null;
                                    ?>
                                    <?php if ($ytThumb): ?>
                                        <div class="relative w-14 h-14 rounded overflow-hidden">
                                            <img src="<?= $ytThumb ?>" alt="" class="w-full h-full object-cover">
                                            <div class="absolute inset-0 flex items-center justify-center bg-black/30">
                                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M8 5v14l11-7z" />
                                                </svg>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="w-14 h-14 rounded bg-slate-100 flex items-center justify-center">
                                            <svg class="w-6 h-6 text-slate-400" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M8 5v14l11-7z" />
                                            </svg>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="w-14 h-14 rounded bg-slate-100 flex items-center justify-center">
                                        <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
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
                                <?php [$badgeClass, $badgeLabel] = $badgeMap[$post['type']] ?? ['bg-slate-100 text-slate-800', $post['type']]; ?>
                                <span
                                    class="px-2 py-1 inline-flex items-center gap-1 text-xs leading-5 font-semibold rounded-full <?= $badgeClass ?>">
                                    <?php if ($post['type'] === 'live'): ?>
                                        <span class="relative flex h-1.5 w-1.5">
                                            <span
                                                class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-500 opacity-75"></span>
                                            <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-red-600"></span>
                                        </span>
                                    <?php endif; ?>
                                    <?= $badgeLabel ?>
                                </span>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap hidden md:table-cell">
                                <div class="text-sm text-gray-600">
                                    <?= htmlspecialchars($post['author_display'] ?? '-') ?>
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
                                    <a href="/fgkirs-admin/posts/delete/<?= $post['id'] ?>" data-confirm-delete
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