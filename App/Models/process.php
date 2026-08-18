<?php

namespace App\Models;

use PDO;
use Core\Model;
use Core\Logs;
use Core\Functions;
use PDOException;
use Core\AppManipularError;



class process extends Model
{
	protected $funciton;
	protected $manipulador;
	##[Override]
	public function __construct()
	{
		$this->funciton  = new Functions();
		$this->manipulador = new AppManipularError(__DIR__ . '/../error/error.txt');
		return parent::__construct();
	}


	public function list_processo($idProcesso, $qtLimit, $info = false)
	{
		$this->funciton->logInfo(date('Y-m-d H:i:s') . " - Iniciando list_processo com idProcessos:  {$idProcesso}  \n");
		$this->funciton->logInfo(date('Y-m-d H:i:s') . " - Iniciando list_processo com x linhas : {$qtLimit} \n");

		$sql = "";
		$sql = "SELECT p.processo_id, p.contrato,
			p.rede,
			p.codcns,
			p.nome_arquivo,
			p.aceite_execucao,
			p.mensagem_alerta,
			p.data_cadastro,
			p.configuracao_json,
			p.campos_aquisicao,
			p.loja,
			p.finalizado,
			p.data_finalizacao,
			p.pause,
			t.transacao_id,
			t.id_processo,
			t.resposta_json
			FROM
		    progestor.transacao t INNER JOIN 
			progestor.processo p ON p.processo_id = t.id_processo 
		WHERE 
		--revovmento o 3 pois ele já e o resultado final, quando faz a busca dento do mongo ele não acha os dados e gera uma demora para concluir os dados
			t.status in (3) AND 
			p.finalizado = true AND
			p.error = false";

		try {

			$params = [];
			if ($idProcesso !== null) {
				$sql .= " AND p.processo_id = ?";
				$params[] = $idProcesso;
			}


			$sql .= " AND p.pause = ?";
			$params[] = $info ? 1 : 0;;



			if ($qtLimit !== null) {
				$qtLimit = (int)$qtLimit; // garante que é inteiro
				$sql .= " ORDER BY random() LIMIT $qtLimit;";
			} else {
				$sql .= " ORDER BY random() LIMIT 10;";
			}


			$results = $this->db->prepare($sql);
			$results->execute($params);

			return $results->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException $e) {
			$this->manipulador->manipuladorDeErros(
				$e->getCode(),
				$e->getMessage(),
				$e->getFile(),
				$e->getLine()
			);
		}
	}

	public function list_processo_parar($idProcesso, $qtLimit, $info = false)
	{
		$this->funciton->logInfo(date('Y-m-d H:i:s') . " - Iniciando list_processo com idProcessos:  {$idProcesso}  \n");
		$this->funciton->logInfo(date('Y-m-d H:i:s') . " - Iniciando list_processo com x linhas : {$qtLimit} \n");


		$sql = "";
		$sql = "SELECT p.processo_id, p.contrato,
			p.rede,
			p.codcns,
			p.nome_arquivo,
			p.aceite_execucao,
			p.mensagem_alerta,
			p.data_cadastro,
			p.configuracao_json,
			p.campos_aquisicao,
			p.loja,
			p.finalizado,
			p.data_finalizacao,
			p.pause,
			t.transacao_id,
			t.id_processo,
			t.resposta_json
			FROM
		    progestor.transacao t INNER JOIN 
			progestor.processo p ON p.processo_id = t.id_processo 
		WHERE 
			t.status in (12,3) AND 
			p.mensagem_alerta = 'parar_processo'
			AND 
			--p.contrato = 417039 AND
			(p.finalizado = false or p.finalizado = true) AND 
			p.error = false";


		$params = [];
		if ($idProcesso !== null) {
			$sql .= " AND p.processo_id = ?";
			$params[] = $idProcesso;
		}


		$sql .= " AND p.pause = ?";
		$params[] = $info ? 1 : 0;




		if ($qtLimit !== null) {
			$qtLimit = (int)$qtLimit; // garante que é inteiro
			$sql .= " ORDER BY random() LIMIT $qtLimit;";
		} else {
			$sql .= " ORDER BY random() LIMIT 10;";
		}


		try {
			$results = $this->db->prepare($sql);
			$results->execute($params);
			// $result = $this->db->query($sql);

			return $results->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException  $e) {

			$this->manipulador->manipuladorDeErros(
				$e->getCode(),
				$e->getMessage(),
				$e->getFile(),
				$e->getLine()
			);
		}
	}

	public function list_processo_alert($idProcesso, $qtLimit)
	{

		$this->funciton->logInfo(date('Y-m-d H:i:s') . " - Iniciando list_processo com x linhas : {$qtLimit} \n");


		$sql = "SELECT p.processo_id, p.contrato,
			p.rede,
			p.codcns,
			p.nome_arquivo,
			p.aceite_execucao,
			p.mensagem_alerta,
			p.data_cadastro,
			p.configuracao_json,
			p.campos_aquisicao,
			p.loja,
			p.finalizado,
			p.data_finalizacao,
			p.pause,
			t.transacao_id,
			t.id_processo,
			t.resposta_json
			FROM
		    progestor.transacao t INNER JOIN 
			progestor.processo p ON p.processo_id = t.id_processo 
		WHERE 
			t.status in (2,12) AND 
		    p.mensagem_alerta = '1' and
			t.sucesso = true AND 
			p.finalizado = true AND 
		    p.pause = false";

		$params = [];
		if ($idProcesso !== null) {
			$sql .= " AND p.processo_id = ?";
			$params[] = $idProcesso;
		}


		if ($qtLimit !== null) {
			$qtLimit = (int)$qtLimit; // garante que é inteiro
			$sql .= " ORDER BY random() LIMIT $qtLimit;";
		} else {
			$sql .= " ORDER BY random() LIMIT 10;";
		}

		try {
			$results = $this->db->prepare($sql);
			$results->execute($params);
			// $result = $this->db->query($sql);

			return $results->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException  $e) {

			$this->manipulador->manipuladorDeErros(
				$e->getCode(),
				$e->getMessage(),
				$e->getFile(),
				$e->getLine()
			);
		}
	}


