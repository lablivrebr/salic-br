<?php

class Proposta_Model_DbTable_PlanilhaUnidade extends MinC_Db_Table_Abstract
{
    protected $_schema  = "sac";
    protected $_name   = "PlanilhaUnidade";
    protected $_primary = "idUnidade";

    public function buscarUnidade() {

        $select = $this->select();
        $select->setIntegrityCheck(false);
        $select->from(
           array('u'=> $this->_name),
            array('u.idUnidade', 'u.Sigla', 'u.Descricao'),
            $this->_schema
        );
        $select->order('3');

        $db= Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_DB::FETCH_OBJ);


        return $db->fetchAll($select);
    }
}