<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Em Breve – FGKIRS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: { colors: { rs: { green: '#00AB4E', red: '#EE302F', yellow: '#FFCB04' } } } }
        }
    </script>
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
        h2 {
            font-family: 'Oswald', sans-serif;
            letter-spacing: .05em;
        }

        .rs-bar {
            background: linear-gradient(to right, #00AB4E 33.33%, #EE302F 33.33% 66.66%, #FFCB04 66.66%);
        }

        .hero-bg {
            background-color: #0f172a;
            background-image:
                radial-gradient(ellipse 80% 50% at 50% 0%, rgba(238, 48, 47, .2) 0%, transparent 60%),
                radial-gradient(ellipse 50% 60% at 80% 90%, rgba(0, 171, 78, .12) 0%, transparent 55%);
        }

        @keyframes pulse-bar {

            0%,
            100% {
                opacity: .6
            }

            50% {
                opacity: 1
            }
        }

        .bar-anim {
            animation: pulse-bar 2s ease-in-out infinite;
        }
    </style>
</head>

<body class="hero-bg min-h-screen flex flex-col antialiased">

    <div class="rs-bar h-1 w-full shrink-0"></div>

    <!-- Vertical RS stripe -->
    <div class="fixed left-0 inset-y-0 w-1.5 opacity-70"
        style="background:linear-gradient(to bottom,#00AB4E,#EE302F,#FFCB04)"></div>

    <main class="flex-1 flex flex-col items-center justify-center px-6 py-20 text-center">

        <img src="/assets/images/logo-fgkirs-white.png" alt="FGKIRS" class="w-64 sm:w-80 mx-auto mb-10 drop-shadow-2xl">

        <!-- Accent bars -->
        <div class="flex justify-center gap-2 mb-8 bar-anim">
            <span class="h-1 w-12 rounded-full bg-rs-green"></span>
            <span class="h-1 w-12 rounded-full bg-rs-red"></span>
            <span class="h-1 w-12 rounded-full bg-rs-yellow"></span>
        </div>

        <h1 class="text-5xl sm:text-6xl font-bold text-white mb-4">Em Breve</h1>
        <p class="text-slate-300 text-lg sm:text-xl max-w-md mb-10 leading-relaxed">
            Estamos construindo algo incrível para o Karatê Gaúcho.<br>
            Volte em breve!
        </p>

        <!-- Affiliate logos -->
        <div class="flex items-center justify-center gap-6 opacity-50 mt-4">
            <img src="/assets/images/logo-wukf.png" alt="WUKF" class="h-10 w-10 object-contain">
            <img src="/assets/images/logo-cbki.png" alt="CBKI" class="h-8 object-contain">
        </div>

    </main>

    <footer class="text-center text-slate-600 text-xs py-4">
        &copy; <?= date('Y') ?> FGKIRS – Federação Gaúcha de Karatê Interestilos
    </footer>

</body>

</html>