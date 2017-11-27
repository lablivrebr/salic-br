<?php

class AnaliseprojetoController extends Zend_Controller_Action
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
            $PermissoesGrupo[] = 93;
            $PermissoesGrupo[] = 118;
            $PermissoesGrupo[] = 119;
            $PermissoesGrupo[] = 120;
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

    // fecha m&eacute;todo init()


    public function analiseprojetoAction()
    {

    }

    public function indexAction()
    {

        $get = Zend_Registry::get('get');
        $pronac = $get->pronac;

        $tbanaliseprojeto = AnaliseprojetoDAO::buscar($pronac);

        $this->view->analiseprojeto = $tbanaliseprojeto;
    }

}