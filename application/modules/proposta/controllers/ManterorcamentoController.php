<?php

class Proposta_ManterorcamentoController extends Proposta_GenericController
{
    private $idUsuario = null;
    private $idPreProjeto = null;

    public function init()
    {
        $idPreProjeto = $this->getRequest()->getParam('idPreProjeto');

        $this->view->title = "Salic - Sistema de Apoio &agrave;s Leis de Incentivo &agrave; Cultura";

        $auth = Zend_Auth::getInstance();
        $PermissoesGrupo = array();
        if (!$auth->hasIdentity())
        {
            return $this->_helper->redirector->goToRoute(array('controller' => 'index', 'action' => 'logout'), null, true);
        }

        //Da permissao de acesso a todos os grupos do usuario logado afim de atender o UC75
        if (isset($auth->getIdentity()->usu_codigo)) {
            //Recupera todos os grupos do Usuario
            $Usuario = new Autenticacao_Model_Usuario(); // objeto usuario
            $grupos = $Usuario->buscarUnidades($auth->getIdentity()->usu_codigo, 21);
            foreach ($grupos as $grupo) {
                $PermissoesGrupo[] = $grupo->gru_codigo;
            }
        }

        isset($auth->getIdentity()->usu_codigo) ? parent::perfil(1, $PermissoesGrupo) : parent::perfil(4, $PermissoesGrupo);
        parent::init(); // chama o init() do pai GenericControllerNew

        //recupera ID do pre projeto (proposta)
        if (!empty ($idPreProjeto)) {
            $this->idPreProjeto = $idPreProjeto;
            $this->view->idPreProjeto = $idPreProjeto;

            //VERIFICA SE A PROPOSTA ESTA COM O MINC
            $Movimentacao = new Proposta_Model_DbTable_TbMovimentacao();
            $rsStatusAtual = $Movimentacao->buscarStatusAtualProposta($idPreProjeto);
            //var_dump($rsStatusAtual);die;
            $this->view->movimentacaoAtual = isset($rsStatusAtual['Movimentacao']) ? $rsStatusAtual['Movimentacao'] : '';
        } else {
            if ($idPreProjeto != '0') {
                parent::message("Necess&aacute;rio informar o número da proposta.", "/proposta/manterpropostaincentivofiscal/index", "ERROR");
            }
        }
        $this->idUsuario = !empty($auth->getIdentity()->usu_codigo) ? $auth->getIdentity()->usu_codigo : $auth->getIdentity()->IdUsuario;
        /* =============================================================================== */
        /* ==== VERIFICA PERMISSAO DE ACESSO DO PROPONENTE A PROPOSTA OU AO PROJETO ====== */
        /* =============================================================================== */
        $this->verificarPermissaoAcesso(true, false, false);

    }

    public function indexAction()
    {
        // Usuario Logado
        $auth = Zend_Auth::getInstance(); // instancia da autenticacao
        $idusuario = $auth->getIdentity()->usu_codigo;

        $GrupoAtivo = new Zend_Session_Namespace('GrupoAtivo'); // cria a sessao com o grupo ativo
        $codOrgao = $GrupoAtivo->codOrgao; //  orgao ativo na sessao

        $this->view->codOrgao = $codOrgao;
        $this->view->idUsuarioLogado = $idusuario;
    }

   public function produtoscadastradosAction()
    {
        $this->view->idPreProjeto = $this->idPreProjeto;

        $this->view->charset = Zend_Registry::get('config')->db->params->charset;

    }
    public function listarProdutosAction()
    {
        $this->_helper->layout->disableLayout();

        $tbPreprojeto = new Proposta_Model_DbTable_PreProjeto();
        $this->view->Produtos = $tbPreprojeto->listarProdutos($this->idPreProjeto);

        $manterOrcamento = new Proposta_Model_DbTable_TbPlanilhaEtapa();
        $listaEtapa = $manterOrcamento->buscarEtapas('P');
        $this->view->EtapasProduto = $this->reordenaretapas($listaEtapa);

        $this->view->ItensProduto = $tbPreprojeto->listarItensProdutos($this->idPreProjeto, null, Zend_DB::FETCH_ASSOC);

        $arrBusca = array(
            'idProjeto' => $this->idPreProjeto,
            'stAbrangencia' => 't'
        );

        $tblAbrangencia = new Proposta_Model_DbTable_Abrangencia();
        $locais = $tblAbrangencia->buscar($arrBusca);

        // Remove outros paises para cadastro na planilha
        $novosLocais = array();
        foreach ($locais as $key => $local) {
            if (isset($local['idPais']) && $local['idPais'] == 31) {
                $novosLocais[] = $local;
            }
        }
        $this->view->localRealizacao = $novosLocais;
    }

