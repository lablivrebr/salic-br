<?php

class Proposta_PlanoDistribuicaoController extends Proposta_GenericController
{
    private $intTamPag;
    private $_idPreProjeto = null;

    public function init()
    {

        $idPreProjeto = $this->getRequest()->getParam('idPreProjeto');
        $auth = Zend_Auth::getInstance(); // instancia da autentica��o
        $PermissoesGrupo = array();

        //Da permissao de acesso a todos os grupos do usuario logado afim de atender o UC75
        if (isset($auth->getIdentity()->usu_codigo)) {
            //Recupera todos os grupos do Usuario
            $Usuario = new Autenticacao_Model_Usuario(); // objeto usu�rio
            $grupos = $Usuario->buscarUnidades($auth->getIdentity()->usu_codigo, 21);
            foreach ($grupos as $grupo) {
                $PermissoesGrupo[] = $grupo->gru_codigo;
            }
        }

        isset($auth->getIdentity()->usu_codigo) ? parent::perfil(1, $PermissoesGrupo) : parent::perfil(4, $PermissoesGrupo);

        // inicializando variaveis com valor padrao
        $this->intTamPag = 10;

        if (!empty ($idPreProjeto)) {
            $this->_idPreProjeto = $idPreProjeto;
            $this->view->idPreProjeto = $idPreProjeto;

            //VERIFICA SE A PROPOSTA ESTA COM O MINC
            $Movimentacao = new Proposta_Model_DbTable_TbMovimentacao();
            $rsStatusAtual = $Movimentacao->buscarStatusAtualProposta($idPreProjeto);
            $this->view->movimentacaoAtual = isset($rsStatusAtual['Movimentacao']) ? $rsStatusAtual['Movimentacao'] : '';
        } elseif ($idPreProjeto != '0') {
                parent::message("Necess&aacute;rio informar o n&uacute;mero da proposta.", "/manterpropostaincentivofiscal/index", "ERROR");
        }

        parent::init();

        /* =============================================================================== */
        /* ==== VERIFICA PERMISSAO DE ACESSO DO PROPONENTE A PROPOSTA OU AO PROJETO ====== */
        /* =============================================================================== */
        $this->verificarPermissaoAcesso(true, false, false);
    }

    public function indexAction()
    {
        $this->view->localRealizacao = true;

        $arrBusca = array();
        $arrBusca['idProjeto'] = $this->_idPreProjeto;
        $arrBusca['stAbrangencia'] = true;
        $tblAbrangencia = new Proposta_Model_DbTable_Abrangencia();
        $rsAbrangencia = $tblAbrangencia->buscar($arrBusca);

        if (empty($rsAbrangencia)) {
            $this->view->localRealizacao = false;
        }

        $pag = 1;
        $get = Zend_Registry::get('get');
        if (isset($get->pag)) $pag = $get->pag;
        if (isset($get->tamPag)) $this->intTamPag = $get->tamPag;
        $inicio = ($pag > 1) ? ($pag - 1) * $this->intTamPag : 0;
        $fim = $inicio + $this->intTamPag;
        $tblPlanoDistribuicao = new Proposta_Model_DbTable_PlanoDistribuicaoProduto();
        $total = $tblPlanoDistribuicao->pegaTotal(
            array(
                "a.idProjeto = ?" => $this->_idPreProjeto,
                "a.stPlanoDistribuicaoProduto = ?" => 'true'
            )
        );
        $tamanho = (($inicio + $this->intTamPag) <= $total) ? $this->intTamPag : $total - ($inicio);
        $rsPlanoDistribuicao = $tblPlanoDistribuicao->buscar(
            array(
                "a.idProjeto = ?" => $this->_idPreProjeto,
                "a.stPlanoDistribuicaoProduto = ?" => 'true'
            ),
            array("idPlanoDistribuicao DESC"),
            $tamanho,
            $inicio
        );


        if ($fim > $total) $fim = $total;
        $totalPag = (int)(($total % $this->intTamPag == 0) ? ($total / $this->intTamPag) : (($total / $this->intTamPag) + 1));
        $arrDados = array(
            "pag" => $pag,
            "total" => $total,
            "inicio" => ($inicio + 1),
            "fim" => $fim,
            "totalPag" => $totalPag,
            "planosDistribuicao" => ($rsPlanoDistribuicao),
            "formulario" => $this->_urlPadrao . "/proposta/plano-distribuicao/frm-plano-distribuicao?idPreProjeto=" . $this->_idPreProjeto,
            "urlApagar" => $this->_urlPadrao . "/proposta/plano-distribuicao/apagar?idPreProjeto=" . $this->_idPreProjeto,
            "urlPaginacao" => $this->_urlPadrao . "/prosposta/plano-distribuicao/index?idPreProjeto=" . $this->_idPreProjeto
        );

        $this->view->idPreProjeto = $this->_idPreProjeto;
        $this->view->isEditavel = $this->isEditavel($this->_idPreProjeto);
        $this->montaTela("planodistribuicao/index.phtml", $arrDados);
    }

