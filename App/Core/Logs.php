<?php

namespace Core;

class Logs
{
    private static $arquivoLog = null;

    public static function iniciar()
    {
        self::$arquivoLog = $_SERVER['DOCUMENT_ROOT'] . '/../error/errorTratativa.txt';
        // echo "Logs iniciado. Log de erros será salvo em: " . self::$arquivoLog . PHP_EOL;
        $diretorio = dirname(self::$arquivoLog);

        if (!is_dir($diretorio)) {
            mkdir($diretorio, 0755, true);
        }
        if (!file_exists(self::$arquivoLog)) {
            touch(self::$arquivoLog);
            chmod(self::$arquivoLog, 0664);
        }

        // Regista os manipuladores globais
        set_error_handler([self::class, 'manipuladorDeErros']);
        set_exception_handler([self::class, 'manipuladorDeExcecoes']);
    }

    public static function manipuladorDeErros($nivel, $mensagem, $arquivo, $linha)
    {
        $dataHora = date('Y-m-d H:i:s');
        $linhaDoErro = "[{$dataHora}] Nível: {$nivel} | Erro: {$mensagem} | Arquivo: {$arquivo} | Linha: {$linha}" . PHP_EOL;
        error_log($linhaDoErro, 3, self::$arquivoLog);
        return false;
    }

    public static function manipuladorDeExcecoes($exception)
    {
        self::manipuladorDeErros($exception->getCode(), $exception->getMessage(), $exception->getFile(), $exception->getLine());
    }
}
