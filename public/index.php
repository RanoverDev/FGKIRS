<?php
/**
 * FGKIRS - Front Controller
 * Single entry point for all requests
 */

// Temporary: show errors for debugging - REMOVE AFTER FIX
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session
session_start();

// Load configuration and autoloader
require_once __DIR__ . '/../config/config.php';

// Import Router
use Core\Router;

// Create router instance
$router = new Router();

// =====================================================
// PUBLIC ROUTES
// =====================================================

// Temporary Sync Route (Remove after fixing)
$router->add('GET', '/debug-sync', function () {
    $path = __DIR__ . '/../app/Views/home';
    if (!is_dir($path))
        mkdir($path, 0755, true);

    $html = <<<'HTML'
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FGKIRS - Federação Gaúcha de Karatê</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <header class="bg-slate-900 text-white">
        <div class="container mx-auto px-4 py-6">
            <div class="flex justify-between items-center">
                <img src="/assets/images/logo-fgkirs.png" alt="FGKIRS" class="h-12">
                <a href="/login" class="bg-rose-600 hover:bg-rose-700 text-white font-semibold py-2 px-6 rounded-lg transition">
                    Área Administrativa
                </a>
            </div>
        </div>
    </header>
    <section class="bg-gradient-to-br from-rose-600 to-rose-800 text-white py-20">
        <div class="container mx-auto px-4 text-center">
            <h2 class="text-5xl font-bold mb-4">Bem-vindo ao FGKIRS</h2>
            <p class="text-xl mb-8">Sistema de Gerenciamento da Federação Gaúcha de Karatê</p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="/login" class="bg-white text-rose-600 hover:bg-gray-100 font-semibold py-3 px-8 rounded-lg transition">Acessar Sistema</a>
                <a href="#sobre" class="bg-transparent border-2 border-white hover:bg-white hover:text-rose-600 font-semibold py-3 px-8 rounded-lg transition">Saiba Mais</a>
            </div>
        </div>
    </section>
    <section id="sobre" class="py-16">
        <div class="container mx-auto px-4">
            <div class="max-w-4xl mx-auto">
                <h2 class="text-4xl font-bold text-slate-900 text-center mb-8">Sobre o Sistema</h2>
                <div class="grid md:grid-cols-2 gap-8 mb-12">
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mb-4">
                            <svg class="w-8 h-8 text-red-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-2">Gestão de Dojos</h3>
                        <p class="text-gray-600">Cadastro e gerenciamento completo de dojos afiliados à federação.</p>
                    </div>
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <div class="w-16 h-16 bg-slate-900 text-white rounded-full flex items-center justify-center mb-4">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-2">Atletas</h3>
                        <p class="text-gray-600">Gerenciamento de alunos, senseis e colaboradores.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <footer class="bg-slate-900 text-white py-8">
        <div class="container mx-auto px-4 text-center">
            <p>&copy; FGKIRS - Federação Gaúcha de Karatê Interestilos.</p>
        </div>
    </footer>
</body>
</html>
HTML;

    file_put_contents(__DIR__ . '/../app/Views/home/index.php', $html);
    echo "<h1>Homepage Sincronizada!</h1><p>O conteúdo real foi injetado. Acesse a Home agora.</p>";
});

$router->add('GET', '/', 'HomeController@underConstruction');
$router->add('GET', '/home', 'HomeController@index');
$router->add('GET', '/a-fgkirs', 'HomeController@about');
$router->add('GET', '/dojos', 'HomeController@dojos');
$router->add('GET', '/contato', 'HomeController@contact');
$router->add('POST', '/contato/enviar', 'HomeController@sendContact');

$router->add('GET', '/noticias', 'HomeController@newsIndex');
$router->add('GET', '/noticias/load', 'HomeController@newsLoadMore');
$router->add('GET', '/noticia/{slug}', 'HomeController@showNews');

$router->add('GET', '/eventos', 'HomeController@eventsIndex');
$router->add('GET', '/eventos/load', 'HomeController@eventsLoadMore');
$router->add('GET', '/evento/{slug}', 'HomeController@showEvent');

$router->add('GET', '/galerias', 'HomeController@galleriesIndex');
$router->add('GET', '/galeria/{slug}', 'HomeController@showGallery');

// =====================================================
// AUTHENTICATION ROUTES
// =====================================================

$router->add('GET', '/login', 'Auth\LoginController@showLoginForm');
$router->add('POST', '/login', 'Auth\LoginController@login');
$router->add('GET', '/logout', 'Auth\LoginController@logout');

// =====================================================
// ADMIN DASHBOARD ROUTES
// =====================================================

$router->add('GET', '/fgkirs-admin', 'Admin\DashboardController@index');
$router->add('GET', '/fgkirs-admin/dashboard/president', 'Admin\DashboardController@president');
$router->add('GET', '/fgkirs-admin/dashboard/sensei', 'Admin\DashboardController@sensei');

// =====================================================
// ADMIN - USERS
// =====================================================

