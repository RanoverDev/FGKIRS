<?php
$fp = $profile ?? [];
require_once __DIR__ . '/partials/public_header.php';

function dojoWaUrl(string $raw, string $dojoName): string {
    $n = preg_replace('/\D/', '', $raw);
    if (strlen($n) <= 11) $n = '55' . $n;
    $msg = urlencode('Olá! Vi o ' . $dojoName . ' no site da FGKIRS e gostaria de mais informações.');
    return 'https://wa.me/' . $n . '?text=' . $msg;
}
?>

<main class="bg-slate-50 min-h-screen">
    <section class="py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">

            <div class="flex items-center gap-3 mb-2">
                <span class="h-7 w-1 rounded-full bg-rs-red"></span>
                <h1 class="text-4xl font-bold text-slate-900">Dojos Oficiais</h1>
            </div>
            <p class="text-slate-500 mb-10 ml-4">Dojos filiados à FGKIRS em todo o Rio Grande do Sul</p>

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

                <div id="dojoGrid" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                    <?php foreach ($dojos as $dojo):
                        $waRaw = $dojo['phone_whatsapp'] ?? $dojo['phone'] ?? '';
                        $hasWa = !empty($waRaw);
                        $waUrl = $hasWa ? dojoWaUrl($waRaw, $dojo['name']) : '';
                        $igUrl = !empty($dojo['instagram']) ? 'https://instagram.com/' . rawurlencode($dojo['instagram']) : '';
                        $fbUrl = !empty($dojo['facebook'])  ? 'https://facebook.com/' . rawurlencode($dojo['facebook'])  : '';
                        $city  = trim(($dojo['city'] ?? '') . ($dojo['state'] ? ' – ' . $dojo['state'] : ''));
                    ?>
                        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden flex flex-col
                                    hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 cursor-pointer"
                             data-search="<?= strtolower(htmlspecialchars($dojo['name'] . ' ' . ($dojo['city'] ?? '') . ' ' . ($dojo['sensei_name'] ?? ''))) ?>"
                             onclick="openModal(<?= $dojo['id'] ?>)">

                            <!-- Logo -->
                            <div class="flex items-center justify-center overflow-hidden" style="height:180px;">
                                <?php if (!empty($dojo['logo'])): ?>
                                    <img src="/uploads/dojos/<?= htmlspecialchars($dojo['logo']) ?>"
                                         alt="<?= htmlspecialchars($dojo['name']) ?>"
                                         class="w-full h-full object-contain">
                                <?php else: ?>
                                    <div class="flex flex-col items-center gap-1 text-slate-200">
                                        <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                                  d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                        </svg>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Accent -->
                            <div class="h-0.5 bg-rs-red shrink-0"></div>

                            <!-- Info -->
                            <div class="px-3 py-3 flex flex-col items-center text-center gap-1 flex-1">
                                <h3 class="text-sm font-bold text-slate-900 leading-snug line-clamp-2">
                                    <?= htmlspecialchars($dojo['name']) ?>
                                </h3>
                                <?php if ($city): ?>
                                    <p class="text-[11px] text-slate-400 flex items-center gap-1">
                                        <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        <?= htmlspecialchars($city) ?>
                                    </p>
                                <?php endif; ?>

                                <!-- Social icons -->
                                <div class="flex items-center justify-center gap-2 mt-1" onclick="event.stopPropagation()">
                                    <?php if ($hasWa): ?>
                                        <a href="<?= htmlspecialchars($waUrl) ?>" target="_blank" rel="noopener"
                                           title="WhatsApp — vi no site da FGKIRS"
                                           class="w-7 h-7 rounded-full flex items-center justify-center text-white transition hover:opacity-80"
                                           style="background:#25D366;">
                                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                            </svg>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($igUrl): ?>
                                        <a href="<?= htmlspecialchars($igUrl) ?>" target="_blank" rel="noopener"
                                           title="Instagram"
                                           class="w-7 h-7 rounded-full flex items-center justify-center text-white transition hover:opacity-80"
                                           style="background:radial-gradient(circle at 30% 107%,#fdf497 0%,#fd5949 45%,#d6249f 60%,#285AEB 90%);">
                                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>
                                            </svg>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($fbUrl): ?>
                                        <a href="<?= htmlspecialchars($fbUrl) ?>" target="_blank" rel="noopener"
                                           title="Facebook"
                                           class="w-7 h-7 rounded-full flex items-center justify-center text-white transition hover:opacity-80"
                                           style="background:#1877F2;">
                                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                            </svg>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Mais info -->
                            <div class="px-3 pb-3 flex justify-end">
                                <span class="text-[11px] text-slate-400 hover:text-rs-red transition flex items-center gap-0.5 font-medium">
                                    mais info
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </span>
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

