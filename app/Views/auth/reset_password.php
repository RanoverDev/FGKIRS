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
    <title>Redefinir Senha – FGKIRS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: { colors: { rs: { green: '#00AB4E', red: '#EE302F', yellow: '#FFCB04' } } } }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Oswald:wght@600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        h1, h2 { font-family: 'Oswald', sans-serif; letter-spacing: .04em; }
        .rs-bar { background: linear-gradient(to right, #00AB4E 33.33%, #EE302F 33.33% 66.66%, #FFCB04 66.66%); }
        .req-item { transition: color .2s; }
        .req-item.ok  { color: #16a34a; }
        .req-item.err { color: #dc2626; }
    </style>
</head>

<body class="min-h-screen bg-slate-900 flex items-stretch antialiased">

    <!-- Left panel -->
    <div class="hidden lg:flex lg:w-1/2 xl:w-3/5 flex-col items-center justify-center relative overflow-hidden"
         style="background: radial-gradient(ellipse 80% 70% at 40% 40%, rgba(238,48,47,.22) 0%, transparent 65%),
                            radial-gradient(ellipse 60% 60% at 70% 70%, rgba(0,171,78,.15) 0%, transparent 60%),
                            #0f172a;">
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

        <div class="lg:hidden mb-8 text-center">
            <img src="/assets/images/logo-fgkirs-white.png" alt="FGKIRS" class="w-52 mx-auto">
        </div>

        <div class="w-full max-w-sm">

            <div class="rs-bar h-1 rounded-full mb-8"></div>

            <h2 class="text-2xl font-bold text-slate-900 mb-1">Redefinir Senha</h2>
            <p class="text-sm text-slate-500 mb-8">Digite sua nova senha abaixo.</p>

            <?php if (!empty($_SESSION['error'])): ?>
                <div class="flex items-start gap-3 bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-6 text-sm">
                    <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <?= htmlspecialchars($_SESSION['error']) ?>
                </div>
            <?php unset($_SESSION['error']); endif; ?>

            <form method="POST" action="/redefinir-senha" class="space-y-5" id="resetForm" novalidate>
                <?= \Helpers\Csrf::field() ?>
                <div style="display:none;"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
                <input type="hidden" name="token" value="<?= htmlspecialchars($token ?? '') ?>">

                <!-- Nova Senha -->
                <div>
                    <label for="password" class="block text-xs font-bold uppercase tracking-widest text-slate-500 mb-2">
                        Nova Senha
                    </label>
                    <div class="relative">
                        <input type="password" id="password" name="password" required autofocus autocomplete="new-password"
                               class="w-full px-4 py-3 pr-11 border-2 border-slate-200 rounded-xl text-sm
                                      focus:outline-none focus:border-rs-red focus:ring-0 transition"
                               oninput="checkStrength()">
                        <button type="button" onclick="toggleVisibility('password', this)"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition"
                                tabindex="-1" aria-label="Mostrar/ocultar senha">
                            <svg id="eye-password" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Barra de força -->
                    <div class="mt-2 flex gap-1 h-1" id="strengthBar">
                        <div class="flex-1 rounded-full bg-slate-200" id="bar1"></div>
                        <div class="flex-1 rounded-full bg-slate-200" id="bar2"></div>
                        <div class="flex-1 rounded-full bg-slate-200" id="bar3"></div>
                        <div class="flex-1 rounded-full bg-slate-200" id="bar4"></div>
                    </div>

                    <!-- Requisitos -->
                    <ul class="mt-2.5 space-y-1 text-[11px] leading-normal" id="requirements">
                        <li class="req-item flex items-center gap-1.5" id="req-len">
                            <span class="req-icon">○</span> Mínimo de 8 caracteres
                        </li>
                        <li class="req-item flex items-center gap-1.5" id="req-upper">
                            <span class="req-icon">○</span> Pelo menos uma letra maiúscula
                        </li>
                        <li class="req-item flex items-center gap-1.5" id="req-lower">
                            <span class="req-icon">○</span> Pelo menos uma letra minúscula
                        </li>
                        <li class="req-item flex items-center gap-1.5" id="req-num">
                            <span class="req-icon">○</span> Pelo menos um número
                        </li>
                        <li class="req-item flex items-center gap-1.5" id="req-special">
                            <span class="req-icon">○</span> Pelo menos um caractere especial (@, #, $, !, ...)
                        </li>
                    </ul>
                </div>

                <!-- Confirmar Senha -->
                <div>
                    <label for="password_confirm" class="block text-xs font-bold uppercase tracking-widest text-slate-500 mb-2">
                        Confirmar Nova Senha
                    </label>
                    <div class="relative">
                        <input type="password" id="password_confirm" name="password_confirm" required autocomplete="new-password"
                               class="w-full px-4 py-3 pr-11 border-2 border-slate-200 rounded-xl text-sm
                                      focus:outline-none focus:border-rs-red focus:ring-0 transition"
                               oninput="checkMatch()">
                        <button type="button" onclick="toggleVisibility('password_confirm', this)"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition"
                                tabindex="-1" aria-label="Mostrar/ocultar confirmação">
                            <svg id="eye-password_confirm" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                    </div>
                    <p class="mt-1.5 text-[11px] hidden" id="matchMsg"></p>
                </div>

                <button type="submit" id="submitBtn"
                        class="w-full py-3 px-6 rounded-xl font-bold text-white text-sm transition-all
                               opacity-50 cursor-not-allowed shadow-lg"
                        style="background:#EE302F" disabled>
                    Redefinir Senha
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

    <script>
        const eyeOpen = `<path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>`;
        const eyeClosed = `<path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>`;

        function toggleVisibility(fieldId, btn) {
            const input = document.getElementById(fieldId);
            const svg   = document.getElementById('eye-' + fieldId);
            const show  = input.type === 'password';
            input.type  = show ? 'text' : 'password';
            svg.innerHTML = show ? eyeClosed : eyeOpen;
            btn.setAttribute('aria-label', show ? 'Ocultar senha' : 'Mostrar senha');
        }

        const rules = {
            len:     { re: /.{8,}/,           id: 'req-len'     },
            upper:   { re: /[A-Z]/,            id: 'req-upper'   },
            lower:   { re: /[a-z]/,            id: 'req-lower'   },
            num:     { re: /[0-9]/,            id: 'req-num'     },
            special: { re: /[^A-Za-z0-9]/,    id: 'req-special' },
        };

        const bars   = [document.getElementById('bar1'), document.getElementById('bar2'),
                        document.getElementById('bar3'), document.getElementById('bar4')];
        const colors = ['#ef4444','#f97316','#eab308','#22c55e'];

        function checkStrength() {
            const val  = document.getElementById('password').value;
            let score  = 0;

            Object.values(rules).forEach(r => {
                const el  = document.getElementById(r.id);
                const ok  = r.re.test(val);
                const icon = el.querySelector('.req-icon');
                el.classList.toggle('ok',  ok);
                el.classList.toggle('err', !ok && val.length > 0);
                icon.textContent = ok ? '✓' : (val.length > 0 ? '✗' : '○');
                if (ok) score++;
            });

            bars.forEach((b, i) => {
                b.style.backgroundColor = i < score ? colors[Math.min(score - 1, 3)] : '#e2e8f0';
            });

            checkMatch();
            updateSubmit(score);
        }

        function checkMatch() {
            const p1  = document.getElementById('password').value;
            const p2  = document.getElementById('password_confirm').value;
            const msg = document.getElementById('matchMsg');
            if (!p2) { msg.className = 'mt-1.5 text-[11px] hidden'; return; }
            const match = p1 === p2;
            msg.textContent  = match ? '✓ Senhas coincidem' : '✗ Senhas não coincidem';
            msg.className    = `mt-1.5 text-[11px] font-medium ${match ? 'text-green-600' : 'text-red-600'}`;
            updateSubmit();
        }

        function updateSubmit(score) {
            const allOk = Object.values(rules).every(r => r.re.test(document.getElementById('password').value));
            const match = document.getElementById('password').value === document.getElementById('password_confirm').value
                          && document.getElementById('password_confirm').value.length > 0;
            const btn = document.getElementById('submitBtn');
            const ok  = allOk && match;
            btn.disabled = !ok;
            btn.classList.toggle('opacity-50',       !ok);
            btn.classList.toggle('cursor-not-allowed', !ok);
            btn.classList.toggle('hover:opacity-90',  ok);
            btn.classList.toggle('active:scale-95',   ok);
        }
    </script>

</body>
</html>