    public function consultarComponenteAction()
    {
        $get = Zend_Registry::get("get");
        $idPreProjeto = $get->idPreProjeto;
        $this->_helper->layout->disableLayout(); // desabilita o layout

        if (!empty($idPreProjeto) || $idPreProjeto == '0') {
            $tblPlanoDistribuicao = new Proposta_Model_DbTable_PlanoDistribuicaoProduto();
            $rsPlanoDistribuicao = $tblPlanoDistribuicao->buscar(array("idProjeto=?" => $idPreProjeto, "stPlanoDistribuicaoProduto=?" => 1), array("idPlanoDistribuicao DESC"), 10);
            $arrDados = array("planosDistribuicao" => $rsPlanoDistribuicao);
            $this->montaTela("planodistribuicao/consultar-componente.phtml", $arrDados);
        }
    }

    public function frmPlanoDistribuicaoAction()
    {

        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();

        $bln_exitePP = "false"; //Nao existe Produto Principal cadastrado

        $get = Zend_Registry::get("get");
        if (!empty($get->idPlanoDistribuicao)) {
            $tblPlanoDistribuicao = new Proposta_Model_DbTable_PlanoDistribuicaoProduto();
            $rsPlanoDistribuicao = $tblPlanoDistribuicao->buscarPlanoDistribuicao(array('idPlanoDistribuicao = ?' => $get->idPlanoDistribuicao));
            $arrDados["planoDistribuicao"] = $rsPlanoDistribuicao;
        }

        $tblProduto = new Produto();
        $rsProdutos = $tblProduto->buscar(array("stEstado = ?" => 'f'), array("Descricao ASC"));


        //BUSCA POR PRODUTO PRINCIPAL CADASTRADO
        $tblPlanoDistribuicao = new Proposta_Model_DbTable_PlanoDistribuicaoProduto();
        $arrPlanoDistribuicao = $tblPlanoDistribuicao->buscar(array(
            "a.idProjeto = ?" => $this->_idPreProjeto,
            "a.stPrincipal = ?" => 't',
            "a.stPlanoDistribuicaoProduto = ?" => 't'), array("idPlanoDistribuicao DESC"))
            ->toArray();

        if (!empty($arrPlanoDistribuicao)) {
            $bln_exitePP = "true"; //Existe Produto Principal Cadastrado

            $tblSegmento = new Segmento();
            $arrDados["Segmento"] = $tblSegmento->buscar(array("Codigo = ?" => $arrPlanoDistribuicao[0]['Segmento']));
        }

        $arrDados["comboprodutos"] = $rsProdutos;
        $manterAgentes = new ManterAgentes();
        $arrDados["comboareasculturais"] = $manterAgentes->listarAreasCulturais();

        $arrDados["acaoSalvar"] = $this->_urlPadrao . "/proposta/plano-distribuicao/salvar?idPreProjeto=" . $this->_idPreProjeto;
        $arrDados["urlApagar"] = $this->_urlPadrao . "/proposta/plano-distribuicao/apagar?idPreProjeto=" . $this->_idPreProjeto;
        $arrDados["acaoCancelar"] = $this->_urlPadrao . "/proposta/plano-distribuicao/index?idPreProjeto=" . $this->_idPreProjeto;
        $arrDados["bln_exitePP"] = $bln_exitePP;
        $this->montaTela("planodistribuicao/formplanodistribuicao.phtml", $arrDados);
    }

