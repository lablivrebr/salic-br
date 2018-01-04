<?php
/**
 * Class Proposta_Model_DbTable_PlanoDeDivulgacao
 *
 * @name Proposta_Model_DbTable_PlanoDeDivulgacao
 * @package Modules/Agente
 * @subpackage Models/DbTable
 * @version $Id$
 *
 * @link http://salic.cultura.gov.br
 *
 * @author  wouerner <wouerner@gmail.com>
 * @author Ruy Junior Ferreira Silva <ruyjfs@gmail.com>
 * @since 21/09/2016
 */
class Proposta_Model_DbTable_PlanoDeDivulgacao extends MinC_Db_Table_Abstract{

    protected $_banco = 'sac';
    protected $_schema = 'sac';
    protected $_name  = 'PlanoDeDivulgacao';
    protected $_primary  = 'idPlanoDivulgacao';

    public static function buscarDigulgacao($idPreProjeto){
        $sql = "SELECT
                pd.idPlanoDivulgacao,
                pd.idProjeto,
                pd.idPeca,
                pd.idVeiculo,
                pd.Usuario,
                ve.Descricao as Pe�a,
                ve1.Descricao as Veiculo

                FROM
                    sac.dbo.PlanoDeDivulgacao pd
                     join sac.dbo.Verificacao ve on ve.idVerificacao = pd.idPeca
                     join sac.dbo.Verificacao ve1 on ve1.idVerificacao = pd.idVeiculo
                WHERE idProjeto = $idPreProjeto
                ";


        $db = Zend_Db_Table::getDefaultAdapter();
	$db->setFetchMode(Zend_DB::FETCH_OBJ);
	$resultado = $db->fetchAll($sql);
        //Zend_Debug::dump($resultado);
	return $resultado;
    }


    public static function UpdateDivulgacao($idPlanoDivulgacao, $idPeca, $idVeiculo){
         try
        {
            $sql = "update  sac.dbo.PlanoDeDivulgacao set idPeca = $idPeca, idVeiculo = $idVeiculo where idPlanoDivulgacao = $idPlanoDivulgacao";
            $db= Zend_Db_Table::getDefaultAdapter();
            $db->setFetchMode(Zend_DB::FETCH_ASSOC);
            $resultado = $db->fetchRow($sql);


        }
        catch (Exception $e)
        {
            die("ERRO" . $e->getMessage());
        }


    }
     public static function inserirDivulgacao($divulgacao)
    {
          try
        {
        $db= Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_DB::FETCH_OBJ);

        $cadastrar = $db->insert("sac.dbo.PlanoDeDivulgacao", $divulgacao);

        } catch (Exception $e){
            die("ERRO" . $e->getMessage());
        }

    }
    public static function  excluirdivulgacao($idPlanoDivulgacao){

        try{

        $sql = "delete sac.dbo.PlanoDeDivulgacao where idPlanoDivulgacao = $idPlanoDivulgacao";
        $db= Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_DB::FETCH_OBJ);
        $resultado = $db->fetchAll($sql);
        }catch (Exception $e){

           die("ERRO" . $e->getMessage());

        }
    }

    public static function consultarDivulgacao(){
        $sql = "select idVerificacao, Descricao from sac.dbo.Verificacao where idTipo = 1 order by Descricao";


        $db = Zend_Db_Table::getDefaultAdapter();
	$db->setFetchMode(Zend_DB::FETCH_OBJ);
	$resultado = $db->fetchAll($sql);
        ///Zend_Debug::dump($resultado);
	return $resultado;
    }


    public function consultarVeiculo($pecaID)
    {
        $select = $this->select();
        $select->setIntegrityCheck(false);
        $select->from(array('r' => 'verificacaopecaxveiculo'), '*', $this->_schema);
        $select->joinLeft(array('p' => 'Verificacao'), 'r.idverificacaopeca = p.idverificacao', 'descricao as pecadescicao', $this->_schema);
        $select->joinLeft(array('v' => 'Verificacao'), 'r.idverificacaoveiculo = v.idverificacao', 'descricao as veiculodescicao', $this->_schema);
        //echo $slct; die;
        $select->where('idverificacaopeca = ? ', $pecaID);
//        $select->order(array("dtmovimentacao DESC"));
        $result = $this->fetchAll($select);
        return ($result) ? $result->toArray() : array();

//        $sql = "SELECT
//		r.idVerificacaoPeca,
//		r.idVerificacaoVeiculo,
//		P.Descricao as PecaDescicao,
//		V.Descricao as VeiculoDescicao
//		FROM sac.dbo.VerificacaoPecaxVeiculo as r
//
//        LEFT JOIN sac.dbo.Verificacao as P on
//            r.idVerificacaoPeca = P.idVerificacao
//
//        LEFT JOIN sac.dbo.Verificacao as V on
//            r.idVerificacaoVeiculo = V.idVerificacao
//
//        WHERE idVerificacaoPeca = ".$pecaID ;
//
//
//        $db = Zend_Db_Table::getDefaultAdapter();
//	$db->setFetchMode(Zend_DB::FETCH_OBJ);
//	$resultado = $db->fetchAll($sql);
//
//        return $resultado;
    }


    /**
     * @todo verificar onde esta sendo utilizado e deletar depois, usando apenas os metodos da abstract.
     */
       public function localiza($where=array(), $order=array(), $tamanho=-1, $inicio=-1)
       {
        $slct = $this->select();
        $slct->setIntegrityCheck(false);
        $slct->from(array('tbr' => $this->_name));
        foreach ($where as $coluna => $valor) {
            $slct->where($coluna, $valor);
        }
        return $this->fetchAll($slct);
    }


    public function buscar($where=array(), $order=array(), $tamanho=-1, $inicio=-1)
    {
        $slct = $this->select();
        $slct->setIntegrityCheck(false);
        $slct->from(array('pd' => $this->_name), $this->_getCols(), $this->_schema);

        $slct->joinInner(
            array('v1' => 'Verificacao'),
                   'v1.idVerificacao = pd.idPeca',
                   array('v1.Descricao as peca'),
            $this->_schema
                           );
        $slct->joinInner(
                array('v2' => 'Verificacao'),
                'v2.idVerificacao = pd.idVeiculo',
                array('v2.Descricao as veiculo'),
            $this->_schema
        );

        //adiciona quantos filtros foram enviados
        foreach ($where as $coluna => $valor) {
            $slct->where($coluna, $valor);
        }

        //adicionando linha order ao select
        $slct->order($order);

        // paginacao
        if ($tamanho > -1) {
            $tmpInicio = 0;
            if ($inicio > -1) {
                $tmpInicio = $inicio;
            }
            $slct->limit($tamanho, $tmpInicio);
        }
        $result = $this->fetchAll($slct);
        return ($result) ? $result->toArray() : array();
    }
}
