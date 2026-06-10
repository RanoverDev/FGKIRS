<?php
/**
 * SMTP Diagnostic Script
 * Acesse via: php smtp_test.php (CLI) ou coloque temporariamente em /public/smtp_test.php
 * REMOVER APÓS DIAGNÓSTICO — expõe credenciais SMTP em saída
 */

require_once __DIR__ . '/config/config.php';

$host = SMTP_HOST;
$port = SMTP_PORT;
$user = SMTP_USER;
$pass = SMTP_PASS;
$to   = SMTP_FROM_EMAIL;

echo "=== SMTP Diagnostic ===\n";
echo "Host : {$host}\n";
echo "Port : {$port}\n";
echo "User : {$user}\n";
echo "\n";

// 1. DNS resolution
echo "[1] DNS lookup... ";
$ip = gethostbyname($host);
if ($ip === $host) {
    echo "FALHOU — host não resolvido.\n";
} else {
    echo "OK ({$ip})\n";
}

// 2. TCP connection
echo "[2] TCP connect {$host}:{$port}... ";
$context = stream_context_create([
    'ssl' => [
        'verify_peer'       => false,
        'verify_peer_name'  => false,
        'allow_self_signed' => true,
    ]
]);

$socketHost = ($port == 465) ? 'ssl://' . $host : $host;
$socket = @stream_socket_client("{$socketHost}:{$port}", $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $context);

if (!$socket) {
    echo "FALHOU — {$errstr} ({$errno})\n";
    exit(1);
}
echo "OK\n";

// Helper
function smtp_read($sock) {
    $r = '';
    while ($line = fgets($sock, 1024)) {
        $r .= $line;
        if (substr($line, 3, 1) === ' ') break;
    }
    echo "  S: " . trim($r) . "\n";
    return $r;
}
function smtp_write($sock, $cmd) {
    echo "  C: {$cmd}\n";
    fwrite($sock, $cmd . "\r\n");
    return smtp_read($sock);
}

// 3. Banner
echo "[3] Greeting...\n";
smtp_read($socket);

// 4. EHLO
echo "[4] EHLO...\n";
smtp_write($socket, "EHLO localhost");

// 5. STARTTLS (port 587)
if ($port == 587) {
    echo "[5] STARTTLS...\n";
    $r = smtp_write($socket, "STARTTLS");
    if (substr($r, 0, 3) !== '220') {
        echo "    FALHOU — servidor não suporta STARTTLS\n";
        exit(1);
    }

    echo "[5b] stream_socket_enable_crypto (TLS 1.2)... ";
    if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT)) {
        echo "FALHOU\n";
        // try fallback
        echo "     Tentando TLS genérico... ";
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            echo "FALHOU\n";
            exit(1);
        }
        echo "OK (TLS genérico)\n";
    } else {
        echo "OK\n";
    }

    echo "[5c] EHLO pós-TLS...\n";
    smtp_write($socket, "EHLO localhost");
}

// 6. AUTH
echo "[6] AUTH LOGIN...\n";
$r = smtp_write($socket, "AUTH LOGIN");
if (substr($r, 0, 3) !== '334') {
    echo "    FALHOU — servidor não aceitou AUTH LOGIN\n";
    exit(1);
}

smtp_write($socket, base64_encode($user));
$r = smtp_write($socket, base64_encode($pass));
if (substr($r, 0, 3) !== '235') {
    echo "    FALHOU — credenciais rejeitadas\n";
    fwrite($socket, "QUIT\r\n");
    fclose($socket);
    exit(1);
}
echo "    Autenticação OK!\n";

// 7. Test send
echo "[7] Enviando e-mail de teste para {$to}...\n";
smtp_write($socket, "MAIL FROM:<{$user}>");
smtp_write($socket, "RCPT TO:<{$to}>");
smtp_write($socket, "DATA");

$msg = "Subject: Teste SMTP FGKIRS\r\n";
$msg .= "From: <{$user}>\r\n";
$msg .= "To: <{$to}>\r\n";
$msg .= "MIME-Version: 1.0\r\n";
$msg .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
$msg .= "Este é um e-mail de diagnóstico SMTP gerado em " . date('Y-m-d H:i:s') . ".\r\n";

fwrite($socket, $msg);
$r = smtp_write($socket, ".");
if (substr($r, 0, 3) === '250') {
    echo "    E-mail enviado com sucesso!\n";
} else {
    echo "    FALHOU ao enviar.\n";
}

smtp_write($socket, "QUIT");
fclose($socket);

echo "\n=== Diagnóstico concluído ===\n";
