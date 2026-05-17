<?php
$pageTitle = 'Galerias de Imagens – FGKIRS';
$pageDesc = 'Fotos de eventos, campeonatos e apresentações da Federação Gaúcha de Karatê Interestilos.';
require_once __DIR__ . '/../partials/public_header.php';

use Core\Database;
use PDO;

try {
    $db = Database::getInstance();
    $galleries = $db->query(
        "SELECT * FROM galleries WHERE status = 'published' ORDER BY event_date DESC LIMIT 60"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (\Exception $e) {
    $galleries = [];
}
?>

<section class="py-16 bg-white min-h-screen">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <h1 class="text-4xl md:text-5xl font-black text-slate-900 mb-10">Galerias de Imagens</h1>

        <?php if (empty($galleries)): ?>
            <p class="text-slate-500 text-lg">Nenhuma galeria publicada ainda.</p>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($galleries as $gallery):
                    $url = '/galeria/' . ($gallery['slug'] ?? $gallery['id']);
                    ?>
                    <a href="<?= $url ?>"
                        class="group block rounded-2xl overflow-hidden shadow hover:shadow-xl transition bg-slate-100">
                        <?php if (!empty($gallery['cover_image'])): ?>
                            <div class="aspect-[4/3] overflow-hidden">
                                <img src="/uploads/galleries/<?= htmlspecialchars($gallery['cover_image']) ?>"
                                    alt="<?= htmlspecialchars($gallery['title']) ?>"
                                    class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                            </div>
                        <?php else: ?>
                            <div class="aspect-[4/3] flex items-center justify-center bg-slate-200">
                                <svg class="w-12 h-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                        <?php endif; ?>
                        <div class="p-4">
                            <?php if (!empty($gallery['event_date'])): ?>
                                <time class="text-xs font-bold uppercase tracking-wider text-rs-red mb-1 block">
                                    <?= date('d/m/Y', strtotime($gallery['event_date'])) ?>
                                </time>
                            <?php endif; ?>
                            <h2 class="text-lg font-bold text-slate-900 leading-snug group-hover:text-rs-red transition">
                                <?= htmlspecialchars($gallery['title']) ?>
                            </h2>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/../partials/public_footer.php'; ?>