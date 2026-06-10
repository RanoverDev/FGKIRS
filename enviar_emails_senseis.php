<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/Helpers/Mailer.php';

$senseis = [
    ['name' => 'Dione Monteblaco', 'email' => 'dmonteblanco@gmail.com'],
    ['name' => 'Luis Aresso', 'email' => 'lc.aresso@outlook.com'],
    ['name' => 'Rogério Chagas', 'email' => 'rogeliochagasrodriguez@gmail.com'],
    ['name' => 'Rosângela Quatrin Ponciano', 'email' => 'ro_quatrin.ponciano@hotmail.com'],
    ['name' => 'Daniel Soares Guimarães', 'email' => 'profdaniboy@gmail.com'],
];

$adminLink = BASE_URL . '/login';
$resetLink = BASE_URL . '/recuperar-senha';
$logoUrl = BASE_URL . '/assets/images/logo-fgkirs-white.png';
$subject = 'FGKIRS - Seu Dojo foi cadastrado com sucesso!';

echo "Iniciando disparo de e-mails de boas-vindas...\n\n";

foreach ($senseis as $sensei) {
    $dojoName = "Dojo " . $sensei['name'];

    $body = "
        <div style='background-color: #f8fafc; padding: 20px; font-family: \"Helvetica Neue\", Helvetica, Arial, sans-serif; font-size: 14px; line-height: 1.6; color: #334155;'>
            <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06); border: 1px solid #e2e8f0;'>

                <!-- Header -->
                <div style='background-color: #0f172a; padding: 25px; text-align: center; border-bottom: 4px solid #EE302F;'>
                    <img src='{$logoUrl}' alt='FGKIRS' width='160' style='width: 160px; max-width: 100%; height: auto; display: inline-block;'>
                </div>

                <!-- Body Content -->
                <div style='padding: 40px 30px; background-color: #ffffff;'>
                    <h2 style='color: #0f172a; font-size: 20px; font-weight: bold; margin-top: 0; margin-bottom: 20px;'>Olá, " . htmlspecialchars($sensei['name']) . "!</h2>
                    <p style='margin-bottom: 20px;'>Temos a satisfação de informar que o dojo <strong>" . htmlspecialchars($dojoName) . "</strong> foi cadastrado com sucesso no sistema da <strong>Federação Gaúcha de Karatê Interestilos (FGKIRS)</strong> e você foi definido como o Sensei responsável.</p>

                    <hr style='border: 0; border-top: 1px solid #e2e8f0; margin: 20px 0;'>

                    <h3 style='color: #00AB4E; font-size: 16px; font-weight: bold; margin-top: 0; margin-bottom: 10px;'>A Importância da Atualização dos Dados</h3>
                    <p style='margin-bottom: 20px;'>Manter as informações do seu dojo atualizadas e o cadastro completo dos seus atletas (incluindo graduações e documentos) é essencial para garantir a participação ativa dos alunos nos eventos oficiais, exames de faixas e torneios organizados pela Federação.</p>

                    <h3 style='color: #00AB4E; font-size: 16px; font-weight: bold; margin-top: 0; margin-bottom: 10px;'>Publicação de Conteúdo</h3>
                    <p style='margin-bottom: 25px;'>Como Sensei responsável, você possui autonomia para publicar notícias, eventos ou artigos relacionados ao seu dojo diretamente em nosso portal. Quando publicar materiais externos, lembre-se de adicionar a fonte original correspondente (URL da notícia/artigo) para garantir a veracidade e autoria dos dados.</p>

                    <hr style='border: 0; border-top: 1px solid #e2e8f0; margin: 25px 0;'>

                    <h3 style='color: #0f172a; font-size: 15px; font-weight: bold; margin-top: 0; margin-bottom: 15px; text-align: center;'>Como Acessar o Painel Administrativo</h3>
                    <div style='text-align: center; margin-bottom: 25px;'>
                        <a href='{$adminLink}' style='background-color: #EE302F; color: #ffffff; padding: 12px 30px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block; font-size: 15px;'>Acessar Área Administrativa</a>
                    </div>

                    <p style='margin-bottom: 10px;'><strong>Primeiro acesso ou esqueceu sua senha?</strong></p>
                    <p style='margin-bottom: 0;'>Caso ainda não possua uma senha definida ou precise redefini-la, acesse o link abaixo e informe seu e-mail cadastrado (<em>" . htmlspecialchars($sensei['email']) . "</em>) para receber as instruções de redefinição:</p>
                    <p style='text-align: center; margin: 20px 0;'>
                        <a href='{$resetLink}' style='color: #EE302F; text-decoration: underline; font-weight: bold;'>Definir ou Redefinir Senha</a>
                    </p>

                    <hr style='border: 0; border-top: 1px solid #e2e8f0; margin-top: 25px; margin-bottom: 15px;'>
                    <p style='font-size: 11px; color: #64748b; margin-bottom: 0; text-align: center;'>Este é um e-mail automático enviado pelo sistema de gerenciamento FGKIRS.</p>
                </div>

                <!-- Footer -->
                <div style='background-color: #f1f5f9; padding: 30px; border-top: 1px solid #e2e8f0; text-align: center; font-size: 12px; color: #475569;'>
                    <p style='font-weight: bold; color: #1e293b; margin: 0 0 10px 0; text-transform: uppercase;'>Federação Gaúcha de Karatê Interestilos</p>
                    <p style='margin: 0 0 5px 0;'>Rua João Macluf, 333, Santa Rosa – RS</p>
                    <p style='margin: 0 0 5px 0;'>Telefone: (55) 3511 2602</p>
                    <p style='margin: 0 0 5px 0;'>WhatsApp: (55) 9 9988 1447</p>
                    <p style='margin: 0 0 15px 0;'>E-mail: <a href='mailto:falecom@fgkirs.com.br' style='color: #EE302F; text-decoration: none;'>falecom@fgkirs.com.br</a></p>
                    <p style='margin: 0; color: #94a3b8;'>&copy; " . date('Y') . " FGKIRS. Todos os direitos reservados.</p>
                </div>

            </div>
        </div>
    ";

    try {
        if (\Helpers\Mailer::send($sensei['email'], $subject, $body)) {
            echo "✅ E-mail enviado com sucesso para: {$sensei['email']}\n";
        } else {
            echo "❌ Falha ao enviar para: {$sensei['email']}\n";
        }
        // Aguarda 1 segundo entre envios para evitar rate limit do servidor SMTP
        sleep(1);
    } catch (Exception $e) {
        echo "❌ Erro ao enviar para {$sensei['email']}: " . $e->getMessage() . "\n";
    }
}

echo "\nDisparo concluído!\n";
