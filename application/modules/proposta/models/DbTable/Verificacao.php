<?php

class Proposta_Model_DbTable_Verificacao extends MinC_Db_Table_Abstract
{

    protected $_schema = 'sac';
    protected $_name = 'Verificacao';
    protected $_primary = 'idVerificacao';

    public function buscarFonteRecurso()
    {

        $select = $this->select();
        $select->setIntegrityCheck(false);
        $select->from(
            array('v' => $this->_name),
            array(
                'idVerificacao',
                $this->getExpressionTrim("v.Descricao", "VerificacaoDescricao"),
            ),
            $this->_schema
        );
        $select->joinInner(array('tipo' => 'Tipo'),
            'v.idTipo = tipo.idTipo',
            null,
            $this->_schema
        );
        $select->where('tipo.idTipo = ?', '5');
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_DB::FETCH_OBJ);

        return $db->fetchAll($select);
    }
}
