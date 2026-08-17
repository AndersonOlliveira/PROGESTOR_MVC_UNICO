<?php

namespace Core;

use Exception;
use Dotenv\Dotenv;
use Core\MailClass;
use Core\AppManipularError;

class Env
{
    /**
     * Valida e carrega o arquivo .env usando a biblioteca oficial
     * 
     * @param string $path Caminho absoluto da PASTA onde o .env está (ex: __DIR__ . '/../')
     */
    public static function load(string $path): void
    {
        // Garante que o caminho termine com barra para validar o arquivo
        $realPath = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        // 1. Se o arquivo físico .env não existir na pasta, gera e grava o erro
        if (!file_exists($realPath . '.env')) {
            $e = new Exception("Arquivo .env não encontrado em: {$realPath}");

            $manipulador = new AppManipularError(__DIR__ . '/../error/error_env.txt');
            $manipulador->manipuladorDeErros(
                $e->getCode(),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            );

            // $assunto = $_ENV['SMTP_SUBJECT'] ?? getenv('SMTP_SUBJECT') ?? 'Erro localizar arquivo .env';
            // $destinatario = $_ENV['SMTP_DESTINATION'] ??  ?? null;
            // $corpo = $e->getMessage();

            // if (!empty($destinatario)) {
            //     $mail = new MailClass();
            //     $mail->enviar_email($destinatario, $assunto, $corpo);
            // }

            // Interrompe a execução já que a aplicação não funciona sem o .env
            die($e->getMessage());
        }

        try {
            // 2. Deixa a biblioteca do Composer ler e injetar no $_ENV e putenv()
            $dotenv = Dotenv::createImmutable($path);
            $dotenv->load();
        } catch (Exception $e) {
            die("Erro na sintaxe do arquivo .env: " . $e->getMessage());
        }
    }
}
