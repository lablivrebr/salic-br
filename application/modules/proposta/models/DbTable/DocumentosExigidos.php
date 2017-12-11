<?php

class Proposta_Model_DbTable_DocumentosExigidos extends MinC_Db_Table_Abstract
{
    protected $_name = 'DocumentosExigidos';
    protected $_schema = 'sac';
    protected $_primary = 'Codigo';

    /**
     * Realizando a busca na view: vwdocumentosexigidosapresentacaoproposta
     * Futuramente deletar este metodo junto com a view, pois nao tem nessecidade desta view por ser muito simples.
     *
     * @return array
     */
    public function buscarDocumentoOpcao($idOpcao)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from($this->getName('vwDocumentosExigidosApresentacaoProposta'),
                array('Codigo', 'Descricao'),
                $this->_schema)
            ->where('Opcao = ?', $idOpcao)
            ->order('Descricao');
        $result = $this->fetchAll($select);
        return ($result) ? $result->toArray() : array();
    }

    public function buscarDocumentoPendente($idPreProjeto)
    {
        $selectProponente = $this->select()
            ->setIntegrityCheck(false)
            ->from(
                array('dp' => 'DocumentosProponente'),
                array('Contador', 'CodigoDocumento'),
                $this->_schema
            )
            ->joinInner(
                array('d' => 'DocumentosExigidos'),
                'dp.CodigoDocumento = d.Codigo',
                array('Opcao'),
                $this->_schema
            )
            ->joinInner(
                array('p' => 'PreProjeto'),
                'dp.IdProjeto = p.idPreProjeto',
                null,
                $this->_schema
            )
            ->joinInner(
                array('m' => 'tbMovimentacao'),
                'm.idProjeto = p.idPreProjeto',
                array('idProjeto'),
                $this->_schema
            )
            ->where('Movimentacao in (?)', array(97, 95))
            ->where('m.stEstado = ?', 'f')
            ->where('m.idProjeto = ?', (int)$idPreProjeto);

        $selectProjeto = $this->select()
            ->setIntegrityCheck(false)
            ->from(
                array('dpr' => 'DocumentosProjeto'),
                array('Contador', 'CodigoDocumento'),
                $this->_schema
            )
            ->joinInner(
                array('d' => 'DocumentosExigidos'),
                'dpr.CodigoDocumento = d.Codigo',
                array('Opcao'),
                $this->_schema
            )
            ->joinInner(
                array('p' => 'PreProjeto'),
                'dpr.idProjeto = p.idPreProjeto',
                null,
                $this->_schema
            )
            ->joinInner(
                array('m' => 'tbMovimentacao'),
                'm.idProjeto = p.idPreProjeto',
                array('idProjeto'),
                $this->_schema
            )
            ->where('Movimentacao in (?)', array(97, 95))
            ->where('m.stEstado = ?', 'f')
            ->where('m.idProjeto = ?', (int)$idPreProjeto);

        $select = $this->select()
            ->union(array($selectProponente, $selectProjeto));

        $resultado = $this->fetchAll($select);
        return $resultado;
    }
}
