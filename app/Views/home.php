<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FGKIRS – A Força do Karatê Gaúcho</title>
    <meta name="description" content="Federação Gaúcha de Karatê Interestilos – unindo dojos e atletas em todo o Rio Grande do Sul.">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        rs: { green: '#00AB4E', red: '#EE302F', yellow: '#FFCB04' }
                    },
                    fontFamily: {
                        heading: ['Oswald', 'sans-serif'],
                        body:    ['Inter',  'sans-serif'],
                    }
                }
            }
        }
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet">

    <style>
        body        { font-family: 'Inter', sans-serif; }
        h1,h2,h3,h4 { font-family: 'Oswald', sans-serif; letter-spacing: .04em; }

        .rs-bar { background: linear-gradient(to right, #00AB4E 33.33%, #EE302F 33.33% 66.66%, #FFCB04 66.66%); }

        .news-card { transition: transform .3s ease, box-shadow .3s ease; }
        .news-card:hover { transform: scale(1.02); box-shadow: 0 25px 50px -12px rgba(0,0,0,.25); }

        .hero-bg {
            background-color: #0f172a;
            background-image:
                radial-gradient(ellipse 80% 50% at 50% 0%, rgba(238,48,47,.18) 0%, transparent 60%),
                radial-gradient(ellipse 40% 60% at 80% 80%, rgba(0,171,78,.12) 0%, transparent 55%);
        }

        .event-item { border-left: 3px solid #FFCB04; }
        .dojo-card  { border-left: 4px solid #EE302F; }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 antialiased">

    <!-- RS flag stripe -->
    <div class="rs-bar h-1 w-full"></div>

    <!-- ═══════════════════════════════════ HEADER ═══════════════════════════ -->
    <header class="bg-slate-900 text-white sticky top-0 z-50 shadow-2xl">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-3 flex items-center justify-between">

            <div class="flex items-center gap-4">
                <img src="/assets/images/logo-fgkirs-white.png" alt="FGKIRS" class="h-10 w-auto">
                <div class="hidden md:block leading-tight">
                    <p class="text-[10px] uppercase tracking-[.2em] text-slate-400">Federação Gaúcha de Karatê</p>
                    <p class="text-sm font-semibold">Interestilos Rio-Grandense</p>
                </div>
            </div>

            <nav class="hidden md:flex items-center gap-6 text-sm text-slate-300">
                <a href="#noticias" class="hover:text-white transition">Notícias</a>
                <a href="#eventos"  class="hover:text-white transition">Eventos</a>
                <a href="#dojos"    class="hover:text-white transition">Dojos</a>
            </nav>

            <a href="/login"
               class="flex items-center gap-2 bg-rs-red hover:bg-red-700 text-white font-semibold py-2 px-5 rounded-lg text-sm transition-all duration-200 shadow-lg">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                Área Administrativa
            </a>

        </div>
    </header>

    <!-- ═══════════════════════════════════ HERO ════════════════════════════ -->
    <section class="hero-bg text-white relative overflow-hidden min-h-[88vh] flex items-center">

        <!-- Decorative vertical stripes -->
        <div class="absolute left-0 inset-y-0 w-1.5 opacity-70"
             style="background: linear-gradient(to bottom, #00AB4E, #EE302F, #FFCB04)"></div>
        <div class="absolute right-0 inset-y-0 w-1 bg-slate-700 opacity-40"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-24 w-full">
            <div class="max-w-3xl">

                <!-- Colored accent bars -->
                <div class="flex gap-2 mb-8">
                    <span class="h-1 w-10 rounded-full bg-rs-green"></span>
                    <span class="h-1 w-10 rounded-full bg-rs-red"></span>
                    <span class="h-1 w-10 rounded-full bg-rs-yellow"></span>
                </div>

                <h1 class="text-6xl sm:text-7xl md:text-8xl font-bold leading-none mb-4">
                    FGKIRS
                </h1>
                <p class="text-2xl sm:text-3xl font-semibold text-slate-200 mb-4">
                    A Força do Karatê Gaúcho
                </p>
                <p class="text-base sm:text-lg text-slate-400 mb-10 max-w-xl leading-relaxed">
                    Unindo tradição, técnica e espírito esportivo em todo o
                    Rio Grande do Sul desde 1985.
                </p>

                <div class="flex flex-col sm:flex-row gap-4">
                    <a href="#dojos"
                       class="inline-flex items-center justify-center gap-2 bg-rs-red hover:bg-red-700
                              text-white font-bold py-4 px-8 rounded-lg text-lg transition-all duration-200
                              shadow-lg hover:shadow-rs-red/30">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Encontre um Dojo
                    </a>
                    <a href="#noticias"
                       class="inline-flex items-center justify-center gap-2 border-2 border-slate-600
                              hover:border-slate-300 text-slate-300 hover:text-white font-semibold
                              py-4 px-8 rounded-lg text-lg transition-all duration-200">
                        Últimas Notícias
                    </a>
                </div>

            </div>
        </div>

        <!-- Stats badges (bottom-right) -->
        <?php if (!empty($dojos)): ?>
        <div class="absolute bottom-8 right-6 hidden lg:flex gap-4">
            <div class="bg-slate-800/80 backdrop-blur border border-slate-700 rounded-xl px-5 py-3 text-center">
                <p class="text-3xl font-black text-rs-green"><?= count($dojos) ?></p>
                <p class="text-xs text-slate-400 uppercase tracking-widest mt-0.5">Dojos</p>
            </div>
            <div class="bg-slate-800/80 backdrop-blur border border-slate-700 rounded-xl px-5 py-3 text-center">
                <p class="text-3xl font-black text-rs-yellow"><?= count($events) ?></p>
                <p class="text-xs text-slate-400 uppercase tracking-widest mt-0.5">Eventos</p>
            </div>
        </div>
        <?php endif; ?>

    </section>

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
                            $img  = !empty($post['featured_image'])
                                    ? htmlspecialchars($post['featured_image'])
                                    : null;
                            $date = date('d/m/Y', strtotime($post['published_at'] ?? $post['created_at']));
                            $excerpt = mb_substr(strip_tags($post['content']), 0, 100);
                        ?>
                        <article class="news-card bg-white rounded-xl shadow-md overflow-hidden border border-slate-100 flex flex-col">

                            <!-- Image -->
                            <div class="aspect-video overflow-hidden bg-slate-800">
                                <?php if ($img): ?>
                                <img src="<?= $img ?>"
                                     alt="<?= htmlspecialchars($post['title']) ?>"
                                     class="w-full h-full object-cover object-center"
                                     loading="lazy">
                                <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-slate-700 to-slate-900">
                                    <svg class="w-12 h-12 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                              d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                                    </svg>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Body -->
                            <div class="p-5 flex flex-col flex-1">
                                <?php if (!empty($post['category_name'])): ?>
                                <span class="text-xs font-semibold uppercase tracking-widest text-rs-red mb-1">
                                    <?= htmlspecialchars($post['category_name']) ?>
                                </span>
                                <?php endif; ?>
                                <time class="text-xs text-slate-400 mb-2"><?= $date ?></time>
                                <h3 class="text-base font-bold text-slate-900 leading-snug mb-2 line-clamp-2">
                                    <?= htmlspecialchars($post['title']) ?>
                                </h3>
                                <p class="text-sm text-slate-500 line-clamp-2 flex-1">
                                    <?= htmlspecialchars($excerpt) ?>…
                                </p>
                            </div>

                        </article>
                        <?php endforeach; ?>
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
                            $evtDate = date('d/m/Y', strtotime($evt['event_date']));
                            $evtDay  = date('d', strtotime($evt['event_date']));
                            $evtMonth = strtoupper(strftime('%b', strtotime($evt['event_date'])));
                            // fallback for PHP 8.1+ where strftime is deprecated
                            $evtMonth = mb_strtoupper(date('M', strtotime($evt['event_date'])));
                        ?>
                        <div class="event-item bg-white rounded-lg p-4 shadow-sm border border-slate-100 flex gap-4 items-start">
                            <!-- Date badge -->
                            <div class="shrink-0 bg-slate-900 text-white rounded-lg w-12 text-center py-2 leading-tight">
                                <span class="block text-xl font-black"><?= $evtDay ?></span>
                                <span class="block text-[10px] font-semibold uppercase tracking-wide text-rs-yellow">
                                    <?= $evtMonth ?>
                                </span>
                            </div>
                            <!-- Info -->
                            <div class="min-w-0">
                                <h4 class="text-sm font-bold text-slate-900 leading-snug mb-1 line-clamp-2">
                                    <?= htmlspecialchars($evt['title']) ?>
                                </h4>
                                <?php if (!empty($evt['event_location'])): ?>
                                <p class="flex items-center gap-1 text-xs text-slate-500">
                                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    <?= htmlspecialchars($evt['event_location']) ?>
                                </p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-slate-400 text-sm italic">Nenhum evento agendado no momento.</p>
                    <?php endif; ?>
                </aside>

            </div>
        </div>
    </section>

    <!-- ══════════════════════════ DOJOS ════════════════════════════════════ -->
    <section id="dojos" class="py-20 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">

            <!-- Section header -->
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

            <!-- Search -->
            <div class="max-w-md mx-auto mb-10 relative">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input id="dojoSearch" type="search"
                       placeholder="Buscar por cidade ou dojo…"
                       class="w-full pl-10 pr-4 py-3 border border-slate-200 rounded-lg bg-white
                              focus:outline-none focus:ring-2 focus:ring-rs-red focus:border-transparent
                              text-sm shadow-sm">
            </div>

            <!-- Grid -->
            <div id="dojoGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($dojos as $dojo): ?>
                <div class="dojo-card bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition-shadow duration-200"
                     data-search="<?= strtolower(htmlspecialchars($dojo['name'] . ' ' . ($dojo['city'] ?? ''))) ?>">

                    <div class="flex items-start gap-4 mb-4">
                        <!-- Logo / placeholder -->
                        <div class="shrink-0 w-14 h-14 rounded-xl overflow-hidden bg-slate-100 border border-slate-200 flex items-center justify-center">
                            <?php if (!empty($dojo['logo'])): ?>
                            <img src="<?= htmlspecialchars($dojo['logo']) ?>"
                                 alt="Logo <?= htmlspecialchars($dojo['name']) ?>"
                                 class="w-full h-full object-contain p-1">
                            <?php else: ?>
                            <svg class="w-7 h-7 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
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
                            <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <span><strong class="text-slate-700">Sensei:</strong> <?= htmlspecialchars($dojo['sensei_name']) ?></span>
                        </p>
                        <?php endif; ?>

                        <?php if (!empty($dojo['address'])): ?>
                        <p class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-slate-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span><?= htmlspecialchars($dojo['address']) ?></span>
                        </p>
                        <?php endif; ?>

                        <?php if (!empty($dojo['phone'])): ?>
                        <p class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498A1 1 0 0121 15.72V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
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
                                  d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                    </a>
                    <?php endif; ?>

                </div>
                <?php endforeach; ?>
            </div>

            <!-- Empty-state after search -->
            <p id="dojoEmpty" class="hidden text-center text-slate-400 text-sm mt-8 italic">
                Nenhum dojo encontrado para esta busca.
            </p>

            <?php else: ?>
            <p class="text-center text-slate-400 text-sm italic">
                Nenhum dojo cadastrado no momento.
            </p>
            <?php endif; ?>

        </div>
    </section>

    <!-- ══════════════════════════ FOOTER ═══════════════════════════════════ -->
    <footer class="bg-slate-900 text-white pt-14 pb-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-10 mb-10">

                <!-- Brand -->
                <div>
                    <img src="/assets/images/logo-fgkirs-white.png" alt="FGKIRS" class="h-14 mb-4">
                    <p class="text-slate-400 text-sm leading-relaxed max-w-xs">
                        A Federação Gaúcha de Karatê Interestilos une dojos e atletas
                        em todo o Rio Grande do Sul, promovendo a excelência técnica e
                        o espírito esportivo do Karatê.
                    </p>
                    <!-- RS flag stripe -->
                    <div class="rs-bar h-1 rounded-full mt-5 w-24 opacity-70"></div>
                    <!-- Affiliations -->
                    <div class="mt-5 flex items-center gap-4">
                        <img src="/assets/images/logo-wukf.png"
                             alt="WUKF – World Union of Karate-Do Federations"
                             class="h-12 w-12 object-contain opacity-80 hover:opacity-100 transition"
                             title="World Union of Karate-Do Federations">
                        <img src="/assets/images/logo-cbki.png"
                             alt="CBKI – Confederação Brasileira de Karatê Interestilos"
                             class="h-10 object-contain opacity-80 hover:opacity-100 transition"
                             title="Confederação Brasileira de Karatê Interestilos">
                    </div>
                </div>

                <!-- Links -->
                <div>
                    <h4 class="text-sm font-bold uppercase tracking-widest text-slate-300 mb-4">Navegação</h4>
                    <ul class="space-y-2 text-slate-400 text-sm">
                        <li><a href="#noticias" class="hover:text-white transition">Notícias</a></li>
                        <li><a href="#eventos"  class="hover:text-white transition">Próximos Eventos</a></li>
                        <li><a href="#dojos"    class="hover:text-white transition">Dojos Oficiais</a></li>
                        <li><a href="/login"    class="hover:text-white transition">Área Administrativa</a></li>
                    </ul>
                </div>

                <!-- Contact -->
                <div>
                    <h4 class="text-sm font-bold uppercase tracking-widest text-slate-300 mb-4">Contato</h4>
                    <ul class="space-y-2 text-slate-400 text-sm">
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-slate-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            contato@fgkirs.com.br
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-slate-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            Rio Grande do Sul, Brasil
                        </li>
                    </ul>
                </div>

            </div>

            <div class="border-t border-slate-800 pt-6 flex flex-col sm:flex-row justify-between gap-2
                        text-slate-500 text-xs">
                <p>&copy; <?= date('Y') ?> FGKIRS – Federação Gaúcha de Karatê Interestilos. Todos os direitos reservados.</p>
                <p>Desenvolvido com PHP 8.x · MVC · Tailwind CSS</p>
            </div>

        </div>
    </footer>

    <!-- ══════════════════════════ SCRIPTS ══════════════════════════════════ -->
    <script>
        const searchInput = document.getElementById('dojoSearch');
        const dojoEmpty   = document.getElementById('dojoEmpty');

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                const term  = this.value.toLowerCase().trim();
                const cards = document.querySelectorAll('#dojoGrid [data-search]');
                let visible = 0;

                cards.forEach(card => {
                    const match = card.dataset.search.includes(term);
                    card.style.display = match ? '' : 'none';
                    if (match) visible++;
                });

                dojoEmpty.classList.toggle('hidden', visible > 0 || term === '');
            });
        }
    </script>

</body>
</html>
