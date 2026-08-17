<?php

namespace App\Models;

use PDO;
use Core\Model;
use Core\Logs;
use PDOException;
use Core\Functions;
use Core\AppManipularError;

class CapturaDadosJob extends Model
{

    public  function execute($idJob)
    {

        $sql =
            "SELECT
			p.nome_arquivo,
			p.header_arquivo,
			p.contrato 
		FROM
			progestor.processo p
		WHERE
			p.processo_id = ? 
		LIMIT 1";

        $dados = array();
        $dados[] = $idJob;

        $result = $this->db->prepare($sql);
        $result->execute($dados);

        if ($row = $result->fetch(PDO::FETCH_ASSOC)) {

            return $row;
        }

        return false;
    }
}
