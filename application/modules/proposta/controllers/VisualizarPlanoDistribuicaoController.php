<?php

class Proposta_VisualizarPlanoDistribuicaoController extends Proposta_GenericController
{
    private $intTamPag;
    private $_idPreProjeto = null;

	public function init()
	{
        $this->_idPreProjeto = $this->getRequest()->getParam('idPreProjeto');
        parent::init();
	}

    public function visualizarAction()
    {
        $this->_helper->layout->disableLayout();

        $arrBusca = array();
        $arrBusca['idprojeto'] = $this->_idPreProjeto;

        $tblAbrangencia = new Proposta_Model_DbTable_Abrangencia();
        $rsAbrangencia = $tblAbrangencia->buscar($arrBusca);
        $this->view->abrangencias = $rsAbrangencia;

        $tblPlanoDistribuicao = new Proposta_Model_DbTable_PlanoDistribuicaoProduto();

        $rsPlanoDistribuicao = $tblPlanoDistribuicao->buscar(
            array("a.idprojeto = ?" => $this->_idPreProjeto, "a.stplanodistribuicaoproduto = ?" => 1),
            array("idplanodistribuicao DESC"),
            $tamanho,
            $inicio
        );

        $this->view->planosDistribuicao=$rsPlanoDistribuicao;

        $this->view->idPreProjeto = $this->_idPreProjeto;
        $this->abrangencias = $rsAbrangencia;
    }

    public function detalharAction()
    {
        $dados = $this->getRequest()->getParams();
        $detalhamento = new Proposta_Model_DbTable_TbDetalhamentoPlanoDistribuicaoProduto();
        $dados = $detalhamento->listarPorMunicicipioUF($dados);

        $this->_helper->json(array('data' => $dados->toArray(), 'success' => 'true'));
    }

}
