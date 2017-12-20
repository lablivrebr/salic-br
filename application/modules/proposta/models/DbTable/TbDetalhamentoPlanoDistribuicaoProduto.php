<?php

class Proposta_Model_DbTable_TbDetalhamentoPlanoDistribuicaoProduto extends MinC_Db_Table_Abstract
{
    protected $_schema = 'sac';
    protected $_name = 'tbDetalhaPlanoDistribuicao';
    protected $_primary = 'idDetalhaPlanoDistribuicao';

    public function salvar($dados)
    {
        return $this->insert($dados);
    }

    public function listarPorMunicicipioUF($dados)
    {
        $cols = array(
            'idDetalhaPlanoDistribuicao',
            'idPlanoDistribuicao',
            'idUF',
            'idMunicipio',
            'stDistribuicao',
            'dsProduto',
            'qtExemplares',
            'qtGratuitaDivulgacao',
            'qtGratuitaPatrocinador',
            'qtGratuitaPopulacao',
            'qtPopularIntegral',
            'qtPopularParcial',
            "vlUnitarioPopularIntegral",
            "vlReceitaPopularIntegral",
            "vlReceitaPopularParcial",
            'qtProponenteIntegral',
            'qtProponenteParcial',
            "vlUnitarioProponenteIntegral",
            "vlReceitaProponenteIntegral",
            "vlReceitaProponenteParcial",
            "vlReceitaPrevista"
        );

        $sql = $this->select()
            ->from($this->_name, $cols, $this->_schema)
            ->where('idUF = ?', $dados['idUF'])
            ->where('idMunicipio = ?', $dados['idMunicipio'])
            ->where('idPlanoDistribuicao = ?', $dados['idPlanoDistribuicao']);

        return $this->fetchAll($sql);
    }

    public function excluir($id)
    {
        return $this->delete("idDetalhaPlanoDistribuicao = $id");
    }

    public function excluirByIdPreProjeto($idPreProjeto, $where = array(), $order = null)
    {
        $slct = $this->select();
        $slct->setIntegrityCheck(false);
        $slct->from(array("d" => $this->_name) , array('d.idDetalhaPlanoDistribuicao'), $this->_schema);

        $slct->joinInner(array("p" => 'PlanoDistribuicaoProduto'),
            "p.idPlanoDistribuicao = d.idPlanoDistribuicao",
            array(), $this->_schema);

        $slct->where('p.idProjeto = ?', $idPreProjeto);

        foreach ($where as $coluna => $valor) {
            $slct->where($coluna, $valor);
        }

        $slct->order($order);

        $this->delete(new Zend_Db_Expr('idDetalhaPlanoDistribuicao in (' . $slct .')'));

    }
}
