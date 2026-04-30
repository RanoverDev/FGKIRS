<?php
use Helpers\Auth;

// Redirect based on role
if (Auth::isAdmin()) {
    header('Location: /fgkirs-admin/dashboard/president');
} elseif (Auth::isSensei()) {
    header('Location: /fgkirs-admin/dashboard/sensei');
} else {
    // Student/Colaborador - show basic dashboard
    $pageTitle = 'Dashboard';
    require_once __DIR__ . '/layout/header.php';
    ?>

    <div class="text-center py-12">
        <h1 class="text-3xl font-extrabold text-slate-900 mb-4">Bem-vindo ao FGKIRS</h1>
        <p class="text-gray-600">Navegue pelo menu para acessar as funcionalidades disponíveis.</p>
    </div>

    <?php
    require_once __DIR__ . '/layout/footer.php';
}
exit;