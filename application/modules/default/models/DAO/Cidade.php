<?php

class Cidade extends MinC_Db_Table_Abstract
{
    protected $_name = 'Municipios';
    protected $_schema = 'agentes';
    protected $_primary = 'idMunicipioIBGE';

    public function buscar($idUF)
    {
        $query = $this->select();
        $query->from(
            $this->_name,
            [
                'id' => 'idMunicipioIBGE',
                'descricao' => 'Descricao'
            ],
            $this->getSchema($this->_schema)
        );
        $query->where('idUFIBGE = ?', $idUF);
        $query->order('Descricao');

        try
        {
            $db = Zend_Db_Table::getDefaultAdapter();
            $db->setFetchMode(Zend_DB::FETCH_OBJ);
            return $db->fetchAll($query);
        }
        catch (Zend_Exception_Db $e)
        {
            $this->view->message = "Erro ao buscar Cidades: " . $e->getMessage();
        }

    }
}
