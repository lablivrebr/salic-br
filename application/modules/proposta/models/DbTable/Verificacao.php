<?php
/**
 * Class Proposta_Model_DbTable_Verificacao
 *
 * @name Proposta_Model_DbTable_Verificacao
 * @package Modules/Agente
 * @subpackage Models/DbTable
 * @version $Id$
 *
 * @author Ruy Junior Ferreira Silva <ruyjfs@gmail.com>
 * @since 21/09/2016
 *
 * @copyright © 2012 - Ministerio da Cultura - Todos os direitos reservados.
 * @link http://salic.cultura.gov.br
 */
class Proposta_Model_DbTable_Verificacao extends MinC_Db_Table_Abstract{

    protected $_schema = 'sac';
    protected $_name  = 'Verificacao';
    protected $_primary  = 'idVerificacao';

    public function buscarFonteRecurso() {
//        $sql = "select Verificacao.idVerificacao, ltrim(Verificacao.Descricao) as VerificacaoDescricao
//                from sac.dbo.Verificacao as Verificacao
//                inner join sac.dbo.Tipo as Tipo
//                on Verificacao.idTipo = Tipo.idTipo
//                where Tipo.idTipo = 5";
        $select = $this->select();
        $select->setIntegrityCheck(false);
        $select->from(
            array('v' => $this->_name),
            array(
                'idVerificacao',
                $this->getExpressionTrim("v.Descricao","VerificacaoDescricao"),
            ),
            $this->_schema
            );
        $select->joinInner(array('tipo'=>'Tipo'),
            'v.idTipo = tipo.idTipo' ,
            null,
            $this->_schema
        );
        $select->where('tipo.idTipo = ?', '5');
        $db= Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_DB::FETCH_OBJ);

        return $db->fetchAll($select);
    }
}
