<?php
$fp = $profile ?? [];
require_once __DIR__ . '/partials/public_header.php';
?>

<main class="bg-white min-h-screen">
    <section class="py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="flex items-center gap-3 mb-8">
                <span class="h-7 w-1 rounded-full bg-rs-red"></span>
                <h1 class="text-4xl font-bold text-slate-900">Dojos Oficiais</h1>
            </div>
            <p class="text-slate-500 mb-8">
                Dojos filiados à FGKIRS em todo o Rio Grande do Sul
            </p>

            <?php if (!empty($dojos)): ?>

                <div class="max-w-md mx-auto mb-10 relative">
                    <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input id="dojoSearch" type="search" placeholder="Buscar por cidade ou dojo…" class="w-full pl-10 pr-4 py-3 border border-slate-200 rounded-xl bg-white
                              focus:outline-none focus:ring-2 focus:ring-rs-red focus:border-transparent
                              text-sm shadow-xl">
                </div>

                <div id="dojoGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($dojos as $dojo): ?>
                        <div class="dojo-card bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition-shadow duration-200"
                            data-search="<?= strtolower(htmlspecialchars($dojo['name'] . ' ' . ($dojo['city'] ?? ''))) ?>">

                            <div class="flex items-start gap-4 mb-4">
                                <div
                                    class="shrink-0 w-14 h-14 rounded-xl overflow-hidden bg-slate-100 border border-slate-200 flex items-center justify-center">
                                    <?php if (!empty($dojo['logo'])): ?>
                                        <img src="<?= htmlspecialchars($dojo['logo']) ?>"
                                            alt="Logo <?= htmlspecialchars($dojo['name']) ?>"
                                            class="w-full h-full object-contain p-1">
                                    <?php else: ?>
                                        <svg class="w-7 h-7 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                    <?php endif; ?>
                                </div>
                                <div class="min-w-0">
                                    <h3 class="text-base font-bold text-slate-900 leading-snug">
                                        <?= htmlspecialchars($dojo['name']) ?>
                                    </h3>
                                    <p class="text-xs text-slate-400 mt-0.5">
                                        <?= htmlspecialchars($dojo['city'] ?? '') ?>
                                        <?= !empty($dojo['state']) ? '– ' . htmlspecialchars($dojo['state']) : '' ?>
                                    </p>
                                </div>
                            </div>

                            <div class="space-y-1.5 text-sm text-slate-600">
                                <?php if (!empty($dojo['sensei_name'])): ?>
                                    <p class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                        <span><strong class="text-slate-700">Sensei:</strong>
                                            <?= htmlspecialchars($dojo['sensei_name']) ?></span>
                                    </p>
                                <?php endif; ?>
                                <?php if (!empty($dojo['address'])): ?>
                                    <p class="flex items-start gap-2">
                                        <svg class="w-4 h-4 text-slate-400 shrink-0 mt-0.5" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        <span><?= htmlspecialchars($dojo['address']) ?></span>
                                    </p>
                                <?php endif; ?>
                                <?php if (!empty($dojo['phone'])): ?>
                                    <p class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498A1 1 0 0121 15.72V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                        </svg>
                                        <span><?= htmlspecialchars($dojo['phone']) ?></span>
                                    </p>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($dojo['website'])): ?>
                                <a href="<?= htmlspecialchars($dojo['website']) ?>" target="_blank" rel="noopener"
                                    class="mt-4 inline-flex items-center gap-1.5 text-xs font-semibold text-rs-red hover:text-red-700 transition">
                                    Visitar site
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                </a>
                            <?php endif; ?>

                        </div>
                    <?php endforeach; ?>
                </div>

                <p id="dojoEmpty" class="hidden text-center text-slate-400 text-sm mt-8 italic">
                    Nenhum dojo encontrado para esta busca.
                </p>

            <?php else: ?>
                <div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-100 text-center">
                    <p class="text-slate-400 text-sm italic">Nenhum dojo cadastrado no momento.</p>
                </div>
            <?php endif; ?>

        </div>
    </section>

</main>

<script>
    // ── Dojo search ──────────────────────────────────────────────────────────
    (function () {
        const input = document.getElementById('dojoSearch');
        const empty = document.getElementById('dojoEmpty');
        if (!input) return;

        input.addEventListener('input', function () {
            const term = this.value.toLowerCase().trim();
            const cards = document.querySelectorAll('.dojo-card');
            let visibleCount = 0;

            cards.forEach(card => {
                const txt = card.getAttribute('data-search') || '';
                if (term === '' || txt.includes(term)) {
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            if (empty) {
                empty.style.display = visibleCount === 0 && cards.length > 0 ? 'block' : 'none';
            }
        });
    })();
</script>

<?php require_once __DIR__ . '/partials/public_footer.php'; ?>