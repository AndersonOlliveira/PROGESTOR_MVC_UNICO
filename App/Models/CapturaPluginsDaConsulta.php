<?php

namespace App\Models;

use PDO;
use Core\Model;
use Core\Logs;
use PDOException;
use Core\Functions;
use Core\AppManipularError;

class CapturaPluginsDaConsulta extends Model
{

	public function execute($codConsulta)
	{


		ini_set('memory_limit', '1024M');

		$sql = "SELECT 
			regcod as plugin, 
			regoco as qt_ocorrencias
		FROM 
			rdecnsreg inner join 
			reg on rdecnsregreg = regid 
		WHERE 
			rdecnsregrdecns = ?
		ORDER BY
			rdecnsregpri;";

		$dados = array();
		$dados[] = $codConsulta;

		$result = $this->db->prepare($sql);
		$result->execute($dados);

		$registros = array();
		while ($row = $result->fetch(PDO::FETCH_ASSOC)) {


			$registros[] = $row;
		}

		return $registros;
	}
}
