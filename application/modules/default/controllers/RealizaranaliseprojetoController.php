<?php

class RealizarAnaliseProjetoController extends MinC_Controller_Action_Abstract
{
    private $bln_readequacao = "false";
    private $idPedidoAlteracao = 0;
    private $intTamPag = 10;
    private $idUsuario;

    public function init()
    {
        $this->view->title = "Salic - Sistema de Apoio &agrave;s Leis de Incentivo &agrave; Cultura"; // titulo da pagina
        $auth = Zend_Auth::getInstance(); // pega a autenticacao
        $Usuario = new Autenticacao_Model_Usuario(); // objeto usuario
        $GrupoAtivo = new Zend_Session_Namespace('GrupoAtivo'); // cria a sessao com o grupo ativo

        if ($auth->hasIdentity()) // caso o usuario esteja autenticado
        {
            // verifica as permissoes
            $PermissoesGrupo = array();
            //$PermissoesGrupo[] = 93;  // Coordenador de Parecerista
            //$PermissoesGrupo[] = 94;  // Parecerista
            //$PermissoesGrupo[] = 103; // Coordenador de Analise
            $PermissoesGrupo[] = 118; // Componente da Comissao
            //$PermissoesGrupo[] = 119; // Presidente da Mesa
            //$PermissoesGrupo[] = 120; // Coordenador Administrativo CNIC
            $PermissoesGrupo[] = 127; // Ministro
            if (!in_array($GrupoAtivo->codGrupo, $PermissoesGrupo)) // verifica se o grupo ativo esta no array de permissoes
            {
                parent::message("Voc&ecirc; n&atilde;o tem permiss&atilde;o para acessar essa &aacute;rea do sistema!", "principal/index", "ALERT");
            }

            $grupos = $Usuario->buscarUnidades($auth->getIdentity()->usu_codigo, 21);
            $this->view->usuario = $auth->getIdentity(); // manda os dados do usuario para a visao
            $this->view->arrayGrupos = $grupos; // manda todos os grupos do usuario para a visao
            $this->view->grupoAtivo = $GrupoAtivo->codGrupo; // manda o grupo ativo do usuario para a visao
            $this->view->orgaoAtivo = $GrupoAtivo->codOrgao; // manda o orgao ativo do usuario para a visao
        }
        else
        {
            return $this->_helper->redirector->goToRoute(array('controller' => 'index', 'action' => 'logout'), null, true);
        }

        parent::init();
        $this->view->bln_readequacao = "false";

        $idpronac = null;
        $idpronac = $this->_request->getParam("idpronac");
        if (!empty($idpronac)) {
            $tbPedidoAlteracao = new tbPedidoAlteracaoProjeto();
            $arrBusca = array();
            $arrBusca['pa.idPronac = ?'] = $idpronac;
            $arrBusca['pa.stPedidoAlteracao = ?'] = 'I'; //pedido enviado pelo proponente
            $arrBusca['pa.siVerificacao = ?'] = '1';
            $arrBusca['paxta.tpAlteracaoProjeto = ?'] = '10'; //tipo Readequacao de Itens de Custo
            $rsPedidoAlteraco = $tbPedidoAlteracao->buscarPedidoAlteracaoPorTipoAlteracao($arrBusca, array('dtSolicitacao DESC'))->current();
            if (!empty($rsPedidoAlteraco)) {
                $this->bln_readequacao = "true";
                $this->view->bln_readequacao = "true";
                $this->idPedidoAlteracao = $rsPedidoAlteraco->idPedidoAlteracao;
            }
        }
    }

    public function indexAction()
    {
        $get = Zend_Registry::get('get');
        $planilha = $get->idPlanilha;
        $pronac = $get->idPronac;
        $produto = $get->idProduto;
        $query_string = "?idPlanilha=" . $planilha . "&idPronac=" . $pronac . "&idProduto=" . $produto;
        $this->_forward("analisedeconta" . $query_string);
    }

    public function parecerconsolidadoAction()
    {
        $idpronac = $this->_request->getParam("idpronac");
        $projeto = new Projetos();
        $planilhaproposta = new Proposta_Model_DbTable_TbPlanilhaProposta();
        $planilhaprojeto = new PlanilhaProjeto();
        $planilhaAprovacao = new PlanilhaAprovacao();
        $tblParecer = new Parecer();
        $tblPauta = new Pauta();
        $analiseaprovacao = new AnaliseAprovacao();

        $buscarPronac = $projeto->buscar(array('IdPRONAC = ?' => $idpronac))->current()->toArray();
        $idprojeto = $buscarPronac['idProjeto'];

        $this->view->idpronac = $idpronac;
        $this->view->projeto = $buscarPronac;
        //define tipo de planilha a ser utilizada baseado na ultima planilha criada
        $rsPlanilhaAtual = $planilhaAprovacao->buscar(array('IdPRONAC = ?' => $idpronac), array('dtPlanilha DESC'))->current();
        $tpPlanilha = (!empty($rsPlanilhaAtual) && $rsPlanilhaAtual->tpPlanilha == 'SE') ? 'SE' : 'CO';
        //antiga busca
        //$analiseparecer = $parecer->buscarParecer(array(1), $idpronac )->current()->toArray();
        //nova busca
        $parecerAtivo = $tblParecer->buscar(array('idPronac=?' => $idpronac, 'stAtivo=?' => '1'))->current();
        $analiseparecer = $tblParecer->buscar(array('idTipoAgente in (?)' => array('1', '6'), 'TipoParecer=?' => $parecerAtivo->TipoParecer, 'idPronac=?' => $idpronac))->current()->toArray();

        $this->view->ResultRealizarAnaliseProjeto = $analiseparecer;
        $produtos = $analiseaprovacao->buscarAnaliseProduto($tpPlanilha, $idpronac);

        /**** MODO ANTIGO ************/
        //$fonteincentivo = $planilhaproposta->somarPlanilhaProposta($idprojeto, 109);
        //$outrasfontes   = $planilhaproposta->somarPlanilhaProposta($idprojeto, false, 109);
        //$parecerista = $planilhaprojeto->somarPlanilhaProjeto($idpronac, 109);
        //$this->view->fontesincentivo  = $fonteincentivo['soma'];
        //$this->view->outrasfontes  = $outrasfontes['soma'];
        //$this->view->valorproposta = $fonteincentivo['soma'] + $outrasfontes['soma'];
        //$this->view->valorparecerista = $parecerista['soma'];
        /***** FIM - MODO ANTIGO ******/

        /**** CODIGO DE READEQUACAO ****/
        /********** MODO NOVO ***************/
        //TRATANDO SOMA DE PROJETO QUANDO ESTE FOR DE READEQUACAO
        $arrWhereSomaPlanilha = array();
        $arrWhereSomaPlanilha['idPronac = ?'] = $idpronac;
        if ($this->bln_readequacao == "false") {
            $fonteincentivo = $planilhaproposta->somarPlanilhaProposta($idprojeto, 109);
            $outrasfontes = $planilhaproposta->somarPlanilhaProposta($idprojeto, false, 109);
            $parecerista = $planilhaprojeto->somarPlanilhaProjeto($idpronac, 109);
        } else {
            $arrWhereFontesIncentivo = $arrWhereSomaPlanilha;
            $arrWhereFontesIncentivo['idPlanilhaItem <> ? '] = '206'; //elaboracao e agenciamento
            $arrWhereFontesIncentivo['tpPlanilha = ? '] = 'SR';
            $arrWhereFontesIncentivo['stAtivo = ? '] = 'N';
            $arrWhereFontesIncentivo['NrFonteRecurso = ? '] = '109';
            $arrWhereFontesIncentivo["idPedidoAlteracao = (?)"] = new Zend_Db_Expr("(SELECT TOP 1 max(idPedidoAlteracao) from sac.dbo.tbPlanilhaAprovacao where IdPRONAC = '{$idpronac}')");
            $arrWhereFontesIncentivo["tpAcao <> ('E') OR tpAcao IS NULL "] = '(?)';
            $fonteincentivo = $planilhaAprovacao->somarItensPlanilhaAprovacao($arrWhereFontesIncentivo);

            $arrWhereOutrasFontes = $arrWhereSomaPlanilha;
            $arrWhereOutrasFontes['idPlanilhaItem <> ? '] = '206'; //elaboracao e agenciamento
            $arrWhereOutrasFontes['tpPlanilha = ? '] = 'SR';
            $arrWhereOutrasFontes['stAtivo = ? '] = 'N';
            $arrWhereOutrasFontes['NrFonteRecurso <> ? '] = '109';
            $arrWhereOutrasFontes["idPedidoAlteracao = (?)"] = new Zend_Db_Expr("(SELECT TOP 1 max(idPedidoAlteracao) from sac.dbo.tbPlanilhaAprovacao where IdPRONAC = '{$idpronac}')");
            $arrWhereOutrasFontes["tpAcao <> ('E') OR tpAcao IS NULL "] = '(?)';
            $outrasfontes = $planilhaAprovacao->somarItensPlanilhaAprovacao($arrWhereOutrasFontes);

            $arrWherePlanilhaPA = $arrWhereSomaPlanilha;
            $arrWherePlanilhaPA['idPlanilhaItem <> ? '] = '206'; //elaboracao e agenciamento
            $arrWherePlanilhaPA['tpPlanilha = ? '] = 'PA';
            $arrWherePlanilhaPA['stAtivo = ? '] = 'N';
            $arrWherePlanilhaPA['NrFonteRecurso = ? '] = '109';
            $arrWherePlanilhaPA["idPedidoAlteracao = (?)"] = new Zend_Db_Expr("(SELECT TOP 1 max(idPedidoAlteracao) from sac.dbo.tbPlanilhaAprovacao where IdPRONAC = '{$idpronac}')");
            $arrWherePlanilhaPA["tpAcao <> ('E') OR tpAcao IS NULL "] = '(?)';
            $parecerista = $planilhaAprovacao->somarItensPlanilhaAprovacao($arrWherePlanilhaPA);
        }
        $this->view->fontesincentivo = $fonteincentivo['soma'];
        $this->view->outrasfontes = $outrasfontes['soma'];
        $this->view->valorproposta = $fonteincentivo['soma'] + $outrasfontes['soma'];
        $this->view->valorparecerista = $parecerista['soma'];
        /***************** FIM  - MODO NOVO ********************/
        /**** FIM -CODIGO DE READEQUACAO ****/

        $this->view->ResultProduto = $produtos;
        $verificaEnquadramento = RealizarAnaliseProjetoDAO::verificaEnquadramento($idpronac, $tpPlanilha);
        if (count($verificaEnquadramento) > 0) {
            if ($verificaEnquadramento[0]->stArtigo18 == true) {
                $this->view->enquadramento = 'Artigo 18';
            } else if ($verificaEnquadramento[0]->stArtigo26 == true) {
                $this->view->enquadramento = 'Artigo 26';
            } else {
                $this->view->enquadramento = 'NAO ENQUADRADO';
            }
        } else {
            $this->view->enquadramento = 'NAO ENQUADRADO';
        }

        $auth = Zend_Auth::getInstance(); // pega a autenticacao
        $idagente = GerenciarPautaReuniaoDAO::consultaAgenteUsuario($auth->getIdentity()->usu_codigo);
        $idagente = $idagente['idAgente'];
        //-------------------------------------------------------------------------------------------------------------
        $reuniao = new Reuniao();
        $ConsultaReuniaoAberta = $reuniao->buscar(array("stEstado = ?" => 0));
        if ($ConsultaReuniaoAberta->count() > 0) {
            $ConsultaReuniaoAberta = $ConsultaReuniaoAberta->current()->toArray();
            $this->view->dadosReuniaoPlenariaAtual = $ConsultaReuniaoAberta;
            //---------------------------------------------------------------------------------------------------------------
            $votantes = new Votante();
            $exibirVotantes = $votantes->selecionarvotantes($ConsultaReuniaoAberta['idNrReuniao']);
            if (count($exibirVotantes) > 0) {
                foreach ($exibirVotantes as $votantes) {
                    $dadosVotante[] = $votantes->idAgente;
                }
                if (count($dadosVotante) > 0) {
                    if (in_array($idagente, $dadosVotante)) {
                        $this->view->votante = true;
                    } else {
                        $this->view->votante = false;
                    }
                }
            }
        } else {
            parent::message("N&atilde;o existe CNIC aberta no momento. Favor aguardar!", "principal/index", "ERROR");
        }
    }

