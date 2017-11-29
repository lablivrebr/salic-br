<?php

class Proposta_Model_TbDeslocamentoMapper extends MinC_Db_Mapper
{
    public function __construct()
    {
        parent::setDbTable('Proposta_Model_DbTable_TbDeslocamento');
    }

    public function save($model)
    {
        return parent::save($model);
    }
}
