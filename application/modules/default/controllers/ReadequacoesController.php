<?php

class ReadequacoesController extends MinC_Controller_Action_Abstract {

    private $intTamPag = 10;
    private $idAgente = 0;
    private $idUsuario = 0;
    private $idOrgao = 0;
    private $idPerfil = 0;

    public function init() {
        $GrupoAtivo = new Zend_Session_Namespace('GrupoAtivo'); // cria a sess&atilde;o com o grupo ativo
        $this->idOrgao = $GrupoAtivo->codOrgao;
        $this->idPerfil = $GrupoAtivo->codGrupo;

        // verifica as permissoes
        $PermissoesGrupo = array();
        $PermissoesGrupo[] = '1111'; //Permissao para proponentes.
        $PermissoesGrupo[] = '151'; //Permissao para proponentes.
        $PermissoesGrupo[] = '148'; //Permissao para proponentes.

        // pega o idAgente do usu&aacute;rio logado
        $auth = Zend_Auth::getInstance(); // pega a autentica&ccedil;&atilde;o
        $this->view->usuarioInterno = false;

        if (isset($auth->getIdentity()->usu_codigo)) { // autenticacao novo salic
            $this->view->usuarioInterno = true;
            $this->idUsuario = $auth->getIdentity()->usu_codigo;

            //Recupera todos os grupos do Usuario
            $Usuario = new Autenticacao_Model_Usuario(); // objeto usu&aacute;rio
            $grupos = $Usuario->buscarUnidades($auth->getIdentity()->usu_codigo, 21);
            foreach ($grupos as $grupo){
                $PermissoesGrupo[] = $grupo->gru_codigo;
            }

            parent::perfil(1, $PermissoesGrupo);
            $this->idAgente = UsuarioDAO::getIdUsuario($auth->getIdentity()->usu_codigo);
            $this->idAgente = ($this->idAgente) ? $this->idAgente["idAgente"] : 0;

        } else { // autenticacao scriptcase
            parent::perfil(4, $PermissoesGrupo);
            $this->idUsuario = (isset($_GET["idusuario"])) ? $_GET["idusuario"] : 0;

            /* =============================================================================== */
            /* ==== VERIFICA PERMISSAO DE ACESSO DO PROPONENTE AO PROJETO ====== */
            /* =============================================================================== */
            $this->verificarPermissaoAcesso(false, true, false);
        }
        parent::init();

        //SE CAIU A SECAO REDIRECIONA
        if(!$auth->hasIdentity()){
            $url = Zend_Controller_Front::getInstance()->getBaseUrl();
            JS::redirecionarURL($url);
        }

        //SE NENHUM PRONAC FOI ENVIADO, REDIRECIONA
        $idPronac = $this->_request->getParam("idPronac"); // pega o id do pronac via get
        if (strlen($idPronac) > 7) {
            $idPronac = Seguranca::dencrypt($idPronac);
        }
        $this->view->idPronac = $idPronac;
    }

    /**
     * Tela de cadastro de readequa&ccedil;&otilde;es - vis&atilde;o do proponente
     * @require idPronac
     */
    public function indexAction() {
        //FUN&ccedil;&atilde;O ACESSADA SOMENTE PELO PROPONENTE.
        $this->view->idPerfil = $this->idPerfil;
        if($this->idPerfil != 1111){
            parent::message("Voc&ecirc; n&atilde;o tem permiss&atilde;o para acessar essa &aacute;rea do sistema!", "principal", "ALERT");
        }

        if(isset($_POST['idUF'])) {
            $this->_helper->layout->disableLayout(); // desabilita o Zend_Layout
            $iduf = $_POST['idUF'];
            $mun = new Agente_Model_DbTable_Municipios();
            $cidade = $mun->listar($iduf);
            $a = 0;
            $cidadeArray = array();
            foreach($cidade as $DadosCidade) {
                $cidadeArray[$a]['idCidade'] = $DadosCidade->id;
                $cidadeArray[$a]['nomeCidade'] = utf8_encode($DadosCidade->Descricao);
                $a++;
            }
            $this->_helper->json($cidadeArray);
            $this->_helper->viewRenderer->setNoRender(TRUE);
        }

        if(isset($_POST['idEtapa']) && isset($_POST['idProduto'])) {
            $this->_helper->layout->disableLayout(); // desabilita o Zend_Layout
            $tbItensPlanilhaProduto = new tbItensPlanilhaProduto();
            $itens = $tbItensPlanilhaProduto->itensPorItemEEtapaReadequacao($_POST['idEtapa'], $_POST['idProduto']);

            $a = 0;
            $itensArray = array();
            foreach($itens as $i) {
                $itensArray[$a]['idPlanilhaItens'] = $i->idPlanilhaItens;
                $itensArray[$a]['Item'] = utf8_encode($i->Item);
                $a++;
            }
            $this->_helper->json($itensArray);
            $this->_helper->viewRenderer->setNoRender(TRUE);
        }

        $idPronac = $this->_request->getParam("idPronac");
        if (strlen($idPronac) > 7) {
            $idPronac = Seguranca::dencrypt($idPronac);
        }

        $this->view->idPronac = $idPronac;
        if(!empty($idPronac)){
            $Projetos = new Projetos();
            $this->view->projeto = $Projetos->buscar(array('IdPRONAC = ?'=>$idPronac))->current();

//            $buscarRecurso = ManterorcamentoDAO::buscarFonteRecurso();

            $tbVerificao = new Proposta_Model_DbTable_Verificacao();
            $buscarRecurso = $tbVerificao->buscarFonteRecurso();

            $this->view->Recursos = $buscarRecurso;

            $uf = new Agente_Model_DbTable_UF();
            $buscarEstado = $uf->buscar();
            $this->view->UFs = $buscarEstado;

            $PlanoDistribuicaoProduto = new Proposta_Model_DbTable_PlanoDistribuicaoProduto();
            $this->view->Produtos = $PlanoDistribuicaoProduto->comboProdutosParaInclusaoReadequacao($idPronac);

            $tbPlanilhaEtapa = new Proposta_Model_DbTable_TbPlanilhaEtapa();
            $this->view->Etapas = $tbPlanilhaEtapa->buscar(array('stEstado = ?'=>1));

            $TbPlanilhaUnidade = new Proposta_Model_DbTable_TbPlanilhaUnidade();
            $buscarUnidade = $TbPlanilhaUnidade->buscarUnidade();
            $this->view->Unidade = $buscarUnidade;

            $tbTipoReadequacao = new tbTipoReadequacao();
            $this->view->TiposReadequacao = $tbTipoReadequacao->buscarTiposReadequacoesPermitidos($idPronac);

            // na listagem de 'outras solicita&ccedil;&otilde;es' n&atilde;o listar planilha or&ccedil;ament&aacute;ria (idTipoReadequacao 2)
            $tbReadequacao = new tbReadequacao();
            $this->view->readequacoesCadastradas = $tbReadequacao->readequacoesCadastradasProponente(array('a.idPronac = ?'=>$idPronac, 'a.siEncaminhamento = ?'=>12, 'a.idTipoReadequacao != ?' => 2), array(1));

        } else {
            parent::message("N&uacute;mero Pronac inv&aacute;lido!", "principalproponente", "ERROR");
        }
    }



    /*
     * Criada em 06/03/14
     * @author: Jefferson Alessandro - jeffersonassilva@gmail.com
     * Essa fun&ccedil;&atilde;o &eacute; acessada para alterar o item da planilha or&ccedil;ament&aacute;ria.
     */
    public function alterarItemSolicitacaoAction() {
        $this->_helper->layout->disableLayout();
        $idPlanilhaAprovacao = $this->_request->getParam("idPlanilha");
        $tbPlanilhaAprovacao = new tbPlanilhaAprovacao();

        /* DADOS DO ITEM ATIVO */
        $itemTipoPlanilha = $tbPlanilhaAprovacao->buscar(array('idPlanilhaAprovacao=?'=>$idPlanilhaAprovacao))->current();
        $where = array();
        if($itemTipoPlanilha->tpPlanilha == 'SR'){
            $where['idPlanilhaAprovacao = ?'] = !empty($itemTipoPlanilha->idPlanilhaAprovacaoPai) ? $itemTipoPlanilha->idPlanilhaAprovacaoPai : $itemTipoPlanilha->idPlanilhaAprovacao;
            $idPlan = !empty($itemTipoPlanilha->idPlanilhaAprovacaoPai) ? $itemTipoPlanilha->idPlanilhaAprovacaoPai : $itemTipoPlanilha->idPlanilhaAprovacao;
        } else {
            $where['idPlanilhaAprovacao = ?'] = $idPlanilhaAprovacao;
            $idPlan = $idPlanilhaAprovacao;
        }
        $where['stAtivo = ?'] = 'S';
        $planilhaAtiva = $tbPlanilhaAprovacao->buscarDadosAvaliacaoDeItemRemanejamento($where);

        /* DADOS DO ITEM PARA EDICAO DA READEQUACAO */
        $where = array();
        $where['idPlanilhaAprovacao = ?'] = $idPlanilhaAprovacao;
        $where['tpPlanilha = ?'] = 'SR';
        $where['stAtivo = ?'] = 'N';
        $planilhaEditaval = $tbPlanilhaAprovacao->buscarDadosAvaliacaoDeItemRemanejamento($where);

        $dadosPlanilhaAtiva = array();
        $dadosPlanilhaEditavel = array();

        /* PROJETO */
        $Projetos = new Projetos();
        $projeto = $Projetos->buscar(array('IdPRONAC = ?' => $_GET['idPronac']))->current();
        $dadosProjeto = array(
            'IdPRONAC' => $projeto->IdPRONAC,
            'PRONAC' => $projeto->AnoProjeto.$projeto->Sequencial,
            'NomeProjeto' => utf8_encode($projeto->NomeProjeto)
        );

        foreach ($planilhaAtiva as $registro) {
            //CALCULAR VALORES MINIMO E MAXIMO PARA VALIDACAO
            $dadosPlanilhaAtiva['idPlanilhaAprovacao'] = $registro['idPlanilhaAprovacao'];
            $dadosPlanilhaAtiva['idProduto'] = $registro['idProduto'];
            $dadosPlanilhaAtiva['descProduto'] = utf8_encode($registro['descProduto']);
            $dadosPlanilhaAtiva['idEtapa'] = $registro['idEtapa'];
            $dadosPlanilhaAtiva['descEtapa'] = utf8_encode($registro['descEtapa']);
            $dadosPlanilhaAtiva['idPlanilhaItem'] = $registro['idPlanilhaItem'];
            $dadosPlanilhaAtiva['descItem'] = utf8_encode($registro['descItem']);
            $dadosPlanilhaAtiva['idUnidade'] = $registro['idUnidade'];
            $dadosPlanilhaAtiva['descUnidade'] = utf8_encode($registro['descUnidade']);
            $dadosPlanilhaAtiva['Quantidade'] = $registro['Quantidade'];
            $dadosPlanilhaAtiva['Ocorrencia'] = $registro['Ocorrencia'];
            $dadosPlanilhaAtiva['ValorUnitario'] = utf8_encode('R$ '.number_format($registro['ValorUnitario'], 2, ',', '.'));
            $dadosPlanilhaAtiva['QtdeDias'] = $registro['QtdeDias'];
            $dadosPlanilhaAtiva['TotalSolicitado'] = utf8_encode('R$ '.number_format(($registro['Quantidade']*$registro['Ocorrencia']*$registro['ValorUnitario']), 2, ',', '.'));
            $dadosPlanilhaAtiva['Justificativa'] = utf8_encode($registro['Justificativa']);
        }

        if(count($planilhaEditaval) > 0){
            foreach ($planilhaEditaval as $registroEditavel) {
                $dadosPlanilhaEditavel['idPlanilhaAprovacao'] = $registroEditavel['idPlanilhaAprovacao'];
                $dadosPlanilhaEditavel['idProduto'] = $registroEditavel['idProduto'];
                $dadosPlanilhaEditavel['descProduto'] = utf8_encode($registroEditavel['descProduto']);
                $dadosPlanilhaEditavel['idEtapa'] = $registroEditavel['idEtapa'];
                $dadosPlanilhaEditavel['descEtapa'] = utf8_encode($registroEditavel['descEtapa']);
                $dadosPlanilhaEditavel['idPlanilhaItem'] = $registroEditavel['idPlanilhaItem'];
                $dadosPlanilhaEditavel['descItem'] = utf8_encode($registroEditavel['descItem']);
                $dadosPlanilhaEditavel['idUnidade'] = $registroEditavel['idUnidade'];
                $dadosPlanilhaEditavel['descUnidade'] = utf8_encode($registroEditavel['descUnidade']);
                $dadosPlanilhaEditavel['Quantidade'] = $registroEditavel['Quantidade'];
                $dadosPlanilhaEditavel['Ocorrencia'] = $registroEditavel['Ocorrencia'];
                $dadosPlanilhaEditavel['ValorUnitario'] = utf8_encode('R$ '.number_format($registroEditavel['ValorUnitario'], 2, ',', '.'));
                $dadosPlanilhaEditavel['QtdeDias'] = $registroEditavel['QtdeDias'];
                $dadosPlanilhaEditavel['TotalSolicitado'] = utf8_encode('R$ '.number_format(($registroEditavel['Quantidade']*$registroEditavel['Ocorrencia']*$registroEditavel['ValorUnitario']), 2, ',', '.'));
                $dadosPlanilhaEditavel['Justificativa'] = utf8_encode($registroEditavel['Justificativa']);
                $dadosPlanilhaEditavel['idAgente'] = $registroEditavel['idAgente'];
            }
        } else {
            $dadosPlanilhaEditavel = $dadosPlanilhaAtiva;
        }

        if(count($planilhaEditaval) > 0 && count($planilhaAtiva)==0){
            $dadosPlanilhaAtiva = $dadosPlanilhaEditavel;
        }

        $tbCompPagxPlanAprov = new tbComprovantePagamentoxPlanilhaAprovacao();
        $res = $tbCompPagxPlanAprov->buscarValorComprovadoDoItem($idPlan);
        $valoresDoItem = array(
            'vlComprovadoDoItem' => utf8_encode('R$ '.number_format($res->vlComprovado, 2, ',', '.')),
            'vlComprovadoDoItemValidacao' => utf8_encode(number_format($res->vlComprovado, 2, '', ''))
        );

        //$jsonEncode = json_encode($dadosPlanilha);
        $this->_helper->json(array('resposta'=>true, 'dadosPlanilhaAtiva'=>$dadosPlanilhaAtiva, 'dadosPlanilhaEditavel'=>$dadosPlanilhaEditavel, 'valoresDoItem'=>$valoresDoItem, 'dadosProjeto'=>$dadosProjeto));
        $this->_helper->viewRenderer->setNoRender(TRUE);
    }

    /*
     * Criada em 14/03/14
     * @author: Jefferson Alessandro - jeffersonassilva@gmail.com
     * Esse fun&ccedil;&atilde;o &eacute; usada pelo proponente para solicitar a exclus&atilde;o de um item da planilha or&ccedil;ament&aacute;ria.
     */
    public function excluirItemSolicitacaoAction() {
        $this->_helper->layout->disableLayout();
        $idPlanilhaAprovacao = $this->_request->getParam("idPlanilha");
        $idPronac = $this->_request->getParam("idPronac");
        if (strlen($idPronac) > 7) {
            $idPronac = Seguranca::dencrypt($idPronac);
        }

        $tbPlanilhaAprovacao = new tbPlanilhaAprovacao();
        $verificarPlanilhaSR = $tbPlanilhaAprovacao->buscar(array('IdPRONAC=?'=>$idPronac, 'tpPlanilha=?'=>'SR'));

        //VERIFICA SE J&aacute; POSSUI A PLANILHA TIPO SR. SE N&atilde;O TIVER, CRIA, E DEPOIS DELETA O ITEM DESEJADO.
        if(count($verificarPlanilhaSR)==0){
            $planilhaAtiva = $tbPlanilhaAprovacao->buscar(array('IdPRONAC=?'=>$idPronac, 'StAtivo=?'=>'S'));
            $planilhaSR = array();

            $objAcesso = new Acesso();

            foreach ($planilhaAtiva as $value) {
                $planilhaSR['tpPlanilha'] = 'SR';
                $planilhaSR['dtPlanilha'] = $objAcesso->getExpressionDate();
                $planilhaSR['idPlanilhaProjeto'] = $value['idPlanilhaProjeto'];
                $planilhaSR['idPlanilhaProposta'] = $value['idPlanilhaProposta'];
                $planilhaSR['IdPRONAC'] = $value['IdPRONAC'];
                $planilhaSR['idProduto'] = $value['idProduto'];
                $planilhaSR['idEtapa'] = $value['idEtapa'];
                $planilhaSR['idPlanilhaItem'] = $value['idPlanilhaItem'];
                $planilhaSR['dsItem'] = $value['dsItem'];
                $planilhaSR['idUnidade'] = $value['idUnidade'];
                $planilhaSR['qtItem'] = $value['qtItem'];
                $planilhaSR['nrOcorrencia'] = $value['nrOcorrencia'];
                $planilhaSR['vlUnitario'] = $value['vlUnitario'];
                $planilhaSR['qtDias'] = $value['qtDias'];
                $planilhaSR['tpDespesa'] = $value['tpDespesa'];
                $planilhaSR['tpPessoa'] = $value['tpPessoa'];
                $planilhaSR['nrContraPartida'] = $value['nrContraPartida'];
                $planilhaSR['nrFonteRecurso'] = $value['nrFonteRecurso'];
                $planilhaSR['idUFDespesa'] = $value['idUFDespesa'];
                $planilhaSR['idMunicipioDespesa'] = $value['idMunicipioDespesa'];
                $planilhaSR['dsJustificativa'] = null;
                $planilhaSR['idAgente'] = 0;
                $planilhaSR['idPlanilhaAprovacaoPai'] = $value['idPlanilhaAprovacao'];
                $planilhaSR['idReadequacao'] = $value['idReadequacao'];
                $planilhaSR['tpAcao'] = 'N';
                $planilhaSR['idRecursoDecisao'] = $value['idRecursoDecisao'];
                $planilhaSR['stAtivo'] = 'N';
                $tbPlanilhaAprovacao->inserir($planilhaSR);
            }
        }

        /* DADOS DO ITEM PARA EXCLUSAO LÓGICA DO ITEM DA READEQUACAO */
        $dados = array();
        $dados['tpAcao'] = 'E';

        $whereIdPlanilha = "idPlanilhaAprovacaoPai = $idPlanilhaAprovacao";
        $itemTipoPlanilha = $tbPlanilhaAprovacao->buscar(array('idPlanilhaAprovacao=?'=>$idPlanilhaAprovacao))->current();

        if($itemTipoPlanilha->tpAcao == 'I'){
            $exclusaoLogica = $tbPlanilhaAprovacao->delete(array('idPlanilhaAprovacao = ?'=>$idPlanilhaAprovacao));
        } else {
            if($itemTipoPlanilha->tpPlanilha == 'SR'){
                $whereIdPlanilha = "idPlanilhaAprovacao = $idPlanilhaAprovacao";
            }
            $where = "stAtivo = 'N' AND tpPlanilha = 'SR' AND $whereIdPlanilha";
            $exclusaoLogica = $tbPlanilhaAprovacao->update($dados, $where);
        }

        if($exclusaoLogica){
            //$jsonEncode = json_encode($dadosPlanilha);
            $this->_helper->json(array('resposta'=>true));

                } else {
            $this->_helper->json(array('resposta'=>false));
                }
        $this->_helper->viewRenderer->setNoRender(TRUE);
    }

    /*
     * Criada em 10/03/14
     * @author: Jefferson Alessandro - jeffersonassilva@gmail.com
     */
    public function incluirItemPlanilhaReadequacaoAction() {
        $this->_helper->layout->disableLayout();
        $idPronac = $this->_request->getParam("idPronac");
        if (strlen($idPronac) > 7) {
            $idPronac = Seguranca::dencrypt($idPronac);
        }

        $tbPlanilhaAprovacao = new tbPlanilhaAprovacao();
        $verificarPlanilhaSR = $tbPlanilhaAprovacao->buscar(array('IdPRONAC=?'=>$idPronac, 'tpPlanilha=?'=>'SR'));

        //VERIFICA SE JA POSSUI A PLANILHA TIPO SR. SE N&atilde;O TIVER, COPIA A ORIGINAL, E DEPOIS INCLUI O ITEM DESEJADO.
        $objAcesso = new Acesso();
        if(count($verificarPlanilhaSR)==0){
            $planilhaAtiva = $tbPlanilhaAprovacao->buscar(array('IdPRONAC=?'=>$idPronac, 'StAtivo=?'=>'S'));
            $planilhaSR = array();

            foreach ($planilhaAtiva as $value) {
                $planilhaSR['tpPlanilha'] = 'SR';
                $planilhaSR['dtPlanilha'] = $objAcesso->getExpressionDate();
                $planilhaSR['idPlanilhaProjeto'] = $value['idPlanilhaProjeto'];
                $planilhaSR['idPlanilhaProposta'] = $value['idPlanilhaProposta'];
                $planilhaSR['IdPRONAC'] = $value['IdPRONAC'];
                $planilhaSR['idProduto'] = $value['idProduto'];
                $planilhaSR['idEtapa'] = $value['idEtapa'];
                $planilhaSR['idPlanilhaItem'] = $value['idPlanilhaItem'];
                $planilhaSR['dsItem'] = $value['dsItem'];
                $planilhaSR['idUnidade'] = $value['idUnidade'];
                $planilhaSR['qtItem'] = $value['qtItem'];
                $planilhaSR['nrOcorrencia'] = $value['nrOcorrencia'];
                $planilhaSR['vlUnitario'] = $value['vlUnitario'];
                $planilhaSR['qtDias'] = $value['qtDias'];
                $planilhaSR['tpDespesa'] = $value['tpDespesa'];
                $planilhaSR['tpPessoa'] = $value['tpPessoa'];
                $planilhaSR['nrContraPartida'] = $value['nrContraPartida'];
                $planilhaSR['nrFonteRecurso'] = $value['nrFonteRecurso'];
                $planilhaSR['idUFDespesa'] = $value['idUFDespesa'];
                $planilhaSR['idMunicipioDespesa'] = $value['idMunicipioDespesa'];
                $planilhaSR['dsJustificativa'] = null;
                $planilhaSR['idAgente'] = 0;
                $planilhaSR['idPlanilhaAprovacaoPai'] = $value['idPlanilhaAprovacao'];
                $planilhaSR['idReadequacao'] = $value['idReadequacao'];
                $planilhaSR['tpAcao'] = 'N';
                $planilhaSR['idRecursoDecisao'] = $value['idRecursoDecisao'];
                $planilhaSR['stAtivo'] = 'N';
                $tbPlanilhaAprovacao->inserir($planilhaSR);
            }
        }

        $idAgente = 0;
        $auth = Zend_Auth::getInstance(); // pega a autentica&ccedil;&atilde;o
        $tblAgente = new Agente_Model_DbTable_Agentes();
        $rsAgente = $tblAgente->buscar(array('CNPJCPF = ?'=>$auth->getIdentity()->Cpf));
        if($rsAgente->count() > 0){
             $idAgente = $rsAgente[0]->idAgente;
        }

        $newValorUnitario = str_replace('.', '', $_POST['newValorUnitario']);
        $newValorUnitario = str_replace(',', '.', $newValorUnitario);


        /* DADOS DO ITEM PARA INCLUS&atilde;O DA READEQUA&ccedil;&atilde;O */
        $dadosInclusao = array();
        $dadosInclusao['tpPlanilha'] = 'SR';
        $dadosInclusao['dtPlanilha'] = $objAcesso->getExpressionDate();
        $dadosInclusao['IdPRONAC'] = $idPronac;
        $dadosInclusao['idProduto'] = $_POST['newProduto'];
        $dadosInclusao['idEtapa'] = $_POST['newEtapa'];
        $dadosInclusao['idPlanilhaItem'] = $_POST['newItem'];
        $dadosInclusao['dsItem'] = '';
        $dadosInclusao['idUnidade'] = $_POST['newUnidade'];
        $dadosInclusao['qtItem'] = $_POST['newQuantidade'];
        $dadosInclusao['nrOcorrencia'] = $_POST['newOcorrencia'];
        $dadosInclusao['vlUnitario'] = $newValorUnitario;
        $dadosInclusao['qtDias'] = $_POST['newDias'];
        $dadosInclusao['tpDespesa'] = 0;
        $dadosInclusao['tpPessoa'] = 0;
        $dadosInclusao['nrContraPartida'] = 0;
        $dadosInclusao['nrFonteRecurso'] = $_POST['newRecursos'];
        $dadosInclusao['idUFDespesa'] = $_POST['newUF'];
        $dadosInclusao['idMunicipioDespesa'] = $_POST['newMunicipio'];
        $dadosInclusao['dsJustificativa'] = utf8_decode($_POST['newJustificativa']);
        $dadosInclusao['idAgente'] = $idAgente;
        $dadosInclusao['stAtivo'] = 'N';
        $dadosInclusao['tpAcao'] = 'I';
        $dadosInclusao['idReadequacao'] = $_POST['idReadequacao'];

        $insert = $tbPlanilhaAprovacao->inserir($dadosInclusao);

        if($insert){
            //$jsonEncode = json_encode($dadosPlanilha);
            $this->_helper->json(array('resposta'=>true));

            } else {
            $this->_helper->json(array('resposta'=>false));
            }
        $this->_helper->viewRenderer->setNoRender(TRUE);
    }

    /*
     * Criada em 06/03/14
     * @author: Jefferson Alessandro - jeffersonassilva@gmail.com
     * Essa fun&ccedil;&atilde;o &eacute; usada para alterar os dados do item da planilha or&ccedil;ament&aacute;ria.
     */
    public function salvarAvaliacaoDoItemAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        $auth = Zend_Auth::getInstance(); // pega a autentica&ccedil;&atilde;o
        $cpf = isset($auth->getIdentity()->Cpf) ? $auth->getIdentity()->Cpf : $auth->getIdentity()->usu_identificacao;

        $tblAgente = new Agente_Model_DbTable_Agentes();
        $rsAgente = $tblAgente->buscar(array('CNPJCPF = ?'=>$cpf));
        $idAgente = 0;
        if($rsAgente->count() > 0){
             $idAgente = $rsAgente[0]->idAgente;
        }

        $ValorUnitario = str_replace('.', '', $_POST['ValorUnitario']);
        $ValorUnitario = str_replace(',', '.', $ValorUnitario);

        $idPronac = $this->_request->getParam("idPronac");
        if (strlen($idPronac) > 7) {
            $idPronac = Seguranca::dencrypt($idPronac);
        }

        $tbPlanilhaAprovacao = new tbPlanilhaAprovacao();
        $verificarPlanilhaSR = $tbPlanilhaAprovacao->buscar(array('IdPRONAC=?'=>$idPronac, 'tpPlanilha=?'=>'SR'));

        $objAcesso = new Acesso();
        if(count($verificarPlanilhaSR)==0){
            $planilhaAtiva = $tbPlanilhaAprovacao->buscar(array('IdPRONAC=?'=>$idPronac, 'StAtivo=?'=>'S'));
            $planilhaSR = array();
            foreach ($planilhaAtiva as $value) {
                $planilhaSR['tpPlanilha'] = 'SR';
                $planilhaSR['dtPlanilha'] = $objAcesso->getExpressionDate();
                $planilhaSR['idPlanilhaProjeto'] = $value['idPlanilhaProjeto'];
                $planilhaSR['idPlanilhaProposta'] = $value['idPlanilhaProposta'];
                $planilhaSR['IdPRONAC'] = $value['IdPRONAC'];
                $planilhaSR['idProduto'] = $value['idProduto'];
                $planilhaSR['idEtapa'] = $value['idEtapa'];
                $planilhaSR['idPlanilhaItem'] = $value['idPlanilhaItem'];
                $planilhaSR['dsItem'] = $value['dsItem'];
                $planilhaSR['idUnidade'] = $value['idUnidade'];
                $planilhaSR['qtItem'] = $value['qtItem'];
                $planilhaSR['nrOcorrencia'] = $value['nrOcorrencia'];
                $planilhaSR['vlUnitario'] = $value['vlUnitario'];
                $planilhaSR['qtDias'] = $value['qtDias'];
                $planilhaSR['tpDespesa'] = $value['tpDespesa'];
                $planilhaSR['tpPessoa'] = $value['tpPessoa'];
                $planilhaSR['nrContraPartida'] = $value['nrContraPartida'];
                $planilhaSR['nrFonteRecurso'] = $value['nrFonteRecurso'];
                $planilhaSR['idUFDespesa'] = $value['idUFDespesa'];
                $planilhaSR['idMunicipioDespesa'] = $value['idMunicipioDespesa'];
                $planilhaSR['dsJustificativa'] = null;
                $planilhaSR['idAgente'] = 0;
                $planilhaSR['idPlanilhaAprovacaoPai'] = $value['idPlanilhaAprovacao'];
                $planilhaSR['idReadequacao'] = $value['idReadequacao'];
                $planilhaSR['tpAcao'] = 'N';
                $planilhaSR['idRecursoDecisao'] = $value['idRecursoDecisao'];
                $planilhaSR['stAtivo'] = 'N';
                $tbPlanilhaAprovacao->inserir($planilhaSR);
            }
        }

