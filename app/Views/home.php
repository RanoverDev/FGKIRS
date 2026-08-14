<?php
$pageTitle = 'FGKIRS – A Força do Karatê Gaúcho';
$pageDesc = 'Federação Gaúcha de Karatê Interestilos – unindo dojos e atletas em todo o Rio Grande do Sul.';
require_once __DIR__ . '/partials/public_header.php';
?>

<!-- ═══════════════════════════════════ HERO SLIDER ═══════════════════════ -->
<section class="relative overflow-hidden" style="min-height: 92vh;">

    <!-- ── Slides (background layers) ── -->
    <?php if (!empty($news)): ?>
        <?php foreach ($news as $i => $post): ?>
            <div class="hero-slide<?= $i === 0 ? ' is-active' : '' ?>" data-idx="<?= $i ?>">
                <div class="ken-bg<?= $i === 0 ? ' playing' : '' ?>" style="background-image: <?= !empty($post['featured_image'])
                            ? "url('/uploads/posts/" . htmlspecialchars($post['featured_image']) . "')"
                            : 'linear-gradient(135deg,#0f172a 0%,#7f1d1d 100%)' ?>;"></div>
                <div class="absolute inset-0" style="background: linear-gradient(to top, rgba(0,0,0,.90) 0%, rgba(0,0,0,.55) 38%, rgba(0,0,0,.18) 100%),
                                        linear-gradient(to right, rgba(0,0,0,.72) 0%, transparent 58%);"></div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="hero-slide is-active">
            <div class="ken-bg playing" style="background: linear-gradient(135deg,#0f172a 0%,#450a0a 100%);"></div>
        </div>
    <?php endif; ?>

    <!-- ── Content overlay ── -->
    <div class="relative z-10 flex flex-col justify-between" style="min-height: 92vh; padding: 2.5rem 1.5rem 2rem;">
        <div class="max-w-7xl mx-auto w-full"><!-- spacer top --></div>

        <!-- Bottom content block -->
        <div class="max-w-7xl mx-auto w-full">

            <!-- Accent bars -->
            <div class="flex gap-2 mb-5">
                <span class="h-0.5 w-7 rounded-full" style="background:#00AB4E;opacity:.75;"></span>
                <span class="h-0.5 w-7 rounded-full" style="background:#EE302F;opacity:.75;"></span>
                <span class="h-0.5 w-7 rounded-full" style="background:#FFCB04;opacity:.75;"></span>
            </div>

            <!-- Tag -->
            <p id="heroTag" class="text-xs font-bold uppercase tracking-[.22em] mb-2" style="color:#FFCB04;">
                <?= !empty($news) ? ($news[0]['type'] === 'event' ? 'Evento' : 'Notícia') : 'FGKIRS' ?>
            </p>

            <!-- Title -->
            <h1 id="heroTitle" class="font-black text-white leading-tight mb-3 max-w-3xl drop-shadow-2xl"
                style="font-size: clamp(2rem, 5vw, 3.75rem); font-family: 'Oswald', sans-serif; letter-spacing: .03em;">
                <?= !empty($news) ? htmlspecialchars($news[0]['title']) : 'A Força do Karatê Gaúcho' ?>
            </h1>

            <!-- Excerpt (first 15 words) -->
            <p id="heroExcerpt" class="text-sm sm:text-base max-w-xl mb-7 leading-relaxed drop-shadow"
                style="color:rgba(255,255,255,.65);">
                <?php
                if (!empty($news)) {
                    $w = array_slice(explode(' ', strip_tags($news[0]['content'])), 0, 15);
                    echo htmlspecialchars(implode(' ', $w)) . '…';
                } else {
                    echo 'Unindo tradição, técnica e espírito esportivo em todo o Rio Grande do Sul desde 1985.';
                }
                ?>
            </p>

            <!-- Buttons -->
            <div class="flex flex-wrap items-center gap-3 mb-8">
                <a href="#dojos"
                    class="inline-flex items-center gap-1.5 text-white font-semibold py-2 px-5 rounded-lg text-sm transition-all duration-200 shadow-lg"
                    style="background:rgba(238,48,47,.85);" onmouseover="this.style.background='#EE302F'"
                    onmouseout="this.style.background='rgba(238,48,47,.85)'">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Encontre um Dojo
                </a>
                <a href="#noticias"
                    class="inline-flex items-center gap-1.5 font-medium py-2 px-5 rounded-lg text-sm transition-all duration-200"
                    style="border:1px solid rgba(255,255,255,.28); color:rgba(255,255,255,.65);"
                    onmouseover="this.style.borderColor='rgba(255,255,255,.65)';this.style.color='#fff'"
                    onmouseout="this.style.borderColor='rgba(255,255,255,.28)';this.style.color='rgba(255,255,255,.65)'">
                    Últimas Notícias
                </a>
            </div>

            <!-- Slide dots -->
            <?php if (count($news) > 1): ?>
                <div id="heroDots" class="flex items-center gap-2">
                    <?php foreach ($news as $i => $_): ?>
                        <button class="hero-dot" onclick="heroGoTo(<?= $i ?>)"
                            style="width:<?= $i === 0 ? '2rem' : '.75rem' ?>;background:<?= $i === 0 ? '#fff' : 'rgba(255,255,255,.3)' ?>;">
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>

