<?php
/**
 * Controller Disvincular Agentes
 * @author Equipe RUP - Politec
 * @since 07/06/2010
 * @version 1.0
 * @package application
 * @subpackage application.controller
 * @link http://www.cultura.gov.br
 * @copyright � 2010 - Minist&eacute;rio da Cultura - Todos os direitos reservados.
 */

class DesvincularagentesController extends MinC_Controller_Action_Abstract {

    /**
     * ====================
     * AGENTES
     * ====================
     */
    /**
     * Reescreve o m&eacute;todo init()
     * @access public
     * @param void
     * @return void
     */
    /**
     * Reescreve o m&eacute;todo init()
     * @access public
     * @param void
     * @return void
     */
    public function init() {
        $this->view->title = "Salic - Sistema de Apoio &agrave;s Leis de Incentivo &agrave; Cultura"; // t&iacute;tulo da p&aacute;gina
        $auth = Zend_Auth::getInstance(); // pega a autentica&ccedil;&atilde;o
        $Usuario = new UsuarioDAO(); // objeto usu&aacute;rio
        $GrupoAtivo = new Zend_Session_Namespace('GrupoAtivo'); // cria a sess&atilde;o com o grupo ativo

        if ($auth->hasIdentity()) // caso o usu&aacute;rio esteja autenticado
        {
            // verifica as permiss&otilde;es
            $PermissoesGrupo = array();
            $PermissoesGrupo[] = 93;  // Coordenador de Parecerista
            //$PermissoesGrupo[] = 94;  // Parecerista
            $PermissoesGrupo[] = 103; // Coordenador de An&aacute;lise
            //$PermissoesGrupo[] = 118; // Componente da Comiss&atilde;o
            //$PermissoesGrupo[] = 119; // Presidente da Mesa
            $PermissoesGrupo[] = 120; // Coordenador Administrativo CNIC
            $PermissoesGrupo[] = 122; // Coordenador de Acompanhamento
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

        parent::init();
        // chama o init() do pai GenericControllerNew
    }

    // fecha m&eacute;todo init()
    /**
     * Redireciona para o fluxo inicial do sistema
     * @access public
     * @param void
     * @return void
     */
    public function indexAction() 
    {
        // despacha para buscaragentes.phtml
        $this->_forward("buscaragentes");
    }

    // fecha m&eacute;todo buscaragentesAction()
    public function desvincularagentesAction() 
    {
        $filter = new Zend_Filter_StripTags();

        $idagente = $filter->filter($this->_request->getPost('idAgente'));
        $dados = DesvincularagentesDAO::desvincularagentes($idagente); 

        $post = Zend_Registry::get('get');
        $cnpjcpfsuperior = $post->cnpjcpfsuperior;
        $cnpjcpf = $post->cnpjcpf;
        $proponente = $post->proponente;
        $idagente = $post->idagente;

        $vin = new DesvincularagentesDAO();

        $tbagentedesvinculado = $vin->desvincularagentes($idagente);
        $this->view->agentedesvinculado = $tbagentedesvinculado;

        $tbvinculoagente = $vin->buscaragentes($cnpjcpf);
        $this->view->vinculoagente = $tbvinculoagente;

        $tbentidade = $vin->buscaentidade($cnpjcpf);
        $this->view->entidade = $tbentidade;
    }

    public function excluirAction() 
    {
        $idVinculoPrincipal = $_GET['idVinculoPrincipal'];
        $idAgente = $_GET['idAgente'];

		if($this->getRequest()->isGET()) 
        {
            $get = Zend_Registry::get('get');
            $cnpjcpf = $get->cnpjcpf;
            $nome = $get->nome;


			try
			{
				$resultado = DesvincularagentesDAO::desvincularagentes($idAgente, $idVinculoPrincipal);
				
				parent::message("Agente desvinculado com sucesso!", "desvincularagentes/mostraragentes?cnpjcpf=" . $cnpjcpf . "&nome=" . $nome." ", "CONFIRM");
				
			}		
			catch (Exception $e) 
	        {
	            parent::message("Erro ao desvincular Agentes!", "desvincularagentes/mostraragentes?cnpjcpf=" . $cnpjcpf . "&nome=" . $nome." ", "ERROR");
	        }
		
			//$this->_redirect("desvincularagentes/mostraragentes?cnpjcpf=" . $cnpjcpf . "&nome=" . $nome);
        
        }

        
     
        
    }


    public function buscaragentesAction() 
    {
		
    }
    
    
    public function buscaragentesvinculadosAction() 
    {
		
        // caso o formul&aacute;rio seja enviado via post
        if($this->getRequest()->isPOST()) 
        {
		    // recebe o cpf/cnpj via post
            $post = Zend_Registry::get('post');
            $cnpjcpf = Mascara::delMaskCNPJ($post->cnpjcpf);
            $nome = $post->nome;
         
            // VALIDA&ccedil;&atilde;O
            try {
                if(!$cnpjcpf && !$nome) 
                {
                    throw new Exception("Por favor, informe o CNPJ/CPF ou o Nome!");
                }
                else 
                {
                    if($cnpjcpf) 
                    {
                        if(strlen($cnpjcpf) == 11 && !Validacao::validarCPF($cnpjcpf)) 
                        {
                            throw new Exception("O N� do CPF &eacute; inv&aacute;lido!");
                        }
                        elseif(strlen($cnpjcpf) > 11 && !Validacao::validarCNPJ($cnpjcpf)) 
                        {
                            throw new Exception("O N� do CNPJ &eacute; inv&aacute;lido!");
                        }
                        
                        if(strlen($cnpjcpf) < 11 || strlen($cnpjcpf) > 14) 
                        {
                            throw new Exception("Informe todos os digitos!");
                        }
                        
                        if($nome) 
                        {
                            if(strlen($nome) > 70) 
                            {
                                throw new Exception("Nome inv&aacute;lido!");
                            }
                        }
                    }
                    else 
                    {
                        if(strlen($nome) > 70) 
                        {
                            throw new Exception("Nome inv&aacute;lido!");
                        }
                    }
                    
                    $this->_redirect("desvincularagentes/mostraragentes?cnpjcpf=" . $cnpjcpf . "&nome=" . $nome);
                    
                }
                
            }
            catch (Exception $e) 
            {
                parent::message($e->getMessage(), "desvincularagentes/buscaragentes", "ERROR");
            }
        }
        
    }

    public function mostraragentesAction() 
    {
        $vin = new DesvincularagentesDAO();
        
        if($this->getRequest()->isGET()) 
        {
            // recebe o cpf/cnpj via post
            $get = Zend_Registry::get('get');
            $cnpjcpf = $get->cnpjcpf;
            $nome = $get->nome;
            
            $tbentidade = $vin->buscaentidade($nome, $cnpjcpf);
            
            try
            {
            	if($tbentidade) 
		        {
		
					// ========== IN�CIO PAGINA&ccedil;&atilde;O ==========
					//criando a pagina&ccedil;ao
					Zend_Paginator::setDefaultScrollingStyle('Sliding');
					Zend_View_Helper_PaginationControl::setDefaultViewPartial('paginacao/paginacao.phtml');
					$paginator = Zend_Paginator::factory($tbentidade); // dados a serem paginados

					// p&aacute;gina atual e quantidade de &iacute;tens por p&aacute;gina
					$currentPage = $this->_getParam('page', 1);
					$paginator->setCurrentPageNumber($currentPage)->setItemCountPerPage(1);
					// ========== FIM PAGINA&ccedil;&atilde;O ==========

                    $this->view->entidade = $paginator;
                    $this->view->qtdEntidade    = count($tbentidade); // quantidade
            

		        }
		        else
		        {
		        	throw new Exception("Registro n&atilde;o encontrado!");
		        }
		        
            }catch (Exception $e) {
                parent::message($e->getMessage(), "desvincularagentes/buscaragentes", "ERROR");
            }      
		        
        }
    }
}// fecha class