    public function reordenaretapas($etapas)
    {

        if (empty($etapas))
            return false;

        $newListaEtapa = $etapas;
        $newListaEtapa[3] = $etapas[2];
        $newListaEtapa[2] = $etapas[3];

        return $newListaEtapa;
    }

    public function planilhaorcamentariaAction()
    {
        $this->view->idPreProjeto = $this->idPreProjeto;
    }

    public function planilhaorcamentariageralAction()
    {
        $this->view->tipoPlanilha = 0; // 0=Planilha Or?ament?ria da Proposta
        $this->view->idPreProjeto = $this->getRequest()->getParam('idPreProjeto');
    }

    public function cadastrarprodutosAction()
    {
        $this->_helper->layout->disableLayout();

        $this->view->idPreProjeto = $this->idPreProjeto;
        $idPreProjeto = $this->_request->getParam('idPreProjeto');
        $idProduto = $this->_request->getParam('produto');

        if(!empty($idPreProjeto) && !empty($idProduto)) {
            $tbPlanoDistribuicaoProduto = new Proposta_Model_DbTable_PlanoDistribuicaoProduto();
            $this->view->Dados = $tbPlanoDistribuicaoProduto->buscarDadosCadastrarProdutos($idPreProjeto, $idProduto);
            $this->view->idProduto = $idProduto;
        }

        $idUf = $this->_request->getParam('idUF');
        if (!empty($idUf)) {
            $this->_helper->layout->disableLayout(); // desabilita o Zend_Layout

            $tbMun = new Agente_Model_DbTable_Municipios();
            $cidade = $tbMun->listar($idUf);

            $a = 0;
            $cidadeArray = [];
            foreach ($cidade as $DadosCidade) {
                $cidadeArray[$a]['idCidade'] = $DadosCidade->id;
                $cidadeArray[$a]['nomeCidade'] = utf8_encode($DadosCidade->Descricao);
                $a++;
            }
            $this->_helper->json($cidadeArray);
        }

        $idEtapa = $this->_request->getParam('idetapa');
        if (!empty($idEtapa)) {
            $this->_helper->layout->disableLayout(); // desabilita o Zend_Layout

            $itensPlanilhaProduto = new tbItensPlanilhaProduto();
            $item = $itensPlanilhaProduto->buscarItens($idEtapa, $idProduto);

            if (count($item) <= 0) {
                $item = $itensPlanilhaProduto->buscarItens($idEtapa, null);
            }
            $a = 0;
            $itemArray = [];
            foreach ($item as $Dadositem) {
                $itemArray[$a]['idItem'] = $Dadositem->idPlanilhaItens;
                $itemArray[$a]['nomeItem'] = utf8_encode($Dadositem->Descricao);
                $a++;
            }
            $this->_helper->json($itemArray);
        }

        $etapaSelecionada["id"] = $this->_request->getParam('etapa');
        $etapaSelecionada["etapaNome"] = $this->_request->getParam('etapaNome');
        $this->view->etapaSelecionada = $etapaSelecionada;

        $uf = new Agente_Model_DbTable_UF();
        $buscarEstado = $uf->buscar();
        $this->view->Estados = $buscarEstado;

        $buscarEtapa = new Proposta_Model_DbTable_TbPlanilhaEtapa();
        $this->view->Etapa = $buscarEtapa->buscarEtapasCadastrarProdutos();

        $buscarRecurso = new Proposta_Model_DbTable_Verificacao();
        $this->view->Recurso = $buscarRecurso->buscarFonteRecurso();

        $buscarUnidade = new Proposta_Model_DbTable_PlanilhaUnidade();
        $this->view->Unidade = $buscarUnidade->buscarUnidade();

        $buscarItem = new Proposta_Model_DbTable_PreProjeto();
        $this->view->Item = $buscarItem->listarItensProdutos($this->idPreProjeto);

        $buscarProduto = new Proposta_Model_DbTable_PreProjeto();
        $this->view->Produtos = $buscarProduto->buscarProdutos($this->idPreProjeto);
    }

