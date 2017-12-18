<?php

class Proposta_Model_DbTable_TbDocumentosPreProjeto  extends MinC_Db_Table_Abstract {
     protected $_schema  = "sac";
     protected $_name = 'tbDocumentosPreProjeto';
     protected $_primary = 'idDocumentosPreprojetos';


    public function buscarDocumentos($where=array(), $order=array(), $tamanho=-1, $inicio=-1) {
        $query = $this->select();
        $query->setIntegrityCheck(false);
        $query->from(
            array("a"=>$this->_name),
            array("CodigoDocumento", new Zend_Db_Expr('(2) as tpdoc'), 'idProjeto as codigo',
                'Data', 'imDocumento', 'NoArquivo', 'TaArquivo', 'idDocumentosPreprojetos'),
            $this->_schema
        );
        $query->joinInner(
            array("b"=> "DocumentosExigidos"), "a.CodigoDocumento = b.Codigo",
            array("Descricao"), $this->getSchema('sac')
        );

        foreach ($where as $coluna => $valor) {
            $query->where($coluna, $valor);
        }

        $query->order($order);

        if ($tamanho > -1) {
            $tmpInicio = 0;
            if ($inicio > -1) {
                $tmpInicio = $inicio;
            }
            $query->limit($tamanho, $tmpInicio);
        }

        $result = $this->fetchAll($query);
        return $result ? $result->toArray() : array();
    }

    public function abrir($id) {
        $slct = $this->select();
        $slct->setIntegrityCheck(false);

        $slct->from(
                array("a"=> $this->_name),
                array("NoArquivo", "imDocumento"),
            $this->_schema
        );

        $slct->where("idDocumentosPreprojetos = ?", $id);

        $db = $this->getDefaultAdapter();
        if ($this->getAdapter() instanceof Zend_Db_Adapter_Pdo_Mssql) {
            $db->fetchAll("SET TEXTSIZE 10485760;");
        }

        return $this->fetchAll($slct);
    }
}