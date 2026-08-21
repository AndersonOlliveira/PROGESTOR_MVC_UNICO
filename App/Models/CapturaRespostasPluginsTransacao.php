<?php

namespace App\Models;

use PDO;
use Core\Model;
use Core\Logs;
use PDOException;
use Core\Functions;
use Core\AppManipularError;
use App\Utilis\Model_ConsultaPrePago_Action_pretpsaldo;

class CapturaRespostasPluginsTransacao extends Model
{

	public function execute($idTransacao)
	{
		$sql =
			"SELECT
			r.header_arquivo,
			r.plugin,
			r.resposta 
		FROM
			progestor.respostas_plugin r
		WHERE
			r.id_transacao = ? 
		ORDER BY 
			r.plugin";

		$dados = array();
		$dados[] = $idTransacao;

		$result = $this->db->prepare($sql);
		$result->execute($dados);

		$registros = array();
		while ($row = $result->fetch(PDO::FETCH_ASSOC)) {

			$registros[] = $row;
		}

		if (count($registros) == 0) {
			return false;
		}

		return $registros;
	}
}
