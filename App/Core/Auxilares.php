<?php

namespace core;

class Auxilares
{
    const E_MAIL_PADRAO = 'anderson@proscore.com.br';
    const TIPO_FATURA_VENCIDA = 6;
    const TIPO_RESPONSAVEL = NULL;
    const TIPO_P_CONTATO = NULL;

    public static function getDataAtual()
    {
        return date('Y-m-d');
    }
}
