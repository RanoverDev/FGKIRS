<?php
define('BASE_URL', 'https://fgkirs.com.br');
define('DB_HOST', 'mysql.fgkirs.com.br');
define('DB_NAME', 'fgkirs01');
define('DB_USER', 'fgkirs01');
define('DB_PASS', 'J3dijf4jg685sdcaTWKj');

// Configurações de imagem conforme pedido
define('IMG_MAX_WIDTH', 1200);
define('IMG_QUALITY', 55);

spl_autoload_register(function ($class) {
    $path = __DIR__ . '/../app/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($path))
        require $path;
});