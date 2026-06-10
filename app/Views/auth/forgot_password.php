<?php
use Helpers\Auth;
if (Auth::check()) {
    header('Location: /fgkirs-admin');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha – FGKIRS</title>
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
            letter-spacing: .04em;
        }

        .rs-bar {
            background: linear-gradient(to right, #00AB4E 33.33%, #EE302F 33.33% 66.66%, #FFCB04 66.66%);
        }
    </style>
</head>

<body class="min-h-screen bg-slate-900 flex items-stretch antialiased">

    <!-- Left panel (hidden on mobile) -->
    <div class="hidden lg:flex lg:w-1/2 xl:w-3/5 flex-col items-center justify-center relative overflow-hidden" style="background: radial-gradient(ellipse 80% 70% at 40% 40%, rgba(238,48,47,.22) 0%, transparent 65%),
                            radial-gradient(ellipse 60% 60% at 70% 70%, rgba(0,171,78,.15) 0%, transparent 60%),
                            #0f172a;">

        <!-- RS vertical stripe -->
        <div class="absolute left-0 inset-y-0 w-1.5"
            style="background:linear-gradient(to bottom,#00AB4E,#EE302F,#FFCB04)"></div>

        <div class="relative z-10 text-center px-16 max-w-xl">
            <img src="/assets/images/logo-fgkirs-white.png" alt="FGKIRS" class="w-72 mx-auto mb-10 drop-shadow-2xl">

            <div class="flex justify-center gap-2 mb-6">
                <span class="h-1 w-10 rounded-full bg-rs-green"></span>
                <span class="h-1 w-10 rounded-full bg-rs-red"></span>
                <span class="h-1 w-10 rounded-full bg-rs-yellow"></span>
            </div>

            <h1 class="text-4xl font-bold text-white mb-3">A Força do Karatê Gaúcho</h1>
            <p class="text-slate-400 text-base leading-relaxed">
                Sistema de gestão da Federação Gaúcha de Karatê Interestilos —
                dojos, atletas, graduações e muito mais.
            </p>
        </div>
    </div>

    <!-- Right panel — form -->
    <div class="w-full lg:w-1/2 xl:w-2/5 flex flex-col items-center justify-center px-6 py-12 bg-white">

        <!-- Mobile logo -->
        <div class="lg:hidden mb-8 text-center">
            <img src="/assets/images/logo-fgkirs-white.png" alt="FGKIRS" class="w-52 mx-auto">
        </div>

        <div class="w-full max-w-sm">

            <div class="rs-bar h-1 rounded-full mb-8"></div>

            <h2 class="text-2xl font-bold text-slate-900 mb-1">Recuperar Senha</h2>
            <p class="text-sm text-slate-500 mb-8">Insira seu e-mail para receber um link de redefinição.</p>

            <?php if (!empty($_SESSION['error'])): ?>
                <div
                    class="flex items-start gap-3 bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-6 text-sm">
                    <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <?= htmlspecialchars($_SESSION['error']) ?>
                </div>
            <?php unset($_SESSION['error']); endif; ?>

            <?php if (!empty($_SESSION['success'])): ?>
                <div
                    class="flex items-start gap-3 bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 mb-6 text-sm">
                    <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <?= htmlspecialchars($_SESSION['success']) ?>
                </div>
            <?php unset($_SESSION['success']); endif; ?>

            <form method="POST" action="/recuperar-senha" class="space-y-5">
                <?= \Helpers\Csrf::field() ?>
                <div style="display:none;"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>

                <div>
                    <label for="email" class="block text-xs font-bold uppercase tracking-widest text-slate-500 mb-2">
                        E-mail
                    </label>
                    <input type="email" id="email" name="email" required autofocus autocomplete="email" class="w-full px-4 py-3 border-2 border-slate-200 rounded-xl text-sm
                                  focus:outline-none focus:border-rs-red focus:ring-0 transition">
                </div>

                <button type="submit" class="w-full py-3 px-6 rounded-xl font-bold text-white text-sm transition-all
                               hover:opacity-90 active:scale-95 shadow-lg" style="background:#EE302F">
                    Enviar Link de Redefinição
                </button>

            </form>

            <p class="mt-8 text-center text-xs text-slate-400">
                <a href="/login" class="hover:text-slate-600 transition">← Voltar para o Login</a>
            </p>

        </div>

        <p class="mt-auto pt-10 text-xs text-slate-300">
            &copy; <?= date('Y') ?> FGKIRS · Todos os direitos reservados
        </p>
    </div>

</body>

</html>