    public function analisedecontaAction()
    {
        $planilhaaprovacao = new PlanilhaAprovacao();
        $tblPauta = new Pauta();
        $tblPlanilhaProposta = new Proposta_Model_DbTable_TbPlanilhaProposta();
        $projeto = new Projetos();
        // caso o formulario seja enviado via post
        // atualiza a planilha
        if ($this->getRequest()->isPost()) {

            // recebe os dados via post
            $post = Zend_Registry::get('post');
            $idPlanilha = $post->idPlanilha;
            $idpronac = $post->idpronac;

            $unidade = $post->unidade;
            $qtdItem = $post->qtd;
            $ocorrencia = $post->ocorrencia;
            $vlunitario = Mascara::delMaskMoeda($post->vlunitario);
            $dias = $post->dias;
            $justificativa = $post->justificativa;
            //define tipo de planilha a ser utilizada baseado na ultima planilha criada
            //antigo modo
            /*$buscaReadAprovacadoCnic = $tblPauta->buscar(array('IdPRONAC = ?'=>$idpronac, 'stAnalise = ?'=>"AS"));
                                if($buscaReadAprovacadoCnic->count() > 0){
                                    $tpPlanilha = 'SE';
                                } else{
                                    $tpPlanilha = 'CO';
                                }*/
            //novo modo
            $rsPlanilhaAtual = $planilhaaprovacao->buscar(array('IdPRONAC = ?' => $idpronac), array('dtPlanilha DESC'))->current();
            $tpPlanilha = (!empty($rsPlanilhaAtual) && $rsPlanilhaAtual->tpPlanilha == 'SE') ? 'SE' : 'CO';

            $auth = Zend_Auth::getInstance(); // pega a autenticacao
            $idagente = GerenciarPautaReuniaoDAO::consultaAgenteUsuario($auth->getIdentity()->usu_codigo);
            $idagente = $idagente['idAgente'];
            $objAcesso = new Acesso();
            $dados = array(
                'dtPlanilha' => $objAcesso->getExpressionDate(),
                'idUnidade' => $unidade,
                'qtItem' => $qtdItem,
                'nrOcorrencia' => $ocorrencia,
                'vlUnitario' => $vlunitario,
                'qtDias' => $dias,
                'dsJustificativa' => $justificativa,
                'idAgente' => $idagente);
            $where = 'idPlanilhaAprovacao = ' . $idPlanilha . "and TpPlanilha = '" . $tpPlanilha . "'";
            $alterarPlanilha = $planilhaaprovacao->alterar($dados, $where);
            if ($alterarPlanilha) {
                parent::message("Registro inserido com sucesso!", "realizaranaliseprojeto/analisedeconta/idpronac/" . $idpronac, "CONFIRM");
            } else {
                throw new Exception("Erro ao efetuar alteracao!");
            }
        }
        else {
            // recebe os dados via get
            $idpronac = $this->_request->getParam("idpronac");
            $buscarprojeto = $projeto->buscar(array('IdPRONAC = ?' => $idpronac))->current()->toArray();

            //antigo modo
            /*$buscaReadAprovacadoCnic = $tblPauta->buscar(array('IdPRONAC = ?'=>$idpronac, 'stAnalise = ?'=>"AS"));
                                if($buscaReadAprovacadoCnic->count() > 0){
                                    $tpplanilha = 'SE';
                                }
                                else{
                                    $tpplanilha = 'CO';
                                }*/
            //novo modo
            //define tipo de planilha a ser utilizada baseado na ultima planilha criada
            $rsPlanilhaAtual = $planilhaaprovacao->buscar(array('IdPRONAC = ?' => $idpronac), array('dtPlanilha DESC'))->current();
            $tpplanilha = (!empty($rsPlanilhaAtual) && $rsPlanilhaAtual->tpPlanilha == 'SE') ? 'SE' : 'CO';

            $buscarAnaliseConta = $planilhaaprovacao->buscarAnaliseConta($idpronac, $tpplanilha, array('pap.stAtivo=?' => 'S'));

            // ===== TOTAL VALOR REDUZIDO E TOTAL DE ITENS =====
            $itemReduzido = false;
            $itemRetirado = false;
            $totalValorReduzido = 0;
            $totalItemReduzido = 0;

            $totalValorRetirado = 0;
            $totalItemRetirado = 0;
            $valores['reduzido'] = array();
            $valores['retirado'] = array();
            foreach ($buscarAnaliseConta as $b) {
                $valorproponente = ($b->qtdSolicitado * $b->ocoSolicitado * $b->vlSolicitado);
                $valorcomponente = ($b->ocorrenciaRelator * $b->vlunitarioRelator * $b->qtdRelator);
                $valorparecerista = ($b->ocoParecer * $b->vlParecer * $b->qtdParecer);
                if ($valorcomponente < $valorproponente and $valorcomponente != 0) {
                    $valores['reduzido'][$totalItemReduzido]['idPlanilhaAprovacao'] = $b->idPlanilhaAprovacao;
                    $valores['reduzido'][$totalItemReduzido]['nrFonteRecurso'] = $b->nrFonteRecurso;
                    $valores['reduzido'][$totalItemReduzido]['idProduto'] = $b->idProduto;
                    $valores['reduzido'][$totalItemReduzido]['item'] = $b->Item;
                    $valores['reduzido'][$totalItemReduzido]['idEtapa'] = $b->idEtapa;
                    $valores['reduzido'][$totalItemReduzido]['Etapa'] = $b->Etapa;
                    $valores['reduzido'][$totalItemReduzido]['Produto'] = $b->produto;
                    $valores['reduzido'][$totalItemReduzido]['vlreduzidoComp'] = $valorproponente - $valorcomponente;
                    $valores['reduzido'][$totalItemReduzido]['VlReduzidoParecerista'] = $valorparecerista - $valorproponente;

                    $valores['reduzido'][$totalItemReduzido]['vltotalsolicitado'] = $valorproponente;
                    $valores['reduzido'][$totalItemReduzido]['UnidadeProposta'] = $b->UnidadeProposta;
                    $valores['reduzido'][$totalItemReduzido]['qtdSolicitado'] = $b->qtdSolicitado;
                    $valores['reduzido'][$totalItemReduzido]['ocoSolicitado'] = $b->ocoSolicitado;
                    $valores['reduzido'][$totalItemReduzido]['vlunitarioSolicitado'] = $b->vlSolicitado;
                    $valores['reduzido'][$totalItemReduzido]['diasSolicitado'] = $b->diasSolicitado;

                    $valores['reduzido'][$totalItemReduzido]['idUnidade'] = $b->idUnidade;
                    $valores['reduzido'][$totalItemReduzido]['Unidade'] = $b->Unidade;
                    $valores['reduzido'][$totalItemReduzido]['diasRelator'] = $b->diasRelator;
                    $valores['reduzido'][$totalItemReduzido]['ocorrenciaRelator'] = $b->ocorrenciaRelator;
                    $valores['reduzido'][$totalItemReduzido]['vlunitarioRelator'] = $b->vlunitarioRelator;
                    $valores['reduzido'][$totalItemReduzido]['diasRelator'] = $b->diasRelator;
                    $valores['reduzido'][$totalItemReduzido]['qtdRelator'] = $b->qtdRelator;
                    $valores['reduzido'][$totalItemReduzido]['vltotalcomponente'] = $valorcomponente;
                    $valores['reduzido'][$totalItemReduzido]['justcomponente'] = $b->JSComponente;

                    $valores['reduzido'][$totalItemReduzido]['UnidadeProjeto'] = $b->UnidadeProposta;
                    $valores['reduzido'][$totalItemReduzido]['qtdParecer'] = $b->qtdParecer;
                    $valores['reduzido'][$totalItemReduzido]['ocoParecer'] = $b->ocoParecer;
                    $valores['reduzido'][$totalItemReduzido]['diasParecerista'] = $b->diasParecerista;
                    $valores['reduzido'][$totalItemReduzido]['vltotalparecerista'] = $valorparecerista;
                    $valores['reduzido'][$totalItemReduzido]['vlunitarioparecerista'] = $b->vlParecer;
                    $valores['reduzido'][$totalItemReduzido]['justparecerista'] = $b->JSParecerista;

                    $itemReduzido = true;
                    $reduzido = $valorproponente - $valorcomponente;
                    $totalValorReduzido += (float)$reduzido;
                    $totalItemReduzido++;
                }
                if ($valorcomponente == 0 and $valorproponente > 0) {

                    $valores['retirado'][$totalItemRetirado]['idPlanilhaAprovacao'] = $b->idPlanilhaAprovacao;
                    $valores['retirado'][$totalItemRetirado]['nrFonteRecurso'] = $b->nrFonteRecurso;
                    $valores['retirado'][$totalItemRetirado]['idProduto'] = $b->idProduto;
                    $valores['retirado'][$totalItemRetirado]['item'] = $b->Item;
                    $valores['retirado'][$totalItemRetirado]['idEtapa'] = $b->idEtapa;
                    $valores['retirado'][$totalItemRetirado]['Etapa'] = $b->Etapa;
                    $valores['retirado'][$totalItemRetirado]['Produto'] = $b->produto;
                    $valores['retirado'][$totalItemRetirado]['vlretiradoComp'] = $valorproponente - $valorcomponente;
                    $valores['retirado'][$totalItemRetirado]['VlretiradoParecerista'] = $valorparecerista - $valorproponente;

                    $valores['retirado'][$totalItemRetirado]['vltotalsolicitado'] = $valorproponente;
                    $valores['retirado'][$totalItemRetirado]['UnidadeProposta'] = $b->UnidadeProposta;
                    $valores['retirado'][$totalItemRetirado]['qtdSolicitado'] = $b->qtdSolicitado;
                    $valores['retirado'][$totalItemRetirado]['ocoSolicitado'] = $b->ocoSolicitado;
                    $valores['retirado'][$totalItemRetirado]['vlunitarioSolicitado'] = $b->vlSolicitado;
                    $valores['retirado'][$totalItemRetirado]['diasSolicitado'] = $b->diasSolicitado;

                    $valores['retirado'][$totalItemRetirado]['idUnidade'] = $b->idUnidade;
                    $valores['retirado'][$totalItemRetirado]['Unidade'] = $b->Unidade;
                    $valores['retirado'][$totalItemRetirado]['diasRelator'] = $b->diasRelator;
                    $valores['retirado'][$totalItemRetirado]['qtdRelator'] = $b->qtdRelator;
                    $valores['retirado'][$totalItemRetirado]['ocorrenciaRelator'] = $b->ocorrenciaRelator;
                    $valores['retirado'][$totalItemRetirado]['vlunitarioRelator'] = $b->vlunitarioRelator;
                    $valores['retirado'][$totalItemRetirado]['diasRelator'] = $b->diasRelator;
                    $valores['retirado'][$totalItemRetirado]['vltotalcomponente'] = $valorcomponente;
                    $valores['retirado'][$totalItemRetirado]['justcomponente'] = $b->JSComponente;

                    $valores['retirado'][$totalItemRetirado]['UnidadeProjeto'] = $b->UnidadeProposta;
                    $valores['retirado'][$totalItemRetirado]['qtdParecer'] = $b->qtdParecer;
                    $valores['retirado'][$totalItemRetirado]['ocoParecer'] = $b->ocoParecer;
                    $valores['retirado'][$totalItemRetirado]['diasParecerista'] = $b->diasParecerista;
                    $valores['retirado'][$totalItemRetirado]['vltotalparecerista'] = $valorparecerista;
                    $valores['retirado'][$totalItemRetirado]['vlunitarioparecerista'] = $b->vlParecer;
                    $valores['retirado'][$totalItemRetirado]['justparecerista'] = $b->JSParecerista;

                    $itemRetirado = true;
                    $retirado = $valorproponente - $valorcomponente;
                    $totalValorRetirado += (float)$retirado;
                    $totalItemRetirado++;
                }
            }
            $buscarPlanilhaUnidade = PlanilhaUnidadeDAO::buscar();
            //antiga soma
            //$buscarsomaaprovacao = $planilhaaprovacao->somarPlanilhaAprovacao($idpronac, 206 , $tpplanilha);
            //nova soma
            $arrWhereSomaPlanilha = array();
            $arrWhereSomaPlanilha['idPronac = ?'] = $idpronac;
            $arrWhereSomaPlanilha['idPlanilhaItem <> ? '] = '206'; //elaboracao e agenciamento
            $arrWhereSomaPlanilha['tpPlanilha = ? '] = $tpplanilha;
            $arrWhereSomaPlanilha['NrFonteRecurso = ? '] = '109';
            $arrWhereSomaPlanilha['stAtivo = ? '] = 'S';
            $buscarsomaaprovacao = $planilhaaprovacao->somarItensPlanilhaAprovacao($arrWhereSomaPlanilha);

            $buscarsomaproposta = $tblPlanilhaProposta->somarPlanilhaProposta($buscarprojeto['idProjeto']);
            $this->view->planilhaUnidade = $buscarPlanilhaUnidade;
            $this->view->analiseReduzido = $valores['reduzido'];
            $this->view->analiseRetirado = $valores['retirado'];
            $this->view->pronac = $buscarprojeto;
            $this->view->idpronac = $idpronac;
            $this->view->itemReduzido = $itemReduzido;
            $this->view->itemRetirado = $itemRetirado;

            $this->view->totValRed = $totalValorReduzido;
            $this->view->totItemRed = $totalItemReduzido;
            $this->view->totValRet = $totalValorRetirado;
            $this->view->totItemRet = $totalItemRetirado;

            $this->view->totalproponente = $buscarsomaproposta['soma'];
            $this->view->totalcomponente = $buscarsomaaprovacao['soma'];

            $auth = Zend_Auth::getInstance(); // pega a autenticacao
            $idagente = GerenciarPautaReuniaoDAO::consultaAgenteUsuario($auth->getIdentity()->usu_codigo);
            $idagente = $idagente['idAgente'];
            //-------------------------------------------------------------------------------------------------------------
            $reuniao = new Reuniao();
            $ConsultaReuniaoAberta = $reuniao->buscar(array("stEstado = ?" => 0));
            if ($ConsultaReuniaoAberta->count() > 0) {
                $ConsultaReuniaoAberta = $ConsultaReuniaoAberta->current()->toArray();
                $this->view->dadosReuniaoPlenariaAtual = $ConsultaReuniaoAberta;
                //---------------------------------------------------------------------------------------------------------------
                $votantes = new Votante();
                $exibirVotantes = $votantes->selecionarvotantes($ConsultaReuniaoAberta['idNrReuniao']);
                if (count($exibirVotantes) > 0) {
                    foreach ($exibirVotantes as $votantes) {
                        $dadosVotante[] = $votantes->idAgente;
                    }
                    if (count($dadosVotante) > 0) {
                        if (in_array($idagente, $dadosVotante)) {
                            $this->view->votante = true;
                        } else {
                            $this->view->votante = false;
                        }
                    }
                }
            } else {
                parent::message("N&atilde;o existe CNIC aberta no momento. Favor aguardar!", "principal/index", "ERROR");
            }
        } // fecha else
    } // fecha Metodo analisedecontaAction()


    /**
     * Metodo para realizar a Analise de Conteudo
     * @access public
     * @param void
     * @return void
     */
    /*public function analisedeconteudoAction()
	{
                $planilhaaprovacao = new PlanilhaAprovacao();
		// caso o formulario seja enviado via post
		if ($this->getRequest()->isPost())
		{
			// recebe os dados via post
			$post                 = Zend_Registry::get('post');
			$idPlanilha           = $post->idPlanilha;
			$IdPRONAC             = $post->IdPRONAC;
			$idProduto            = $post->idProduto;

			$stLei8313            = $post->stLei8313;
			$stArtigo3            = $post->stArtigo3;
			$nrIncisoArtigo3      = $post->nrIncisoArtigo3;
			$dsAlineaArt3         = $post->dsAlineaArt3;

			$stArtigo18           = $post->stArtigo18;
			$dsAlineaArtigo18     = $post->dsAlineaArtigo18;
			$stArtigo26           = $post->stArtigo26;

			$stLei5761            = $post->stLei5761;
			$stArtigo27           = $post->stArtigo27;
			$stIncisoArtigo27_I   = $post->stIncisoArtigo27_I;
			$stIncisoArtigo27_II  = $post->stIncisoArtigo27_II;
			$stIncisoArtigo27_III = $post->stIncisoArtigo27_III;
			$stIncisoArtigo27_IV  = $post->stIncisoArtigo27_IV;
			$stAvaliacao          = $post->stAvaliacao;
			$dsAvaliacao          = $post->dsAvaliacao;

                        $page = $post->page;

                        $rsPlanilhaAtual = $planilhaaprovacao->buscar(array('IdPRONAC = ?'=>$IdPRONAC), array('dtPlanilha DESC'))->current();
                        $tpAnalise = (!empty($rsPlanilhaAtual) && $rsPlanilhaAtual->tpPlanilha == 'SE') ? 'SE' : 'CO';

			try
			{
                            $auth = Zend_Auth::getInstance(); // pega a autenticacao
                            $idagente = GerenciarPautaReuniaoDAO::consultaAgenteUsuario($auth->getIdentity()->usu_codigo);
                            $idagente = $idagente['idAgente'];
				// atualiza todos os campos caso siga a Lei 8313
				if ($stLei8313 == 1)
				{
					$dados = array(
						'tpAnalise'            => $tpAnalise,
						'dtAnalise'            => new Zend_Db_Expr('GETDATE()'),
						'stLei8313'            => $stLei8313,
						'stArtigo3'            => $stArtigo3,
						'nrIncisoArtigo3'      => $nrIncisoArtigo3,
						'dsAlineaArt3'         => $dsAlineaArt3,
						'stArtigo18'           => $stArtigo18,
						'dsAlineaArtigo18'     => $dsAlineaArtigo18,
						'stArtigo26'           => $stArtigo26,
						'stLei5761'            => $stLei5761,
						'stArtigo27'           => $stArtigo27,
						'stIncisoArtigo27_I'   => $stIncisoArtigo27_I,
						'stIncisoArtigo27_II'  => $stIncisoArtigo27_II,
						'stIncisoArtigo27_III' => $stIncisoArtigo27_III,
						'stIncisoArtigo27_IV'  => $stIncisoArtigo27_IV,
						'stAvaliacao'          => $stAvaliacao,
						'dsAvaliacao'          => $dsAvaliacao,
						'idAgente'             => $idagente);
					$alterarAnalise = AnaliseAprovacaoDAO::alterar($dados, $IdPRONAC, $idProduto, $idPlanilha, $tpAnalise);
				}
				// atualiza apenas o campo com a Lei 8313 caso o valor da mesma seja 0
				else
				{
					$dados = array(
						'tpAnalise'            => 'CO',
						'dtAnalise'            => new Zend_Db_Expr('GETDATE()'),
						'stLei8313'            => $stLei8313,
						'idAgente'             => $idagente);
					$alterarAnalise = AnaliseAprovacaoDAO::alterar($dados, $IdPRONAC, $idProduto, $idPlanilha, $tpAnalise);
				}

				if ($alterarAnalise)
				{
					parent::message("Registro inserido com sucesso!", "realizaranaliseprojeto/analisedeconteudo/idpronac/".$IdPRONAC, "CONFIRM");
				}
				else
				{
					throw new Exception("Erro ao efetuar alteracao!");
				}
			} // fecha try
			catch (Exception $e)
			{
				parent::message($e->getMessage(), "realizaranaliseprojeto/analisedeconteudo/idpronac/".$IdPRONAC, "ERROR");
			}
		} // fecha if
		// quando a pagina e aberta
		else
		{
			// recebe o id da planilha a ser alterada
			$idPronac   = $this->_request->getParam("idpronac");
                        $tblPauta = new Pauta();
                        $projeto = new Projetos();

                        $buscarprojeto = $projeto->buscar(array('IdPRONAC = ?'=>$idPronac))->current()->toArray();
                        $analise = new AnaliseAprovacao();
                        //antigo modo
                        //$buscaReadAprovacadoCnic = $tblPauta->buscar(array('IdPRONAC = ?'=>$idPronac, 'stAnalise = ?'=>"AS"));
                        //novo modo
                        //define tipo de planilha a ser utilizada baseado na ultima planilha criada
                        $rsPlanilhaAtual = $planilhaaprovacao->buscar(array('IdPRONAC = ?'=>$idPronac), array('dtPlanilha DESC'))->current();
                        if(!empty($rsPlanilhaAtual) && $rsPlanilhaAtual->tpPlanilha == 'SE'){
                            $buscar = $analise->buscarAnaliseProduto('SE', $idPronac);
                        }
                        else{
//                            $buscar = RealizarAnaliseProjetoDAO::analiseDeConteudo($idPronac, 'CO') ;
                            $buscar = $analise->buscarAnaliseProduto('CO', $idPronac);
                        }
			// ========== INICIO PAGINACAO ==========
			// criando a paginacao
			Zend_Paginator::setDefaultScrollingStyle('Sliding');
			Zend_View_Helper_PaginationControl::setDefaultViewPartial('paginacao/paginacao.phtml');
			$paginator = Zend_Paginator::factory($buscar); // dados a serem paginados

			// pagina atual e quantidade de itens por pagina
			$currentPage = $this->_getParam('page', 1);
			$paginator->setCurrentPageNumber($currentPage)->setItemCountPerPage(1);
			// ========== FIM PAGINACAO ==========

			// manda para a visao
			$this->view->dados    = $paginator;
                        $this->view->dadosprojeto = $buscarprojeto;
			$this->view->qtdItens = count($buscar); // quantidade de itens
                        $auth              = Zend_Auth::getInstance(); // pega a autenticacao
                        $idagente = GerenciarPautaReuniaoDAO::consultaAgenteUsuario($auth->getIdentity()->usu_codigo);
                        $idagente = $idagente['idAgente'];

                        //-------------------------------------------------------------------------------------------------------------
                        $reuniao = new Reuniao();
                        $ConsultaReuniaoAberta = $reuniao->buscar(array("stEstado = ?" => 0));
                        if ($ConsultaReuniaoAberta->count() > 0) {
                            $ConsultaReuniaoAberta = $ConsultaReuniaoAberta->current()->toArray();
                            $this->view->dadosReuniaoPlenariaAtual = $ConsultaReuniaoAberta;
                            //---------------------------------------------------------------------------------------------------------------
                            $votantes = new Votante();
                            $exibirVotantes = $votantes->selecionarvotantes($ConsultaReuniaoAberta['idNrReuniao']);
                            if (count($exibirVotantes) > 0) {
                                foreach ($exibirVotantes as $votantes) {
                                    $dadosVotante[] = $votantes->idAgente;
                                }
                                if (count($dadosVotante) > 0) {
                                    if (in_array($idagente, $dadosVotante)) {
                                        $this->view->votante = true;
                                    } else {
                                        $this->view->votante = false;
                                    }
                                }
                            }
                        } else {
                            parent::message("N&atilde;o existe CNIC aberta no momento. Favor aguardar!", "principal/index", "ERROR");
                        }
                } // fecha else
	}*/ // fecha metodo analisedeconteudoAction()

