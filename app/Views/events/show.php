<?php
$images = $images ?? [];
$dateObj = new DateTime($post['event_date']);
$dateStr = $dateObj->format('d/m/Y');
$day = $dateObj->format('d');
$month = mb_strtoupper($dateObj->format('M'));
$excerpt = mb_substr(strip_tags($post['content']), 0, 160, 'UTF-8');

// Build image list: post_images first, fall back to featured_image
$allImgs = [];
foreach ($images as $pi) {
    $allImgs[] = '/uploads/posts/' . $pi['filename'];
}
if (empty($allImgs) && !empty($post['featured_image'])) {
    $allImgs[] = '/uploads/posts/' . $post['featured_image'];
}
$heroImg = $allImgs[0] ?? null;

$pageTitle = htmlspecialchars($post['title']) . ' – FGKIRS';
$pageDesc = $excerpt;
$ogImage = $heroImg ? 'https://fgkirs.com.br' . $heroImg : 'https://fgkirs.com.br/assets/images/og-default.jpg';
$ogUrl = 'https://fgkirs.com.br/evento/' . ($post['slug'] ?? $post['id']);

require_once __DIR__ . '/../partials/public_header.php';
?>

<?php
$shareUrl = urlencode($ogUrl);
$shareTitle = urlencode($post['title']);
?>

