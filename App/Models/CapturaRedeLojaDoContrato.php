<?php


namespace App\Models;

use PDO;
use Core\Model;
use Core\Logs;
use PDOException;
use Core\Functions;
use Core\AppManipularError;

class CapturaRedeLojaDoContrato extends Model
{
	public  function execute($contrato)
	{

		$sql =
			"SELECT rdeljaid as loja, rdeljarde as rede FROM ctr inner join rdelja on rdeljactr = ctrid WHERE ctrid = ? LIMIT 1;";

		$dados = array();
		$dados[] = $contrato;

		$result = $this->db->prepare($sql);
		$result->execute($dados);

		$rede = null;
		$loja = null;
		if ($row = $result->fetch(PDO::FETCH_ASSOC)) {

			$rede = $row['rede'];
			$loja = $row['loja'];
		}

		return array(
			'rede' => $rede,
			'loja' => $loja
		);
	}
}
