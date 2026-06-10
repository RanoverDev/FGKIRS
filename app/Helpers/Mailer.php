<?php

namespace Helpers;

/**
 * Mailer Class - Envio de e-mails usando socket SMTP nativo
 */
class Mailer
{
    /**
     * Envia um e-mail HTML usando autenticação SMTP
     *
     * @param string $to E-mail do destinatário
     * @param string $subject Assunto do e-mail
     * @param string $body Corpo em formato HTML
     * @param array $extraHeaders Cabeçalhos adicionais
     * @return bool Retorna true em caso de sucesso, false caso contrário
     */
    public static function send(string $to, string $subject, string $body, array $extraHeaders = []): bool
    {
        $host = SMTP_HOST;
        $port = SMTP_PORT;
        $user = SMTP_USER;
        $pass = SMTP_PASS;
        $fromEmail = SMTP_FROM_EMAIL;
        $fromName = SMTP_FROM_NAME;

        // Limpeza do assunto e sanitização de e-mails
        $subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        
        // Conexão via socket com contexto para aceitar certificados curingas (wildcards) do servidor
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);
        
        $socketHost = ($port == 465) ? 'ssl://' . $host : $host;
        
        $socket = @stream_socket_client(
            $socketHost . ':' . $port,
            $errno,
            $errstr,
            15,
            STREAM_CLIENT_CONNECT,
            $context
        );
        if (!$socket) {
            error_log("SMTP Connection Error [{$host}:{$port}]: $errstr ($errno)");
            return false;
        }

        try {
            self::readResponse($socket, '220');

            // EHLO
            self::sendCommand($socket, "EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'), '250');

            // Se for porta 587, iniciar TLS
            if ($port == 587) {
                self::sendCommand($socket, "STARTTLS", '220');
                $cryptoMethod = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
                if (!stream_socket_enable_crypto($socket, true, $cryptoMethod)) {
                    throw new \Exception("Failed to start TLS 1.2 encryption on {$host}:{$port}");
                }
                // Enviar EHLO novamente após iniciar TLS
                self::sendCommand($socket, "EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'), '250');
            }

            // Autenticação
            self::sendCommand($socket, "AUTH LOGIN", '334');
            self::sendCommand($socket, base64_encode($user), '334');
            self::sendCommand($socket, base64_encode($pass), '235');

            // MAIL FROM
            self::sendCommand($socket, "MAIL FROM:<$fromEmail>", '250');

            // RCPT TO
            self::sendCommand($socket, "RCPT TO:<$to>", '250');

            // DATA
            self::sendCommand($socket, "DATA", '354');

            // Montagem dos cabeçalhos
            $boundary = md5(uniqid(time()));
            
            $headers = [
                "MIME-Version: 1.0",
                "Content-Type: multipart/alternative; boundary=\"$boundary\"",
                "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <$fromEmail>",
                "To: <$to>",
                "Subject: $subject",
                "Date: " . date('r')
            ];

            foreach ($extraHeaders as $k => $v) {
                $headers[] = "$k: $v";
            }

            $message = implode("\r\n", $headers) . "\r\n\r\n";
            
            // Corpo Multipart: Texto Puro e HTML
            $message .= "--$boundary\r\n";
            $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
            $message .= strip_tags($body) . "\r\n\r\n";
            
            $message .= "--$boundary\r\n";
            $message .= "Content-Type: text/html; charset=UTF-8\r\n";
            $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $message .= chunk_split(base64_encode($body)) . "\r\n\r\n";
            
            $message .= "--$boundary--\r\n";
            
            fwrite($socket, $message);
            self::sendCommand($socket, ".", '250');

            // QUIT
            self::sendCommand($socket, "QUIT", '221');

            fclose($socket);
            return true;
        } catch (\Exception $e) {
            error_log("SMTP Mailer Error [{$host}:{$port}] to <{$to}>: " . $e->getMessage());
            @fclose($socket);
            return false;
        }
    }

    private static function sendCommand($socket, $command, $expectedResponse)
    {
        fwrite($socket, $command . "\r\n");
        return self::readResponse($socket, $expectedResponse);
    }

    private static function readResponse($socket, $expectedResponse)
    {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) == ' ') {
                break;
            }
        }
        
        $code = substr($response, 0, 3);
        if ($code !== $expectedResponse) {
            throw new \Exception("SMTP error: Expected $expectedResponse, got $response");
        }
        return $response;
    }
}