<article class="py-20 bg-slate-50 min-h-screen">
    <div class="max-w-4xl mx-auto px-4 sm:px-6">

        <div class="bg-white rounded-2xl shadow-xl overflow-hidden mb-8">
            <div class="p-8 md:p-12">
                <!-- Data e Título (Anexo) -->
                <div class="flex flex-col md:flex-row gap-8 items-start mb-8 pb-8 border-b border-slate-100">
                    <div class="shrink-0 bg-slate-900 text-white rounded-xl w-24 text-center py-4 shadow-lg">
                        <span class="block text-4xl font-black mb-1"><?= $day ?></span>
                        <span class="block text-sm font-bold uppercase tracking-widest text-rs-yellow"><?= $month ?></span>
                    </div>

                    <div class="flex-1">
                        <h1 class="text-3xl md:text-4xl font-black text-slate-900 leading-tight mb-4">
                            <?= htmlspecialchars($post['title']) ?>
                        </h1>

                        <?php if (!empty($post['event_location'])): ?>
                            <p class="flex items-center gap-2 text-slate-600 mb-4 font-medium">
                                <svg class="w-5 h-5 text-rs-red" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <?= htmlspecialchars($post['event_location']) ?>
                            </p>
                        <?php endif; ?>

                        <?php if (!empty($post['author_display'])): ?>
                            <p class="text-sm text-slate-500">Por <span
                                    class="font-semibold text-slate-700"><?= htmlspecialchars($post['author_display']) ?></span>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 2 Colunas: 60% Imagem / 40% Texto Longo -->
                <div class="grid grid-cols-1 md:grid-cols-10 gap-8 items-start">
                    <!-- Primeira coluna: Imagem (60%) -->
                    <div class="md:col-span-6">
                        <?php if ($heroImg): ?>
                            <div class="cursor-pointer overflow-hidden rounded-2xl shadow-md hover:shadow-lg transition bg-slate-100" onclick="openLightbox(0)">
                                <img src="<?= htmlspecialchars($heroImg) ?>" alt="<?= htmlspecialchars($post['title']) ?>"
                                    class="w-full h-auto object-cover rounded-2xl">
                            </div>
                        <?php else: ?>
                            <div class="aspect-[3/4] w-full bg-slate-100 rounded-2xl flex items-center justify-center text-slate-400">
                                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Segunda coluna: Texto Longo (40%) -->
                    <div class="md:col-span-4">
                        <div class="prose prose-slate max-w-none leading-relaxed text-slate-700">
                            <?php if ($post['content'] !== strip_tags($post['content'])): ?>
                                <?= $post['content'] ?>
                            <?php else: ?>
                                <?php
                                $paragraphs = array_filter(array_map('trim', preg_split('/\n{2,}/', $post['content'])));
                                foreach ($paragraphs as $p):
                                    ?>
                                    <p class="mb-4"><?= nl2br(htmlspecialchars($p)) ?></p>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navegação de Eventos -->
        <div class="grid grid-cols-2 gap-4 mb-8">
            <div>
                <?php if ($prevEvent): ?>
                    <a href="/evento/<?= $prevEvent['slug'] ?? $prevEvent['id'] ?>"
                        class="flex flex-col items-start p-4 bg-white rounded-xl shadow-sm hover:shadow-md border border-slate-100 hover:border-slate-200 transition group h-full justify-between">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            Evento Anterior
                        </span>
                        <span class="text-sm font-bold text-slate-800 mt-1 line-clamp-1 group-hover:text-slate-950 text-left">
                            <?= htmlspecialchars($prevEvent['title']) ?>
                        </span>
                    </a>
                <?php else: ?>
                    <div class="h-full flex flex-col items-start p-4 bg-slate-50 border border-dashed border-slate-200 rounded-xl opacity-60 justify-center">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                            Sem Evento Anterior
                        </span>
                    </div>
                <?php endif; ?>
            </div>

            <div>
                <?php if ($nextEvent): ?>
                    <a href="/evento/<?= $nextEvent['slug'] ?? $nextEvent['id'] ?>"
                        class="flex flex-col items-end p-4 bg-white rounded-xl shadow-sm hover:shadow-md border border-slate-100 hover:border-slate-200 transition group h-full justify-between">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
                            Próximo Evento
                            <svg class="w-3.5 h-3.5 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                            </svg>
                        </span>
                        <span class="text-sm font-bold text-slate-800 mt-1 line-clamp-1 group-hover:text-slate-950 text-right">
                            <?= htmlspecialchars($nextEvent['title']) ?>
                        </span>
                    </a>
                <?php else: ?>
                    <div class="h-full flex flex-col items-end p-4 bg-slate-50 border border-dashed border-slate-200 rounded-xl opacity-60 justify-center">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                            Sem Próximo Evento
                        </span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (count($allImgs) > 1): ?>
            <!-- ── Grade de imagens ── -->
            <div class="bg-white rounded-2xl shadow-xl p-6 mb-8">
                <h2 class="text-lg font-bold text-slate-800 mb-4">Fotos
                    <span class="text-sm font-normal text-slate-500">(<?= count($allImgs) ?>)</span>
                </h2>
                <?php
                $cols = count($allImgs) === 2 ? 'grid-cols-2'
                    : (count($allImgs) === 3 ? 'grid-cols-2 sm:grid-cols-3'
                        : 'grid-cols-2 sm:grid-cols-3 md:grid-cols-4');
                ?>
                <div class="grid <?= $cols ?> gap-2">
                    <?php foreach ($allImgs as $i => $src): ?>
                        <div class="aspect-square overflow-hidden rounded-lg cursor-pointer bg-slate-100 hover:opacity-90 transition"
                            onclick="openLightbox(<?= $i ?>)">
                            <img src="<?= htmlspecialchars($src) ?>" alt="" class="w-full h-full object-cover" loading="lazy">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- ── Compartilhar ── -->
        <div class="mb-8 bg-white rounded-2xl shadow p-6">
            <p class="text-sm font-semibold text-slate-500 uppercase tracking-widest mb-4">Compartilhar</p>
            <div class="flex flex-wrap gap-3">

                <a href="https://www.facebook.com/sharer/sharer.php?u=<?= $shareUrl ?>" target="_blank" rel="noopener"
                    class="inline-flex items-center gap-2 bg-[#1877F2] hover:bg-[#166FE5] text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M24 12.073C24 5.405 18.627 0 12 0S0 5.405 0 12.073C0 18.1 4.388 23.094 10.125 24v-8.437H7.078v-3.49h3.047V9.41c0-3.025 1.792-4.697 4.533-4.697 1.312 0 2.686.235 2.686.235v2.97h-1.513c-1.491 0-1.956.93-1.956 1.884v2.25h3.328l-.532 3.49h-2.796V24C19.612 23.094 24 18.1 24 12.073z" />
                    </svg>
                    Facebook
                </a>

                <a href="https://wa.me/?text=<?= $shareTitle ?>%20<?= $shareUrl ?>" target="_blank" rel="noopener"
                    class="inline-flex items-center gap-2 bg-[#25D366] hover:bg-[#1EBE5A] text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                    </svg>
                    WhatsApp
                </a>

                <a href="https://t.me/share/url?url=<?= $shareUrl ?>&text=<?= $shareTitle ?>" target="_blank"
                    rel="noopener"
                    class="inline-flex items-center gap-2 bg-[#26A5E4] hover:bg-[#1E96D3] text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z" />
                    </svg>
                    Telegram
                </a>

                <a href="https://twitter.com/intent/tweet?url=<?= $shareUrl ?>&text=<?= $shareTitle ?>" target="_blank"
                    rel="noopener"
                    class="inline-flex items-center gap-2 bg-slate-900 hover:bg-slate-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.744l7.737-8.835L1.254 2.25H8.08l4.253 5.622zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
                    </svg>
                    X (Twitter)
                </a>

                <a href="mailto:?subject=<?= $shareTitle ?>&body=<?= $shareUrl ?>"
                    class="inline-flex items-center gap-2 bg-slate-200 hover:bg-slate-300 text-slate-800 text-sm font-semibold px-4 py-2 rounded-lg transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    E-mail
                </a>

                <button onclick="copyLink(this)" data-url="<?= htmlspecialchars($ogUrl) ?>"
                    class="inline-flex items-center gap-2 bg-slate-200 hover:bg-slate-300 text-slate-800 text-sm font-semibold px-4 py-2 rounded-lg transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    Copiar link
                </button>
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

