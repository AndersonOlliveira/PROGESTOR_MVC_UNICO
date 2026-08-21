<?php

date_default_timezone_set('America/Sao_Paulo');

require __DIR__ . '/vendor/autoload.php';

// $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
// $dotenv->load();


set_time_limit(0);

use Core\Env;
use Core\Logs;
use Core\MailClass;
use Core\AppProcessor;
use Core\AppManipularError;


Env::load(__DIR__);

$app = new AppProcessor();

$id = 253;
$quantidade = 1000;

$tempoEspera = 20;

register_shutdown_function(function () {
    $error = error_get_last();

    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {

        $mensagem = "SCRIPT PAROU!\n\n" .
            "Erro: {$error['message']}\n" .
            "Arquivo: {$error['file']}\n" .
            "Linha: {$error['line']}\n";

        echo "[" . date('H:i:s') . "] " . $mensagem;


        $manipulador = new AppManipularError(__DIR__ . '/../error/error_iniciar.txt');
        $manipulador->manipuladorDeErros($error['type'], $error['message'], $error['file'], $error['line']);

        $destinatario = $_ENV['SMTP_DESTINATION'] ?? getenv('SMTP_DESTINATION') ?? null;
        if (!empty($destinatario)) {
            $assunto = "ALERTA: Script Progestor";
            $mail = new MailClass();
            $mail->enviar_email($destinatario, $assunto, $mensagem);
        }
    }
});

while (true) {

    try {

        echo "[" . date('H:i:s') . "] Executando processo CLI...\n";

        $app->processar(
            $id,
            $quantidade
        );

        echo "[" . date('H:i:s') . "] Aguardando {$tempoEspera} segundos...\n";

        sleep($tempoEspera);
    } catch (Throwable $e) {

        echo "[" . date('H:i:s') . "] ERRO: {$e->getMessage()}\n";

        $manipulador = new AppManipularError(__DIR__ . '/../error/error_iniciar.txt');

        $assunto = $_ENV['SMTP_SUBJECT'] ?? getenv('SMTP_SUBJECT') ?? 'Erro na execução script Progestor';

        $destinatario = $_ENV['SMTP_DESTINATION'] ?? getenv('SMTP_DESTINATION') ?? null;
        $corpo = "Progestor " . $e->getMessage();

        if (!empty($destinatario)) {
            $mail = new MailClass();
            $mail->enviar_email($destinatario, $assunto, $corpo);
        }

        $manipulador->manipuladorDeErros(
            $e->getCode(),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        );

        echo "Arquivo: {$e->getFile()}\n";
        echo "Linha: {$e->getLine()}\n";

        sleep($tempoEspera);
    }
}
