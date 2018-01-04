<?php
/**
 * DAO tbPlanilhaEtapa
 * @author jeffersonassilva@gmail.com - XTI
 * @since 07/03/2014
 * @version 1.0
 * @package application
 * @subpackage application.model
 * @copyright � 2011 - Minist�rio da Cultura - Todos os direitos reservados.
 * @link http://www.cultura.gov.br
 */

class Proposta_Model_DbTable_TbPlanilhaEtapa extends MinC_Db_Table_Abstract
{
	protected $_schema = 'sac';
	protected $_name   = 'tbPlanilhaEtapa';
    protected $_primary = 'idPlanilhaEtapa';

    public function listarEtapasProdutos($idPreProjeto)
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_DB::FETCH_OBJ);

        $sql = $db->select()
            ->from(array('tbplanilhaetapa'), array('idPlanilhaEtapa as idEtapa', 'Descricao as DescricaoEtapa'), $this->_schema)
            ->where("tpCusto = 'P'")
            ->where("stEstado = 1")
            ->order("idPlanilhaEtapa ASC")
        ;

        //$sql = " SELECT idPlanilhaEtapa as idEtapa, Descricao as DescricaoEtapa FROM sac.dbo.tbPlanilhaEtapa WHERE tpCusto = 'P' ";

        return $db->fetchAll($sql);
    }

    public function buscarEtapas($tipoEtapa, $fetchMode = Zend_DB::FETCH_OBJ)
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode($fetchMode);

        $sql = $db->select()
            ->from(array('tbPlanilhaEtapa'), array('idPlanilhaEtapa as idEtapa', 'Descricao as DescricaoEtapa'), $this->_schema)
            ->where('"tpCusto" = ?' , $tipoEtapa)
            ->where('"stEstado" = ?', 't')
            ->order('idPlanilhaEtapa ASC')
        ;

        return $db->fetchAll($sql);
    }

    public  function buscarEtapasCadastrarProdutos() {

        $select = $this->select();
        $select->setIntegrityCheck(false);
        $select->from(
            array($this->_name),
            array(
                'idPlanilhaEtapa',
                'Descricao'
            ),
            $this->_schema
        );
        $select->where('tpCusto = ?', 'P');
        $select->order('Descricao');
//        $sql.= " ORDER BY Descricao ";

        try {
            $db = Zend_Db_Table::getDefaultAdapter();
            $db->setFetchMode(Zend_DB::FETCH_OBJ);
        }
        catch (Zend_Exception_Db $e) {
            $this->view->message = "Erro ao buscar Etapas: " . $e->getMessage();
        }
        //die($sql);
        return $db->fetchAll($select);
    }

    public function buscarEtapasCusto() {

        $select = $this->select();
        $select->setIntegrityCheck(false);
        $select->from(
            array($this->_name),
            array(
                'idPlanilhaEtapa',
                'Descricao'
            ),
            $this->_schema
        );
        $select->where('tpcusto = ?','A');
        $select->order('Descricao');

        try {
            $db = Zend_Db_Table::getDefaultAdapter();
            $db->setFetchMode(Zend_DB::FETCH_OBJ);
        }
        catch (Zend_Exception_Db $e) {
            $this->view->message = "Erro ao buscar Etapas: " . $e->getMessage();
        }

        return $db->fetchAll($select);
    }

    public function listarCustosAdministrativos()
    {

//        $sql = $db->select()
//            ->from('tbplanilhaetapa', ['idplanilhaetapa as idEtapa', 'Descricao as DescricaoEtapa'], $this->_schema)
//            ->where("tpCusto = 'A' AND idPlanilhaEtapa <> 6")
//        ;

        $select = $this->select();
        $select->setIntegrityCheck(false);
        $select->from(
            array('pet'=>$this->_name),
            array(
                'idplanilhaetapa as idetapa',
                'Descricao as descricaoEtapa'
            ),
            $this->_schema
        );
        $select->where('tpcusto = ?', 'A');
        $select->where('idPlanilhaEtapa != ? ', '6');

        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_DB::FETCH_OBJ);
        return $db->fetchAll($select);
    }

    public function listarItensCustosAdministrativos($idPreProjeto, $tipoCusto)
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_DB::FETCH_OBJ);

        $tpp = array(
            'tpp.idUsuario',
            'tpp.idProjeto as idProposta',
            'tpp.idPlanilhaProposta',
            'tpp.Quantidade',
            'tpp.Ocorrencia',
            'tpp.ValorUnitario',
            'tpp.QtdeDias',
        );

        $tpe = array(
            'tpe.tpCusto as custo',
            'tpe.Descricao as etapa',
            'tpe.idPlanilhaEtapa as idEtapa',
            'tpe.tpCusto',
        );

        $tpi = array(
            'tpi.Descricao as DescricaoItem',
            'tpi.idPlanilhaItens as idItem',
        );

        $uf = array(
            'uf.Descricao as DescricaoUf',
            'uf.Sigla as SiglaUF',
        );

        $veri = array(
            'veri.idVerificacao as idFonteRecurso',
            'veri.Descricao as DescricaoFonteRecurso'
        );

        $sql = $this->select()
            ->from(array('tpp' => 'tbPlanilhaProposta'), $tpp, $this->_schema)
            ->joinLeft(array('pd' => 'Produto'), "pd.Codigo = tpp.idProduto", null, $this->_schema)
            ->join(array('tpe' => 'tbPlanilhaEtapa'), 'tpe.idPlanilhaEtapa = tpp.idEtapa', $tpe, $this->_schema)
            ->join(array('tpi' => 'tbPlanilhaItens'), 'tpi.idPlanilhaItens = tpp.idPlanilhaItem', $tpi, $this->_schema)
            ->joinLeft(array('uf' => 'UF'), 'uf.idUF = tpp.UfDespesa', $uf, $this->getSchema('agentes'))
            ->joinLeft(array('municipio' => 'Municipios'),
                'municipio.idMunicipioIBGE::varchar(6) = tpp.MunicipioDespesa::varchar(6)',
                'municipio.Descricao as Municipio',
                $this->getSchema('agentes')
            )
            ->join(array('prep' => 'PreProjeto'), 'prep.idPreProjeto = tpp.idProjeto', null, $this->_schema)
            ->join(array('mec' => 'Mecanismo'), 'mec.Codigo::integer = prep.Mecanismo::integer', 'mec.Descricao as mecanismo', $this->_schema)
            ->join(array('un' => 'tbPlanilhaUnidade'), 'un.idUnidade = tpp.Unidade', 'un.Descricao as Unidade', $this->_schema)
            ->join(array('veri' => 'Verificacao'), 'veri.idVerificacao = tpp.FonteRecurso', $veri, $this->_schema)
            ->where('tpe.tpCusto = ?', $tipoCusto)
            ->where('tpp.idProjeto = ?', $idPreProjeto)
            ->order('tpe.Descricao');