    public function analisedeconteudoAction()
    {
        $this->intTamPag = 1;
        $params = ($this->getRequest()->getParams());
        $idPronac = $params['idpronac'];
        $this->view->idpronac = $idPronac;

        //DEFINE PARAMETROS DE ORDENACAO / QTDE. REG POR PAG. / PAGINACAO
        if ($this->_request->getParam("qtde")) {
            $this->intTamPag = $this->_request->getParam("qtde");
        }
        $order = array();

        //==== parametro de ordenacao  ======//
        if ($this->_request->getParam("ordem")) {
            $ordem = $this->_request->getParam("ordem");
            if ($ordem == "ASC") {
                $novaOrdem = "DESC";
            } else {
                $novaOrdem = "ASC";
            }
        } else {
            $ordem = "ASC";
            $novaOrdem = "ASC";
        }

        //==== campo de ordenacao  ======//
        if ($this->_request->getParam("campo")) {
            $campo = $this->_request->getParam("campo");
            $order = array($campo . " " . $ordem);
            $ordenacao = "&campo=" . $campo . "&ordem=" . $ordem;

        } else {
            $campo = null;
            $order = array('stPrincipal');
//                $order = array('ordenacao');
//                $order = array(1);
            $ordenacao = null;
        }

        $pag = 1;
        $get = Zend_Registry::get('get');
        if (isset($get->pag)) $pag = $get->pag;
        $inicio = ($pag > 1) ? ($pag - 1) * $this->intTamPag : 0;

        /* ================== PAGINACAO ======================*/
        $projetos = New Projetos();
        $tbPlanilhaAprovacao = new PlanilhaAprovacao();
        $tbAnaliseAprovacao = new AnaliseAprovacao();

        $rsProjeto = $projetos->buscar(array('IdPRONAC = ?' => $idPronac))->current()->toArray();

        //define tipo de planilha a ser utilizada baseado na ultima planilha criada
        $rsPlanilhaAtual = $tbPlanilhaAprovacao->buscar(array('IdPRONAC = ?' => $idPronac), array('dtPlanilha DESC'))->current();
        if (!empty($rsPlanilhaAtual) && $rsPlanilhaAtual->tpPlanilha == 'SE') {
            $tpPlanilha = 'SE';
        } else {
            $tpPlanilha = 'CO';
        }

        $where = array();
        $where['aa.tpAnalise = ?'] = $tpPlanilha;
        $where['aa.idPronac = ?'] = $idPronac;
        $where['PDP.stPlanoDistribuicaoProduto = ?'] = 1;

        $total = $tbAnaliseAprovacao->buscarAnalises($where, $order, null, null, true);
        $fim = $inicio + $this->intTamPag;

        $totalPag = (int)(($total % $this->intTamPag == 0) ? ($total / $this->intTamPag) : (($total / $this->intTamPag) + 1));
        $tamanho = ($fim > $total) ? $total - $inicio : $this->intTamPag;

        $busca = $tbAnaliseAprovacao->buscarAnalises($where, $order, $tamanho, $inicio);

        $paginacao = array(
            "pag" => $pag,
            "qtde" => $this->intTamPag,
            "campo" => $campo,
            "ordem" => $ordem,
            "ordenacao" => $ordenacao,
            "novaOrdem" => $novaOrdem,
            "total" => $total,
            "inicio" => ($inicio + 1),
            "fim" => $fim,
            "totalPag" => $totalPag,
            "Itenspag" => $this->intTamPag,
            "tamanho" => $tamanho
        );

        $auth = Zend_Auth::getInstance(); // pega a autenticao
        $Usuario = new Autenticacao_Model_Usuario(); // objeto usuario
        $idagente = $Usuario->getIdUsuario($auth->getIdentity()->usu_codigo);
        $idagente = $idagente['idAgente'];

        $reuniao = new Reuniao();
        $ConsultaReuniaoAberta = $reuniao->buscar(array("stEstado = ?" => 0));
        if ($ConsultaReuniaoAberta->count() > 0) {
            $this->view->dadosReuniaoPlenariaAtual = $ConsultaReuniaoAberta;
            $this->view->usu_codigo = $auth->getIdentity()->usu_codigo;
            $ConsultaReuniaoAberta = $ConsultaReuniaoAberta->current()->toArray();
            $this->view->plenariaatual = $ConsultaReuniaoAberta['idNrReuniao'];
            $this->view->dadosReuniaoPlenariaAtual = $ConsultaReuniaoAberta;
            //---------------------------------------------------------------------------------------------------------------
            $votantes = new Votante();
            $exibirVotantes = $votantes->selecionarvotantes($ConsultaReuniaoAberta['idNrReuniao']);
            if (count($exibirVotantes) > 0) {
                foreach ($exibirVotantes as $votantes) {
                    $dadosVotante[] = $votantes->idAgente;
                }
                if (count($dadosVotante) > 0) {
                    if (in_array($idagente, $dadosVotante)) {
                        $this->view->votante = true;
                    } else {
                        $this->view->votante = false;
                    }
                }
            }
        }

        $this->view->paginacao = $paginacao;
        $this->view->qtdRegistros = $total;
        $this->view->dados = $busca;
        $this->view->intTamPag = $this->intTamPag;
        $this->view->dadosprojeto = $rsProjeto;

    } // fecha metodo analisedeconteudoAction()

    public function formAlterarAnaliseDeConteudoAction()
    {
        $this->_helper->layout->disableLayout(); // desabilita o layout

        $post = Zend_Registry::get('post');
        $idPronac = $this->_request->getParam("idpronac");
        $tpPlanilha = $this->_request->getParam("tpAnalise");
        $idAnaliseAprovacao = $this->_request->getParam("idAnaliseAprovacao");

        $projeto = new Projetos();
        $tbAnaliseAprovacao = new AnaliseAprovacao();

        $rsProjeto = $projeto->buscar(array('IdPRONAC = ?' => $idPronac))->current();
        $this->view->dadosprojeto = $rsProjeto;

        $rs = $tbAnaliseAprovacao->buscarAnaliseProduto($tpPlanilha, $idPronac, null, array('aa.idAnaliseAprovacao=?' => $idAnaliseAprovacao))->current();
        $this->view->analise = $rs;
        $this->view->parametrosBusca = $_POST;
    }

    public function alterarAnaliseDeConteudoAction()
    {
//            $this->_helper->layout->disableLayout(); // desabilita o layout

        $post = Zend_Registry::get('post');

        $projeto = new Projetos();
        $tbPlanilhaAprovacao = new PlanilhaAprovacao();
        $tbAnaliseAprovacao = new AnaliseAprovacao();

        // recebe os dados via post
        $tpAnalise = $post->tpAnalise;

        $idPlanilha = $post->idPlanilha;
        $IdPRONAC = $post->IdPRONAC;
        $idProduto = $post->idProduto;

        $stLei8313 = $post->stLei8313;
        $stArtigo3 = $post->stArtigo3;
        $nrIncisoArtigo3 = $post->nrIncisoArtigo3;
        $dsAlineaArt3 = $post->dsAlineaArt3;

        $stArtigo18 = $post->stArtigo18;
        $dsAlineaArtigo18 = $post->dsAlineaArtigo18;
        $stArtigo26 = $post->stArtigo26;

        $stLei5761 = empty($post->stLei5761) ? 1 : $post->stLei5761;
        $stArtigo27 = $post->stArtigo27;
        $stIncisoArtigo27_I = $post->stIncisoArtigo27_I;
        $stIncisoArtigo27_II = $post->stIncisoArtigo27_II;
        $stIncisoArtigo27_III = $post->stIncisoArtigo27_III;
        $stIncisoArtigo27_IV = $post->stIncisoArtigo27_IV;
        $stAvaliacao = $post->stAvaliacao;
        $dsAvaliacao = Seguranca::tratarVarAjaxUFT8($post->dsAvaliacao);

        try {
            $auth = Zend_Auth::getInstance(); // pega a autenticacao
            $idagente = GerenciarPautaReuniaoDAO::consultaAgenteUsuario($auth->getIdentity()->usu_codigo);
            $idagente = $idagente['idAgente'];
            // atualiza todos os campos caso siga a Lei 8313
            $objAcesso = new Acesso();
            if ($stLei8313 == 1) {
                $dados = array(
                    'tpAnalise' => $tpAnalise,
                    'dtAnalise' => $objAcesso->getExpressionDate(),
                    'stLei8313' => $stLei8313,
                    'stArtigo3' => $stArtigo3,
                    'nrIncisoArtigo3' => $nrIncisoArtigo3,
                    'dsAlineaArt3' => $dsAlineaArt3,
                    'stArtigo18' => $stArtigo18,
                    'dsAlineaArtigo18' => $dsAlineaArtigo18,
                    'stArtigo26' => $stArtigo26,
                    'stLei5761' => $stLei5761,
                    'stArtigo27' => $stArtigo27,
                    'stIncisoArtigo27_I' => $stIncisoArtigo27_I,
                    'stIncisoArtigo27_II' => $stIncisoArtigo27_II,
                    'stIncisoArtigo27_III' => $stIncisoArtigo27_III,
                    'stIncisoArtigo27_IV' => $stIncisoArtigo27_IV,
                    'stAvaliacao' => $stAvaliacao,
                    'dsAvaliacao' => trim($dsAvaliacao),
                    'idAgente' => $idagente);
                $alterarAnalise = AnaliseAprovacaoDAO::alterar($dados, $IdPRONAC, $idProduto, $idPlanilha, $tpAnalise);
            } // atualiza apenas o campo com a Lei 8313 caso o valor da mesma seja 0
            else {

                $dados = array(
                    'tpAnalise' => $tpAnalise,
                    'dtAnalise' => $objAcesso->getExpressionDate(),
                    'stLei8313' => $stLei8313,
                    'stArtigo3' => 0,
                    'nrIncisoArtigo3' => NULL,
                    'dsAlineaArt3' => NULL,
                    'stArtigo18' => 0,
                    'dsAlineaArtigo18' => NULL,
                    'stArtigo26' => 0,
                    'stLei5761' => 0,
                    'stArtigo27' => 0,
                    'stIncisoArtigo27_I' => NULL,
                    'stIncisoArtigo27_II' => NULL,
                    'stIncisoArtigo27_III' => NULL,
                    'stIncisoArtigo27_IV' => NULL,
                    'stAvaliacao' => 0,
                    'dsAvaliacao' => trim($dsAvaliacao),
                    'idAgente' => $idagente);
                $alterarAnalise = AnaliseAprovacaoDAO::alterar($dados, $IdPRONAC, $idProduto, $idPlanilha, $tpAnalise);
            }

            //ZERA VALORES DOS ITENS DA PLANILHA DO PRODUTO DESFAVORECIDO
            if ($stAvaliacao != 1) {

                try {
                    $tblPlanilhaAprovacao = new PlanilhaAprovacao();

                    $dados = null;
                    $dados = array('qtItem' => 0,
                        'nrOcorrencia' => 0,
                        'vlUnitario' => 0);

                    $where = "IdPRONAC = '{$IdPRONAC}'";
                    $where .= " AND idProduto = '{$idProduto}'";
                    $where .= " AND tpPlanilha = '{$tpAnalise}'";
                    if ($this->bln_readequacao == "true") {
                        $where .= " AND stAtivo = 'N'";
                        $where .= " AND idPedidoAlteracao = (SELECT TOP 1 max(idPedidoAlteracao) from sac.dbo.tbPlanilhaAprovacao where IdPRONAC = '{$IdPRONAC}')";
                    } else {
                        $where .= " AND stAtivo = 'S'";
                    }
                    $tblPlanilhaAprovacao->alterar($dados, $where);

                } // fecha try
                catch (Exception $e) {
                    throw new Exception("Erro ao efetuar alteracao!");
                }
            }

            if ($alterarAnalise) {
//                            $this->_forward('analisedeconteudo',null,null, array('error'=>'false','msg'=>'Dados alterados com sucesso!'));
                parent::message("Dados alterados com sucesso!", "realizaranaliseprojeto/analisedeconteudo/idpronac/" . $IdPRONAC, "CONFIRM");
            } else {
//                            $this->_forward('analisedeconteudo',null,null, array('error'=>'true','msg'=>'Erro ao efetuar altera��o!'));
                throw new Exception("Erro ao efetuar alteracao!");
            }
        } // fecha try
        catch (Exception $e) {
//                $this->_forward('analisedeconteudo',null,null, array('error'=>'true','msg'=>$e->getMessage(),'pag'=>$this->_request->getParam("pag")));
            parent::message($e->getMessage(), "realizaranaliseprojeto/analisedeconteudo/idpronac/" . $IdPRONAC, "ERROR");
        }
    }

    /**
     * Metodo com a tabela de analise de custos
     * @access public
     * @param void
     * @return void
     */
    public function analisedecustosAction()
    {
        $auth = Zend_Auth::getInstance(); // pega a autenticao
        $idagente = GerenciarPautaReuniaoDAO::consultaAgenteUsuario($auth->getIdentity()->usu_codigo);
        $idagente = $idagente['idAgente'];
        $tblPlanilhaAprovacao = new PlanilhaAprovacao();
        $tblPlanilhaProposta = new Proposta_Model_DbTable_TbPlanilhaProposta();
        $tblPlanilhaProjeto = new PlanilhaProjeto();
        $arrProdutosFavoraveis = array();
        // caso o formulario seja enviado via post
        // atualiza a planilha
        if ($this->getRequest()->isPost()) {
            // recebe os dados via post
            $post = Zend_Registry::get('post');
            $idPronac = $post->idpronac;
            $this->view->idpronac = $idPronac;
            $idplanilhaaprovacao = $post->idPlanilha;

            $unidade = $post->unidade;
            $qtdItem = $post->qtd;
            $ocorrencia = $post->ocorrencia;
            $vlunitario = Mascara::delMaskMoeda($post->vlunitario);
            $dias = $post->dias;
            $justificativa = $_POST['justificativa'];
            $idEtapa = $post->idEtapaDoItem;

            try {
                $rsPlanilhaAtual = $tblPlanilhaAprovacao->buscar(array('IdPRONAC = ?' => $idPronac), array('dtPlanilha DESC'))->current();
                $tpPlanilha = (!empty($rsPlanilhaAtual) && $rsPlanilhaAtual->tpPlanilha == 'SE') ? 'SE' : 'CO';
                $this->view->tpPlanilha = $tpPlanilha;
                $objAcesso = new Acesso();
                $dados = array(
                    'tpPlanilha' => $tpPlanilha,
                    'dtPlanilha' => $objAcesso->getExpressionDate(),
                    'idUnidade' => $unidade,
                    'qtItem' => $qtdItem,
                    'nrOcorrencia' => $ocorrencia,
                    'vlUnitario' => $vlunitario,
                    'qtDias' => $dias,
                    'dsJustificativa' => $justificativa,
                    'idAgente' => $idagente,
                    'idEtapa' => $idEtapa
                );

                $where = 'idPlanilhaAprovacao = ' . $idplanilhaaprovacao;
                $alterarPlanilha = $tblPlanilhaAprovacao->alterar($dados, $where);

                if ($alterarPlanilha) {
                    parent::message("Registro inserido com sucesso!", "realizaranaliseprojeto/analisedecustos/idpronac/" . $idPronac . "/?idplanilha=" . $idplanilhaaprovacao, "CONFIRM");
                } else {
                    throw new Exception("Erro ao efetuar altera&ccedil;o!");
                }
            } // fecha try
            catch (Exception $e) {
                parent::message($e->getMessage(), "realizaranaliseprojeto/analisedecustos/idpronac/" . $idPronac, "ERROR");
            }
        } // fecha if
        else {
            // recebe os dados via get
            $idpronac = $this->_request->getParam("idpronac");
            $this->view->idpronac = $idpronac;
            $tblPlanilhaAprovacao = new PlanilhaAprovacao();
            $tblPlanilhaProposta = new Proposta_Model_DbTable_TbPlanilhaProposta();
            $tblPlanilhaProjeto = new PlanilhaProjeto();
            $tblProjetos = new Projetos();
            $tblPlanoDistribuicao = new Proposta_Model_DbTable_PlanoDistribuicaoProduto();
            $tblAnaliseAprovacao = new AnaliseAprovacao();

            $rsPlanilhaAtual = $tblPlanilhaAprovacao->buscar(array('IdPRONAC = ?' => $idpronac), array('dtPlanilha DESC'))->current();
            $tipoplanilha = (!empty($rsPlanilhaAtual) && $rsPlanilhaAtual->tpPlanilha == 'SE') ? 'SE' : 'CO';
            $this->view->tpPlanilha = $tipoplanilha;

            $rsProjeto = $tblProjetos->buscar(array('IdPRONAC=?' => $idpronac))->current();
            $idPreProjeto = (!empty($rsProjeto->idProjeto)) ? $rsProjeto->idProjeto : '0';

            $rsProdutoPrincipal = $tblPlanoDistribuicao->buscar(array('idProjeto=?' => $idPreProjeto, 'stPrincipal=?' => 1, 'stPlanoDistribuicaoProduto = ?' => 1))->current();
            $rsAnaliseProdutoPrincipal = $tblAnaliseAprovacao->buscar(array('idPronac=?' => $idpronac, 'idProduto=?' => $rsProdutoPrincipal->idProduto, 'tpAnalise=?' => $tipoplanilha))->current();

            $buscarplanilha = $tblPlanilhaAprovacao->buscarAnaliseCustos($idpronac, $tipoplanilha, array('PAP.stAtivo=?' => 'S'));
            $planilhaaprovacao = array();
            $count = 0;
            $fonterecurso = null;
            foreach ($buscarplanilha as $resuplanilha) {
                $produto = $resuplanilha->Produto == null ? 'Administra&ccedil;&atilde;o do Projeto' : $resuplanilha->Produto;
                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['idPlanilhaAprovacao'] = $resuplanilha->idPlanilhaAprovacao;
                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['idUnidade'] = $resuplanilha->idUnidade;
                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['nrFonteRecurso'] = $resuplanilha->nrFonteRecurso;
                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['item'] = $resuplanilha->Item;

                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['diasprop'] = $resuplanilha->diasprop;
                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['quantidadeprop'] = $resuplanilha->quantidadeprop;
                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['ocorrenciaprop'] = $resuplanilha->ocorrenciaprop;
                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['valorUnitarioprop'] = $resuplanilha->valorUnitarioprop;
                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['UnidadeProposta'] = $resuplanilha->UnidadeProposta;
                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['vlproponente'] = $resuplanilha->VlSolicitado;
                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['justificitivaproponente'] = $resuplanilha->justificitivaproponente;

                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['UnidadeProjeto'] = $resuplanilha->UnidadeProjeto;
                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['quantidadeparc'] = $resuplanilha->quantidadeparc;
                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['ocorrenciaparc'] = $resuplanilha->ocorrenciaparc;
                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['ocorrenciaparc'] = $resuplanilha->ocorrenciaparc;
                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['diasparc'] = $resuplanilha->diasparc;
                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['valorUnitarioparc'] = $resuplanilha->valorUnitarioparc;
                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['vlparecerista'] = $resuplanilha->VlSugeridoParecerista;
                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['justificativaparecerista'] = $resuplanilha->dsJustificativaParecerista;

                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['Unidade'] = $resuplanilha->Unidade;
                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['qtitemcomp'] = $resuplanilha->qtitemcomp;
                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['nrocorrenciacomp'] = $resuplanilha->nrocorrenciacomp;
                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['vlunitariocomp'] = $resuplanilha->vlunitariocomp;
                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['qtdiascomp'] = $resuplanilha->qtdiascomp;
                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['Unidadecomp'] = $resuplanilha->Unidade;
                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['vlconselheiro'] = $resuplanilha->VlSugeridoConselheiro;
                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['justificativaconselheiro'] = $resuplanilha->dsJustificativaConselheiro;
                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['reducao'] = $resuplanilha->VlSugeridoConselheiro < $resuplanilha->VlSolicitado ? 1 : 0;
                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['idProduto'] = $resuplanilha->idProduto;
                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['idEtapa'] = $resuplanilha->idEtapa;
                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['idPlanilhaItem'] = $resuplanilha->idPlanilhaItem;
                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['stAvaliacao'] = $resuplanilha->stAvaliacao;

                //grava array com produtos favorecidos na analise de conteudo
                if ($resuplanilha->idProduto >= 1) {
                    if ($resuplanilha->stAvaliacao == 1) {
                        $arrProdutosFavoraveis[$produto] = $resuplanilha->stAvaliacao;
                    }
                } else {
                    if ($rsAnaliseProdutoPrincipal->stAvaliacao == 1) {
                        $arrProdutosFavoraveis[$produto] = 1; //Admistracao do Projeto, que nao possui codigo de produto (so favorece os custos administrativos se o produto principal estiver favorecido)
                    }
                }
                $count++;
            }

            $buscarprojeto = $tblProjetos->buscar(array('IdPRONAC = ?' => $idpronac))->current();
            //antiga soma
            //$buscarsomaaprovacao = $tblPlanilhaAprovacao->somarPlanilhaAprovacao($idpronac, 206 , $tipoplanilha, array('PAP.stAtivo=?'=>'S'));

            //nova soma
            $arrWhereSomaPlanilha = array();
            $arrWhereSomaPlanilha['idPronac = ?'] = $idpronac;
            $arrWhereSomaPlanilha['idPlanilhaItem <> ? '] = '206'; //elaboracao e agenciamento
            $arrWhereSomaPlanilha['tpPlanilha = ? '] = $tipoplanilha;
            $arrWhereSomaPlanilha['NrFonteRecurso = ? '] = '109';
            $arrWhereSomaPlanilha['stAtivo = ? '] = 'S';
            $buscarsomaaprovacao = $tblPlanilhaAprovacao->somarItensPlanilhaAprovacao($arrWhereSomaPlanilha);

            $buscarsomaproposta = $tblPlanilhaProposta->somarPlanilhaProposta($buscarprojeto->idProjeto);
            $buscarsomaprojeto = $tblPlanilhaProjeto->somarPlanilhaProjeto($idpronac, 109);
            $buscarPlanilhaUnidade = PlanilhaUnidadeDAO::buscar();
            $this->view->planilhaUnidade = $buscarPlanilhaUnidade;
            $this->view->planilha = $planilhaaprovacao;
            $this->view->projeto = $buscarprojeto;
            $this->view->totalcomponente = $buscarsomaaprovacao['soma'];
            $this->view->totalparecerista = $buscarsomaprojeto['soma'];
            $this->view->totalproponente = $buscarsomaproposta['soma'];
            $this->view->produtosFavoraveis = $arrProdutosFavoraveis;
        } // fecha else

        $auth = Zend_Auth::getInstance(); // pega a autenticao
        $Usuario = new Autenticacao_Model_Usuario(); // objeto usuario
        $idagente = $Usuario->getIdUsuario($auth->getIdentity()->usu_codigo);
        $idagente = $idagente['idAgente'];

        $reuniao = new Reuniao();
        $ConsultaReuniaoAberta = $reuniao->buscar(array("stEstado = ?" => 0));
        if ($ConsultaReuniaoAberta->count() > 0) {
            $this->view->dadosReuniaoPlenariaAtual = $ConsultaReuniaoAberta;
            $this->view->usu_codigo = $auth->getIdentity()->usu_codigo;
            $ConsultaReuniaoAberta = $ConsultaReuniaoAberta->current()->toArray();
            $this->view->plenariaatual = $ConsultaReuniaoAberta['idNrReuniao'];
            $this->view->dadosReuniaoPlenariaAtual = $ConsultaReuniaoAberta;
            //---------------------------------------------------------------------------------------------------------------
            $votantes = new Votante();
            $exibirVotantes = $votantes->selecionarvotantes($ConsultaReuniaoAberta['idNrReuniao']);
            if (count($exibirVotantes) > 0) {
                foreach ($exibirVotantes as $votantes) {
                    $dadosVotante[] = $votantes->idAgente;
                }
                if (count($dadosVotante) > 0) {
                    if (in_array($idagente, $dadosVotante)) {
                        $this->view->votante = true;
                    } else {
                        $this->view->votante = false;
                    }
                }
            }
        }
    } // fecha metodo analisedecustosAction()


