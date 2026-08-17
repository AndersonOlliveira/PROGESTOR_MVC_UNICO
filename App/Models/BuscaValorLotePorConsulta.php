<?php

namespace App\Models;

use PDO;
use Core\Model;
use Core\Logs;
use App\Models\CapturaModulo;
use  App\Models\CapturaVolumetria;
use  App\Models\CapturaValorPrePago;
use  App\Models\CapturaValorDaConsulta;
use  App\Models\CapturaValorDaConsultaPorFaixa;

use PDOException;
use Core\Functions;
use Core\AppManipularError;




class BuscaValorLotePorConsulta extends Model
{

	/**VERSAO PRÉ PAGO */
	// public function calcula($codConsulta, $rede, $quantidade, $contrato)
	public function calcula($codConsulta, $rede, $quantidade)
	{

		// require_once 'CapturaValorDaConsulta.php';
		// require_once 'CapturaValorDaConsultaPorFaixa.php';
		// require_once 'CapturaModulo.php';
		// require_once 'CapturaVolumetria.php';
		// require_once 'CapturaValorPrePago.php';

		$CapturaModulo = new CapturaModulo();
		$CapturaVolumetria = new CapturaVolumetria();
		$CapturaValorPrePago = new CapturaValorPrePago();


		/**VERSÃO PRE PAGO */
		/*
		if ($contrato) {

			$result_prePago = $CapturaValorPrePago->push_value_prePago($contrato, $codConsulta);



			$values = $result_prePago[0];
			if (filter_var($values['perfilcobtipo'], FILTER_VALIDATE_BOOLEAN)) {
				// if (!empty($values['perfilcobtipo'])) {
				return [$values['valor'] * $quantidade, ['perfilcobtipo' => true]];
				// }
			}
		}

		*/


		$result = $CapturaModulo->resultModulo($codConsulta);



		$volumetria = $CapturaVolumetria->captura($rede, $codConsulta);

		$temFaixa = (is_array($volumetria) && isset($volumetria['rdefxacnsvlr']));
		$valorFaixa = $temFaixa ? $volumetria['rdefxacnsvlr'] : null;


		if ($result && !$temFaixa) {

			$valor = $CapturaModulo->CaputuraValor($codConsulta);
			if ($valor) {
				// return [0.98 * $quantidade, $result];
				return [$valor * $quantidade, $result];
			}
		}

		$capturaValor = new CapturaValorDaConsulta();
		$valor = !$valorFaixa ? $capturaValor->execute($codConsulta) : $valorFaixa;
		if ($valor) {
			return [$valor * $quantidade, $volumetria];
		}

		$capturaValorFaixa = new CapturaValorDaConsultaPorFaixa();
		$valor = !$valorFaixa ?  $capturaValorFaixa->execute($codConsulta, $rede, $quantidade) : $valorFaixa;
		if ($valor) {
			return [$valor * $quantidade, $volumetria];
		}

		return [0, $result];
	}
}