    public function resumoPlanilhaAction()
    {
        $this->_helper->layout->disableLayout();

        $tbPreprojeto = new Proposta_Model_DbTable_PreProjeto();
        $itens = $tbPreprojeto->listarItensProdutos($this->idPreProjeto, null, Zend_DB::FETCH_ASSOC);

        $manterOrcamento = new Proposta_Model_DbTable_TbPlanilhaEtapa();
        $listaEtapa = $manterOrcamento->buscarEtapas('P', Zend_DB::FETCH_ASSOC);

        $this->view->EtapaCusto = $manterOrcamento->buscarEtapas("A");
        $this->view->ItensEtapaCusto = $manterOrcamento->listarItensCustosAdministrativos($this->idPreProjeto, "A");

        $valorEtapa = array();

        if ($itens) {
            $etapasPlanilha = [];
            foreach ($itens as $item) {
                $valorTotalItem = $item['Quantidade'] * $item['Ocorrencia'] * $item['ValorUnitario'];
                $valorEtapa[$item['idEtapa']] = $valorTotalItem + $valorEtapa[$item['idEtapa']];
            }

            foreach ($listaEtapa as $etapa) {

                if (isset($valorEtapa[$etapa['idEtapa']])) {
                    $etapa['valorTotal'] = $valorEtapa[$etapa['idEtapa']];
                }

                $etapasPlanilha[] = $etapa;
            }

            $this->view->EtapasProduto = $this->reordenaretapas($etapasPlanilha);

        }
    }

    public function formitemAction()
    {
        $this->_helper->layout->disableLayout();

        $this->view->idPreProjeto = $this->idPreProjeto;
        $params = $this->getRequest()->getParams();

        $idPreProjeto = $params['idPreProjeto'];
        $idProduto = $params['produto'];
        $idUf = $params['idUf'];
        $idMunicipio = $params['idMunicipio'];
        $idEtapa = $params['etapa'];
        $idItem = $params['item'];
        $dadosDoItemOrcamentario = [];

        if (!empty($params['idPlanilhaProposta'])) { // editar item
            $idPlanilhaProposta = $params['idPlanilhaProposta'];
            $tbPlanilhaProposta = new Proposta_Model_DbTable_TbPlanilhaProposta();
            $dadosDoItemOrcamentario = $tbPlanilhaProposta->buscarDadosEditarProdutos($idPreProjeto, $idEtapa, $idProduto, $idItem, $idPlanilhaProposta, $idUf, $idMunicipio);

        } else { // novo item
            if (!empty($idPreProjeto) && !empty($idProduto)) {
                $tbPlanoDistribuicaoProduto = new Proposta_Model_DbTable_PlanoDistribuicaoProduto();
                $dadosDoItemOrcamentario= $tbPlanoDistribuicaoProduto->buscarDadosCadastrarProdutos($idPreProjeto, $idProduto);
                $this->view->idProduto = $idProduto;
            }
        }
        $this->view->Dados = $dadosDoItemOrcamentario;

        $uf = new Agente_Model_DbTable_UF();
        $estado = $uf->findBy(array("idUF" => $idUf));
        $this->view->Estado = $estado;

        $mun = new Agente_Model_DbTable_Municipios();
        $municipio = $mun->findBy(array("idMunicipioIBGE" => $idMunicipio));
        $this->view->Municipio = $municipio;

        $buscarEtapa = new Proposta_Model_DbTable_TbPlanilhaEtapa();
        $this->view->Etapa = $buscarEtapa->findBy(array('idPlanilhaEtapa' => $idEtapa));

        $buscarRecurso = new Proposta_Model_DbTable_Verificacao();
        $this->view->Recurso = $buscarRecurso->buscarFonteRecurso();

        $buscarUnidade = new Proposta_Model_DbTable_PlanilhaUnidade();
        $this->view->Unidade = $buscarUnidade->buscarUnidade();

        $itensPlanilhaProduto = new tbItensPlanilhaProduto();
        $this->view->Item = $itensPlanilhaProduto->buscarItens($idEtapa, $idProduto);

        $buscarProduto = new Proposta_Model_DbTable_PreProjeto();
        $this->view->Produtos = $buscarProduto->buscarProdutos($this->idPreProjeto);

    }