<!-- Lightbox -->
<div id="lightbox" class="fixed inset-0 z-50 hidden bg-black/90 flex items-center justify-center"
    onclick="handleBackdrop(event)">
    <button onclick="closeLightbox()"
        class="absolute top-4 right-4 text-white bg-black/50 hover:bg-black/80 rounded-full w-10 h-10 flex items-center justify-center transition text-2xl leading-none">
        &times;
    </button>
    <button onclick="moveLightbox(-1)"
        class="absolute left-4 top-1/2 -translate-y-1/2 text-white bg-black/50 hover:bg-black/80 rounded-full w-10 h-10 flex items-center justify-center transition text-xl">
        &#8592;
    </button>
    <button onclick="moveLightbox(1)"
        class="absolute right-4 top-1/2 -translate-y-1/2 text-white bg-black/50 hover:bg-black/80 rounded-full w-10 h-10 flex items-center justify-center transition text-xl">
        &#8594;
    </button>
    <img id="lbImg" src="" alt="" class="max-h-[90vh] max-w-[90vw] object-contain rounded-lg shadow-2xl">
    <span id="lbCounter" class="absolute bottom-4 left-1/2 -translate-x-1/2 text-white/70 text-sm"></span>
</div>

<script>
    const lbImages = <?= json_encode(array_values($allImgs)) ?>;
    let lbIndex = 0;

    function renderLightbox() {
        document.getElementById('lbImg').src = lbImages[lbIndex];
        document.getElementById('lbCounter').textContent = (lbIndex + 1) + ' / ' + lbImages.length;
        const prevBtn = document.querySelector('#lightbox button:nth-child(2)');
        const nextBtn = document.querySelector('#lightbox button:nth-child(3)');
        prevBtn.style.display = lbImages.length > 1 ? '' : 'none';
        nextBtn.style.display = lbImages.length > 1 ? '' : 'none';
    }

    function openLightbox(idx) {
        lbIndex = idx;
        renderLightbox();
        document.getElementById('lightbox').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeLightbox() {
        document.getElementById('lightbox').classList.add('hidden');
        document.body.style.overflow = '';
    }

    function moveLightbox(dir) {
        lbIndex = (lbIndex + dir + lbImages.length) % lbImages.length;
        renderLightbox();
    }

    function handleBackdrop(e) {
        if (e.target === document.getElementById('lightbox')) closeLightbox();
    }

    document.addEventListener('keydown', e => {
        if (document.getElementById('lightbox').classList.contains('hidden')) return;
        if (e.key === 'Escape') closeLightbox();
        if (e.key === 'ArrowLeft') moveLightbox(-1);
        if (e.key === 'ArrowRight') moveLightbox(1);
    });

    function copyLink(btn) {
        navigator.clipboard.writeText(btn.dataset.url).then(() => {
            const orig = btn.innerHTML;
            btn.textContent = 'Link copiado!';
            setTimeout(() => { btn.innerHTML = orig; }, 2000);
        });
    }
</script>

<?php require_once __DIR__ . '/../partials/public_footer.php'; ?>