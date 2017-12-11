<?php

class Proposta_Model_TbDocumentosPreProjeto extends MinC_Db_Model
{
    protected $_idDocumentosPreprojetos;
    protected $_CodigoDocumento;
    protected $_idProjeto;
    protected $_idPRONAC;
    protected $_Data;
    protected $_imDocumento;
    protected $_NoArquivo;
    protected $_TaArquivo;
    protected $_biDocumento;
    protected $_dsDocumento;

    /**
     * @return mixed
     */
    public function getIdDocumentosPreprojetos()
    {
        return $this->_idDocumentosPreprojetos;
    }

    /**
     * @param mixed $idDocumentosPreprojetos
     * @return Proposta_Model_TbDocumentosPreProjeto
     */
    public function setIdDocumentosPreprojetos($idDocumentosPreprojetos)
    {
        $this->_idDocumentosPreprojetos = $idDocumentosPreprojetos;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCodigoDocumento()
    {
        return $this->_CodigoDocumento;
    }

    /**
     * @param mixed $CodigoDocumento
     * @return Proposta_Model_TbDocumentosPreProjeto
     */
    public function setCodigoDocumento($CodigoDocumento)
    {
        $this->_CodigoDocumento = $CodigoDocumento;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIdProjeto()
    {
        return $this->_idProjeto;
    }

    /**
     * @param mixed $idProjeto
     * @return Proposta_Model_TbDocumentosPreProjeto
     */
    public function setIdProjeto($idProjeto)
    {
        $this->_idProjeto = $idProjeto;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIdPRONAC()
    {
        return $this->_idPRONAC;
    }

    /**
     * @param mixed $idPRONAC
     * @return Proposta_Model_TbDocumentosPreProjeto
     */
    public function setIdPRONAC($idPRONAC)
    {
        $this->_idPRONAC = $idPRONAC;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->_Data;
    }

    /**
     * @param mixed $Data
     * @return Proposta_Model_TbDocumentosPreProjeto
     */
    public function setData($Data)
    {
        $this->_Data = $Data;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getImDocumento()
    {
        return $this->_imDocumento;
    }

    /**
     * @param mixed $imDocumento
     * @return Proposta_Model_TbDocumentosPreProjeto
     */
    public function setImDocumento($imDocumento)
    {
        $this->_imDocumento = $imDocumento;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getNoArquivo()
    {
        return $this->_NoArquivo;
    }

    /**
     * @param mixed $NoArquivo
     * @return Proposta_Model_TbDocumentosPreProjeto
     */
    public function setNoArquivo($NoArquivo)
    {
        $this->_NoArquivo = $NoArquivo;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTaArquivo()
    {
        return $this->_TaArquivo;
    }

    /**
     * @param mixed $TaArquivo
     * @return Proposta_Model_TbDocumentosPreProjeto
     */
    public function setTaArquivo($TaArquivo)
    {
        $this->_TaArquivo = $TaArquivo;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getBiDocumento()
    {
        return $this->_biDocumento;
    }

    /**
     * @param mixed $biDocumento
     * @return Proposta_Model_TbDocumentosPreProjeto
     */
    public function setBiDocumento($biDocumento)
    {
        $this->_biDocumento = $biDocumento;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDsDocumento()
    {
        return $this->_dsDocumento;
    }

    /**
     * @param mixed $dsDocumento
     * @return Proposta_Model_TbDocumentosPreProjeto
     */
    public function setDsDocumento($dsDocumento)
    {
        $this->_dsDocumento = $dsDocumento;
        return $this;
    }

}