<!-- ═══════════════ MODAL ═══════════════ -->
<div id="dojoModal" class="fixed inset-0 z-50 hidden" aria-modal="true" role="dialog">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeModal()"></div>
    <!-- Panel -->
    <div class="absolute inset-x-4 top-1/2 -translate-y-1/2 max-w-md mx-auto bg-white rounded-2xl shadow-2xl overflow-hidden
                max-h-[90vh] flex flex-col">

        <!-- Header do modal -->
        <div class="flex items-start gap-4 p-5 border-b border-slate-100">
            <div id="mLogo" class="w-20 h-20 rounded-xl flex items-center justify-center shrink-0 overflow-hidden">
                <!-- logo injetada via JS -->
            </div>
            <div class="flex-1 min-w-0">
                <h2 id="mName" class="text-base font-bold text-slate-900 leading-snug"></h2>
                <p id="mCity" class="text-sm text-slate-500 mt-0.5 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span id="mCityText"></span>
                </p>
            </div>
            <button onclick="closeModal()" class="shrink-0 text-slate-400 hover:text-slate-700 transition mt-0.5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Body do modal -->
        <div class="overflow-y-auto flex-1 p-5 space-y-4">

            <!-- Senseis -->
            <div id="mSenseisWrap">
                <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-2">Sensei(s)</p>
                <ul id="mSenseis" class="space-y-1.5"></ul>
            </div>

            <!-- Estilos -->
            <div id="mStylesWrap">
                <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-2">Estilos praticados</p>
                <div id="mStyles" class="flex flex-wrap gap-2"></div>
            </div>

            <!-- Atletas -->
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-1">Atletas cadastrados</p>
                <p id="mStudents" class="text-sm text-slate-700 font-semibold"></p>
            </div>
        </div>

        <!-- Footer com contatos -->
        <div id="mContacts" class="p-5 border-t border-slate-100 flex flex-wrap gap-2"></div>
    </div>
</div>

<?php
// Embed all dojo data as JSON for the modal
$dojoData = [];
foreach ($dojos as $d) {
    $waRaw = $d['phone_whatsapp'] ?? $d['phone'] ?? '';
    $hasWa = !empty($waRaw);
    $n = preg_replace('/\D/', '', $waRaw);
    if (strlen($n) <= 11) $n = '55' . $n;
    $msg = 'Olá! Vi o ' . $d['name'] . ' no site da FGKIRS e gostaria de mais informações.';
    $city = trim(($d['city'] ?? '') . ($d['state'] ? ' – ' . $d['state'] : ''));

    $dojoData[$d['id']] = [
        'name'      => $d['name'],
        'logo'      => $d['logo'] ? '/uploads/dojos/' . $d['logo'] : null,
        'city'      => $city,
        'students'  => (int)$d['student_count'],
        'senseis'   => array_map(fn($s) => [
            'name'       => $s['name'],
            'graduation' => $s['graduation_name'] ?? null,
        ], $d['senseis']),
        'styles'    => $d['styles'],
        'wa'        => $hasWa ? 'https://wa.me/' . $n . '?text=' . rawurlencode($msg) : null,
        'ig'        => !empty($d['instagram']) ? 'https://instagram.com/' . rawurlencode($d['instagram']) : null,
        'fb'        => !empty($d['facebook'])  ? 'https://facebook.com/' . rawurlencode($d['facebook'])  : null,
    ];
}
?>

