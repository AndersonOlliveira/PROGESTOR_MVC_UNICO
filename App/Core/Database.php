<?php

namespace Core;

use PDO;
use PDOException;
use Core\MailClass;
// use Core\Database;
use Core\AppManipularError;

class Database
{
    private static $instance = null;

    protected $manipulador;
    protected $mail;

    private static function initErrorHandler()
    {
        return new AppManipularError(__DIR__ . '/../error/error_banco.txt');
    }
    // private static function initMailHandler()
    // {
    //     return new AppManipularError(__DIR__ . '/../error/error_banco.txt');
    // }

    public function __construct()
    {
        $this->manipulador = self::initErrorHandler();

        $this->mail = new MailClass();
    }

    public static function connect()
    {
        if (!self::$instance) {

            try {

                self::$instance = new PDO(
                    sprintf(
                        "%s:host=%s;port=%s;dbname=%s",
                        $_ENV['DB_CONNECTION'],
                        $_ENV['DB_HOST'],
                        $_ENV['DB_PORT'],
                        $_ENV['DB_DATA_BASE']
                    ),
                    $_ENV['DB_USER'],
                    $_ENV['DB_PASSWORD']
                );

                self::$instance->setAttribute(
                    PDO::ATTR_ERRMODE,
                    PDO::ERRMODE_EXCEPTION
                );

                self::$instance->setAttribute(
                    PDO::ATTR_DEFAULT_FETCH_MODE,
                    PDO::FETCH_ASSOC
                );
            } catch (PDOException $e) {

                $manipulador = new AppManipularError(__DIR__ . '/../error/error_banco.txt');

                $assunto = $_ENV['SMTP_SUBJECT'] ?? getenv('SMTP_SUBJECT') ?? 'Erro de conexão com o banco';

                $destinatario = $_ENV['SMTP_DESTINATION'] ?? getenv('SMTP_DESTINATION') ?? null;
                $corpo = $e->getMessage();

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

                die('Erro ao conectar com banco: ' . $e->getMessage());
            }
        }

        return self::$instance;
    }
}
