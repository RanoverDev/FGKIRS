<?php require_once __DIR__ . '/../partials/public_header.php'; ?>

<?php
$img = !empty($post['featured_image']) ? '/uploads/posts/' . htmlspecialchars($post['featured_image']) : null;
$dateObj = new DateTime($post['event_date']);
$dateStr = $dateObj->format('d/m/Y');
$day = $dateObj->format('d');
$month = mb_strtoupper($dateObj->format('M'));
?>
<article class="py-20 bg-slate-50 min-h-screen">
    <div class="max-w-4xl mx-auto px-4 sm:px-6">

        <div class="bg-white rounded-2xl shadow-xl overflow-hidden mb-12">
            <?php if ($img): ?>
                <div class="aspect-[21/9] w-full bg-slate-800">
                    <img src="<?= $img ?>" alt="<?= htmlspecialchars($post['title']) ?>" class="w-full h-full object-cover">
                </div>
            <?php endif; ?>

            <div class="p-8 md:p-12">
                <div class="flex flex-col md:flex-row gap-8 items-start">
                    <div class="shrink-0 bg-slate-900 text-white rounded-xl w-24 text-center py-4 shadow-lg">
                        <span class="block text-4xl font-black mb-1"><?= $day ?></span>
                        <span
                            class="block text-sm font-bold uppercase tracking-widest text-rs-yellow"><?= $month ?></span>
                    </div>

                    <div class="flex-1">
                        <h1 class="text-3xl md:text-4xl font-black text-slate-900 leading-tight mb-4">
                            <?= htmlspecialchars($post['title']) ?></h1>

                        <?php if (!empty($post['event_location'])): ?>
                            <p class="flex items-center gap-2 text-slate-600 mb-6 font-medium">
                                <svg class="w-5 h-5 text-rs-red" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <?= htmlspecialchars($post['event_location']) ?>
                            </p>
                        <?php endif; ?>

                        <div class="prose prose-slate max-w-none">
                            <?= $post['content'] ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-8">
            <a href="/eventos"
                class="inline-flex items-center gap-2 text-slate-600 hover:text-slate-900 font-semibold transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M7 16l-4-4m0 0l4-4m-4 4h18"></path>
                </svg>
                Voltar para Eventos
            </a>
        </div>
    </div>
</article>

<?php require_once __DIR__ . '/../partials/public_footer.php'; ?>