    public function salvarItemAction()
    {
        $this->_helper->layout->disableLayout();
        $params = $this->getRequest()->getParams();
        $dados = [];

        try {

            $justificativa = substr(trim(strip_tags($params['justificativa'])), 0, 1000);

            $dados = array(
                'idProjeto' => $params['idPreProjeto'],
                'idProduto' => $params['produto'],
                'idEtapa' => $params['idPlanilhaEtapa'],
                'idPlanilhaItem' => $params['planilhaitem'],
                'Descricao' => $this->getRequest()->getParam('Descricao', null),
                'Unidade' => $params['unidade'],
                'Quantidade' => $params['qtd'],
                'Ocorrencia' => $params['ocorrencia'],
                'ValorUnitario' => str_replace(",", ".", str_replace(".", "", $params['vlunitario'])),
                'QtdeDias' => $params['qtdDias'],
                'TipoDespesa' => 0,
                'TipoPessoa' => 0,
                'Contrapartida' => 0,
                'FonteRecurso' => $params['fonterecurso'],
                'UfDespesa' => $params['uf'],
                'MunicipioDespesa' => $params['municipio'],
                'dsJustificativa' => $justificativa,
                'idUsuario' => $this->idUsuario,
                'stCustoPraticado' => !empty($params['stCustoPraticado']) ? $params['stCustoPraticado'] : 'f'
            );

            $idPlanilhaProposta = $this->getRequest()->getParam('idPlanilhaProposta', null);

            $retorno[] = self::salvarItemPlanilha($dados, $idPlanilhaProposta); # salva o item principal

            $outrasLocalidades = isset($params['comboOutrasCidades']) ? $params['comboOutrasCidades'] : null;

            if ($outrasLocalidades && count($outrasLocalidades) > 0) {

                foreach ($outrasLocalidades as $localidade) {
                    if ($localidade == 'all') {
                        continue;
                    }

                    $custoPraticado = isset($params['stCustoPraticado_' . $localidade]) ? $params['stCustoPraticado_' . $localidade] : 0;

                    if ($custoPraticado == 1) {
                        $justificativa = isset($params['justificativa_' . $localidade]) ? $params['justificativa_' . $localidade] : '';
                        $dados['dsJustificativa'] = substr(trim(strip_tags($justificativa)), 0, 1000);
                        if (empty($justificativa)) {
                            continue;
                        }
                    }

                    $dados['stCustoPraticado'] = $custoPraticado;
                    $estadoMunicipio = explode(':', $localidade);
                    $dados['UfDespesa'] = $estadoMunicipio[0];
                    $dados['MunicipioDespesa'] = $estadoMunicipio[1];
                    $retorno[] = self::salvarItemPlanilha($dados, null, true);
                }
            }

            $this->atualizarCustosVinculadosDaPlanilha($params['idPreProjeto']);

            $this->_helper->json($retorno);
        } catch (Exception $e) {

            $this->_helper->json(
                [
                    'status' => false,
                    'msg' => $e->getMessage(),
                    'dados' => $dados
                ]
            );
        }
    }

    private function salvarItemPlanilha($dados, $idPlanilhaProposta = null, $outraLocalidade = false)
    {

        $retorno = [
            'idPlanilhaProposta' => null,
            'close' => false,
            'status' => false,
            'msg' => "Erro ao salvar item",
            'dados' => $dados
        ];

        try {
            $modelPlanilhaProposta = new Proposta_Model_TbPlanilhaProposta($dados);
            $tbPlanilhaPropostaMapper = new Proposta_Model_TbPlanilhaPropostaMapper();

            $tbPlanilhaProposta = new Proposta_Model_DbTable_TbPlanilhaProposta();
            $itemCadastrado = $tbPlanilhaProposta->buscarDadosEditarProdutos(
                $dados['idProjeto'],
                $dados['idEtapa'],
                $dados['idProduto'],
                $dados['idPlanilhaItem'],
                null,
                $dados['UfDespesa'],
                $dados['MunicipioDespesa'],
                null,
                null,
                null,
                null,
                null,
                $dados['FonteRecurso']
            );

            if(isset($itemCadastrado) && $itemCadastrado[0]->idPlanilhaProposta <> $idPlanilhaProposta) {
                throw new Exception("Item duplicado na mesma etapa!");
            }

            $retorno['msg'] = "Item cadastrado com sucesso.";
            $retorno['close'] = false;
            $retorno['action'] = 'insert';

            if(!empty($idPlanilhaProposta)) {
                $retorno['msg'] = "Altera&ccedil;&atilde;o realizada com sucesso!";
                $retorno['close'] = true;
                $retorno['action'] = 'update';

                $modelPlanilhaProposta->setIdPlanilhaProposta($idPlanilhaProposta);
            }

            $id = $tbPlanilhaPropostaMapper->save($modelPlanilhaProposta);

            if ($id) {
                $retorno['idPlanilhaProposta'] = $id;
                $retorno['status'] = true;
                $retorno['dados'] = array_merge($dados, self::obterDadosParaMontarItem($dados, $retorno['idPlanilhaProposta']));
            }

            return $retorno;

        } catch (Exception $e) {
            $retorno['msg'] = $e->getMessage();
            return $retorno;
        }
    }

