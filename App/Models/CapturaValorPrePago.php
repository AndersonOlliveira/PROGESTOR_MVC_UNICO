<?php

namespace App\Models;

use PDO;
use Core\Model;
use Core\Logs;
use PDOException;
use Core\Functions;
use Core\AppManipularError;
use App\Utilis\Model_ConsultaPrePago_Action_pretpsaldo;



class CapturaValorPrePago extends Model
{


    protected $model_consulta;


    public function __construct()
    {
        // parent::__construct();
        // require_once 'Model_ConsultaPrePago_Action_pretpsaldo.php';
        $this->model_consulta = new Model_ConsultaPrePago_Action_pretpsaldo();
    }



    public function push_value_prePago($contrato, $consulta)
    {


        $valor = 0;
        $retorno = [];
        $sql = <<<CUSTOM
    SELECT perfilcobtipo 
    FROM perfilcob, ctr 
    WHERE ctrid = ? 
    AND ctrperfilcobid = perfilcobid 
    AND perfilcobid IN (1,4);
CUSTOM;

        $result =  $this->db->prepare($sql);
        $result->execute([$contrato]);

        if ($result->rowCount() > 0) {

            $sql = <<<CUSTOM
        SELECT ROUND(MAX(rdefxacnsvlr),2) as rdefxacnsvlr
        FROM rde, rdecns, rdefxacns
        WHERE rdecnsid = ?
        AND rdefxacnsatv = true
        AND rdeid = rdecnsrde
        AND rdefxacnsrde = rdeid
        AND rdefxacnsrdecns = rdecnsid
CUSTOM;

            $result_valor_cod = $this->db->prepare($sql);
            $result_valor_cod->execute([$consulta]);

            $row = $result_valor_cod->fetch(PDO::FETCH_ASSOC);

            if ($row && isset($row["rdefxacnsvlr"])) {

                $retorno[] = [
                    'perfilcobtipo' => true,
                    'valor' => trim($row["rdefxacnsvlr"])
                ];
            } else {
                $retorno[] = [
                    'perfilcobtipo' => false,
                    'valor' => $valor
                ];
            }
        } else {
            $retorno[] = [
                'perfilcobtipo' => false,
                'valor' => $valor
            ];
        }

        return $retorno;
    }
    public function valor($contrato)
    {
        $caminho = 'https://site2.proscore.com.br/class/Model/ConsultaPrePago/Action/pretpsaldo.php';


        // if (!file_exists($caminho)) {
        //     die("Arquivo não encontrado: " . $caminho);
        // }

        // require_once $caminho;

        $resultado = (object)[
            'valor_unitario' => 0,
            'valor_grupo'    => 0,
            'valor_global'   => 0,
            'ids'            => ''
        ];


        $sql = <<<CUSTOM
SELECT saldovalor, saldoid, tpsaldoid FROM fnc_retorno_saldo_tipo(?) ORDER BY saldoid ASC;
CUSTOM;

        $retorno = $this->db->prepare($sql);
        $retorno->execute([$contrato]);

        if ($retorno->rowCount() > 0) {
            $ids = [];

            foreach ($retorno as $valor) {

                $tipo = trim($valor['tpsaldoid']);
                $saldo = (float) $valor['saldovalor'];

                if ($tipo == $this->model_consulta::tpsaldoid_unitario) {
                    $resultado->valor_unitario += $saldo;
                } elseif ($tipo == $this->model_consulta::tpsaldoid_grupo) {
                    $resultado->valor_grupo += $saldo;
                } else {
                    $resultado->valor_global += $saldo;
                }

                $ids[] = trim($valor['saldoid']);
            }

            $resultado->ids = implode(',', $ids);
        } else {

            return false;
        }

        return $resultado;
    }

    public function atualizarSaldoPrePago($ctr, $cnsid, $valor_consulta, $codConsulta)
    {
        $saldoid = null;



        try {

            //verificar se tem saldo contrato ou rede e debitar
            $sql = "SELECT saldoid FROM fnc_retorno_saldo_tipo(?) WHERE tpsaldoid = 1 OR tpsaldoid = 2 ORDER BY tpsaldoid DESC;";
            $dados = array();
            $dados[] = $ctr;
            $result = $this->db->prepare($sql);
            $result->execute($dados);
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {

                $sql = "UPDATE pre_saldo set saldovalor = (saldovalor - ?), saldodatatu = now() where saldoid = ? and (saldovalor >= ?) returning *;";
                $dados = array();
                $dados[] = $valor_consulta;
                $dados[] = $row["saldoid"];
                $dados[] = $valor_consulta;
                $result_update = $this->db->prepare($sql);
                $result_update->execute($dados);
                if ($row_update = $result_update->fetch(PDO::FETCH_ASSOC)) {

                    $sqlFxa = "SELECT 
                           rdefxacnsid
                        FROM
                           rde,
                           rdecns,  
                           rdefxacns
                        WHERE
                           rdecnsid = ? and
                           rdefxacnsatv = true and
                           rdeid = rdecnsrde and
                           rdefxacnsrde = rdeid and
                           rdefxacnsrdecns = rdecnsid
                        GROUP BY rdefxacnsid;";
                    $dados = array();
                    $dados[] = $codConsulta;
                    $resultFxa = $this->db->prepare($sqlFxa);
                    $resultFxa->execute($dados);
                    if ($rowFxa = $resultFxa->fetch(PDO::FETCH_ASSOC)) {

                        $faixa = $rowFxa['rdefxacnsid'];

                        //$sql = "INSERT INTO pre_saida(saidacnsid, saidasaldoid, saidactrid, saidardefxacns, saidavalor, saidacad) VALUES (?,?,?,?,?,NOW());"; // ERRO saidacnsid chave unica
                        $sql = "UPDATE pre_saida SET saidavalor = saidavalor + ? WHERE saidacnsid = ?;";
                        $dados = array();
                        $dados[] = $valor_consulta;
                        $dados[] = $cnsid;

                        $result_insert = $this->db->prepare($sql);
                        $result_insert->execute($dados);

                        $saldoid = $row["saldoid"];
                        break;
                    }
                }
            }
        } catch (\Exception  $e) {


            return $e->getMessage();
        }


        return $saldoid;
    }


    //funcao para pagar a proixma seguencia para inserir dentro no update da atualizacao do valor 

    public function seguence()
    {
        try {
            $sql = "SELECT nextval('cns_cnsid_seq') FROM cns_cnsid_seq";
            $result = $this->db->prepare($sql);
            $result->execute();


            $row = $result->fetch(PDO::FETCH_ASSOC);

            return $row['nextval'];
        } catch (\Exception $e) {

            return $e->getMessage();
        }
    }
}
