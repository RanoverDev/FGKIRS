<?php
$cover = !empty($gallery['cover_image']) ? $gallery['cover_image'] : null;
$date = !empty($gallery['event_date']) ? date('d/m/Y', strtotime($gallery['event_date'])) : null;
$excerpt = mb_substr(strip_tags($gallery['description'] ?? ''), 0, 160, 'UTF-8');

$pageTitle = htmlspecialchars($gallery['title']) . ' – FGKIRS';
$pageDesc = $excerpt ?: 'Galeria de imagens da FGKIRS.';
$ogImage = $cover
    ? 'https://fgkirs.com.br/uploads/galleries/' . $cover
    : 'https://fgkirs.com.br/assets/images/og-default.jpg';
$ogUrl = 'https://fgkirs.com.br/galeria/' . ($gallery['slug'] ?? $gallery['id']);

require_once __DIR__ . '/../partials/public_header.php';

$shareUrl = urlencode($ogUrl);
$shareTitle = urlencode($gallery['title']);
?>

<section class="py-16 bg-white min-h-screen">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">

        <div class="mb-10">
            <?php if ($date): ?>
                <time class="text-sm font-bold uppercase tracking-widest text-rs-red mb-3 block"><?= $date ?></time>
            <?php endif; ?>
            <h1 class="text-4xl md:text-5xl font-black text-slate-900 leading-tight mb-4">
                <?= htmlspecialchars($gallery['title']) ?>
            </h1>
            <?php if (!empty($gallery['description'])): ?>
                <p class="text-slate-600 text-lg max-w-2xl"><?= htmlspecialchars($gallery['description']) ?></p>
            <?php endif; ?>
        </div>

        <?php if (empty($images)): ?>
            <div class="text-center py-20 text-slate-500">Nenhuma imagem nesta galeria ainda.</div>
        <?php else: ?>
            <div class="columns-2 sm:columns-3 lg:columns-4 gap-3 space-y-3 mb-12">
                <?php foreach ($images as $img): ?>
                    <a href="/uploads/galleries/<?= htmlspecialchars($img['filename']) ?>" target="_blank" rel="noopener"
                        class="block rounded-lg overflow-hidden break-inside-avoid hover:opacity-90 transition">
                        <img src="/uploads/galleries/<?= htmlspecialchars($img['filename']) ?>"
                            alt="<?= htmlspecialchars($gallery['title']) ?>" class="w-full h-auto object-cover">
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- ── Compartilhar ── -->
        <div class="border-t border-slate-200 pt-8 mb-8">
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

        <a href="/galerias"
            class="inline-flex items-center gap-2 text-slate-600 hover:text-slate-900 font-semibold transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h18">
                </path>
            </svg>
            Voltar para Galerias
        </a>

    </div>
</section>

<script>
    function copyLink(btn) {
        navigator.clipboard.writeText(btn.dataset.url).then(() => {
            const orig = btn.innerHTML;
            btn.textContent = 'Link copiado!';
            setTimeout(() => { btn.innerHTML = orig; }, 2000);
        });
    }
</script>

<?php require_once __DIR__ . '/../partials/public_footer.php'; ?>