    protected function obterDadosParaMontarItem($dados, $idPlanilhaProposta)
    {
        $tbItensPlanilhaProduto = new tbItensPlanilhaProduto();
        $item = $tbItensPlanilhaProduto->buscarItem(["idPlanilhaItens = ?" => $dados['idPlanilhaItem']]);

        $buscarUnidade = new Proposta_Model_DbTable_TbPlanilhaUnidade();
        $unidade = $buscarUnidade->findBy(["idUnidade" => $dados['Unidade']]);

        $arrUrlEditarProduto = [
            'module' => 'proposta',
            'controller' => 'manterorcamento',
            'action' => 'formitem',
            'item' => $dados['idPlanilhaItem'],
            'etapa' => $dados['idEtapa'],
            'produto' => $dados['idProduto'],
            'idPlanilhaProposta' => $idPlanilhaProposta,
            'idPreProjeto' => $dados['idProjeto'],
            'idUf' => $dados['UfDespesa'],
            'idMunicipio' => $dados['MunicipioDespesa'],
        ];

        $dadosItem = [
            'idPlanilhaProposta' => $idPlanilhaProposta,
            'itemNome' => $item->Descricao,
            'unidade' => $unidade['Descricao'],
            'quantidade' => number_format($dados['Quantidade'], 0),
            'ocorrencia' => number_format($dados['Ocorrencia'], 0),
            'valorUnitario' => number_format($dados['ValorUnitario'], 2, ",", "."),
            'total' => number_format(($dados['Quantidade'] * $dados['ValorUnitario']) * $dados['Ocorrencia'], 2, ",", "."),
            'urlEditarProduto' => $this->_helper->url->url($arrUrlEditarProduto)
        ];

        return $dadosItem;
    }

    /*
    * Quando o sistema abre a opcao de alterar projeto, o proponente
    * nao pode ultrapassar o valor total inicialmente solicitado para incentivo fiscal(Fonte Recurso=109)
    */
    public function verificarSeUltrapassaValorOriginal($idPreProjeto, $dados, $idPlanilhaProposta = null)
    {
        $tbPlanilhaProposta = new Proposta_Model_DbTable_TbPlanilhaProposta();

        $totalItemSalvo = 0;

        $tbProjetos = new Projetos();
        $projeto = $tbProjetos->findBy(array('idProjeto = ?' => $idPreProjeto));
        $valorSolicitadoInicialmente = $projeto['SolicitadoReal'];

        $TPP = new Proposta_Model_DbTable_TbPlanilhaProposta();

        # Faz a soma da planilha da proposta
        $somaPlanilhaPropostaProdutos = $TPP->somarPlanilhaPropostaProdutos($idPreProjeto, 109);
        $somaPlanilhaPropostaProdutos = !empty($somaPlanilhaPropostaProdutos['soma']) ? $somaPlanilhaPropostaProdutos['soma'] : 0;

        # Item atual
        $totalItemAtual = $dados['Quantidade'] * $dados['ValorUnitario'] * $dados['Ocorrencia'];

        # Se tiver editando um item, tem que subtrair o valor anterior
        if (!empty($idPlanilhaProposta)) {

            $item = $tbPlanilhaProposta->findBy(array("idPlanilhaProposta = ?" => $idPlanilhaProposta));

            if ($item) {
                $totalItemSalvo = $item['Quantidade'] * $item['Ocorrencia'] * $item['ValorUnitario'];
                $custosDesteItem = $this->somarTotalCustosVinculados($idPreProjeto, $totalItemSalvo);
                $totalItemSalvo = $totalItemSalvo + $custosDesteItem;
            }
        }

        # eh necessario calcular os custos vinculados com o novo item
        $valorTotaldosProdutosIncentivados = $somaPlanilhaPropostaProdutos + ($totalItemAtual - $totalItemSalvo);
        $custosvinculados = $this->somarTotalCustosVinculados($idPreProjeto, $valorTotaldosProdutosIncentivados);

        $valorSolicitadoAtual = $valorTotaldosProdutosIncentivados + $custosvinculados;

        return ($valorSolicitadoInicialmente < $valorSolicitadoAtual);
    }