    /**
     * Metodo para emitir parecer
     * @access public
     * @param void
     * @return void
     */
    public function emitirparecerAction()
    {
        $idPronac = $this->_request->getParam("idpronac");
        $pa = new paChecarLimitesOrcamentario();
        $resultadoCheckList = $pa->exec($idPronac, 3);
        $i = 0;
        foreach ($resultadoCheckList as $resultado) {
            if ($resultado->Observacao == 'PENDENTE') {
                $i++;
            }
        }

        $this->view->qtdErrosCheckList = $i;
        $this->view->resultadoCheckList = $resultadoCheckList;

        $tblParecer = new Parecer();
        $tblPlanilhaAprovacao = new PlanilhaAprovacao();
        $auth = Zend_Auth::getInstance(); // pega a autenticacao
        $tblProjetos = new Projetos();
        $tblPreProjeto = new Proposta_Model_DbTable_PreProjeto();

        $ConsultaReuniaoAberta = ReuniaoDAO::buscarReuniaoAberta();
        $NumeroReuniao = $ConsultaReuniaoAberta['NrReuniao'];
        //CASO O COMPONENTE QUEIRA APENAS SALVAR O SEU PARECER - INICIO
        if (isset($_POST['usu_codigo'])) {
            $this->_helper->layout->disableLayout(); // desabilita o Zend_Layout

            $tipoAgente = $_POST['tipoplanilha'];
            $parecer = $_POST['parecer'];

            $buscarParecer = $tblParecer->buscar(array('IdPRONAC = ?' => $_POST['idpronac'], 'stAtivo = ?' => 1))->current();
            if (count($buscarParecer) > 0) {
                $buscarParecer = $buscarParecer->toArray();
                $dados = array(
                    'idPRONAC' => $_POST['idpronac'],
                    'AnoProjeto' => $buscarParecer['AnoProjeto'],
                    'idEnquadramento' => $buscarParecer['idEnquadramento'],
                    'Sequencial' => $buscarParecer['Sequencial'],
                    'TipoParecer' => $buscarParecer['TipoParecer'],
                    'ParecerFavoravel' => Seguranca::tratarVarAjaxUFT8($parecer),
                    'dtParecer' => date('Y-m-d H:i:s'),
                    'NumeroReuniao' => $NumeroReuniao,
                    'ResumoParecer' => utf8_decode($_POST['justificativa']),
                    'SugeridoUfir' => 0,
                    'SugeridoReal' => $_POST['valorreal'],
                    'SugeridoCusteioReal' => 0,
                    'SugeridoCapitalReal' => 0,
                    'Atendimento' => 'S',
                    'Logon' => $_POST['usu_codigo'],
                    'stAtivo' => 1,
                    'idTipoAgente' => $tipoAgente
                );
                $idparecer = isset($buscarParecer['IdParecer']) ? $buscarParecer['IdParecer'] : $buscarParecer['idParecer'];

                //se parecer ativo nao � o Componente, inativa os outros e grava o do Componente
                if (!$buscarParecer or $buscarParecer['idTipoAgente'] != $tipoAgente) {
                    try {
                        $dadosAtualizar = array('stAtivo' => 0);
                        $where = "idparecer = " . $idparecer;

                        $update = $tblParecer->alterar($dadosAtualizar, $where);
                        $inserir = $tblParecer->inserir($dados);
                        $this->_helper->json(array('error' => false));
                    } catch (Exception $e) {
                        $this->_helper->json(array('error' => true, 'Descricao' => $e->getMessage()));
                    }
                    $this->_helper->viewRenderer->setNoRender(TRUE);
                } else {
                    try {
                        $where = "idparecer = " . $idparecer;
                        $update = $tblParecer->alterar($dados, $where);
                        $this->_helper->json(array('error' => false));

                    } catch (Zend_Exception $e) {
                        $this->_helper->json(array('error' => true, 'Descricao' => $e->getMessage()));
                    }
                    $this->_helper->viewRenderer->setNoRender(TRUE);
                }
            } else {

                $this->_helper->json(array('error' => true, 'Descricao' => 'N&atilde;o foi encontrado parecer v&aacute;lido da an&aacute;lise t&eacute;cnica.'));
                $this->_helper->viewRenderer->setNoRender(TRUE);
            }
        }
        //CASO O COMPONENTE QUEIRA SALVAR O SEU PARECER - FIM

        //CASO O PROJETO VOLTE DA CNIC APROVADO PARA O COMPONENTE READEQUAR - INICIO
        if (isset($_POST['idpronac'])) {

            $idpronac = $_POST['idpronac'];
            $tblSituacao = new Situacao();
            $tblPauta = new Pauta();
            $buscarnrreuniaoprojeto = $tblPauta->dadosiniciaistermoaprovacao(array($idpronac))->current();
            $dados = array();
            //TRATANDO SITUACAO DO PROJETO QUANDO ESTE FOR DE READEQUACAO
            if ($this->bln_readequacao == "false") {
                $dados['Situacao'] = 'D03';
                $buscarsituacao = $tblSituacao->listasituacao(array('D03'))->current();
            } else {
                $dados['Situacao'] = 'D02';
                $buscarsituacao = $tblSituacao->listasituacao(array('D02'))->current();
            }
            $dados['DtSituacao'] = date('Y-m-d H:i:s');
            $dados['ProvidenciaTomada'] = 'PROJETO APROVADO NA CNIC N&ordm ' . $buscarnrreuniaoprojeto->NrReuniao . ' - ' . $buscarsituacao['Descricao'];
            $dados['Logon'] = $auth->getIdentity()->usu_codigo;
            $where = "IdPRONAC = " . $idpronac;
            $tblProjetos->alterar($dados, $where);
            //$tblProjetos->alterarSituacao($idpronac, NULL, $dados['Situacao'], $dados['ProvidenciaTomada']);
            parent::message("Projeto readequado com sucesso!", "areadetrabalho/index", "CONFIRM");
            $this->_helper->viewRenderer->setNoRender(TRUE);

        }
        //CASO O PROJETO VOLTE DA CNIC APROVADO PARA O COMPONENTE READEQUAR - FIM


        //FINALIZAR ANALISE - JUSTIFICATIVA DE PLENARIA - INICIO
        if (isset($_POST['justificativaenvioplenaria'])) {
            // recebe os dados via post
            $post = Zend_Registry::get('post');

            $parecerAtualizado = trim($post->pareceratualizado);
            //CONFIRMA SE O PARECER REALMENTE FOI EMITIDO E ESTA GRAVADO
            $tblParecer = new Parecer();
            $rsParecer = $tblParecer->buscar(array("IdPRONAC = ?" => $post->idPronac, "idTipoAgente = ?" => 6, "stAtivo = ?" => 1));

            if ($rsParecer->count() <= 0) {
                parent::message("Para finalizar a An&aacute;lise &eacute; necess&aacute;rio emitir um parecer. O parecer n&atilde;o foi gravado corretamente.", "realizaranaliseprojeto/emitirparecer/idpronac/" . $post->idPronac, "ERROR");
                $this->_helper->viewRenderer->setNoRender(TRUE);
            }//GARANTE A ATUALIZACAO O PARECER
            else {
                $idParecer = isset($rsParecer[0]->IdParecer) ? $rsParecer[0]->IdParecer : $rsParecer[0]->idParecer;
                $rsParecerAtualizar = $tblParecer->find($idParecer)->current();
                $rsParecerAtualizar->ResumoParecer = ($parecerAtualizado);
                $rsParecerAtualizar->save();
            }

            $tblSituacao = new Situacao();
            $tblProjetos = new Projetos();
            $tblPauta = new Pauta();
            $tblReuniao = new tbreuniao();

            $idPronac = $post->idPronac;

            //recupera tipo da planilha de acordo com o resultado da reuniao plenaria
            /*$buscarnrreuniaoprojeto = $tblPauta->dadosiniciaistermoaprovacao(array($idPronac) )->current();
                $rsReuniao = $tblReuniao->buscar(array('NrReuniao=?'=>$buscarnrreuniaoprojeto->NrReuniao))->current();
                $buscaReadAprovacadoCnic = $tblPauta->buscar(array('IdPRONAC = ?'=>$idPronac, 'stAnalise = ?'=>'AS', 'idNrReuniao = ?'=>$rsReuniao->idNrReuniao));
                if($buscaReadAprovacadoCnic->count() > 0){ $tpPlanilha = "SE"; } else{ $tpPlanilha = "CO"; }*/

            $tblPlanilhaAprovacao = new PlanilhaAprovacao();
            $rsPlanilhaAtual = $tblPlanilhaAprovacao->buscar(array('IdPRONAC = ?' => $idPronac), array('dtPlanilha DESC'))->current();
            $tpPlanilha = (!empty($rsPlanilhaAtual) && $rsPlanilhaAtual->tpPlanilha == 'SE') ? 'SE' : 'CO';

            //TRATANDO SITUACAO DO PROJETO QUANDO ESTE FOR DE READEQUACAO
            $codSituacao = ($this->bln_readequacao == "false") ? 'D03' : 'D02';
            $stEnvioPlenaria = isset($post->stEnvioPlenaria) ? 'S' : 'N';
            $justificativa = $post->justificativaenvioplenaria;
            $TipoAprovacao = $post->decisao;
            $situacao = $post->situacao == null ? $codSituacao : $post->situacao;
            $dtsituacao = date('Y-m-d H:i:s');
            $buscarsituacao = $tblSituacao->listasituacao(array($situacao))->current()->toArray();
            $providencia = $post->situacao == null ? 'PROJETO APROVADO NA CNIC N ' . $NumeroReuniao . ' - ' . $buscarsituacao['Descricao'] : 'PROJETO INDEFERIDO NA CNIC N ' . $NumeroReuniao . ' - ' . $buscarsituacao['Descricao'];
            $stEnvioPlenaria = $post->stEnvioPlenaria == null ? 'N' : 'S';

            /**** CODIGO DE READEQUACAO ****/
            //SE O PROJETO FOR DE READEQUACAO e a DECISAO FOR DE APROVACAO - INATIVA A ANTIGA PLANILHA 'CO' e ATIVA A 'CO' READEQUADA
            if ($this->bln_readequacao == "true") {

                //finaliza readequacao do projeto
                if (!empty($this->idPedidoAlteracao) && $this->idPedidoAlteracao > 0) {
                    //ReadequacaoProjetos::alterarPedido($this->idPedidoAlteracao, '2');
                    $tbPedidoAlteracao = new tbPedidoAlteracaoProjeto();
                    $rsPedidoAlteracao = $tbPedidoAlteracao->buscar(array('idPedidoAlteracao = ?' => $this->idPedidoAlteracao))->current();
                    $rsPedidoAlteracao->siVerificacao = 2;
                    $rsPedidoAlteracao->save();
                    //$dados = array('siVerificacao' => 2);
                    //$where = array('idPedidoAlteracao = ' => $this->idPedidoAlteracao);
                    //$rsPedidoAlteracao = $tbPedidoAlteracao->alterar($dados, $where);
                }

                //troca planilhas apenas se a decisao do componente for de aprovar a readequacao  //Se a planilha atual � SE significa que voltou da plenaria e nao entra na opcao de desativar a antiga e ativar a nova
                if ($post->decisao = 'AC' && $tpPlanilha != 'SE') {

                    try {
                        //ATIVA PLANILHA CO READEQUADA
                        $tblPlanilhaAprovacao = new PlanilhaAprovacao();
                        $rsPlanilha_Ativa = $tblPlanilhaAprovacao->buscar(array('idPronac = ?' => $idPronac, 'stAtivo = ?' => 'S', 'tpPlanilha=?' => 'CO')); //PLANILHA DA APROVACAO INICIAL

                        $arrBuscaPlanilha = array();
                        $arrBuscaPlanilha["idPronac = ? "] = $idPronac;
                        $arrBuscaPlanilha["tpPlanilha = ? "] = 'CO';
                        $arrBuscaPlanilha["stAtivo = ? "] = 'N';
                        $arrBuscaPlanilha["idPedidoAlteracao = (SELECT TOP 1 max(idPedidoAlteracao) from sac.dbo.tbPlanilhaAprovacao where IdPRONAC = '{$idPronac}')"] = '(?)';
                        $rsPlanilha_Inativa = $tblPlanilhaAprovacao->buscar($arrBuscaPlanilha); //PLANILHA DA READEQUACAO
                        //inativa Planilha Aprovacao Inicial
                        foreach ($rsPlanilha_Ativa as $planilhaI) {
                            $planilhaI->stAtivo = 'N';
                            $planilhaI->save();
                        }
                        //ativa Planilha Readequada
                        $planilha = null;
                        foreach ($rsPlanilha_Inativa as $planilhaR) {
                            $planilhaR->stAtivo = 'S';
                            $planilhaR->save();
                        }

                    }// fecha try
                    catch (Exception $e) {

                        parent::message("Erro ao ativar Planilha readequada. " . $e->getMessage(), "realizaranaliseprojeto/emitirparecer" . $query_string, "ERROR");
                    }
                }
            }
            /**** FIM CODIGO DE READEQUACAO ****/

            try {
                // busca a reuniao aberta
                $idReuniao = $ConsultaReuniaoAberta['idNrReuniao'];
                // verifica se ja esta na pauta
                $verificaPauta = RealizarAnaliseProjetoDAO::retornaRegistro($idPronac, $idReuniao);
                $objAcesso = new Acesso();
                if (count($verificaPauta) == 0) {
                    // cadastra o projeto na pauta
                    $dados = array(
                        'idNrReuniao' => $idReuniao,
                        'IdPRONAC' => $idPronac,
                        'dtEnvioPauta' => $objAcesso->getExpressionDate(),
                        'stEnvioPlenario' => $stEnvioPlenaria,
                        'tpPauta' => 1,
                        'stAnalise' => $TipoAprovacao,
                        'dsAnalise' => TratarString::escapeString($justificativa),
                        'stPlanoAnual' => $post->stPlanoAnual
                    );
                    $tblPauta->inserir($dados);
                    $dadosprojeto = array(
                        'Situacao' => $situacao,
                        'DtSituacao' => $dtsituacao,
                        'ProvidenciaTomada' => $providencia
                    );
                    $tblProjetos->alterar($dadosprojeto, 'IdPRONAC = ' . $idPronac);
                    parent::message("Projeto cadastrado na Pauta com sucesso!", "areadetrabalho/index", "CONFIRM");
                    $this->_helper->viewRenderer->setNoRender(TRUE);
                } else {
                    // altera o projeto na pauta
                    $dados = array(
                        'idNrReuniao' => $idReuniao,
                        'dtEnvioPauta' => $objAcesso->getExpressionDate(),
                        'stEnvioPlenario' => $stEnvioPlenaria,
                        'tpPauta' => 1,
                        'dsAnalise' => TratarString::escapeString($justificativa),
                        'stAnalise' => $TipoAprovacao,
                        'stPlanoAnual' => $post->stPlanoAnual
                    );
//                            $alterar = $tblPauta->alterar($dados, 'IdPRONAC = '.$idPronac.' and idNrReuniao = '.$idReuniao);
                    $dadosprojeto = array(
                        'Situacao' => $situacao,
                        'DtSituacao' => $dtsituacao,
                        'ProvidenciaTomada' => $providencia
                    );
//                            $tblProjetos->alterar($dadosprojeto, 'IdPRONAC = '.$idPronac);

                    $tbRecurso = new tbRecurso();
                    $dadosRecursoAtual = $tbRecurso->buscar(array('IdPRONAC = ?' => $idPronac, 'stAtendimento = ?' => 'N', 'tpSolicitacao =?' => 'EN'));
                    if (count($dadosRecursoAtual) > 0) {
                        $auth = Zend_Auth::getInstance(); // pega a autentica��o
                        $idUsuario = $auth->getIdentity()->usu_codigo;
                        //ATUALIZA��O DA TABELA RECURSO//
                        $dadosNovos = array(
                            'dtAvaliacao' => $objAcesso->getExpressionDate(),
                            'dsAvaliacao' => 'Recurso deferido conforme solicita��o do Proponente.',
                            'idAgenteAvaliador' => $idUsuario
                        );
                        $tbRecurso->update($dadosNovos, "idRecurso=" . $dadosRecursoAtual[0]->idRecurso);

                        //ATUALIZA��O DA TABELA Enquadramento//
                        $Enquadramento = new Admissibilidade_Model_Enquadramento();
                        $dadosEnquadramentoAtual = $Enquadramento->buscarDados($idPronac, null);
                        if (count($dadosRecursoAtual) > 0) {
                            $tpEnquadramento = ($dadosEnquadramentoAtual[0]->Enquadramento == 1) ? 2 : 1;
                            $dadosNovosEnquadramento = array(
                                'Enquadramento' => $tpEnquadramento,
                                'dtEnquadramento' => $objAcesso->getExpressionDate(),
                                'Observacao' => 'Altera&ccedil;&atilde;o de Enquadramento conforme deferimento de recurso.',
                                'Logon' => $idUsuario
                            );
                            $Enquadramento->update($dadosNovosEnquadramento, "IdEnquadramento=" . $dadosEnquadramentoAtual[0]->IdEnquadramento);
                        }
                    }
                    parent::message("Projeto j&aacute; est&aacute; em Pauta, sendo alterado com sucesso!", "areadetrabalho/index", "CONFIRM");
                    $this->_helper->viewRenderer->setNoRender(TRUE);
                }

            } // fecha try
            catch (Exception $e) {

                parent::message("Erro ao incluir projeto na Pauta. " . $e->getMessage(), "realizaranaliseprojeto/emitirparecer" . $query_string, "ERROR");
            }
        } // fecha if
        // =================================================================
        // ========= CARREGANDO TELA DE EMISSAO DE PARECER =================
        else {
            // recebe os dados via get
            $idpronac = $this->_request->getParam("idpronac");
            $this->view->idpronac = $idpronac;

            try {
                if (empty($idpronac)) {
                    //throw new Exception("Por favor, clique no Pronac Aguardando An&aacute;lise!");
                    parent::message("Erro ao realizar opera��o.", "realizaranaliseprojeto/emitirparecer", "ERROR");
                } else {
                    $idpronac = $this->_request->getParam("idpronac");

                    $projeto = new Projetos();
                    $planilhaproposta = new Proposta_Model_DbTable_TbPlanilhaProposta();
                    $planilhaprojeto = new PlanilhaProjeto();
                    $planoDistribuicao = new Proposta_Model_DbTable_PlanoDistribuicaoProduto();
                    $analiseaprovacao = new AnaliseAprovacao();
                    $planilhaAprovacao = new PlanilhaAprovacao();
                    $tbPreProjeto = new Proposta_Model_DbTable_PreProjeto();
                    //antigo modo
                    //$tblPauta = new Pauta();
                    //$buscaReadAprovacadoCnic = $tblPauta->buscar(array('IdPRONAC = ?'=>$idpronac, 'stAnalise = ?'=>"AS"));
                    //novo modo
                    $rsPlanilhaAtual = $planilhaAprovacao->buscar(array('IdPRONAC = ?' => $idpronac), array('dtPlanilha DESC'))->current();
                    $tipoplanilha = (!empty($rsPlanilhaAtual) && $rsPlanilhaAtual->tpPlanilha == 'SE') ? 'SE' : 'CO';

                    if (!empty($rsPlanilhaAtual) && $rsPlanilhaAtual->tpPlanilha == 'SE') {
                        $tpPlanilha = "SE";
                        $tpAnalise = "SE";
                        $tpAgente = '10';
                    } else {
                        $tpPlanilha = "CO";
                        $tpAnalise = "CO";
                        $tpAgente = '6';
                    }

                    $projetoAtual = $projeto->buscar(array('IdPRONAC = ?' => $idpronac))->current()->toArray();
                    $idprojeto = $projetoAtual['idProjeto'];

                    $rsPreprojeto = $tbPreProjeto->buscar(array('idPreProjeto=?' => $idprojeto))->current();
                    if (!empty($rsPreprojeto)) {
                        $stPlanoAnual = $rsPreprojeto->stPlanoAnual;
                    } else {
                        $stPlanoAnual = '0';
                    }
                    $this->view->tipoplanilha = $tpPlanilha;
                    $this->view->tipoagente = $tpAgente;
                    $this->view->stPlanoAnual = $stPlanoAnual;

                    /**** CODIGO DE READEQUACAO ****/

                    $arrWhereSomaPlanilha = array();
                    $arrWhereSomaPlanilha['idPronac = ?'] = $idpronac;

                    //TRATANDO SOMA DE PROJETO QUANDO ESTE FOR DE READEQUACAO
                    if ($this->bln_readequacao == "false") {
                        $fonteincentivo = $planilhaproposta->somarPlanilhaProposta($idprojeto, 109);
                        $outrasfontes = $planilhaproposta->somarPlanilhaProposta($idprojeto, false, 109);
                    } else {
                        $arrWhereFontesIncentivo = $arrWhereSomaPlanilha;
                        $arrWhereFontesIncentivo['idPlanilhaItem <> ? '] = '206'; //elaboracao e agenciamento
                        $arrWhereFontesIncentivo['tpPlanilha = ? '] = 'SR';
                        $arrWhereFontesIncentivo['stAtivo = ? '] = 'N';
                        $arrWhereFontesIncentivo['NrFonteRecurso = ? '] = '109';
                        $arrWhereFontesIncentivo["idPedidoAlteracao = (?)"] = new Zend_Db_Expr("(SELECT TOP 1 max(idPedidoAlteracao) from sac.dbo.tbPlanilhaAprovacao where IdPRONAC = '{$idpronac}')");
                        $arrWhereFontesIncentivo["tpAcao <> ('E') OR tpAcao IS NULL "] = '(?)';
                        $fonteincentivo = $planilhaAprovacao->somarItensPlanilhaAprovacao($arrWhereFontesIncentivo);

                        $arrWhereOutrasFontes = $arrWhereSomaPlanilha;
                        $arrWhereOutrasFontes['idPlanilhaItem <> ? '] = '206'; //elaboracao e agenciamento
                        $arrWhereOutrasFontes['tpPlanilha = ? '] = 'SR';
                        $arrWhereOutrasFontes['stAtivo = ? '] = 'N';
                        $arrWhereOutrasFontes['NrFonteRecurso <> ? '] = '109';
                        $arrWhereOutrasFontes["idPedidoAlteracao = (?)"] = new Zend_Db_Expr("(SELECT TOP 1 max(idPedidoAlteracao) from sac.dbo.tbPlanilhaAprovacao where IdPRONAC = '{$idpronac}')");
                        $arrWhereOutrasFontes["tpAcao <> ('E') OR tpAcao IS NULL "] = '(?)';
                        $outrasfontes = $planilhaAprovacao->somarItensPlanilhaAprovacao($arrWhereOutrasFontes);
                    }
                    $this->view->fontesincentivo = $fonteincentivo['soma'];
                    $this->view->outrasfontes = $outrasfontes['soma'];
                    $this->view->valorproposta = $fonteincentivo['soma'] + $outrasfontes['soma'];

                    $arrWhereSomaPlanilha = array();
                    $arrWhereSomaPlanilha['idPronac = ?'] = $idpronac;
                    $arrWhereSomaPlanilha['idPlanilhaItem <> ? '] = '206'; //elaboracao e agenciamento
                    $arrWhereSomaPlanilha['tpPlanilha = ? '] = $tpPlanilha;
                    $arrWhereSomaPlanilha['NrFonteRecurso = ? '] = '109';
                    if ($this->bln_readequacao == "true") {
                        $arrWhereSomaPlanilha["idPedidoAlteracao = (SELECT TOP 1 max(idPedidoAlteracao) from sac.dbo.tbPlanilhaAprovacao where IdPRONAC = '{$idpronac}')"] = '(?)';
                        $arrWhereSomaPlanilha["tpAcao <> ('E') OR tpAcao IS NULL "] = '(?)';
                        $arrWhereSomaPlanilha['stAtivo = ? '] = 'N';
                    } else {
                        $arrWhereSomaPlanilha['stAtivo = ? '] = 'S';
                    }

                    //VALOR TOTAL DO PROJETO - V1
                    //antiga soma
                    //$valorProjeto = $planilhaAprovacao->somarPlanilhaAprovacao($idpronac,206, $tpPlanilha, $arrWhereReadequacao);
                    //nova soma
                    $valorProjeto = $planilhaAprovacao->somarItensPlanilhaAprovacao($arrWhereSomaPlanilha);

                    /**** FIM CODIGO DE READEQUACAO ****/

                    $this->view->totalsugerido = $valorProjeto['soma'] ? $valorProjeto['soma'] : 0; //valor total do projeto (Planilha Aprovacao)

                    //CALCULO DOS 20% - ETAPA DIVULGACAO
                    //soma para calculo dos 20% etapada de Divulgacao
                    $arrWhereEtapa = array();
                    $arrWhereEtapa['pa.idPronac = ?'] = $idpronac;
                    $arrWhereEtapa['pa.idPlanilhaItem <> ? '] = '206'; //elaboracao e agenciamento
                    $arrWhereEtapa['pa.tpPlanilha = ? '] = $tpPlanilha;
                    $arrWhereEtapa['pa.NrFonteRecurso = ? '] = '109';
                    $arrWhereEtapa['pa.idEtapa = ?'] = '3';
                    if ($this->bln_readequacao == "true") {
                        $arrWhereEtapa["pa.idPedidoAlteracao = (SELECT TOP 1 max(idPedidoAlteracao) from sac.dbo.tbPlanilhaAprovacao where IdPRONAC = '{$idpronac}')"] = '(?)';
                        $arrWhereEtapa["pa.tpAcao <> ('E') OR pa.tpAcao IS NULL "] = '(?)';
                        $arrWhereEtapa['pa.stAtivo = ? '] = 'N';
                    } else {
                        $arrWhereEtapa['pa.stAtivo = ? '] = 'S';
                    }
                    $arrWhereEtapa['aa.tpAnalise = ?'] = $tpPlanilha;
                    $arrWhereEtapa['aa.stAvaliacao = ?'] = 1; // 1 = parecer favoravel, 0 = parecer nao favoravel
                    //$this->view->V2 = $valorProjetoDivulgacao['soma'];
                    $valorProjetoDivulgacao = $planilhaAprovacao->somarItensPlanilhaAprovacaoProdutosFavoraveis($arrWhereEtapa);

                    //CALCULO DOS 15% - CUSTOS ADMINISTRATIVOS
                    $arrWhereCustoAdm = array();
                    $arrWhereCustoAdm['idPronac = ?'] = $idpronac;
                    $arrWhereCustoAdm['idProduto = ?'] = 0;
                    $arrWhereCustoAdm['idEtapa = ?'] = 4; //custos administrativos
                    $arrWhereCustoAdm['idPlanilhaItem NOT in (?)'] = array(5249, 206, 1238);//Remuneracao de captacao de recursos
                    $arrWhereCustoAdm['tpPlanilha = ? '] = $tpPlanilha;
                    $arrWhereCustoAdm['NrFonteRecurso = ? '] = '109';
                    if ($this->bln_readequacao == "true") {
                        $arrWhereCustoAdm["idPedidoAlteracao = (SELECT TOP 1 max(idPedidoAlteracao) from sac.dbo.tbPlanilhaAprovacao where IdPRONAC = '{$idpronac}')"] = '(?)';
                        $arrWhereCustoAdm["tpAcao <> ('E') OR tpAcao IS NULL "] = '(?)';
                        $arrWhereCustoAdm['stAtivo = ? '] = 'N';
                    } else {
                        $arrWhereCustoAdm['stAtivo = ? '] = 'S';
                    }
                    //$this->view->V2.1 = $valoracustosadministrativos['soma'];
                    $valoracustosadministrativos = $planilhaAprovacao->somarItensPlanilhaAprovacao($arrWhereCustoAdm);

                    //CALCULO DOS 10% - REMUNERACAO PARA CAPTACAO DE RECURSO
                    $arrWhereItemCaptRecurso = array();
                    $arrWhereItemCaptRecurso['idPronac = ?'] = $idpronac;
                    $arrWhereItemCaptRecurso['idPlanilhaItem = ?'] = '5249'; //Item de Remuneracao de captacao de recursos
                    $arrWhereItemCaptRecurso['tpPlanilha = ? '] = $tpPlanilha;
                    $arrWhereItemCaptRecurso['NrFonteRecurso = ? '] = '109';
                    if ($this->bln_readequacao == "true") {
                        $arrWhereItemCaptRecurso["idPedidoAlteracao = (SELECT TOP 1 max(idPedidoAlteracao) from sac.dbo.tbPlanilhaAprovacao where IdPRONAC = '{$idpronac}')"] = '(?)';
                        $arrWhereItemCaptRecurso["tpAcao <> ('E') OR tpAcao IS NULL "] = '(?)';
                        $arrWhereItemCaptRecurso['stAtivo = ? '] = 'N';
                    } else {
                        $arrWhereItemCaptRecurso['stAtivo = ? '] = 'S';
                    }
                    //$this->view->V2.2 = $valorItemCaptacaoRecurso['soma'];
                    $valorItemCaptacaoRecurso = $planilhaAprovacao->somarItensPlanilhaAprovacao($arrWhereItemCaptRecurso);

                    //Calcula os 20% do valor total do projeto V3
                    $porcentValorProjeto = ($valorProjeto['soma'] * 0.20);

                    //Calcula os 15% do valor total do projeto V3.1
                    $quinzecentoprojeto = ($valorProjeto['soma'] * 0.15);

                    //Calcula os 10% do valor total do projeto V3.2
                    $dezPercentValorProjeto = ($valorProjeto['soma'] * 0.10);

                    //Calculo do 20% -> V4
                    //Subtrai os custos da etapa divulgacao pelos 20% do projeto (V2 - V3)
                    $verificacaonegativo20porcento = $valorProjetoDivulgacao->soma - $porcentValorProjeto;

                    //Calculo do 15% -> V4.1
                    //Subtrai os custos administrativos pelos 15% do projeto (V2.1 - V3.1)
                    $verificacaonegativo = $valoracustosadministrativos['soma'] - $quinzecentoprojeto;

                    //Calculo do 10% -> V4.2
                    //Subtrai o item de captacao de recurso pelos 10% do projeto (V2.2 - V3.2)
                    $verificacaonegativo10porcento = $valorItemCaptacaoRecurso['soma'] - $dezPercentValorProjeto;

                    //if V4 e V4.1 maior que zero soma os dois V4
                    if ($verificacaonegativo20porcento > 0 && $verificacaonegativo > 0 && $verificacaonegativo10porcento > 0) {

                        //V1 - (V4 + V4.1 + V4.2) = V5
                        /*V5*/
                        $novoValorProjeto = /*V1*/
                            $valorProjeto['soma'] - (/*V4*/
                                $verificacaonegativo20porcento + /*V4.1*/
                                $verificacaonegativo + /*V4.2*/
                                $verificacaonegativo10porcento);

                        /*V6*/
                        $vinteporcentovalorretirar = /*V5*/
                            $novoValorProjeto * 0.20;
                        //V2 - V6
                        $valorretirarplanilhaEtapaDivulgacao = $valorProjetoDivulgacao->soma - $vinteporcentovalorretirar; //(correcao V2 - V6)
                        //$this->view->verifica15porcento = $valorretirarplanilha;
                        $this->view->valorReadequar20porcento = $valorretirarplanilhaEtapaDivulgacao;
                        $this->view->totaldivulgacao = "true";

                        /*V6.1*/
                        $quinzecentovalorretirar = /*V5*/
                            $novoValorProjeto * 0.15;
                        //V2 - V6
                        $valorretirarplanilha = $valoracustosadministrativos['soma'] - $quinzecentovalorretirar; //(correcao V2 - V6)
                        $this->view->verifica15porcento = $valorretirarplanilha;

                        /*V6.2*/
                        $dezcentovalorretirar = /*V5*/
                            $novoValorProjeto * 0.10;
                        //V2 - V6
                        $valorretirarplanilhaItemCaptacaoRecurso = $valorItemCaptacaoRecurso['soma'] - $dezcentovalorretirar; //(correcao V2 - V6)
                        $this->view->valorReadequar10porcento = $valorretirarplanilhaItemCaptacaoRecurso;
                        $this->view->totalcaptacaorecurso = "true";

                    } elseif ($verificacaonegativo20porcento > 0 || $verificacaonegativo > 0 || $verificacaonegativo10porcento > 0) {

                        //Calculo dos 20%
                        if ($verificacaonegativo20porcento <= 0) {
                            $this->view->totaldivulgacao = "false";
                            $this->view->valorReadequar20porcento = 0;
                        } else {
                            //V1 - V4 = V5
                            /*V5*/
                            $valorretirar20porcento = /*V1*/
                                $valorProjeto['soma'] - /*V4*/
                                $verificacaonegativo20porcento;
                            /*V6*/
                            $vinteporcentovalorretirar = /*V5*/
                                $valorretirar20porcento * 0.20;
                            //V2 - V6
                            $valorretirarplanilhaEtapaDivulgacao = $valorProjetoDivulgacao->soma - $vinteporcentovalorretirar; //(correcao V2 - V6)
                            $this->view->valorReadequar20porcento = $valorretirarplanilhaEtapaDivulgacao;
                            $this->view->totaldivulgacao = "true";
                        }

                        //Calculo dos 10%
                        if ($verificacaonegativo10porcento <= 0) {
                            $this->view->totalcaptacaorecurso = "false";
                            $this->view->valorReadequar10porcento = 0;
                        } else {
                            //V1 - V4 = V5
                            /*V5*/
                            $valorretirar10porcento = /*V1*/
                                $valorProjeto['soma'] - /*V4*/
                                $verificacaonegativo10porcento;
                            /*V6*/
                            $dezcentovalorretirar = /*V5*/
                                $valorretirar10porcento * 0.10;
                            //V2 - V6
                            $valorretirarplanilhaItemCaptacaoRecurso = $valorItemCaptacaoRecurso['soma'] - $dezcentovalorretirar; //(correcao V2 - V6)
                            $this->view->valorReadequar10porcento = $valorretirarplanilhaItemCaptacaoRecurso;
                            $this->view->totalcaptacaorecurso = "true";
                        }

                        //Calculo dos 10% (complemento)
                        $tetoCemMil = (int)'100000.00';
                        if ($valorItemCaptacaoRecurso['soma'] > $tetoCemMil) { //verfica se o valor do item de captacao de recurso � maior que R$100.000,00
                            $this->view->totalcaptacaorecurso = "true";
                            $this->view->valorReadequar10porcento = $valorItemCaptacaoRecurso['soma'] - $tetoCemMil;
                        }

                        //Calculo dos 15%
                        if ($valorProjeto['soma'] > 0 and $valoracustosadministrativos['soma'] < $valorProjeto['soma']) {
                            if ($verificacaonegativo <= 0) {
                                $this->view->verifica15porcento = 0;
                            } else {
                                //V1 - V4 = V5
                                /*V5*/
                                $valorretirar = /*V1*/
                                    $valorProjeto['soma'] - /*V4*/
                                    $verificacaonegativo;
                                /*V6*/
                                $quinzecentovalorretirar = /*V5*/
                                    $valorretirar * 0.15;
                                //V2 - V6
                                $valorretirarplanilha = $valoracustosadministrativos['soma'] - $quinzecentovalorretirar; //(correcao V2 - V6)
                                $this->view->verifica15porcento = $valorretirarplanilha;
                            }
                        } else {
                            $this->view->verifica15porcento = $valoracustosadministrativos['soma'];
                        }

                    } else {
                        //Calculo dos 20%
                        $this->view->totaldivulgacao = "false";
                        $this->view->valorReadequar20porcento = 0;

                        //Calculo dos 10% (complemento)
                        $tetoCemMil = (int)'100000.00';
                        if ($valorItemCaptacaoRecurso['soma'] > $tetoCemMil) { //verfica se o valor do item de captacao de recurso � maior que R$100.000,00
                            $this->view->totalcaptacaorecurso = "true";
                            $this->view->valorReadequar10porcento = $valorItemCaptacaoRecurso['soma'] - $tetoCemMil;
                        } else {
                            $this->view->totalcaptacaorecurso = "false";
                            $this->view->valorReadequar10porcento = 0;
                        }
                        //Calculo dos 15%
                        $this->view->verifica15porcento = 0;
                    }
                    //FIM - DOS CALCULO DOS 20% e 15%

                    $buscarPlano = $planoDistribuicao->buscar(array('idProjeto = ?' => $projetoAtual['idProjeto'], 'stPrincipal= ?' => 1))->current()->toArray();
                    $buscarAnaliseAp = $analiseaprovacao->buscar(array('IdPRONAC = ?' => $idpronac, 'idProduto = ?' => $buscarPlano['idProduto'], 'tpAnalise = ?' => $tpAnalise));

                    //CALCULO DOS 50%
                    if ($buscarAnaliseAp->count() > 0) {
                        $buscarAnaliseAp = $buscarAnaliseAp->current()->toArray();
                        //$aprovacao['planilhaprovacao'] = 0;
                        //$aprovacao['planilhaprojeto'] = 0;
                        if ($buscarAnaliseAp['stAvaliacao'] == 1) {
                            //CODIGO ANTIGO
                            /*$buscaraprovacao  = $planilhaAprovacao->CompararPlanilha($idpronac, $tpPlanilha);
                                        foreach($buscaraprovacao as $resu){
                                            $aprovacao['planilhaprovacao'] += $resu->planilhaaprovacao;
                                            $aprovacao['planilhaprojeto'] += $resu->planilhaprojeto;
                                        }
                                        $aprovacao['planilhaprovacao'] = $aprovacao['planilhaprovacao'] != 0 ? $aprovacao['planilhaprovacao'] : 1;
                                        $valoraprovacao = $aprovacao['planilhaprojeto'] * 0.5; */
                            $valoraprovacao = $this->view->fontesincentivo * 0.5;
                            if ($valoraprovacao >= $this->view->totalsugerido) {
                                $this->view->parecerfavoravel = 'NAO';
                                $this->view->nrparecerfavoravel = 1;
                            } else {
                                $this->view->parecerfavoravel = 'SIM';
                                $this->view->nrparecerfavoravel = 2;
                            }
                        } else {
                            $this->view->parecerfavoravel = 'NAO';
                            $this->view->nrparecerfavoravel = 1;
                        }
                    } else {
                        $this->view->parecerfavoravel = 'NAO';
                        $this->view->nrparecerfavoravel = 1;
                    }

                    //$this->view->V1=$valorProjeto['soma'];
                    $produtos = RealizarAnaliseProjetoDAO::analiseDeConteudo($idpronac, $tpPlanilha);
                    $this->view->ResultRealizarAnaliseProjeto = RealizarAnaliseProjetoDAO::analiseparecerConsolidado($idpronac);
                    $verificaEnquadramento = RealizarAnaliseProjetoDAO::verificaEnquadramento($idpronac, $tpPlanilha);
                    if (isset($verificaEnquadramento[0]->stArtigo18) && $verificaEnquadramento[0]->stArtigo18 == true) {
                        $this->view->enquadramento = 'Artigo 18';
                    } else if (isset($verificaEnquadramento[0]->stArtigo26) && $verificaEnquadramento[0]->stArtigo26 == true) {
                        $this->view->enquadramento = 'Artigo 26';
                    } else {
                        $this->view->enquadramento = 'NAO ENQUADRADO';
                    }
                    $this->view->ResultProduto = $produtos;
                    $this->view->ResultValoresAnaliseProjeto = $produtos;
                    $indeferidos = RealizarAnaliseProjetoDAO::retornaIndeferimento();
                    $this->view->indeferidos = $indeferidos;
                    $this->view->idpronac = $idpronac;

                    /**** CODIGO DE READEQUACAO ****/
                    //antiga busca
                    //$buscarjustificativa = $tblParecer->buscarParecer($tpAgente,$idpronac);
                    //nova busca
                    $arrBuscaParecer = array();
                    $arrBuscaParecer['idPronac = ?'] = $idpronac;
                    $arrBuscaParecer['idTipoAgente = ?'] = $tpAgente;
                    $arrBuscaParecer['TipoParecer = ?'] = ($this->bln_readequacao == "true") ? '2' : '1';
                    $buscarjustificativa = $tblParecer->buscar($arrBuscaParecer);
                    /**** FIM - CODIGO DE READEQUACAO ****/

                    if ($buscarjustificativa->count() > 0) {
                        $buscarjustificativa = $buscarjustificativa->current()->toArray();
                        $this->view->valorJustificativa = $buscarjustificativa['ResumoParecer'];
                    } else {
                        $this->view->valorJustificativa = null;
                    }

                    $auth = Zend_Auth::getInstance(); // pega a autenticao
                    $Usuario = new Autenticacao_Model_Usuario(); // objeto usuario
                    $idagente = $Usuario->getIdUsuario($auth->getIdentity()->usu_codigo);
                    $idagente = $idagente['idAgente'];
                    //-------------------------------------------------------------------------------------------------------------
                    $reuniao = new Reuniao();
                    //---------------------------------------------------------------------------------------------------------------
                    $ConsultaReuniaoAberta = $reuniao->buscar(array("stEstado = ?" => 0));
                    if ($ConsultaReuniaoAberta->count() > 0) {
                        $this->view->dadosReuniaoPlenariaAtual = $ConsultaReuniaoAberta;
                        $this->view->usu_codigo = $auth->getIdentity()->usu_codigo;
                        $ConsultaReuniaoAberta = $ConsultaReuniaoAberta->current()->toArray();
                        $this->view->plenariaatual = $ConsultaReuniaoAberta['idNrReuniao'];
                        $this->view->dadosReuniaoPlenariaAtual = $ConsultaReuniaoAberta;
                        //---------------------------------------------------------------------------------------------------------------
                        $votantes = new Votante();
                        $exibirVotantes = $votantes->selecionarvotantes($ConsultaReuniaoAberta['idNrReuniao']);
                        if (count($exibirVotantes) > 0) {
                            foreach ($exibirVotantes as $votantes) {
                                $dadosVotante[] = $votantes->idAgente;
                            }
                            if (count($dadosVotante) > 0) {
                                if (in_array($idagente, $dadosVotante)) {
                                    $this->view->votante = true;
                                } else {
                                    $this->view->votante = false;
                                }
                            }
                        }
                    } else {
                        parent::message("N&atilde;o existe CNIC aberta no momento. Favor aguardar!", "principal/index", "ERROR");
                    }
                } // fecha else
            } // fecha try
            catch (Exception $e) {
                die($e->getMessage());
            }
        } // fecha else

    } // fecha metodo emitirparecerAction()


