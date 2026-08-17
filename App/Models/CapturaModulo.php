<?php

namespace App\Models;

use PDO;
use Core\Model;
use Core\Logs;
use PDOException;
use Core\Functions;
use Core\AppManipularError;


class CapturaModulo extends Model
{


    public function resultModulo($idConsultation)
    {

        // require_once 'ConexaoBd.php';
        // $conexaoBd = conexaoBd::getInstance();

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
        } catch (\Exception $e) {

            return false;
        }
    }

    public  function CaputuraValor($idConsultation)
    {

        // require_once 'ConexaoBd.php';
        // $conexaoBd = conexaoBd::getInstance();
        $sql = "";
        // $sql = "SELECT rdecnsregrdecns as consulta, array_to_string(array_agg(regcod),',') as plugins, rdecnsreg_modulo as modulo, rdecnsreg_modulo_valor as modulo_valor
        $sql = "SELECT rdecnsreg_modulo_valor as modulo_valor
        FROM rdecnsreg AS a
        INNER JOIN reg AS b ON (a.rdecnsregreg = b.regid)
        WHERE rdecnsregrdecns = ?
        GROUP BY rdecnsregrdecns, rdecnsreg_modulo, rdecnsreg_modulo_valor;";

        try {
            $dadosTransacao = [$idConsultation];
            $results = $this->db->prepare($sql);
            $results->execute($dadosTransacao);
        } catch (\Exception $e) {

            return $e->getMessage();
        }


        $retorno = [];

        if ($results->rowCount() > 0) {

            while ($row = $results->fetch(PDO::FETCH_ASSOC)) {

                $retorno[] = $row;
            }
        }
        $somatoria = 0;
        foreach ($retorno as $valores) {
            $somatoria += $valores['modulo_valor'];
        }

        $valor_geral = number_format($somatoria, 2, '.', '');

        return $valor_geral;
    }
}
