<?php
/**
 * Login View
 */

use Helpers\Auth;

// If already authenticated, redirect
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
    <title>Login - FGKIRS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="max-w-md w-full">
            <!-- Logo/Brand -->
            <div class="text-center mb-8">
                <img src="/assets/images/logo-fgkirs.png" alt="FGKIRS" class="w-64 mx-auto mb-4">
                <p class="text-gray-600 mt-2">Federação Gaúcha de Karatê Interestilos</p>
            </div>

            <!-- Login Card -->
            <div class="bg-white rounded-lg shadow-lg p-8">
                <h2 class="text-2xl font-bold text-center text-slate-900 mb-6">Área Administrativa</h2>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <?= htmlspecialchars($_SESSION['error']) ?>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <form method="POST" action="/login">
                    <!-- Email -->
                    <div class="mb-4">
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                            Email
                        </label>
                        <input type="email" id="email" name="email" required autofocus
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-rose-600 focus:border-transparent">
                    </div>

                    <!-- Password -->
                    <div class="mb-6">
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                            Senha
                        </label>
                        <input type="password" id="password" name="password" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-rose-600 focus:border-transparent">
                    </div>

                    <!-- Submit Button -->
                    <button type="submit"
                        class="w-full bg-rose-600 hover:bg-rose-700 text-white font-semibold py-3 px-6 rounded-lg  transition">
                        Entrar
                    </button>
                </form>


            </div>
        </div>
    </div>
</body>

</html>