//        echo '<pre>';
//        echo($sql->assemble());
//        exit;
        return $this->fetchAll($sql);
    }



    public function listarDadosCadastrarCustos($idPreProjeto)
    {
        $db= Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_DB::FETCH_OBJ);
        $sql = $db->select()
            ->from(array('tpp' => 'tbplanilhaproposta'), 'tpp.idprojeto as idProposta', $this->_schema)
            ->where('tpp.idProjeto = ?', $idPreProjeto)
            ->limit(1)
        ;

        return $db->fetchAll($sql);
    }

    public function listarEtapasCusto()
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_DB::FETCH_OBJ);

        $sql = $db->select()
            ->from(array('tbplanilhaetapa'), array('idPlanilhaEtapa', 'Descricao'), $this->_schema)
            ->where("tpcusto = 'A'")
            ->order('Descricao')
        ;

        //$sql = "SELECT
        //idPlanilhaEtapa ,
        //Descricao
        //FROM sac..tbPlanilhaEtapa where tpcusto = 'A'";

        //$sql.= " ORDER BY Descricao ";

        try {
        }
        catch (Zend_Exception_Db $e) {
            $this->view->message = "Erro ao buscar Etapas: " . $e->getMessage();
        }

        return $db->fetchAll($sql);
    }
} // fecha class