	public function list_processo_modulo($idProcesso, $qtLimit)
	{

		$this->funciton->logInfo(date('Y-m-d H:i:s') . " - Iniciando Pesquisa modulo com x linhas : {$qtLimit} \n");


		$sql = "SELECT 
		    p.processo_id,
			p.contrato,
			p.rede,
			p.codcns,
			p.data_cadastro,
			p.data_finalizacao,
			p.valor_total
			FROM
			progestor.processo p 
		WHERE 
		    p.status_output = 2 and 
		    p.finalizado = true AND
		    p.pause = false";



		$params = [];
		if ($idProcesso !== null) {
			$sql .= " AND p.processo_id = ?";
			$params[] = $idProcesso;
		}


		if ($qtLimit !== null) {
			$qtLimit = (int)$qtLimit; // garante que é inteiro
			$sql .= " ORDER BY random() LIMIT $qtLimit;";
		} else {
			$sql .= " ORDER BY random() LIMIT 10;";
		}

		try {




			$results = $this->db->prepare($sql);
			$results->execute($params);
			// $result = $this->db->query($sql);

			return $results->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException  $e) {

			$this->manipulador->manipuladorDeErros(
				$e->getCode(),
				$e->getMessage(),
				$e->getFile(),
				$e->getLine()
			);
		}
	}


	public function count_new_quantidade_OLD($idProcesso, $qtLimit)
	{

		$this->funciton->logInfo(date('Y-m-d H:i:s') . " - Iniciando Pesquisa modulo com x linhas : {$qtLimit} \n");



		$sql = "";
		$sql = "SELECT 
		    p.processo_id,
			p.valor_total,
			p.contrato,
			p.codcns,
			 COALESCE(SUM(CASE WHEN  t.status != 6 or t.status != 17 AND t.campo_aquisicao IS NOT NULL  THEN 1 ELSE 0 END), 0) AS qtd_registros
			FROM
			progestor.processo p 
			inner join 
			progestor.transacao as t on 
		    (p.processo_id = t.id_processo)
		WHERE 
		    p.status_output = 2 and 
			-- p.processo_id = 51 AND
		    p.finalizado = true AND
		    p.pause = false
			group by p.processo_id ";



		$params = [];
		// if ($idProcesso !== null) {
		// 	$sql .= " AND p.processo_id = ?";
		// 	$params[] = $idProcesso;
		// }


		if ($qtLimit !== null) {
			$qtLimit = (int)$qtLimit; // garante que é inteiro
			$sql .= " ORDER BY random() LIMIT $qtLimit;";
		} else {
			$sql .= " ORDER BY random() LIMIT 10;";
		}

		try {
			$results = $this->db->prepare($sql);
			$results->execute($params);
			// $result = $this->db->query($sql);

			return $results->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException  $e) {

			$this->manipulador->manipuladorDeErros(
				$e->getCode(),
				$e->getMessage(),
				$e->getFile(),
				$e->getLine()
			);
		}
	}


