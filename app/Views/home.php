<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FGKIRS – A Força do Karatê Gaúcho</title>
    <meta name="description"
        content="Federação Gaúcha de Karatê Interestilos – unindo dojos e atletas em todo o Rio Grande do Sul.">

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
                        body: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Oswald:wght@500;600;700&display=swap"
        rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        h1,
        h2,
        h3,
        h4 {
            font-family: 'Oswald', sans-serif;
            letter-spacing: .04em;
        }

        .rs-bar {
            background: linear-gradient(to right, #00AB4E 33.33%, #EE302F 33.33% 66.66%, #FFCB04 66.66%);
        }

        .news-card {
            transition: transform .3s ease, box-shadow .3s ease;
        }

        .news-card:hover {
            transform: scale(1.02);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, .25);
        }

        .event-item {
            border-left: 3px solid #FFCB04;
        }

        .dojo-card {
            border-left: 4px solid #EE302F;
        }

        /* ── Hero Slider ── */
        @keyframes kenburns {
            from {
                transform: scale(1) translate(0, 0);
            }

            to {
                transform: scale(1.18) translate(-2%, -1%);
            }
        }

        .hero-slide {
            position: absolute;
            inset: 0;
            opacity: 0;
            transition: opacity 1s ease;
        }

        .hero-slide.is-active {
            opacity: 1;
        }

        .ken-bg {
            position: absolute;
            inset: 0;
            background-size: cover;
            background-position: center;
            transform-origin: center center;
            will-change: transform;
        }

        .ken-bg.playing {
            animation: kenburns 9s ease-out forwards;
        }

        .hero-dot {
            height: 3px;
            border-radius: 9999px;
            transition: width .4s ease, background-color .4s ease;
            cursor: pointer;
            border: none;
            padding: 0;
            display: block;
        }

        #heroTitle,
        #heroExcerpt,
        #heroTag {
            transition: opacity .4s ease;
        }
    </style>
</head>
<?php
$fp = $profile ?? [];
$waNumber = preg_replace('/\D/', '', $fp['whatsapp'] ?? '');
if ($waNumber && !str_starts_with($waNumber, '55')) {
    $waNumber = '55' . $waNumber;
}
?>

