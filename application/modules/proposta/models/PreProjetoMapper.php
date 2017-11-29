<?php

class Proposta_Model_PreProjetoMapper extends MinC_Db_Mapper
{
    public function __construct()
    {
        parent::setDbTable('Proposta_Model_DbTable_PreProjeto');
    }

    public function save($model)
    {
        return parent::save($model);
    }
}
