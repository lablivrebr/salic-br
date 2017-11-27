<?php
/**
 * DiligenciarProponenteController
 * @author Equipe RUP - Politec
 * @since 07/06/2010
 * @version 1.0
 * @package application
 * @subpackage application.controller
 * @link http://www.cultura.gov.br
 * @copyright � 2010 - Minist�rio da Cultura - Todos os direitos reservados.
 */

class DiligenciarProponenteController extends MinC_Controller_Action_Abstract
{
    /**
     * Reescreve o m&eacute;todo init()
     * @access public
     * @param void
     * @return void
     */
    public function init()
    {
        $this->view->title = "Salic - Sistema de Apoio &agrave;s Leis de Incentivo &agrave; Cultura"; // t&iacute;tulo da p&aacute;gina
        $auth = Zend_Auth::getInstance(); // pega a autentica&ccedil;&atilde;o
        $Usuario = new UsuarioDAO(); // objeto usu&aacute;rio
        $GrupoAtivo = new Zend_Session_Namespace('GrupoAtivo'); // cria a sess&atilde;o com o grupo ativo

        if ($auth->hasIdentity()) // caso o usu&aacute;rio esteja autenticado
        {
            // verifica as permiss&otilde;es
            $PermissoesGrupo = array();
           // $PermissoesGrupo[] = 93;
            $PermissoesGrupo[] = 118;
           // $PermissoesGrupo[] = 119;
           // $PermissoesGrupo[] = 120;
            if (!in_array($GrupoAtivo->codGrupo, $PermissoesGrupo)) // verifica se o grupo ativo est&aacute; no array de permiss&otilde;es
            {
                parent::message("Voc� n&atilde;o tem permiss&atilde;o para acessar essa &aacute;rea do sistema!", "principal/index", "ALERT");
            }

            // pega as unidades autorizadas, org&atilde;os e grupos do usu&aacute;rio (pega todos os grupos)
            $grupos = $Usuario->buscarUnidadesAutorizadas($auth->getIdentity()->usu_codigo, 21);

            // manda os dados para a vis&atilde;o
            $this->view->usuario = $auth->getIdentity(); // manda os dados do usu&aacute;rio para a vis&atilde;o
            $this->view->arrayGrupos = $grupos; // manda todos os grupos do usu&aacute;rio para a vis&atilde;o
            $this->view->grupoAtivo = $GrupoAtivo->codGrupo; // manda o grupo ativo do usu&aacute;rio para a vis&atilde;o
            $this->view->orgaoAtivo = $GrupoAtivo->codOrgao; // manda o �rg&atilde;o ativo do usu&aacute;rio para a vis&atilde;o
        } // fecha if
        else // caso o usu&aacute;rio n&atilde;o esteja autenticado
        {
            return $this->_helper->redirector->goToRoute(array('controller' => 'index', 'action' => 'logout'), null, true);
        }

        parent::init(); // chama o init() do pai GenericControllerNew
    }
	public function indexAction()
	{
        $auth              = Zend_Auth::getInstance(); // pega a autentica&ccedil;&atilde;o
        $Usuario = new Autenticacao_Model_Usuario(); // objeto usu&aacute;rio
        $idagente = $Usuario->getIdUsuario($auth->getIdentity()->usu_codigo);
        $idagente = $idagente['idAgente'];

		// caso o formul&aacute;rio seja enviado via post
		// atualiza a planilha
		if ($this->getRequest()->isPost())
		{
			// recebe os dados via post
                        $post = Zend_Registry::get('post');
			$idPronac      = $post->idPronac;
			$justificativa = $post->justificativa;
			$TipoAprovacao = $post->aprovacao;

			try
			{
				// valida os campos
				if (empty($justificativa) || $justificativa == "Digite a justificativa...")
				{
					throw new Exception("Por favor, informe a justificativa!");
				}
				else if (strlen($justificativa) < 20)
				{
					throw new Exception("A justificativa deve conter no m�nimo 20 caracteres!");
				}
				else
				{
					// verifica se j&aacute; est&aacute; na pauta
                                        $projetos = new Projetos();
                                        $reuniao = new Reuniao();
                                        $diligencia = new Diligencia();
                                        $idReuniao = $reuniao->buscarReuniaoAberta();
                                        $idReuniao = $idReuniao['idNrReuniao'];
				
                                        $dadosDiligencia = array(
                                                                'idPronac' =>$idPronac,
                                                                'idTipoDiligencia' => 126,
                                                                'DtSolicitacao'=> date('Y-m-d H:i:s'),
                                                                'Solicitacao' => TratarString::escapeString($justificativa) ,
                                                                'idSolicitante'=> $idagente,
                                                                );
                                       $gravarDiligiencia = $diligencia->inserirDiligencia($dadosDiligencia);
                                       $dadosAltProjetos = array('Situacao'=>'C30');
                                       $whereAltProjetos = "IdPRONAC = $idPronac";

                                       $alterarSituacao = $projetos->alterar($dadosAltProjetos , $whereAltProjetos);
                                       $this->_redirect('areadetrabalho/index');
				} // fecha else
			} // fecha try
			catch (Exception $e)
			{
				parent::message($e->getMessage(), "diligenciarproponente/index", "ERROR");
			}
		} // fecha if
		else
		{
			// recebe os dados via get
			$idpronac   = $this->_request->getParam("idpronac");

			// dados via get

			try
			{
				// busca o pronac
                                $pronac = ProjetoDAO::buscarPronac($idpronac);

				$buscarPronac = ProjetoDAO::buscar($pronac['pronac']);

				// valida��o
				if (empty($pronac))
				{
					throw new Exception("Por favor, clique no Pronac Aguardando An�lise!");
				}
				else
				{

                                        $diligencia = new Diligencia();

                                        $respostaDiligencia = $diligencia->buscar(array('idPronac = ?'=>$idpronac));

					// manda os dados para a vis&atilde;o
					//$this->view->buscar          = $buscar;

					$this->view->pronac          = $buscarPronac;
                                        $this->view->idpronac        = $idpronac;
                                        
                                        $this->view->Respostas       = ($respostaDiligencia->count() > 0) ? $respostaDiligencia : false;
                                          //-------------------------------------------------------------------------------------------------------------
                                    $reuniao = new Reuniao();
                                     $ConsultaReuniaoAberta = $reuniao->buscar(array("stEstado = ?" => 0));
                                    if($ConsultaReuniaoAberta->count() > 0)
                                    {
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
                                    }
                                    else{
                                        parent::message("N&atilde;o existe CNIC aberta no momento. Favor aguardar!", "principal/index", "ERROR");
                                    }
				} // fecha else
			} // fecha try
			catch (Exception $e)
			{
				parent::message($e->getMessage(), "diligenciarproponente/index", "ERROR");
			}
		} // fecha else
	} // fecha m&eacute;todo indexAction()

} // fecha class