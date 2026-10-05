<?php
use Helpers\Auth;
$currentUser = Auth::user();
$path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$path = rtrim($path, '/') ?: '/';

function navActive(string $route, string $path): string
{
    if ($route === '/fgkirs-admin') {
        return ($path === '/fgkirs-admin' || str_starts_with($path, '/fgkirs-admin/dashboard'))
            ? 'bg-rs-red text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white';
    }
    return str_starts_with($path, $route)
        ? 'bg-rs-red text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white';
}
?>

<aside id="sidebar" class="fixed lg:static inset-y-0 left-0 w-64 bg-slate-900 text-white flex flex-col
              transform -translate-x-full lg:translate-x-0 transition-transform duration-300 z-40 shadow-2xl">

    <!-- RS stripe -->
    <div class="rs-bar h-1 w-full shrink-0"></div>

    <!-- Logo -->
    <div class="px-6 py-5 border-b border-slate-700/60 shrink-0">
        <a href="/fgkirs-admin" class="block">
            <img src="/assets/images/logo-fgkirs-white.png" alt="FGKIRS" class="h-10 w-auto">
        </a>
    </div>

    <!-- User badge -->
    <div class="px-4 py-4 border-b border-slate-700/60 shrink-0">
        <div class="flex items-center gap-3 bg-slate-800 rounded-xl px-4 py-3">
            <div class="w-9 h-9 rounded-full bg-rs-red flex items-center justify-center font-bold text-sm shrink-0">
                <?= strtoupper(substr($currentUser['name'] ?? 'U', 0, 1)) ?>
            </div>
            <div class="min-w-0">
                <p class="font-semibold text-sm truncate"><?= htmlspecialchars($currentUser['name'] ?? 'Usuário') ?></p>
                <p class="text-xs text-slate-400"><?= ucfirst($currentUser['role'] ?? '') ?></p>
            </div>
        </div>
    </div>

    <!-- Nav -->
    <nav class="flex-1 px-3 py-4 overflow-y-auto space-y-1">

        <p class="px-3 text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-2">Menu</p>

        <!-- Dashboard -->
        <a href="/fgkirs-admin"
            class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all <?= navActive('/fgkirs-admin', $path) ?>">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            Dashboard
        </a>

        <?php if (Auth::authorize(['admin', 'sensei'])): ?>
            <a href="/fgkirs-admin/users"
                class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all <?= navActive('/fgkirs-admin/users', $path) ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                Usuários
            </a>

            <a href="/fgkirs-admin/athletes"
                class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all <?= navActive('/fgkirs-admin/athletes', $path) ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Atletas
            </a>
        <?php endif; ?>

        <?php if (Auth::authorize(['admin'])): ?>
            <?php
            $divisoesActive = str_starts_with($path, '/fgkirs-admin/styles')
                || str_starts_with($path, '/fgkirs-admin/graduations')
                || str_starts_with($path, '/fgkirs-admin/rulesets');
            ?>
            <div x-data="{ open: <?= $divisoesActive ? 'true' : 'false' ?> }">
                <button @click="open = !open"
                    class="w-full flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all
                           <?= $divisoesActive ? 'bg-rs-red text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    <span class="flex-1 text-left">Divisões</span>
                    <svg class="w-4 h-4 transition-transform" :class="open ? 'rotate-180' : ''" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div x-show="open" x-transition class="mt-1 ml-4 pl-3 border-l border-slate-700 space-y-1">
                    <?php if (Auth::authorize(['admin'])): ?>
                        <a href="/fgkirs-admin/styles"
                            class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-all <?= navActive('/fgkirs-admin/styles', $path) ?>">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                            </svg>
                            Estilos
                        </a>
                    <?php endif; ?>

                    <a href="/fgkirs-admin/graduations"
                        class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-all <?= navActive('/fgkirs-admin/graduations', $path) ?>">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                        </svg>
                        Graduações
                    </a>

                    <a href="/fgkirs-admin/categories"
                        class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-all <?= navActive('/fgkirs-admin/categories', $path) ?>">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 10h16M4 14h10M4 18h10" />
                        </svg>
                        Categorias de Disputa
                    </a>

                    <a href="/fgkirs-admin/rulesets"
                        class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-all <?= navActive('/fgkirs-admin/rulesets', $path) ?>">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                        Regulamentos
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <?php if (Auth::isAdmin()): ?>
            <a href="/fgkirs-admin/dojos"
                class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all <?= navActive('/fgkirs-admin/dojos', $path) ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                Dojos
            </a>
        <?php elseif (Auth::isSensei()): ?>
            <?php
            $myDojoId = Auth::dojoId();
            if (!$myDojoId) {
                try {
                    $db = \Core\Database::getInstance();
                    $dbDojo = $db->query("SELECT id FROM dojos WHERE sensei_id = :sensei_id LIMIT 1", ['sensei_id' => Auth::id()])->fetch();
                    $myDojoId = $dbDojo ? $dbDojo['id'] : null;
                } catch (\Exception $e) {}
            }
            ?>
            <?php if ($myDojoId): ?>
                <a href="/fgkirs-admin/dojos/edit/<?= $myDojoId ?>"
                    class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all <?= navActive('/fgkirs-admin/dojos/edit', $path) ?>">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    Meu Dojo
                </a>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (Auth::authorize(['admin', 'sensei'])): ?>
            <a href="/fgkirs-admin/championships"
                class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all <?= navActive('/fgkirs-admin/championships', $path) ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 21h8m-4-4v4m-7-9a7 7 0 0014 0V4H5v8zM5 6H3a2 2 0 002 2m14-2h2a2 2 0 01-2 2" />
                </svg>
                Eventos
            </a>
        <?php endif; ?>

        <?php if (Auth::authorize(['admin', 'sensei', 'aluno-colaborador'])): ?>
            <a href="/fgkirs-admin/posts"
                class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all <?= navActive('/fgkirs-admin/posts', $path) ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                </svg>
                Notícias
            </a>

            <a href="/fgkirs-admin/galleries"
                class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all <?= navActive('/fgkirs-admin/galleries', $path) ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                Galerias de Imagens
            </a>
        <?php endif; ?>

        <?php if (Auth::authorize(['admin'])): ?>
            <a href="/fgkirs-admin/federation-profile"
                class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all <?= navActive('/fgkirs-admin/federation-profile', $path) ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Perfil da Federação
            </a>

            <a href="/fgkirs-admin/popup"
                class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all <?= navActive('/fgkirs-admin/popup', $path) ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
                </svg>
                Popup do Site
            </a>

            <a href="/fgkirs-admin/board"
                class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all <?= navActive('/fgkirs-admin/board', $path) ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Estrutura Administrativa
            </a>
        <?php endif; ?>

        <a href="/fgkirs-admin/help"
            class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all <?= navActive('/fgkirs-admin/help', $path) ?>">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Ajuda
        </a>

    </nav>

    <!-- Logout -->
    <div class="px-3 py-4 border-t border-slate-700/60 shrink-0">
        <a href="/logout" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium text-slate-400
                  hover:bg-red-900/40 hover:text-red-400 transition-all">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
            Sair do Sistema
        </a>
    </div>

</aside>