<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Administração' ?> – FGKIRS</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        rs: { green: '#00AB4E', red: '#EE302F', yellow: '#FFCB04' }
                    }
                }
            }
        }
    </script>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Oswald:wght@600;700&display=swap"
        rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        h1,
        h2,
        h3 {
            font-family: 'Oswald', sans-serif;
            letter-spacing: .04em;
        }

        .rs-bar {
            background: linear-gradient(to right, #00AB4E 33.33%, #EE302F 33.33% 66.66%, #FFCB04 66.66%);
        }

        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #0f172a;
        }

        ::-webkit-scrollbar-thumb {
            background: #334155;
            border-radius: 3px;
        }
    </style>
</head>

<body class="bg-slate-100 antialiased">

    <!-- Mobile topbar -->
    <div
        class="lg:hidden fixed top-0 inset-x-0 z-50 bg-slate-900 text-white px-4 py-3 flex justify-between items-center shadow-xl">
        <a href="https://fgkirs.com.br/">
            <img src="/assets/images/logo-fgkirs-white.png" alt="FGKIRS" class="h-8">
        </a>
        <button id="menuToggle" class="text-white p-1">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
    </div>

    <div class="flex min-h-screen pt-14 lg:pt-0">
        <?php require_once __DIR__ . '/sidebar.php'; ?>
        <main class="flex-1 p-5 lg:p-8 overflow-auto">