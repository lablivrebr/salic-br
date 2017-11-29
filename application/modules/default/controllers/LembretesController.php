<?php

class LembretesController extends MinC_Controller_Action_Abstract {
	
	public function LembretesAction() {
	}
	
	/**
	 * Reescreve o m&eacute;todo init()
	 * @access public
	 * @param void
	 * @return void
	 */
	public function init()
	{
		$this->view->title = "Salic - Sistema de Apoio &agrave;s Leis de Incentivo &agrave; Cultura"; // t&iacute;tulo da p&aacute;gina
		$auth              = Zend_Auth::getInstance(); // pega a autentica&ccedil;&atilde;o
		$Usuario           = new UsuarioDAO(); // objeto usu&aacute;rio
		$GrupoAtivo        = new Zend_Session_Namespace('GrupoAtivo'); // cria a sess&atilde;o com o grupo ativo

		if ($auth->hasIdentity()) // caso o usu&aacute;rio esteja autenticado
		{
			// verifica as permiss&otilde;es
			$PermissoesGrupo = array();
			//$PermissoesGrupo[] = 93;  // Coordenador de Parecerista
			//$PermissoesGrupo[] = 94;  // Parecerista
			//$PermissoesGrupo[] = 103; // Coordenador de An&aacute;lise
			$PermissoesGrupo[] = 118; // Componente da Comiss&atilde;o
			//$PermissoesGrupo[] = 119; // Presidente da Mesa
			//$PermissoesGrupo[] = 120; // Coordenador Administrativo CNIC
			if (!in_array($GrupoAtivo->codGrupo, $PermissoesGrupo)) // verifica se o grupo ativo est&aacute; no array de permiss&otilde;es
			{
				parent::message("Voc� n&atilde;o tem permiss&atilde;o para acessar essa &aacute;rea do sistema!", "principal/index", "ALERT");
			}

			// pega as unidades autorizadas, org&atilde;os e grupos do usu&aacute;rio (pega todos os grupos)
			$grupos = $Usuario->buscarUnidadesAutorizadas($auth->getIdentity()->usu_codigo, 21);

			// manda os dados para a vis&atilde;o
			$this->view->usuario     = $auth->getIdentity(); // manda os dados do usu&aacute;rio para a vis&atilde;o
			$this->view->arrayGrupos = $grupos; // manda todos os grupos do usu&aacute;rio para a vis&atilde;o
			$this->view->grupoAtivo  = $GrupoAtivo->codGrupo; // manda o grupo ativo do usu&aacute;rio para a vis&atilde;o
			$this->view->orgaoAtivo  = $GrupoAtivo->codOrgao; // manda o �rg&atilde;o ativo do usu&aacute;rio para a vis&atilde;o
		} // fecha if
		else // caso o usu&aacute;rio n&atilde;o esteja autenticado
		{
			return $this->_helper->redirector->goToRoute(array('controller' => 'index', 'action' => 'logout'), null, true);
		}

		parent::init(); // chama o init() do pai GenericControllerNew
	} // fecha m&eacute;todo init()
	
	public function indexAction() 
	{
		// caso o formul&aacute;rio seja enviado via post
		if ($this->getRequest()->isPost())
		{
			$post = Zend_Registry::get('post');
			$contador  = $post->Contador;
			$lembrete  = $post->descricao;
			$idPronac  = $post->pronac;
			$databusca = $post->databusca;
			
		
			$mens = new LembretesDAO();
			$mens->alterarlembrete($contador, $lembrete);
			parent::message("Altera&ccedil;&atilde;o efetuada com sucesso!", "lembretes/index?pronac=".$idPronac."&databusca=".$databusca);
		}
			$get = Zend_Registry::get('get');
			$pronac    = $get->pronac;
			$dtlembrete = $get->databusca;
			
			$this->view->lembretesprojeto 	= LembretesDAO::buscaProjeto($pronac);
			//$this->view->lembretes 			= LembretesDAO::buscaLembrete($pronac);
			$this->view->lembretes 			= LembretesDAO::pesquisaLembrete($pronac,$dtlembrete);
	}
		
