<?php

namespace App\Utilis;

use Core\Model;
use App\Models\CapturaCamposDoPlugin;
use App\Models\CapturaPluginsDaConsulta;

class MontaJsonConfigEHeadersDaConsulta extends Model
{

	protected $utils;
	protected $tratamento;

	protected $MontaJsonConfigEHeadersDaConsultas;
	protected $capturaPlugins;
	protected $CapturaCamposDoPlugin;

	// O construtor fica limpo e sem require_once
	public function __construct()
	{
		$this->capturaPlugins = new CapturaPluginsDaConsulta();
		$this->CapturaCamposDoPlugin = new CapturaCamposDoPlugin();
	}

	public function execute($codConsulta)
	{
		ini_set('memory_limit', '1024M');
		ini_set('max_execution_time', 300); // 5 minutos

		$config = array();
		$return = array();

		$plugins = $this->capturaPlugins->execute($codConsulta);

		echo "<pre>";
		echo "LISTA COM OS \n";
		print_R($plugins);


		$header = "";
		foreach ($plugins as $plugin) {

			$campos = $this->CapturaCamposDoPlugin->execute($plugin['plugin']);

			echo "<pre>";
			echo "PLUGIN COM CAMPOS DA CONSULTA?\n";
			print_R($campos);


			$separar = (!empty($plugin['qt_ocorrencias']) && $plugin['qt_ocorrencias'] > 1);
			$key = 'header_' . $plugin['plugin'];


			if (!isset($return[$key])) {
				$return[$key] = '';
			}


			$i = 1;
			$camposPlg = [];

			foreach ($campos as $c) {

				$camposPlg[] = $i;

				if ($separar) {
					$return[$key] .= self::limpaNomeCampo($c['nome_campo']) . ";";
				} else {
					$header .= $c['nome_campo'] . " " . $plugin['plugin'] . ";";
				}

				$i++;
			}

			$arrPlugin = [
				"plugin" => $plugin['plugin'],
				"separar" => $separar,
				"ocorrencias" => $plugin['qt_ocorrencias'],
				"campos" => $camposPlg
			];

			$config[] = $arrPlugin;
		}

		$header = self::limpaNomeCampo($header);
		$return['json_config'] = json_encode($config, JSON_UNESCAPED_UNICODE);
		$return['header_arquivo_principal'] = $header;
		return $return;
	}

	private function limpaNomeCampo($header)
	{

		$header = preg_replace("/\;$/", "", $header);
		$header = preg_replace("/\n|\r|\t/", " ", $header);
		$header = preg_replace("/\s\s+/", " ", $header);

		return trim($header);
	}
}