    public function gerarpdfAction()
    {
        $this->_helper->layout->disableLayout();
    }

    /**
     * Metodo com a Analise de Cortes Sugeridos - Projetos em Readequacao
     * @access public
     * @param void
     * @return void
     */
    public function analisedecontareadequacaoAction()
    {
        $planilhaaprovacao = new PlanilhaAprovacao();
        $tblPauta = new Pauta();
        $tblPlanilhaProposta = new Proposta_Model_DbTable_TbPlanilhaProposta();
        $projeto = new Projetos();
        // caso o formulario seja enviado via post
        // atualiza a planilha
        if ($this->getRequest()->isPost()) {
            // recebe os dados via post
            $post = Zend_Registry::get('post');
            $idPlanilha = $post->idPlanilha;
            $idpronac = $post->idpronac;

            $unidade = $post->unidade;
            $qtdItem = $post->qtd;
            $ocorrencia = $post->ocorrencia;
            $vlunitario = Mascara::delMaskMoeda($post->vlunitario);
            $dias = $post->dias;
            $justificativa = $post->justificativa;

            $rsPlanilhaAtual = $planilhaaprovacao->buscar(array('IdPRONAC = ?' => $idpronac), array('dtPlanilha DESC'))->current();
            $tpPlanilha = (!empty($rsPlanilhaAtual) && $rsPlanilhaAtual->tpPlanilha == 'SE') ? 'SE' : 'CO';

            $auth = Zend_Auth::getInstance(); // pega a autenticacao
            $idagente = GerenciarPautaReuniaoDAO::consultaAgenteUsuario($auth->getIdentity()->usu_codigo);
            $idagente = $idagente['idAgente'];
            $objAcesso = new Acesso();
            $dados = array(
                'dtPlanilha' => $objAcesso->getExpressionDate(),
                'idUnidade' => $unidade,
                'qtItem' => $qtdItem,
                'nrOcorrencia' => $ocorrencia,
                'vlUnitario' => $vlunitario,
                'qtDias' => $dias,
                'dsJustificativa' => $justificativa,
                'idAgente' => $idagente,
                'stAtivo' => 'N');
            $where = 'idPlanilhaAprovacao = ' . $idPlanilha . "and TpPlanilha = '" . $tpPlanilha . "'";
            $alterarPlanilha = $planilhaaprovacao->alterar($dados, $where);
            if ($alterarPlanilha) {
                parent::message("Registro inserido com sucesso!", "realizaranaliseprojeto/analisedecontareadequacao/idpronac/" . $idpronac, "CONFIRM");
            } else {
                throw new Exception("Erro ao efetuar altera��o!");
            }
        } // fecha if
        else {
            // recebe os dados via get
            $idpronac = $this->_request->getParam("idpronac");
            $buscarprojeto = $projeto->buscar(array('IdPRONAC = ?' => $idpronac))->current()->toArray();

            $rsPlanilhaAtual = $planilhaaprovacao->buscar(array('IdPRONAC = ?' => $idpronac), array('dtPlanilha DESC'))->current();
            $tpplanilha = (!empty($rsPlanilhaAtual) && $rsPlanilhaAtual->tpPlanilha == 'SE') ? 'SE' : 'CO';

            //$buscarAnaliseConta = $planilhaaprovacao->buscarAnaliseConta($idpronac,$tpplanilha); //codigo antigo
            /********************************************************************************/
            $arrBuscaPlanilha = array();
            $arrBuscaPlanilha["pap.stAtivo = ? "] = 'N';
            $arrBuscaPlanilha["pap.idPedidoAlteracao = (SELECT TOP 1 max(idPedidoAlteracao) from sac.dbo.tbPlanilhaAprovacao where IdPRONAC = '{$idpronac}')"] = '(?)';

            $buscarplanilhaCO = $planilhaaprovacao->buscarAnaliseContaPlanilhaAprovacao($idpronac, $tpplanilha, $arrBuscaPlanilha);
            $buscarAnaliseConta = array();
            $cont = 0;
            foreach ($buscarplanilhaCO as $resuplanilha) {
                $buscarAnaliseConta[$cont]['qtdRelator'] = $resuplanilha->qtItem;
                $buscarAnaliseConta[$cont]['nrFonteRecurso'] = $resuplanilha->nrFonteRecurso;
                $buscarAnaliseConta[$cont]['diasRelator'] = $resuplanilha->qtDias;
                $buscarAnaliseConta[$cont]['ocorrenciaRelator'] = $resuplanilha->nrOcorrencia;
                $buscarAnaliseConta[$cont]['vlunitarioRelator'] = $resuplanilha->vlUnitario;
                $buscarAnaliseConta[$cont]['idPlanilhaAprovacao'] = $resuplanilha->idPlanilhaAprovacao;
                $buscarAnaliseConta[$cont]['idProduto'] = $resuplanilha->idProduto;
                $buscarAnaliseConta[$cont]['idUnidade'] = $resuplanilha->idUnidade;
                $buscarAnaliseConta[$cont]['idEtapa'] = $resuplanilha->idEtapa;
                $buscarAnaliseConta[$cont]['JSComponente'] = $resuplanilha->dsJustificativa;

                $buscarAnaliseConta[$cont]['Unidade'] = $resuplanilha->Unidade;
                $buscarAnaliseConta[$cont]['Item'] = $resuplanilha->Item;
                $buscarAnaliseConta[$cont]['Etapa'] = $resuplanilha->Etapa;
                $buscarAnaliseConta[$cont]['produto'] = $resuplanilha->produto;
                $cont++;
            }

            /******** Planilha aprovacao SR (Proponente - solicitada) ****************/
            $arrBuscaPlanilha = array();
            $arrBuscaPlanilha["pap.stAtivo = ? "] = 'N';
            $arrBuscaPlanilha["pap.idPedidoAlteracao = (SELECT TOP 1 max(idPedidoAlteracao) from sac.dbo.tbPlanilhaAprovacao where IdPRONAC = '{$idpronac}')"] = '(?)';

            $resuplanilha = null;
            $cont = 0;
            $buscarplanilhaSR = $planilhaaprovacao->buscarAnaliseContaPlanilhaAprovacao($idpronac, 'SR', $arrBuscaPlanilha);
            foreach ($buscarplanilhaSR as $resuplanilha) {
                $buscarAnaliseConta[$cont]['qtdSolicitado'] = $resuplanilha->qtItem;
                $buscarAnaliseConta[$cont]['ocoSolicitado'] = $resuplanilha->nrOcorrencia;
                $buscarAnaliseConta[$cont]['vlSolicitado'] = $resuplanilha->vlUnitario;
                $buscarAnaliseConta[$cont]['diasSolicitado'] = $resuplanilha->qtDias;
                $buscarAnaliseConta[$cont]['UnidadeProposta'] = $resuplanilha->Unidade;
                $cont++;
            }

            /******** Planilha aprovacao PA (Parecerista) ****************/
            $arrBuscaPlanilha = array();
            $arrBuscaPlanilha["pap.stAtivo = ? "] = 'N';
            $arrBuscaPlanilha["pap.idPedidoAlteracao = (SELECT TOP 1 max(idPedidoAlteracao) from sac.dbo.tbPlanilhaAprovacao where IdPRONAC = '{$idpronac}')"] = '(?)';

            $resuplanilha = null;
            $cont = 0;
            $buscarplanilhaPA = $planilhaaprovacao->buscarAnaliseContaPlanilhaAprovacao($idpronac, 'PA', $arrBuscaPlanilha);
            foreach ($buscarplanilhaPA as $resuplanilha) {
                $buscarAnaliseConta[$cont]['qtdParecer'] = $resuplanilha->qtItem;
                $buscarAnaliseConta[$cont]['ocoParecer'] = $resuplanilha->nrOcorrencia;
                $buscarAnaliseConta[$cont]['vlParecer'] = $resuplanilha->vlUnitario;
                $buscarAnaliseConta[$cont]['JSParecerista'] = $resuplanilha->dsJustificativa;
                $buscarAnaliseConta[$cont]['diasParecerista'] = $resuplanilha->qtDias;
                $buscarAnaliseConta[$cont]['UnidadeProjeto'] = $resuplanilha->Unidade;
                $cont++;
            }

            /********************************************************************************/

            // ===== TOTAL VALOR REDUZIDO E TOTAL DE ITENS =====
            $itemReduzido = false;
            $itemRetirado = false;
            $totalValorReduzido = 0;
            $totalItemReduzido = 0;

            $totalValorRetirado = 0;
            $totalItemRetirado = 0;
            $valores['reduzido'] = array();
            $valores['retirado'] = array();

            foreach ($buscarAnaliseConta as $b) {

                $valorproponente = ($b['qtdSolicitado'] * $b['ocoSolicitado'] * $b['vlSolicitado']);
                $valorcomponente = ($b['ocorrenciaRelator'] * $b['vlunitarioRelator'] * $b['qtdRelator']);
                $valorparecerista = ($b['ocoParecer'] * $b['vlParecer'] * $b['qtdParecer']);
                if ($valorcomponente < $valorproponente and $valorcomponente != 0) {
                    $valores['reduzido'][$totalItemReduzido]['idPlanilhaAprovacao'] = $b['idPlanilhaAprovacao'];
                    $valores['reduzido'][$totalItemReduzido]['nrFonteRecurso'] = $b['nrFonteRecurso'];
                    $valores['reduzido'][$totalItemReduzido]['idProduto'] = $b['idProduto'];
                    $valores['reduzido'][$totalItemReduzido]['item'] = $b['Item'];
                    $valores['reduzido'][$totalItemReduzido]['idEtapa'] = $b['idEtapa'];
                    $valores['reduzido'][$totalItemReduzido]['Etapa'] = $b['Etapa'];
                    $valores['reduzido'][$totalItemReduzido]['Produto'] = $b['produto'];
                    $valores['reduzido'][$totalItemReduzido]['vlreduzidoComp'] = $valorproponente - $valorcomponente;
                    $valores['reduzido'][$totalItemReduzido]['VlReduzidoParecerista'] = $valorparecerista - $valorproponente;

                    $valores['reduzido'][$totalItemReduzido]['vltotalsolicitado'] = $valorproponente;
                    $valores['reduzido'][$totalItemReduzido]['UnidadeProposta'] = $b['UnidadeProposta'];
                    $valores['reduzido'][$totalItemReduzido]['qtdSolicitado'] = $b['qtdSolicitado'];
                    $valores['reduzido'][$totalItemReduzido]['ocoSolicitado'] = $b['ocoSolicitado'];
                    $valores['reduzido'][$totalItemReduzido]['vlunitarioSolicitado'] = $b['vlSolicitado'];
                    $valores['reduzido'][$totalItemReduzido]['diasSolicitado'] = $b['diasSolicitado'];

                    $valores['reduzido'][$totalItemReduzido]['idUnidade'] = $b['idUnidade'];
                    $valores['reduzido'][$totalItemReduzido]['Unidade'] = $b['Unidade'];
                    $valores['reduzido'][$totalItemReduzido]['diasRelator'] = $b['diasRelator'];
                    $valores['reduzido'][$totalItemReduzido]['ocorrenciaRelator'] = $b['ocorrenciaRelator'];
                    $valores['reduzido'][$totalItemReduzido]['vlunitarioRelator'] = $b['vlunitarioRelator'];
                    $valores['reduzido'][$totalItemReduzido]['diasRelator'] = $b['diasRelator'];
                    $valores['reduzido'][$totalItemReduzido]['qtdRelator'] = $b['qtdRelator'];
                    $valores['reduzido'][$totalItemReduzido]['vltotalcomponente'] = $valorcomponente;
                    $valores['reduzido'][$totalItemReduzido]['justcomponente'] = $b['JSComponente'];

                    $valores['reduzido'][$totalItemReduzido]['UnidadeProjeto'] = $b['UnidadeProjeto'];
                    $valores['reduzido'][$totalItemReduzido]['qtdParecer'] = $b['qtdParecer'];
                    $valores['reduzido'][$totalItemReduzido]['ocoParecer'] = $b['ocoParecer'];
                    $valores['reduzido'][$totalItemReduzido]['diasParecerista'] = $b['diasParecerista'];
                    $valores['reduzido'][$totalItemReduzido]['vltotalparecerista'] = $valorparecerista;
                    $valores['reduzido'][$totalItemReduzido]['vlunitarioparecerista'] = $b['vlParecer'];
                    $valores['reduzido'][$totalItemReduzido]['justparecerista'] = $b['JSParecerista'];

                    $itemReduzido = true;
                    $reduzido = $valorproponente - $valorcomponente;
                    $totalValorReduzido += (float)$reduzido;
                    $totalItemReduzido++;
                }
                if ($valorcomponente == 0 and $valorproponente > 0) {

                    $valores['retirado'][$totalItemRetirado]['idPlanilhaAprovacao'] = $b['idPlanilhaAprovacao'];
                    $valores['retirado'][$totalItemRetirado]['nrFonteRecurso'] = $b['nrFonteRecurso'];
                    $valores['retirado'][$totalItemRetirado]['idProduto'] = $b['idProduto'];
                    $valores['retirado'][$totalItemRetirado]['item'] = $b['Item'];
                    $valores['retirado'][$totalItemRetirado]['idEtapa'] = $b['idEtapa'];
                    $valores['retirado'][$totalItemRetirado]['Etapa'] = $b['Etapa'];
                    $valores['retirado'][$totalItemRetirado]['Produto'] = $b['produto'];
                    $valores['retirado'][$totalItemRetirado]['vlretiradoComp'] = $valorproponente - $valorcomponente;
                    $valores['retirado'][$totalItemRetirado]['VlretiradoParecerista'] = $valorparecerista - $valorproponente;

                    $valores['retirado'][$totalItemRetirado]['vltotalsolicitado'] = $valorproponente;
                    $valores['retirado'][$totalItemRetirado]['UnidadeProposta'] = $b['UnidadeProposta'];
                    $valores['retirado'][$totalItemRetirado]['qtdSolicitado'] = $b['qtdSolicitado'];
                    $valores['retirado'][$totalItemRetirado]['ocoSolicitado'] = $b['ocoSolicitado'];
                    $valores['retirado'][$totalItemRetirado]['vlunitarioSolicitado'] = $b['vlSolicitado'];
                    $valores['retirado'][$totalItemRetirado]['diasSolicitado'] = $b['diasSolicitado'];

                    $valores['retirado'][$totalItemRetirado]['idUnidade'] = $b['idUnidade'];
                    $valores['retirado'][$totalItemRetirado]['Unidade'] = $b['Unidade'];
                    $valores['retirado'][$totalItemRetirado]['diasRelator'] = $b['diasRelator'];
                    $valores['retirado'][$totalItemRetirado]['qtdRelator'] = $b['qtdRelator'];
                    $valores['retirado'][$totalItemRetirado]['ocorrenciaRelator'] = $b['ocorrenciaRelator'];
                    $valores['retirado'][$totalItemRetirado]['vlunitarioRelator'] = $b['vlunitarioRelator'];
                    $valores['retirado'][$totalItemRetirado]['diasRelator'] = $b['diasRelator'];
                    $valores['retirado'][$totalItemRetirado]['vltotalcomponente'] = $valorcomponente;
                    $valores['retirado'][$totalItemRetirado]['justcomponente'] = $b['JSComponente'];

                    $valores['retirado'][$totalItemRetirado]['UnidadeProjeto'] = $b['UnidadeProjeto'];
                    $valores['retirado'][$totalItemRetirado]['qtdParecer'] = $b['qtdParecer'];
                    $valores['retirado'][$totalItemRetirado]['ocoParecer'] = $b['ocoParecer'];
                    $valores['retirado'][$totalItemRetirado]['diasParecerista'] = $b['diasParecerista'];
                    $valores['retirado'][$totalItemRetirado]['vltotalparecerista'] = $valorparecerista;
                    $valores['retirado'][$totalItemRetirado]['vlunitarioparecerista'] = $b['vlParecer'];
                    $valores['retirado'][$totalItemRetirado]['justparecerista'] = $b['JSParecerista'];

                    $itemRetirado = true;
                    $retirado = $valorproponente - $valorcomponente;
                    $totalValorRetirado += (float)$retirado;
                    $totalItemRetirado++;
                }
            }
            $buscarPlanilhaUnidade = PlanilhaUnidadeDAO::buscar();
            //$buscarsomaaprovacao = $planilhaaprovacao->somarPlanilhaAprovacao($idpronac, 206 , $tpplanilha);
            //$buscarsomaproposta = $tblPlanilhaProposta->somarPlanilhaProposta($buscarprojeto['idProjeto']);

            //NOVO MODELO DE SOMA
            /**********************************/
            $arrWhereSomaPlanilha = array();
            $arrWhereSomaPlanilha['idPronac = ?'] = $idpronac;
            $arrWhereSomaPlanilha['idPlanilhaItem <> ? '] = '206'; //elaboracao e agenciamento
            $arrWhereSomaPlanilha['tpPlanilha = ? '] = $tpplanilha;
            $arrWhereSomaPlanilha['NrFonteRecurso = ? '] = '109';
            $arrWhereSomaPlanilha['stAtivo = ? '] = 'N';
            $arrWhereSomaPlanilha["idPedidoAlteracao = (?)"] = new Zend_Db_Expr("(SELECT TOP 1 max(idPedidoAlteracao) from sac.dbo.tbPlanilhaAprovacao where IdPRONAC = '{$idpronac}')");
            $arrWhereSomaPlanilha["tpAcao <> ('E') OR tpAcao IS NULL "] = '(?)';
            //componente
            $buscarsomaaprovacao = $planilhaaprovacao->somarItensPlanilhaAprovacao($arrWhereSomaPlanilha);
            //proponente
            $arrWhereSomaPlanilha['tpPlanilha = ? '] = 'SR';
            $buscarsomaproposta = $planilhaaprovacao->somarItensPlanilhaAprovacao($arrWhereSomaPlanilha);
            /************************************/

            $this->view->planilhaUnidade = $buscarPlanilhaUnidade;
            $this->view->analiseReduzido = $valores['reduzido'];
            $this->view->analiseRetirado = $valores['retirado'];
            $this->view->pronac = $buscarprojeto;
            $this->view->idpronac = $idpronac;
            $this->view->itemReduzido = $itemReduzido;
            $this->view->itemRetirado = $itemRetirado;

            $this->view->totValRed = $totalValorReduzido;
            $this->view->totItemRed = $totalItemReduzido;
            $this->view->totValRet = $totalValorRetirado;
            $this->view->totItemRet = $totalItemRetirado;

            $this->view->totalproponente = $buscarsomaproposta['soma'];
            $this->view->totalcomponente = $buscarsomaaprovacao['soma'];

            $auth = Zend_Auth::getInstance(); // pega a autentica��o
            $idagente = GerenciarPautaReuniaoDAO::consultaAgenteUsuario($auth->getIdentity()->usu_codigo);
            $idagente = $idagente['idAgente'];
            //-------------------------------------------------------------------------------------------------------------
            $reuniao = new Reuniao();
            $ConsultaReuniaoAberta = $reuniao->buscar(array("stEstado = ?" => 0));
            if ($ConsultaReuniaoAberta->count() > 0) {
                $ConsultaReuniaoAberta = $ConsultaReuniaoAberta->current()->toArray();
                $this->view->dadosReuniaoPlenariaAtual = $ConsultaReuniaoAberta;
                //---------------------------------------------------------------------------------------------------------------
                $votantes = new Votante();
                $exibirVotantes = $votantes->selecionarvotantes($ConsultaReuniaoAberta['idNrReuniao']);
                if (count($exibirVotantes) > 0) {
                    foreach ($exibirVotantes as $votantes) {
                        $dadosVotante[] = $votantes->idAgente;
                    }
                    if (count($dadosVotante) > 0) {
                        if (in_array($idagente, $dadosVotante)) {
                            $this->view->votante = true;
                        } else {
                            $this->view->votante = false;
                        }
                    }
                }
            } else {
                parent::message("N�o existe CNIC aberta no momento. Favor aguardar!", "principal/index", "ERROR");
            }
        } // fecha else
    } // fecha Metodo analisedecontaAction()

