<?php

class Proposta_Model_DbTable_TbDocumentosPreProjeto  extends MinC_Db_Table_Abstract {
     protected $_schema  = "sac";
     protected $_name = 'tbDocumentosPreProjeto';
     protected $_primary = 'idDocumentosPreprojetos';


    public function buscarDocumentos($where=array(), $order=array(), $tamanho=-1, $inicio=-1) {
        $slct = $this->select();
        $slct->setIntegrityCheck(false);
        $slct->from(
            array("a"=>$this->_name),
            array("CodigoDocumento", new Zend_Db_Expr('(2) as tpdoc'), 'idProjeto as codigo',
                'Data', 'imDocumento', 'NoArquivo', 'TaArquivo', 'idDocumentosPreprojetos'),
            $this->_schema
        );
        $slct->joinInner(
            array("b"=> "DocumentosExigidos"), "a.CodigoDocumento = b.Codigo",
            array("Descricao"), $this->getSchema('sac')
        );

        foreach ($where as $coluna => $valor) {
            $slct->where($coluna, $valor);
        }

        $slct->order($order);

        if ($tamanho > -1) {
            $tmpInicio = 0;
            if ($inicio > -1) {
                $tmpInicio = $inicio;
            }
            $slct->limit($tamanho, $tmpInicio);
        }

        $result = $this->fetchAll($slct);
        return $result ? $result->toArray() : array();
    }

    public function abrir($id) {
        $slct = $this->select();
        $slct->setIntegrityCheck(false);

        $slct->from(
                array("a"=> $this->_name),
                array("noarquivo", "imdocumento"),
            $this->_schema
        );

        $slct->where("iddocumentospreprojetos = ?", $id);

        //$this->fetchAll("SET TEXTSIZE 10485760;");
        $db = $this->getDefaultAdapter();
        if ($this->getAdapter() instanceof Zend_Db_Adapter_Pdo_Mssql) {
            $db->fetchAll("SET TEXTSIZE 10485760;");
        }

        return $this->fetchAll($slct);
    }
}