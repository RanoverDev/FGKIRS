<?php
use Helpers\Auth;
$currentUser = Auth::user();
$currentPath = $_SERVER['REQUEST_URI'] ?? '';
?>

<!-- Sidebar -->
<aside id="sidebar"
    class="fixed lg:static top-16 lg:top-0 left-0 w-64 bg-slate-900 text-white min-h-screen transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out z-40">

    <!-- Logo -->
    <div class="p-6 border-b border-slate-700">
        <a href="/fgkirs-admin">
            <img src="/assets/images/logo-fgkirs.png" alt="FGKIRS" class="w-full h-auto">
        </a>
    </div>

    <!-- User Info -->
    <div class="p-6 border-b border-slate-700">
        <div class="flex items-center space-x-3">
            <div
                class="w-12 h-12 rounded-full bg-rose-600 flex items-center justify-center text-white font-bold text-lg">
                <?= strtoupper(substr($currentUser['name'] ?? 'U', 0, 1)) ?>
            </div>
            <div>
                <p class="font-semibold">
                    <?= htmlspecialchars($currentUser['name'] ?? 'Usuário') ?>
                </p>
                <p class="text-sm text-gray-400">
                    <?= ucfirst($currentUser['role'] ?? '') ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Navigation Menu -->
    <nav class="p-4">
        <ul class="space-y-2">
            <!-- Dashboard -->
            <li>
                <a href="/fgkirs-admin"
                    class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-slate-800 transition <?= str_contains($currentPath, 'fgkirs-admin') ? 'bg-rose-600' : '' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
                        </path>
                    </svg>
                    <span>Dashboard</span>
                </a>
            </li>

            <!-- Usuários -->
            <?php if (Auth::authorize(['admin', 'sensei'])): ?>
                <li>
                    <a href="/fgkirs-admin/users"
                        class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-slate-800 transition <?= str_contains($currentPath, 'users') ? 'bg-rose-600' : '' ?>">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                            </path>
                        </svg>
                        <span>Usuários</span>
                    </a>
                </li>
            <?php endif; ?>

            <!-- Dojos -->
            <?php if (Auth::authorize(['admin'])): ?>
                <li>
                    <a href="/fgkirs-admin/dojos"
                        class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-slate-800 transition <?= str_contains($currentPath, 'dojos') ? 'bg-rose-600' : '' ?>">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                            </path>
                        </svg>
                        <span>Dojos</span>
                    </a>
                </li>
            <?php endif; ?>

            <!-- Graduations -->
            <?php if (Auth::authorize(['admin', 'sensei'])): ?>
                <li>
                    <a href="/fgkirs-admin/graduations"
                        class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-slate-800 transition <?= str_contains($currentPath, 'graduations') ? 'bg-rose-600' : '' ?>">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z">
                            </path>
                        </svg>
                        <span>Graduações</span>
                    </a>
                </li>
            <?php endif; ?>

            <!-- Posts/News -->
            <?php if (Auth::check()): ?>
                <li>
                    <a href="/fgkirs-admin/posts"
                        class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-slate-800 transition <?= str_contains($currentPath, 'posts') ? 'bg-rose-600' : '' ?>">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z">
                            </path>
                        </svg>
                        <span>Notícias</span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>

    <!-- Logout -->
    <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-slate-700">
        <a href="/logout.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-red-700 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1">
                </path>
            </svg>
            <span>Sair</span>
        </a>
    </div>
</aside>