    /**
     * Metodo com a tabela de analise de custos - Projetos em Readequacao
     * @access public
     * @param void
     * @return void
     */
    public function analisedecustosreadequacaoAction()
    {
        $auth = Zend_Auth::getInstance(); // pega a autenticao
        $idagente = GerenciarPautaReuniaoDAO::consultaAgenteUsuario($auth->getIdentity()->usu_codigo);
        $idagente = $idagente['idAgente'];
        $tblPlanilhaAprovacao = new PlanilhaAprovacao();
        $tblPlanilhaProposta = new Proposta_Model_DbTable_TbPlanilhaProposta();
        $tblPlanilhaProjeto = new PlanilhaProjeto();
        $tblPlanoDistribuicao = new Proposta_Model_DbTable_PlanoDistribuicaoProduto();
        $tblAnaliseAprovacao = new AnaliseAprovacao();
        // caso o formulario seja enviado via post
        // atualiza a planilha
        if ($this->getRequest()->isPost()) {
            // recebe os dados via post
            $post = Zend_Registry::get('post');
            $idPronac = $post->idpronac;
            $idplanilhaaprovacao = $post->idPlanilha;

            $unidade = $post->unidade;
            $qtdItem = $post->qtd;
            $ocorrencia = $post->ocorrencia;
            $vlunitario = Mascara::delMaskMoeda($post->vlunitario);
            $dias = $post->dias;
            $justificativa = $post->justificativa;
            $idEtapa = $post->idEtapaDoItem;


            try {
                $rsPlanilhaAtual = $tblPlanilhaAprovacao->buscar(array('IdPRONAC = ?' => $idPronac), array('dtPlanilha DESC'))->current();
                $tpPlanilha = (!empty($rsPlanilhaAtual) && $rsPlanilhaAtual->tpPlanilha == 'SE') ? 'SE' : 'CO';
                $this->view->tpPlanilha = $tpPlanilha;

                $objAcesso = new Acesso();
                $dados = array(
                    'tpPlanilha' => $tpPlanilha,
                    'dtPlanilha' => $objAcesso->getExpressionDate(),
                    'idUnidade' => $unidade,
                    'qtItem' => $qtdItem,
                    'nrOcorrencia' => $ocorrencia,
                    'vlUnitario' => $vlunitario,
                    'qtDias' => $dias,
                    'dsJustificativa' => $justificativa,
                    'idAgente' => $idagente,
                    'stAtivo' => 'N');

                $where = 'idPlanilhaAprovacao = ' . $idplanilhaaprovacao;
                $alterarPlanilha = $tblPlanilhaAprovacao->alterar($dados, $where);

                //ATUALIZA ETAPA DO ITEM CASO TENHA SIDO ENVIADA
                if (!empty($idEtapa)) {

                    //recupera informacoes do item de custo que esta sendo alterado
                    $rsPlanCO = $tblPlanilhaAprovacao->buscar(array('idPlanilhaAprovacao = ? ' => $idplanilhaaprovacao))->current();

                    //recupera item de custo correspondente na planilha PA
                    $rsPlanPA = $tblPlanilhaAprovacao->buscar(array('idPlanilhaAprovacao = ? ' => $rsPlanCO->idPlanilhaAprovacaoPai))->current();

                    //recupera item de custo correspondente na planilha SR
                    $rsPlanSR = $tblPlanilhaAprovacao->buscar(array('idPlanilhaAprovacao = ? ' => $rsPlanPA->idPlanilhaAprovacaoPai))->current();

                    //etapa a ser atualizada
                    $dados = array('idEtapa' => $idEtapa);

                    //ATUALIZA ETAPA - PLANILHA CO ou SE
                    $where = 'idPlanilhaAprovacao = ' . $idplanilhaaprovacao;
                    $tblPlanilhaAprovacao->alterar($dados, $where);

                    //ATUALIZA ETAPA - PLANILHA PA
                    $wherePA = 'idPlanilhaAprovacao = ' . $rsPlanPA->idPlanilhaAprovacao;
                    $tblPlanilhaAprovacao->alterar($dados, $wherePA);

                    //ATUALIZA ETAPA - PLANILHA SR
                    $whereSR = 'idPlanilhaAprovacao = ' . $rsPlanSR->idPlanilhaAprovacao;
                    $tblPlanilhaAprovacao->alterar($dados, $whereSR);

                }

                if ($alterarPlanilha) {
                    parent::message("Registro inserido com sucesso!", "realizaranaliseprojeto/analisedecustosreadequacao/idpronac/" . $idPronac . "/?idplanilha=" . $idplanilhaaprovacao, "CONFIRM");
                } else {
                    throw new Exception("Erro ao efetuar altera&ccedil;o!");
                }
            } // fecha try
            catch (Exception $e) {
                parent::message($e->getMessage(), "realizaranaliseprojeto/analisedecustosreadequacao/idpronac/" . $idPronac, "ERROR");
            }
        } // fecha if
        else {
            // recebe os dados via get
            $idpronac = $this->_request->getParam("idpronac");
            $tblPlanilhaAprovacao = new PlanilhaAprovacao();
            $tblPlanilhaProposta = new Proposta_Model_DbTable_TbPlanilhaProposta();
            $tblPlanilhaProjeto = new PlanilhaProjeto();
            $tblProjetos = new Projetos();

            $rsPlanilhaAtual = $tblPlanilhaAprovacao->buscar(array('IdPRONAC = ?' => $idpronac), array('dtPlanilha DESC'))->current();
            $tipoplanilha = (!empty($rsPlanilhaAtual) && $rsPlanilhaAtual->tpPlanilha == 'SE') ? 'SE' : 'CO';
            $this->view->tpPlanilha = $tipoplanilha;

            $rsProjeto = $tblProjetos->buscar(array('IdPRONAC=?' => $idpronac))->current();
            $idPreProjeto = (!empty($rsProjeto->idProjeto)) ? $rsProjeto->idProjeto : '0';

            $rsProdutoPrincipal = $tblPlanoDistribuicao->buscar(array('idProjeto=?' => $idPreProjeto, 'stPrincipal=?' => 1, 'stPlanoDistribuicaoProduto = ?' => 1))->current();
            $rsAnaliseProdutoPrincipal = $tblAnaliseAprovacao->buscar(array('idPronac=?' => $idpronac, 'idProduto=?' => $rsProdutoPrincipal->idProduto, 'tpAnalise=?' => $tipoplanilha))->current();

            $arrBuscaPlanilha = array();
            $arrBuscaPlanilha["pap.stAtivo = ? "] = 'N';
            $arrBuscaPlanilha["pap.idPedidoAlteracao = (SELECT TOP 1 max(idPedidoAlteracao) from sac.dbo.tbPlanilhaAprovacao where IdPRONAC = '{$idpronac}')"] = '(?)';

            $buscarplanilhaCO = $tblPlanilhaAprovacao->buscarAnaliseCustosPlanilhaAprovacao($idpronac, $tipoplanilha, $arrBuscaPlanilha);

            $planilhaaprovacao = array();
            $count = 0;
            $fonterecurso = null;
            foreach ($buscarplanilhaCO as $resuplanilha) {
                $produto = $resuplanilha->Produto == null ? 'Adminitra&ccedil;&atilde;o do Projeto' : $resuplanilha->Produto;
                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['idPlanilhaAprovacao'] = $resuplanilha->idPlanilhaAprovacao;
                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['idUnidade'] = $resuplanilha->idUnidade;
                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['nrFonteRecurso'] = $resuplanilha->nrFonteRecurso;
                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['item'] = $resuplanilha->Item;

                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['Unidade'] = $resuplanilha->Unidade;
                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['qtitemcomp'] = $resuplanilha->qtItem;
                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['nrocorrenciacomp'] = $resuplanilha->nrOcorrencia;
                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['vlunitariocomp'] = $resuplanilha->vlUnitario;
                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['qtdiascomp'] = $resuplanilha->qtDias;
                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['Unidadecomp'] = $resuplanilha->Unidade;
                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['vlconselheiro'] = ($resuplanilha->vlTotal) ? $resuplanilha->vlTotal : 0;
                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['justificativaconselheiro'] = $resuplanilha->dsJustificativa;
                //$planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa.' - '.$resuplanilha->Etapa][$resuplanilha->UF.' - '.$resuplanilha->Cidade][$count]['reducao'] = $resuplanilha->VlSugeridoConselheiro < $resuplanilha->VlSolicitado ? 1 : 0;
                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['idProduto'] = $resuplanilha->idProduto;
                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['idEtapa'] = $resuplanilha->idEtapa;
                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['idPlanilhaItem'] = $resuplanilha->idPlanilhaItem;
                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['stAvaliacao'] = $resuplanilha->stAvaliacao;

                //grava array com produtos favorecidos na analise de conteudo
                if ($resuplanilha->idProduto >= 1) {
                    if ($resuplanilha->stAvaliacao == 1) {
                        $arrProdutosFavoraveis[$produto] = $resuplanilha->stAvaliacao;
                    }
                } else {
                    if ($rsAnaliseProdutoPrincipal->stAvaliacao == 1) {
                        $arrProdutosFavoraveis[$produto] = 1; //Admistracao do Projeto, que nao possui codigo de produto (so favorece os custos administrativos se o produto principal estiver favorecido)
                    }
                }
                $count++;
            }

            /******** Planilha aprovacao SR (Proponente - solicitada) ****************/
            $arrBuscaPlanilha = array();
            $arrBuscaPlanilha["pap.stAtivo = ? "] = 'N';
            $arrBuscaPlanilha["pap.idPedidoAlteracao = (SELECT TOP 1 max(idPedidoAlteracao) from sac.dbo.tbPlanilhaAprovacao where IdPRONAC = '{$idpronac}')"] = '(?)';

            $resuplanilha = null;
            $count = 0;
            $buscarplanilhaSR = $tblPlanilhaAprovacao->buscarAnaliseCustosPlanilhaAprovacao($idpronac, 'SR', $arrBuscaPlanilha);

            foreach ($buscarplanilhaSR as $resuplanilha) {
                $produto = $resuplanilha->Produto == null ? 'Adminitra&ccedil;&atilde;o do Projeto' : $resuplanilha->Produto;

                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['diasprop'] = $resuplanilha->qtDias;
                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['quantidadeprop'] = $resuplanilha->qtItem;
                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['ocorrenciaprop'] = $resuplanilha->nrOcorrencia;
                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['valorUnitarioprop'] = $resuplanilha->vlUnitario;
                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['UnidadeProposta'] = $resuplanilha->Unidade;
                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['vlproponente'] = ($resuplanilha->vlTotal) ? $resuplanilha->vlTotal : 0;
                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['justificitivaproponente'] = $resuplanilha->dsJustificativa;

                $valorConselheiro = $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['vlconselheiro'];
                $valorSolicitado = $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['vlproponente'];
                $reducao = $valorConselheiro < $valorSolicitado ? 1 : 0;
                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['reducao'] = $reducao;
                $count++;
            }

            /******** Planilha aprovacao PA (Parecerista) ****************/
            $arrBuscaPlanilha = array();
            $arrBuscaPlanilha["pap.stAtivo = ? "] = 'N';
            $arrBuscaPlanilha["pap.idPedidoAlteracao = (SELECT TOP 1 max(idPedidoAlteracao) from sac.dbo.tbPlanilhaAprovacao where IdPRONAC = '{$idpronac}')"] = '(?)';

            $resuplanilha = null;
            $count = 0;
            $buscarplanilhaPA = $tblPlanilhaAprovacao->buscarAnaliseCustosPlanilhaAprovacao($idpronac, 'PA', $arrBuscaPlanilha);

            foreach ($buscarplanilhaPA as $resuplanilha) {
                $produto = $resuplanilha->Produto == null ? 'Adminitra&ccedil;&atilde;o do Projeto' : $resuplanilha->Produto;
                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['UnidadeProjeto'] = $resuplanilha->Unidade;
                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['quantidadeparc'] = $resuplanilha->qtItem;
                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['ocorrenciaparc'] = $resuplanilha->nrOcorrencia;
                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['diasparc'] = $resuplanilha->qtDias;
                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['valorUnitarioparc'] = $resuplanilha->vlUnitario;
                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['vlparecerista'] = ($resuplanilha->vlTotal) ? $resuplanilha->vlTotal : 0;
                $planilhaaprovacao[$resuplanilha->FonteRecurso][$produto][$resuplanilha->idEtapa . ' - ' . $resuplanilha->Etapa][$resuplanilha->UF . ' - ' . $resuplanilha->Cidade][$count]['justificativaparecerista'] = $resuplanilha->dsJustificativa;
                $count++;
            }

            $buscarprojeto = $tblProjetos->buscar(array('IdPRONAC = ?' => $idpronac))->current();
            //$buscarsomaaprovacao = $tblPlanilhaAprovacao->somarPlanilhaAprovacao($idpronac, 206 , $tipoplanilha, array('PAP.stAtivo=?'=>'N'));

            $arrWhereSomaPlanilha = array();
            $arrWhereSomaPlanilha['idPronac = ?'] = $idpronac;
            $arrWhereSomaPlanilha['idPlanilhaItem <> ? '] = '206'; //elaboracao e agenciamento
            $arrWhereSomaPlanilha['tpPlanilha = ? '] = $tipoplanilha;
            $arrWhereSomaPlanilha['NrFonteRecurso = ? '] = '109';
            $arrWhereSomaPlanilha['stAtivo = ? '] = 'N';
            $arrWhereSomaPlanilha["idPedidoAlteracao = (SELECT TOP 1 max(idPedidoAlteracao) from sac.dbo.tbPlanilhaAprovacao where IdPRONAC = '{$idpronac}')"] = '(?)';
            $arrWhereSomaPlanilha["tpAcao <> ('E') OR tpAcao IS NULL "] = '(?)';
            $buscarsomaaprovacao = $tblPlanilhaAprovacao->somarItensPlanilhaAprovacao($arrWhereSomaPlanilha);
            $arrWhereSomaPlanilha['tpPlanilha = ? '] = 'SR';
            $buscarsomaproposta = $tblPlanilhaAprovacao->somarItensPlanilhaAprovacao($arrWhereSomaPlanilha);
            $arrWhereSomaPlanilha['tpPlanilha = ? '] = 'PA';
            $buscarsomaprojeto = $tblPlanilhaAprovacao->somarItensPlanilhaAprovacao($arrWhereSomaPlanilha);
            //$buscarsomaproposta = $tblPlanilhaProposta->somarPlanilhaProposta($buscarprojeto->idProjeto);
            //$buscarsomaprojeto = $tblPlanilhaProjeto->somarPlanilhaProjeto($idpronac, 109);
            $buscarPlanilhaUnidade = PlanilhaUnidadeDAO::buscar();
            $this->view->planilhaUnidade = $buscarPlanilhaUnidade;
            $this->view->planilha = $planilhaaprovacao;
            $this->view->projeto = $buscarprojeto;
            $this->view->totalcomponente = $buscarsomaaprovacao['soma'];
            $this->view->totalparecerista = $buscarsomaprojeto['soma'];
            $this->view->totalproponente = $buscarsomaproposta['soma'];
            $this->view->produtosFavoraveis = $arrProdutosFavoraveis;

            $this->montaTela("realizaranaliseprojeto/analisedecustos.phtml", array());

        } // fecha else
    } // fecha metodo analisedecustosAction()

