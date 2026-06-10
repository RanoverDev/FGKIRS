<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    $pageTitle = $pageTitle ?? 'FGKIRS – A Força do Karatê Gaúcho';
    $pageDesc = $pageDesc ?? 'Federação Gaúcha de Karatê Interestilos – unindo dojos e atletas em todo o Rio Grande do Sul.';
    $ogImage = $ogImage ?? 'https://fgkirs.com.br/assets/images/og-default.jpg';
    $ogUrl = $ogUrl ?? 'https://fgkirs.com.br' . parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    ?>
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($pageDesc) ?>">

    <!-- Open Graph -->
    <meta property="og:type" content="article">
    <meta property="og:site_name" content="FGKIRS">
    <meta property="og:title" content="<?= htmlspecialchars($pageTitle) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($pageDesc) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($ogImage) ?>">
    <meta property="og:url" content="<?= htmlspecialchars($ogUrl) ?>">

    <!-- Twitter / X Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($pageTitle) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($pageDesc) ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($ogImage) ?>">

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

            <a href="/login" class="flex items-center gap-1 hover:text-white transition text-slate-300 font-medium ml-1" target="_blank">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span>Entrar</span>
            </a>

        </div>
    </div>

    <!-- ═══════════════════════════════════ HEADER ═══════════════════════════ -->
    <header class="bg-slate-900 text-white sticky top-0 z-50 shadow-2xl">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-3 flex items-center justify-between">

            <div class="flex items-center gap-4">
                <a href="/">
                    <img src="/assets/images/logo-fgkirs-white.png"
                        alt="Federação Gaúcha de Karatê Interestilos do Rio Grande do Sul" class="h-10 w-auto">
                </a>
            </div>

            <nav class="hidden md:flex items-center gap-6 text-sm text-slate-300">
                <a href="/a-fgkirs" class="hover:text-white transition">A FGKIRS</a>
                <a href="/noticias" class="hover:text-white transition">Notícias</a>
                <a href="/eventos" class="hover:text-white transition">Eventos</a>
                <a href="/galerias" class="hover:text-white transition">Galerias</a>
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
            <a href="/galerias" class="block py-2 text-slate-300 hover:text-white transition">Galerias</a>
            <a href="/dojos" class="block py-2 text-slate-300 hover:text-white transition">Dojos</a>
            <a href="/contato" class="block py-2 text-slate-300 hover:text-white transition">Contatos</a>
            <a href="/login" class="block py-2 text-slate-300 hover:text-white transition flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span>Entrar</span>
            </a>
        </div>
    </header>