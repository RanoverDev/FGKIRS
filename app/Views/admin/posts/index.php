<?php
use Helpers\Auth;

$pageTitle = 'Notícias e Eventos';
require_once __DIR__ . '/../layout/header.php';
?>

<!-- Page Header -->
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-extrabold text-slate-900 uppercase">Notícias e Eventos</h1>
        <p class="text-gray-600 mt-1">Gerenciar comunicações e agenda</p>
    </div>
    <?php if (Auth::authorize(['admin', 'sensei', 'colaborador'])): ?>
        <a href="/admin/posts/create"
            class="bg-rose-600 hover:bg-rose-700 text-white font-bold py-3 px-6 rounded uppercase border-2 border-slate-900 shadow-lg transition">
            + Nova Postagem
        </a>
    <?php endif; ?>
</div>

<!-- Posts Grid -->
<?php if (empty($posts)): ?>
    <div class="bg-white rounded border-2 border-slate-900 shadow-lg p-12 text-center">
        <p class="text-gray-500 text-lg">Nenhuma postagem ainda.</p>
    </div>
<?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($posts as $post): ?>
            <div class="bg-white rounded border-2 border-slate-900 shadow-lg overflow-hidden">
                <!-- Featured Image -->
                <?php if ($post['featured_image']): ?>
                    <img src="/uploads/posts/<?= htmlspecialchars($post['featured_image']) ?>"
                        alt="<?= htmlspecialchars($post['title']) ?>" class="w-full h-48 object-cover">
                <?php else: ?>
                    <div class="w-full h-48 bg-gradient-to-br from-rose-600 to-slate-900 flex items-center justify-center">
                        <svg class="w-16 h-16 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                            </path>
                        </svg>
                    </div>
                <?php endif; ?>

                <!-- Content -->
                <div class="p-4">
                    <!-- Type Badge -->
                    <span class="inline-block px-2 py-1 text-xs font-bold uppercase rounded mb-2
                <?= $post['type'] === 'event' ? 'bg-rose-600 text-white' : 'bg-slate-900 text-white' ?>">
                        <?= $post['type'] === 'event' ? '📅 Evento' : '📰 Notícia' ?>
                    </span>

                    <!-- Title -->
                    <h2 class="text-xl font-bold text-slate-900 mb-2 line-clamp-2">
                        <?= htmlspecialchars($post['title']) ?>
                    </h2>

                    <!-- Content Preview -->
                    <p class="text-sm text-gray-600 mb-3 line-clamp-3">
                        <?= htmlspecialchars(substr(strip_tags($post['content']), 0, 120)) ?>...
                    </p>

                    <!-- Event Info -->
                    <?php if ($post['type'] === 'event' && $post['event_date']): ?>
                        <div class="text-xs text-gray-500 mb-3">
                            <p class="font-bold">📅
                                <?= date('d/m/Y H:i', strtotime($post['event_date'])) ?>
                            </p>
                            <?php if ($post['event_location']): ?>
                                <p>📍
                                    <?= htmlspecialchars($post['event_location']) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Meta Info -->
                    <div class="text-xs text-gray-500 mb-3">
                        <p>Por: <span class="font-bold">
                                <?= htmlspecialchars($post['author_name']) ?>
                            </span></p>
                        <p>
                            <?= date('d/m/Y', strtotime($post['created_at'])) ?>
                        </p>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-2">
                        <?php if ($post['author_id'] === Auth::id() || Auth::authorize(['admin', 'sensei'])): ?>
                            <a href="/admin/posts/edit/<?= $post['id'] ?>"
                                class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-center font-bold py-2 px-3 rounded text-xs uppercase transition">
                                ✏️ Editar
                            </a>
                        <?php endif; ?>

                        <?php if (Auth::authorize(['admin', 'sensei']) || $post['author_id'] === Auth::id()): ?>
                            <form action="/admin/posts/delete/<?= $post['id'] ?>" method="POST" class="flex-1"
                                onsubmit="return confirm('Confirma exclusão?')">
                                <button type="submit"
                                    class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-3 rounded text-xs uppercase transition">
                                    🗑️ Deletar
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>