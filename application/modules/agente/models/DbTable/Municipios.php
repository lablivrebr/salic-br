<?php

class Agente_Model_DbTable_Municipios extends MinC_Db_Table_Abstract
{
    protected $_name = 'Municipios';
    protected $_schema = 'agentes';
    protected $_primary = 'idMunicipioIBGE';


    public function buscar($where = array(), $order = array(), $tamanho = -1, $inicio = -1)
    {
        $select = $this->select();
        $select->setIntegrityCheck(false);
        $select->from(
            array($this->_name),
            array('idMunicipioIBGE as id',
            'Descricao'),
            $this->_schema
        );

        if (!empty($idCidade))
        {
            $select->where('idMunicipioIBGE = ?' , $idCidade);
        }

        $select->order('Descricao');

        try
        {
            $db = Zend_Db_Table::getDefaultAdapter();
            $db->setFetchMode(Zend_DB::FETCH_OBJ);
        }
        catch (Zend_Exception_Db $e)
        {
            $this->view->message = "Erro ao buscar Cidades: " . $e->getMessage();
        }

        return $db->fetchAll($select);
    }

    public function listar($idUF, $idCidade = null)
    {
        $select = $this->select();
        $select->setIntegrityCheck(false);
        $select->from(
            array($this->_name),
            array('idMunicipioIBGE as id',
                'Descricao'),
            $this->_schema
        );

        $select->where('idufibge = ?' , $idUF);

        if (!empty($idCidade))
        {
            $select->where('idMunicipioIBGE = ?' , $idCidade);
        }

        $select->order('Descricao');

        try
        {
            $db = Zend_Db_Table::getDefaultAdapter();
            $db->setFetchMode(Zend_DB::FETCH_OBJ);
        }
        catch (Zend_Exception_Db $e)
        {
            $this->view->message = "Erro ao buscar Cidades: " . $e->getMessage();
        }

        return $db->fetchAll($select);
    }
}
