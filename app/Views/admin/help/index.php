<?php
use Helpers\Auth;
use Helpers\Csrf;

$pageTitle = 'Ajuda';
require_once __DIR__ . '/../layout/header.php';

foreach ($videos as &$v) {
    preg_match(
        '/(?:youtube\.com\/(?:watch\?.*v=|live\/|embed\/|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/',
        $v['url'],
        $ym
    );
    $v['yt_id'] = $ym[1] ?? null;
}
unset($v);
?>

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-slate-900">Ajuda</h1>
        <p class="text-sm text-slate-500 mt-1">Vídeos com tutoriais e orientações sobre o sistema.</p>
    </div>
    <?php if (Auth::isAdmin()): ?>
        <button type="button" onclick="openAddVideoModal()"
            class="bg-red-700 hover:bg-red-800 text-white font-semibold py-2 px-6 rounded-lg transition">
            + Adicionar Vídeo
        </button>
    <?php endif; ?>
</div>

<?php if (empty($videos)): ?>
    <div class="bg-white rounded-lg shadow p-8 text-center text-gray-500">
        Nenhum vídeo cadastrado ainda.
    </div>
<?php else: ?>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <?php foreach ($videos as $video): ?>
            <div class="bg-white rounded-lg shadow overflow-hidden group relative">
                <?php if (Auth::isAdmin()): ?>
                    <a href="/fgkirs-admin/help/delete/<?= $video['id'] ?>" data-confirm-delete title="Excluir"
                        class="absolute top-2 right-2 z-10 w-8 h-8 rounded-full bg-black/60 hover:bg-red-700 text-white flex items-center justify-center transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m-10 0h14" />
                        </svg>
                    </a>
                <?php endif; ?>

                <button type="button"
                    onclick="openVideoLightbox('<?= htmlspecialchars($video['yt_id'] ?? '', ENT_QUOTES) ?>')"
                    class="block w-full text-left cursor-pointer">
                    <div class="relative aspect-video bg-slate-100">
                        <?php if ($video['yt_id']): ?>
                            <img src="https://img.youtube.com/vi/<?= htmlspecialchars($video['yt_id']) ?>/mqdefault.jpg"
                                alt="<?= htmlspecialchars($video['title']) ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center">
                                <svg class="w-8 h-8 text-slate-400" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M8 5v14l11-7z" />
                                </svg>
                            </div>
                        <?php endif; ?>
                        <div class="absolute inset-0 flex items-center justify-center bg-black/20 group-hover:bg-black/35 transition">
                            <div class="w-10 h-10 rounded-full bg-white/90 flex items-center justify-center shadow-lg group-hover:scale-105 transition">
                                <svg class="w-4 h-4 text-rs-red ml-0.5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M8 5v14l11-7z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="p-3">
                        <p class="text-sm font-semibold text-slate-800 line-clamp-2"><?= htmlspecialchars($video['title']) ?></p>
                    </div>
                </button>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Modal: Adicionar Vídeo -->
<?php if (Auth::isAdmin()): ?>
<div id="addVideoModal" class="fixed inset-0 z-50 bg-black/60 hidden items-center justify-center p-4"
     role="dialog" aria-modal="true" aria-label="Adicionar vídeo">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold text-slate-900">Adicionar Vídeo</h2>
            <button type="button" onclick="closeAddVideoModal()" class="text-slate-400 hover:text-slate-700 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form action="/fgkirs-admin/help/store" method="POST">
            <?= Csrf::field() ?>

            <div class="mb-4">
                <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Título</label>
                <input type="text" id="title" name="title" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
            </div>

            <div class="mb-6">
                <label for="url" class="block text-sm font-medium text-gray-700 mb-2">URL do Vídeo</label>
                <input type="url" id="url" name="url" required placeholder="https://www.youtube.com/watch?v=..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-700 focus:border-transparent">
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeAddVideoModal()"
                    class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-6 rounded-lg transition">
                    Cancelar
                </button>
                <button type="submit"
                    class="bg-red-700 hover:bg-red-800 text-white font-semibold py-2 px-6 rounded-lg transition">
                    Salvar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddVideoModal() {
    const m = document.getElementById('addVideoModal');
    m.classList.remove('hidden');
    m.classList.add('flex');
}
function closeAddVideoModal() {
    const m = document.getElementById('addVideoModal');
    m.classList.add('hidden');
    m.classList.remove('flex');
}
document.getElementById('addVideoModal').addEventListener('click', function (e) {
    if (e.target === this) closeAddVideoModal();
});
</script>
<?php endif; ?>

<!-- Lightbox de vídeo -->
<div id="videoLightbox" class="fixed inset-0 z-50 bg-black/95 hidden flex-col items-center justify-center"
     role="dialog" aria-modal="true" aria-label="Assistir vídeo">
    <button onclick="closeVideoLightbox()" aria-label="Fechar"
        class="absolute top-4 right-4 text-white hover:text-slate-300 transition">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
    </button>

    <div class="w-full max-w-4xl aspect-video px-4">
        <iframe id="videoLightboxFrame" src=""
            class="w-full h-full rounded-lg shadow-2xl" frameborder="0"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
            allowfullscreen></iframe>
    </div>
</div>

<script>
function openVideoLightbox(ytId) {
    if (!ytId) return;
    document.getElementById('videoLightboxFrame').src = 'https://www.youtube.com/embed/' + ytId + '?autoplay=1';
    const lb = document.getElementById('videoLightbox');
    lb.classList.remove('hidden');
    lb.classList.add('flex');
    document.body.style.overflow = 'hidden';
}
function closeVideoLightbox() {
    const lb = document.getElementById('videoLightbox');
    lb.classList.add('hidden');
    lb.classList.remove('flex');
    document.getElementById('videoLightboxFrame').src = '';
    document.body.style.overflow = '';
}
document.getElementById('videoLightbox').addEventListener('click', function (e) {
    if (e.target === this) closeVideoLightbox();
});
document.addEventListener('keydown', e => {
    if (document.getElementById('videoLightbox').classList.contains('hidden')) return;
    if (e.key === 'Escape') closeVideoLightbox();
});
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
