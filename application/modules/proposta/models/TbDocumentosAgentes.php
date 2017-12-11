<?php

class Proposta_Model_TbDocumentosAgentes extends MinC_Db_Model
{
    protected $_idDocumentosAgentes;
    protected $_CodigoDocumento;
    protected $_idAgente;
    protected $_Data;
    protected $_imDocumento;
    protected $_NoArquivo;
    protected $_TaArquivo;

    /**
     * @return mixed
     */
    public function getIdDocumentosAgentes()
    {
        return $this->_idDocumentosAgentes;
    }

    /**
     * @param mixed $idDocumentosAgentes
     * @return Proposta_Model_TbDocumentosAgentes
     */
    public function setIdDocumentosAgentes($idDocumentosAgentes)
    {
        $this->_idDocumentosAgentes = $idDocumentosAgentes;
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
     * @return Proposta_Model_TbDocumentosAgentes
     */
    public function setCodigoDocumento($CodigoDocumento)
    {
        $this->_CodigoDocumento = $CodigoDocumento;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIdAgente()
    {
        return $this->_idAgente;
    }

    /**
     * @param mixed $idAgente
     * @return Proposta_Model_TbDocumentosAgentes
     */
    public function setIdAgente($idAgente)
    {
        $this->_idAgente = $idAgente;
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
     * @return Proposta_Model_TbDocumentosAgentes
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
     * @return Proposta_Model_TbDocumentosAgentes
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
     * @return Proposta_Model_TbDocumentosAgentes
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
     * @return Proposta_Model_TbDocumentosAgentes
     */
    public function setTaArquivo($TaArquivo)
    {
        $this->_TaArquivo = $TaArquivo;
        return $this;
    }
}