    public function formReintegrarEtapaAction()
    {
        $this->_helper->layout->disableLayout(); // desabilita o layout

        $post = Zend_Registry::get('post');
        $idPronac = $this->_request->getParam("idPronac");
        $idProduto = $this->_request->getParam("idProduto");
        $idEtapa = $this->_request->getParam("idEtapa");
        $tpPlanilha = $this->_request->getParam("tpPlanilha");
        $codEtapa = $this->_request->getParam("codEtapa");

        $tblPlanilhaAprovacao = new PlanilhaAprovacao();
        $tblPlanilhaProposta = new Proposta_Model_DbTable_TbPlanilhaProposta();
        $tblPlanilhaProjeto = new PlanilhaProjeto();

        $arrBusca = array();
        $arrBusca['PAP.idProduto = ?'] = $idProduto;
        $arrBusca['PAP.idEtapa = ?'] = $idEtapa;
        $arrBusca['PAP.stAtivo = ?'] = 'S';
        $rsPlanilha = $tblPlanilhaAprovacao->buscarAnaliseCustos($idPronac, $tpPlanilha, $arrBusca)->current();
        $this->view->dados = $rsPlanilha;

        $tblProjetos = new Projetos();
        $rsProjeto = $tblProjetos->buscar(array('IdPRONAC = ?' => $idPronac))->current();
        $this->view->dadosProjeto = $rsProjeto;

        if ($this->bln_readequacao != "true") //projeto de analise inicial
        {
            /*==== ETAPA - TOTAL SOLICITADO ====*/
            $arrWhereSolicitado = array();
            $arrWhereSolicitado['idProduto = ?'] = $idProduto;
            $arrWhereSolicitado['idEtapa = ?'] = $idEtapa;
            $rsTotalSolicitado = $tblPlanilhaProposta->somarPlanilhaProposta($rsProjeto->idProjeto, 109, null, $arrWhereSolicitado);

            /*==== ETAPA - TOTAL PARECERISTA ====*/
            $arrWhereParecerista = array();
            $arrWhereParecerista['idProduto = ?'] = $idProduto;
            $arrWhereParecerista['idEtapa = ?'] = $idEtapa;
            $rsTotalParecerista = $tblPlanilhaProjeto->somarPlanilhaProjeto($idPronac, 109, null, $arrWhereParecerista);

            /*==== ETAPA - TOTAL COMPONENTE =====*/
            $arrWhereSomaPlanilha['idPronac = ?'] = $idPronac;
            $arrWhereSomaPlanilha['tpPlanilha = ? '] = $tpPlanilha;
            $arrWhereSomaPlanilha['idEtapa = ? '] = $idEtapa;
            $arrWhereSomaPlanilha['idProduto = ? '] = $idProduto;
            $arrWhereSomaPlanilha['idPlanilhaItem <> ? '] = '206'; //elaboracao e agenciamento
            $arrWhereSomaPlanilha['NrFonteRecurso = ? '] = '109';
            $arrWhereSomaPlanilha['stAtivo = ? '] = 'S';
            $rsTotalComponente = $tblPlanilhaAprovacao->somarItensPlanilhaAprovacao($arrWhereSomaPlanilha);

        } else { //projeto de readequacao

            $arrWhereSomaPlanilha = array();
            $arrWhereSomaPlanilha["idPronac = ?"] = $idPronac;
            $arrWhereSomaPlanilha["tpPlanilha = ? "] = $tpPlanilha;
            $arrWhereSomaPlanilha["idEtapa = ? "] = $idEtapa;
            $arrWhereSomaPlanilha["idProduto = ? "] = $idProduto;
            $arrWhereSomaPlanilha["idPlanilhaItem <> ? "] = '206'; //elaboracao e agenciamento
            $arrWhereSomaPlanilha["NrFonteRecurso = ? "] = '109';
            $arrWhereSomaPlanilha["stAtivo = ? "] = 'N';
            $arrWhereSomaPlanilha["idPedidoAlteracao = (?)"] = new Zend_Db_Expr("(SELECT TOP 1 max(idPedidoAlteracao) from sac.dbo.tbPlanilhaAprovacao where IdPRONAC = '{$idPronac}')");
            $arrWhereSomaPlanilha["tpAcao <> ('E') OR tpAcao IS NULL "] = '(?)';

            /*==== ETAPA - TOTAL COMPONENTE =====*/
            $rsTotalComponente = $tblPlanilhaAprovacao->somarItensPlanilhaAprovacao($arrWhereSomaPlanilha);

            /*==== ETAPA - TOTAL SOLICITADO ====*/
            $arrWhereSomaPlanilha['tpPlanilha = ? '] = 'SR';
            $rsTotalSolicitado = $tblPlanilhaAprovacao->somarItensPlanilhaAprovacao($arrWhereSomaPlanilha);

            /*==== ETAPA - TOTAL PARECERISTA ====*/
            $arrWhereSomaPlanilha['tpPlanilha = ? '] = 'PA';
            $rsTotalParecerista = $tblPlanilhaAprovacao->somarItensPlanilhaAprovacao($arrWhereSomaPlanilha);

        }

        $this->view->totalEtapaSolicitado = $rsTotalSolicitado['soma'];
        $this->view->totalEtapaParecerista = $rsTotalParecerista['soma'];
        $this->view->totalEtapaComponente = $rsTotalComponente['soma'];

        $this->view->idProduto = $idProduto;
        $this->view->idEtapa = $idEtapa;
        $this->view->tpPlanilha = $tpPlanilha;
        $this->view->codEtapa = $codEtapa;
    }

