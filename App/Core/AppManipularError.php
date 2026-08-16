<?php

namespace Core;

class AppManipularError
{
    private string $arquivoLog;

    public function __construct($arquivoLog)
    {
        $this->arquivoLog = $arquivoLog;
    }

    public function manipuladorDeErros(
        $nivel,
        $mensagem,
        $arquivo,
        $linha
    ) {
        $dataHora = date('Y-m-d H:i:s');

        $linhaDoErro =
            "[{$dataHora}] Nível: {$nivel} | Erro: {$mensagem} | Arquivo: {$arquivo} | Linha: {$linha}" .
            PHP_EOL;

        error_log($linhaDoErro, 3, $this->arquivoLog);

        return false;
    }
}