        $itemTipoPlanilha = $tbPlanilhaAprovacao->buscar(array('idPlanilhaAprovacao=?'=>$_POST['idPlanilha']))->current();
        if($itemTipoPlanilha->tpPlanilha == 'SR'){
            $editarItem = $tbPlanilhaAprovacao->buscar(array('IdPRONAC=?'=>$idPronac, 'tpPlanilha=?'=>'SR', 'idPlanilhaAprovacao=?'=>$_POST['idPlanilha']))->current();
        } else {
            $editarItem = $tbPlanilhaAprovacao->buscar(array('IdPRONAC=?'=>$idPronac, 'tpPlanilha=?'=>'SR', 'idPlanilhaAprovacaoPai=?'=>$_POST['idPlanilha']))->current();
        }
        $editarItem->idUnidade = $_POST['Unidade'];
        $editarItem->qtItem = $_POST['Quantidade'];
        $editarItem->nrOcorrencia = $_POST['Ocorrencia'];
        $editarItem->vlUnitario = $ValorUnitario;
        $editarItem->qtDias = $_POST['QtdeDias'];
        $editarItem->dsJustificativa = utf8_decode($_POST['Justificativa']);
        $editarItem->idAgente = $idAgente;
        if($editarItem->tpAcao == 'N'){
            $editarItem->tpAcao = 'A';
        }
//        $editarItem->idAgente = $auth->getIdentity()->IdUsuario;
        $editarItem->save();