$router->add('GET', '/fgkirs-admin/users', 'Admin\UserController@index');
$router->add('GET', '/fgkirs-admin/users/create', 'Admin\UserController@create');
$router->add('POST', '/fgkirs-admin/users/store', 'Admin\UserController@store');
$router->add('GET', '/fgkirs-admin/users/edit/{id}', 'Admin\UserController@edit');
$router->add('POST', '/fgkirs-admin/users/update/{id}', 'Admin\UserController@update');
$router->add('GET', '/fgkirs-admin/users/delete/{id}', 'Admin\UserController@delete');
$router->add('POST', '/fgkirs-admin/users/toggle-status/{id}', 'Admin\UserController@toggleStatus');

// =====================================================
// ADMIN - DOJOS
// =====================================================

$router->add('GET', '/fgkirs-admin/dojos', 'Admin\DojoController@index');
$router->add('GET', '/fgkirs-admin/dojos/create', 'Admin\DojoController@create');
$router->add('POST', '/fgkirs-admin/dojos/store', 'Admin\DojoController@store');
$router->add('GET', '/fgkirs-admin/dojos/edit/{id}', 'Admin\DojoController@edit');
$router->add('POST', '/fgkirs-admin/dojos/update/{id}', 'Admin\DojoController@update');
$router->add('GET', '/fgkirs-admin/dojos/delete/{id}', 'Admin\DojoController@delete');

// =====================================================
// ADMIN - STYLES
// =====================================================

$router->add('GET', '/fgkirs-admin/styles', 'Admin\StyleController@index');
$router->add('GET', '/fgkirs-admin/styles/create', 'Admin\StyleController@create');
$router->add('POST', '/fgkirs-admin/styles/store', 'Admin\StyleController@store');
$router->add('GET', '/fgkirs-admin/styles/edit/{id}', 'Admin\StyleController@edit');
$router->add('POST', '/fgkirs-admin/styles/update/{id}', 'Admin\StyleController@update');
$router->add('GET', '/fgkirs-admin/styles/delete/{id}', 'Admin\StyleController@delete');

// =====================================================
// ADMIN - GRADUATIONS
// =====================================================

$router->add('GET', '/fgkirs-admin/graduations', 'Admin\GraduationController@index');
$router->add('GET', '/fgkirs-admin/graduations/create', 'Admin\GraduationController@create');
$router->add('POST', '/fgkirs-admin/graduations/store', 'Admin\GraduationController@store');
$router->add('GET', '/fgkirs-admin/graduations/edit/{id}', 'Admin\GraduationController@edit');
$router->add('POST', '/fgkirs-admin/graduations/update/{id}', 'Admin\GraduationController@update');
$router->add('GET', '/fgkirs-admin/graduations/delete/{id}', 'Admin\GraduationController@delete');
$router->add('GET', '/fgkirs-admin/graduations/history/{id}', 'Admin\GraduationController@history');
$router->add('GET', '/fgkirs-admin/graduations/promote/{id}', 'Admin\GraduationController@promoteForm');
$router->add('POST', '/fgkirs-admin/graduations/promote', 'Admin\GraduationController@promoteStudent');

// =====================================================
// ADMIN - POSTS/NEWS
// =====================================================

$router->add('GET', '/fgkirs-admin/posts', 'Admin\PostController@index');
$router->add('GET', '/fgkirs-admin/posts/create', 'Admin\PostController@create');
$router->add('POST', '/fgkirs-admin/posts/store', 'Admin\PostController@store');
$router->add('GET', '/fgkirs-admin/posts/edit/{id}', 'Admin\PostController@edit');
$router->add('POST', '/fgkirs-admin/posts/update/{id}', 'Admin\PostController@update');
$router->add('GET', '/fgkirs-admin/posts/delete/{id}', 'Admin\PostController@delete');
$router->add('POST', '/fgkirs-admin/posts/add-image/{id}', 'Admin\PostController@addImage');
$router->add('GET', '/fgkirs-admin/posts/remove-image/{id}', 'Admin\PostController@removeImage');
$router->add('GET', '/fgkirs-admin/posts/set-featured/{id}', 'Admin\PostController@setFeatured');

// =====================================================
// ADMIN - GALLERIES
// =====================================================

$router->add('GET', '/fgkirs-admin/galleries', 'Admin\GalleryController@index');
$router->add('GET', '/fgkirs-admin/galleries/create', 'Admin\GalleryController@create');
$router->add('POST', '/fgkirs-admin/galleries/store', 'Admin\GalleryController@store');
$router->add('GET', '/fgkirs-admin/galleries/edit/{id}', 'Admin\GalleryController@edit');
$router->add('POST', '/fgkirs-admin/galleries/update/{id}', 'Admin\GalleryController@update');
$router->add('GET', '/fgkirs-admin/galleries/delete/{id}', 'Admin\GalleryController@delete');
$router->add('POST', '/fgkirs-admin/galleries/upload-zip/{id}', 'Admin\GalleryController@uploadZip');
$router->add('GET', '/fgkirs-admin/galleries/remove-image/{id}', 'Admin\GalleryController@removeImage');
$router->add('GET', '/fgkirs-admin/galleries/set-cover/{id}', 'Admin\GalleryController@setCover');

// =====================================================
// ADMIN - FEDERATION PROFILE
// =====================================================

$router->add('GET', '/fgkirs-admin/federation-profile', 'Admin\FederationProfileController@edit');
$router->add('POST', '/fgkirs-admin/federation-profile/update', 'Admin\FederationProfileController@update');

// =====================================================
// DISPATCH ROUTER
// =====================================================

$router->dispatch();
