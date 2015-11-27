<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Reuniao
 *
 * @author augusto
 */
class Reuniao extends GenericModel{

    protected $_banco = 'SAC';
    protected $_name  = 'dbo.tbReuniao';

    /**
     * Retorna a Ultima Reiniao Aberta
     * @return null|Zend_Db_Table_Row_Abstract
     */
    public function buscarReuniaoAberta(){
        $select = $this->select();
        $select->from(
                        $this,
                        array
                            (
                                "idNrReuniao",
                                "NrReuniao",
                                "stPlenaria",
                                "DtInicio",
                                "DtFinal"
                            )
                      );
        $select->where("stEstado = ?", 0);
        return $this->fetchRow($select);
    }

    /**
     * Retorna uma Reuniao filtrando pelo NrReuniao
     * @param $nrReuniao
     * @return null|Zend_Db_Table_Row_Abstract
     */
    public function buscarReuniaoPorNumero($nrReuniao){
        $select = $this->select();
        $select->from(
            $this,
            array
            (
                "idNrReuniao",
                "NrReuniao",
                "stPlenaria",
                "DtInicio",
                "DtFinal"
            )
        );
        $select->where("NrReuniao = ?", $nrReuniao);
        return $this->fetchRow($select);
    }

    public function buscarTodasReunioes(){
        $select = $this->select();
        $select->from(
            $this,
            array
            (
                "idNrReuniao",
                "NrReuniao",
                "stPlenaria",
                "DtInicio",
                "DtFinal"
            )
        );

        return $this->fetchAll($select);
    }

    public function buscarReuniaoPorId($idNrReuniao){
        $select = $this->select();
        $select->from(
            $this,
            array
            (
                "idNrReuniao",
                "NrReuniao",
                "stPlenaria",
                "DtInicio",
                "DtFinal"
            )
        );
        $select->where("idNrReuniao = ?", $idNrReuniao);
        return $this->fetchRow($select);
    }
    
}

?>