    function reintegrarValoresEtapaAction()
    {

        $post = Zend_Registry::get('post');
        $idPronac = $this->_request->getParam("idPronac");
        $idProduto = $this->_request->getParam("idProduto");
        $idEtapa = $this->_request->getParam("idEtapa");
        $tpPlanilha = $this->_request->getParam("tpPlanilha");
        $etapaAReintegrar = $this->_request->getParam("etapaAReintegrar");
        $codEtapa = $this->_request->getParam("codEtapa");
        $justificativa = $this->_request->getParam("justificativa");

        $tblPlanilhaAprovacao = new PlanilhaAprovacao();

        if ($this->bln_readequacao != "true") {
            $url = "analisedecustos";
        } else {
            $url = "analisedecustosreadequacao";
        }

        try {


            if ($this->bln_readequacao != "true") //projeto de ANALISE INICIAL
            {
                $arrBusca = array();
                $arrBusca['PAP.idProduto = ?'] = $idProduto;
                $arrBusca['PAP.idEtapa = ?'] = $idEtapa;
                $arrBusca['PAP.stAtivo = ?'] = 'S';
                $rsPlanilha = $tblPlanilhaAprovacao->buscarAnaliseCustos($idPronac, $tpPlanilha, $arrBusca);

                foreach ($rsPlanilha as $planilha) {

                    //reintegra valos do proponente (Solicitado)
                    if ($etapaAReintegrar == "solicitado") {

                        $dados = null;
                        $dados = array('qtItem' => $planilha->quantidadeprop,
                            'nrOcorrencia' => $planilha->ocorrenciaprop,
                            'vlUnitario' => $planilha->valorUnitarioprop,
                            'dsJustificativa' => $justificativa);
                    }
                    //reintegra valos do parecerista
                    if ($etapaAReintegrar == "parecerista") {

                        $dados = null;
                        $dados = array('qtItem' => $planilha->quantidadeparc,
                            'nrOcorrencia' => $planilha->ocorrenciaparc,
                            'vlUnitario' => $planilha->valorUnitarioparc,
                            'dsJustificativa' => $justificativa);
                    }

                    $where = "IdPRONAC = '{$idPronac}'";
                    $where .= " AND idProduto = '{$idProduto}'";
                    $where .= " AND idEtapa   = '{$idEtapa}'";
                    $where .= " AND tpPlanilha = '{$tpPlanilha}'";
                    $where .= " AND idPlanilhaAprovacao = '{$planilha->idPlanilhaAprovacao}'";
                    $tblPlanilhaAprovacao->alterar($dados, $where);
                }

            } else { //projeto de READEQUACAO

                $arrBusca = array();
                $arrBusca['PAP.idProduto = ?'] = $idProduto;
                $arrBusca['PAP.idEtapa = ?'] = $idEtapa;
                $arrBusca['PAP.stAtivo = ?'] = 'N';
                $arrBusca['PAP.idPedidoAlteracao = (?)'] = new Zend_Db_Expr('(SELECT TOP 1 max(idPedidoAlteracao) from sac.dbo.tbPlanilhaAprovacao where IdPRONAC = PAP.IdPRONAC)');
                $rsPlanilha = $tblPlanilhaAprovacao->buscarAnaliseCustosPlanilhaAprovacao($idPronac, $tpPlanilha, $arrBusca);

                foreach ($rsPlanilha as $planilha) {

                    //recupera item de custo correspondente na planilha PA
                    $rsPlanPA = $tblPlanilhaAprovacao->buscar(array('idPlanilhaAprovacao = ? ' => $planilha->idPlanilhaAprovacaoPai))->current();

                    //recupera item de custo correspondente na planilha SR
                    $rsPlanSR = $tblPlanilhaAprovacao->buscar(array('idPlanilhaAprovacao = ? ' => $rsPlanPA->idPlanilhaAprovacaoPai))->current();

                    //reintegra valores do proponente (Solicitado)
                    if ($etapaAReintegrar == "solicitado") {

                        $dados = null;
                        $dados = array('qtItem' => $rsPlanSR->qtItem,
                            'nrOcorrencia' => $rsPlanSR->nrOcorrencia,
                            'vlUnitario' => $rsPlanSR->vlUnitario,
                            'dsJustificativa' => $justificativa);
                    }

                    //reintegra valos do parecerista
                    if ($etapaAReintegrar == "parecerista") {

                        $dados = null;
                        $dados = array('qtItem' => $rsPlanPA->qtItem,
                            'nrOcorrencia' => $rsPlanPA->nrOcorrencia,
                            'vlUnitario' => $rsPlanPA->vlUnitario,
                            'dsJustificativa' => $justificativa);
                    }

                    $where = "IdPRONAC = '{$idPronac}'";
                    $where .= " AND idProduto = '{$idProduto}'";
                    $where .= " AND idEtapa   = '{$idEtapa}'";
                    $where .= " AND tpPlanilha = '{$tpPlanilha}'";
                    $where .= " AND idPlanilhaAprovacao = '{$planilha->idPlanilhaAprovacao}'";
                    $tblPlanilhaAprovacao->alterar($dados, $where);

                }//feach foreach (planilha)
            }

            parent::message("Etapa reintegrada com sucesso!", "realizaranaliseprojeto/" . $url . "/idpronac/" . $idPronac . "/?ETP=" . $codEtapa, "CONFIRM");

        } // fecha try
        catch (Exception $e) {

            parent::message("Erro ao efetuar altera&ccedil;&atilde;o! " . $e->getMessage(), "realizaranaliseprojeto/" . $url . "/idpronac/" . $idPronac, "ERROR");
        }
    }

    public function recuperarEtapasDoItemAction()
    {

        $post = Zend_Registry::get('post');
        $this->_helper->layout->disableLayout(); // desabilita o layout
        $this->_helper->viewRenderer->setNoRender(true);
        //header("Content-Type: text/html; charset=ISO-8859-1");
        $idProduto = $post->idProduto;
        $idItem = $post->idItem;

        $arrEtapas = array();
        $tbItensXPlanXProduto = new tbItensPlanilhaProduto();
        $arrBusca = array();
        $arrBusca['p.idProduto = ?'] = $idProduto;
        $arrBusca['p.idPlanilhaItens = ?'] = $idItem;
        //$arrBusca['p.idPlanilhaEtapa = ?'] = 1;
        $rsEtapas = $tbItensXPlanXProduto->buscarEtapasDoItem($arrBusca, array('Etapa ASC'));
        foreach ($rsEtapas as $chave => $etapa) {
            $arrEtapas[$chave]["idPlanilhaEtapa"] = $etapa->idPlanilhaEtapa;
            $arrEtapas[$chave]["etapa"] = utf8_encode($etapa->Etapa);
        }
        //$arrEtapas = $rsEtapas->toArray();
        //x($arrEtapas);
        $this->_helper->json($arrEtapas);
        $this->_helper->viewRenderer->setNoRender(TRUE);
    }
} // fecha class
