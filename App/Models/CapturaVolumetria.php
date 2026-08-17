<?php


namespace App\Models;

use PDO;
use Core\Model;
use Core\Logs;
use PDOException;
use Core\Functions;
use Core\AppManipularError;


class CapturaVolumetria extends Model
{

    public  function captura($rede, $codConsulta)
    {


        // $rede = 5290; //dado para teste enviado por Rodrigo
        // $codConsulta = 280968; //dado para teste enviado por Rodrigo

        $sql = ""; //rede primeiro e cod consulta
        $sql = "SELECT * FROM progestor.fnc_progestor_cns_faixa_vlr (?, ?);";

        try {

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$rede, $codConsulta]);

            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($stmt->rowCount() == 0) {

                return (bool)false;
            }

            return $row;
        } catch (\Exception $e) {
            return false;
        }
    }
}