    public function salvarAction()
    {

        $post = Zend_Registry::get("post");

        try {
            if (($this->isEditarProjeto($this->_idPreProjeto) && $post->prodprincipal == 1)) {
                throw new Exception("Em alterar projeto, n&atilde;o pode alterar o produto principal cadastrado. A opera&ccedil;&atilde;o foi cancelada.");
            }

            $dados = array(
                "Area" => $post->areaCultural,
                "idProjeto" => $this->_idPreProjeto,
                "idProduto" => $post->produto,
                "Segmento" => $post->segmentoCultural,
                "dsJustificativaPosicaoLogo" => $post->dsJustificativaPosicaoLogo,
                "stPrincipal" => $post->prodprincipal,
                "Usuario" => $this->_SGCacesso['IdUsuario']
            );

            if (isset($post->idPlanoDistribuicao)) {
                $dados["idPlanoDistribuicao"] = $post->idPlanoDistribuicao;
            }

            $dados["stPlanoDistribuicaoProduto"] = true;

            $tblPlanoDistribuicao = new Proposta_Model_DbTable_PlanoDistribuicaoProduto();

            //VERIFICA SE JA EXISTE PRODUTO PRINCIPAL JA CADASTRADO
            $arrBusca = array();
            $arrBusca['a.idProjeto = ?'] = $this->_idPreProjeto;
            $arrBusca['a.stPrincipal = ?'] = true;
            !empty($post->idPlanoDistribuicao) ? $arrBusca['idPlanoDistribuicao <> ?'] = $post->idPlanoDistribuicao : '';
            //$arrBusca['idPlanoDistribuicao <> ?'] = $post->idPlanoDistribuicao;
            $arrBusca['stPlanoDistribuicaoProduto = ?'] = true;
            $arrPlanoDistribuicao = $tblPlanoDistribuicao->buscar($arrBusca, array("idPlanoDistribuicao DESC"))->toArray();

            if ($post->patrocinador != 0
                || $post->divulgacao != 0
                || $post->beneficiarios != 0
                || $post->qtdenormal != 0
                || $post->qtdepromocional != 0
            ) {
                if (!empty($arrPlanoDistribuicao) && $post->prodprincipal == "1") {
                    throw new Exception("J&aacute; existe um Produto Principal cadastrado. A opera&ccedil;&atilde;o foi cancelada.");
                }
            }

            if (isset($post->produto)) {
                //VERIFICA SE PRODUTO JA ESTA CADASTRADO - NAO PODE GRAVAR O MESMO PRODUTO MAIS DE UMA VEZ.
                $arrBuscaProduto['a.idProjeto = ?'] = $this->_idPreProjeto;
                $arrBuscaProduto['a.idProduto = ?'] = $post->produto;
                $objProduto = $tblPlanoDistribuicao->buscar($arrBuscaProduto);

                if ($objProduto[0]['idPlanoDistribuicao']) {
                    throw new Exception("Produto j&aacute; cadastrado no plano de distribui&ccedil;&atilde;o desta proposta!");
                }
            }
            $mprPlanoDistribuicaoProduto = new Proposta_Model_PlanoDistribuicaoProdutoMapper();
            $mdlPlanoDistribuicaoProduto = new Proposta_Model_PlanoDistribuicaoProduto($dados);
            $id = $mprPlanoDistribuicaoProduto->save($mdlPlanoDistribuicaoProduto);

            if (empty($id)) {
                throw new Exception("N&atilde;o foi poss&iacute;vel realizar a opera&ccedil;&atilde;o!");
            }

            parent::message("Opera&ccedil;&atilde;o realizada com sucesso!", "/proposta/plano-distribuicao/index?idPreProjeto=" . $this->_idPreProjeto, "CONFIRM");

        } catch (Exception $e) {
            parent::message($e->getMessage(), "/proposta/plano-distribuicao/index?idPreProjeto=" . $this->_idPreProjeto, "ERROR");
        }

    }

