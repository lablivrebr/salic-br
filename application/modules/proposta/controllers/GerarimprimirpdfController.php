<?php

/**
 * AnexarDocumentosController
 * @author Equipe RUP - Politec
 * @author wouerner <wouerner@gmail.com>
 * @since 28/04/2010
 * @link http://www.cultura.gov.br
 */
//require_once "GenericControllerNew.php";

class Proposta_GerarimprimirpdfController extends Proposta_GenericController
{
    public function init()
    {

        $this->view->title = "Salic - Sistema de Apoio &agrave;s Leis de Incentivo &agrave; Cultura";
        $auth = Zend_Auth::getInstance(); // pega a autenticacao
        $Usuario = new UsuarioDAO(); // objeto usuario
        $GrupoAtivo = new Zend_Session_Namespace('GrupoAtivo'); // cria a sess�o com o grupo ativo

        if ($auth->hasIdentity()) // caso o usuario esteja autenticado
        {
            // verifica as permissoes
            $PermissoesGrupo = array();
            $PermissoesGrupo[] = 93;  // Coordenador de Parecerista
            $PermissoesGrupo[] = 94;  // Parecerista
            $PermissoesGrupo[] = 103; // Coordenador de Analise
            $PermissoesGrupo[] = 118; // Componente da Comissao
            $PermissoesGrupo[] = 119; // Presidente da Mesa

        }
        else // caso o usuario nao esteja autenticado
        {
            return $this->_helper->redirector->goToRoute(array('controller' => 'index', 'action' => 'logout'), null, true);
        }
        parent::init(); // chama o init() do pai GenericControllerNew
    }
    public function indexAction(){
       $this->_helper->layout->disableLayout();

       function verifica($var){
           if($var or $var == 1){
               return "Sim";
           }else{
               return "N&atilde;o";
           }
       }
       function data($data){
           if(!empty ($data)){
                $dataF = new data($data);
                return $dataF->dataBrasileira($dataF->tratarDataZend($data, "americano"));
           }
       }
       function tratatexto($valor){

           $valor = str_replace('<br>', '|br|', $valor);
           $valor = str_replace('</p>', '|br|', $valor);
           $valor = strip_tags($valor);
           $valor = str_replace('|br|', '<br>', $valor);
           return $valor;

       }

        $id_projeto = $this->getRequest()->getParam('idPreProjeto');
        if( empty($id_projeto) ) {
            $id_projeto = $this->getRequest()->getParam('idpreprojeto');
        }


        $this->view->id_projeto = $id_projeto;
        $tblPreProjeto = new Proposta_Model_DbTable_PreProjeto();

        $rsDadosProjeto = array_change_key_case( $tblPreProjeto->buscaCompleta(array("idPreProjeto = ?"=>$id_projeto))->current()->toArray() );
        $this->view->rsDadosProjeto = $rsDadosProjeto;

        if( !empty($rsDadosProjeto['stproposta'])) {
            // Busca na tabela apoio ExecucaoImediata stproposta
            $tableVerificacao = new Proposta_Model_DbTable_Verificacao();
            $this->view->ExecucaoImediata = $tableVerificacao->findBy(array('idVerificacao' => $rsDadosProjeto['stproposta']));
        }

        $tbAbrangencia = new Proposta_Model_DbTable_Abrangencia();
        $this->view->rsAbrangencias = $tbAbrangencia->buscar( array("idProjeto"=>$id_projeto) );

        $tblPlanoDivulgacao = new Proposta_Model_DbTable_PlanoDeDivulgacao();
        $this->view->rsPlanoDivulgacao = $tblPlanoDivulgacao->buscar(array("pd.idProjeto = ?" => $id_projeto)); //busca dados de divulgacao do preprojeto

        $tblPlanoDistribuicao = new Proposta_Model_DbTable_PlanoDistribuicaoProduto();
        $this->view->rsPlanoDistribuicao = $tblPlanoDistribuicao->buscar( array("a.idProjeto = ?" => $id_projeto), array("idPlanoDistribuicao DESC"));

        $tblPlanilhaProposta = new Proposta_Model_DbTable_PlanilhaProposta;
        $this->view->rsOrcamento = $tblPlanilhaProposta->Orcamento($id_projeto);

        if (($rsDadosProjeto['mecanismo'] == "1") AND ( $rsDadosProjeto['idedital'] == NULL OR ($rsDadosProjeto['idedital'] == "0" ))) {
            $this->view->mecanismo = 'Incentivo Fiscal';
        } else {
            $this->view->mecanismo = 'FNC';
        }

        $this->_helper->layout->disableLayout();

        $html = $this->view->render('/gerarimprimirpdf/index.phtml');

        $pdf = new PDF($html, 'pdf');

        $pdf->gerarRelatorio();

        $this->_helper->viewRenderer->setNoRender(TRUE);
    }
}
