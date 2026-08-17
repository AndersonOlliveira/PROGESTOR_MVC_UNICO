<?php

namespace App\Models;

use PDO;
use Core\Model;
use Core\Logs;
use PDOException;
use Core\Functions;
use Core\AppManipularError;

class CapturaDadosTransacoesJob extends Model
{
    public  function execute($idJob)
    {

        $sql =
            "SELECT
			t.resposta,
			t.campo_aquisicao,
			t.transacao_id 
		FROM
			progestor.processo p INNER JOIN 
			progestor.transacao t ON t.id_processo = p.processo_id
		WHERE
			p.processo_id = ? 
			AND t.status = 3  -- COLOCADO ESTA CONDICAO PARA TRATAR OS DADOS
            AND t.resposta_json is null 
           ;";

        $dados = array();
        $dados[] = $idJob;

        $result = $this->db->prepare($sql);
        $result->execute($dados);

        $transacoes = array();
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {

            $transacoes[] = $row;
        }

        if (count($transacoes) == 0) {
            return false;
        }

        return $transacoes;
    }
}
