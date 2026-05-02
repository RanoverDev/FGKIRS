<?php
try {
    $pdo = new PDO('mysql:host=mysql.fgkirs.com.br;dbname=fgkirs01;charset=utf8mb4', 'fgkirs01', 'J3dijf4jg685sdcaTWKj');
    echo "Connected successfully\n";
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