</section>

<?php
// ── Live stream block ─────────────────────────────────────────────────────
$lp = $livePost ?? null;
if ($lp && !empty($lp['video_url'])) {
    preg_match('/(?:youtube\.com\/(?:watch\?.*v=|live\/|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $lp['video_url'], $ym);
    $ytId = $ym[1] ?? null;
} else {
    $ytId = null;
}
?>
<?php if ($lp && $ytId): ?>
    <!-- ══════════════════════════ AO VIVO ════════════════════════════════════ -->
    <section class="bg-slate-950 py-6 sm:py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6">

            <div class="flex items-center gap-3 mb-4">
                <span class="relative flex h-3 w-3">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-500 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-red-600"></span>
                </span>
                <span class="text-red-500 text-xs font-bold uppercase tracking-[.2em]">Ao Vivo Agora</span>
            </div>

            <h2 class="text-white text-xl sm:text-2xl font-bold mb-4 leading-snug">
                <?= htmlspecialchars($lp['title']) ?>
            </h2>

            <div class="aspect-video w-full rounded-xl overflow-hidden shadow-2xl">
                <iframe src="https://www.youtube.com/embed/<?= htmlspecialchars($ytId) ?>?autoplay=1&mute=0"
                    class="w-full h-full" frameborder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen>
                </iframe>
            </div>

            <?php if (!empty($lp['content'])): ?>
                <p class="text-slate-400 text-sm mt-4 leading-relaxed max-w-3xl">
                    <?= htmlspecialchars(mb_substr($lp['content'], 0, 220)) ?>
                    <?= mb_strlen($lp['content']) > 220 ? '…' : '' ?>
                </p>
            <?php endif; ?>

        </div>
    </section>
<?php endif; ?>

<!-- ══════════════════════════ CTA ════════════════════════════════════════ -->
<section class="py-16 bg-white text-center">
    <div class="max-w-2xl mx-auto px-4 sm:px-6">
        <p class="text-xs font-bold uppercase tracking-[.22em] mb-3" style="color:#EE302F;">
            Federação Gaúcha de Karatê Interestilos
        </p>
        <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 mb-4 leading-tight">
            Karatê de alto nível no<br class="hidden sm:block"> Rio Grande do Sul
        </h2>
        <p class="text-slate-500 text-sm leading-relaxed mb-8">
            A FGKIRS reúne dojos, senseis e atletas de todo o estado, promovendo competições,
            graduações e o crescimento do karatê como esporte e arte marcial.
        </p>
        <a href="/login" class="inline-flex items-center gap-2 bg-slate-900 hover:bg-slate-700 text-white
                      font-semibold py-3 px-8 rounded-lg text-sm transition-all duration-200 shadow-md">
            Acessar o Sistema
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
        </a>
    </div>
</section>

<!-- ══════════════════════ PRESS RELEASE ══════════════════════════════════ -->
<?php require_once __DIR__ . '/partials/about_block.php'; ?>

<!-- ═══════════════════ NEWS GRID + EVENTS SIDEBAR ═══════════════════════ -->
<section id="noticias" class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">

        <div class="lg:grid lg:grid-cols-4 lg:gap-10">

            <!-- ── NEWS GRID (3/4) ── -->
            <div class="lg:col-span-3">
                <div class="flex items-center gap-3 mb-8">
                    <span class="h-7 w-1 rounded-full bg-rs-red"></span>
                    <h2 class="text-3xl font-bold text-slate-900">Últimas Notícias</h2>
                </div>

                <?php if (!empty($news)): ?>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <?php foreach ($news as $post):
                            $img = !empty($post['featured_image'])
                                ? '/uploads/posts/' . htmlspecialchars($post['featured_image'])
                                : null;
                            $date = date('d/m/Y', strtotime($post['published_at'] ?? $post['created_at']));
                            $excerpt = mb_substr(strip_tags($post['content']), 0, 100);
                            ?>
                            <article
                                class="news-card bg-white rounded-xl shadow-md overflow-hidden border border-slate-100 flex flex-col">
                                <a href="/noticia/<?= $post['slug'] ?? $post['id'] ?>"
                                    class="aspect-video overflow-hidden bg-slate-800 block">
                                    <?php if ($img): ?>
                                        <img src="<?= $img ?>" alt="<?= htmlspecialchars($post['title']) ?>"
                                            class="w-full h-full object-cover object-center transition-transform duration-300 hover:scale-105"
                                            loading="lazy">
                                    <?php else: ?>
                                        <div
                                            class="w-full h-full flex items-center justify-center bg-gradient-to-br from-slate-700 to-slate-900 transition-transform duration-300 hover:scale-105">
                                            <svg class="w-12 h-12 text-slate-500" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                    d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                                            </svg>
                                        </div>
                                    <?php endif; ?>
                                </a>
                                <div class="p-5 flex flex-col flex-1">
                                    <time class="text-xs text-slate-400 mb-2"><?= $date ?></time>
                                    <h3 class="text-base font-bold text-slate-900 leading-snug mb-2 line-clamp-2">
                                        <a href="/noticia/<?= $post['slug'] ?? $post['id'] ?>"
                                            class="hover:text-rs-red transition-colors"><?= htmlspecialchars($post['title']) ?></a>
                                    </h3>
                                    <p class="text-sm text-slate-500 line-clamp-2 flex-1">
                                        <?= htmlspecialchars($excerpt) ?>…
                                    </p>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <div class="mt-8 text-center md:text-left">
                        <a href="/noticias"
                            class="inline-flex items-center gap-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold py-2.5 px-6 rounded-lg text-sm transition-all duration-200">
                            Mais Notícias
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                            </svg>
                        </a>
                    </div>
                <?php else: ?>
                    <p class="text-slate-400 text-sm italic">Nenhuma notícia publicada no momento.</p>
                <?php endif; ?>
            </div>

            <!-- ── EVENTS SIDEBAR (1/4) ── -->
            <aside id="eventos" class="mt-12 lg:mt-0">
                <div class="flex items-center gap-3 mb-8">
                    <span class="h-7 w-1 rounded-full bg-rs-yellow"></span>
                    <h2 class="text-3xl font-bold text-slate-900">Próximos Eventos</h2>
                </div>

                <?php if (!empty($events)): ?>
                    <div class="space-y-4">
                        <?php foreach ($events as $evt):
                            $evtDay = date('d', strtotime($evt['event_date']));
                            $evtMonth = mb_strtoupper(date('M', strtotime($evt['event_date'])));
                            ?>
                            <div
                                class="event-item bg-white rounded-lg p-4 shadow-sm border border-slate-100 flex gap-4 items-start relative hover:shadow-md transition">
                                <a href="/evento/<?= $evt['slug'] ?? $evt['id'] ?>" class="absolute inset-0 z-10"></a>
                                <div class="shrink-0 bg-slate-900 text-white rounded-lg w-12 text-center py-2 leading-tight">
                                    <span class="block text-xl font-black"><?= $evtDay ?></span>
                                    <span class="block text-[10px] font-semibold uppercase tracking-wide text-rs-yellow">
                                        <?= $evtMonth ?>
                                    </span>
                                </div>
                                <div class="min-w-0">
                                    <h4 class="text-sm font-bold text-slate-900 leading-snug mb-1 line-clamp-2">
                                        <?= htmlspecialchars($evt['title']) ?>
                                    </h4>
                                    <?php if (!empty($evt['event_location'])): ?>
                                        <p class="flex items-center gap-1 text-xs text-slate-500">
                                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            <?= htmlspecialchars($evt['event_location']) ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="mt-6 text-center lg:text-left">
                        <a href="/eventos"
                            class="inline-flex items-center gap-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold py-2 px-5 rounded-lg text-sm transition-all duration-200">
                            Mais Eventos
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                            </svg>
                        </a>
                    </div>
                <?php else: ?>
                    <p class="text-slate-400 text-sm italic">Nenhum evento agendado no momento.</p>
                <?php endif; ?>
            </aside>

        </div>
    </div>
</section>

<!-- ══════════════════════════ GALERIAS ═════════════════════════════════ -->
<?php if (!empty($galleries)): ?>
    <section id="galerias" class="py-20 bg-slate-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">

            <div class="flex items-center justify-between mb-10">
                <div class="flex items-center gap-3">
                    <span class="h-7 w-1 rounded-full bg-rs-yellow"></span>
                    <h2 class="text-3xl font-bold text-slate-900">Galeria de Fotos</h2>
                </div>
                <a href="/galerias"
                    class="hidden sm:inline-flex items-center gap-2 text-sm font-semibold text-slate-600 hover:text-slate-900 transition">
                    Ver todas
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 8l4 4m0 0l-4 4m4-4H3" />
                    </svg>
                </a>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-3 gap-3 sm:gap-4">
                <?php foreach ($galleries as $gal):
                    $cover = !empty($gal['cover_filename'])
                        ? '/uploads/galleries/' . $gal['cover_filename']
                        : (!empty($gal['cover_image']) ? '/uploads/galleries/' . $gal['cover_image'] : null);
                    $slug = $gal['slug'] ?? $gal['id'];
                    $evtDate = !empty($gal['event_date']) ? date('d/m/Y', strtotime($gal['event_date'])) : '';
                    ?>
                    <a href="/galeria/<?= htmlspecialchars($slug) ?>#lightbox"
                        class="group relative rounded-xl overflow-hidden aspect-[4/3] bg-slate-800 shadow-md hover:shadow-xl transition-shadow duration-300 block">

                        <?php if ($cover): ?>
                            <img src="<?= htmlspecialchars($cover) ?>" alt="<?= htmlspecialchars($gal['title']) ?>"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        <?php else: ?>
                            <div
                                class="w-full h-full flex items-center justify-center bg-gradient-to-br from-slate-700 to-slate-900">
                                <svg class="w-10 h-10 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                        <?php endif; ?>

                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>

                        <div class="absolute bottom-0 left-0 right-0 p-4">
                            <?php if ($evtDate): ?>
                                <p class="text-xs text-white/60 mb-1"><?= $evtDate ?></p>
                            <?php endif; ?>
                            <h3 class="text-sm font-bold text-white leading-snug line-clamp-2">
                                <?= htmlspecialchars($gal['title']) ?>
                            </h3>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="mt-8 text-center sm:hidden">
                <a href="/galerias"
                    class="inline-flex items-center gap-2 bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold py-2.5 px-6 rounded-lg text-sm transition">
                    Ver todas as galerias
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 8l4 4m0 0l-4 4m4-4H3" />
                    </svg>
                </a>
            </div>

        </div>
    </section>
<?php endif; ?>

<!-- ══════════════════════════ DOJOS ════════════════════════════════════ -->
<section id="dojos" class="py-20 bg-slate-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">

        <div class="text-center mb-12">
            <div class="flex justify-center gap-2 mb-4">
                <span class="h-1 w-8 rounded-full bg-rs-green"></span>
                <span class="h-1 w-8 rounded-full bg-rs-red"></span>
                <span class="h-1 w-8 rounded-full bg-rs-yellow"></span>
            </div>
            <h2 class="text-4xl font-bold text-slate-900 mb-2">Dojos Oficiais</h2>
            <p class="text-slate-500 text-sm">Dojos filiados à FGKIRS em todo o Rio Grande do Sul</p>
        </div>

        <?php if (!empty($dojos)): ?>

            <div class="max-w-md mx-auto mb-10 relative">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input id="dojoSearch" type="search" placeholder="Buscar por cidade ou dojo…"
                       class="w-full pl-10 pr-4 py-3 border border-slate-200 rounded-lg bg-white
                              focus:outline-none focus:ring-2 focus:ring-rs-red focus:border-transparent
                              text-sm shadow-sm">
            </div>

            <?php
            $homeDojoData = [];
            foreach ($dojos as $d) {
                $waRaw = $d['phone_whatsapp'] ?? $d['phone'] ?? '';
                $n = preg_replace('/\D/', '', $waRaw);
                if (strlen($n) <= 11 && $n !== '') $n = '55' . $n;
                $msg = 'Olá! Vi o ' . $d['name'] . ' no site da FGKIRS e gostaria de mais informações.';
                $city = trim(($d['city'] ?? '') . ($d['state'] ? ' – ' . $d['state'] : ''));
                $homeDojoData[$d['id']] = [
                    'name'     => $d['name'],
                    'logo'     => $d['logo'] ? '/uploads/dojos/' . $d['logo'] : null,
                    'city'     => $city,
                    'students' => (int)($d['student_count'] ?? 0),
                    'senseis'  => array_map(fn($s) => ['name' => $s['name'], 'graduation' => $s['graduation_name'] ?? null], $d['senseis'] ?? []),
                    'styles'   => $d['styles'] ?? [],
                    'wa'       => $n ? 'https://wa.me/' . $n . '?text=' . rawurlencode($msg) : null,
                    'ig'       => !empty($d['instagram']) ? 'https://instagram.com/' . rawurlencode($d['instagram']) : null,
                    'fb'       => !empty($d['facebook'])  ? 'https://facebook.com/' . rawurlencode($d['facebook'])  : null,
                ];
            }
            ?>

            <div id="dojoGrid" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                <?php foreach ($dojos as $dojo):
                    $dd   = $homeDojoData[$dojo['id']];
                    $city = $dd['city'];
                ?>
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden flex flex-col
                                hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 cursor-pointer"
                         data-search="<?= strtolower(htmlspecialchars($dojo['name'] . ' ' . ($dojo['city'] ?? '') . ' ' . ($dojo['sensei_name'] ?? ''))) ?>"
                         onclick="openHomeDojo(<?= $dojo['id'] ?>)">

                        <!-- Logo -->
                        <div class="flex items-center justify-center overflow-hidden" style="height:180px;">
                            <?php if (!empty($dojo['logo'])): ?>
                                <img src="/uploads/dojos/<?= htmlspecialchars($dojo['logo']) ?>"
                                     alt="<?= htmlspecialchars($dojo['name']) ?>"
                                     class="w-full h-full object-contain">
                            <?php else: ?>
                                <svg class="w-16 h-16 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                          d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                            <?php endif; ?>
                        </div>

                        <div class="h-0.5 bg-rs-red shrink-0"></div>

                        <div class="px-3 py-3 flex flex-col items-center text-center gap-1 flex-1">
                            <h3 class="text-sm font-bold text-slate-900 leading-snug line-clamp-2">
                                <?= htmlspecialchars($dojo['name']) ?>
                            </h3>
                            <?php if ($city): ?>
                                <p class="text-[11px] text-slate-400 flex items-center gap-1">
                                    <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    <?= htmlspecialchars($city) ?>
                                </p>
                            <?php endif; ?>

                            <div class="flex items-center justify-center gap-2 mt-1" onclick="event.stopPropagation()">
                                <?php if ($dd['wa']): ?>
                                    <a href="<?= htmlspecialchars($dd['wa']) ?>" target="_blank" rel="noopener"
                                       title="WhatsApp" class="w-7 h-7 rounded-full flex items-center justify-center text-white hover:opacity-80 transition"
                                       style="background:#25D366;">
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                    </a>
                                <?php endif; ?>
                                <?php if ($dd['ig']): ?>
                                    <a href="<?= htmlspecialchars($dd['ig']) ?>" target="_blank" rel="noopener"
                                       title="Instagram" class="w-7 h-7 rounded-full flex items-center justify-center text-white hover:opacity-80 transition"
                                       style="background:radial-gradient(circle at 30% 107%,#fdf497 0%,#fd5949 45%,#d6249f 60%,#285AEB 90%);">
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                                    </a>
                                <?php endif; ?>
                                <?php if ($dd['fb']): ?>
                                    <a href="<?= htmlspecialchars($dd['fb']) ?>" target="_blank" rel="noopener"
                                       title="Facebook" class="w-7 h-7 rounded-full flex items-center justify-center text-white hover:opacity-80 transition"
                                       style="background:#1877F2;">
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>

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

            <p id="dojoEmpty" class="hidden text-center text-slate-400 text-sm mt-8 italic">
                Nenhum dojo encontrado para esta busca.
            </p>

            <div class="mt-10 text-center">
                <a href="/dojos"
                   class="inline-flex items-center gap-2 bg-slate-900 hover:bg-slate-700 text-white
                          font-semibold py-2.5 px-8 rounded-lg text-sm transition-all duration-200 shadow-md">
                    Ver todos os Dojos
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>

        <?php else: ?>
            <p class="text-center text-slate-400 text-sm italic">Nenhum dojo cadastrado no momento.</p>
        <?php endif; ?>

<!-- Modal de dojo (home) -->
<div id="homeDojoModal" class="fixed inset-0 z-50 hidden" aria-modal="true" role="dialog">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeHomeDojo()"></div>
    <div class="absolute inset-x-4 top-1/2 -translate-y-1/2 max-w-md mx-auto bg-white rounded-2xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
        <div class="flex items-start gap-4 p-5 border-b border-slate-100">
            <div id="hmLogo" class="w-20 h-20 rounded-xl flex items-center justify-center shrink-0 overflow-hidden"></div>
            <div class="flex-1 min-w-0">
                <h2 id="hmName" class="text-base font-bold text-slate-900 leading-snug"></h2>
                <p class="text-sm text-slate-500 mt-0.5 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span id="hmCity"></span>
                </p>
            </div>
            <button onclick="closeHomeDojo()" class="shrink-0 text-slate-400 hover:text-slate-700 transition mt-0.5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="overflow-y-auto flex-1 p-5 space-y-4">
            <div id="hmSenseisWrap"><p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-2">Sensei(s)</p><ul id="hmSenseis" class="space-y-1.5"></ul></div>
            <div id="hmStylesWrap"><p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-2">Estilos praticados</p><div id="hmStyles" class="flex flex-wrap gap-2"></div></div>
            <div><p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-1">Atletas cadastrados</p><p id="hmStudents" class="text-sm text-slate-700 font-semibold"></p></div>
        </div>
        <div id="hmContacts" class="p-5 border-t border-slate-100 flex flex-wrap gap-2"></div>
    </div>
</div>

<script>
const HOME_DOJOS = <?= json_encode($homeDojoData, JSON_UNESCAPED_UNICODE) ?>;
function openHomeDojo(id) {
    const d = HOME_DOJOS[id]; if (!d) return;
    document.getElementById('hmLogo').innerHTML = d.logo ? `<img src="${d.logo}" class="w-full h-full object-contain p-1">` : `<svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>`;
    document.getElementById('hmName').textContent    = d.name;
    document.getElementById('hmCity').textContent    = d.city || '—';
    document.getElementById('hmStudents').textContent = d.students > 0 ? d.students + ' atleta' + (d.students !== 1 ? 's' : '') : 'Sem atletas cadastrados';
    const sl = document.getElementById('hmSenseis');
    if (d.senseis.length) { sl.innerHTML = d.senseis.map(s => `<li class="flex items-center gap-2 text-sm text-slate-700"><svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg><span class="font-medium">${s.name}</span>${s.graduation ? `<span class="text-xs text-slate-400">· ${s.graduation}</span>` : ''}</li>`).join(''); document.getElementById('hmSenseisWrap').classList.remove('hidden'); } else { document.getElementById('hmSenseisWrap').classList.add('hidden'); }
    const st = document.getElementById('hmStyles');
    if (d.styles.length) { st.innerHTML = d.styles.map(s => `<span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-slate-100 text-slate-700">${s}</span>`).join(''); document.getElementById('hmStylesWrap').classList.remove('hidden'); } else { document.getElementById('hmStylesWrap').classList.add('hidden'); }
    const waSvg = `<svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>`;
    const igSvg = `<svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>`;
    const fbSvg = `<svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>`;
    const contacts = [];
    if (d.wa) contacts.push(`<a href="${d.wa}" target="_blank" rel="noopener" class="flex items-center gap-2 px-4 py-2 rounded-xl text-white text-sm font-semibold hover:opacity-90 transition" style="background:#25D366;">${waSvg} WhatsApp</a>`);
    if (d.ig) contacts.push(`<a href="${d.ig}" target="_blank" rel="noopener" class="flex items-center gap-2 px-4 py-2 rounded-xl text-white text-sm font-semibold hover:opacity-90 transition" style="background:radial-gradient(circle at 30% 107%,#fdf497 0%,#fd5949 45%,#d6249f 60%,#285AEB 90%);">${igSvg} Instagram</a>`);
    if (d.fb) contacts.push(`<a href="${d.fb}" target="_blank" rel="noopener" class="flex items-center gap-2 px-4 py-2 rounded-xl text-white text-sm font-semibold hover:opacity-90 transition" style="background:#1877F2;">${fbSvg} Facebook</a>`);
    document.getElementById('hmContacts').innerHTML = contacts.join('') || '<span class="text-sm text-slate-400 italic">Sem contatos cadastrados</span>';
    document.getElementById('homeDojoModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}
function closeHomeDojo() { document.getElementById('homeDojoModal').classList.add('hidden'); document.body.style.overflow = ''; }
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeHomeDojo(); });
</script>

    </div>
</section>

<!-- ══════════════════════════ SCRIPTS ══════════════════════════════════ -->
<script>
    // ── Hero Slider ──────────────────────────────────────────────────────────
    (function () {
        const slides = document.querySelectorAll('.hero-slide');
        const dots = document.querySelectorAll('.hero-dot');
        if (!slides.length) return;

        const heroNews = <?= !empty($news) ? json_encode(array_map(function ($p) {
            $words = array_slice(explode(' ', strip_tags($p['content'])), 0, 15);
            return [
                'title' => $p['title'],
                'excerpt' => implode(' ', $words) . '…',
                'type' => $p['type'],
            ];
        }, $news), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) : '[]' ?>;

        let cur = 0;

        function activate(idx) {
            slides[cur].classList.remove('is-active');
            if (dots[cur]) { dots[cur].style.width = '.75rem'; dots[cur].style.background = 'rgba(255,255,255,.3)'; }

            cur = ((idx % slides.length) + slides.length) % slides.length;

            slides[cur].classList.add('is-active');
            if (dots[cur]) { dots[cur].style.width = '2rem'; dots[cur].style.background = '#fff'; }

            const bg = slides[cur].querySelector('.ken-bg');
            if (bg) {
                bg.classList.remove('playing');
                void bg.offsetWidth;
                bg.classList.add('playing');
            }

            const n = heroNews[cur];
            if (n) {
                const el = {
                    title: document.getElementById('heroTitle'),
                    excerpt: document.getElementById('heroExcerpt'),
                    tag: document.getElementById('heroTag'),
                };
                if (el.title) el.title.textContent = n.title;
                if (el.excerpt) el.excerpt.textContent = n.excerpt;
                if (el.tag) el.tag.textContent = n.type === 'event' ? 'Evento' : 'Notícia';
            }
        }

        window.heroGoTo = activate;

        if (slides.length > 1) {
            setInterval(function () { activate(cur + 1); }, 5000);
        }
    })();

    // ── Dojo search ──────────────────────────────────────────────────────────
    (function () {
        const input = document.getElementById('dojoSearch');
        const empty = document.getElementById('dojoEmpty');
        if (!input) return;

        input.addEventListener('input', function () {
            const term = this.value.toLowerCase().trim();
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