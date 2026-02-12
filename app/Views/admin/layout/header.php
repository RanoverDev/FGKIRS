<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= $pageTitle ?? 'Administração' ?> - FGKIRS
    </title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Custom color configuration */
        :root {
            --red-rio: #b91c1c;
            /* Red-700 Rio Grande */
            --black-belt: #0f172a;
            /* Slate-900 Black Belt */
        }
    </style>
</head>

<body class="bg-gray-50">
    <!-- Mobile Menu Toggle -->
    <div
        class="lg:hidden fixed top-0 left-0 right-0 z-50 bg-slate-900 text-white p-4 flex justify-between items-center">
        <img src="/assets/images/logo-fgkirs.png" alt="FGKIRS" class="h-8">
        <button id="menuToggle" class="text-white focus:outline-none">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16">
                </path>
            </svg>
        </button>
    </div>

    <!-- Main Container -->
    <div class="flex min-h-screen pt-16 lg:pt-0">
        <!-- Sidebar -->
        <?php require_once __DIR__ . '/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="flex-1 p-4 lg:p-8">