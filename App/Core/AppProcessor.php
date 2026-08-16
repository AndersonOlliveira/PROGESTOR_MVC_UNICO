<?php

namespace Core;

use Services\Processor;

class AppProcessor
{
    private Processor $processor;

    public function __construct()
    {
        $this->processor = new Processor();
    }

    public function processar($idProcesso, $qtLimit)
    {
        $this->processor->executar_ciclo(
            $idProcesso,
            $qtLimit
        );
    }
}
