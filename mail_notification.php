<?php


date_default_timezone_set('America/Sao_Paulo');

require __DIR__ . '/vendor/autoload.php';

set_time_limit(0);

use Core\Env;
use Core\Logs;
use Core\MailClass;
use Core\AppProcessor;
use Core\AppManipularError;


Env::load(__DIR__);
$acao = $argv[1] ?? 'desconhecida';
$detalhes = $argv[2] ?? '';

$data = date('d/m/Y H:i:s');

switch ($acao) {

    case 'reiniciado':

        $assunto = "ALERTA: Script Progestor foi reiniciado";

        $mensagem = "Foi identificado que o script Progestor parou e foi reiniciado.\n\n";
        $mensagem .= "Data/Hora: {$data}\n";
        $mensagem .= "Ação: Reinicialização automática\n";

        if (!empty($detalhes)) {
            $mensagem .= "Detalhes: {$detalhes}\n";
        }

        break;


    case 'erro':

        $assunto = "ERRO: Falha ao reiniciar Script Progestor";

        $mensagem = "ATENÇÃO!\n\n";
        $mensagem .= "Foi identificado que o script Progestor parou, porém não foi possível reiniciá-lo.\n\n";
        $mensagem .= "Data/Hora: {$data}\n";
        $mensagem .= "Ação: Tentativa de reinicialização\n";

        if (!empty($detalhes)) {
            $mensagem .= "Detalhes: {$detalhes}\n";
        }

        break;


    case 'parou':

        $assunto = "ALERTA: Script Progestor parou";

        $mensagem = "O script Progestor foi identificado como parado.\n\n";
        $mensagem .= "Data/Hora: {$data}\n";

        if (!empty($detalhes)) {
            $mensagem .= "Detalhes: {$detalhes}\n";
        }

        break;


    default:

        $assunto = "ALERTA: Monitor Progestor";

        $mensagem = "O monitor identificou uma ocorrência no Script Progestor.\n\n";
        $mensagem .= "Data/Hora: {$data}\n";
        $mensagem .= "Ação: {$acao}\n";

        if (!empty($detalhes)) {
            $mensagem .= "Detalhes: {$detalhes}\n";
        }

        break;
}


/*
|--------------------------------------------------------------------------
| Envia e-mail
|--------------------------------------------------------------------------
*/

$destinatario = $_ENV['SMTP_DESTINATION']
    ?? getenv('SMTP_DESTINATION')
    ?? null;


if (empty($destinatario)) {

    echo "[" . date('H:i:s') . "] ERRO: SMTP_DESTINATION não configurado.\n";

    exit(1);
}


try {

    $mail = new MailClass();

    $mail->enviar_email(
        $destinatario,
        $assunto,
        $mensagem
    );

    echo "[" . date('H:i:s') . "] E-mail enviado com sucesso.\n";
    echo $mensagem;
} catch (\Throwable $e) {

    echo "[" . date('H:i:s') . "] ERRO ao enviar e-mail: ";
    echo $e->getMessage() . "\n";

    exit(1);
}
