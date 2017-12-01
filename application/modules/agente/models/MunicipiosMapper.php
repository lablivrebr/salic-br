<?php

class Agente_Model_MunicipiosMapper extends MinC_Db_Mapper
{
    public function __construct()
    {
        parent::setDbTable('Municipios');
    }

    /**
     * @todo retirar futuramente, metodo feito para utilizar no xml.
     */
    public function fetchPairs($key, $value, array $where = array(), $order = '')
    {
        $result = parent::fetchPairs($key, $value, $where, $order);
        $resultNew = array();
        foreach ($result as $key => $value) {
            $stdClass = new stdClass();
            $stdClass->id = $key;
            $stdClass->descricao = $value;
            $resultNew[] = $stdClass;
        }

        return $resultNew;
    }
}