	public function push_value_modulo($rede, $codConsulta, $dataInicio, $dataFim, $qtLimit)
	{

		$this->funciton->logInfo(date('Y-m-d H:i:s') . " - Iniciando Pesquisa modulo com x linhas : {$qtLimit} \n");

		// (4061, 266982, '2025-12-03 00:00:00','2025-12-03 23:59:59');";
		$sql = "
		SELECT * 
		FROM
		progestor.fnc_extrato_modulos (
		:rede,
        :codConsulta,
        :data_inicio,
        :data_fim);";


		$stmt = $this->db->prepare($sql);

		$stmt->bindValue(':rede', $rede, PDO::PARAM_INT);
		$stmt->bindValue(':codConsulta', $codConsulta, PDO::PARAM_INT);
		$stmt->bindValue(':data_inicio', $dataInicio);
		$stmt->bindValue(':data_fim', $dataFim);

		try {


			$stmt->execute();

			$resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);

			return $resultado;
		} catch (PDOException  $e) {

			$this->manipulador->manipuladorDeErros(
				$e->getCode(),
				$e->getMessage(),
				$e->getFile(),
				$e->getLine()
			);
		}
	}

	public function list_processo_qta_process()
	{

		$this->funciton->logInfo(date('Y-m-d H:i:s') . " - Iniciando processo com status mensagem 1 \n");


		$sql = "SELECT COALESCE(SUM(CASE WHEN t.status in (2,5) THEN 1 ELSE 0 END), 0) AS qta_processar,
		p.processo_id,
		p.mensagem_alerta as info
		FROM progestor.transacao as t
		inner join progestor.processo as p on (p.processo_id = t.id_processo)
		where p.mensagem_alerta ='1'
		group by p.mensagem_alerta, p.processo_id;";

		try {




			$results = $this->db->prepare($sql);
			$results->execute();
			// $result = $this->db->query($sql);

			return $results->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException  $e) {

			$this->manipulador->manipuladorDeErros(
				$e->getCode(),
				$e->getMessage(),
				$e->getFile(),
				$e->getLine()
			);
		}
	}


	public  function list_data_modulo($idConsultation)
	{

		$sql = "";
		$sql = "SELECT rdecnsmod FROM rdecns WHERE rdecnsid = ? LIMIT 1";

		try {
			$stmt = $this->db->prepare($sql);
			$stmt->execute([$idConsultation]);

			$row = $stmt->fetch(PDO::FETCH_ASSOC);

			if (!$row) {

				return false;
			}

			//forco o retorno ser um boleano
			return filter_var($row['rdecnsmod'], FILTER_VALIDATE_BOOLEAN);
		} catch (PDOException $e) {
			$this->manipulador->manipuladorDeErros(
				$e->getCode(),
				$e->getMessage(),
				$e->getFile(),
				$e->getLine()
			);

			return false;
		}
	}



	public function finish_process_die($id_process)
	{
		$erros = [];

		try {
			//recebe 6 
			$sql = "UPDATE progestor.processo SET finalizado = ?, data_finalizacao = ?, mensagem_alerta = ? WHERE processo_id = ? ";
			// $sql = "UPDATE progestor.processo SET valor_total = ?, finalizado = ?, data_finalizacao = ? WHERE processo_id = ? ";

			// $dados =  [$value, true, date("Y-m-d H:i:s"), $id_process];
			$dados =  [true, date("Y-m-d H:i:s"), 0, $id_process];
			$result = $this->db->prepare($sql);
			$result->execute($dados);
		} catch (\Exception $e) {


			$this->manipulador->manipuladorDeErros(
				$e->getCode(),
				$e->getMessage(),
				$e->getFile(),
				$e->getLine()
			);



			$erros[] = [
				'msg' =>  $e->getMessage()
			];
		}


		return [
			'erros' => empty($erros) ? [] : $erros,
			'status' => empty($erros) ? 2 : 0,

		];
	}
	//finizo o job com status 8 para 17 para finalizar o job
	public function up_status_eight($dados)
	{
		$erros = [];

		echo "<pre>";
		echo "chegando no update para o status 17\n";

		print_r($dados['processo_id']);


		try {
			//recebe 17
			$sql = " UPDATE progestor.transacao SET status = ?, sucesso = ? , resposta_json = ?  WHERE id_processo = ? 
			AND transacao_id IN ( SELECT transacao_id FROM progestor.transacao 
			WHERE id_processo = ?  AND status = ? )";

			$sql_up =  [17, 0, 'error', $dados['processo_id'], $dados['processo_id'], 8];

			echo "<pre>";
			echo "meu sql pronto\n";
			print_R($sql_up);
			$result = $this->db->prepare($sql);
			$result->execute($sql_up);
		} catch (\Exception $e) {


			$this->manipulador->manipuladorDeErros(
				$e->getCode(),
				$e->getMessage(),
				$e->getFile(),
				$e->getLine()
			);



			$erros[] = [
				'msg' =>  $e->getMessage()
			];
		}


		return [
			'erros' => empty($erros) ? [] : $erros,
			'status' => empty($erros) ? 2 : 0,

		];
	}
	public function atualizarValorJobs($id_process, $contrato, $valor)
	{
		$erros = [];

		try {
			//atualizo o valor correto.
			$sql = "UPDATE progestor.processo SET valor_total = ? WHERE processo_id = ? and contrato = ? ";


			$dados =  [$valor, $id_process, $contrato];
			$result = $this->db->prepare($sql);
			$result->execute($dados);
		} catch (\Exception $e) {

			$this->manipulador->manipuladorDeErros(
				$e->getCode(),
				$e->getMessage(),
				$e->getFile(),
				$e->getLine()
			);

			echo $e->getMessage();


			$erros[] = [
				'msg' =>  $e->getMessage()
			];
		}


		return [
			'erros' => empty($erros) ? [] : $erros,
			'status' => empty($erros) ? 2 : 0,

		];
	}


	public function get_query_all()
	{
		ini_set('memory_Limit', '1024M');


		$query = "SELECT *
		FROM
        pg_stat_activity;";

		$result = $this->db->query($query);

		try {
			if (!$result) {
				echo "Ocorreu um erro na consulta.\n";
				exit;
			} else {

				return $result->fetchAll(PDO::FETCH_ASSOC);
			}
		} catch (PDOException $e) {
			$this->manipulador->manipuladorDeErros(
				$e->getCode(),
				$e->getMessage(),
				$e->getFile(),
				$e->getLine()
			);
		}
	}

	public function get_all_teste()
	{

		return "estou aquiiii!!!!";
	}

	public function filtros_data($ids)
	{
		$sql = "SELECT 
		 t.id_processo,
         t.transacao_id,
         t.status as new_status,
		 t.resposta_json,
		 t.sucesso,
		 t.campo_aquisicao
		FROM
		progestor.transacao t INNER JOIN 
		progestor.processo p ON p.processo_id = t.id_processo 
		WHERE 
		    t.status = 2 AND 
			t.sucesso = true AND 
			p.finalizado = false AND 
		    p.pause = false";

		$params = [];
		if ($ids != null) {
			$sql .= " AND t.transacao_id = ?;";
			$params[] = $ids;
		}

		$results = $this->db->prepare($sql);

		try {
			$results->execute($params);

			//ira receber um lote de ids para alterar para 0 resposta_json vazio
			$up_status_one = [];
			$up_mongo_data = [];

			while ($row = $results->fetchAll(PDO::FETCH_ASSOC)) {

				if (empty($row['resposta_json'])) {

					$up_status_one = $row[0];
				}
				if (!isset($row['resposta_json'])) {

					$up_mongo_data = $row[0];
				}
			}

			if (isset($up_mongo_data)) {

				return $up_mongo_data;
			}

			self::up_zero_status($up_status_one['transacao_id']);
		} catch (PDOException $e) {
			$this->manipulador->manipuladorDeErros(
				$e->getCode(),
				$e->getMessage(),
				$e->getFile(),
				$e->getLine()
			);
		}
	}

	public function up_zero_status($ids)
	{

		$sql = "UPDATE progestor.transacao SET status = ? , sucesso =? where transacao_id = ?;";

		try {
			$dadosTransacao = [0, 0, $ids];
			$results = $this->db->prepare($sql);
			$results->execute($dadosTransacao);

			echo "ok para atualizar\n";

			return true;
		} catch (\Exception $e) {
			$this->manipulador->manipuladorDeErros(
				$e->getCode(),
				$e->getMessage(),
				$e->getFile(),
				$e->getLine()
			);

			return $e->getMessage();
		}
	}

	public function up_valor_modules($dados)
	{

		echo "<pre>";
		echo "MEUS DADOS PARA SER ATUALIZAD NO POSTEGREE\n";

		$sql = "UPDATE progestor.processo SET valor_total = ? where processo_id = ?;";

		try {
			$dadosTransacao = [$dados['valor_geral'], $dados['processo_id']];
			$results = $this->db->prepare($sql);
			$results->execute($dadosTransacao);

			echo "ok para atualizar\n";

			return true;
		} catch (\Exception $e) {
			$this->manipulador->manipuladorDeErros(
				$e->getCode(),
				$e->getMessage(),
				$e->getFile(),
				$e->getLine()
			);

			return $e->getMessage();
		}
	}

	public function size_pgAdmin()
	{
		$sql = "SELECT 
        nspname || '.' || relname AS tabela,
        pg_size_pretty(pg_relation_size(c.oid)) AS tamanho_dados,
        pg_total_relation_size(c.oid) AS tamanho_total,
        ROUND(c.reltuples) AS estimated_rows
    FROM 
        pg_class c
    LEFT JOIN
        pg_namespace n ON n.oid = c.relnamespace
    WHERE
        relkind = 'r'
        AND nspname = 'progestor';
    ";


		try {
			$results = $this->db->prepare($sql);
			$results->execute();
			return $results->fetchAll(PDO::FETCH_ASSOC);
		} catch (\Exception $e) {
			$this->manipulador->manipuladorDeErros(
				$e->getCode(),
				$e->getMessage(),
				$e->getFile(),
				$e->getLine()
			);
		}
	}


	public function count_new_quantidade()
	{
		$sql = "";
		$sql = "SELECT 
    p.processo_id,
    p.valor_total,
    p.contrato,
    p.codcns,
    COALESCE(
        SUM(
            CASE 
                WHEN t.status != 6 AND t.status != 17
                     AND t.campo_aquisicao IS NOT NULL 
                THEN 1 
                ELSE 0 
            END
        ), 0
    ) AS qtd_registros
FROM progestor.processo p
INNER JOIN progestor.transacao t 
    ON p.processo_id = t.id_processo
WHERE 
    p.status_output = 2 
	AND p.finalizado = true 
    AND p.pause = false
GROUP BY 
    p.processo_id,
    p.valor_total,
    p.contrato,
    p.codcns
HAVING 
    p.valor_total < COALESCE(
        SUM(
            CASE 
                WHEN t.status != 6 and t.status != 17
                     AND t.campo_aquisicao IS NOT NULL 
                THEN 1 
                ELSE 0 
            END
        ), 0
    );
";

		try {
			$results = $this->db->prepare($sql);
			$results->execute();
			return $results->fetchAll(PDO::FETCH_ASSOC);
		} catch (\Exception $e) {
			$this->manipulador->manipuladorDeErros(
				$e->getCode(),
				$e->getMessage(),
				$e->getFile(),
				$e->getLine()
			);

			print_r($e->getMessage());
		}
	}

	//funcao para pegar a quantidade de dia que estes processo esta paralisado
	public function get_count_day_process()
	{

		$sql = "";
		$sql = "";
	}

	public function get_limit_day_contrato($contrato)
	{


		$sql = "";
		$sql = "SELECT limite_dias
			FROM
			progestor.config_limit_dias
			WHERE
			ctr_cliente = ?
			AND limite_dias > 0
			ORDER BY
			data_configuracao
			DESC limit 1;";

		$Newregistros = [];
		$dados = [$contrato];

		try {
			$result = $this->db->prepare($sql);
			$result->execute($dados);
		} catch (PDOException $e) {
			print_R("Erro na consulta: " . $e->getMessage());
			$this->manipulador->manipuladorDeErros(
				$e->getCode(),
				$e->getMessage(),
				$e->getFile(),
				$e->getLine()
			);
			return $e->getMessage();
		}


		$row = $result->fetch(PDO::FETCH_ASSOC);

		if (!$row) {
			return false; //
		}

		return (int) $row['limite_dias'];
	}


	public function breack_process_paralizar($id_process, $id_status)
	{

		$erros = [];


		try {
			//recebe 6 
			$sql = "UPDATE progestor.transacao SET status = ? WHERE id_processo = ? and status not in (12,3);";
			// $sql = "SELET progestor.transacao SET status = ? WHERE id_processo = ? ";

			$dados = array();
			$dados[] = $id_status;
			$dados[] = $id_process;

			$result = $this->db->prepare($sql);

			$result->execute($dados);
		} catch (\Exception $e) {
			$this->manipulador->manipuladorDeErros(
				$e->getCode(),
				$e->getMessage(),
				$e->getFile(),
				$e->getLine()
			);

			$this->db->rollback();

			$erros[] = [
				'msg' =>  $e->getMessage()
			];
		}

		return [
			'erros' => empty($erros) ? [] : $erros,
			'status' => empty($erros) ? 2 : 0,
			'qta_afetadas' => $result->rowCount()

		];
	}


	public function lista_data_paralisados($idProcesso)
	{
		$sql =  "";
		$sql =  "SELECT transacao_id,id_processo
	           FROM PROGESTOR.TRANSACAO 
			   where id_processo = ?
			   AND campo_aquisicao is not null 
			   AND status NOT IN (3,12,17);";

		try {

			$Newregistros = [];
			$dados = [$idProcesso];
			$result = $this->db->prepare($sql);
			$result->execute($dados);
		} catch (PDOException $e) {
			print_R("Erro na consulta: " . $e->getMessage());
			$this->manipulador->manipuladorDeErros(
				$e->getCode(),
				$e->getMessage(),
				$e->getFile(),
				$e->getLine()
			);
			return null;
		}

		if ($result->rowCount() == 0) {
			return null;
		}

		while ($row = $result->fetchall(PDO::FETCH_ASSOC)) {
			$Newregistros = [
				'ids' => $row,
				'linhas_afetadas' => $result->rowCount()
			];
		}

		// echo "<pre>";
		// echo "meus registros\n";

		// print_r($Newregistros);
		// die();


		return $Newregistros;
	}

	//pega a quantidade para realizar o update
	public function count_process_finalizado_paralizado($idProcesso)
	{
		$sql =  "";
		$sql =  "SELECT count(*) as total,
		  p.contrato,
		  p.processo_id,p.rede, codcns as consultas,p.mensagem_alerta,
		  SUM(CASE WHEN t.status = 3
                     AND t.campo_aquisicao IS NOT NULL  THEN 1 ELSE 0 END) AS finalizados
	      FROM
		    progestor.transacao t INNER JOIN 
			  progestor.processo p ON p.processo_id = t.id_processo 
			   where id_processo = ?
			   AND campo_aquisicao is not null 
			   AND status IN (3,12)
			--    AND p.menssagem_alerta is null
			   group by  p.contrato, p.processo_id,p.mensagem_alerta;";

		try {

			$Newregistros = [];
			$dados = [$idProcesso];
			$result = $this->db->prepare($sql);
			$result->execute($dados);
		} catch (PDOException $e) {
			print_R("Erro na consulta: " . $e->getMessage());
			$this->manipulador->manipuladorDeErros(
				$e->getCode(),
				$e->getMessage(),
				$e->getFile(),
				$e->getLine()
			);
			return null;
		}

		if ($result->rowCount() == 0) {
			return null;
		}

		while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
			$Newregistros = $row;
		}

		return $Newregistros;
	}

	//pega a quantidade para realizar o update
	public function count_process_paradados($idProcesso)
	{
		$sql =  "";
		$sql =  "SELECT 
		  SUM(CASE WHEN t.status in (17)
                     AND t.campo_aquisicao IS NOT NULL  THEN 1 ELSE 0 END) AS total_paralizados
	      FROM
		    progestor.transacao t INNER JOIN 
			  progestor.processo p ON p.processo_id = t.id_processo 
			   where id_processo = ?;";

		try {

			$Newregistros = [];
			$dados = [$idProcesso];
			$result = $this->db->prepare($sql);
			$result->execute($dados);
		} catch (PDOException $e) {
			print_R("Erro na consulta: " . $e->getMessage());
			$this->manipulador->manipuladorDeErros(
				$e->getCode(),
				$e->getMessage(),
				$e->getFile(),
				$e->getLine()
			);
			return null;
		}

		if ($result->rowCount() == 0) {
			return null;
		}

		while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
			$Newregistros = $row;
		}

		return $Newregistros;
	}

	public function alter_valores_process_paralizar($id_process, $value)
	{
		$erros = [];

		try {

			$sql = "UPDATE progestor.processo SET valor_total = ?, mensagem_alerta = ?, pause = ? WHERE processo_id = ? ";
			$dados =  [$value, '1', 0, $id_process];
			$result = $this->db->prepare($sql);
			$result->execute($dados);

			echo '<pre>';
			echo "MEU SQL MONTATO PARA UPDATE\n";

			var_dump($sql);

			print_r('ESTOU SAINDO AQUI\n');
		} catch (\Exception $e) {

			$this->manipulador->manipuladorDeErros(
				$e->getCode(),
				$e->getMessage(),
				$e->getFile(),
				$e->getLine()
			);
			echo "<pre>";
			echo "erro vindo aqui\n";
			print_r($e->getMessage());

			$erros[] = [
				'msg' =>  $e->getMessage()
			];
		}


		return [
			'erros' => empty($erros) ? [] : $erros,
			'status' => empty($erros) ? 2 : 0,

		];
	}

	public function busca_erros_eight()
	{

		$sql =  "";
		$sql = "SELECT  p.processo_id,
          SUM(t.qtd_registros) AS qtd_registros,
          SUM(t.qtd_status_egth) AS qtd_status_egth,
          SUM(t.qtd_registros_processado) AS qtd_registros_processado
		  FROM progestor.processo AS p
		  INNER JOIN (
		  SELECT 
           id_processo, 
           transacao_id,
		   SUM(
            CASE 
                WHEN campo_aquisicao IS NOT NULL 
                AND status NOT IN (6,17)
                THEN 1 ELSE 0 
            END
        ) AS qtd_registros,
       
        SUM(
            CASE 
                WHEN campo_aquisicao IS NOT NULL 
                AND status IN (3)
                THEN 1 ELSE 0 
            END
        ) AS qtd_registros_processado,
				SUM(
					CASE 
						WHEN campo_aquisicao IS NOT NULL 
						AND status = 8
						THEN 1 ELSE 0 
					END
				) AS qtd_status_egth
			FROM progestor.transacao
			GROUP BY id_processo, transacao_id
		) t ON t.id_processo = p.processo_id
		where p.pause = false AND p.error = false
		GROUP BY p.processo_id
		HAVING SUM(t.qtd_status_egth) > 0 order by p.processo_id desc;";


		try {
			$results = $this->db->prepare($sql);
			$results->execute();
			// $result = $this->db->query($sql);

			return $results->fetchAll(PDO::FETCH_ASSOC);
		} catch (\Exception $e) {
			$this->manipulador->manipuladorDeErros(
				$e->getCode(),
				$e->getMessage(),
				$e->getFile(),
				$e->getLine()
			);
			print_r($e->getMessage());
		}
	}

	public function up_status_finish_eight($id)
	{

		echo "<pre>";
		echo "MEUS DADOS PARA SER ATUALIZAD NO POSTEGREE\n";

		$sql = "UPDATE progestor.processo SET finalizado = ? where processo_id = ?;";

		try {
			//true = 1 
			$dadosTransacao = [1, $id];
			$results = $this->db->prepare($sql);
			$results->execute($dadosTransacao);

			echo "ok para atualizar\n";

			return true;
		} catch (\Exception $e) {
			echo "<pre>";
			$this->manipulador->manipuladorDeErros(
				$e->getCode(),
				$e->getMessage(),
				$e->getFile(),
				$e->getLine()
			);

			print_r($e->getMessage());

			return $e->getMessage();
		}
	}

	public function get_ids_contrato($id_contrato)
	{

		$sql =  "";
		$sql = "SELECT p.processo_id 
		 FROM progestor.processo AS p
		 where p.contrato = ?
		 GROUP BY p.processo_id
		 order by p.processo_id desc;";


		try {
			$results = $this->db->prepare($sql);
			$results->execute([$id_contrato]);
			if ($results->rowCount() == 0) {
				return [
					'success' => (boolval(false)),
					'message' => 'dados para o contrato não localizado'
				];
			}
			return $results->fetchAll(PDO::FETCH_ASSOC);
		} catch (\Exception $e) {
			$this->manipulador->manipuladorDeErros(
				$e->getCode(),
				$e->getMessage(),
				$e->getFile(),
				$e->getLine()
			);
			return [
				'success' => false,
				'error' => $e->getMessage()
			];
		}
	}

	public function get_fingers_process_jobs($id_contrato)
	{

		$sql =  "";
		$sql = "SELECT p.processo_id, p.data_cadastro, p.finger,p.mensagem_alerta
		 FROM progestor.processo AS p
		 where p.contrato = ?
		 GROUP BY p.processo_id
		 order by p.processo_id desc;";


		try {
			$results = $this->db->prepare($sql);
			$results->execute([$id_contrato]);
			if ($results->rowCount() == 0) {
				return [
					'success' => (boolval(false)),
					'message' => 'dados para o contrato não localizado'
				];
			}
			return $results->fetchAll(PDO::FETCH_ASSOC);
		} catch (\Exception $e) {
			$this->manipulador->manipuladorDeErros(
				$e->getCode(),
				$e->getMessage(),
				$e->getFile(),
				$e->getLine()
			);
			return [
				'success' => false,
				'error' => $e->getMessage()
			];
		}
	}

	public function get_fingers_downloads_jobs($id_contrato)
	{

		$sql =  "";
		$sql = "SELECT d.id_info_downloads as processo_id_downloads, TO_CHAR(d.data_download, 'DD/MM/YYYY HH24:MI') as data_download ,d.finger_download, d.ids_baixados
		 FROM progestor.info_downloads AS d
		 where d.ctr_cliente = ?
		 GROUP BY d.id_info_downloads
		 order by d.id_info_downloads desc;";


		try {
			$results = $this->db->prepare($sql);
			$results->execute([$id_contrato]);
			if ($results->rowCount() == 0) {
				return [
					'success' => (boolval(false)),
					'message' => 'dados para o contrato não localizado'
				];
			}
			return $results->fetchAll(PDO::FETCH_ASSOC);
		} catch (\Exception $e) {
			$this->manipulador->manipuladorDeErros(
				$e->getCode(),
				$e->getMessage(),
				$e->getFile(),
				$e->getLine()
			);
			return [
				'success' => false,
				'error' => $e->getMessage()
			];
		}
	}


	public function push_dados_parar_processo()
	{

		$sql =  "";
		$sql = "SELECT p.processo_id,t.transacao_id,t.status,t.sucesso,
		t.campo_aquisicao from progestor.processo as p
		INNER JOIN progestor.transacao as t on (t.id_processo = p.processo_id)
		WHERE p.processo_id = 184 and p.mensagem_alerta = 'parar_processo'
		and t.resposta is null;";

		try {
			$results = $this->db->prepare($sql);
			$results->execute();
			if ($results->rowCount() == 0) {
				return [
					'success' => (boolval(false)),
					'message' => 'dados para o contrato não localizado'
				];
			}
			return $results->fetchAll(PDO::FETCH_ASSOC);
		} catch (\Exception $e) {
			$this->manipulador->manipuladorDeErros(
				$e->getCode(),
				$e->getMessage(),
				$e->getFile(),
				$e->getLine()
			);
			return [
				'success' => false,
				'error' => $e->getMessage()
			];
		}
	}

	public function alter_die_msg_process($id_process)
	{
		$erros = [];

		try {

			$sql = "UPDATE progestor.processo SET mensagem_alerta = ? WHERE processo_id = ?";
			$dados =  ['parar_processo', $id_process];
			$result = $this->db->prepare($sql);
			$result->execute($dados);
		} catch (\Exception $e) {
			$this->manipulador->manipuladorDeErros(
				$e->getCode(),
				$e->getMessage(),
				$e->getFile(),
				$e->getLine()
			);
			$erros[] = [
				'msg' =>  $e->getMessage()
			];
		}


		return [
			'erros' => empty($erros) ? [] : $erros,
			'status' => empty($erros) ? 2 : 0,

		];
	}

	public function get_info_status_process()
	{
		$erros = [];

		try {

			$sql = "";

			$sql = "SELECT count(t.id_processo)  as qta,
		  p.processo_id,
		  p.codcns,
		  p.contrato,
		 --COALESCE(t.qtd_registros, 0) AS qtd_registros,
		  COALESCE(SUM(CASE WHEN t.status in (12) THEN 1 ELSE 0 END), 0) AS qta_processar_status,
		  COALESCE(SUM(CASE WHEN t.status in (3) THEN 1 ELSE 0 END), 0) AS qta_processado,
		  COALESCE(SUM(CASE WHEN t.status not in (12,3,6) THEN 1 ELSE 0 END), 0) AS qta_processar,
		  COALESCE(SUM(CASE WHEN t.status in (6) THEN 1 ELSE 0 END), 0) AS qta_paralizado
		  FROM progestor.transacao as t
		  INNER JOIN progestor.processo as p on
		  (p.processo_id = t.id_processo)
          --WHERE t.id_processo = 181 and campo_aquisicao is  not null and mensagem_alerta = 'parar_processo'
          WHERE t.campo_aquisicao is not null and p.mensagem_alerta = 'parar_processo' and  (p.finalizado = false or p.finalizado = true) AND
		  p.error = false and p.pause = false GROUP BY p.processo_id, p.codcns, p.contrato;";


			$result = $this->db->prepare($sql);
			$result->execute();


			if ($result->rowCount() > 0) {

				return $result->fetchAll(PDO::FETCH_ASSOC);
			} else {
				return false;
			}
		} catch (\Exception $e) {
			$this->manipulador->manipuladorDeErros(
				$e->getCode(),
				$e->getMessage(),
				$e->getFile(),
				$e->getLine()
			);
			$erros[] = [
				'msg' =>  $e->getMessage()
			];
		}


		return [
			'erros' => empty($erros) ? [] : $erros,
			'status' => empty($erros) ? 2 : 0,

		];
	}

	public function process_breackss($id_process, $id_status)
	{

		$erros = [];

		try {
			//recebe 6 
			$sql = "UPDATE progestor.transacao SET status = ? WHERE id_processo = ? and status not in (12,3);";
			// $sql = "SELET progestor.transacao SET status = ? WHERE id_processo = ? ";

			$dados = array();
			$dados[] = $id_status;
			$dados[] = $id_process;

			$result = $this->db->prepare($sql);

			$result->execute($dados);
		} catch (\Exception $e) {
			$this->manipulador->manipuladorDeErros(
				$e->getCode(),
				$e->getMessage(),
				$e->getFile(),
				$e->getLine()
			);

			//   $conexaoBd->BD_COM->rollback();

			$erros[] = [
				'msg' =>  $e->getMessage()
			];
		}

		return [
			'erros' => empty($erros) ? [] : $erros,
			'status' => empty($erros) ? 2 : 0,
			'qta_afetadas' => $result->rowCount()

		];
	}

	public  function list_transation($id)
	{
		$erros = [];

		try {

			$sql = "";
			$sql = "SELECT t.transacao_id, p.processo_id FROM progestor.transacao as t
		  INNER JOIN progestor.processo as p on
		  (p.processo_id = t.id_processo)
          WHERE t.id_processo =  ?  and t.status not in (3,12)
		  GROUP BY t.transacao_id , p.processo_id;";


			$result = $this->db->prepare($sql);
			$result->execute([$id]);


			if ($result->rowCount() > 0) {

				return $result->fetchAll(PDO::FETCH_ASSOC);
			} else {
				return false;
			}
		} catch (\Exception $e) {
			$this->manipulador->manipuladorDeErros(
				$e->getCode(),
				$e->getMessage(),
				$e->getFile(),
				$e->getLine()
			);
			$erros[] = [
				'msg' =>  $e->getMessage()
			];
		}


		return [
			'erros' => empty($erros) ? [] : $erros,
			'status' => empty($erros) ? 2 : 0,

		];
	}

	public function alter_value_process_status($id_process, $value)
	{
		$erros = [];

		try {
			//recebe 6 
			$sql = "UPDATE progestor.processo SET valor_total = ?, mensagem_alerta = ? WHERE processo_id = ? ";

			$dados =  [$value, 1, $id_process];
			$result = $this->db->prepare($sql);
			$result->execute($dados);
		} catch (\Exception $e) {
			$this->manipulador->manipuladorDeErros(
				$e->getCode(),
				$e->getMessage(),
				$e->getFile(),
				$e->getLine()
			);


			$erros[] = [
				'msg' =>  $e->getMessage()
			];
		}


		return [
			'erros' => empty($erros) ? [] : $erros,
			'status' => empty($erros) ? 2 : 0,

		];
	}

	public function insert_plugin($sql, $valores_para_bind)
	{
		try {


			$result = $this->db->prepare($sql);
			$result->execute($valores_para_bind);

			return [
				'success' => true,
				'message' => 'Plugin inserido com sucesso'
			];
		} catch (\Exception $e) {
			$this->manipulador->manipuladorDeErros(
				$e->getCode(),
				$e->getMessage(),
				$e->getFile(),
				$e->getLine()
			);
			echo "ERRO NO INSERT: " . $e->getMessage() . "\n";
			return [
				'success' => false,
				'error' => $e->getMessage()
			];
		}
	}
	public function get_lista_plugin()
	{

		try {

			$sql = "";

			$sql = "SELECT
            DISTINCT cpovar, cpodsc, regcod
            FROM
            rdecns inner join 
            rdecnsreg on rdecnsid = rdecnsregrdecns inner join 
            regcpo on rdecnsregreg = regcporeg  inner join
            cpo on cpoid = regcpocpo
            inner join
            reg on regid = rdecnsregreg 
            WHERE rdecnsid not in ( SELECT numero_plugin from progestor.plugin_campos_input) order by regcod  LIMIT 5;";

			$result = $this->db->prepare($sql);
			$result->execute();

			if ($result->rowCount() > 0) {

				return $result->fetchAll(PDO::FETCH_ASSOC);
			} else {

				return false;
			}
		} catch (\Exception $e) {
			$this->manipulador->manipuladorDeErros(
				$e->getCode(),
				$e->getMessage(),
				$e->getFile(),
				$e->getLine()
			);

			$erros[] = [
				'msg' =>  $e->getMessage()
			];
		}


		return [
			'erros' => empty($erros) ? [] : $erros,
			'status' => empty($erros) ? 2 : 0

		];
	}
	public function get_lista_plugin_with()
	{

		try {

			$sql = "";

			$sql = "WITH consulta_base AS (
					SELECT DISTINCT regcod, cpodsc, cpovar
					FROM rdecns 
					INNER JOIN rdecnsreg ON rdecnsid = rdecnsregrdecns 
					INNER JOIN regcpo ON rdecnsregreg = regcporeg  
					INNER JOIN cpo    ON cpoid = regcpocpo
					INNER JOIN reg    ON (regid = rdecnsregreg AND regcod NOT IN (SELECT numero_plugin FROM progestor.plugin_campos_input))
									)
					SELECT * 
					FROM consulta_base
					WHERE regcod IN (
						SELECT regcod 
						FROM consulta_base 
						--WHERE regcod = '5603'
						GROUP BY regcod 
						HAVING COUNT(*) = 1
					) 
					ORDER BY regcod, cpodsc;";

			$result = $this->db->prepare($sql);
			$result->execute();

			if ($result->rowCount() > 0) {

				return $result->fetchAll(PDO::FETCH_ASSOC);
			} else {

				return false;
			}
		} catch (\Exception $e) {
			$this->manipulador->manipuladorDeErros(
				$e->getCode(),
				$e->getMessage(),
				$e->getFile(),
				$e->getLine()
			);

			$erros[] = [
				'msg' =>  $e->getMessage()
			];
		}


		return [
			'erros' => empty($erros) ? [] : $erros,
			'status' => empty($erros) ? 2 : 0

		];
	}
	public function get_lista_plugin_description($cod)
	{

		try {

			$sql = "";

			$sql = "SELECT
            DISTINCT cpovar, cpodsc, regcod
            FROM
            rdecns inner join 
            rdecnsreg on rdecnsid = rdecnsregrdecns inner join 
            regcpo on rdecnsregreg = regcporeg  inner join
            cpo on cpoid = regcpocpo
            inner join
            reg on regid = rdecnsregreg 
            WHERE rdecnsid = ?";

			$result = $this->db->prepare($sql);
			$result->execute([$cod]);

			if ($result->rowCount() > 0) {

				while ($row = $result->fetch((PDO::FETCH_ASSOC))) {

					echo "<pre>";
					echo "LINHA GERADA\n";

					print_r($row);
				}
				// return $result->fetchAll(PDO::FETCH_ASSOC);
			} else {

				return false;
			}
		} catch (\Exception $e) {
			$this->manipulador->manipuladorDeErros(
				$e->getCode(),
				$e->getMessage(),
				$e->getFile(),
				$e->getLine()
			);

			$erros[] = [
				'msg' =>  $e->getMessage()
			];
		}


		return [
			'erros' => empty($erros) ? [] : $erros,
			'status' => empty($erros) ? 2 : 0

		];
	}
	public function get_plugin_nome_puglin($cod)

	{
		echo "<pre>";
		print_R("MEU CÓDIGO PARA VER SE EXISTE O PLUGIN \n" . $cod);

		try {

			$sql = "";

			$sql = "SELECT EXISTS (SELECT 1 FROM progestor.plugin_campos_input WHERE numero_plugin = ?);";

			$result = $this->db->prepare($sql);
			$result->execute([$cod]);

			// while ($results =  $result->fetchAll(PDO::FETCH_ASSOC)) {


			// return $results['exists'] ? "Plugin já existe" : "Plugin não existe";

			// }
			return $result->fetch(PDO::FETCH_ASSOC)['exists'] ? true : "null";
		} catch (\Exception $e) {
			$this->manipulador->manipuladorDeErros(
				$e->getCode(),
				$e->getMessage(),
				$e->getFile(),
				$e->getLine()
			);

			$erros[] = [
				'msg' =>  $e->getMessage()
			];
		}


		return [
			'erros' => empty($erros) ? [] : $erros,
			'status' => empty($erros) ? 2 : 0

		];
	}

	public function get_lista_plugin_ativoss()
	{

		try {

			$resultado = [];
			$sql = "";

			$sql = "SELECT 
					DISTINCT rdecns.rdecnsid AS codigo_consulta,
					rdecns.rdecnsdsc AS nome_consulta,
					rdelja.rdeljaid AS loja_contrato,
					rdelja.rdeljactr AS contratos
				FROM rdecns
				JOIN rdelja ON rdecns.rdecnsrde = rdelja.rdeljarde
				JOIN rdeljacns ON rdeljacns.rdeljacnsrdelja = rdelja.rdeljaid 
				AND rdeljacns.rdeljacnsrdecns = rdecns.rdecnsid
				JOIN ctr ON rdelja.rdeljactr = ctr.ctrid
				JOIN cli ON ctr.ctrcli = cli.cliid
				WHERE cli.cliatv = 'S' LIMIT 1 ;";

			$result = $this->db->prepare($sql);
			$result->execute();

			if ($result->rowCount() > 0) {

				while ($row = $result->fetch(PDO::FETCH_ASSOC)) {

					echo "<pre>";
					echo "MEUS CÓDIGOS AQUI \n";
					print_r($row['codigo_consulta']);
					$row['exists'] = self::get_plugin_nome_puglin($row['codigo_consulta']);

					if ($row['exists'] == "null") {

						$row['colunas'] = self::get_lista_plugin_description($row['codigo_consulta']);
					}

					$resultado[] = $row;
				}

				return $resultado;
			} else {

				return false;
			}
		} catch (\Exception $e) {
			$this->manipulador->manipuladorDeErros(
				$e->getCode(),
				$e->getMessage(),
				$e->getFile(),
				$e->getLine()
			);

			$erros[] = [
				'msg' =>  $e->getMessage()
			];
		}


		return [
			'erros' => empty($erros) ? [] : $erros,
			'status' => empty($erros) ? 2 : 0

		];
	}
}
