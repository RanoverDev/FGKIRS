<?php require_once __DIR__ . '/../partials/public_header.php'; ?>

<?php
$img = !empty($post['featured_image']) ? '/uploads/posts/' . htmlspecialchars($post['featured_image']) : null;
$date = date('d/m/Y', strtotime($post['published_at'] ?? $post['created_at']));
?>
<article class="py-20 bg-white min-h-screen">
    <div class="max-w-4xl mx-auto px-4 sm:px-6">
        <div class="mb-8">
            <time class="text-sm font-bold uppercase tracking-widest text-rs-red mb-4 block"><?= $date ?></time>
            <h1 class="text-4xl md:text-5xl font-black text-slate-900 leading-tight mb-6">
                <?= htmlspecialchars($post['title']) ?></h1>
        </div>

        <?php if ($img): ?>
            <div class="aspect-video w-full rounded-2xl overflow-hidden mb-12 shadow-xl">
                <img src="<?= $img ?>" alt="<?= htmlspecialchars($post['title']) ?>" class="w-full h-full object-cover">
            </div>
        <?php endif; ?>

        <div class="prose prose-lg prose-slate max-w-none">
            <?= $post['content'] ?>
        </div>

        <div class="mt-16 pt-8 border-t border-slate-200">
            <a href="/noticias"
                class="inline-flex items-center gap-2 text-slate-600 hover:text-slate-900 font-semibold transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M7 16l-4-4m0 0l4-4m-4 4h18"></path>
                </svg>
                Voltar para Notícias
            </a>
        </div>
    </div>
</article>

<?php require_once __DIR__ . '/../partials/public_footer.php'; ?>