<script>
const DOJOS = <?= json_encode($dojoData, JSON_UNESCAPED_UNICODE) ?>;

function openModal(id) {
    const d = DOJOS[id];
    if (!d) return;

    // Logo
    document.getElementById('mLogo').innerHTML = d.logo
        ? `<img src="${d.logo}" class="w-full h-full object-contain p-1">`
        : `<svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                     d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
           </svg>`;

    document.getElementById('mName').textContent    = d.name;
    document.getElementById('mCityText').textContent = d.city || '—';
    document.getElementById('mStudents').textContent =
        d.students > 0 ? d.students + ' atleta' + (d.students !== 1 ? 's' : '') : 'Sem atletas cadastrados';

    // Senseis
    const sl = document.getElementById('mSenseis');
    if (d.senseis.length > 0) {
        sl.innerHTML = d.senseis.map(s =>
            `<li class="flex items-center gap-2 text-sm text-slate-700">
                <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <span class="font-medium">${s.name}</span>
                ${s.graduation ? `<span class="text-xs text-slate-400">· ${s.graduation}</span>` : ''}
             </li>`
        ).join('');
        document.getElementById('mSenseisWrap').classList.remove('hidden');
    } else {
        document.getElementById('mSenseisWrap').classList.add('hidden');
    }

    // Estilos
    const st = document.getElementById('mStyles');
    if (d.styles.length > 0) {
        st.innerHTML = d.styles.map(s =>
            `<span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-slate-100 text-slate-700">${s}</span>`
        ).join('');
        document.getElementById('mStylesWrap').classList.remove('hidden');
    } else {
        document.getElementById('mStylesWrap').classList.add('hidden');
    }

    // Contatos
    const contacts = [];
    if (d.wa) contacts.push(`
        <a href="${d.wa}" target="_blank" rel="noopener"
           class="flex items-center gap-2 px-4 py-2 rounded-xl text-white text-sm font-semibold hover:opacity-90 transition"
           style="background:#25D366;">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
            </svg>
            WhatsApp
        </a>`);
    if (d.ig) contacts.push(`
        <a href="${d.ig}" target="_blank" rel="noopener"
           class="flex items-center gap-2 px-4 py-2 rounded-xl text-white text-sm font-semibold hover:opacity-90 transition"
           style="background:radial-gradient(circle at 30% 107%,#fdf497 0%,#fd5949 45%,#d6249f 60%,#285AEB 90%);">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>
            </svg>
            Instagram
        </a>`);
    if (d.fb) contacts.push(`
        <a href="${d.fb}" target="_blank" rel="noopener"
           class="flex items-center gap-2 px-4 py-2 rounded-xl text-white text-sm font-semibold hover:opacity-90 transition"
           style="background:#1877F2;">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
            </svg>
            Facebook
        </a>`);

    document.getElementById('mContacts').innerHTML = contacts.join('') ||
        '<span class="text-sm text-slate-400 italic">Sem contatos cadastrados</span>';

    document.getElementById('dojoModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    document.getElementById('dojoModal').classList.add('hidden');
    document.body.style.overflow = '';
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

// Search
(function () {
    const input = document.getElementById('dojoSearch');
    const empty = document.getElementById('dojoEmpty');
    if (!input) return;
    input.addEventListener('input', function () {
        const term  = this.value.toLowerCase().trim();
        const cards = document.querySelectorAll('#dojoGrid [data-search]');
        let visible = 0;
        cards.forEach(c => {
            const show = c.dataset.search.includes(term);
            c.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        if (empty) empty.classList.toggle('hidden', visible > 0 || term === '');
    });
})();
</script>

<?php require_once __DIR__ . '/partials/public_footer.php'; ?>