    public function apagarAction()
    {
        try {

            if (empty($this->_idPreProjeto))
                throw new Exception("Informe o numero da proposta");

            $get = Zend_Registry::get("get");

            $tblPlanoDistribuicao = new Proposta_Model_DbTable_PlanoDistribuicaoProduto();
            $rsPlanoDistribuicao = $tblPlanoDistribuicao->findBy(array("idPlanoDistribuicao = ?" => $get->idPlanoDistribuicao));

            if (($this->isEditarProjeto($this->_idPreProjeto) && $rsPlanoDistribuicao['stPrincipal'] == 1)) {
                throw new Exception("Em alterar projeto, n&atilde;o pode excluir o produto principal cadastrado. A opera&ccedil;&atilde;o foi cancelada.");
            }

            $retorno = $tblPlanoDistribuicao->apagar($get->idPlanoDistribuicao);

            if ($retorno < 1) {
                throw new Exception("N&atilde;o foi poss&iacute;vel realizar a opera&ccedil;&atilde;o!!");
            }

            parent::message("Opera&ccedil;&atilde;o realizada com sucesso!", "/proposta/plano-distribuicao/index?idPreProjeto=" . $this->_idPreProjeto, "CONFIRM");
        } catch (Exception $e) {
            parent::message($e->getMessage(), "/proposta/plano-distribuicao/index?idPreProjeto=" . $this->_idPreProjeto, "ERROR");
        }
    }

    public function detalharPlanoDistribuicaoAction()
    {
        try {
            $params = $this->getRequest()->getParams();

            if (empty($params['idPlanoDistribuicao'])) {
                throw new Exception("&Eacute; necess&aacute;rio informar o produto do plano de distribui&ccedil;&atilde;o");
            }

            $tblPlanoDistribuicao = new Proposta_Model_DbTable_PlanoDistribuicaoProduto();
            $rsPlanoDistribuicao = $tblPlanoDistribuicao->buscar(
                array(
                    "a.idPlanoDistribuicao = ?" => $params['idPlanoDistribuicao'],
                    "a.idProjeto = ?" => $this->_idPreProjeto,
                    "a.stPlanoDistribuicaoProduto = ?" => true
                ),
                array("idPlanoDistribuicao DESC")
            );

            $arrBusca['idProjeto'] = $this->_idPreProjeto;
            $arrBusca['stAbrangencia'] = true;
            $tblAbrangencia = new Proposta_Model_DbTable_Abrangencia();
            $rsAbrangencia = $tblAbrangencia->buscar($arrBusca);

            $this->view->abrangencias = $rsAbrangencia;
            $this->view->planosDistribuicao = $rsPlanoDistribuicao;
            $this->view->isEditavel = $this->isEditavel($this->_idPreProjeto);

        } catch (Exception $e) {
            parent::message($e->getMessage(), "/proposta/plano-distribuicao/index?idPreProjeto=" . $this->_idPreProjeto, "ERROR");
        }
    }

    public function salvarDetalhamentoAction()
    {
        $dados = $this->getRequest()->getPost();
        $detalhamento = new Proposta_Model_DbTable_TbDetalhamentoPlanoDistribuicaoProduto();
        $tblPlanoDistribuicao = new Proposta_Model_DbTable_PlanoDistribuicaoProduto();

        try {
            $dados['stDistribuicao'] = isset($dados['stDistribuicao']) ? $dados['stDistribuicao'] : true;

            if (!$detalhamento->salvar($dados)) {
                throw new Exception("Erro ao salvar detalhamento");
            }

            $tblPlanoDistribuicao->updateConsolidacaoPlanoDeDistribuicao($dados['idPlanoDistribuicao']);

            $this->_helper->json(array('data' => $dados, 'success' => 'true'));

        } catch (Exception $e) {
            $this->_helper->json(array('msg' => $e->getMessage(), 'data' => $dados, 'success' => 'false', 'error' => $e));
        }

    }

    public function obterDetalhamentoAction()
    {
        $dados = $this->getRequest()->getParams();
        $detalhamento = new Proposta_Model_DbTable_TbDetalhamentoPlanoDistribuicaoProduto();
        $dados = $detalhamento->listarPorMunicicipioUF($dados);
        sleep(1);

        $this->_helper->json(array('data' => $dados->toArray(), 'success' => 'true'));
    }

    public function excluirDetalhamentoAction()
    {
        $id = (int)$this->getRequest()->getParam('idDetalhaPlanoDistribuicao');
        $idPlanoDistribuicao = (int)$this->getRequest()->getParam('idPlanoDistribuicao');

        $detalhamento = new Proposta_Model_DbTable_TbDetalhamentoPlanoDistribuicaoProduto();
        $dados = $detalhamento->excluir($id);

        $tblPlanoDistribuicao = new Proposta_Model_DbTable_PlanoDistribuicaoProduto();
        $tblPlanoDistribuicao->updateConsolidacaoPlanoDeDistribuicao($idPlanoDistribuicao);

        $this->_helper->json(array('data' => $dados, 'success' => 'true'));
    }

}