<body class="bg-slate-50 text-slate-900 antialiased">

    <!-- RS flag stripe -->
    <div class="rs-bar h-1 w-full"></div>

    <!-- ══════════════════════════ TOP BAR ════════════════════════════════════ -->
    <div class="bg-slate-800 border-b border-slate-700/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-1.5 flex items-center justify-end gap-5 text-slate-400 text-xs">

            <?php if (!empty($fp['email'])): ?>
                <a href="mailto:<?= htmlspecialchars($fp['email']) ?>"
                    class="flex items-center gap-1.5 hover:text-white transition">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <span class="hidden sm:inline"><?= htmlspecialchars($fp['email']) ?></span>
                </a>
            <?php endif; ?>

            <?php if ($waNumber): ?>
                <a href="https://wa.me/<?= $waNumber ?>" target="_blank" rel="noopener"
                    class="flex items-center gap-1.5 hover:text-white transition">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                    </svg>
                    <span class="hidden sm:inline"><?= htmlspecialchars($fp['whatsapp']) ?></span>
                </a>
            <?php endif; ?>

            <?php if (!empty($fp['phone'])): ?>
                <span class="hidden md:flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498A1 1 0 0121 15.72V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                    <?= htmlspecialchars($fp['phone']) ?>
                </span>
            <?php endif; ?>

            <?php if (!empty($fp['facebook'])): ?>
                <a href="<?= htmlspecialchars($fp['facebook']) ?>" target="_blank" rel="noopener"
                    class="hover:text-white transition" title="Facebook">
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M24 12.073C24 5.405 18.627 0 12 0S0 5.405 0 12.073C0 18.1 4.388 23.094 10.125 24v-8.437H7.078v-3.49h3.047V9.41c0-3.025 1.792-4.697 4.533-4.697 1.312 0 2.686.235 2.686.235v2.97h-1.513c-1.491 0-1.956.93-1.956 1.884v2.25h3.328l-.532 3.49h-2.796V24C19.612 23.094 24 18.1 24 12.073z" />
                    </svg>
                </a>
            <?php endif; ?>

            <?php if (!empty($fp['instagram'])): ?>
                <a href="<?= htmlspecialchars($fp['instagram']) ?>" target="_blank" rel="noopener"
                    class="hover:text-white transition" title="Instagram">
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z" />
                    </svg>
                </a>
            <?php endif; ?>

        </div>
    </div>

    <!-- ═══════════════════════════════════ HEADER ═══════════════════════════ -->
    <header class="bg-slate-900 text-white sticky top-0 z-50 shadow-2xl">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-3 flex items-center justify-between">

            <div class="flex items-center gap-4">
                <img src="/assets/images/logo-fgkirs-white.png"
                    alt="Federação Gaúcha de Karatê Interestilos do Rio Grande do Sul" class="h-10 w-auto">
            </div>

            <nav class="hidden md:flex items-center gap-6 text-sm text-slate-300">
                <a href="/a-fgkirs" class="hover:text-white transition">A FGKIRS</a>
                <a href="/noticias" class="hover:text-white transition">Notícias</a>
                <a href="/eventos" class="hover:text-white transition">Eventos</a>
                <a href="/dojos" class="hover:text-white transition">Dojos</a>
                <a href="/contato" class="hover:text-white transition">Contatos</a>
            </nav>

            <button id="mobileMenuBtn" class="md:hidden text-slate-300 hover:text-white p-1">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>

        </div>

        <div id="mobileMenu" class="hidden md:hidden border-t border-slate-700/50 px-4 pb-3 space-y-1 text-sm">
            <a href="/a-fgkirs" class="block py-2 text-slate-300 hover:text-white transition">A FGKIRS</a>
            <a href="/noticias" class="block py-2 text-slate-300 hover:text-white transition">Notícias</a>
            <a href="/eventos" class="block py-2 text-slate-300 hover:text-white transition">Eventos</a>
            <a href="/dojos" class="block py-2 text-slate-300 hover:text-white transition">Dojos</a>
            <a href="/contato" class="block py-2 text-slate-300 hover:text-white transition">Contatos</a>
            <a href="/login" class="block py-2 text-slate-300 hover:text-white transition">Área Administrativa</a>
        </div>
    </header>

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

                <!-- Buttons (smaller, discreet) -->
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

                <!-- Cabeçalho ao vivo -->
                <div class="flex items-center gap-3 mb-4">
                    <span class="relative flex h-3 w-3">
                        <span
                            class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-500 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-red-600"></span>
                    </span>
                    <span class="text-red-500 text-xs font-bold uppercase tracking-[.2em]">Ao Vivo Agora</span>
                </div>

                <h2 class="text-white text-xl sm:text-2xl font-bold mb-4 leading-snug">
                    <?= htmlspecialchars($lp['title']) ?>
                </h2>

                <!-- Embed YouTube -->
                <div class="aspect-video w-full rounded-xl overflow-hidden shadow-2xl">
                    <iframe src="https://www.youtube.com/embed/<?= htmlspecialchars($ytId) ?>?autoplay=1&mute=0"
                        class="w-full h-full" frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen>
                    </iframe>
                </div>

                <?php if (!empty($lp['content'])): ?>
                    <p class="text-slate-400 text-sm mt-4 leading-relaxed max-w-3xl">
                        <?= htmlspecialchars(mb_substr($lp['content'], 0, 220)) ?>        <?= mb_strlen($lp['content']) > 220 ? '…' : '' ?>
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
                                    <a href="/noticia/<?= $post['id'] ?>"
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
                                            <a href="/noticia/<?= $post['id'] ?>"
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
                                    <a href="/evento/<?= $evt['id'] ?>" class="absolute inset-0 z-10"></a>
                                    <div
                                        class="shrink-0 bg-slate-900 text-white rounded-lg w-12 text-center py-2 leading-tight">
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
                                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
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
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input id="dojoSearch" type="search" placeholder="Buscar por cidade ou dojo…" class="w-full pl-10 pr-4 py-3 border border-slate-200 rounded-lg bg-white
                              focus:outline-none focus:ring-2 focus:ring-rs-red focus:border-transparent
                              text-sm shadow-sm">
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
                <p class="text-center text-slate-400 text-sm italic">Nenhum dojo cadastrado no momento.</p>
            <?php endif; ?>

        </div>
    </section>

    <!-- ══════════════════════════ FOOTER ═══════════════════════════════════ -->
    <footer id="contato" class="bg-slate-900 text-white pt-14 pb-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-10 mb-10">

                <div>
                    <img src="/assets/images/logo-fgkirs-white.png" alt="FGKIRS" class="h-14 mb-4">
                    <p class="text-slate-400 text-sm leading-relaxed max-w-xs">
                        A Federação Gaúcha de Karatê Interestilos une dojos e atletas
                        em todo o Rio Grande do Sul, promovendo a excelência técnica e
                        o espírito esportivo do Karatê.
                    </p>
                    <div class="rs-bar h-1 rounded-full mt-5 w-24 opacity-70"></div>
                    <div class="mt-5 flex items-center gap-4">
                        <img src="/assets/images/logo-wukf.png" alt="WUKF – World Union of Karate-Do Federations"
                            class="h-12 w-12 object-contain opacity-80 hover:opacity-100 transition"
                            title="World Union of Karate-Do Federations">
                        <img src="/assets/images/logo-cbki.png"
                            alt="CBKI – Confederação Brasileira de Karatê Interestilos"
                            class="h-10 object-contain opacity-80 hover:opacity-100 transition"
                            title="Confederação Brasileira de Karatê Interestilos">
                    </div>
                </div>

                <div>
                    <h4 class="text-sm font-bold uppercase tracking-widest text-slate-300 mb-4">Navegação</h4>
                    <ul class="space-y-2 text-slate-400 text-sm">
                        <li><a href="/home#sobre" class="hover:text-white transition">A FGKIRS</a></li>
                        <li><a href="/noticias" class="hover:text-white transition">Notícias</a></li>
                        <li><a href="/eventos" class="hover:text-white transition">Próximos Eventos</a></li>
                        <li><a href="/dojos" class="hover:text-white transition">Dojos Oficiais</a></li>
                        <li><a href="/contato" class="hover:text-white transition">Contatos</a></li>
                        <li><a href="/login" class="hover:text-white transition">Área Administrativa</a></li>
                    </ul>
                </div>

                <?php $fp = $profile ?? []; ?>
                <div>
                    <h4 class="text-sm font-bold uppercase tracking-widest text-slate-300 mb-4">Contato</h4>
                    <ul class="space-y-2 text-slate-400 text-sm">

                        <?php if (!empty($fp['email'])): ?>
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-slate-500 shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                <a href="mailto:<?= htmlspecialchars($fp['email']) ?>" class="hover:text-white transition">
                                    <?= htmlspecialchars($fp['email']) ?>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php if (!empty($fp['whatsapp'])): ?>
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-slate-500 shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                                <?= htmlspecialchars($fp['whatsapp']) ?>
                            </li>
                        <?php endif; ?>

                        <?php if (!empty($fp['phone'])): ?>
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-slate-500 shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498A1 1 0 0121 15.72V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                                <?= htmlspecialchars($fp['phone']) ?>
                            </li>
                        <?php endif; ?>

                        <?php if (!empty($fp['city'])): ?>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-slate-500 shrink-0 mt-0.5" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span>
                                    <?= htmlspecialchars($fp['city']) ?>
                                    <?= !empty($fp['state']) ? '– ' . htmlspecialchars($fp['state']) : '' ?>
                                    <?= !empty($fp['zip_code']) ? '<br><span class="text-xs">' . htmlspecialchars($fp['zip_code']) . '</span>' : '' ?>
                                </span>
                            </li>
                        <?php endif; ?>

                        <?php if (!empty($fp['facebook']) || !empty($fp['instagram'])): ?>
                            <li class="flex items-center gap-3 pt-1">
                                <?php if (!empty($fp['facebook'])): ?>
                                    <a href="<?= htmlspecialchars($fp['facebook']) ?>" target="_blank" rel="noopener"
                                        class="text-slate-400 hover:text-white transition" title="Facebook">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                            <path
                                                d="M24 12.073C24 5.405 18.627 0 12 0S0 5.405 0 12.073C0 18.1 4.388 23.094 10.125 24v-8.437H7.078v-3.49h3.047V9.41c0-3.025 1.792-4.697 4.533-4.697 1.312 0 2.686.235 2.686.235v2.97h-1.513c-1.491 0-1.956.93-1.956 1.884v2.25h3.328l-.532 3.49h-2.796V24C19.612 23.094 24 18.1 24 12.073z" />
                                        </svg>
                                    </a>
                                <?php endif; ?>
                                <?php if (!empty($fp['instagram'])): ?>
                                    <a href="<?= htmlspecialchars($fp['instagram']) ?>" target="_blank" rel="noopener"
                                        class="text-slate-400 hover:text-white transition" title="Instagram">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                            <path
                                                d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z" />
                                        </svg>
                                    </a>
                                <?php endif; ?>
                            </li>
                        <?php endif; ?>

                    </ul>
                </div>

            </div>

            <div class="border-t border-slate-800 pt-6 flex flex-col sm:flex-row justify-between gap-2
                        text-slate-500 text-xs">
                <p>&copy; <?= date('Y') ?> FGKIRS – Federação Gaúcha de Karatê Interestilos. Todos os direitos
                    reservados.</p>
                <p>Desenvolvido com PHP 8.x · MVC · Tailwind CSS</p>
            </div>

        </div>
    </footer>

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
                    // hide current
                    slides[cur].classList.remove('is-active');
                    if (dots[cur]) { dots[cur].style.width = '.75rem'; dots[cur].style.background = 'rgba(255,255,255,.3)'; }

                    cur = ((idx % slides.length) + slides.length) % slides.length;

                    // show next
                    slides[cur].classList.add('is-active');
                    if (dots[cur]) { dots[cur].style.width = '2rem'; dots[cur].style.background = '#fff'; }

                    // restart ken burns on the newly active slide's background
                    const bg = slides[cur].querySelector('.ken-bg');
                    if (bg) {
                        bg.classList.remove('playing');
                        void bg.offsetWidth; // force reflow to restart animation
                        bg.classList.add('playing');
                    }

                    // update text content
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
                    setInterval(function () { activate(cur + 1); }, 3000);
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

</body>

</html>