        $this->_helper->json(array('resposta'=>true, 'msg'=>'Dados salvos com sucesso!'));
        $this->_helper->viewRenderer->setNoRender(TRUE);
    }

    /*
     * Criada em 18/03/2014
     * @author: Jefferson Alessandro - jeffersonassilva@gmail.com
     * Essa fun&ccedil;&atilde;o &eacute; usada pelost&eacute;cnicos, pareceristas e componentes da comiss&atilde;o para alterarem os itens da planilha or&ccedil;ament&aacute;ria.
     */
    public function alteracoesTecnicasNoItemAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        $tbPlanilhaAprovacao = new tbPlanilhaAprovacao();
        $editarItem = $tbPlanilhaAprovacao->buscar(array('idPlanilhaAprovacao=?'=>$_POST['idPlanilha']))->current();
        //$editarItem->idAgente = $idAgente;
        if($editarItem->tpAcao == 'E'){
            $editarItem->tpAcao = 'N';
        } else if($editarItem->tpAcao == 'I'){
            $editarItem->delete();
        } else if($editarItem->tpAcao == 'A'){
            $itemAtivo = $tbPlanilhaAprovacao->buscar(array('idPlanilhaAprovacao=?'=>$editarItem->idPlanilhaAprovacaoPai))->current();
            $editarItem->idProduto = $itemAtivo->idProduto;
            $editarItem->idEtapa = $itemAtivo->idEtapa;
            $editarItem->idUnidade = $itemAtivo->idUnidade;
            $editarItem->qtItem = $itemAtivo->qtItem;
            $editarItem->nrOcorrencia = $itemAtivo->nrOcorrencia;
            $editarItem->vlUnitario = $itemAtivo->vlUnitario;
            $editarItem->qtDias = $itemAtivo->qtDias;
            $editarItem->qtDias = $itemAtivo->qtDias;
            $editarItem->tpDespesa = $itemAtivo->tpDespesa;
            $editarItem->tpPessoa = $itemAtivo->tpPessoa;
            $editarItem->nrContraPartida = $itemAtivo->nrContraPartida;
            $editarItem->nrFonteRecurso = $itemAtivo->nrFonteRecurso;
            $editarItem->idUFDespesa = $itemAtivo->idUFDespesa;
            $editarItem->idMunicipioDespesa = $itemAtivo->idMunicipioDespesa;
            $editarItem->idMunicipioDespesa = $itemAtivo->idMunicipioDespesa;
            $editarItem->tpAcao = 'N';
        }
        $editarItem->save();

        $this->_helper->json(array('resposta'=>true, 'msg'=>'Dados salvos com sucesso!'));
        $this->_helper->viewRenderer->setNoRender(TRUE);
    }

    /*
     * Criada em 18/03/2014
     * @author: Jefferson Alessandro - jeffersonassilva@gmail.com
     * Essa fun&ccedil;&atilde;o &eacute; usada pelost&eacute;cnicos, pareceristas e componentes da comiss&atilde;o para alterarem os locais de realiza&ccedil;&atilde;o.
     */
    public function alteracoesTecnicasNoLocalDeRealizacaoAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        $tbAbrangencia = new tbAbrangencia();
        $editarItem = $tbAbrangencia->buscar(array('idAbrangencia=?'=>$_POST['idAbrangencia']))->current();

        if($this->idPerfil == 94 || $this->idPerfil == 121){ //Parecerista e T&eacute;cnico de Acompanhamento
            $editarItem->tpAnaliseTecnica = $_POST['tpAcao'];

        } else if($this->idPerfil == 118){ //Componente da Comiss&atilde;o
            $editarItem->tpAnaliseComissao = $_POST['tpAcao'];

        } else if($this->idPerfil == 1111){ //Proponente
            if($editarItem->tpSolicitacao == 'E'){
                $editarItem->tpSolicitacao = 'N';
            } else if($editarItem->tpSolicitacao == 'I'){
                $editarItem->delete();
                $this->_helper->json(array('resposta'=>true, 'msg'=>'Dados salvos com sucesso!')); $this->_helper->viewRenderer->setNoRender(TRUE);
            }
        }
        $editarItem->save();

        $this->_helper->json(array('resposta'=>true, 'msg'=>'Dados salvos com sucesso!'));
        $this->_helper->viewRenderer->setNoRender(TRUE);
    }

    /*
     * Criada em 28/03/2014
     * @author: Jefferson Alessandro - jeffersonassilva@gmail.com
     * Essa fun&ccedil;&atilde;o &eacute; usada pelost&eacute;cnicos, pareceristas e componentes da comiss&atilde;o para alterarem os planos de divulga&ccedil;&atilde;o.
     */
    public function alteracoesTecnicasNoPlanoDeDivulgacaoAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        $tbPlanoDivulgacao = new tbPlanoDivulgacao();
        $editarItem = $tbPlanoDivulgacao->buscar(array('idPlanoDivulgacao=?'=>$_POST['idPlanoDivulgacao']))->current();

        if($this->idPerfil == 94 || $this->idPerfil == 121){ //Parecerista e T&eacute;cnico de Acompanhamento
            $editarItem->tpAnaliseTecnica = $_POST['tpAcao'];

        } else if($this->idPerfil == 118){ //Componente da Comiss&atilde;o
            $editarItem->tpAnaliseComissao = $_POST['tpAcao'];

        } else if($this->idPerfil == 1111){ //Proponente
            if($editarItem->tpSolicitacao == 'E'){
                $editarItem->tpSolicitacao = 'N';
            } else if($editarItem->tpSolicitacao == 'I'){
                $editarItem->delete();
                $this->_helper->json(array('resposta'=>true, 'msg'=>'Dados salvos com sucesso!')); $this->_helper->viewRenderer->setNoRender(TRUE);
            }
        }
        $editarItem->save();

        $this->_helper->json(array('resposta'=>true, 'msg'=>'Dados salvos com sucesso!'));
        $this->_helper->viewRenderer->setNoRender(TRUE);
    }

    /*
     * Criada em 1/04/2014
     * @author: Jefferson Alessandro - jeffersonassilva@gmail.com
     * Essa fun&ccedil;&atilde;o &eacute; usada pelost&eacute;cnicos, pareceristas, proponente e componentes da comiss&atilde;o para alterarem os planos de distribui&ccedil;&atilde;o.
     */
    public function alteracoesTecnicasNoPlanoDeDistribuicaoAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        $tbPlanoDistribuicao = new tbPlanoDistribuicao();
        $editarItem = $tbPlanoDistribuicao->buscar(array('idPlanoDistribuicao=?'=>$_POST['idPlanoDistribuicao']))->current();

        if($this->idPerfil == 94 || $this->idPerfil == 121){ //Parecerista e T&eacute;cnico de Acompanhamento
            $editarItem->tpAnaliseTecnica = $_POST['tpAcao'];

        } else if($this->idPerfil == 118){ //Componente da Comiss&atilde;o
            $editarItem->tpAnaliseComissao = $_POST['tpAcao'];

        } else if($this->idPerfil == 1111){ //Proponente
            if($editarItem->tpSolicitacao == 'E'){
                $editarItem->tpSolicitacao = 'N';
            } else if($editarItem->tpSolicitacao == 'I'){
                $editarItem->delete();
                $this->_helper->json(array('resposta'=>true, 'msg'=>'Dados salvos com sucesso!')); $this->_helper->viewRenderer->setNoRender(TRUE);
            }
        }
        $editarItem->save();

        $this->_helper->json(array('resposta'=>true, 'msg'=>'Dados salvos com sucesso!'));
        $this->_helper->viewRenderer->setNoRender(TRUE);
    }

    public function criarCampoTipoReadequacaoAction() {
        $this->_helper->layout->disableLayout(); // desabilita o Zend_Layout
        $get = Zend_Registry::get('get');
        $tipoReadequacao = $get->tpReadequacao;
        $valorPreCarregado = null;
//        x($tipoReadequacao);

        //ALTERA&ccedil;&atilde;O DE RAZ&atilde;O SOCIAL DO PROPONENTE
        if($tipoReadequacao == 3){
            $Projetos = new Projetos();
            $dadosProjeto = $Projetos->buscarDadosUC75($get->idPronac)->current();

            if($dadosProjeto){
                $valorPreCarregado = $dadosProjeto->Proponente;
            }

        } else if($tipoReadequacao == 4){
            $Projetos = new Projetos();
            $dadosProjeto = $Projetos->buscar(array('IdPRONAC = ?'=>$get->idPronac))->current();

            if($dadosProjeto){
                $ContaBancaria = new ContaBancaria();
                $dadosBancarios = $ContaBancaria->contaPorProjeto($get->idPronac);
                if(count($dadosBancarios)>0){
                    $valorPreCarregado = $dadosBancarios->Agencia;
                }
            }

        //SINOPSE DA OBRA
        } else if($tipoReadequacao == 5){
            $Projetos = new Projetos();
            $dadosProjeto = $Projetos->buscar(array('IdPRONAC = ?'=>$get->idPronac))->current();

            if($dadosProjeto){
                $PreProjeto = new Proposta_Model_DbTable_PreProjeto();
                $dadosPreProjeto = $PreProjeto->buscar(array('idPreProjeto = ?'=>$dadosProjeto->idProjeto))->current();
                if($dadosPreProjeto){
                    $valorPreCarregado = $dadosPreProjeto->Sinopse;
                }
            }

        //IMPACTO AMBIENTAL
        } else if($tipoReadequacao == 6){
            $Projetos = new Projetos();
            $dadosProjeto = $Projetos->buscar(array('IdPRONAC = ?'=>$get->idPronac))->current();

            if($dadosProjeto){
                $PreProjeto = new Proposta_Model_DbTable_PreProjeto();
                $dadosPreProjeto = $PreProjeto->buscar(array('idPreProjeto = ?'=>$dadosProjeto->idProjeto))->current();
                if($dadosPreProjeto){
                    $valorPreCarregado = $dadosPreProjeto->ImpactoAmbiental;
                }
            }

        //ESPECIFICACAO TECNICA
        } else if($tipoReadequacao == 7){
            $Projetos = new Projetos();
            $dadosProjeto = $Projetos->buscar(array('IdPRONAC = ?'=>$get->idPronac))->current();

            if($dadosProjeto){
                $PreProjeto = new Proposta_Model_DbTable_PreProjeto();
                $dadosPreProjeto = $PreProjeto->buscar(array('idPreProjeto = ?'=>$dadosProjeto->idProjeto))->current();
                if($dadosPreProjeto){
                    $valorPreCarregado = $dadosPreProjeto->EspecificacaoTecnica;
                }
            }

        //ESTRAT&eacute;GIA DE EXECU&ccedil;&atilde;O
        } else if($tipoReadequacao == 8){
            $Projetos = new Projetos();
            $dadosProjeto = $Projetos->buscar(array('IdPRONAC = ?'=>$get->idPronac))->current();

            if($dadosProjeto){
                $PreProjeto = new Proposta_Model_DbTable_PreProjeto();
                $dadosPreProjeto = $PreProjeto->buscar(array('idPreProjeto = ?'=>$dadosProjeto->idProjeto))->current();
                if($dadosPreProjeto){
                    $valorPreCarregado = $dadosPreProjeto->EstrategiadeExecucao;
                }
            }

        //ALTERA&ccedil;&atilde;O DE PROPONENTE
        } else if($tipoReadequacao == 10){
            $Projetos = new Projetos();
            $dadosProjeto = $Projetos->buscar(array('IdPRONAC = ?'=>$get->idPronac))->current();

            if($dadosProjeto){
                $valorPreCarregado = $dadosProjeto->CgcCpf;
            }

        //NOME DO PROJETO
        } else if($tipoReadequacao == 12){
            $Projetos = new Projetos();
            $dadosProjeto = $Projetos->buscar(array('IdPRONAC = ?'=>$get->idPronac))->current();

            if($dadosProjeto){
                $valorPreCarregado = $dadosProjeto->NomeProjeto;
            }

        //PERÍODO DE EXECUCAO
        } else if($tipoReadequacao == 13){
            $Projetos = new Projetos();
            $dadosProjeto = $Projetos->buscar(array('IdPRONAC = ?'=>$get->idPronac))->current();
            $DtFimExecucao = Data::tratarDataZend($dadosProjeto->DtFimExecucao, 'brasileira');

            if($dadosProjeto){
                $valorPreCarregado = $DtFimExecucao;
            }

        //SÍNTESE DO PROJETO
        } else if($tipoReadequacao == 15){
            $Projetos = new Projetos();
            $dadosProjeto = $Projetos->buscar(array('IdPRONAC = ?'=>$get->idPronac))->current();

            if($dadosProjeto){
                $valorPreCarregado = $dadosProjeto->ResumoProjeto;
            }

        //OBJETIVOS
        } else if($tipoReadequacao == 16){
            $Projetos = new Projetos();
            $dadosProjeto = $Projetos->buscar(array('IdPRONAC = ?'=>$get->idPronac))->current();

            if($dadosProjeto){
                $PreProjeto = new Proposta_Model_DbTable_PreProjeto();
                $dadosPreProjeto = $PreProjeto->buscar(array('idPreProjeto = ?'=>$dadosProjeto->idProjeto))->current();
                if($dadosPreProjeto){
                    $valorPreCarregado = $dadosPreProjeto->Objetivos;
                }
            }

        //JUSTIFICATIVA
        } else if($tipoReadequacao == 17){
            $Projetos = new Projetos();
            $dadosProjeto = $Projetos->buscar(array('IdPRONAC = ?'=>$get->idPronac))->current();

            if($dadosProjeto){
                $PreProjeto = new Proposta_Model_DbTable_PreProjeto();
                $dadosPreProjeto = $PreProjeto->buscar(array('idPreProjeto = ?'=>$dadosProjeto->idProjeto))->current();
                if($dadosPreProjeto){
                    $valorPreCarregado = $dadosPreProjeto->Justificativa;
                }
            }

        //ACESSIBILIDADE
        } else if($tipoReadequacao == 18){
            $Projetos = new Projetos();
            $dadosProjeto = $Projetos->buscar(array('IdPRONAC = ?'=>$get->idPronac))->current();

            if($dadosProjeto){
                $PreProjeto = new Proposta_Model_DbTable_PreProjeto();
                $dadosPreProjeto = $PreProjeto->buscar(array('idPreProjeto = ?'=>$dadosProjeto->idProjeto))->current();
                if($dadosPreProjeto){
                    $valorPreCarregado = $dadosPreProjeto->Acessibilidade;
                }
            }

        //DEMOCRATIZACAO DE ACESSO
        } else if($tipoReadequacao == 19){
            $Projetos = new Projetos();
            $dadosProjeto = $Projetos->buscar(array('IdPRONAC = ?'=>$get->idPronac))->current();

            if($dadosProjeto){
                $PreProjeto = new Proposta_Model_DbTable_PreProjeto();
                $dadosPreProjeto = $PreProjeto->buscar(array('idPreProjeto = ?'=>$dadosProjeto->idProjeto))->current();
                if($dadosPreProjeto){
                    $valorPreCarregado = $dadosPreProjeto->DemocratizacaoDeAcesso;
                }
            }

        //ETAPAS DE TRABALHO
        } else if($tipoReadequacao == 20){
            $Projetos = new Projetos();
            $dadosProjeto = $Projetos->buscar(array('IdPRONAC = ?'=>$get->idPronac))->current();

            if($dadosProjeto){
                $PreProjeto = new Proposta_Model_DbTable_PreProjeto();
                $dadosPreProjeto = $PreProjeto->buscar(array('idPreProjeto = ?'=>$dadosProjeto->idProjeto))->current();
                if($dadosPreProjeto){
                    $valorPreCarregado = $dadosPreProjeto->EtapaDeTrabalho;
                }
            }

        //FICHA TECNICA
        } else if($tipoReadequacao == 21){
            $Projetos = new Projetos();
            $dadosProjeto = $Projetos->buscar(array('IdPRONAC = ?'=>$get->idPronac))->current();

            if($dadosProjeto){
                $PreProjeto = new Proposta_Model_DbTable_PreProjeto();
                $dadosPreProjeto = $PreProjeto->buscar(array('idPreProjeto = ?'=>$dadosProjeto->idProjeto))->current();
                if($dadosPreProjeto){
                    $valorPreCarregado = $dadosPreProjeto->FichaTecnica;
                }
            }
        }

        $this->montaTela(
            'readequacoes/criar-campo-tipo-readequacao.phtml',
            array(
                'tipoReadequacao' => $tipoReadequacao,
                'valorPreCarregado' => $valorPreCarregado
            )
        );
    }

    public function carregarValorEntrePlanilhasAction() {
        $auth = Zend_Auth::getInstance(); // pega a autenticacao
        $this->_helper->layout->disableLayout(); // desabilita o Zend_Layout
        $get = Zend_Registry::get('get');
        $idPronac = $this->_request->getParam("idPronac");
        if (strlen($idPronac) > 7) {
            $idPronac = Seguranca::dencrypt($idPronac);
        }

        $tbPlanilhaAprovacao = new tbPlanilhaAprovacao();

        //BUSCAR VALOR TOTAL DA PLANILHA ATIVA
        $where = array();
        $where['a.IdPRONAC = ?'] = $idPronac;
        $where['a.stAtivo = ?'] = 'S';
        $PlanilhaAtiva = $tbPlanilhaAprovacao->valorTotalPlanilha($where)->current();

        //BUSCAR VALOR TOTAL DA PLANILHA DE READEQUADA
        $where = array();
        $where['a.IdPRONAC = ?'] = $idPronac;
        $where['a.tpPlanilha = ?'] = 'SR';
        $where['a.stAtivo = ?'] = 'N';
        $where['a.tpAcao != ?'] = 'E';
        $PlanilhaReadequada = $tbPlanilhaAprovacao->valorTotalPlanilha($where)->current();

        if($PlanilhaReadequada->Total > 0){
            if($PlanilhaAtiva->Total == $PlanilhaReadequada->Total){
                $statusPlanilha = 'neutro';
            } else if($PlanilhaAtiva->Total > $PlanilhaReadequada->Total){
                $statusPlanilha = 'positivo';
            } else {
                $statusPlanilha = 'negativo';
            }
        } else {
            $PlanilhaAtiva->Total = 0;
            $PlanilhaReadequada->Total = 0;
            $statusPlanilha = 'neutro';
        }

        $this->montaTela(
            'readequacoes/carregar-valor-entre-planilhas.phtml', array(
            'statusPlanilha' => $statusPlanilha,
            'vlDiferencaPlanilhas' => 'R$ '.number_format(($PlanilhaReadequada->Total-$PlanilhaAtiva->Total), 2, ',', '.')
            )
        );
    }

    /*
     * Criada em 19/03/2014
     * @author: Jefferson Alessandro - jeffersonassilva@gmail.com
     * Essa fun&ccedil;&atilde;o &eacute; usada para carregar os dados do locais de realiza&ccedil;&atilde;o do projeto.
     */
    public function carregarLocaisDeRealizacaoAction() {
        $this->_helper->layout->disableLayout(); // desabilita o Zend_Layout
        $GrupoAtivo = new Zend_Session_Namespace('GrupoAtivo'); // cria a sess&atilde;o com o grupo ativo
        $this->view->idPerfil = $GrupoAtivo->codGrupo;

        if(isset($_POST['idUF'])) {
            $iduf = $_POST['idUF'];

            $mun = new Agente_Model_DbTable_Municipios();
            $cidade = $mun->listar($iduf);
            $a = 0;
            $cidadeArray = array();
            foreach($cidade as $DadosCidade) {
                $cidadeArray[$a]['idCidade'] = $DadosCidade->id;
                $cidadeArray[$a]['nomeCidade'] = utf8_encode($DadosCidade->Descricao);
                $a++;
            }
            $this->_helper->json($cidadeArray);
            die;
        }

        $idPronac = $this->_request->getParam("idPronac");
        if (strlen($idPronac) > 7) {
            $idPronac = Seguranca::dencrypt($idPronac);
        }
        $tbAbrangencia = new tbAbrangencia();
        $locais = $tbAbrangencia->buscarLocaisParaReadequacao($idPronac,'tbAbrangencia');
        if(count($locais)==0){
            $locais = $tbAbrangencia->buscarLocaisParaReadequacao($idPronac,'Abrangencia');
        }

        $tbPais = new Pais();
        $this->view->Paises = $tbPais->buscar(array(), array(3));

//        $buscarEstado = EstadoDAO::buscar();
        $uf = new Agente_Model_DbTable_UF();
        $buscarEstado = $uf->buscar();
        $this->view->UFs = $buscarEstado;

        $get = Zend_Registry::get('get');
        $link = isset($get->link) ? true : false;

        $this->montaTela(
            'readequacoes/carregar-locais-de-realizacao.phtml',
            array(
                'idPronac' => $idPronac,
                'locaisDeRealizacao' => $locais,
                'link' => $link
            )
        );
    }

    /*
     * Criada em 27/03/2014
     * @author: Jefferson Alessandro - jeffersonassilva@gmail.com
     * Essa fun&ccedil;&atilde;o &eacute; usada para carregar os dados do locais de realiza&ccedil;&atilde;o do projeto.
     */
    public function carregarLocaisDeRealizacaoReadequacoesAction() {
        $this->_helper->layout->disableLayout(); // desabilita o Zend_Layout
        $GrupoAtivo = new Zend_Session_Namespace('GrupoAtivo'); // cria a sess&atilde;o com o grupo ativo
        $this->view->idPerfil = $GrupoAtivo->codGrupo;

        $idPronac = $this->_request->getParam("idPronac");
        $idReadequacao = $this->_request->getParam("idReadequacao");
        if (strlen($idPronac) > 7) {
            $idPronac = Seguranca::dencrypt($idPronac);
        }

        $tbAbrangencia = new tbAbrangencia();
        $locais = $tbAbrangencia->buscarLocaisConsolidadoReadequacao($idReadequacao);

        $tbReadequacao = new tbReadequacao();
        $dadosReadequacao = $tbReadequacao->buscar(array('idReadequacao=?'=>$idReadequacao))->current();
        $siEncaminhamento = $dadosReadequacao->siEncaminhamento;

        $tbPais = new Pais();
        $this->view->Paises = $tbPais->buscar(array(), array(3));


        $uf = new Agente_Model_DbTable_UF();
        $this->view->UFs = $uf->buscar();
        $get = Zend_Registry::get('get');
        $link = isset($get->link) ? true : false;

        $this->montaTela(
            'readequacoes/carregar-locais-de-realizacao.phtml',
            array(
                'idPronac' => $idPronac,
                'locaisDeRealizacao' => $locais,
                'link' => $link,
                'idReadequacao' => $idReadequacao,
                'siEncaminhamento' => $siEncaminhamento
            )
        );
    }

    /*
     * Criada em 10/03/14
     * @author: Jefferson Alessandro - jeffersonassilva@gmail.com
     */
    public function incluirLocalDeRealizacaoAction() {
        $this->_helper->layout->disableLayout();
        $idPronac = $this->_request->getParam("idPronac");
        if (strlen($idPronac) > 7) {
            $idPronac = Seguranca::dencrypt($idPronac);
        }

        //VERIFICA SE JA POSSUI AS ABRANGENCIAS NA TABELA tbAbrangencia (READEQUACAO), SE N&atilde;O TIVER, COPIA DA ORIGINAL, E DEPOIS INCLUI O ITEM DESEJADO.
        $tbAbrangencia = new tbAbrangencia();
        $readequacaoLR = $tbAbrangencia->buscar(array('idPronac=?'=>$idPronac, 'stAtivo=?'=>'S'));
        $locaisAtivos = $tbAbrangencia->buscarLocaisParaReadequacao($idPronac);

        if(count($readequacaoLR)==0){
            $locaisCopiados = array();
            foreach ($locaisAtivos as $value){
                $locaisCopiados['idReadequacao'] = NULL;
                $locaisCopiados['idPais'] = $value->idPais;
                $locaisCopiados['idUF'] = $value->idUF;
                $locaisCopiados['idMunicipioIBGE'] = $value->idCidade;
                $locaisCopiados['tpSolicitacao'] = 'N';
                $locaisCopiados['stAtivo'] = 'S';
                $locaisCopiados['idPronac'] = $idPronac;
                $tbAbrangencia->inserir($locaisCopiados);
            }
        }

        if($_POST['newPaisLR'] == 31){
            if(empty($_POST['newUFLR']) && empty($_POST['newMunicipioLR'])){
                $msg = utf8_encode('Ao escolher o Brasil, os campos de UF e Munic&iacute;pio se tornam obrigatórios no cadastro!');
                $this->_helper->json(array('resposta'=>false, 'msg'=>$msg)); $this->_helper->viewRenderer->setNoRender(TRUE);
            }
            $verificaLocalRepetido = $tbAbrangencia->buscar(array('idPronac=?'=>$idPronac, 'stAtivo=?'=>'S', 'idPais=?'=>$_POST['newPaisLR'], 'idUF=?'=>$_POST['newUFLR'], 'idMunicipioIBGE=?'=>$_POST['newMunicipioLR']));
        } else {
            $verificaLocalRepetido = $tbAbrangencia->buscar(array('idPronac=?'=>$idPronac, 'stAtivo=?'=>'S', 'idPais=?'=>$_POST['newPaisLR']));
        }

        if(count($verificaLocalRepetido)==0){
            /* DADOS DO ITEM PARA INCLUS&atilde;O DA READEQUA&ccedil;&atilde;O */
            $dadosInclusao = array();
            $dadosInclusao['idReadequacao'] = NULL;
            $dadosInclusao['idPais'] = $_POST['newPaisLR'];
            $dadosInclusao['idUF'] = isset($_POST['newUFLR']) ? $_POST['newUFLR'] : 0;
            $dadosInclusao['idMunicipioIBGE'] = isset($_POST['newMunicipioLR']) ? $_POST['newMunicipioLR'] : 0;
            $dadosInclusao['tpSolicitacao'] = 'I';
            $dadosInclusao['stAtivo'] = 'S';
            $dadosInclusao['idPronac'] = $idPronac;
            $insert = $tbAbrangencia->inserir($dadosInclusao);
            if($insert){
                //$jsonEncode = json_encode($dadosPlanilha);
                $this->_helper->json(array('resposta'=>true));
            } else {
                $this->_helper->json(array('resposta'=>false));
            }
        } else {
            $msg = utf8_encode('Esse local de realiza&ccedil;&atilde;o j&aacute; foi cadastrado!');
            $this->_helper->json(array('resposta'=>false, 'msg'=>$msg));
        }
        $this->_helper->viewRenderer->setNoRender(TRUE);
    }

    /*
     * Criada em 25/03/2014
     * @author: Jefferson Alessandro - jeffersonassilva@gmail.com
     * Esse fun&ccedil;&atilde;o &eacute; usada pelo proponente para solicitar a exclus&atilde;o de um local de realiza&ccedil;&atilde;o.
     */
    public function excluirLocalDeRealizacaoAction() {
        $this->_helper->layout->disableLayout();
        $idAbrangencia = $this->_request->getParam("idAbrangencia");
        $idPronac = $this->_request->getParam("idPronac");
        if (strlen($idPronac) > 7) {
            $idPronac = Seguranca::dencrypt($idPronac);
        }

        $tbAbrangencia = new tbAbrangencia();
        $readequacaoLR = $tbAbrangencia->buscar(array('idPronac=?'=>$idPronac, 'stAtivo=?'=>'S'));

        //VERIFICA SE JA POSSUI AS ABRANGENCIAS NA TABELA tbAbrangencia (READEQUACAO), SE N&atilde;O TIVER, COPIA DA ORIGINAL, E DEPOIS INCLUI O ITEM DESEJADO.
        $locaisAtivos = $tbAbrangencia->buscarLocaisParaReadequacao($idPronac);
        if(count($readequacaoLR)==0){
            $locaisCopiados = array();
            foreach ($locaisAtivos as $value){
                $locaisCopiados['idReadequacao'] = NULL;
                $locaisCopiados['idPais'] = $value->idPais;
                $locaisCopiados['idUF'] = $value->idUF;
                $locaisCopiados['idMunicipioIBGE'] = $value->idCidade;
                $locaisCopiados['tpSolicitacao'] = 'N';
                $locaisCopiados['stAtivo'] = 'S';
                $locaisCopiados['idPronac'] = $idPronac;
                $tbAbrangencia->inserir($locaisCopiados);
            }
        }

        /* DADOS DO ITEM PARA EXCLUSAO LÓGICA DO ITEM DA READEQUACAO */
        $dados = array();
        $dados['tpSolicitacao'] = 'E';

        $itemLR = $tbAbrangencia->buscar(array('idAbrangencia=?'=>$idAbrangencia))->current();
        if($itemLR){
            if($itemLR->tpSolicitacao == 'I'){
                $exclusaoLogica = $tbAbrangencia->delete(array('idAbrangencia = ?'=>$idAbrangencia));
            } else {
                $where = "stAtivo = 'S' AND idAbrangencia = $idAbrangencia";
                $exclusaoLogica = $tbAbrangencia->update($dados, $where);
            }
        } else {
            $Abrangencia = new Proposta_Model_DbTable_Abrangencia();
            $itemLR = $Abrangencia->find(array('idAbrangencia=?'=>$idAbrangencia))->current();
            $dadosArray = array(
                'idPais =?' => $itemLR->idPais,
                'idUF =?' => $itemLR->idUF,
                'idMunicipioIBGE =?' => $itemLR->idMunicipioIBGE,
                'idPronac =?' => $idPronac,
                'stAtivo =?' => 'S',
            );
            $itemLR = $tbAbrangencia->buscar($dadosArray)->current();
            $where = "stAtivo = 'S' AND idAbrangencia = $itemLR->idAbrangencia";
            $exclusaoLogica = $tbAbrangencia->update($dados, $where);
        }

        if($exclusaoLogica){
            //$jsonEncode = json_encode($dadosPlanilha);
            $this->_helper->json(array('resposta'=>true));

        } else {
            $this->_helper->json(array('resposta'=>false));
        }
        $this->_helper->viewRenderer->setNoRender(TRUE);
    }

    /*
     * Criada em 28/03/2014
     * @author: Jefferson Alessandro - jeffersonassilva@gmail.com
     * Essa fun&ccedil;&atilde;o &eacute; usada para carregar os dados do planos de divulga&ccedil;&atilde;o do projeto.
     */
    public function carregarPlanosDeDivulgacaoAction(){
        $this->_helper->layout->disableLayout(); // desabilita o Zend_Layout
        $GrupoAtivo = new Zend_Session_Namespace('GrupoAtivo'); // cria a sess&atilde;o com o grupo ativo
        $this->view->idPerfil = $GrupoAtivo->codGrupo;

        $idPronac = $this->_request->getParam("idPronac");
        if (strlen($idPronac) > 7) {
            $idPronac = Seguranca::dencrypt($idPronac);
        }
        $tbPlanoDivulgacao = new tbPlanoDivulgacao();
        $planosDivulgacao = $tbPlanoDivulgacao->buscarPlanosDivulgacaoReadequacao($idPronac, 'tbPlanoDivulgacao');

        if(count($planosDivulgacao)==0){
            $planosDivulgacao = $tbPlanoDivulgacao->buscarPlanosDivulgacaoReadequacao($idPronac, 'PlanoDeDivulgacao');
        }

        $Verificacao = new Verificacao();
        $pecas = $Verificacao->buscar(array('idTipo=?'=>1), array('Descricao'));
        $veiculos = $Verificacao->buscar(array('idTipo=?'=>2), array('Descricao'));

        $get = Zend_Registry::get('get');
        $link = isset($get->link) ? true : false;

        $this->montaTela(
            'readequacoes/carregar-planos-de-divulgacao.phtml',
            array(
                'idPronac' => $idPronac,
                'planosDeDivulgacao' => $planosDivulgacao,
                'pecas' => $pecas,
                'veiculos' => $veiculos,
                'link' => $link
            )
        );
    }

    /*
     * Criada em 27/03/2014
     * @author: Jefferson Alessandro - jeffersonassilva@gmail.com
     * Essa fun&ccedil;&atilde;o &eacute; usada para carregar os dados dos planos de divulga&ccedil;&atilde;o do projeto.
     */
    public function carregarPlanosDeDivulgacaoReadequacoesAction() {
        $this->_helper->layout->disableLayout(); // desabilita o Zend_Layout
        $GrupoAtivo = new Zend_Session_Namespace('GrupoAtivo'); // cria a sess&atilde;o com o grupo ativo
        $this->view->idPerfil = $GrupoAtivo->codGrupo;

        $idPronac = $this->_request->getParam("idPronac");
        $idReadequacao = $this->_request->getParam("idReadequacao");
        if (strlen($idPronac) > 7) {
            $idPronac = Seguranca::dencrypt($idPronac);
        }

        $tbPlanoDivulgacao = new tbPlanoDivulgacao();
        $planosDivulgacao = $tbPlanoDivulgacao->buscarPlanosDivulgacaoConsolidadoReadequacao($idReadequacao);

        $tbReadequacao = new tbReadequacao();
        $dadosReadequacao = $tbReadequacao->buscar(array('idReadequacao=?'=>$idReadequacao))->current();
        $siEncaminhamento = $dadosReadequacao->siEncaminhamento;

        $Verificacao = new Verificacao();
        $pecas = $Verificacao->buscar(array('idTipo=?'=>1), array('Descricao'));
        $veiculos = $Verificacao->buscar(array('idTipo=?'=>2), array('Descricao'));

        $get = Zend_Registry::get('get');
        $link = isset($get->link) ? true : false;

        $this->montaTela(
            'readequacoes/carregar-planos-de-divulgacao.phtml',
            array(
                'idPronac' => $idPronac,
                'planosDeDivulgacao' => $planosDivulgacao,
                'pecas' => $pecas,
                'veiculos' => $veiculos,
                'link' => $link,
                'idReadequacao' => $idReadequacao,
                'siEncaminhamento' => $siEncaminhamento
            )
        );
    }

    /*
     * Criada em 28/03/2014
     * @author: Jefferson Alessandro - jeffersonassilva@gmail.com
     * Essa fun&ccedil;&atilde;o &eacute; usada para que o proponente fa&ccedil;a uma solicita&ccedil;&atilde;o de inclus&atilde;o de plano de divulga&ccedil;&atilde;o
     */
    public function incluirPlanosDeDivulgacaoAction() {
        $this->_helper->layout->disableLayout();
        $idPronac = $this->_request->getParam("idPronac");
        if (strlen($idPronac) > 7) {
            $idPronac = Seguranca::dencrypt($idPronac);
        }

        //VERIFICA SE JA POSSUI OS PLANOS DE DIVULGA&ccedil;AO NA TABELA tPlanoDivulgacao (READEQUACAO), SE N&atilde;O TIVER, COPIA DA ORIGINAL, E DEPOIS INCLUI O ITEM DESEJADO.
        $tbPlanoDivulgacao = new tbPlanoDivulgacao();
        $readequacaoPDD = $tbPlanoDivulgacao->buscar(array('idPronac=?'=>$idPronac, 'stAtivo=?'=>'S'));
        $planosAtivos = $tbPlanoDivulgacao->buscarPlanosDivulgacaoReadequacao($idPronac);

        if(count($readequacaoPDD)==0){
            $planosCopiados = array();
            foreach ($planosAtivos as $value){
                $planosCopiados['idReadequacao'] = NULL;
                $planosCopiados['idPeca'] = $value->idPeca;
                $planosCopiados['idVeiculo'] = $value->idVeiculo;
                $planosCopiados['tpSolicitacao'] = 'N';
                $planosCopiados['stAtivo'] = 'S';
                $planosCopiados['idPronac'] = $idPronac;
                $tbPlanoDivulgacao->inserir($planosCopiados);
            }
        }

        $verificaPlanoRepetido = $tbPlanoDivulgacao->buscar(array('idPronac=?'=>$idPronac, 'stAtivo=?'=>'S', 'idPeca=?'=>$_POST['newPeca'], 'idVeiculo=?'=>$_POST['newVeiculo']));
        if(count($verificaPlanoRepetido)==0){
            /* DADOS DO PLANO PARA INCLUS&atilde;O DA READEQUA&ccedil;&atilde;O */
            $dadosInclusao = array();
            $dadosInclusao['idReadequacao'] = NULL;
            $dadosInclusao['idPeca'] = $_POST['newPeca'];
            $dadosInclusao['idVeiculo'] = $_POST['newVeiculo'];
            $dadosInclusao['tpSolicitacao'] = 'I';
            $dadosInclusao['stAtivo'] = 'S';
            $dadosInclusao['idPronac'] = $idPronac;
            $insert = $tbPlanoDivulgacao->inserir($dadosInclusao);
            if($insert){
                //$jsonEncode = json_encode($dadosPlanilha);
                $this->_helper->json(array('resposta'=>true));
        } else {
                $this->_helper->json(array('resposta'=>false));
        }
        } else {
            $msg = utf8_encode('Esse plano de divulga&ccedil;&atilde;o j&aacute; foi cadastrado!');
            $this->_helper->json(array('resposta'=>false, 'msg'=>$msg));
    }
        $this->_helper->viewRenderer->setNoRender(TRUE);
    }

    /*
     * Criada em 28/03/2014
     * @author: Jefferson Alessandro - jeffersonassilva@gmail.com
     * Esse fun&ccedil;&atilde;o &eacute; usada pelo proponente para solicitar a exclus&atilde;o de um plano de divulga&ccedil;&atilde;o.
     */
    public function excluirPlanoDeDivulgacaoAction() {
        $this->_helper->layout->disableLayout();
        $idPlanoDivulgacao = $this->_request->getParam("idPlanoDivulgacao");
        $idPronac = $this->_request->getParam("idPronac");
        if (strlen($idPronac) > 7) {
            $idPronac = Seguranca::dencrypt($idPronac);
        }

        //VERIFICA SE JA POSSUI OS PLANOS DE DIVULGA&ccedil;&atilde;O NA TABELA tPlanoDivulgacao (READEQUACAO), SE N&atilde;O TIVER, COPIA DA ORIGINAL, E DEPOIS INCLUI O ITEM DESEJADO.
        $tbPlanoDivulgacao = new tbPlanoDivulgacao();
        $readequacaoPDD = $tbPlanoDivulgacao->buscar(array('idPronac=?'=>$idPronac, 'stAtivo=?'=>'S'));
        $planosAtivos = $tbPlanoDivulgacao->buscarPlanosDivulgacaoReadequacao($idPronac);

        if(count($readequacaoPDD)==0){
            $planosCopiados = array();
            foreach ($planosAtivos as $value){
                $planosCopiados['idReadequacao'] = NULL;
                $planosCopiados['idPeca'] = $value->idPeca;
                $planosCopiados['idVeiculo'] = $value->idVeiculo;
                $planosCopiados['tpSolicitacao'] = 'N';
                $planosCopiados['stAtivo'] = 'S';
                $planosCopiados['idPronac'] = $idPronac;
                $tbPlanoDivulgacao->inserir($planosCopiados);
            }
        }

        /* DADOS DO ITEM PARA EXCLUSAO LÓGICA DO ITEM DA READEQUACAO */
        $dados = array();
        $dados['tpSolicitacao'] = 'E';

        $itemPDD = $tbPlanoDivulgacao->buscar(array('idPlanoDivulgacao=?'=>$idPlanoDivulgacao))->current();
        if($itemPDD){
            if($itemPDD->tpSolicitacao == 'I'){
                $exclusaoLogica = $tbPlanoDivulgacao->delete(array('idPlanoDivulgacao = ?'=>$idPlanoDivulgacao));
            } else {
                $where = "stAtivo = 'S' AND idPlanoDivulgacao = $idPlanoDivulgacao";
                $exclusaoLogica = $tbPlanoDivulgacao->update($dados, $where);
            }
        } else {
            $PlanoDivulgacao = new PlanoDeDivulgacao();
            $itemPDD = $PlanoDivulgacao->find(array('idPlanoDivulgacao=?'=>$idPlanoDivulgacao))->current();
            $dadosArray = array(
                'idPeca =?' => $itemPDD->idPeca,
                'idVeiculo =?' => $itemPDD->idVeiculo,
                'idPronac =?' => $idPronac,
                'stAtivo =?' => 'S',
            );
            $itemPDD = $tbPlanoDivulgacao->buscar($dadosArray)->current();
            $where = "stAtivo = 'S' AND idPlanoDivulgacao = $itemPDD->idPlanoDivulgacao";
            $exclusaoLogica = $tbPlanoDivulgacao->update($dados, $where);
        }

        if($exclusaoLogica){
            //$jsonEncode = json_encode($dadosPlanilha);
            $this->_helper->json(array('resposta'=>true));

        } else {
            $this->_helper->json(array('resposta'=>false));
        }
        $this->_helper->viewRenderer->setNoRender(TRUE);
    }

    /*
     * Criada em 31/03/2014
     * @author: Jefferson Alessandro - jeffersonassilva@gmail.com
     * Essa fun&ccedil;&atilde;o &eacute; usada para carregar os dados do planos de distribui&ccedil;&atilde;o do projeto.
     */
    public function carregarPlanosDeDistribuicaoAction(){
        $this->_helper->layout->disableLayout(); // desabilita o Zend_Layout
        $GrupoAtivo = new Zend_Session_Namespace('GrupoAtivo'); // cria a sess&atilde;o com o grupo ativo
        $this->view->idPerfil = $GrupoAtivo->codGrupo;

        $idPronac = $this->_request->getParam("idPronac");
        if (strlen($idPronac) > 7) {
            $idPronac = Seguranca::dencrypt($idPronac);
        }

        $tbPlanoDistribuicao = new tbPlanoDistribuicao();
        $planosDistribuicao = $tbPlanoDistribuicao->buscarPlanosDistribuicaoReadequacao($idPronac, 'tbPlanoDistribuicao');

        if(count($planosDistribuicao)==0){
            $planosDistribuicao = $tbPlanoDistribuicao->buscarPlanosDistribuicaoReadequacao($idPronac, 'PlanoDistribuicaoProduto');
        }

        $Produtos = new Produto();
        $produtos = $Produtos->buscar(array('stEstado=?'=>0), array('Descricao'));

        $Verificacao = new Verificacao();
        $posicoesLogomarca = $Verificacao->buscar(array('idTipo=?'=>3), array('Descricao'));

        $Area = new Area();
        $areas = $Area->buscar(array('Codigo != ?'=>7), array('Descricao'));

        $get = Zend_Registry::get('get');
        $link = isset($get->link) ? true : false;

        $this->montaTela(
            'readequacoes/carregar-planos-de-distribuicao.phtml',
            array(
                'idPronac' => $idPronac,
                'planosDistribuicao' => $planosDistribuicao,
                'produtos' => $produtos,
                'posicoesLogomarca' => $posicoesLogomarca,
                'areas' => $areas,
                'link' => $link
            )
        );
    }

    /*
     * Criada em 27/03/2014
     * @author: Jefferson Alessandro - jeffersonassilva@gmail.com
     * Essa fun&ccedil;&atilde;o &eacute; usada para carregar os dados dos planos de divulga&ccedil;&atilde;o do projeto.
     */
    public function carregarPlanosDeDistribuicaoReadequacoesAction() {
        $this->_helper->layout->disableLayout(); // desabilita o Zend_Layout
        $GrupoAtivo = new Zend_Session_Namespace('GrupoAtivo'); // cria a sess&atilde;o com o grupo ativo
        $this->view->idPerfil = $GrupoAtivo->codGrupo;

        $idPronac = $this->_request->getParam("idPronac");
        $idReadequacao = $this->_request->getParam("idReadequacao");
        if (strlen($idPronac) > 7) {
            $idPronac = Seguranca::dencrypt($idPronac);
        }

        $tbPlanoDistribuicao = new tbPlanoDistribuicao();
        $planosDistribuicao = $tbPlanoDistribuicao->buscarPlanosDistribuicaoConsolidadoReadequacao($idReadequacao);

        $tbReadequacao = new tbReadequacao();
        $dadosReadequacao = $tbReadequacao->buscar(array('idReadequacao=?'=>$idReadequacao))->current();
        $siEncaminhamento = $dadosReadequacao->siEncaminhamento;

        $get = Zend_Registry::get('get');
        $link = isset($get->link) ? true : false;

        $this->montaTela(
            'readequacoes/carregar-planos-de-distribuicao.phtml',
            array(
                'idPronac' => $idPronac,
                'planosDeDistribuicao' => $planosDistribuicao,
                'link' => $link,
                'idReadequacao' => $idReadequacao,
                'siEncaminhamento' => $siEncaminhamento
            )
        );
    }

    /*
     * Criada em 31/03/2014
     * @author: Jefferson Alessandro - jeffersonassilva@gmail.com
     * Essa fun&ccedil;&atilde;o &eacute; usada para que o proponente fa&ccedil;a uma solicita&ccedil;&atilde;o de inclus&atilde;o de plano de distribui&ccedil;&atilde;o
     */
    public function incluirPlanosDeDistribuicaoAction() {
        $this->_helper->layout->disableLayout();
        $idPronac = $this->_request->getParam("idPronac");
        if (strlen($idPronac) > 7) {
            $idPronac = Seguranca::dencrypt($idPronac);
        }

        //VERIFICA SE JA POSSUI OS PLANOS DE DISTRIBUI&ccedil;&atilde;O NA TABELA tbPlanoDistribuicao (READEQUACAO), SE N&atilde;O TIVER, COPIA DA ORIGINAL, E DEPOIS INCLUI O ITEM DESEJADO.
        $tbPlanoDistribuicao = new tbPlanoDistribuicao();
        $readequacaoPDDist = $tbPlanoDistribuicao->buscar(array('idPronac=?'=>$idPronac, 'stAtivo=?'=>'S'));
        $planosAtivos = $tbPlanoDistribuicao->buscarPlanosDistribuicaoReadequacao($idPronac);

        if(count($readequacaoPDDist)==0){
            $planosCopiados = array();
            foreach ($planosAtivos as $value){
                $planosCopiados['idReadequacao'] = NULL;
                $planosCopiados['idProduto'] = $value->idProduto;
                $planosCopiados['cdArea'] = $value->idArea;
                $planosCopiados['cdSegmento'] = $value->idSegmento;
                $planosCopiados['idPosicaoLogo'] = $value->idPosicaoDaLogo;
                $planosCopiados['qtProduzida'] = $value->QtdeProduzida;
                $planosCopiados['qtPatrocinador'] = $value->QtdePatrocinador;
                $planosCopiados['qtOutros'] = $value->QtdeOutros;
                $planosCopiados['qtProponente'] = $value->QtdeProponente;
                $planosCopiados['qtVendaNormal'] = $value->QtdeVendaNormal;
                $planosCopiados['qtVendaPromocional'] = $value->QtdeVendaPromocional;
                $planosCopiados['vlUnitarioNormal'] = $value->PrecoUnitarioNormal;
                $planosCopiados['vlUnitarioPromocional'] = $value->PrecoUnitarioPromocional;
                $planosCopiados['stPrincipal'] = $value->stPrincipal;
                $planosCopiados['tpSolicitacao'] = 'N';
                $planosCopiados['stAtivo'] = 'S';
                $planosCopiados['idPronac'] = $idPronac;
                $tbPlanoDistribuicao->inserir($planosCopiados);
            }
        }

        $verificaPlanoRepetido = $tbPlanoDistribuicao->buscar(array('idPronac=?'=>$idPronac, 'stAtivo=?'=>'S', 'idProduto=?'=>$_POST['newPlanoDistribuicao']));
        if(count($verificaPlanoRepetido)==0){
            $QtdeProduzida = $_POST['newQntdNormal']+$_POST['newQntdPromocional']+$_POST['newQntdPatrocinador']+$_POST['newQntdPopulacaoBaixaRenda']+$_POST['newQntdDivulgacao'];
            $preconormal = str_replace(",", ".", str_replace(".", "", $_POST['newVlNormal']));
            $precopromocional = str_replace(",", ".", str_replace(".", "", $_POST['newVlPromocional']));

            /* DADOS DO PLANO PARA INCLUS&atilde;O DA READEQUA&ccedil;&atilde;O */
            $dadosInclusao = array();
            $dadosInclusao['idReadequacao'] = NULL;
            $dadosInclusao['idProduto'] = $_POST['newPlanoDistribuicao'];
            $dadosInclusao['cdArea'] = $_POST['newArea'];
            $dadosInclusao['cdSegmento'] = $_POST['newSegmento'];
            $dadosInclusao['idPosicaoLogo'] = $_POST['newPosicaoLogomarca'];
            $dadosInclusao['qtProduzida'] = $QtdeProduzida;
            $dadosInclusao['qtPatrocinador'] = $_POST['newQntdPatrocinador'];
            $dadosInclusao['qtProponente'] = $_POST['newQntdDivulgacao'];
            $dadosInclusao['qtOutros'] = $_POST['newQntdPopulacaoBaixaRenda'];
            $dadosInclusao['qtVendaNormal'] = $_POST['newQntdNormal'];
            $dadosInclusao['qtVendaPromocional'] = $_POST['newQntdPromocional'];
            $dadosInclusao['vlUnitarioNormal'] = $preconormal;
            $dadosInclusao['vlUnitarioPromocional'] = $precopromocional;
            $dadosInclusao['stPrincipal'] = 0;
            $dadosInclusao['tpSolicitacao'] = 'I';
            $dadosInclusao['stAtivo'] = 'S';
            $dadosInclusao['idPronac'] = $idPronac;
            $insert = $tbPlanoDistribuicao->inserir($dadosInclusao);

            if($insert){
                //$jsonEncode = json_encode($dadosPlanilha);
                $this->_helper->json(array('resposta'=>true));
            } else {
                $this->_helper->json(array('resposta'=>false));
            }
        } else {
            $msg = utf8_encode('Esse plano de distribui&ccedil;&atilde;o j&aacute; foi cadastrado!');
            $this->_helper->json(array('resposta'=>false, 'msg'=>$msg));
        }
        $this->_helper->viewRenderer->setNoRender(TRUE);
    }

    /*
     * Criada em 28/03/2014
     * @author: Jefferson Alessandro - jeffersonassilva@gmail.com
     * Esse fun&ccedil;&atilde;o &eacute; usada pelo proponente para solicitar a exclus&atilde;o de um plano de distribui&ccedil;&atilde;o.
     */
    public function excluirPlanoDeDistribuicaoAction() {
        $this->_helper->layout->disableLayout();
        $idPlanoDistribuicao = $this->_request->getParam("idPlanoDistribuicao");
        $idPronac = $this->_request->getParam("idPronac");
        if (strlen($idPronac) > 7) {
            $idPronac = Seguranca::dencrypt($idPronac);
        }

        //VERIFICA SE JA POSSUI OS PLANOS DE DISTRIBUI&ccedil;&atilde;O NA TABELA tbPlanoDistribuicao (READEQUACAO), SE N&atilde;O TIVER, COPIA DA ORIGINAL, E DEPOIS INCLUI O ITEM DESEJADO.
        $tbPlanoDistribuicao = new tbPlanoDistribuicao();
        $readequacaoPDDist = $tbPlanoDistribuicao->buscar(array('idPronac=?'=>$idPronac, 'stAtivo=?'=>'S'));
        $planosAtivos = $tbPlanoDistribuicao->buscarPlanosDistribuicaoReadequacao($idPronac);

        if(count($readequacaoPDDist)==0){
            $planosCopiados = array();
            foreach ($planosAtivos as $value){
                $planosCopiados['idReadequacao'] = NULL;
                $planosCopiados['idProduto'] = $value->idProduto;
                $planosCopiados['cdArea'] = $value->idArea;
                $planosCopiados['cdSegmento'] = $value->idSegmento;
                $planosCopiados['idPosicaoLogo'] = $value->idPosicaoDaLogo;
                $planosCopiados['qtProduzida'] = $value->QtdeProduzida;
                $planosCopiados['qtPatrocinador'] = $value->QtdePatrocinador;
                $planosCopiados['qtOutros'] = $value->QtdeOutros;
                $planosCopiados['qtProponente'] = $value->QtdeProponente;
                $planosCopiados['qtVendaNormal'] = $value->QtdeVendaNormal;
                $planosCopiados['qtVendaPromocional'] = $value->QtdeVendaPromocional;
                $planosCopiados['vlUnitarioNormal'] = $value->PrecoUnitarioNormal;
                $planosCopiados['vlUnitarioPromocional'] = $value->PrecoUnitarioPromocional;
                $planosCopiados['stPrincipal'] = $value->stPrincipal;
                $planosCopiados['tpSolicitacao'] = 'N';
                $planosCopiados['stAtivo'] = 'S';
                $planosCopiados['idPronac'] = $idPronac;
                $tbPlanoDistribuicao->inserir($planosCopiados);
            }
        }

        /* DADOS DO ITEM PARA EXCLUSAO LÓGICA DO ITEM DA READEQUACAO */
        $dados = array();
        $dados['tpSolicitacao'] = 'E';

        $itemPDDist = $tbPlanoDistribuicao->buscar(array('idPlanoDistribuicao=?'=>$idPlanoDistribuicao))->current();
        if($itemPDDist){
            if($itemPDDist->tpSolicitacao == 'I'){
                $exclusaoLogica = $tbPlanoDistribuicao->delete(array('idPlanoDistribuicao = ?'=>$idPlanoDistribuicao));
            } else {
                $where = "stAtivo = 'S' AND idPlanoDistribuicao = $idPlanoDistribuicao";
                $exclusaoLogica = $tbPlanoDistribuicao->update($dados, $where);
            }
        } else {
            $PlanoDistribuicao = new PlanoDistribuicao();
            $itemPDDist = $PlanoDistribuicao->find(array('idPlanoDistribuicao=?'=>$idPlanoDistribuicao))->current();
            $dadosArray = array(
                'idProduto =?' => $itemPDDist->idProduto,
                'idPronac =?' => $idPronac,
                'stAtivo =?' => 'S',
            );
            $itemPDDist = $tbPlanoDistribuicao->buscar($dadosArray)->current();
            $where = "stAtivo = 'S' AND idPlanoDistribuicao = $itemPDDist->idPlanoDistribuicao";
            $exclusaoLogica = $tbPlanoDistribuicao->update($dados, $where);
        }

        if($exclusaoLogica){
            //$jsonEncode = json_encode($dadosPlanilha);
            $this->_helper->json(array('resposta'=>true));

        } else {
            $this->_helper->json(array('resposta'=>false));
        }
        $this->_helper->viewRenderer->setNoRender(TRUE);
    }

    /*
     * Criada em 14/03/2014
     * @author: Jefferson Alessandro - jeffersonassilva@gmail.com
     * Essa fun&ccedil;&atilde;o para incluir uma solicita&ccedil;&atilde;o de readequa&ccedil;&atilde;o.
     */
    public function incluirSolicitacaoReadequacaoAction() {
        //FUN&ccedil;&atilde;O ACESSADA SOMENTE PELO PROPONENTE.
        if($this->idPerfil != 1111){
            parent::message("Voc&ecirc; n&atilde;o tem permiss&atilde;o para acessar essa &aacute;rea do sistema!", "principal", "ALERT");
        }

        $idPronac = $this->_request->getParam("idPronac");
        if (strlen($idPronac) > 7) {
            $idPronac = Seguranca::dencrypt($idPronac);
        }

        $idReadequacao = filter_var($this->_request->getParam("idReadequacao"), FILTER_SANITIZE_NUMBER_INT);
        $idTipoReadequacao = filter_var($this->_request->getParam("tipoReadequacao"), FILTER_SANITIZE_NUMBER_INT);

        $tbReadequacao = new tbReadequacao();
        $busca = array();

        if($idTipoReadequacao == 2){ //Planilha Or&ccedil;ament&aacute;ria
            $tbPlanilhaAprovacao = new tbPlanilhaAprovacao();
            $planilhaReadequada = $tbPlanilhaAprovacao->buscar(array('IdPRONAC = ?'=>$idPronac, 'tpPlanilha = ?'=>'SR', 'idReadequacao= ?' => $idReadequacao));
            if(count($planilhaReadequada)==0){
                parent::message('N&atilde;o houve nenhuma altera&ccedil;&atilde;o na planilha or&ccedil;ament&aacute;ria do projeto!', "readequacoes/planilha-orcamentaria?idPronac=".Seguranca::encrypt($idPronac), "ERROR");
            }
        } else if($idTipoReadequacao == 9){ //Local de Realiza&ccedil;&atilde;o
            $tbAbrangencia = new tbAbrangencia();
            $locaisReadequados = $tbAbrangencia->buscar(array('idPronac = ?'=>$idPronac, 'idReadequacao is null'=>''));
            if(count($locaisReadequados)==0){
                parent::message('N&atilde;o houve nenhuma altera&ccedil;&atilde;o nos locais de realiza&ccedil;&atilde;o do projeto!', "readequacoes/index?idPronac=".Seguranca::encrypt($idPronac), "ERROR");
            }
        } else if($idTipoReadequacao == 11){ //Planos de Distribui&ccedil;&atilde;o
            $tbPlanoDistribuicao = new tbPlanoDistribuicao();
            $planosReadequados = $tbPlanoDistribuicao->buscar(array('idPronac = ?'=>$idPronac, 'idReadequacao is null'=>''));
            if(count($planosReadequados)==0){
                parent::message('N&atilde;o houve nenhuma altera&ccedil;&atilde;o nos planos de distribui&ccedil;&atilde;o do projeto!', "readequacoes/index?idPronac=".Seguranca::encrypt($idPronac), "ERROR");
            }
        } else if($idReadequacao == 14){ //Planos de Divulga&ccedil;&atilde;o
            $tbPlanoDivulgacao = new tbPlanoDivulgacao();
            $planosReadequados = $tbPlanoDivulgacao->buscar(array('idPronac = ?'=>$idPronac, 'idReadequacao is null'=>''));
            if(count($planosReadequados)==0){
                parent::message('N&atilde;o houve nenhuma altera&ccedil;&atilde;o nos planos de divulga&ccedil;&atilde;o do projeto!', "readequacoes/index?idPronac=".Seguranca::encrypt($idPronac), "ERROR");
            }
        }

        try {

            $tbArquivoDAO = new tbArquivo();
            $tbArquivoImagemDAO = new tbArquivoImagem();
            $tbDocumentoDAO = new tbDocumento();

            // ==================== Dados do arquivo de upload ===============================
            $arquivoNome = $_FILES['arquivo']['name']; // nome
            $arquivoTemp = $_FILES['arquivo']['tmp_name']; // nome tempor&aacute;rio
            $arquivoTipo = $_FILES['arquivo']['type']; // tipo
            $arquivoTamanho = $_FILES['arquivo']['size']; // tamanho

            $idDocumento = null;
            if(!empty($arquivoTemp)){
                $arquivoExtensao = Upload::getExtensao($arquivoNome); // extens&atilde;o
                $arquivoBinario = Upload::setBinario($arquivoTemp); // bin&aacute;rio
                $arquivoHash = Upload::setHash($arquivoTemp); // hash

                if ($arquivoExtensao != 'pdf' && $arquivoExtensao != 'PDF') // extens&atilde;o do arquivo
                {
                    throw new Exception('A extens&atilde;o do arquivo &eacute; inv&aacute;lida, envie somente arquivos <strong>.pdf</strong>!');
                }
                else if ($arquivoTamanho > 5242880) // tamanho m&aacute;ximo do arquivo: 5MB
                {
                    throw new Exception('O arquivo n&atilde;o pode ser maior do que <strong>5MB</strong>!');
                }

                $objAcesso = new Acesso();
                $dadosArquivo = array(
                    'nmArquivo' => $arquivoNome,
                    'sgExtensao' => $arquivoExtensao,
                    'dsTipoPadronizado' => $arquivoTipo,
                    'nrTamanho' => $arquivoTamanho,
                    'dtEnvio' => $objAcesso->getExpressionDate(),
                    'dsHash' => $arquivoHash,
                    'stAtivo' => 'A'
                );
                $idArquivo = $tbArquivoDAO->inserir($dadosArquivo);

                // ==================== Insere na Tabela tbArquivoImagem ===============================
                $dadosBinario = array(
                    'idArquivo' => $idArquivo,
                    'biArquivo' => new Zend_Db_Expr("CONVERT(varbinary(MAX), {$arquivoBinario})")
                );
                $idArquivo = $tbArquivoImagemDAO->inserir($dadosBinario);

                // ==================== Insere na Tabela tbDocumento ===============================
                $dados = array(
                        'idTipoDocumento' => 38,
                        'idArquivo' => $idArquivo,
                        'dsDocumento' => 'Solicita&ccedil;&atilde;o de Readequa&ccedil;&atilde;o',
                        'dtEmissaoDocumento' => NULL,
                        'dtValidadeDocumento' => NULL,
                        'idTipoEventoOrigem' => NULL,
                        'nmTitulo' => 'Readequacao'
                );

                $idDocumento = $tbDocumentoDAO->inserir($dados);
                $idDocumento = $idDocumento['idDocumento'];
            }

            $auth = Zend_Auth::getInstance(); // pega a autentica&ccedil;&atilde;o
            $tblAgente = new Agente_Model_DbTable_Agentes();
            $rsAgente = $tblAgente->buscar(array('CNPJCPF=?'=>$auth->getIdentity()->Cpf))->current();

            if ($idTipoReadequacao == 2) {
                $descJustificativa = $this->_request->getParam('descJustificativa');

                // se for tipo planilha or&ccedil;amentaria, somente atualiza tbReadequacao
                $dadosReadequacao = array();
                $dadosReadequacao['dsJustificativa'] = $descJustificativa;

                if ($idDocumento) {
                    $dadosReadequacao['idDocumento'] = $idDocumento;
                }

                $readequacaoWhere = "idReadequacao = $idReadequacao";
                $tbReadequacao->update($dadosReadequacao, $readequacaoWhere);

            } else {
                // outros casos: insere tbReadequacao e altera tbPlanilhaAprovacao
                $dados = array();
                $dados['idPronac'] = $idPronac;
                $dados['idTipoReadequacao'] = $idTipoReadequacao;
                $dados['dtSolicitacao'] = $objAcesso->getExpressionDate();
                $dados['idSolicitante'] = $rsAgente->idAgente;
                $dados['dsJustificativa'] = $_POST['descJustificativa'];
                $dados['dsSolicitacao'] = $_POST['descSolicitacao'];
                $dados['idDocumento'] = $idDocumento;
                $dados['siEncaminhamento'] = 12;
                $dados['stEstado'] = 0;
                $idReadequacao = $tbReadequacao->inserir($dados);

                $tbPlanilhaAprovacao = new tbPlanilhaAprovacao();
                $dadosPlanilha = array();
                $dadosPlanilha['idReadequacao'] = $idReadequacao;
                $wherePlanilha = "IdPRONAC = $idPronac AND tpPlanilha = 'SR' AND idReadequacao is null";
                $tbPlanilhaAprovacao->update($dadosPlanilha, $wherePlanilha);
            }

            if ($idReadequacao && $idTipoReadequacao != 2) {
                $acaoErro = 'cadastrar';

                parent::message("Solicita&ccedil;&atilde;o cadastrada com sucesso!", "readequacoes/index?idPronac=".Seguranca::encrypt($idPronac), "CONFIRM");
            }  else if ($idReadequacao && $idTipoReadequacao == 2) {
                $acaoErro = 'alterar';

                parent::message("Solicita&ccedil;&atilde;o alterada com sucesso!", "readequacoes/planilha-orcamentaria?idPronac=".Seguranca::encrypt($idPronac), "CONFIRM");
            } else {
                throw new Exception("Erro ao $acaoErro a readequa&ccedil;&atilde;o!");
            }
        } // fecha try
        catch(Exception $e) {
            parent::message($e->getMessage(), "readequacoes?idPronac=".Seguranca::encrypt($idPronac), "ERROR");
        }
    }

    /*
     * Criada em 14/03/2014
     * @author: Jefferson Alessandro - jeffersonassilva@gmail.com
     * Essa fun&ccedil;&atilde;o para excluir uma solicita&ccedil;&atilde;o de readequa&ccedil;&atilde;o.
     */
    public function excluirSolicitacaoReadequacaoAction() {
        //FUN&ccedil;&atilde;O ACESSADA SOMENTE PELO PROPONENTE.
        if($this->idPerfil != 1111){
            parent::message("Voc&ecirc; n&atilde;o tem permiss&atilde;o para acessar essa &aacute;rea do sistema!", "principal", "ALERT");
        }

        $idPronac = $this->_request->getParam("idPronac");
        if (strlen($idPronac) > 7) {
            $idPronac = Seguranca::dencrypt($idPronac);
        }

        $get = Zend_Registry::get('get');
        try {

            $tbReadequacao = new tbReadequacao();
            $dados = $tbReadequacao->buscar(array('idReadequacao =?'=>$get->idReadequacao))->current();

            if(!empty($dados->idDocumento)){
                $tbDocumento = new tbDocumento();
                $dadosArquivo = $tbDocumento->buscar(array('idDocumento =?'=>$dados->idDocumento))->current();

                if($dadosArquivo){
//                    $vwAnexarComprovantes = new vwAnexarComprovantes();
//                    $x = $vwAnexarComprovantes->excluirArquivo($dadosArquivo->idArquivo);

                    $tbDocumento = new tbDocumento();
                    $tbDocumento->excluir("idArquivo = {$dadosArquivo->idArquivo} and idDocumento= {$dados->idDocumento} ");

                    $tbArquivoImagem = new tbArquivoImagem();
                    $tbArquivoImagem->excluir("idArquivo =  {$dadosArquivo->idArquivo} ");

                    $tbArquivo = new tbArquivo();
                    $tbArquivo->excluir("idArquivo = {$dadosArquivo->idArquivo} ");
                }
            }

            //Se for readequa&ccedil;&atilde;o de planilha or&ccedil;ament&aacute;ria, exclui a planilha SR gerada.
            if($dados->idTipoReadequacao == 2){
                $tbPlanilhaAprovacao = new tbPlanilhaAprovacao();

                $tbPlanilhaAprovacao->delete(array('IdPRONAC = ?'=>$idPronac, 'tpPlanilha = ?'=>'SR', 'idReadequacao = ?'=>$get->idReadequacao));
            }

            //Se for readequa&ccedil;&atilde;o de local de realiza&ccedil;&atilde;o, exclui os registros na tbAbrangencia.
            if($dados->idTipoReadequacao == 9){
                $tbAbrangencia = new tbAbrangencia();
                $tbAbrangencia->delete(array('idPronac = ?'=>$idPronac, 'stAtivo = ?'=>'S'));
            }

            //Se for readequa&ccedil;&atilde;o de plano de distribui&ccedil;&atilde;o, exclui os registros na tbPlanoDistribuicao.
            if($dados->idTipoReadequacao == 11){
                $tbPlanoDistribuicao = new tbPlanoDistribuicao();
                $tbPlanoDistribuicao->delete(array('idPronac = ?'=>$idPronac, 'stAtivo = ?'=>'S'));
            }

            //Se for readequa&ccedil;&atilde;o de plano de divulga&ccedil;&atilde;o, exclui os registros na tbPlanoDivulgacao.
            if($dados->idTipoReadequacao == 14){
                $tbPlanoDivulgacao = new tbPlanoDivulgacao();
                $tbPlanoDivulgacao->delete(array('idPronac = ?'=>$idPronac, 'stAtivo = ?'=>'S'));
            }

            $exclusao = $tbReadequacao->delete(array('idPronac =?'=>$idPronac, 'idReadequacao =?'=>$get->idReadequacao));

            if ($exclusao) {
                if ($dados->idTipoReadequacao == 2) {
                    parent::message('Tipo de readequa&ccedil;&atilde;o exclu&iacute;da com sucesso!', "readequacoes/planilha-orcamentaria?idPronac=".Seguranca::encrypt($idPronac), "CONFIRM");
                } else {
                    parent::message('Tipo de readequa&ccedil;&atilde;o exclu&iacute;da com sucesso!', "readequacoes/index?idPronac=".Seguranca::encrypt($idPronac), "CONFIRM");
                }
            } else {
                throw new Exception("Erro ao excluir o tipo de readequa&ccedil;&atilde;o!");
            }
        } // fecha try
        catch(Exception $e) {
            parent::message($e->getMessage(), "readequacoes?idPronac=".Seguranca::encrypt($idPronac), "ERROR");
        }
    }

    public function finalizarSolicitacaoReadequacaoAction() {
        //FUN&ccedil;&atilde;O ACESSADA SOMENTE PELO PROPONENTE.
        if($this->idPerfil != 1111){
            parent::message("Voc&ecirc; n&atilde;o tem permiss&atilde;o para acessar essa &aacute;rea do sistema!", "principal", "ALERT");
        }

        $idPronac = $this->_request->getParam("idPronac");
        if (strlen($idPronac) > 7) {
            $idPronac = Seguranca::dencrypt($idPronac);
        }

        try {
            $tbReadequacao = new tbReadequacao();
            $readequacoes = $tbReadequacao->buscar(array('idPronac=?'=>$idPronac, 'siEncaminhamento=?'=>12,'stEstado=?'=>0));

            $dados = array();
            $dados['siEncaminhamento'] = 1;
            $where = "idPronac = $idPronac AND siEncaminhamento = 12 AND stEstado = 0";
            $atualizar = $tbReadequacao->update($dados, $where);

            foreach ($readequacoes as $r) {
                if($r->idTipoReadequacao == 9){
                    $tbAbrangencia = new tbAbrangencia();
                    $dadosAb = array();
                    $dadosAb['idReadequacao'] = $r->idReadequacao;
                    $whereAb = "idPronac = $idPronac AND stAtivo = 'S' AND idReadequacao is null";
                    $tbAbrangencia->update($dadosAb, $whereAb);

                } else if($r->idTipoReadequacao == 11){
                    $tbPlanoDistribuicao = new tbPlanoDistribuicao();
                    $dadosPDDist = array();
                    $dadosPDDist['idReadequacao'] = $r->idReadequacao;
                    $wherePDDist = "idPronac = $idPronac AND stAtivo = 'S' AND idReadequacao is null";
                    $tbPlanoDistribuicao->update($dadosPDDist, $wherePDDist);

                } else if($r->idTipoReadequacao == 14){
                    $tbPlanoDivulgacao = new tbPlanoDivulgacao();
                    $dadosPDD = array();
                    $dadosPDD['idReadequacao'] = $r->idReadequacao;
                    $wherePDD = "idPronac = $idPronac AND stAtivo = 'S' AND idReadequacao is null";
                    $tbPlanoDivulgacao->update($dadosPDD, $wherePDD);
                }
            }
            if($atualizar) {
                //altera a situa&ccedil;&atilde;o do projeto
                //$alterarSituacao = ProjetoDAO::alterarSituacao($idPronac, 'D20');
                parent::message('Solicita&ccedil;&atilde;o enviada com sucesso!', "consultardadosprojeto/index?idPronac=".Seguranca::encrypt($idPronac), "CONFIRM");
            } // fecha if
            else {
                throw new Exception("Erro ao finalizar as readequa&ccedil;&otilde;es!");
            }
        } // fecha try
        catch(Exception $e) {
            parent::message($e->getMessage(), "readequacoes?idPronac=".Seguranca::encrypt($idPronac), "ERROR");
        }
    }

    /**
     * painelAction - Lista dos Projetos em nas situa&ccedil;&otilde;es: Aguardando An&aacute;lise, Em
     * Analise, Analisados.
     *
     * @since Alterada em 30/06/16
     * @author wouerner <wouerner@gmail.com>
     * @author fernao <fernao.lara@cultura.gov.br>
     * @access public
     * @return void
     */
    public function painelAction()
    {
        //FUN&ccedil;&atilde;O ACESSADA SOMENTE PELOS PERFIS DE COORD. GERAL DE ACOMPANHAMENTO E COORD. DE ACOMPANHAMENTO.
        if($this->idPerfil != 122 && $this->idPerfil != 123 && $this->idPerfil != 151 && $this->idPerfil != 148){
            parent::message("Voc&ecirc; n&atilde;o tem permiss&atilde;o para acessar essa &aacute;rea do sistema!", "principal", "ALERT");
        }

        //DEFINE PARAMETROS DE ORDENACAO / QTDE. REG POR PAG. / PAGINACAO
        if($this->_request->getParam("qtde")) {
            $this->intTamPag = $this->_request->getParam("qtde");
        }
        $order = array();

        //==== parametro de ordenacao  ======//
        if($this->_request->getParam("ordem")) {
            $ordem = $this->_request->getParam("ordem");
            if($ordem == "DESC") {
                $novaOrdem = "ASC";
            } else {
                $novaOrdem = "DESC";
            }
        } else {
            $campo = 8;  //qtDiasAguardandoDistribuicao default
            $ordem = "DESC";
            $novaOrdem = "ASC";
        }

        //==== campo de ordenacao  ======//
        if($this->_request->getParam("campo")) {
            $campo = $this->_request->getParam("campo");
            $order = array($campo." ".$ordem);
            $ordenacao = "&campo=".$campo."&ordem=".$ordem;

        } else {
            // ordena&ccedil;&atilde;o padr&atilde;o
            $order = array($campo." ".$ordem);
            $ordenacao = null;
        }

        $pag = 1;
        $get = Zend_Registry::get('get');
        if (isset($get->pag)) $pag = $get->pag;
        $inicio = ($pag>1) ? ($pag-1)*$this->intTamPag : 0;

        /* ================== PAGINACAO ======================*/
        $where = array();

        $filtro = null;
        if ($this->_request->getParam('tipoFiltro')) {
            $filtro = $this->_request->getParam('tipoFiltro');
        } else {
            $filtro = 'aguardando_distribuicao';
        }

        $this->view->filtro = $filtro;

        // lista apenas readequa&ccedil;&otilde;es do órg&atilde;o atual
        if ($filtro == 'aguardando_distribuicao') {
            $where['idOrgao = ?'] = $this->idOrgao;
        } else {
            $where['idOrgaoOrigem = ?'] = $this->idOrgao;
        }

        if ($this->_request->getParam('pronac')) {
            $where['a.PRONAC = ?'] = $this->_request->getParam('pronac');
            $this->view->pronac = $this->_request->getParam('pronac');
        }

        $tbReadequacao = New tbReadequacao();

        $total = $tbReadequacao->painelReadequacoesCoordenadorAcompanhamentoCount($where, $filtro);

        $fim = $inicio + $this->intTamPag;

        $totalPag = (int)(($total % $this->intTamPag == 0)?($total/$this->intTamPag):(($total/$this->intTamPag)+1));
        $tamanho = ($fim > $total) ? $total - $inicio : $this->intTamPag;

        $busca = $tbReadequacao->painelReadequacoesCoordenadorAcompanhamento($where, $order, $tamanho, $inicio, false, $filtro);

        $paginacao = array(
            "pag"=>$pag,
            "qtde"=>$this->intTamPag,
            "campo"=>$campo,
            "ordem"=>$ordem,
            "ordenacao"=>$ordenacao,
            "novaOrdem"=>$novaOrdem,
            "total"=>$total,
            "inicio"=>($inicio+1),
            "fim"=>$fim,
            "totalPag"=>$totalPag,
            "Itenspag"=>$this->intTamPag,
            "tamanho"=>$tamanho
        );

        $tbTitulacaoConselheiro = new tbTitulacaoConselheiro();
        $this->view->conselheiros = $tbTitulacaoConselheiro->buscarConselheirosTitularesTbUsuarios();

        $this->view->paginacao     = $paginacao;
        $this->view->qtdRegistros  = $total;
        $this->view->dados         = $busca;
        $this->view->intTamPag     = $this->intTamPag;
    }

    /*
     * Alterada em 15/05/15
     */
    public function imprimirReadequacoesAction(){

        //FUN&ccedil;&atilde;O ACESSADA SOMENTE PELOS PERFIS DE COORD. GERAL DE ACOMPANHAMENTO E COORD. DE ACOMPANHAMENTO.
        if($this->idPerfil != 122 && $this->idPerfil != 123){
            parent::message("Voc&ecirc; n&atilde;o tem permiss&atilde;o para acessar essa &aacute;rea do sistema!", "principal", "ALERT");
        }

        //DEFINE PARAMETROS DE ORDENACAO / QTDE. REG POR PAG. / PAGINACAO
        if($this->_request->getParam("qtde")) {
            $this->intTamPag = $this->_request->getParam("qtde");
        }
        $order = array();

        //==== parametro de ordenacao  ======//
        if($this->_request->getParam("ordem")) {
            $ordem = $this->_request->getParam("ordem");
            if($ordem == "ASC") {
                $novaOrdem = "DESC";
            }else {
                $novaOrdem = "ASC";
            }
        }else {
            $ordem = "ASC";
            $novaOrdem = "ASC";
        }

        //==== campo de ordenacao  ======//
        if($this->_request->getParam("campo")) {
            $campo = $this->_request->getParam("campo");
            $order = array($campo." ".$ordem);
            $ordenacao = "&campo=".$campo."&ordem=".$ordem;

        } else {
            $campo = null;
            $order = array(5); //Dt. Solicita&ccedil;&atilde;o
            $ordenacao = null;
        }

        $pag = 1;
        if (isset($get->pag)) $pag = $this->_request->getParam('pag');
        $inicio = ($pag>1) ? ($pag-1)*$this->intTamPag : 0;

        /* ================== PAGINACAO ======================*/

        $where = array();
        if ($this->_request->getParam('pronac')) {
            $where['a.PRONAC = ?'] = $this->_request->getParam('pronac');
            $this->view->pronac = $this->_request->getParam('pronac');
        }

        $filtro = null;
        if ($this->_request->getParam('tipoFiltro')) {
            $filtro = $this->_request->getParam('tipoFiltro');
        } else {
            $filtro = 'aguardando_distribuicao';
        }

        $tbReadequacao = New tbReadequacao();

        $total = $tbReadequacao->painelReadequacoesCoordenadorAcompanhamentoCount($where, $filtro);

        $fim = $inicio + $this->intTamPag;
        $totalPag = (int)(($total % $this->intTamPag == 0)?($total/$this->intTamPag):(($total/$this->intTamPag)+1));
        $tamanho = ($fim > $total) ? $total - $inicio : $this->intTamPag;

        $busca = $tbReadequacao->painelReadequacoesCoordenadorAcompanhamento($where, $order, $tamanho, $inicio, null, $filtro);

        $this->view->qtdRegistros = $total;
        $this->view->dados = $busca;
        $this->_helper->layout->disableLayout(); // Desabilita o Zend Layout
    }

    /*
     * Alterada em 19/03/2014
     * @author: Jefferson Alessandro - jeffersonassilva@gmail.com
     * Fun&ccedil;&atilde;o usada para o coordenador de acompanhamento deferir ou n&atilde;o a solicita&ccedil;&atilde;o de readequa&ccedil;&atilde;o.
     */
    public function avaliarReadequacaoAction() {
        //FUN&ccedil;&atilde;O ACESSADA SOMENTE PELOS PERFIS DE COORD. GERAL DE ACOMPANHAMENTO E COORD. DE ACOMPANHAMENTO.
        $perfisAcesso = array(121, 122, 123);

        if(!in_array($this->idPerfil, $perfisAcesso)){
            parent::message("Voc&ecirc; n&atilde;o tem permiss&atilde;o para acessar essa &aacute;rea do sistema!", "principal", "ALERT");
        }

        $idReadequacao = Seguranca::dencrypt($this->_request->getParam('id'));

        $tbReadequacao = new tbReadequacao();
        $r = $tbReadequacao->buscarDadosReadequacoes(array('idReadequacao = ?'=>$idReadequacao))->current();

        if($r){
            $Projetos = new Projetos();
            $p = $Projetos->buscarProjetoXProponente(array('idPronac = ?' => $r->idPronac))->current();

            $this->view->filtro = $this->_request->getParam('filtro');
            $this->view->readequacao = $r;
            $this->view->projeto = $p;
            $this->view->idPronac = $r->idPronac;
        } else {
            parent::message('Nenhum registro encontrado.', "readequacoes/painel", "ERROR");
        }
    }

    /*
     * Alterada em 06/03/14
     * @author: Jefferson Alessandro - jeffersonassilva@gmail.com
     */
    public function buscarDestinatariosAction() {
        $this->_helper->layout->disableLayout(); // desabilita o Zend_Layout
        $vinculada = $this->_request->getParam('vinculada');
        $idPronac = $this->_request->getParam('idPronac');

        $a = 0;
        $dadosUsuarios = array();

        if($vinculada == 166 || $vinculada == 262){
            $dados = array();
            $dados['sis_codigo = ?'] = 21;
            $dados['uog_status = ?'] = 1;
            $dados['gru_codigo = ?'] = 121; //Tecnico de Acompanhamento
            if($vinculada == 166){
                $dados['org_superior = ?'] = 160;
            } else {
                $dados['org_superior = ?'] = 251;
            }

            $vw = new vwUsuariosOrgaosGrupos();
            $result = $vw->buscar($dados, array('usu_nome'));

            if(count($result) > 0){
                foreach ($result as $registro) {
                    $dadosUsuarios[$a]['id'] = $registro['usu_codigo'];
                    $dadosUsuarios[$a]['nome'] = utf8_encode($registro['usu_nome']);
                    $a++;
                }
                $jsonEncode = json_encode($dadosUsuarios);
                $this->_helper->json(array('resposta'=>true,'conteudo'=>$dadosUsuarios));

            } else {
                $this->_helper->json(array('resposta'=>false));
            }

        } else { //CNIC
            $tbTitulacaoConselheiro = new tbTitulacaoConselheiro();
            $result = $tbTitulacaoConselheiro->buscarConselheirosTitularesTbUsuarios();

            if(count($result) > 0){
                foreach ($result as $registro) {
                    $dadosUsuarios[$a]['id'] = $registro['id'];
                    $dadosUsuarios[$a]['nome'] = utf8_encode($registro['nome']);
                    $a++;
                }
                $jsonEncode = json_encode($dadosUsuarios);
                $this->_helper->json(array('resposta'=>true,'conteudo'=>$dadosUsuarios));

            } else {
                $this->_helper->json(array('resposta'=>false));
            }
        }
        $this->_helper->viewRenderer->setNoRender(TRUE);
    }

    /**
     * salvarAvaliacaoAction Fun&ccedil;&atilde;o utilizada pleo Coord. de Acompanhamento para avaliar a readequa&ccedil;&atilde;o e encaminh&aacute;-la.
     *
     * @since  Alterada em 11/03/14
     * @author Jefferson Alessandro <jeffersonassilva@gmail.com>
     * @author wouerner <wouerner@gmail.com>
     * @access public
     * @return void
     */
    public function salvarAvaliacaoAction() {
        $perfisAcesso = array(121, 122, 123);

        //FUN&ccedil;&atilde;O ACESSADA SOMENTE PELOS PERFIS DE COORD. GERAL DE ACOMPANHAMENTO, TECNICO DE ACOMPANHAMENTO E COORD. DE ACOMPANHAMENTO.
        if(!in_array($this->idPerfil, $perfisAcesso)){
            parent::message("Voc&ecirc; n&atilde;o tem permiss&atilde;o para acessar essa &aacute;rea do sistema!", "principal", "ALERT");
        }

        $idReadequacao = $this->_request->getParam('idReadequacao');
        $filtro = $this->_request->getParam('filtro');

        $tbReadequacao = new tbReadequacao();
        $r = $tbReadequacao->find(array('idReadequacao = ?'=>$idReadequacao))->current();
        $stValidacaoCoordenador = 0;
        $dataEnvio = null;

        $objAcesso = new Acesso();
        if($r){
            $r->stAtendimento = $this->_request->getParam('stAtendimento');
            $r->dsAvaliacao = $this->_request->getParam('dsAvaliacao');
            $r->dtAvaliador = $objAcesso->getExpressionDate();
            $r->idAvaliador = $this->idUsuario;

            if($this->_request->getParam('stAtendimento') == 'I'){
                // indeferida
                $r->siEncaminhamento = 2; //2=Solicita&ccedil;&atilde;o indeferida
                $r->stEstado = 1;
            } else if ($this->_request->getParam('stAtendimento') == 'DP') {
                // devolvida ao proponente
                $r->siEncaminhamento = 12;
                $r->stEstado = 0;
                $r->stAtendimento = 'E';
            } else {
                // deferida
                if($this->_request->getParam('vinculada') == 262 || $this->_request->getParam('vinculada') == 166){
                    $r->siEncaminhamento = 4; //4=Enviado para An&aacute;lise T&eacute;cnica (SAV, SEFIC)
                    $dataEnvio = $objAcesso->getExpressionDate();
                    $r->idAvaliador = $this->_request->getParam('destinatario');
                } else if($this->_request->getParam('vinculada') == 400) {
                    $stValidacaoCoordenador = 1;
                    $r->siEncaminhamento = 7; //7=CNIC
                    $r->idAvaliador = $this->_request->getParam('destinatario');
                } else {
                    $r->siEncaminhamento = 3; //3=Enviado para o coordenador de parecer
                }
            }
            $r->save();

            if($this->_request->getParam('stAtendimento') == 'D'){

                // busca readequacao para ver se existe. Se n&atilde;o existe, cria, sen&atilde;o atualiza
                $tbDistribuirReadequacao = new tbDistribuirReadequacao();
                $jaDistribuiu = $tbDistribuirReadequacao->buscar(array('idReadequacao = ?'=>$r->idReadequacao))->current();

                if (empty($jaDistribuiu)) {
                    $dados = array(
                        'idReadequacao' => $r->idReadequacao,
                        'idUnidade' => $this->_request->getParam('vinculada'),
                        'DtEncaminhamento' => $objAcesso->getExpressionDate(),
                        'idAvaliador' => (null !== $this->_request->getParam('destinatario')) ? $this->_request->getParam('destinatario') : null,
                        'dtEnvioAvaliador' => !empty($dqataEnvio) ? $dataEnvio : null,
                        'stValidacaoCoordenador' => $stValidacaoCoordenador,
                        'dsOrientacao' => $r->dsAvaliacao
                    );

                    $tbDistribuirReadequacao->inserir($dados);
                } else {
                    $dados = array(
                        'idUnidade' => $this->_request->getParam('vinculada'),
                        'DtEncaminhamento' => $objAcesso->getExpressionDate(),
                        'idAvaliador' => (null !== $this->_request->getParam('destinatario')) ? $this->_request->getParam('destinatario') : null,
                        'dtEnvioAvaliador' => !empty($dqataEnvio) ? $dataEnvio : null,
                        'stValidacaoCoordenador' => $stValidacaoCoordenador,
                        'dsOrientacao' => $r->dsAvaliacao
                    );
                    $where = "idReadequacao = " . $r->idReadequacao;

                    $tbDistribuirReadequacao->update($dados, $where);


                }
            }
            if ($this->idPerfil == 121) {
                parent::message('Dados salvos com sucesso!', "readequacoes/painel-readequacoes?tipoFiltro=$filtro", "CONFIRM");
            } else {
                parent::message('Dados salvos com sucesso!', "readequacoes/painel?tipoFiltro=$filtro", "CONFIRM");
            }

        } else {
            if ($this->idPerfil == 121) {
                parent::message('Nenhum registro encontrado.', "readequacoes/painel-readequacoes?tipoFiltro=$filtro", "ERROR");
            } else {
                parent::message('Nenhum registro encontrado.', "readequacoes/painel?tipoFiltro=$filtro", "ERROR");
            }
        }
    }

    /*
     * Alterada em 11/03/14
     * @author: Jefferson Alessandro - jeffersonassilva@gmail.com
     * FUN&ccedil;&atilde;O ACESSADA PELO TECNICO DE ACOMPANHAMENTO
    */
    public function painelReadequacoesAction() {

        //FUN&ccedil;&atilde;O ACESSADA SOMENTE PELOS PERFIS DE COORD. DE PARECER, PARECERISTA E TECNICO DE ACOMPANHAMENTO.
        if($this->idPerfil != 93 && $this->idPerfil != 94 && $this->idPerfil != 121){
            parent::message("Voc&ecirc; n&atilde;o tem permiss&atilde;o para acessar essa &aacute;rea do sistema!", "principal", "ALERT");
        }

        //DEFINE PARAMETROS DE ORDENACAO / QTDE. REG POR PAG. / PAGINACAO
        if($this->_request->getParam("qtde")) {
            $this->intTamPag = $this->_request->getParam("qtde");
        }
        $order = array();

        //==== parametro de ordenacao  ======//
        if($this->_request->getParam("ordem")) {
            $ordem = $this->_request->getParam("ordem");
            if($ordem == "ASC") {
                $novaOrdem = "DESC";
            }else {
                $novaOrdem = "ASC";
            }
        }else {
            $ordem = "ASC";
            $novaOrdem = "ASC";
        }

        //==== campo de ordenacao  ======//
        if($this->_request->getParam("campo")) {
            $campo = $this->_request->getParam("campo");
            $order = array($campo." ".$ordem);
            $ordenacao = "&campo=".$campo."&ordem=".$ordem;

        } else {
            $campo = null;
            $order = array(1); //idDistribuirReadequacao
            $ordenacao = null;
        }

        $pag = 1;
        $get = Zend_Registry::get('get');
        if (isset($get->pag)) $pag = $get->pag;
        $inicio = ($pag>1) ? ($pag-1)*$this->intTamPag : 0;

        /* ================== PAGINACAO ======================*/
        $where = array();

        if ($this->_request->getParam('tipoFiltro') !== null) {
            $filtro = $this->_request->getParam('tipoFiltro');
        } else {
            if ($this->idPerfil == 93) {
                $filtro = 'aguardando_distribuicao';
            } else {
                $filtro = 'painel_do_tecnico';
            }
        }
        $this->view->filtro = $filtro;

        if ($this->_request->getParam('pronac')) {
            $where['PRONAC = ?'] = $this->_request->getParam('pronac');
            $this->view->pronac = $this->_request->getParam('pronac');
        }

        // hack!
        if ($this->idOrgao == 272) {
            $where['idUnidade = ?'] = 262;
        } else {
            $where['idUnidade = ?'] = $this->idOrgao;
        }

        $tbReadequacao = New tbReadequacao();
        $tbDistribuirReadequacao = New tbDistribuirReadequacao();

        if ($this->idPerfil == 93) {
            // coordenador parecer

            switch($filtro){
                case 'aguardando_distribuicao':
                    $total = count($tbDistribuirReadequacao->buscarReadequacaoCoordenadorParecerAguardandoAnalise($where));
//                    $total = $tbReadequacao->count('vwPainelReadequacaoCoordenadorParecerAguardandoAnalise' , $where);
                    break;
                case 'em_analise':
//                    $total = $tbReadequacao->count('vwPainelReadequacaoCoordenadorParecerEmAnalise' , $where);
                    $total = count($tbReadequacao->buscarReadequacaoCoordenadorParecerEmAnalise($where));
                    break;
                case 'analisados':
                    $total  = count($tbDistribuirReadequacao->buscarReadequacaoCoordenadorParecerAnalisados($where));
//                    $total = $tbReadequacao->count('vwPainelReadequacaoCoordenadorParecerAnalisados' , $where);
                    break;
            }
        } else if ($this->idPerfil == 121 || $this->idPerfil == 94) {
            // t&eacute;cnico de acompanhamento ou parecerista de vinculada
            $auth = Zend_Auth::getInstance(); // pega a autentica&ccedil;&atilde;o
            $where['d.idAvaliador = ?'] = $auth->getIdentity()->usu_codigo;

            $total = count($tbReadequacao->painelReadequacoesTecnicoAcompanhamento($where));

            $this->filtro = '';
        }

        $fim = $inicio + $this->intTamPag;

        $totalPag = (int)(($total % $this->intTamPag == 0)?($total/$this->intTamPag):(($total/$this->intTamPag)+1));
        $tamanho = ($fim > $total) ? $total - $inicio : $this->intTamPag;

        // coordenador de parecer
        if ($this->idPerfil == 93) {

            switch ($filtro) {
                case 'aguardando_distribuicao':
                        $busca = $tbDistribuirReadequacao->buscarReadequacaoCoordenadorParecerAguardandoAnalise($where, $order, $tamanho, $inicio, false);
                    break;
                case 'em_analise':
                    $busca = $tbReadequacao->buscarReadequacaoCoordenadorParecerEmAnalise($where, $order, $tamanho, $inicio, false);
                    break;
                case 'analisados':
                    $busca = $tbDistribuirReadequacao->buscarReadequacaoCoordenadorParecerAnalisados($where, $order, $tamanho, $inicio, false);
                    break;
            }

        } else if ($this->idPerfil == 121 || $this->idPerfil == 94) {
             // tecnico de acompanhamento ou parecerista de vinculada
            $busca = $tbReadequacao->painelReadequacoesTecnicoAcompanhamento($where, $order, $tamanho, $inicio, false);
        }
        $paginacao = array(
            "pag"=>$pag,
            "qtde"=>$this->intTamPag,
            "campo"=>$campo,
            "ordem"=>$ordem,
            "ordenacao"=>$ordenacao,
            "novaOrdem"=>$novaOrdem,
            "total"=>$total,
            "inicio"=>($inicio+1),
            "fim"=>$fim,
            "totalPag"=>$totalPag,
            "Itenspag"=>$this->intTamPag,
            "tamanho"=>$tamanho
        );

        $this->view->paginacao     = $paginacao;
        $this->view->qtdRegistros  = $total;
        $this->view->dados         = $busca;
        $this->view->intTamPag     = $this->intTamPag;
        $this->view->idPerfil      = $this->idPerfil;
        $this->view->idOrgao       = $this->idOrgao;

    }

    /*
     * Alterada em 12/03/14
     * Fun&ccedil;&atilde;o acessada pelo Coordenador de Parecer, Coordenador de Acompanhamento, e Coordenador Geral de Acomapanhamento.
    */
    public function visualizarReadequacaoAction() {

        //FUN&ccedil;&atilde;O ACESSADA SOMENTE PELOS PERFIS DE COORD. DE PARECER, COORD. DE ACOMPANHAMETO E COORD. GERAL DE ACOMPANHAMENTO.
        if($this->idPerfil != 93 && $this->idPerfil != 122 && $this->idPerfil != 123){
            parent::message("Voc&ecirc; n&atilde;o tem permiss&atilde;o para acessar essa &aacute;rea do sistema!", "principal", "ALERT");
        }

        $id = Seguranca::dencrypt($this->_request->getParam('id'));

        $tbReadequacao = new tbReadequacao();
        $d = $tbReadequacao->visualizarReadequacao(array('a.idReadequacao = ?'=>$id))->current();
        $this->view->dados = $d;

        $Projetos = new Projetos();
        $p = $Projetos->buscarProjetoXProponente(array('idPronac = ?' => $d->idPronac))->current();
        $this->view->projeto = $p;

        $tbReadequacaoXParecer = new tbReadequacaoXParecer();
        $this->view->Parecer = $tbReadequacaoXParecer->buscarPareceresReadequacao(array('a.idReadequacao = ?'=>$id, 'idTipoAgente =?'=>1))->current();
    }

     /*
     * Alterada em 13/03/14
     * @author: Jefferson Alessandro - jeffersonassilva@gmail.com
     * Fun&ccedil;&atilde;o acessada pelo Coordenador de Parecer para encaminhar a readequa&ccedil;&atilde;o para o Parecerista.
    */
    public function encaminharReadequacaoAction() {
        $this->_helper->layout->disableLayout(); // desabilita o Zend_Layout
        $vinculada = $this->idOrgao;

        $idDistRead = $this->_request->getParam('idDistRead');
        $idReadequacao = $this->_request->getParam('idReadequacao');

        //Atualiza a tabela tbDistribuirReadequacao
        if ($vinculada != Orgaos::ORGAO_SUPERIOR_IPHAN) { // todos os casos exceto IPHAN
            $idAvaliador = $this->_request->getParam('parecerista');

            $dados = array();
            $dados['idAvaliador'] = $idAvaliador;
            $objAcesso = new Acesso();
            $dados['DtEnvioAvaliador'] = $objAcesso->getExpressionDate();
            $where["idDistribuirReadequacao = ? "] = $idDistRead;
            $tbDistribuirReadequacao = new tbDistribuirReadequacao();
            $return = $tbDistribuirReadequacao->update($dados, $where);

            //Atualiza a tabela tbReadequacao
            $dados = array();
            $dados['siEncaminhamento'] = 4; // Enviado para an&aacute;lise t&eacute;cnica
            $where = array();
            $where['idReadequacao = ?'] = $idReadequacao;
            $tbReadequacao = new tbReadequacao();
            $return2 = $tbReadequacao->update($dados, $where);

            if($return && $return2){
                $this->_helper->json(array('resposta'=>true));
            } else {
                $this->_helper->json(array('resposta'=>false));
            }

        } else {
            // IPHAN

            $idVinculada = $this->_request->getParam('parecerista');

            $dados = array();
            $dados['idUnidade'] = $idVinculada;
            $where["idDistribuirReadequacao = ? "] = $idDistRead;
            $tbDistribuirReadequacao = new tbDistribuirReadequacao();
            $return = $tbDistribuirReadequacao->update($dados, $where);

            if ($return) {
                $this->_helper->json(array('resposta'=>true));
            } else {
                $this->_helper->json(array('resposta'=>false));
            }
        }
        $this->_helper->viewRenderer->setNoRender(TRUE);
    }

    /*
     * Criada em 12/03/14
     * @author: Jefferson Alessandro - jeffersonassilva@gmail.com
     * Fun&ccedil;&atilde;o acessada pelo Parecerista ou T&eacute;cnico de acompanhamento para avaliar a readequa&ccedil;&atilde;o.
    */
    public function formAvaliarReadequacaoAction(){
        $perfisAcesso = array(94, 121);

        if(!in_array($this->idPerfil, $perfisAcesso)){
            parent::message("Voc&ecirc; n&atilde;o tem permiss&atilde;o para acessar essa &aacute;rea do sistema!", "principal", "ALERT");
        }

        $idReadequacao = (strlen($this->_request->getParam('id')) > 7) ? (int) Seguranca::dencrypt($this->_request->getParam('id')) : $this->_request->getParam('id');

        $this->view->idReadequacao = $idReadequacao;

        $tbReadequacao = new tbReadequacao();
        $dados = $tbReadequacao->buscarDadosReadequacoes(array('idReadequacao = ?'=>$idReadequacao))->current();
        if(!$dados){
            parent::message("Readequa&ccedil;&atilde;o n&atilde;o encontrada!", "readequacoes/painel-readequacoes", "ERROR");
        }
        $this->view->dados = $dados;
        $this->view->idPronac = $dados->idPronac;

        $this->view->nmPagina = $dados->dsReadequacao;
        $d = array();
        $d['ProvidenciaTomada'] = 'Readequa&ccedil;&atilde;o enviado para avalia&ccedil;&atilde;o t&eacute;cnica.';
        $objAcesso = new Acesso();
        $d['dtSituacao'] = $objAcesso->getExpressionDate();
        $where = "IdPRONAC = $dados->idPronac";
        $Projetos = new Projetos();
        $Projetos->update($d, $where);

        $TbPlanilhaUnidade = new Proposta_Model_DbTable_TbPlanilhaUnidade();
        $buscarUnidade = $TbPlanilhaUnidade->buscarUnidade();
        $this->view->Unidade = $buscarUnidade;

        $tbReadequacaoXParecer = new tbReadequacaoXParecer();
        $this->view->Parecer = $tbReadequacaoXParecer->buscarPareceresReadequacao(array('a.idReadequacao = ?'=>$idReadequacao, 'b.idTipoAgente = ?'=>1))->current();

        //DADOS DO PROJETO
        $p = $Projetos->buscarProjetoXProponente(array('idPronac = ?' => $dados->idPronac))->current();
        $this->view->projeto = $p;
    }

    /*
     * Criada em 12/03/14
     * @author: Jefferson Alessandro - jeffersonassilva@gmail.com
     * Fun&ccedil;&atilde;o acessada pelo Parecerista ou T&eacute;cnico de acompanhamento para avaliar a readequa&ccedil;&atilde;o.
    */
    public function salvarParecerTecnicoAction()
	{
        if($this->idPerfil != 94 && $this->idPerfil != 121){
            parent::message("Voc&ecirc; n&atilde;o tem permiss&atilde;o para acessar essa &aacute;rea do sistema!", "principal", "ALERT");
        }

        $idPronac = $_POST['idPronac'];
        $idReadequacao = $_POST['idReadequacao'];
        $dsParecer = $_POST['dsParecer'];
        $parecerProjeto = $_POST['parecerProjeto'];
        $campoTipoParecer = 8;
        $vlPlanilha = 0;

        $tbReadequacao = new tbReadequacao();
        $dadosRead = $tbReadequacao->buscar(array('idReadequacao=?'=>$idReadequacao))->current();

        //SE FOR READEQUA&ccedil;&atilde;O DE PLANILHA OR&ccedil;AMENT&aacute;RIA, O CAMPO TipoParecer DA TABELA sac.dbo.Parecer MUDAR.
        if($dadosRead->idTipoReadequacao == 2){
            $tbPlanilhaAprovacao = new tbPlanilhaAprovacao();

            //BUSCAR VALOR TOTAL DA PLANILHA ATIVA
            $where = array();
            $where['a.IdPRONAC = ?'] = $idPronac;
            $where['a.stAtivo = ?'] = 'S';
            $PlanilhaAtiva = $tbPlanilhaAprovacao->valorTotalPlanilha($where)->current();

            //BUSCAR VALOR TOTAL DA PLANILHA DE READEQUADA
            $where = array();
            $where['a.IdPRONAC = ?'] = $idPronac;
            $where['a.tpPlanilha = ?'] = 'SR';
            $where['a.stAtivo = ?'] = 'N';
            $PlanilhaReadequada = $tbPlanilhaAprovacao->valorTotalPlanilha($where)->current();

            if($PlanilhaAtiva->Total > $PlanilhaReadequada->Total){
                $vlPlanilha = $PlanilhaAtiva->Total-$PlanilhaReadequada->Total;
                $campoTipoParecer = 4;
            } else {
                $vlPlanilha = $PlanilhaReadequada->Total-$PlanilhaAtiva->Total;
                $campoTipoParecer = 2;
            }
        }

        try {
            //ATUALIAZA A SITUA&ccedil;&atilde;O, &aacute;REA E SEGMENTO DO PROJETO
            $d = array();
            $d['ProvidenciaTomada'] = 'Readequa&ccedil;&atilde;o em an&aacute;lise pela &aacute;rea t&eacute;cnica.';
            //$d['dtSituacao'] = new Zend_Db_Expr('GETDATE()');
            $where = "IdPRONAC = $idPronac";
            $Projetos = new Projetos();
            $Projetos->update($d, $where);

            $enquadramentoDAO = new Admissibilidade_Model_Enquadramento();
            $buscaEnquadramento = $enquadramentoDAO->buscarDados($idPronac, null, false);

            $dadosProjeto = $Projetos->buscar(array('IdPRONAC = ?'=>$idPronac));
            if(count($dadosProjeto)>0){

                //CADASTRA OU ATUALIZA O PARECER DO TECNICO
                $parecerDAO = new Parecer();
                $dadosParecer = array(
                    'idPRONAC' => $idPronac,
                    'AnoProjeto' => $dadosProjeto[0]->AnoProjeto,
                    'Sequencial' => $dadosProjeto[0]->Sequencial,
                    'TipoParecer' => $campoTipoParecer,
                    'ParecerFavoravel' => $parecerProjeto,
                    'DtParecer' => MinC_Db_Expr::date(),
                    'NumeroReuniao' => null,
                    'ResumoParecer' => $dsParecer,
                    'SugeridoReal' => $vlPlanilha,
                    'Atendimento' => 'S',
                    'idEnquadramento' => $buscaEnquadramento['IdEnquadramento'],
                    'stAtivo' => 1,
                    'idTipoAgente' => 1,
                    'Logon' => $this->idUsuario
                );

                foreach ($dadosParecer as $dp) {
                    $parecerAntigo = array(
                        'Atendimento' => 'S',
                        'stAtivo' => 0
                    );
                    $whereUpdateParecer = 'IdPRONAC = '.$idPronac;
                    $alteraParecer = $parecerDAO->alterar($parecerAntigo, $whereUpdateParecer);
                }

                $tbReadequacaoXParecer = new tbReadequacaoXParecer();
                $buscarParecer = $tbReadequacaoXParecer->buscarPareceresReadequacao(array('a.idReadequacao = ?'=>$idReadequacao))->current();

                if($buscarParecer){
                    $whereUpdateParecer = 'IdParecer = '.$buscarParecer->IdParecer;
                    $parecerDAO->alterar($dadosParecer, $whereUpdateParecer);
                    $idParecer = $buscarParecer->IdParecer;
                } else {
                    $idParecer = $parecerDAO->inserir($dadosParecer);
                }

                $tbReadequacaoXParecer = new tbReadequacaoXParecer();
                $parecerReadequacao = $tbReadequacaoXParecer->buscar(array('idReadequacao = ?'=>$idReadequacao, 'idParecer =?'=>$idParecer));
                if(count($parecerReadequacao) == 0){
                    $dadosInclusao = array(
                        'idReadequacao' => $idReadequacao,
                        'idParecer' => $idParecer
                    );
                    $tbReadequacaoXParecer->inserir($dadosInclusao);
                }
            }

            if(isset($_POST['finalizarAvaliacao']) && $_POST['finalizarAvaliacao'] == 1){

                $tbDistribuirReadequacao = new tbDistribuirReadequacao();
                $dDP = $tbDistribuirReadequacao->buscar(array('idReadequacao = ?'=>$idReadequacao));

                $objAcesso = new Acesso();
                if(count($dDP)>0){
                    //ATUALIZA A TABELA tbDistribuirReadequacao
                    $dadosDP = array();
                    $dadosDP['DtRetornoAvaliador'] = $objAcesso->getExpressionDate();
                    $whereDP = "idDistribuirReadequacao = ".$dDP[0]->idDistribuirReadequacao;
                    $x = $tbDistribuirReadequacao->update($dadosDP, $whereDP);

                    $siEncaminhamento = 5; //Devolvido da an&aacute;lise t&eacute;cnica
                    if($this->idPerfil == 121){
                        $siEncaminhamento = 10; //Devolver para Coordenador do MinC
                    }
                    //ATUALIZA A TABELA tbReadequacao
                    $dados = array();
                    $dados['siEncaminhamento'] = $siEncaminhamento;
                    $where = "idReadequacao = $idReadequacao";
                    $tbReadequacao->update($dados, $where);
                }
                parent::message("A avalia&ccedil;&atilde;o da readequa&ccedil;&atilde;o foi finalizada com sucesso! ", "readequacoes/painel-readequacoes", "CONFIRM");
            }
            $idReadequacao = Seguranca::encrypt($idReadequacao);
            parent::message("Dados salvos com sucesso!", "readequacoes/form-avaliar-readequacao?id=$idReadequacao", "CONFIRM");

        } // fecha try
        catch (Exception $e) {
            parent::message($e->getMessage(), "readequacoes/form-avaliar-readequacao?id=$idReadequacao", "ERROR");
        }
	}

    public function coordParecerFinalizarReadequacaoAction() {
        $this->_helper->layout->disableLayout(); // desabilita o Zend_Layout

        $post = Zend_Registry::get('post');
        $idReadequacao = (int) $post->idReadequacao;

        //Atualiza a tabela tbReadequacao
        $dados = array();
        $dados['siEncaminhamento'] = 6; // Devolvido para o coordenador geral de acompanhamento
        $where = "idReadequacao = $idReadequacao";
        $tbReadequacao = new tbReadequacao();
        $return = $tbReadequacao->update($dados, $where);

        if($return){
            $this->_helper->json(array('resposta'=>true));
        } else {
            $this->_helper->json(array('resposta'=>false));
        }
        $this->_helper->viewRenderer->setNoRender(TRUE);
    }

    /*
     * Alterada em 12/03/14
     * @author: Jefferson Alessandro - jeffersonassilva@gmail.com
     * Fun&ccedil;&atilde;o acessada pelos coordenadores para devolver para an&aacute;lise t&eacute;cnica.
    */
    public function devolverReadequacaoAction() {
        $dados = array();
        $get = Zend_Registry::get('get');
        $idReadequacao = (int) $get->id;

        $tbReadequacao = new tbReadequacao();
        $dadosReadequacao = $tbReadequacao->find(array('idReadequacao=?'=>$idReadequacao))->current();

        $siEncaminhamento = $dadosReadequacao->siEncaminhamento;

        //RECURSOS TRATADOS POR PARECERISTA
        if($siEncaminhamento == 6){
            //Atualiza a tabela tbRecurso
            $dados['siEncaminhamento'] = 3; // Encaminhado do MinC para Unidade de An&aacute;lise
            $where = "idReadequacao = $idReadequacao";
        } else {
            $dados['siEncaminhamento'] = 4; // Encaminhado para o T&eacute;cnico
            $where = "idReadequacao = $idReadequacao";
        }
        $tbReadequacao->update($dados, $where);

        parent::message("Readequa&ccedil;&atilde;o devolvida com sucesso!", "readequacoes/painel?tipoFiltro=analisados", "CONFIRM");
    }

    /*
     * Alterada em 14/03/14
     * @author: Jefferson Alessandro - jeffersonassilva@gmail.com
     * Fun&ccedil;&atilde;o acessada pelo coordenador de an&aacute;lise para finalizar a readequa&ccedil;&atilde;o e encaminhar para o componente da comiss&atilde;o.
    */
    public function coordAnaliseFinalizarReadequacaoAction() {
        $this->_helper->layout->disableLayout(); // desabilita o Zend_Layout
        //$vinculada = $this->idOrgao;

        $post = Zend_Registry::get('post');
        $idComponente = (int) $post->componente;
        $idReadequacao = (int) $post->idReadequacao;

        $tbReadequacao = new tbReadequacao();
        $dadosReadequacao = $tbReadequacao->buscar(array('idReadequacao = ?'=>$idReadequacao))->current();
        $idPronac = $dadosReadequacao->idPronac;

        $tbDistribuirReadequacao = new tbDistribuirReadequacao();
        $dadosDistRead = $tbDistribuirReadequacao->buscar(array('idReadequacao=?'=>$idReadequacao));
        $objAcesso = new Acesso();
        if(count($dadosDistRead)>0){
            //Atualiza a tabela tbDistribuirReadequacao
            $dadosDP = array();
            $dadosDP['stValidacaoCoordenador'] = 1;
            $dadosDP['DtValidacaoCoordenador'] = $objAcesso->getExpressionDate();
            $dadosDP['idCoordenador'] = $this->idUsuario;

            // atualiza com dados para enviar para cnic
            $dadosDP['idUnidade'] = 400;
            $dadosDP['DtEncaminhamento'] = $objAcesso->getExpressionDate();
            $dadosDP['idAvaliador'] = $idComponente;
            $dadosDP['dtEnvioAvaliador'] = $objAcesso->getExpressionDate();

            $whereDP = "idDistribuirReadequacao = ".$dadosDistRead[0]->idDistribuirReadequacao;
            $distribuicao = $tbDistribuirReadequacao->update($dadosDP, $whereDP);
        }

        $reuniao = new Reuniao();
        $raberta = $reuniao->buscarReuniaoAberta();
        $idNrReuniao = ($raberta['stPlenaria'] == 'A') ? $raberta['idNrReuniao']+1 : $raberta['idNrReuniao'];

        //Atualiza a tabela tbReadequacao
        $dados = array();
        $dados['siEncaminhamento'] = 7; // Enviado para CNIC
        $dados['idNrReuniao'] = $idNrReuniao;
        $where = "idReadequacao = $idReadequacao";
        $return = $tbReadequacao->update($dados, $where);

        if($return && $distribuicao){
            $this->_helper->json(array('resposta'=>true));
        } else {
            $this->_helper->json(array('resposta'=>false));
        }
        $this->_helper->viewRenderer->setNoRender(TRUE);
    }

    /*
     * Alterada em 14/03/14
     * @author: Jefferson Alessandro - jeffersonassilva@gmail.com
     * Fun&ccedil;&atilde;o acessada pelo componente da comiss&atilde;o para analisar as readequa&ccedil;&otilde;es - painel.
    */
    public function analisarReadequacoesCnicAction()
	{
        if($this->idPerfil != 118){
            parent::message("Voc&ecirc; n&atilde;o tem permiss&atilde;o para acessar essa &aacute;rea do sistema!", "principal", "ALERT");
        }

        //DEFINE PARAMETROS DE ORDENACAO / QTDE. REG POR PAG. / PAGINACAO
        if($this->_request->getParam("qtde")) {
            $this->intTamPag = $this->_request->getParam("qtde");
        }
        $order = array();

        //==== parametro de ordenacao  ======//
        if($this->_request->getParam("ordem")) {
            $ordem = $this->_request->getParam("ordem");
            if($ordem == "ASC") {
                $novaOrdem = "DESC";
            }else {
                $novaOrdem = "ASC";
            }
        }else {
            $ordem = "ASC";
            $novaOrdem = "ASC";
        }

        //==== campo de ordenacao  ======//
        if($this->_request->getParam("campo")) {
            $campo = $this->_request->getParam("campo");
            $order = array($campo." ".$ordem);
            $ordenacao = "&campo=".$campo."&ordem=".$ordem;

        } else {
            $campo = null;
            $order = array(2); //idReadequacao
            $ordenacao = null;
        }

        $pag = 1;
        $get = Zend_Registry::get('get');
        if (isset($get->pag)) $pag = $get->pag;
        $inicio = ($pag>1) ? ($pag-1)*$this->intTamPag : 0;

        /* ================== PAGINACAO ======================*/

        $where = array();
        $where['a.stEstado = ?'] = 0; // 0=Atual; 1=Historico
        $where['a.siEncaminhamento = ?'] = 7; // 7=Encaminhar para ao Componente da Comiss&atilde;o
        $where['d.idUnidade = ?'] = 400; // CNIC
        $where['d.idAvaliador = ?'] = $this->idUsuario;

        if((isset($_GET['pronac']) && !empty($_GET['pronac']))){
            $where['b.AnoProjeto+b.Sequencial = ?'] = $_GET['pronac'];
            $this->view->pronac = $_GET['pronac'];
        }

        $tbReadequacao = New tbReadequacao();
        $total = $tbReadequacao->painelReadequacoesComponente($where, $order, null, null, true);
        $fim = $inicio + $this->intTamPag;

        $totalPag = (int)(($total % $this->intTamPag == 0)?($total/$this->intTamPag):(($total/$this->intTamPag)+1));
        $tamanho = ($fim > $total) ? $total - $inicio : $this->intTamPag;

        $busca = $tbReadequacao->painelReadequacoesComponente($where, $order, $tamanho, $inicio);
        $paginacao = array(
                "pag"=>$pag,
                "qtde"=>$this->intTamPag,
                "campo"=>$campo,
                "ordem"=>$ordem,
                "ordenacao"=>$ordenacao,
                "novaOrdem"=>$novaOrdem,
                "total"=>$total,
                "inicio"=>($inicio+1),
                "fim"=>$fim,
                "totalPag"=>$totalPag,
                "Itenspag"=>$this->intTamPag,
                "tamanho"=>$tamanho
         );

        $this->view->paginacao     = $paginacao;
        $this->view->qtdRegistros  = $total;
        $this->view->dados         = $busca;
        $this->view->intTamPag     = $this->intTamPag;
	}

    /*
     * Alterada em 17/03/2014
     * @author: Jefferson Alessandro - jeffersonassilva@gmail.com
     * Fun&ccedil;&atilde;o acessada pelo componente da comiss&atilde;o para avaliar as readequa&ccedil;&otilde;es.
    */
    public function formAvaliarReadequacaoCnicAction(){

        if($this->idPerfil != 118){
            parent::message("Voc&ecirc; n&atilde;o tem permiss&atilde;o para acessar essa &aacute;rea do sistema!", "principal", "ALERT");
        }

        $get = Zend_Registry::get('get');
        $idReadequacao = (int) Seguranca::dencrypt($get->id);

        $tbReadequacao = new tbReadequacao();
        $dados = $tbReadequacao->buscarDadosReadequacoesCnic(array('a.idReadequacao = ?'=>$idReadequacao, 'f.idUnidade != ?'=>400))->current();

        if(!$dados){
            $dados = $tbReadequacao->buscarDadosReadequacoesCnic(array('a.idReadequacao = ?'=>$idReadequacao, 'f.idUnidade = ?'=>400))->current();
        }

        $this->view->dados = $dados;
        $this->view->idPronac = $dados->idPronac;
        $this->view->nmPagina = $dados->dsReadequacao;

        //DADOS DO PROJETO
        $Projetos = new Projetos();
        $p = $Projetos->buscarProjetoXProponente(array('idPronac = ?' => $dados->idPronac))->current();
        $this->view->projeto = $p;

        $TbPlanilhaUnidade = new Proposta_Model_DbTable_TbPlanilhaUnidade();
        $buscarUnidade =  $TbPlanilhaUnidade->buscarUnidade();
        $this->view->Unidade = $buscarUnidade;

        //DADOS DA AVALIA&ccedil;&atilde;O T&eacute;CNICA ou PARECERISTA
        $avaliacaoTecnica = $tbReadequacao->buscarDadosParecerReadequacao(array('a.idReadequacao = ?'=>$idReadequacao, 'c.idTipoAgente = ?'=>1))->current();
        $this->view->avaliacaoTecnica = $avaliacaoTecnica;

        //DADOS DA AVALIA&ccedil;&atilde;O DO COMPONENTE
        $avaliacaoConselheiro = $tbReadequacao->buscarDadosParecerReadequacao(array('a.idReadequacao = ?'=>$idReadequacao, 'c.idTipoAgente = ?'=>6))->current();
        $this->view->avaliacaoConselheiro = $avaliacaoConselheiro;
    }

    /*
     * Alterada em 15/05/2015
     * Fun&ccedil;&atilde;o acessada pelo componente da comiss&atilde;o para avaliar as readequa&ccedil;&otilde;es.
    */
    public function componenteComissaoSalvarAvaliacaoAction(){
        if($this->idPerfil != 118){
            parent::message("Voc&ecirc; n&atilde;o tem permiss&atilde;o para acessar essa &aacute;rea do sistema!", "principal", "ALERT");
        }

        $idPronac = $_POST['idPronac'];
        $idReadequacao = $_POST['idReadequacao'];
        $parecerProjeto = $_POST['parecerProjeto'];
        $dsParecer = $_POST['dsParecer'];
        $campoTipoParecer = 8;
        $vlPlanilha = 0;

        $tbReadequacao = new tbReadequacao();
        $dadosRead = $tbReadequacao->buscar(array('idReadequacao=?'=>$idReadequacao))->current();

        //SE FOR READEQUA&ccedil;&atilde;O DE PLANILHA OR&ccedil;AMENT&aacute;RIA, O CAMPO TipoParecer DA TABELA sac.dbo.Parecer MUDAR.
        if($dadosRead->idTipoReadequacao == 2){
            $tbPlanilhaAprovacao = new tbPlanilhaAprovacao();

            //BUSCAR VALOR TOTAL DA PLANILHA ATIVA
            $where = array();
            $where['a.IdPRONAC = ?'] = $idPronac;
            $where['a.stAtivo = ?'] = 'S';
            $PlanilhaAtiva = $tbPlanilhaAprovacao->valorTotalPlanilha($where)->current();

            //BUSCAR VALOR TOTAL DA PLANILHA DE READEQUADA
            $where = array();
            $where['a.IdPRONAC = ?'] = $idPronac;
            $where['a.tpPlanilha = ?'] = 'SR';
            $where['a.stAtivo = ?'] = 'N';
            $PlanilhaReadequada = $tbPlanilhaAprovacao->valorTotalPlanilha($where)->current();

            if($PlanilhaAtiva->Total > $PlanilhaReadequada->Total){
                $vlPlanilha = $PlanilhaAtiva->Total-$PlanilhaReadequada->Total;
                $campoTipoParecer = 4;
            } else {
                $vlPlanilha = $PlanilhaReadequada->Total-$PlanilhaAtiva->Total;
                $campoTipoParecer = 2;
            }
        }

        try {

            $Projetos = new Projetos();
            $enquadramentoDAO = new Admissibilidade_Model_Enquadramento();
            $buscaEnquadramento = $enquadramentoDAO->buscarDados($idPronac, null, false);

            $dadosProjeto = $Projetos->buscar(array('IdPRONAC = ?'=>$idPronac));
            if(count($dadosProjeto)>0){

                //CADASTRA OU ATUALIZA O PARECER DO TECNICO
                $parecerDAO = new Parecer();
                $dadosParecer = array(
                    'idPRONAC' => $idPronac,
                    'AnoProjeto' => $dadosProjeto[0]->AnoProjeto,
                    'Sequencial' => $dadosProjeto[0]->Sequencial,
                    'TipoParecer' => $campoTipoParecer,
                    'ParecerFavoravel' => $parecerProjeto,
                    'DtParecer' => MinC_Db_Expr::date(),
                    'NumeroReuniao' => null,
                    'ResumoParecer' => $dsParecer,
                    'SugeridoReal' => $vlPlanilha,
                    'Atendimento' => 'S',
                    'idEnquadramento' => $buscaEnquadramento['IdEnquadramento'],
                    'stAtivo' => 1,
                    'idTipoAgente' => 6, //Componente da Comiss&atilde;o
                    'Logon' => $this->idUsuario
                );

                foreach ($dadosParecer as $dp) {
                    $parecerAntigo = array(
                        'Atendimento' => 'S',
                        'stAtivo' => 0
                    );
                    $whereUpdateParecer = 'IdPRONAC = '.$idPronac;
                    $alteraParecer = $parecerDAO->alterar($parecerAntigo, $whereUpdateParecer);
                }

                $tbReadequacaoXParecer = new tbReadequacaoXParecer();
                $buscarParecer = $tbReadequacaoXParecer->buscarPareceresReadequacao(array('a.idReadequacao = ?'=>$idReadequacao, 'b.idTipoAgente =?'=>6))->current();
                if($buscarParecer){
                    $whereUpdateParecer = 'IdParecer = '.$buscarParecer->IdParecer;
                    $parecerDAO->alterar($dadosParecer, $whereUpdateParecer);
                    $idParecer = $buscarParecer->IdParecer;
                } else {
                    $idParecer = $parecerDAO->inserir($dadosParecer);
                }

                $tbReadequacaoXParecer = new tbReadequacaoXParecer();
                $parecerReadequacao = $tbReadequacaoXParecer->buscar(array('idReadequacao = ?'=>$idReadequacao, 'idParecer =?'=>$idParecer));
                if(count($parecerReadequacao) == 0){
                    $dadosInclusao = array(
                        'idReadequacao' => $idReadequacao,
                        'idParecer' => $idParecer
                    );
                    $tbReadequacaoXParecer->inserir($dadosInclusao);
                }
            }

            if(isset($_POST['finalizarAvaliacao']) && $_POST['finalizarAvaliacao'] == 1){

                $tbDistribuirReadequacao = new tbDistribuirReadequacao();
                $dDP = $tbDistribuirReadequacao->buscar(array('idReadequacao = ?'=>$idReadequacao, 'idUnidade =?'=>400, 'idAvaliador=?'=>$this->idUsuario));

                $objAcesso = new Acesso();
                if(count($dDP)>0){
                    //ATUALIZA A TABELA tbDistribuirReadequacao
                    $dadosDP = array();
                    $dadosDP['DtRetornoAvaliador'] = $objAcesso->getExpressionDate();
                    $whereDP = "idDistribuirReadequacao = ".$dDP[0]->idDistribuirReadequacao;
                    $x = $tbDistribuirReadequacao->update($dadosDP, $whereDP);

                    $reuniao = new Reuniao();
                    $raberta = $reuniao->buscarReuniaoAberta();
                    $idNrReuniao = ($raberta['stPlenaria'] == 'A') ? $raberta['idNrReuniao']+1 : $raberta['idNrReuniao'];

                    $read = $tbReadequacao->buscarReadequacao(array('idReadequacao =?'=>$idReadequacao))->current();

                    $stEstado = 0;
                    if($_POST['plenaria']){
                        $campoSiEncaminhamento = 8; // 8=Enviado &agrave; Plen&aacute;ria
                    } else {
                        $campoSiEncaminhamento = 9; // 9=Enviado para Checklist Publica&ccedil;&atilde;o
                        if(!in_array($read->idTipoReadequacao, array(2,3,10,12,15))){
                            $campoSiEncaminhamento = 15; // 15=Finaliza a readequa&ccedil;&atilde;o sem a necessidade de enviar para publica&ccedil;&atilde;o no DOU.
                            $stEstado = 1;
                        }
                    }

                    //ATUALIZA A TABELA tbReadequacao
                    $dados = array();
                    $dados['siEncaminhamento'] = $campoSiEncaminhamento; // Devolvido da an&aacute;lise t&eacute;cnica
                    $dados['idNrReuniao'] = $idNrReuniao;
                    $dados['stEstado'] = $stEstado;
                    if($parecerProjeto == 2){
                        $dados['stAnalise'] = 'AC';
                    } else {
                        $dados['stAnalise'] = 'IC';
                    }

                    $where = "idReadequacao = $idReadequacao";
                    $tbReadequacao = new tbReadequacao();
                    $tbReadequacao->update($dados, $where);

                    if($campoSiEncaminhamento != 8){

                        if($parecerProjeto == 2){ //Se for parecer favor&aacute;vel, atualiza os dados solicitados na readequa&ccedil;&atilde;o

                            // READEQUA&ccedil;&atilde;O DE PLANILHA OR&ccedil;AMENT&aacute;RIA
                            if($read->idTipoReadequacao == 2){
                                $Projetos = new Projetos();
                                $dadosPrj = $Projetos->find(array('IdPRONAC=?'=>$read->idPronac))->current();

                                $tbPlanilhaAprovacao = new tbPlanilhaAprovacao();
                                //BUSCAR VALOR TOTAL DA PLANILHA ATIVA
                                $where = array();
                                $where['a.IdPRONAC = ?'] = $read->idPronac;
                                $where['a.stAtivo = ?'] = 'S';
                                $PlanilhaAtiva = $tbPlanilhaAprovacao->valorTotalPlanilha($where)->current();

                                //BUSCAR VALOR TOTAL DA PLANILHA DE READEQUADA
                                $where = array();
                                $where['a.IdPRONAC = ?'] = $read->idPronac;
                                $where['a.tpPlanilha = ?'] = 'SR';
                                $where['a.stAtivo = ?'] = 'N';
                                $PlanilhaReadequada = $tbPlanilhaAprovacao->valorTotalPlanilha($where)->current();

                                if($PlanilhaAtiva->Total < $PlanilhaReadequada->Total){
                                    $TipoAprovacao = 2;
                                    $dadosPrj->Situacao = 'D28';
                                } else {
                                    $TipoAprovacao = 4;
                                    $dadosPrj->Situacao = 'D29';
                                }
                                $dadosPrj->save();

                                $AprovadoReal = number_format(($PlanilhaReadequada->Total-$PlanilhaAtiva->Total), 2, '.', '');
                                $tbAprovacao = new Aprovacao();
                                $dadosAprovacao = array(
                                    'IdPRONAC' => $read->idPronac,
                                    'AnoProjeto' => $dadosPrj->AnoProjeto,
                                    'Sequencial' => $dadosPrj->Sequencial,
                                    'TipoAprovacao' => $TipoAprovacao,
                                    'DtAprovacao' => $objAcesso->getExpressionDate(),
                                    'ResumoAprovacao' => 'Parecer favor&aacute;vel para readequa&ccedil;&atilde;o',
                                    'AprovadoReal' => $AprovadoReal,
                                    'Logon' => $this->idUsuario,
                                    'idReadequacao' => $idReadequacao
                                );
                                $idAprovacao = $tbAprovacao->inserir($dadosAprovacao);

                            // READEQUA&ccedil;&atilde;O DE ALTERA&ccedil;&atilde;O DE RAZ&atilde;O SOCIAL
                            } else if($read->idTipoReadequacao == 3){ //Se for readequa&ccedil;&atilde;o de altera&ccedil;&atilde;o de raz&atilde;o social, atualiza os dados na agentes.dbo.Nomes.
                                $Projetos = new Projetos();
                                $dadosPrj = $Projetos->find(array('IdPRONAC=?'=>$read->idPronac))->current();

                                $tbAprovacao = new Aprovacao();
                                $dadosAprovacao = array(
                                    'IdPRONAC' => $read->idPronac,
                                    'AnoProjeto' => $dadosPrj->AnoProjeto,
                                    'Sequencial' => $dadosPrj->Sequencial,
                                    'TipoAprovacao' => 8,
                                    'DtAprovacao' => $objAcesso->getExpressionDate(),
                                    'ResumoAprovacao' => 'Parecer favor&aacute;vel para readequa&ccedil;&atilde;o',
                                    'Logon' => $this->idUsuario,
                                    'idReadequacao' => $idReadequacao
                                );
                                $idAprovacao = $tbAprovacao->inserir($dadosAprovacao);

                            // READEQUA&ccedil;&atilde;O DE AGÊNCIA BANC&aacute;RIA
                            } else if($read->idTipoReadequacao == 4){ //Se for readequa&ccedil;&atilde;o de agência banc&aacute;ria, atualiza os dados na sac.dbo.PreProjeto.// READEQUA&ccedil;&atilde;O DE ALTERA&ccedil;&atilde;O DE RAZ&atilde;O SOCIAL
                                $Projetos = new Projetos();
                                $dadosPrj = $Projetos->buscar(array('IdPRONAC=?'=>$read->idPronac))->current();
                                $agenciaBancaria = str_replace('-', '', $read->dsSolicitacao);

                                $tblContaBancaria = new ContaBancaria();
                                $arrayDadosBancarios = array(
                                    'Agencia' => $agenciaBancaria,
                                    'ContaBloqueada' => '000000000000',
                                    'DtLoteRemessaCB' => NULL,
                                    'LoteRemessaCB' => '00000',
                                    'OcorrenciaCB' => '000',
                                    'ContaLivre' => '000000000000',
                                    'DtLoteRemessaCL' => NULL,
                                    'LoteRemessaCL' => '00000',
                                    'OcorrenciaCL' => '000',
                                    'Logon' => $this->idUsuario,
                                    'idPronac' => $read->idPronac
                                );
                                $whereDadosBancarios['AnoProjeto = ?'] = $dadosPrj->AnoProjeto;
                                $whereDadosBancarios['Sequencial = ?'] = $dadosPrj->Sequencial;
                                $tblContaBancaria->alterar($arrayDadosBancarios, $whereDadosBancarios);

                            // READEQUA&ccedil;&atilde;O DE SINOPSE DA OBRA
                            } else if($read->idTipoReadequacao == 5){ //Se for readequa&ccedil;&atilde;o de sinopse da obra, atualiza os dados na sac.dbo.PreProjeto.
                                $Projetos = new Projetos();
                                $dadosPrj = $Projetos->buscar(array('IdPRONAC=?'=>$read->idPronac))->current();

                                $PrePropojeto = new Proposta_Model_DbTable_PreProjeto();
                                $dadosPreProjeto = $PrePropojeto->find(array('idPreProjeto=?'=>$dadosPrj->idProjeto))->current();
                                $dadosPreProjeto->Sinopse = $read->dsSolicitacao;
                                $dadosPreProjeto->save();

                            // READEQUA&ccedil;&atilde;O DE IMPACTO AMBIENTAL
                            } else if($read->idTipoReadequacao == 6){ //Se for readequa&ccedil;&atilde;o de impacto ambiental, atualiza os dados na sac.dbo.PreProjeto.
                                $Projetos = new Projetos();
                                $dadosPrj = $Projetos->buscar(array('IdPRONAC=?'=>$read->idPronac))->current();

                                $PrePropojeto = new Proposta_Model_DbTable_PreProjeto();
                                $dadosPreProjeto = $PrePropojeto->find(array('idPreProjeto=?'=>$dadosPrj->idProjeto))->current();
                                $dadosPreProjeto->ImpactoAmbiental = $read->dsSolicitacao;
                                $dadosPreProjeto->save();

                            // READEQUA&ccedil;&atilde;O DE ESPECIFICA&ccedil;&atilde;O T&eacute;CNICA
                            } else if($read->idTipoReadequacao == 7){ //Se for readequa&ccedil;&atilde;o de especifica&ccedil;&atilde;o t&eacute;cnica, atualiza os dados na sac.dbo.PreProjeto.
                                $Projetos = new Projetos();
                                $dadosPrj = $Projetos->buscar(array('IdPRONAC=?'=>$read->idPronac))->current();

                                $PrePropojeto = new Proposta_Model_DbTable_PreProjeto();
                                $dadosPreProjeto = $PrePropojeto->find(array('idPreProjeto=?'=>$dadosPrj->idProjeto))->current();
                                $dadosPreProjeto->EspecificacaoTecnica = $read->dsSolicitacao;
                                $dadosPreProjeto->save();

                            // READEQUA&ccedil;&atilde;O DE ESTRAT&eacute;GIA DE EXECU&ccedil;&atilde;O
                            } else if($read->idTipoReadequacao == 8){ //Se for readequa&ccedil;&atilde;o de estrat&eacute;gia de execu&ccedil;&atilde;o, atualiza os dados na sac.dbo.PreProjeto.
                                $Projetos = new Projetos();
                                $dadosPrj = $Projetos->buscar(array('IdPRONAC=?'=>$read->idPronac))->current();

                                $PrePropojeto = new Proposta_Model_DbTable_PreProjeto();
                                $dadosPreProjeto = $PrePropojeto->find(array('idPreProjeto=?'=>$dadosPrj->idProjeto))->current();
                                $dadosPreProjeto->EstrategiadeExecucao = $read->dsSolicitacao;
                                $dadosPreProjeto->save();

                            // READEQUA&ccedil;&atilde;O DE LOCAL DE REALIZA&ccedil;&atilde;O
                            } else if($read->idTipoReadequacao == 9){ //Se for readequa&ccedil;&atilde;o de local de realiza&ccedil;&atilde;o, atualiza os dados na sac.dbo.Abrangencia.
                                $Abrangencia = new Proposta_Model_DbTable_Abrangencia();
                                $tbAbrangencia = new tbAbrangencia();
                                $abrangencias = $tbAbrangencia->buscar(array('idReadequacao=?'=>$idReadequacao));
                                foreach ($abrangencias as $abg) {
                                    $Projetos = new Projetos();
                                    $dadosPrj = $Projetos->buscar(array('IdPRONAC=?'=>$read->idPronac))->current();

                                    //Se n&atilde;o houve avali&ccedil;&atilde;o do conselheiro, pega a avalia&ccedil;&atilde;o t&eacute;cnica como referencia.
                                    $avaliacao = $abg->tpAnaliseComissao;
                                    if($abg->tpAnaliseComissao == 'N'){
                                        $avaliacao = $abg->tpAnaliseTecnica;
                                    }

                                    //Se a avalia&ccedil;&atilde;o foi deferida, realiza as mudan&ccedil;as necess&aacute;rias na tabela original.
                                    if($avaliacao == 'D'){
                                        if($abg->tpSolicitacao == 'E'){ //Se a abrangencia foi exclu&iacute;da, atualiza os status da abrangencia na sac.dbo.Abrangencia
                                            $Abrangencia->delete(array('idProjeto = ?'=>$dadosPrj->idProjeto, 'idPais = ?'=>$abg->idPais, 'idUF = ?'=>$abg->idUF, 'idMunicipioIBGE = ?'=>$abg->idMunicipioIBGE));

                                        } else if($abg->tpSolicitacao == 'I') { //Se a abangência foi inclu&iacute;da, cria um novo registro na tabela sac.dbo.Abrangencia
                                            $novoLocalRead = array();
                                            $novoLocalRead['idProjeto'] = $dadosPrj->idProjeto;
                                            $novoLocalRead['idPais'] = $abg->idPais;
                                            $novoLocalRead['idUF'] = $abg->idUF;
                                            $novoLocalRead['idMunicipioIBGE'] = $abg->idMunicipioIBGE;
                                            $novoLocalRead['Usuario'] = $this->idUsuario;
                                            $novoLocalRead['stAbrangencia'] = true;
                                            $Abrangencia->salvar($novoLocalRead);
                                        }
                                    }
                                }

                                $dadosAbr = array();
                                $dadosAbr['stAtivo'] = 'N';
                                $whereAbr = "idPronac = $read->idPronac AND idReadequacao = $idReadequacao";
                                $tbAbrangencia->update($dadosAbr, $whereAbr);

                            // READEQUA&ccedil;&atilde;O DE ALTERA&ccedil;&atilde;O DE PROPONENTE
                            } else if($read->idTipoReadequacao == 10){ //Se for readequa&ccedil;&atilde;o de altera&ccedil;&atilde;o de proponente, atualiza os dados na sac.dbo.Projetos.

                                $Projetos = new Projetos();
                                $dadosPrj = $Projetos->find(array('IdPRONAC=?'=>$read->idPronac))->current();

                                $tbAprovacao = new Aprovacao();
                                $dadosAprovacao = array(
                                    'IdPRONAC' => $read->idPronac,
                                    'AnoProjeto' => $dadosPrj->AnoProjeto,
                                    'Sequencial' => $dadosPrj->Sequencial,
                                    'TipoAprovacao' => 8,
                                    'DtAprovacao' => $objAcesso->getExpressionDate(),
                                    'ResumoAprovacao' => 'Parecer favor&aacute;vel para readequa&ccedil;&atilde;o',
                                    'Logon' => $this->idUsuario,
                                    'idReadequacao' => $idReadequacao
                                );
                                $idAprovacao = $tbAprovacao->inserir($dadosAprovacao);

                            // READEQUA&ccedil;&atilde;O DE PLANO DE DISTRIBUI&ccedil;&atilde;O
                            } else if($read->idTipoReadequacao == 11){ //Se for readequa&ccedil;&atilde;o de plano de distribui&ccedil;&atilde;o, atualiza os dados na sac.dbo.PlanoDistribuicaoProduto.
                                $PlanoDistribuicaoProduto = new Proposta_Model_DbTable_PlanoDistribuicaoProduto();
                                $tbPlanoDistribuicao = new tbPlanoDistribuicao();
                                $planosDistribuicao = $tbPlanoDistribuicao->buscar(array('idReadequacao=?'=>$idReadequacao));

                                foreach ($planosDistribuicao as $plano) {
                                    $Projetos = new Projetos();
                                    $dadosPrj = $Projetos->buscar(array('IdPRONAC=?'=>$read->idPronac))->current();

                                    //Se n&atilde;o houve avali&ccedil;&atilde;o do conselheiro, pega a avalia&ccedil;&atilde;o t&eacute;cnica como referencia.
                                    $avaliacao = $plano->tpAnaliseComissao;
                                    if($plano->tpAnaliseComissao == 'N'){
                                        $avaliacao = $plano->tpAnaliseTecnica;
                                    }

                                    //Se a avalia&ccedil;&atilde;o foi deferida, realiza as mudan&ccedil;as necess&aacute;rias na tabela original.
                                    if($avaliacao == 'D'){
                                        if($plano->tpSolicitacao == 'E'){ //Se o plano de distribui&ccedil;&atilde;o foi exclu&iacute;do, atualiza os status do plano na sac.dbo.PlanoDistribuicaoProduto
                                            $PlanoDistribuicaoProduto->delete(array('idProjeto = ?'=>$dadosPrj->idProjeto, 'idProduto = ?'=>$plano->idProduto, 'Area = ?'=>$plano->cdArea, 'Segmento = ?'=>$plano->cdSegmento));

                                        } else if($plano->tpSolicitacao == 'I') { //Se o plano de distribui&ccedil;&atilde;o foi inclu&iacute;do, cria um novo registro na tabela sac.dbo.PlanoDistribuicaoProduto
                                            $novoPlanoDistRead = array();
                                            $novoPlanoDistRead['idProjeto'] = $dadosPrj->idProjeto;
                                            $novoPlanoDistRead['idProduto'] = $plano->idProduto;
                                            $novoPlanoDistRead['Area'] = $plano->cdArea;
                                            $novoPlanoDistRead['Segmento'] = $plano->cdSegmento;
                                            $novoPlanoDistRead['idPosicaoDaLogo'] = $plano->idPosicaoLogo;
                                            $novoPlanoDistRead['QtdeProduzida'] = $plano->qtProduzida;
                                            $novoPlanoDistRead['QtdePatrocinador'] = $plano->qtPatrocinador;
                                            $novoPlanoDistRead['QtdeProponente'] = $plano->qtProponente;
                                            $novoPlanoDistRead['QtdeOutros'] = $plano->qtOutros;
                                            $novoPlanoDistRead['QtdeVendaNormal'] = $plano->qtVendaNormal;
                                            $novoPlanoDistRead['QtdeVendaPromocional'] = $plano->qtVendaPromocional;
                                            $novoPlanoDistRead['PrecoUnitarioNormal'] = $plano->vlUnitarioNormal;
                                            $novoPlanoDistRead['PrecoUnitarioPromocional'] = $plano->vlUnitarioPromocional;
                                            $novoPlanoDistRead['stPrincipal'] = 0;
                                            $novoPlanoDistRead['Usuario'] = $this->idUsuario;
                                            $novoPlanoDistRead['dsJustificativaPosicaoLogo'] = null;
                                            $novoPlanoDistRead['stPlanoDistribuicaoProduto'] = 1;
                                            $PlanoDistribuicaoProduto->inserir($novoPlanoDistRead);
                                        }
                                    }
                                }

                                $dadosPDD = array();
                                $dadosPDD['stAtivo'] = 'N';
                                $wherePDD = "idPronac = $read->idPronac AND idReadequacao = $idReadequacao";
                                $tbPlanoDistribuicao->update($dadosPDD, $wherePDD);

                            // READEQUA&ccedil;&atilde;O DE NOME DO PROJETO
                            } else if($read->idTipoReadequacao == 12){ //Se for readequa&ccedil;&atilde;o de nome do projeto, insere o registo na tela de Checklist de Publica&ccedil;&atilde;o.
                                $Projetos = new Projetos();
                                $dadosPrj = $Projetos->find(array('IdPRONAC=?'=>$read->idPronac))->current();

                                $tbAprovacao = new Aprovacao();
                                $dadosAprovacao = array(
                                    'IdPRONAC' => $read->idPronac,
                                    'AnoProjeto' => $dadosPrj->AnoProjeto,
                                    'Sequencial' => $dadosPrj->Sequencial,
                                    'TipoAprovacao' => 8,
                                    'DtAprovacao' => $objAcesso->getExpressionDate(),
                                    'ResumoAprovacao' => 'Parecer favor&aacute;vel para readequa&ccedil;&atilde;o',
                                    'Logon' => $this->idUsuario,
                                    'idReadequacao' => $idReadequacao
                                );
                                $idAprovacao = $tbAprovacao->inserir($dadosAprovacao);

                            // READEQUA&ccedil;&atilde;O DE PERÍODO DE EXECU&ccedil;&atilde;O
                            } else if($read->idTipoReadequacao == 13){ //Se for readequa&ccedil;&atilde;o de per&iacute;odo de execu&ccedil;&atilde;o, atualiza os dados na sac.dbo.Projetos.
                                $dtFimExecucao = Data::dataAmericana($read->dsSolicitacao);
                                $Projetos = new Projetos();
                                $dadosPrj = $Projetos->find(array('IdPRONAC=?'=>$read->idPronac))->current();
                                $dadosPrj->DtFimExecucao = $dtFimExecucao;
                                $dadosPrj->save();

                            // READEQUA&ccedil;&atilde;O DE PLANO DE DIVULGA&ccedil;&atilde;O
                            } else if($read->idTipoReadequacao == 14){ //Se for readequa&ccedil;&atilde;o de plano de divulgacao, atualiza os dados na sac.dbo.PlanoDeDivulgacao.
                                $PlanoDeDivulgacao = new PlanoDeDivulgacao();
                                $tbPlanoDivulgacao = new tbPlanoDivulgacao();
                                $planosDivulgacao = $tbPlanoDivulgacao->buscar(array('idReadequacao=?'=>$idReadequacao));

                                foreach ($planosDivulgacao as $plano) {
                                    $Projetos = new Projetos();
                                    $dadosPrj = $Projetos->buscar(array('IdPRONAC=?'=>$read->idPronac))->current();

                                    //Se n&atilde;o houve avali&ccedil;&atilde;o do conselheiro, pega a avalia&ccedil;&atilde;o t&eacute;cnica como referencia.
                                    $avaliacao = $plano->tpAnaliseComissao;
                                    if($plano->tpAnaliseComissao == 'N'){
                                        $avaliacao = $plano->tpAnaliseTecnica;
                                    }

                                    //Se a avalia&ccedil;&atilde;o foi deferida, realiza as mudan&ccedil;as necess&aacute;rias na tabela original.
                                    if($avaliacao == 'D'){
                                        if($plano->tpSolicitacao == 'E'){ //Se o plano de divulga&ccedil;&atilde;o foi exclu&iacute;do, atualiza os status do plano na sac.dbo.PlanoDeDivulgacao
                                            $PlanoDivulgacaoEmQuestao = $PlanoDeDivulgacao->buscar(array('idProjeto = ?'=>$dadosPrj->idProjeto, 'idPeca = ?'=>$plano->idPeca, 'idVeiculo = ?'=>$plano->idVeiculo))->current();
                                            $tbLogomarca = new tbLogomarca();
                                            $dadosLogomarcaDaDivulgacao = $tbLogomarca->buscar(array('idPlanoDivulgacao = ?' => $PlanoDivulgacaoEmQuestao->idPlanoDivulgacao))->current();
                                            if (!empty($dadosLogomarcaDaDivulgacao)) {
                                                $dadosLogomarcaDaDivulgacao->delete();
                                            }
                                            if (!empty($PlanoDivulgacaoEmQuestao)) {
                                                $PlanoDivulgacaoEmQuestao->delete();
                                            }

                                        } else if($plano->tpSolicitacao == 'I') { //Se o plano de divulga&ccedil;&atilde;o foi inclu&iacute;do, cria um novo registro na tabela sac.dbo.PlanoDeDivulgacao
                                            $novoPlanoDivRead = array();
                                            $novoPlanoDivRead['idProjeto'] = $dadosPrj->idProjeto;
                                            $novoPlanoDivRead['idPeca'] = $plano->idPeca;
                                            $novoPlanoDivRead['idVeiculo'] = $plano->idVeiculo;
                                            $novoPlanoDivRead['Usuario'] = $this->idUsuario;
                                            $novoPlanoDivRead['siPlanoDeDivulgacao'] = 0;
                                            $novoPlanoDivRead['idDocumento'] = null;
                                            $novoPlanoDivRead['stPlanoDivulgacao'] = 1;
                                            $PlanoDeDivulgacao->inserir($novoPlanoDivRead);
                                        }
                                    }
                                }

                                $dadosPDD = array();
                                $dadosPDD['stAtivo'] = 'N';
                                $wherePDD = "idPronac = $read->idPronac AND idReadequacao = $idReadequacao";
                                $tbPlanoDivulgacao->update($dadosPDD, $wherePDD);

                            // READEQUA&ccedil;&atilde;O DE RESUMO DO PROJETO
                            } else if($read->idTipoReadequacao == 15){ //Se for readequa&ccedil;&atilde;o de resumo do projeto, atualiza os dados na sac.dbo.PreProjeto.
                                $Projetos = new Projetos();
                                $dadosPrj = $Projetos->find(array('IdPRONAC=?'=>$read->idPronac))->current();

                                $tbAprovacao = new Aprovacao();
                                $dadosAprovacao = array(
                                    'IdPRONAC' => $read->idPronac,
                                    'AnoProjeto' => $dadosPrj->AnoProjeto,
                                    'Sequencial' => $dadosPrj->Sequencial,
                                    'TipoAprovacao' => 8,
                                    'DtAprovacao' => $objAcesso->getExpressionDate(),
                                    'ResumoAprovacao' => 'Parecer favor&aacute;vel para readequa&ccedil;&atilde;o',
                                    'Logon' => $this->idUsuario,
                                    'idReadequacao' => $idReadequacao
                                );
                                $idAprovacao = $tbAprovacao->inserir($dadosAprovacao);

                            // READEQUA&ccedil;&atilde;O DE OBJETIVOS
                            } else if($read->idTipoReadequacao == 16){ //Se for readequa&ccedil;&atilde;o de objetivos, atualiza os dados na sac.dbo.PreProjeto.
                                $Projetos = new Projetos();
                                $dadosPrj = $Projetos->buscar(array('IdPRONAC=?'=>$read->idPronac))->current();

                                $PrePropojeto = new Proposta_Model_DbTable_PreProjeto();
                                $dadosPreProjeto = $PrePropojeto->find(array('idPreProjeto=?'=>$dadosPrj->idProjeto))->current();
                                $dadosPreProjeto->Objetivos = $read->dsSolicitacao;
                                $dadosPreProjeto->save();

                            // READEQUA&ccedil;&atilde;O DE JUSTIFICATIVA
                            } else if($read->idTipoReadequacao == 17){ //Se for readequa&ccedil;&atilde;o de justificativa, atualiza os dados na sac.dbo.PreProjeto.
                                $Projetos = new Projetos();
                                $dadosPrj = $Projetos->buscar(array('IdPRONAC=?'=>$read->idPronac))->current();

                                $PrePropojeto = new Proposta_Model_DbTable_PreProjeto();
                                $dadosPreProjeto = $PrePropojeto->find(array('idPreProjeto=?'=>$dadosPrj->idProjeto))->current();
                                $dadosPreProjeto->Justificativa = $read->dsSolicitacao;
                                $dadosPreProjeto->save();

                            // READEQUA&ccedil;&atilde;O DE ACESSIBILIDADE
                            } else if($read->idTipoReadequacao == 18){ //Se for readequa&ccedil;&atilde;o de acesibilidade, atualiza os dados na sac.dbo.PreProjeto.
                                $Projetos = new Projetos();
                                $dadosPrj = $Projetos->buscar(array('IdPRONAC=?'=>$read->idPronac))->current();

                                $PrePropojeto = new Proposta_Model_DbTable_PreProjeto();
                                $dadosPreProjeto = $PrePropojeto->find(array('idPreProjeto=?'=>$dadosPrj->idProjeto))->current();
                                $dadosPreProjeto->Acessibilidade = $read->dsSolicitacao;
                                $dadosPreProjeto->save();

                            // READEQUA&ccedil;&atilde;O DE DEMOCRATIZA&ccedil;&atilde;O DE ACESSO
                            } else if($read->idTipoReadequacao == 19){ //Se for readequa&ccedil;&atilde;o de democratiza&ccedil;&atilde;o de acesso, atualiza os dados na sac.dbo.PreProjeto.
                                $Projetos = new Projetos();
                                $dadosPrj = $Projetos->buscar(array('IdPRONAC=?'=>$read->idPronac))->current();

                                $PrePropojeto = new Proposta_Model_DbTable_PreProjeto();
                                $dadosPreProjeto = $PrePropojeto->find(array('idPreProjeto=?'=>$dadosPrj->idProjeto))->current();
                                $dadosPreProjeto->DemocratizacaoDeAcesso = $read->dsSolicitacao;
                                $dadosPreProjeto->save();

                            // READEQUA&ccedil;&atilde;O DE ETAPAS DE TRABALHO
                            } else if($read->idTipoReadequacao == 20){ //Se for readequa&ccedil;&atilde;o de etapas de trabalho, atualiza os dados na sac.dbo.PreProjeto.
                                $Projetos = new Projetos();
                                $dadosPrj = $Projetos->buscar(array('IdPRONAC=?'=>$read->idPronac))->current();

                                $PrePropojeto = new Proposta_Model_DbTable_PreProjeto();
                                $dadosPreProjeto = $PrePropojeto->find(array('idPreProjeto=?'=>$dadosPrj->idProjeto))->current();
                                $dadosPreProjeto->EtapaDeTrabalho = $read->dsSolicitacao;
                                $dadosPreProjeto->save();

                            // READEQUA&ccedil;&atilde;O DE FICHA T&eacute;CNICA
                            } else if($read->idTipoReadequacao == 21){ //Se for readequa&ccedil;&atilde;o de ficha t&eacute;cnica, atualiza os dados na sac.dbo.PreProjeto.
                                $Projetos = new Projetos();
                                $dadosPrj = $Projetos->buscar(array('IdPRONAC=?'=>$read->idPronac))->current();

                                $PrePropojeto = new Proposta_Model_DbTable_PreProjeto();
                                $dadosPreProjeto = $PrePropojeto->find(array('idPreProjeto=?'=>$dadosPrj->idProjeto))->current();
                                $dadosPreProjeto->FichaTecnica = $read->dsSolicitacao;
                                $dadosPreProjeto->save();
                            }
                        }

                        //Atualiza a tabela tbDistribuirReadequacao
                        $dados = array();
                        $dados['stValidacaoCoordenador'] = 1;
                        $dados['DtValidacaoCoordenador'] = $objAcesso->getExpressionDate();
                        $dados['idCoordenador'] = $this->idUsuario;
                        $where = "idReadequacao = $idReadequacao";
                        $tbDistribuirReadequacao = new tbDistribuirReadequacao();
                        $tbDistribuirReadequacao->update($dados, $where);
                    }

                    parent::message("A avalia&ccedil;&atilde;o da readequa&ccedil;&atilde;o foi finalizada com sucesso!", "readequacoes/analisar-readequacoes-cnic", "CONFIRM");

                } else {
                    parent::message("Erro ao avaliar a readequa&ccedil;&atilde;o!", "form-avaliar-readequacao-cnic?id=$idReadequacao", "ERROR");
                }
            }
            $idReadequacao = Seguranca::encrypt($idReadequacao);
            parent::message("Dados salvos com sucesso!", "readequacoes/form-avaliar-readequacao-cnic?id=$idReadequacao", "CONFIRM");

        } // fecha try
        catch (Exception $e) {
            parent::message($e->getMessage(), "readequacoes/form-avaliar-readequacao-cnic?id=$idReadequacao", "ERROR");
        }
	}

    /*
     * Alterada em 15/05/2015
     * Fun&ccedil;&atilde;o criada para finalizar ou encaminhar a readequa&ccedil;&atilde;o para checklist de publica&ccedil;&atilde;o.
    */
    public function encaminharReadequacaoChecklistAction() {

        if($this->idPerfil != 93 && $this->idPerfil != 94 && $this->idPerfil != 121 && $this->idPerfil != 122 && $this->idPerfil != 123){
            parent::message("Você n&atilde;o tem permiss&atilde;o para acessar essa &aacute;rea do sistema!", "principal", "ALERT");
        }
        // TODO: quando finalizar, mantem filtro de pronac caso estiver marca
        $auth = Zend_Auth::getInstance();
        $idCoordenador = $auth->getIdentity()->usu_codigo;
        $pronacSelecionado = $this->_request->getParam("pronac");
        $urlComplemento = ($pronacSelecionado) ? "&pronac=$pronacSelecionado" : "";

        $get = Zend_Registry::get('get');
        $idReadequacao = (int) $get->id;

        $reuniao = new Reuniao();
        $raberta = $reuniao->buscarReuniaoAberta();
        $idNrReuniao = ($raberta['stPlenaria'] == 'A') ? $raberta['idNrReuniao']+1 : $raberta['idNrReuniao'];

        $tbReadequacaoXParecer = new tbReadequacaoXParecer();
        $dadosParecer = $tbReadequacaoXParecer->buscar(array('idReadequacao=?'=>$idReadequacao));
        foreach ($dadosParecer as $key => $dp) {
            $pareceres = array();
            $pareceres[$key] = $dp->idParecer;
        }

        $Parecer = new Parecer();
        $parecerTecnico = $Parecer->buscar(array('IdParecer=(?)'=>$pareceres), array('IdParecer'))->current();

        $tbReadequacao = new tbReadequacao();
        $read = $tbReadequacao->buscarReadequacao(array('idReadequacao =?'=>$idReadequacao))->current();

        if($parecerTecnico->ParecerFavoravel == 2){ //Se for parecer favor&aacute;vel, atualiza os dados solicitados na readequa&ccedil;&atilde;o
            // READEQUA&ccedil;&atilde;O DE PLANILHA OR&ccedil;AMENT&aacute;RIA
            if($read->idTipoReadequacao == 2){
                $Projetos = new Projetos();
                $dadosPrj = $Projetos->find(array('IdPRONAC=?'=>$read->idPronac))->current();

                $tbPlanilhaAprovacao = new tbPlanilhaAprovacao();
                //BUSCAR VALOR TOTAL DA PLANILHA ATIVA
                $wherePlanilhaAtiva = array();
                $wherePlanilhaAtiva['a.IdPRONAC = ?'] = $read->idPronac;
                $wherePlanilhaAtiva['a.stAtivo = ?'] = 'S';
                $PlanilhaAtiva = $tbPlanilhaAprovacao->valorTotalPlanilha($wherePlanilhaAtiva)->current();

                //BUSCAR VALOR TOTAL DA PLANILHA DE READEQUADA
                $whereTotalPlanilha = array();
                $whereTotalPlanilha['a.IdPRONAC = ?'] = $read->idPronac;
                $whereTotalPlanilha['a.tpPlanilha = ?'] = 'SR';
                $whereTotalPlanilha['a.stAtivo = ?'] = 'N';
                $PlanilhaReadequada = $tbPlanilhaAprovacao->valorTotalPlanilha($whereTotalPlanilha)->current();

                // chama SP que verifica o tipo do remanejamento
                $spTipoDeReadequacaoOrcamentaria = new spTipoDeReadequacaoOrcamentaria();
                $TipoDeReadequacao = $spTipoDeReadequacaoOrcamentaria->exec($read->idPronac);

                // complementacao
                if ($TipoDeReadequacao[0]['TipoDeReadequacao'] == 'CO') {
                    $TipoAprovacao = 2;
                    $dadosPrj->Situacao = 'D28';
                    $dadosPrj->ProvidenciaTomada = 'Aguardando portaria de complementa&ccedil;&atilde;o';
                    $dadosPrj->Logon = $auth->getIdentity()->usu_codigo;
                } else if ($TipoDeReadequacao[0]['TipoDeReadequacao'] == 'RE'){
                    // reducao
                    $TipoAprovacao = 4;
                    $dadosPrj->Situacao = 'D29';
                    $dadosPrj->ProvidenciaTomada = 'Aguardando portaria de redu&ccedil;&atilde;o';
                    $dadosPrj->Logon = $auth->getIdentity()->usu_codigo;
                }

                // insere somente em reducao ou complementacao
                if ($TipoDeReadequacao[0]['TipoDeReadequacao'] == 'CO' || $TipoDeReadequacao[0]['TipoDeReadequacao'] == 'RE') {

                    $dadosPrj->save();
                    $tbAprovacao = new Aprovacao();
                    $dadosAprovacao = array(
                        'IdPRONAC' => $read->idPronac,
                        'AnoProjeto' => $dadosPrj->AnoProjeto,
                        'Sequencial' => $dadosPrj->Sequencial,
                        'TipoAprovacao' => $TipoAprovacao,
                        $objAcesso = new Acesso();
                        'DtAprovacao' => $objAcesso->getExpressionDate(),
                        'ResumoAprovacao' => 'Parecer favor&aacute;vel para readequa&ccedil;&atilde;o',
                        #'AprovadoReal' => $AprovadoReal,
                        'AprovadoReal' => $TipoDeReadequacao[0]['vlReadequado'], //Alterado pelo valor retornado pela Store
                        'Logon' => $auth->getIdentity()->usu_codigo,
                        'idReadequacao' => $idReadequacao
                    );

		    $idAprovacao = $tbAprovacao->inserir($dadosAprovacao);
                }

                // READEQUA&ccedil;&atilde;O DE ALTERA&ccedil;&atilde;O DE RAZ&atilde;O SOCIAL
            } else if($read->idTipoReadequacao == 3){ //Se for readequa&ccedil;&atilde;o de altera&ccedil;&atilde;o de raz&atilde;o social, atualiza os dados na agentes.dbo.Nomes.
                $Projetos = new Projetos();
                $dadosPrj = $Projetos->find(array('IdPRONAC=?'=>$read->idPronac))->current();

                $tbAprovacao = new Aprovacao();
                $dadosAprovacao = array(
                    'IdPRONAC' => $read->idPronac,
                    'AnoProjeto' => $dadosPrj->AnoProjeto,
                    'Sequencial' => $dadosPrj->Sequencial,
                    'TipoAprovacao' => 8,
                    'DtAprovacao' => $tbAprovacao->getExpressionDate(),
                    'ResumoAprovacao' => 'Parecer favor&aacute;vel para readequa&ccedil;&atilde;o',
                    'Logon' => $auth->getIdentity()->usu_codigo,
                    'idReadequacao' => $idReadequacao
                );
                $idAprovacao = $tbAprovacao->inserir($dadosAprovacao);

            // READEQUA&ccedil;&atilde;O DE AGÊNCIA BANC&aacute;RIA
            } else if($read->idTipoReadequacao == 4){ //Se for readequa&ccedil;&atilde;o de agência banc&aacute;ria, atualiza os dados na sac.dbo.PreProjeto.
                $Projetos = new Projetos();
                $dadosPrj = $Projetos->buscar(array('IdPRONAC=?'=>$read->idPronac))->current();
                $agenciaBancaria = str_replace('-', '', $read->dsSolicitacao);

                $tblContaBancaria = new ContaBancaria();
                $arrayDadosBancarios = array(
                    'Agencia' => $agenciaBancaria,
                    'ContaBloqueada' => '000000000000',
                    'DtLoteRemessaCB' => NULL,
                    'LoteRemessaCB' => '00000',
                    'OcorrenciaCB' => '000',
                    'ContaLivre' => '000000000000',
                    'DtLoteRemessaCL' => NULL,
                    'LoteRemessaCL' => '00000',
                    'OcorrenciaCL' => '000',
                    'Logon' => $auth->getIdentity()->usu_codigo,
                    'idPronac' => $read->idPronac
                );
                $whereDadosBancarios['AnoProjeto = ?'] = $dadosPrj->AnoProjeto;
                $whereDadosBancarios['Sequencial = ?'] = $dadosPrj->Sequencial;
                $tblContaBancaria->alterar($arrayDadosBancarios, $whereDadosBancarios);

            // READEQUA&ccedil;&atilde;O DE SINOPSE DA OBRA
            } else if($read->idTipoReadequacao == 5){ //Se for readequa&ccedil;&atilde;o de sinopse da obra, atualiza os dados na sac.dbo.PreProjeto.
                $Projetos = new Projetos();
                $dadosPrj = $Projetos->buscar(array('IdPRONAC=?'=>$read->idPronac))->current();

                $PrePropojeto = new Proposta_Model_DbTable_PreProjeto();
                $dadosPreProjeto = $PrePropojeto->find(array('idPreProjeto=?'=>$dadosPrj->idProjeto))->current();
                $dadosPreProjeto->Sinopse = $read->dsSolicitacao;
                $dadosPreProjeto->save();

            // READEQUA&ccedil;&atilde;O DE IMPACTO AMBIENTAL
            } else if($read->idTipoReadequacao == 6){ //Se for readequa&ccedil;&atilde;o de impacto ambiental, atualiza os dados na sac.dbo.PreProjeto.
                $Projetos = new Projetos();
                $dadosPrj = $Projetos->buscar(array('IdPRONAC=?'=>$read->idPronac))->current();

                $PrePropojeto = new Proposta_Model_DbTable_PreProjeto();
                $dadosPreProjeto = $PrePropojeto->find(array('idPreProjeto=?'=>$dadosPrj->idProjeto))->current();
                $dadosPreProjeto->ImpactoAmbiental = $read->dsSolicitacao;
                $dadosPreProjeto->save();

            // READEQUA&ccedil;&atilde;O DE ESPECIFICA&ccedil;&atilde;O T&eacute;CNICA
            } else if($read->idTipoReadequacao == 7){ //Se for readequa&ccedil;&atilde;o de especifica&ccedil;&atilde;o t&eacute;cnica, atualiza os dados na sac.dbo.PreProjeto.
                $Projetos = new Projetos();
                $dadosPrj = $Projetos->buscar(array('IdPRONAC=?'=>$read->idPronac))->current();

                $PrePropojeto = new Proposta_Model_DbTable_PreProjeto();
                $dadosPreProjeto = $PrePropojeto->find(array('idPreProjeto=?'=>$dadosPrj->idProjeto))->current();
                $dadosPreProjeto->EspecificacaoTecnica = $read->dsSolicitacao;
                $dadosPreProjeto->save();

            // READEQUA&ccedil;&atilde;O DE ESTRAT&eacute;GIA DE EXECU&ccedil;&atilde;O
            } else if($read->idTipoReadequacao == 8){ //Se for readequa&ccedil;&atilde;o de estrat&eacute;gia de execu&ccedil;&atilde;o, atualiza os dados na sac.dbo.PreProjeto.
                $Projetos = new Projetos();
                $dadosPrj = $Projetos->buscar(array('IdPRONAC=?'=>$read->idPronac))->current();

                $PrePropojeto = new Proposta_Model_DbTable_PreProjeto();
                $dadosPreProjeto = $PrePropojeto->find(array('idPreProjeto=?'=>$dadosPrj->idProjeto))->current();
                $dadosPreProjeto->EstrategiadeExecucao = $read->dsSolicitacao;
                $dadosPreProjeto->save();

            // READEQUA&ccedil;&atilde;O DE LOCAL DE REALIZA&ccedil;&atilde;O
            } else if($read->idTipoReadequacao == 9){ //Se for readequa&ccedil;&atilde;o de local de realiza&ccedil;&atilde;o, atualiza os dados na sac.dbo.Abrangencia.
                $Abrangencia = new Proposta_Model_DbTable_Abrangencia();

                $tbAbrangencia = new tbAbrangencia();
                $abrangencias = $tbAbrangencia->buscar(array('idReadequacao=?'=>$idReadequacao));
                foreach ($abrangencias as $abg) {
                    $Projetos = new Projetos();
                    $dadosPrj = $Projetos->buscar(array('IdPRONAC=?'=>$read->idPronac))->current();

                    //Se n&atilde;o houve avali&ccedil;&atilde;o do conselheiro, pega a avalia&ccedil;&atilde;o t&eacute;cnica como referencia.
                    $avaliacao = $abg->tpAnaliseComissao;
                    if($abg->tpAnaliseComissao == 'N'){
                        $avaliacao = $abg->tpAnaliseTecnica;
                    }

                    //Se a avalia&ccedil;&atilde;o foi deferida, realiza as mudan&ccedil;as necess&aacute;rias na tabela original.
                    if($avaliacao == 'D'){
                        if($abg->tpSolicitacao == 'E'){ //Se a abrangencia foi exclu&iacute;da, atualiza os status da abrangencia na sac.dbo.Abrangencia
                            $Abrangencia->delete(array('idProjeto = ?'=>$dadosPrj->idProjeto, 'idPais = ?'=>$abg->idPais, 'idUF = ?'=>$abg->idUF, 'idMunicipioIBGE = ?'=>$abg->idMunicipioIBGE));

                        } else if($abg->tpSolicitacao == 'I') { //Se a abangência foi inclu&iacute;da, cria um novo registro na tabela sac.dbo.Abrangencia
                            $novoLocalRead = array();
                            $novoLocalRead['idProjeto'] = $dadosPrj->idProjeto;
                            $novoLocalRead['idPais'] = $abg->idPais;
                            $novoLocalRead['idUF'] = $abg->idUF;
                            $novoLocalRead['idMunicipioIBGE'] = $abg->idMunicipioIBGE;
                            $novoLocalRead['Usuario'] = $this->idUsuario;
                            $novoLocalRead['stAbrangencia'] = true;
                            $Abrangencia->salvar($novoLocalRead);
                        }
                    }
                }

                $dadosAbr = array();
                $dadosAbr['stAtivo'] = 'N';
                $whereAbr = "idPronac = $read->idPronac AND idReadequacao = $idReadequacao";
                $tbAbrangencia->update($dadosAbr, $whereAbr);

            // READEQUA&ccedil;&atilde;O DE ALTERA&ccedil;&atilde;O DE PROPONENTE
            } else if($read->idTipoReadequacao == 10){ //Se for readequa&ccedil;&atilde;o de altera&ccedil;&atilde;o de proponente, atualiza os dados na sac.dbo.Projetos.

                $Projetos = new Projetos();
                $dadosPrj = $Projetos->find(array('IdPRONAC=?'=>$read->idPronac))->current();

                $tbAprovacao = new Aprovacao();
                $dadosAprovacao = array(
                    'IdPRONAC' => $read->idPronac,
                    'AnoProjeto' => $dadosPrj->AnoProjeto,
                    'Sequencial' => $dadosPrj->Sequencial,
                    'TipoAprovacao' => 8,
                    'DtAprovacao' => $tbAprovacao->getExpressionDate(),
                    'ResumoAprovacao' => 'Parecer favor&aacute;vel para readequa&ccedil;&atilde;o',
                    'Logon' => $auth->getIdentity()->usu_codigo,
                    'idReadequacao' => $idReadequacao
                );
                $idAprovacao = $tbAprovacao->inserir($dadosAprovacao);

            // READEQUA&ccedil;&atilde;O DE PLANO DE DISTRIBUI&ccedil;&atilde;O
            } else if($read->idTipoReadequacao == 11){ //Se for readequa&ccedil;&atilde;o de plano de distribui&ccedil;&atilde;o, atualiza os dados na sac.dbo.PlanoDistribuicaoProduto.
                $PlanoDistribuicaoProduto = new Proposta_Model_DbTable_PlanoDistribuicaoProduto();
                $tbPlanoDistribuicao = new tbPlanoDistribuicao();
                $planosDistribuicaoReadequados = $tbPlanoDistribuicao->buscar(array('idReadequacao=?'=>$idReadequacao));

                $Projetos = new Projetos();
                $dadosPrj = $Projetos->buscar(array('IdPRONAC=?'=>$read->idPronac))->current();

                // lista todos os planos originais
                $planosDistribuicaoOriginais = $PlanoDistribuicaoProduto->buscar(array('idProjeto=?' => $dadosPrj->idProjeto));
                $planosExistentes = array();
                foreach ($planosDistribuicaoOriginais as $planoOriginal) {
                    $planosExistentes[] = $planoOriginal->idPlanoDistribuicao;
                }

                foreach ($planosDistribuicaoReadequados as $planoReadequado) {

                    //Se n&atilde;o houve avali&ccedil;&atilde;o do conselheiro, pega a avalia&ccedil;&atilde;o t&eacute;cnica como referencia.
                    $avaliacao = $planoReadequado->tpAnaliseComissao;
                    if($planoReadequado->tpAnaliseComissao == 'N'){
                        $avaliacao = $planoReadequado->tpAnaliseTecnica;
                    }

                    //Se a avalia&ccedil;&atilde;o foi deferida, realiza as mudan&ccedil;as necess&aacute;rias na tabela original.
                    if($avaliacao == 'D'){
                        $registroExiste = false;

                        if (in_array($planoReadequado->idPlanoDistribuicao, $planosExistentes)) {
                            $registroExiste = true;
                        }
                        // workaround para barrar update de projetos antigos: faz update somente quando tem o id na tabela

                        if ($registroExiste) {
                            // pega dados da tabela temporria (tbPlanoDistribuicao) e faz update em PlanoDistribuicaoProduto
                            $updatePlanoDistr = array();
                            $updatePlanoDistr['idProjeto'] = $dadosPrj->idProjeto;
                            $updatePlanoDistr['idProduto'] = $planoReadequado->idProduto;
                            $updatePlanoDistr['Area'] = $planoReadequado->cdArea;
                            $updatePlanoDistr['Segmento'] = $planoReadequado->cdSegmento;
                            $updatePlanoDistr['idPosicaoDaLogo'] = $planoReadequado->idPosicaoLogo;
                            $updatePlanoDistr['QtdeProduzida'] = $planoReadequado->qtProduzida;
                            $updatePlanoDistr['QtdePatrocinador'] = $planoReadequado->qtPatrocinador;
                            $updatePlanoDistr['QtdeProponente'] = $planoReadequado->qtProponente;
                            $updatePlanoDistr['QtdeOutros'] = $planoReadequado->qtOutros;
                            $updatePlanoDistr['QtdeVendaNormal'] = $planoReadequado->qtVendaNormal;
                            $updatePlanoDistr['QtdeVendaPromocional'] = $planoReadequado->qtVendaPromocional;
                            $updatePlanoDistr['PrecoUnitarioNormal'] = $planoReadequado->vlUnitarioNormal;
                            $updatePlanoDistr['PrecoUnitarioPromocional'] = $planoReadequado->vlUnitarioPromocional;
                            $updatePlanoDistr['stPrincipal'] = 0;
                            $updatePlanoDistr['Usuario'] = $auth->getIdentity()->usu_codigo;
                            $updatePlanoDistr['dsJustificativaPosicaoLogo'] = null;
                            $updatePlanoDistr['stPlanoDistribuicaoProduto'] = 1;

                            $wherePlanoDistr = array();
                            $wherePlanoDistr['idPlanoDistribuicao = ?'] = $planoOriginal->idPlanoDistribuicao;

                            $PlanoDistribuicaoProduto->update($updatePlanoDistr, $wherePlanoDistr);
                        }
                    }
                }

                $dadosPDD = array();
                $dadosPDD['stAtivo'] = 'N';
                $wherePDD = array();
                $wherePDD['idPronac = ? '] = $read->idPronac;
                $wherePDD['idReadequacao = ?'] = $idReadequacao;
                $tbPlanoDistribuicao->update($dadosPDD, $wherePDD);

            // READEQUA&ccedil;&atilde;O DE NOME DO PROJETO
            } else if($read->idTipoReadequacao == 12){ //Se for readequa&ccedil;&atilde;o de nome do projeto, insere o registo na tela de Checklist de Publica&ccedil;&atilde;o.
                $Projetos = new Projetos();
                $dadosPrj = $Projetos->find(array('IdPRONAC=?'=>$read->idPronac))->current();

                $tbAprovacao = new Aprovacao();
                $dadosAprovacao = array(
                    'IdPRONAC' => $read->idPronac,
                    'AnoProjeto' => $dadosPrj->AnoProjeto,
                    'Sequencial' => $dadosPrj->Sequencial,
                    'TipoAprovacao' => 8,
                    'DtAprovacao' => $tbAprovacao->getExpressionDate(),
                    'ResumoAprovacao' => 'Parecer favor&aacute;vel para readequa&ccedil;&atilde;o',
                    'Logon' => $auth->getIdentity()->usu_codigo,
                    'idReadequacao' => $idReadequacao
                );
                $idAprovacao = $tbAprovacao->inserir($dadosAprovacao);

            // READEQUA&ccedil;&atilde;O DE PERÍODO DE EXECU&ccedil;&atilde;O
            } else if($read->idTipoReadequacao == 13){ //Se for readequa&ccedil;&atilde;o de per&iacute;odo de execu&ccedil;&atilde;o, atualiza os dados na sac.dbo.Projetos.
                $dtFimExecucao = Data::dataAmericana($read->dsSolicitacao);
                $Projetos = new Projetos();
                $dadosPrj = $Projetos->find(array('IdPRONAC=?'=>$read->idPronac))->current();
                $dadosPrj->DtFimExecucao = $dtFimExecucao;
                $dadosPrj->save();

            // READEQUA&ccedil;&atilde;O DE PLANO DE DIVULGA&ccedil;&atilde;O
            } else if($read->idTipoReadequacao == 14){ //Se for readequa&ccedil;&atilde;o de plano de divulgacao, atualiza os dados na sac.dbo.PlanoDeDivulgacao.
                $PlanoDeDivulgacao = new PlanoDeDivulgacao();
                $tbPlanoDivulgacao = new tbPlanoDivulgacao();
                $planosDivulgacao = $tbPlanoDivulgacao->buscar(array('idReadequacao=?'=>$idReadequacao));

                foreach ($planosDivulgacao as $plano) {
                    $Projetos = new Projetos();
                    $dadosPrj = $Projetos->buscar(array('IdPRONAC=?'=>$read->idPronac))->current();

                    //Se n&atilde;o houve avali&ccedil;&atilde;o do conselheiro, pega a avalia&ccedil;&atilde;o t&eacute;cnica como referencia.
                    $avaliacao = $plano->tpAnaliseComissao;
                    if($plano->tpAnaliseComissao == 'N'){
                        $avaliacao = $plano->tpAnaliseTecnica;
                    }

                    //Se a avalia&ccedil;&atilde;o foi deferida, realiza as mudan&ccedil;as necess&aacute;rias na tabela original.
                    if($avaliacao == 'D'){
                        if($plano->tpSolicitacao == 'E'){ //Se o plano de divulga&ccedil;&atilde;o foi exclu&iacute;do, atualiza os status do plano na sac.dbo.PlanoDeDivulgacao
                            $PlanoDivulgacaoEmQuestao = $PlanoDeDivulgacao->buscar(array('idProjeto = ?'=>$dadosPrj->idProjeto, 'idPeca = ?'=>$plano->idPeca, 'idVeiculo = ?'=>$plano->idVeiculo))->current();
                            $tbLogomarca = new tbLogomarca();
                            $dadosLogomarcaDaDivulgacao = $tbLogomarca->buscar(array('idPlanoDivulgacao = ?' => $PlanoDivulgacaoEmQuestao->idPlanoDivulgacao))->current();
                            if (!empty($dadosLogomarcaDaDivulgacao)) {
                                $dadosLogomarcaDaDivulgacao->delete();
                            }
                            $PlanoDivulgacaoEmQuestao->delete();

                        } else if($plano->tpSolicitacao == 'I') { //Se o plano de divulga&ccedil;&atilde;o foi inclu&iacute;do, cria um novo registro na tabela sac.dbo.PlanoDeDivulgacao
                            $novoPlanoDivRead = array();
                            $novoPlanoDivRead['idProjeto'] = $dadosPrj->idProjeto;
                            $novoPlanoDivRead['idPeca'] = $plano->idPeca;
                            $novoPlanoDivRead['idVeiculo'] = $plano->idVeiculo;
                            $novoPlanoDivRead['Usuario'] = $auth->getIdentity()->usu_codigo;
                            $novoPlanoDivRead['siPlanoDeDivulgacao'] = 0;
                            $novoPlanoDivRead['idDocumento'] = null;
                            $novoPlanoDivRead['stPlanoDivulgacao'] = 1;
                            $PlanoDeDivulgacao->inserir($novoPlanoDivRead);
                        }
                    }
                }

                $dadosPDD = array();
                $dadosPDD['stAtivo'] = 'N';
                $wherePDD = "idPronac = $read->idPronac AND idReadequacao = $idReadequacao";
                $tbPlanoDivulgacao->update($dadosPDD, $wherePDD);

            // READEQUA&ccedil;&atilde;O DE RESUMO DO PROJETO
            } else if($read->idTipoReadequacao == 15){ //Se for readequa&ccedil;&atilde;o de resumo do projeto, atualiza os dados na sac.dbo.PreProjeto.
                $Projetos = new Projetos();
                $dadosPrj = $Projetos->find(array('IdPRONAC=?'=>$read->idPronac))->current();

                $tbAprovacao = new Aprovacao();
                $dadosAprovacao = array(
                    'IdPRONAC' => $read->idPronac,
                    'AnoProjeto' => $dadosPrj->AnoProjeto,
                    'Sequencial' => $dadosPrj->Sequencial,
                    'TipoAprovacao' => 8,
                    'DtAprovacao' => $tbAprovacao->getExpressionDate(),
                    'ResumoAprovacao' => 'Parecer favor&aacute;vel para readequa&ccedil;&atilde;o',
                    'Logon' => $auth->getIdentity()->usu_codigo,
                    'idReadequacao' => $idReadequacao
                );
                $idAprovacao = $tbAprovacao->inserir($dadosAprovacao);

            // READEQUA&ccedil;&atilde;O DE OBJETIVOS
            } else if($read->idTipoReadequacao == 16){ //Se for readequa&ccedil;&atilde;o de objetivos, atualiza os dados na sac.dbo.PreProjeto.
                $Projetos = new Projetos();
                $dadosPrj = $Projetos->buscar(array('IdPRONAC=?'=>$read->idPronac))->current();

                $PrePropojeto = new Proposta_Model_DbTable_PreProjeto();
                $dadosPreProjeto = $PrePropojeto->find(array('idPreProjeto=?'=>$dadosPrj->idProjeto))->current();
                $dadosPreProjeto->Objetivos = $read->dsSolicitacao;
                $dadosPreProjeto->save();

            // READEQUA&ccedil;&atilde;O DE JUSTIFICATIVA
            } else if($read->idTipoReadequacao == 17){ //Se for readequa&ccedil;&atilde;o de justificativa, atualiza os dados na sac.dbo.PreProjeto.
                $Projetos = new Projetos();
                $dadosPrj = $Projetos->buscar(array('IdPRONAC=?'=>$read->idPronac))->current();

                $PrePropojeto = new Proposta_Model_DbTable_PreProjeto();
                $dadosPreProjeto = $PrePropojeto->find(array('idPreProjeto=?'=>$dadosPrj->idProjeto))->current();
                $dadosPreProjeto->Justificativa = $read->dsSolicitacao;
                $dadosPreProjeto->save();

            // READEQUA&ccedil;&atilde;O DE ACESSIBILIDADE
            } else if($read->idTipoReadequacao == 18){ //Se for readequa&ccedil;&atilde;o de acesibilidade, atualiza os dados na sac.dbo.PreProjeto.
                $Projetos = new Projetos();
                $dadosPrj = $Projetos->buscar(array('IdPRONAC=?'=>$read->idPronac))->current();

                $PrePropojeto = new Proposta_Model_DbTable_PreProjeto();
                $dadosPreProjeto = $PrePropojeto->find(array('idPreProjeto=?'=>$dadosPrj->idProjeto))->current();
                $dadosPreProjeto->Acessibilidade = $read->dsSolicitacao;
                $dadosPreProjeto->save();

            // READEQUA&ccedil;&atilde;O DE DEMOCRATIZA&ccedil;&atilde;O DE ACESSO
            } else if($read->idTipoReadequacao == 19){ //Se for readequa&ccedil;&atilde;o de democratiza&ccedil;&atilde;o de acesso, atualiza os dados na sac.dbo.PreProjeto.
                $Projetos = new Projetos();
                $dadosPrj = $Projetos->buscar(array('IdPRONAC=?'=>$read->idPronac))->current();

                $PrePropojeto = new Proposta_Model_DbTable_PreProjeto();
                $dadosPreProjeto = $PrePropojeto->find(array('idPreProjeto=?'=>$dadosPrj->idProjeto))->current();
                $dadosPreProjeto->DemocratizacaoDeAcesso = $read->dsSolicitacao;
                $dadosPreProjeto->save();

            // READEQUA&ccedil;&atilde;O DE ETAPAS DE TRABALHO
            } else if($read->idTipoReadequacao == 20){ //Se for readequa&ccedil;&atilde;o de etapas de trabalho, atualiza os dados na sac.dbo.PreProjeto.
                $Projetos = new Projetos();
                $dadosPrj = $Projetos->buscar(array('IdPRONAC=?'=>$read->idPronac))->current();

                $PrePropojeto = new Proposta_Model_DbTable_PreProjeto();
                $dadosPreProjeto = $PrePropojeto->find(array('idPreProjeto=?'=>$dadosPrj->idProjeto))->current();
                $dadosPreProjeto->EtapaDeTrabalho = $read->dsSolicitacao;
                $dadosPreProjeto->save();

            // READEQUA&ccedil;&atilde;O DE FICHA T&eacute;CNICA
            } else if($read->idTipoReadequacao == 21){ //Se for readequa&ccedil;&atilde;o de ficha t&eacute;cnica, atualiza os dados na sac.dbo.PreProjeto.
                $Projetos = new Projetos();
                $dadosPrj = $Projetos->buscar(array('IdPRONAC=?'=>$read->idPronac))->current();

                $PrePropojeto = new Proposta_Model_DbTable_PreProjeto();
                $dadosPreProjeto = $PrePropojeto->find(array('idPreProjeto=?'=>$dadosPrj->idProjeto))->current();
                $dadosPreProjeto->FichaTecnica = $read->dsSolicitacao;
                $dadosPreProjeto->save();
            }
        }

        //Atualiza a tabela tbReadequacao
        $dados = array();
        $dados['siEncaminhamento'] = 15; //Finalizam sem a necessidade de passar pela publica&ccedil;&atilde;o no DOU.
        $dados['stEstado'] = 1;

        $tiposParaChecklist = array(2,3,10,12,15);
        if(in_array($read->idTipoReadequacao, $tiposParaChecklist)){
            // se remanejamento orcamentario

            if ($read->idTipoReadequacao == 2 && $TipoDeReadequacao[0]['TipoDeReadequacao'] == 'RM') {
                $dados['siEncaminhamento'] = 15; //Finalizam sem a necessidade de passar pela publica&ccedil;&atilde;o no DOU.
                $dados['stEstado'] = 1;
            } else {
                // reducao ou complementacao orcamentaria

		// verificacao do tipo de parecer.
		// $parecerTecnico->ParecerFavoravel
		// 1 = desfavor&aacute;vel
		// 2 = favor&aacute;vel
		if ($parecerTecnico->ParecerFavoravel === '1') { // desfavoravel
		    $dados['siEncaminhamento'] = 15;
		    $dados['stEstado'] = 1;
		} else {
		    $dados['stEstado'] = 0;
		    $dados['siEncaminhamento'] = 9; //Encaminhado pelo sistema para o Checklist de Publica&ccedil;&atilde;o
		}
            }
        }

        // atualiza registro de tbReadequacao
        $dados['idNrReuniao'] = $idNrReuniao;
        $where["idReadequacao = ?"] = $idReadequacao;

        $return = $tbReadequacao->update($dados, $where);

        if ($read->idTipoReadequacao == 2 && $TipoDeReadequacao[0]['TipoDeReadequacao'] == 'RM') {
            // remanejamento: chama sp para trocar planilha ativa (desativa atual e ativa remanejada)
            $spAtivarPlanilhaOrcamentaria = new spAtivarPlanilhaOrcamentaria();
            $ativarPlanilhaOrcamentaria = $spAtivarPlanilhaOrcamentaria->exec($read->idPronac);
        }

        //Atualiza a tabela tbDistribuirReadequacao
        $dados = array();
        $dados['stValidacaoCoordenador'] = 1;
        $dados['DtValidacaoCoordenador'] = $objAcesso->getExpressionDate();
        $dados['idCoordenador'] = $idCoordenador;
        $where = "idReadequacao = $idReadequacao";
        $tbDistribuirReadequacao = new tbDistribuirReadequacao();
        $return2 = $tbDistribuirReadequacao->update($dados, $where);

        if(!$return && !$return2){
            parent::message("N&atilde;o foi poss&iacute;vel encaminhar a readequa&ccedil;&atilde;o para o Checklist de Publica&ccedil;&atilde;o", "readequacoes/painel?tipoFiltro=analisados" . $urlComplemento, "ERROR");
        }
        parent::message("Readequa&ccedil;&atilde;o finalizada com sucesso!", "readequacoes/painel?tipoFiltro=analisados" . $urlComplemento, "CONFIRM");
    }


    /*
     * P&aacute;gina de cria&ccedil;&atilde;o de planilha or&ccedil;ament&aacute;ria
     * Criada em 02/06/2016
     * @author: Fern&atilde;o Lopes Ginez de Lara fernao.lara@cultura.gov.br
     * @access public
     * @return void
     */
    public function planilhaOrcamentariaAction() {
        //FUN&ccedil;&atilde;O ACESSADA SOMENTE PELO PROPONENTE.
        $this->view->idPerfil = $this->idPerfil;
        if($this->idPerfil != 1111){
            parent::message("Voc&ecirc; n&atilde;o tem permiss&atilde;o para acessar essa &aacute;rea do sistema!", "principal", "ALERT");
        }

        $idPronac = $this->_request->getParam("idPronac");
        if (strlen($idPronac) > 7) {
            $idPronac = Seguranca::dencrypt($idPronac);
        }

        // verifica&ccedil;&atilde;o adicional na pagina se pode incluir nova planilha orcamentaria
        $auth = Zend_Auth::getInstance(); // pega a autenticacao

        $proj = new Projetos();
        $cpf = $proj->buscarProponenteProjeto($idPronac);
        $cpf = $cpf->CgcCpf;
        $idUsuarioLogado = $auth->getIdentity()->IdUsuario;

        $links = new fnLiberarLinks();
        $linksXpermissao = $links->links(2, $cpf, $idUsuarioLogado, $idPronac);
        $linksPermissao = str_replace(' ', '', explode('-', $linksXpermissao->links));
        $permiteReadequacaoPlanilha = $linksPermissao[14];

        if (!$permiteReadequacaoPlanilha) {
            parent::message("Voc&ecirc;  n&atilde;o pode realizar uma nova readequa&ccedil;&atilde;o.", "principalproponente", "ERROR");
        }

        $this->view->idPronac = $idPronac;
        if(!empty($idPronac)){
            $Projetos = new Projetos();
            $this->view->projeto = $Projetos->buscar(array('IdPRONAC = ?'=>$idPronac))->current();

            $tbVerificao = new Proposta_Model_DbTable_Verificacao();
            $buscarRecurso = $tbVerificao->buscarFonteRecurso();
            $this->view->Recursos = $buscarRecurso;

            $tbuf = new Agente_Model_DbTable_UF();

            $buscarEstado = $tbuf->buscar();
            $this->view->UFs = $buscarEstado;

            $PlanoDistribuicaoProduto = new Proposta_Model_DbTable_PlanoDistribuicaoProduto();
            $this->view->Produtos = $PlanoDistribuicaoProduto->comboProdutosParaInclusaoReadequacao($idPronac);

            $tbPlanilhaEtapa = new Proposta_Model_DbTable_TbPlanilhaEtapa();
            $this->view->Etapas = $tbPlanilhaEtapa->buscar(array('stEstado = ?'=>1));

            $TbPlanilhaUnidade = new Proposta_Model_DbTable_TbPlanilhaUnidade();
            $buscarUnidade = $TbPlanilhaUnidade->buscarUnidade();
            $this->view->Unidade = $buscarUnidade;

            $tbReadequacao = new tbReadequacao();
            $this->view->readequacao = $tbReadequacao->readequacoesCadastradasProponente(array(
                'a.idPronac = ?'=>$idPronac,
                'a.siEncaminhamento = ?'=>12,
                'a.idTipoReadequacao = ?' => 2,
            ), array(1));
        } else {
            parent::message("N&uacute;mero Pronac inv&aacute;lido!", "principalproponente", "ERROR");
        }
    }


    /*
     * Fun&ccedil;&atilde;o para verificar e criar planilha or&ccedil;ament&aacute;ria. Recebe flag opcional para criar a planilha
     * Criada em 31/05/2016
     * @author: Fern&atilde;o Lopes Ginez de Lara fernao.lara@cultura.gov.br
     * @access public
     * @return Bool   True se foi poss&iacute;vel criar a planilha ou se ela existe
     */
    public function verificarPlanilhaAtivaAction() {
        $auth = Zend_Auth::getInstance(); // pega a autenticacao
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout(); // desabilita o Zend_Layout
        $post = Zend_Registry::get('post');

        $idPronac = $post->idPronac;
        $idReadequacao = $post->idReadequacao;
        $criarNovaPlanilha = $post->criarNovaPlanilha;

        // Se n&atilde;o possui idReadequacao, cria entrada na tabela tbReadequacao
        if ($idReadequacao == 0) {

            $tblAgente = new Agente_Model_DbTable_Agentes();
            $rsAgente = $tblAgente->buscar(array('CNPJCPF=?'=>$auth->getIdentity()->Cpf))->current();

            $tbReadequacao = new tbReadequacao();
            $dados = array();
            $dados['idPronac'] = $idPronac;
            $dados['idTipoReadequacao'] = 2;
            $objAcesso = new Acesso();
            $dados['dtSolicitacao'] = $objAcesso->getExpressionDate();
            $dados['idSolicitante'] = $rsAgente->idAgente;
            $dados['dsJustificativa'] = '';
            $dados['dsSolicitacao'] = '';
            $dados['idDocumento'] = null;
            $dados['siEncaminhamento'] = 12;
            $dados['stEstado'] = 0;

            try {
                $idReadequacao = $tbReadequacao->inserir($dados);
            } catch (Zend_Exception $e) {
                $this->_helper->json(array('msg' => 'Houve um erro na cria&ccedil;&atilde;o do registro de tbReadequacao'));
                $this->_helper->viewRenderer->setNoRender(TRUE);
            }
        }

        //VERIFICA SE JA POSSUI A PLANILHA TIPO SR. SE N&atilde;O TIVER, COPIA A ORIGINAL
        $tbPlanilhaAprovacao = new tbPlanilhaAprovacao();
        $verificarPlanilhaSR = $tbPlanilhaAprovacao->buscar(array('IdPRONAC=?'=>$idPronac, 'tpPlanilha=?'=>'SR'));

        if ($criarNovaPlanilha && count($verificarPlanilhaSR) == 0 ){
            $planilhaAtiva = $tbPlanilhaAprovacao->buscar(array('IdPRONAC=?'=>$idPronac, 'StAtivo=?'=>'S'));
            $planilhaSR = array();

            try {
                foreach ($planilhaAtiva as $value) {
                    $planilhaSR['tpPlanilha'] = 'SR';
                    $planilhaSR['dtPlanilha'] = $objAcesso->getExpressionDate();
                    $planilhaSR['idPlanilhaProjeto'] = $value['idPlanilhaProjeto'];
                    $planilhaSR['idPlanilhaProposta'] = $value['idPlanilhaProposta'];
                    $planilhaSR['IdPRONAC'] = $value['IdPRONAC'];
                    $planilhaSR['idProduto'] = $value['idProduto'];
                    $planilhaSR['idEtapa'] = $value['idEtapa'];
                    $planilhaSR['idPlanilhaItem'] = $value['idPlanilhaItem'];
                    $planilhaSR['dsItem'] = $value['dsItem'];
                    $planilhaSR['idUnidade'] = $value['idUnidade'];
                    $planilhaSR['qtItem'] = $value['qtItem'];
                    $planilhaSR['nrOcorrencia'] = $value['nrOcorrencia'];
                    $planilhaSR['vlUnitario'] = $value['vlUnitario'];
                    $planilhaSR['qtDias'] = $value['qtDias'];
                    $planilhaSR['tpDespesa'] = $value['tpDespesa'];
                    $planilhaSR['tpPessoa'] = $value['tpPessoa'];
                    $planilhaSR['nrContraPartida'] = $value['nrContraPartida'];
                    $planilhaSR['nrFonteRecurso'] = $value['nrFonteRecurso'];
                    $planilhaSR['idUFDespesa'] = $value['idUFDespesa'];
                    $planilhaSR['idMunicipioDespesa'] = $value['idMunicipioDespesa'];
                    $planilhaSR['dsJustificativa'] = null;
                    $planilhaSR['idAgente'] = 0;
                    $planilhaSR['idPlanilhaAprovacaoPai'] = $value['idPlanilhaAprovacao'];
                    $planilhaSR['idReadequacao'] = $idReadequacao;
                    $planilhaSR['tpAcao'] = 'N';
                    $planilhaSR['idRecursoDecisao'] = $value['idRecursoDecisao'];
                    $planilhaSR['stAtivo'] = 'N';
                    $tbPlanilhaAprovacao->inserir($planilhaSR);
                }
                $this->_helper->json(
                    array(
                        'msg' => 'Planilhas SR criadas',
                        'idReadequacao' => $idReadequacao
                    )
                );
            } catch (Zend_Exception $e) {
                $this->_helper->json(array('msg' => 'Houve um erro na cria&ccedil;&atilde;o das planilhas SR'));
            }
            $this->_helper->viewRenderer->setNoRender(TRUE);
        } else {
            $this->_helper->json(array(
                'msg' => 'Planilha existente',
                'idReadequacao' => $idReadequacao
            ));
        }

    }

     /*
      * consultarReadequacaoAction Pagina acessada por ajax que retorna dados de readequao
      * @since  13/06/2016
      * @author Fernao Lopes Ginez de Lara fernao.lara@cultura.gov.br
      * @access public
      * @return Mixed Retorna json object com readequao
      */
    public function consultarReadequacaoAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout(); // desabilita o Zend_Layout

        $idPronac = $this->_request->getParam("idPronac"); // pega o id do pronac via get
        if (strlen($idPronac) > 7) {
            $idPronac = Seguranca::dencrypt($idPronac);
        }

        $idPronac = $this->_request->getParam("idPronac");
        $idReadequacao = $this->_request->getParam("idReadequacao");
        $idTipoReadequacao = $this->_request->getParam("idTipoReadequacao");

        $where = array();
        $where['a.idPronac = ?'] = $idPronac;

        if ($idReadequacao) {
            $where['a.idReadequacao = ?'] = $idReadequacao;
        }
        if ($idTipoReadequacao) {
             $where['a.idTipoReadequacao = ?'] = $idTipoReadequacao;
        }

        try {
            $tbReadequacao = new tbReadequacao();
            $readequacoes = $tbReadequacao->buscarDadosReadequacoes($where);

            // prepara sada json
            $output = array();
            foreach ($readequacoes as $readequacao) {
                $output[] = array(
                    'idReadequacao' => $readequacao->idReadequacao,
                    'idPronac' => $readequacao->idPronac,
                    'dtSolicitacao' => $readequacao->dtSolicitacao,
                    'dsSolicitacao' => $readequacao->dsSolicitacao,
                    'dsJustificativa' => $readequacao->dsJustificativa
                );
            }

            $this->_helper->json(array('readequacoes' => $output));

        } catch (Zend_Exception $e) {
            $this->_helper->json(array('msg' => 'Registro no localizado'));
        }
    }

    /*
     * Encaminhar para an&aacute;lise t&eacute;cnica
     * Criada em 13/06/2016
     * @author: Fern&atilde;o Lopes Ginez de Lara fernao.lara@cultura.gov.br
     * @access public
     * @return void
     */
    public function encaminharAnaliseTecnicaAction() {
        $perfisAcesso = array(121, 122, 123);

        //FUN&ccedil;&atilde;O ACESSADA SOMENTE PELOS PERFIS DE COORD. GERAL DE ACOMPANHAMENTO, TECNICO DE ACOMPANHAMENTO E COORD. DE ACOMPANHAMENTO.
        if(!in_array($this->idPerfil, $perfisAcesso)){
            parent::message("Voc&ecirc; n&atilde;o tem permiss&atilde;o para acessar essa &aacute;rea do sistema!", "principal", "ALERT");
        }

        $idReadequacao = Seguranca::dencrypt($this->_request->getParam('id'));
        $cnic = $this->_request->getParam('cnic');

        $tbReadequacao = new tbReadequacao();
        $r = $tbReadequacao->buscarDadosReadequacoes(array('idReadequacao = ?'=>$idReadequacao))->current();

        if($r){
            $Projetos = new Projetos();
            $p = $Projetos->buscarProjetoXProponente(array('idPronac = ?' => $r->idPronac))->current();

            $this->view->filtro = $this->_request->getParam('filtro');
            $this->view->readequacao = $r;
            $this->view->projeto = $p;
            $this->view->idPronac = $r->idPronac;
            $this->view->cnic = $cnic;

        } else {
            parent::message('Nenhum registro encontrado.', "readequacoes/painel", "ERROR");
        }
    }


    /**
     * formEncaminharAnaliseTecnicaAction Fun&ccedil;&atilde;o utilizada pleo Coord. de Acompanhamento para encaminhar readequacao
     *
     * @since  Criada em 13/06/2016
     * @author Fern&atilde;o Lopes Ginez de Lara <fernao.lara@cultura.gov.br>
     * @access public
     * @return void
     */
    public function formEncaminharAnaliseTecnicaAction() {
        $perfisAcesso = array(121, 122, 123);

        //FUN&ccedil;&atilde;O ACESSADA SOMENTE PELOS PERFIS DE COORD. GERAL DE ACOMPANHAMENTO, TECNICO DE ACOMPANHAMENTO E COORD. DE ACOMPANHAMENTO.
        if(!in_array($this->idPerfil, $perfisAcesso)){
            parent::message("Voc&ecirc; n&atilde;o tem permiss&atilde;o para acessar essa &aacute;rea do sistema!", "principal", "ALERT");
        }

        $idReadequacao = $this->_request->getParam('idReadequacao');
        $filtro = $this->_request->getParam('filtro');
        $dsOrientacao = $this->_request->getParam('dsAvaliacao');
        $stValidacaoCoordenador = 0;
        $dataEnvio = null;
        $destinatario = (null !== $this->_request->getParam('destinatario')) ? $this->_request->getParam('destinatario') : null;
        $idUnidade = $this->_request->getParam('vinculada');

        try {
            if (in_array($idUnidade, array(Orgaos::ORGAO_SAV, Orgaos::ORGAO_SEFIC))) {
                // MUDAN&ccedil;A DE TECNICO
                $tbDistribuirReadequacao = new tbDistribuirReadequacao();
                $dados = array(
                    'idAvaliador' => $destinatario,
                    'DtEnvioAvaliador' => $tbDistribuirReadequacao->getExpressionDate(),
                    'dsOrientacao' => $dsOrientacao,
                    'idUnidade' => $idUnidade
                );
                $where = array();
                $where['idReadequacao = ?'] = $idReadequacao;

                $u = $tbDistribuirReadequacao->update($dados, $where);

                // atualizar tbReadequacao
                $tbReadequacao = new tbReadequacao();
                $dados = array(
                    'siEncaminhamento' => 4
                );
                $where = array();
                $where['idReadequacao = ?'] = $idReadequacao;
                $tbReadequacao->update($dados, $where);

                if ($this->idPerfil == 121) {
                    parent::message('Dados salvos com sucesso!', "readequacoes/painel-readequacoes?tipoFiltro=$filtro", "CONFIRM");
                } else {
                    parent::message('Dados salvos com sucesso!', "readequacoes/painel?tipoFiltro=$filtro", "CONFIRM");
                }
            } else {
                // MUDAN&ccedil;A DE VINCULADA

                // MUDAN&ccedil;A DE TECNICO
                $tbDistribuirReadequacao = new tbDistribuirReadequacao();
                $dados = array(
                    'idUnidade' => $idUnidade,
                    'DtEncaminhamento' => new Zend_Db_Expr('GETDATE()'),
                    'idAvaliador' => null,
                    'DtEnvioAvaliador' => null,
                    'dsOrientacao' => $dsOrientacao
                );
                $where = array();
                $where['idReadequacao = ?'] = $idReadequacao;

                $tbDistribuirReadequacao->update($dados, $where);

                $tbReadequacao = new tbReadequacao();
                $dadosReadequacao = array(
                    'siEncaminhamento' => 3
                );
                $u = $tbReadequacao->update($dadosReadequacao, $where);

                if ($this->idPerfil == 121) {
                    parent::message('Dados salvos com sucesso!', "readequacoes/painel-readequacoes?tipoFiltro=$filtro", "CONFIRM");
                } else {
                    parent::message('Dados salvos com sucesso!', "readequacoes/painel?tipoFiltro=$filtro", "CONFIRM");
                }

            }
        } catch(Exception $e) {
            if ($this->idPerfil == 121) {
                parent::message('Erro ao encaminhar readequa&ccedil;&atilde;o!', "readequacoes/painel-readequacoes?tipoFiltro=$filtro", "ERROR");
            } else {
                parent::message('Erro ao encaminhar readequa&ccedil;&atilde;o!', "readequacoes/painel?tipoFiltro=$filtro", "ERROR");
            }
        }
    }

     /*
      * verificarLimitesOrcamentarios Consulta ajax para verficiar limites or&ccedil;ament&aacute;rios de readequa&ccedil;&atilde;o
      * @since  31/08/2016
      * @author Fernao Lopes Ginez de Lara fernao.lara@cultura.gov.br
      * @access public
      * @return Mixed Retorna json object com mensagem
      */
    public function verificarLimitesOrcamentariosAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout(); // desabilita o Zend_Layout

        $idPronac = $this->_request->getParam("idPronac");
        if (strlen($idPronac) > 7) {
            $idPronac = Seguranca::dencrypt($idPronac);
        }

        $pa = new spChecarLimitesOrcamentarioReadequacao();
        $resultadoCheckList = $pa->exec($idPronac, 3);

        $resultado = array();
        $i = 0;

        $mensagem = array(
            'custo_administrativo' => array(
                'OK' => "O custo administrativo inferior a 15% do valor total do projeto.",
                'PENDENTE' => "O custo administrativo supera 15% do valor total do projeto. Para corrigir, reduza o valor da etapa em R$ ",
            ),
            'remuneracao' => array(
                'OK' => "Remunera&ccedil;&atilde;o para capta&ccedil;&atilde;o de recursos est&aacute; dentro dos par&acirc;metros permitidos.",
                'PENDENTE' => "A remunera&ccedil;&atilde;o para capta&ccedil;&atilde;o de recursos supera 10% do valor do projeto ou R$ 100.000,00. O valor correto &eacute; R$ "
            ),
            'divulgacao' => array(
                'OK' => "Divulga&ccedil;&atilde;o / Comercializa&ccedil;&atilde;o est&aacute; dentro dos par&acirc;metros permitidos.",
                'PENDENTE' => "A divulga&ccedil;&atilde;o ou a comercializa&ccedil;&atilde;o supera 20%. Para corrigir, reduza o valor da etapa em R$ "
            )
        );

        foreach($resultadoCheckList as $item) {
            $resultado[$i]['idPronac'] = $item->idPronac;
            $resultado[$i]['Descricao'] = utf8_encode($mensagem[$item->Tipo][$item->Observacao]);
            $resultado[$i]['vlDiferenca'] = $item->vlDiferenca;
            $resultado[$i]['Observacao'] = $item->Observacao;
            $i++;
        }
        $this->_helper->json(array('mensagens' => $resultado));
    }
}
