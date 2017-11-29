<?php

class Proposta_Model_TbDeslocamento extends MinC_Db_Model
{
    protected $_idDeslocamento;
    protected $_idProjeto;
    protected $_idPaisOrigem;
    protected $_idUFOrigem;
    protected $_idMunicipioOrigem;
    protected $_idPaisDestino;
    protected $_idUFDestino;
    protected $_idMunicipioDestino;
    protected $_Qtde;
    protected $_idUsuario;

    /**
     * @return mixed
     */
    public function getIdDeslocamento()
    {
        return $this->_idDeslocamento;
    }

    /**
     * @param mixed $idDeslocamento
     * @return Proposta_Model_TbDeslocamento
     */
    public function setIdDeslocamento($idDeslocamento)
    {
        $this->_idDeslocamento = $idDeslocamento;
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
     * @return Proposta_Model_TbDeslocamento
     */
    public function setIdProjeto($idProjeto)
    {
        $this->_idProjeto = $idProjeto;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIdPaisOrigem()
    {
        return $this->_idPaisOrigem;
    }

    /**
     * @param mixed $idPaisOrigem
     * @return Proposta_Model_TbDeslocamento
     */
    public function setIdPaisOrigem($idPaisOrigem)
    {
        $this->_idPaisOrigem = $idPaisOrigem;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIdUFOrigem()
    {
        return $this->_idUFOrigem;
    }

    /**
     * @param mixed $idUFOrigem
     * @return Proposta_Model_TbDeslocamento
     */
    public function setIdUFOrigem($idUFOrigem)
    {
        $this->_idUFOrigem = $idUFOrigem;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIdMunicipioOrigem()
    {
        return $this->_idMunicipioOrigem;
    }

    /**
     * @param mixed $idMunicipioOrigem
     * @return Proposta_Model_TbDeslocamento
     */
    public function setIdMunicipioOrigem($idMunicipioOrigem)
    {
        $this->_idMunicipioOrigem = $idMunicipioOrigem;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIdPaisDestino()
    {
        return $this->_idPaisDestino;
    }

    /**
     * @param mixed $idPaisDestino
     * @return Proposta_Model_TbDeslocamento
     */
    public function setIdPaisDestino($idPaisDestino)
    {
        $this->_idPaisDestino = $idPaisDestino;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIdUFDestino()
    {
        return $this->_idUFDestino;
    }

    /**
     * @param mixed $idUFDestino
     * @return Proposta_Model_TbDeslocamento
     */
    public function setIdUFDestino($idUFDestino)
    {
        $this->_idUFDestino = $idUFDestino;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIdMunicipioDestino()
    {
        return $this->_idMunicipioDestino;
    }

    /**
     * @param mixed $idMunicipioDestino
     * @return Proposta_Model_TbDeslocamento
     */
    public function setIdMunicipioDestino($idMunicipioDestino)
    {
        $this->_idMunicipioDestino = $idMunicipioDestino;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getQtde()
    {
        return $this->_Qtde;
    }

    /**
     * @param mixed $Qtde
     * @return Proposta_Model_TbDeslocamento
     */
    public function setQtde($Qtde)
    {
        $this->_Qtde = $Qtde;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIdUsuario()
    {
        return $this->_idUsuario;
    }

    /**
     * @param mixed $idUsuario
     * @return Proposta_Model_TbDeslocamento
     */
    public function setIdUsuario($idUsuario)
    {
        $this->_idUsuario = $idUsuario;
        return $this;
    }


}