	public function inserirlembreteAction() 
	{
		$get = Zend_Registry::get('get');
		$pronac = $get->pronac;
		

		$tblembretesprojeto = LembretesDAO::buscaProjeto($pronac);
		$this->view->lembretesprojeto = $tblembretesprojeto;

		$tblembretes = LembretesDAO::buscaLembrete($pronac);
		$this->view->lembretes = $tblembretes;

		$this->view->anoprojeto = $tblembretesprojeto[0]->AnoProjeto;		
		$this->view->sequencial = $tblembretesprojeto[0]->Sequencial;

		// caso o formul&aacute;rio seja enviado via post
		if ($this->getRequest()->isPost())
		{
			$post 		= Zend_Registry::get('post');
			$pronac     = $post->pronac;
			$lembrete   = $post->descricao;
			$anoprojeto = $post->AnoProjeto;		
			$sequencial = $post->Sequencial;
			
			try
			{
				if (empty($lembrete))
				{
					throw new Exception("Por favor, informe o lembrete!");
				}
				else
				{
					$mens = new LembretesDAO();

					if ($mens->inserirLembrete($anoprojeto, $sequencial, $lembrete))
					{
						parent::message("Cadastro efetuado com sucesso!", "lembretes/index?pronac=".$pronac);
					}
					else
					{
						throw new Exception("Erro ao efetuar cadastro!");
					}
				}
			}
			catch(Exception $e)
			{
				parent::message($e->getMessage(), "lembretes/inserirlembrete?pronac=".$pronac, "ERROR");
			}
		}

	} 
		


	
	
	public function excluirAction()
	{
		$contador  = $_GET['id'];
		$pronac    = $_GET['pronac'];
		$databusca = $_GET['databusca'];

		$resultado = LembretesDAO::exluirlembrete($contador);
			
		if ($resultado)
		{
			parent::message("Erro ao excluir lembrete!", "lembretes/index?pronac=".$pronac."&databusca=".$databusca, "CONFIRM");
		}
		else
		{
			parent::message("Lembrete exclu&iacute;do com sucesso!", "lembretes/index?pronac=".$pronac."&databusca=".$databusca, "CONFIRM");
		}

		
	}

	/*

public function alterarlembreteAction() 
		{	


		$contador  = $_GET['id'];
		$pronac    = $_GET['pronac'];

		$lembrete = $_GET['Descricao'];
	
		$resultado = LembretesDAO::alterarlembrete($contador, $lembrete);
				
		if ($resultado)
		{
			parent::message("Erro ao alterar lembrete!", "lembretes/index?pronac=".$pronac, "CONFIRM");
		}
		else
		{
			parent::message("Lembrete alterado com sucesso!", "lembretes/index?pronac=".$pronac, "CONFIRM");
		}

		
	}

	
*/

	public function buscalembreteAction()
	
		{
		// caso o formul&aacute;rio seja enviado via post
		if ($this->getRequest()->isPost())
		{
			// recebe o pronac e data do lembrete via post
			$post   = Zend_Registry::get('post');
			$pronac = (int) $post->pronac;
			
			$dtlembrete =  $post->dtlembrete;
			
			try
			{
				// verifica se a data dolembrete veio vazio
				if (empty($dtlembrete) && !Data::validarData($dtlembrete))
				{
					throw new Exception("A Data &eacute; inv&aacute;lida!");
				}
				// busca o pronac no banco
				else
				{
					// integra&ccedil;&atilde;o MODELO e VIS&atilde;O

					$resultado = LembretesDAO::pesquisaLembrete($pronac,$dtlembrete);

					// caso o Lembrete n&atilde;o esteja cadastrado
					if (!$resultado)
					{
						throw new Exception("Registro n&atilde;o encontrado!");
					}
					// caso o Lembrete esteja cadastrado, 
					// vai para a p&aacute;gina dos Lembretes
					else
					{
						// redireciona a data para o lembrete
						$this->redirect("lembretes/index?pronac=" . $pronac ."&databusca=".$dtlembrete);
					}
				} // fecha else
			} // fecha try
			catch (Exception $e)
			{
				parent::message($e->getMessage(), "lembretes/buscalembrete?pronac=" . $pronac, "ERROR");
			}
		} // fecha if
		$get = Zend_Registry::get('get');
		$pronac = $get->pronac;
		$dtlembrete = $get->dtlembrete;
		
		$mens = new LembretesDAO();
		
		$tblembretesprojeto = $mens->buscaProjeto($pronac);
		$this->view->lembretesprojeto = $tblembretesprojeto;
		
		$tblembretes = $mens->buscaLembrete($pronac);
		$this->view->lembretes = $tblembretes;
	} 
}