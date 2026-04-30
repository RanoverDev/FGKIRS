<?php
use Helpers\Auth;
$currentUser = Auth::user();
$path        = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$path        = rtrim($path, '/') ?: '/';

function navActive(string $route, string $path): string {
    if ($route === '/fgkirs-admin') {
        return ($path === '/fgkirs-admin' || str_starts_with($path, '/fgkirs-admin/dashboard'))
            ? 'bg-rs-red text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white';
    }
    return str_starts_with($path, $route)
        ? 'bg-rs-red text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white';
}
?>

<aside id="sidebar"
       class="fixed lg:static inset-y-0 left-0 w-64 bg-slate-900 text-white flex flex-col
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
                      d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            Dashboard
        </a>

        <?php if (Auth::authorize(['admin', 'sensei'])): ?>
        <a href="/fgkirs-admin/users"
           class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all <?= navActive('/fgkirs-admin/users', $path) ?>">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            Usuários
        </a>
        <?php endif; ?>

        <?php if (Auth::authorize(['admin'])): ?>
        <a href="/fgkirs-admin/dojos"
           class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all <?= navActive('/fgkirs-admin/dojos', $path) ?>">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
            Dojos
        </a>
        <?php endif; ?>

        <?php if (Auth::authorize(['admin', 'sensei'])): ?>
        <a href="/fgkirs-admin/graduations"
           class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all <?= navActive('/fgkirs-admin/graduations', $path) ?>">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
            </svg>
            Graduações
        </a>
        <?php endif; ?>

        <?php if (Auth::check()): ?>
        <a href="/fgkirs-admin/posts"
           class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all <?= navActive('/fgkirs-admin/posts', $path) ?>">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
            </svg>
            Notícias
        </a>
        <?php endif; ?>

    </nav>

    <!-- Logout -->
    <div class="px-3 py-4 border-t border-slate-700/60 shrink-0">
        <a href="/logout"
           class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium text-slate-400
                  hover:bg-red-900/40 hover:text-red-400 transition-all">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
            Sair do Sistema
        </a>
    </div>

</aside>
