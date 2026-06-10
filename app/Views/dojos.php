<?php
$fp = $profile ?? [];
require_once __DIR__ . '/partials/public_header.php';

function dojoWhatsappUrl(string $raw): string {
    $n = preg_replace('/\D/', '', $raw);
    if (strlen($n) <= 11) $n = '55' . $n;
    return 'https://wa.me/' . $n;
}
?>

<main class="bg-slate-50 min-h-screen">
    <section class="py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">

            <div class="flex items-center gap-3 mb-2">
                <span class="h-7 w-1 rounded-full bg-rs-red"></span>
                <h1 class="text-4xl font-bold text-slate-900">Dojos Oficiais</h1>
            </div>
            <p class="text-slate-500 mb-10 ml-4">
                Dojos filiados à FGKIRS em todo o Rio Grande do Sul
            </p>

            <?php if (!empty($dojos)): ?>

                <div class="max-w-md mx-auto mb-10 relative">
                    <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input id="dojoSearch" type="search" placeholder="Buscar por cidade ou dojo…"
                           class="w-full pl-10 pr-4 py-3 border border-slate-200 rounded-xl bg-white
                                  focus:outline-none focus:ring-2 focus:ring-rs-red focus:border-transparent
                                  text-sm shadow-sm">
                </div>

                <div id="dojoGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <?php foreach ($dojos as $dojo):
                        $hasWa = !empty($dojo['phone_whatsapp']) || !empty($dojo['phone']);
                        $waRaw = $dojo['phone_whatsapp'] ?? $dojo['phone'] ?? '';
                        $waUrl = $hasWa ? dojoWhatsappUrl($waRaw) : '';
                    ?>
                        <div class="bg-white rounded-2xl shadow-md overflow-hidden flex flex-col
                                    hover:shadow-xl hover:-translate-y-0.5 transition-all duration-200"
                             data-search="<?= strtolower(htmlspecialchars($dojo['name'] . ' ' . ($dojo['city'] ?? '') . ' ' . ($dojo['sensei_name'] ?? ''))) ?>">

                            <!-- Logo -->
                            <div class="flex items-center justify-center bg-white" style="height:200px;">
                                <?php if (!empty($dojo['logo'])): ?>
                                    <img src="/uploads/dojos/<?= htmlspecialchars($dojo['logo']) ?>"
                                         alt="Logo <?= htmlspecialchars($dojo['name']) ?>"
                                         class="w-full h-full object-contain p-8">
                                <?php else: ?>
                                    <div class="flex flex-col items-center gap-2 text-slate-200">
                                        <svg class="w-20 h-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                                  d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                        </svg>
                                        <span class="text-xs font-medium uppercase tracking-wide text-slate-300">Sem logo</span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Accent bar -->
                            <div class="h-1 bg-rs-red shrink-0"></div>

                            <!-- Info -->
                            <div class="p-5 flex flex-col flex-1 gap-2">
                                <h3 class="text-base font-bold text-slate-900 leading-snug">
                                    <?= htmlspecialchars($dojo['name']) ?>
                                </h3>

                                <?php if (!empty($dojo['sensei_name'])): ?>
                                    <p class="flex items-center gap-1.5 text-sm text-slate-600">
                                        <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                        <?= htmlspecialchars($dojo['sensei_name']) ?>
                                    </p>
                                <?php endif; ?>

                                <?php if (!empty($dojo['city'])): ?>
                                    <p class="flex items-center gap-1.5 text-sm text-slate-500">
                                        <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        <?= htmlspecialchars($dojo['city']) ?><?= !empty($dojo['state']) ? ' – ' . htmlspecialchars($dojo['state']) : '' ?>
                                    </p>
                                <?php endif; ?>

                                <!-- Spacer -->
                                <div class="flex-1"></div>

                                <!-- WhatsApp button -->
                                <?php if ($hasWa): ?>
                                    <a href="<?= htmlspecialchars($waUrl) ?>" target="_blank" rel="noopener"
                                       class="mt-3 flex items-center justify-center gap-2 rounded-xl py-2.5 px-4
                                              text-white text-sm font-semibold transition-all duration-200
                                              hover:opacity-90 active:scale-95"
                                       style="background:#25D366;">
                                        <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                        </svg>
                                        Contato via WhatsApp
                                    </a>
                                <?php else: ?>
                                    <div class="mt-3 flex items-center justify-center gap-2 rounded-xl py-2.5 px-4
                                                text-slate-300 text-sm font-medium border border-slate-100 cursor-default">
                                        Sem contato cadastrado
                                    </div>
                                <?php endif; ?>
                            </div>

                        </div>
                    <?php endforeach; ?>
                </div>

                <p id="dojoEmpty" class="hidden text-center text-slate-400 text-sm mt-10 italic">
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
    (function () {
        const input = document.getElementById('dojoSearch');
        const empty = document.getElementById('dojoEmpty');
        if (!input) return;

        input.addEventListener('input', function () {
            const term  = this.value.toLowerCase().trim();
            const cards = document.querySelectorAll('#dojoGrid [data-search]');
            let visible = 0;

            cards.forEach(function (card) {
                const match = card.dataset.search.includes(term);
                card.style.display = match ? '' : 'none';
                if (match) visible++;
            });

            if (empty) empty.classList.toggle('hidden', visible > 0 || term === '');
        });
    })();
</script>

<?php require_once __DIR__ . '/partials/public_footer.php'; ?>