    public function excluirItemAction()
    {
        try {

            $this->verificarPermissaoAcesso(true, false, false);

            $this->_helper->layout->disableLayout();

            $params = $this->getRequest()->getParams();

            $dados['msg'] = "Erro ao excluir o item";
            $dados['status'] = false;

            $idPlanilhaProposta = $params['idPlanilhaProposta'];
            $tbPlanilhaProposta = new Proposta_Model_DbTable_TbPlanilhaProposta();

            $where = 'idPlanilhaProposta = ' . $idPlanilhaProposta;
            $result = $tbPlanilhaProposta->delete($where);

            if ($result) {
                $this->atualizarCustosVinculadosDaPlanilha($this->idPreProjeto);
                $dados['msg'] = "Exclus&atilde;o realizada com sucesso!";
                $dados['status'] = true;
            }

            $this->_helper->json($dados);

        } catch (Exception $e) {
            $dados['msg'] = $e->getMessage();
            $dados['status'] = false;
            $this->_helper->json($dados);
        }

    }

    public function restaurarplanilhaAction()
    {
        $idPreProjeto = $this->getRequest()->getParam('idPreProjeto');

        $return['msg'] = "Erro ao restaurar a planilha! ";
        $return['status'] = false;
        $restaurar = false;

        if (!empty($idPreProjeto)) {

            if ($this->isEditarProjeto($idPreProjeto)) {

                # restaura o local de realizacao
                $TA = new Proposta_Model_DbTable_Abrangencia();
                $this->restaurarObjetoSerializadoParaTabela($TA, $idPreProjeto, 'alterarprojeto_abrangencia');

                # restaura o plano de distribuicao e o plano de distribuicao detalhado
                $this->restaurarPlanoDistribuicaoDetalhado($idPreProjeto);

                # restaura o orcamento
                $TPP = new Proposta_Model_DbTable_TbPlanilhaProposta();
                $restaurar = $this->restaurarObjetoSerializadoParaTabela($TPP, $idPreProjeto, 'alterarprojeto_tbplanilhaproposta');
            }

            if ($restaurar) {
                $return['msg'] = "Plano distribui&ccedil;&atilde;o, Local de realiza&ccedil;&atilde;o e Or&ccedil;amento foram restaurados com sucesso!";
                $return['status'] = true;
            }
        }

        $this->_helper->json($return);
        die;
    }

    public function resumorestaurarplanilhaAction() {

    }

    public function custosVinculadosAction()
    {
        $this->view->acao = $this->_urlPadrao . "/proposta/manterorcamento/salvar-percentuais-custos-vinculados";

        $arrBusca = array(
            'idProjeto' => $this->idPreProjeto,
            'stAbrangencia' => 't'
        );

        $tblAbrangencia = new Proposta_Model_DbTable_Abrangencia();
        $this->view->localRealizacao = $tblAbrangencia->buscar($arrBusca);

        $this->view->itensCustosVinculados = $this->gerarArrayCustosVinculados($this->idPreProjeto);
    }

    public function salvarPercentuaisCustosVinculadosAction()
    {
        $params = $this->getRequest()->getParams();

        $custosVinculados = $params['itensCustosVinculados'];
        $arrayCustosVinculados = $this->gerarArrayCustosVinculados($params['idPreProjeto']);

        $mapper = new Proposta_Model_TbCustosVinculadosMapper();

        try {
            foreach ($custosVinculados as $key => $item) {
                if (in_array($key, array_column($arrayCustosVinculados, 'idPlanilhaItens'))) {

                    $dados = array(
                        'idCustosVinculados' => $item['idCustosVinculados'],
                        'idProjeto' => $params['idPreProjeto'],
                        'idPlanilhaItem' => $key,
                        'dtCadastro' =>  MinC_Db_Expr::date(),
                        'pcCalculo' => $item['percentual'],
                        'idUsuario' => $params['idagente']
                    );

                    $mapper->save(new Proposta_Model_TbCustosVinculados($dados));
                }
            }

            $this->atualizarCustosVinculadosDaPlanilha($this->idPreProjeto);

            parent::message('Cadastro realizado com sucesso!', "/proposta/manterorcamento/custos-vinculados?idPreProjeto=" . $this->idPreProjeto, "CONFIRM");
        } catch (Zend_Exception $ex) {
            parent::message("N&atilde;o foi poss&iacute;vel realizar a opera&ccedil;&atilde;o!" . $ex->getMessage(), "/proposta/manterorcamento/custos-vinculados?idPreProjeto=" . $this->idPreProjeto, "ERROR");
        }
    }
}
