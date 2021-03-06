<?php

/* ProjetosDAO
 * @author Equipe RUP - Politec
 * @since 29/03/2010
 * @version 1.0
 * @package application
 * @subpackage application.controllers
 * @link http://www.politec.com.br
 * @copyright � 2010 - Politec - Todos os direitos reservados.
 */

class ProjetosDAO extends Zend_Db_Table
{

    protected $_name = 'dbo.Agentes';

    /*     * ************************************************************************************************************************
     * Fun��o que retorna o sql desejado
     * *********************************************************************************************************************** */

    public static function retornaSQL($sqlDesejado)
    {

        $sql = '';

        if ($sqlDesejado == "sqlProjetos")
        {

            $sql = "SELECT idPronac, Pronac, NomeProjeto, CodSituacao,Situacao,
						   idParecer, DtConsolidacao,ValorProposta,OutrasFontes,
						   ValorSolicitado,ValorSugerido,Elaboracao,ValorParecer,PERC,Acima
								FROM sac.dbo.vwDesconsolidarParecer
									WHERE idSecretaria = 251
										ORDER BY DtConsolidacao, Pronac";
        }

        return $sql;
    }

    /*     * ************************************************************************************************************************
     * Fun��o que copia as tabelas sac.dbo.tbPlanilhaProjeto e tbAnaliseDeConteudo
     * e cola nas tabelas tbPlanilhaProjetoConselheiro e tbAnaliseConteudoConselheiro
     * *********************************************************************************************************************** */

    public static function tbPlanilhaProjeto($idPronac)
    {

        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_DB :: FETCH_OBJ);
        $objAcesso= new Acesso();

        $sql = "	INSERT INTO sac.dbo.tbPlanilhaAprovacao
                                                (tpPlanilha,
                                                 dtPlanilha,
                                                 idPlanilhaProjeto,
                                                 idPlanilhaProposta,
                                                 IdPRONAC,
                                                 idProduto,
                                                 idEtapa,
                                                 idPlanilhaItem,
                                                 dsItem,
                                                 idUnidade,
                                                 qtItem,
                                                 nrOcorrencia,
                                                 vlUnitario,
                                                 qtDias,
                                                 tpDespesa,
                                                 tpPessoa,
                                                 nrContraPartida,
                                                 nrFonteRecurso,
                                                 idUFDespesa,
                                                 idMunicipioDespesa,
                                                 dsJustificativa,
                                                 idAgente,
                                                 idPlanilhaAprovacaoPai,
                                                 idPedidoAlteracao,
                                                 tpAcao,
                                                 idRecursoDecisao,
                                                 stAtivo)
                                                 SELECT
                                                 'CO',
                                                 {$objAcesso->getDate()},
                                                 idPlanilhaProjeto,
                                                 idPlanilhaProposta,
                                                 idPRONAC,
                                                 idProduto,
                                                 idEtapa,
                                                 idPlanilhaItem,
                                                 Descricao,
                                                 idUnidade,
                                                 Quantidade,
                                                 Ocorrencia,
                                                 ValorUnitario,
                                                 QtdeDias,
                                                 TipoDespesa,
                                                 TipoPessoa,
                                                Contrapartida,
                                                FonteRecurso,
                                                UfDespesa,
                                                MunicipioDespesa,
                                                Justificativa,
                                                NULL,NULL,NULL,NULL,NULL,
                                                'S'
                                                FROM sac.dbo.tbPlanilhaProjeto  WHERE idPRONAC=$idPronac;"; 
        $db->fetchAll($sql);
    }

    public static function tbAnaliseDeConteudo($idPronac)
    {

        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_DB :: FETCH_OBJ);

        $objAcesso= new Acesso();
        $sqlAnaliseOriginal = "	INSERT INTO sac.dbo.tbAnaliseAprovacao
                                            (tpAnalise,
                                             dtAnalise,
                                             idAnaliseConteudo,
                                             IdPRONAC,
                                             idProduto,
                                             stLei8313,
                                             stArtigo3,
                                             nrIncisoArtigo3,
                                             dsAlineaArt3,
                                             stArtigo18,
                                             dsAlineaArtigo18,
                                             stArtigo26,
                                             stLei5761,
                                             stArtigo27,
                                             stIncisoArtigo27_I,
                                             stIncisoArtigo27_II,
                                             stIncisoArtigo27_III,
                                             stIncisoArtigo27_IV,
                                             stAvaliacao,
                                             dsAvaliacao)
                                             SELECT  'CO',
                                             {$objAcesso->getDate()},
                                             idAnaliseDeConteudo,
                                             idPronac,
                                             idProduto,
                                             Lei8313,
                                             Artigo3,
                                             IncisoArtigo3,
                                             AlineaArtigo3,
                                             Artigo18,
                                             AlineaArtigo18,
                                             Artigo26,
                                             Lei5761,
                                             Artigo27,
                                             IncisoArtigo27_I,
                                             IncisoArtigo27_II,
                                             IncisoArtigo27_III,
                                             IncisoArtigo27_IV,
                                             ParecerFavoravel,
                                             ParecerDeConteudo
                                             FROM sac.dbo.tbAnaliseDeConteudo WHERE idPRONAC=$idPronac  ";

             $db->fetchAll($sqlAnaliseOriginal);
    }

    /*     * ************************************************************************************************************************
     * Fun��o que faz o balanceamento  
     * Pega o Componente que tem menos projeto da �rea do projeto
     * ou manda para o componente que � da �rea e seguimento do projeto
     * *********************************************************************************************************************** */

    public static function balancear($idPronac)
    {
        try{
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_DB :: FETCH_OBJ);

        $sqlProjetoAreaSegmento = "SELECT Pr.idPRONAC, 
        ar.Codigo as area,
        sg.Codigo as segmento 
        FROM sac.dbo.Projetos Pr
        left JOIN sac.dbo.Area ar on ar.Codigo = pr.Area
        left JOIN sac.dbo.Segmento sg on sg.Codigo = pr.Segmento
        WHERE Pr.idPRONAC = $idPronac";

        // Busca a �rea e seguimento do projeto
        $PAS = $db->fetchAll($sqlProjetoAreaSegmento);
        foreach ($PAS as $dados)
        {
            $areaP = $dados->area;
            $segmentoP = $dados->segmento;
        }

        // Busca para verificar se existe algum componente para a area e segmento do projeto
        $sqlComponenteAreaSegmento = "
        SELECT C.idAgente,
               C.cdArea,
               C.cdSegmento,
               C.stTitular
               FROM agentes.dbo.tbTitulacaoConselheiro C 
               WHERE C.stConselheiro = 'A' AND C.cdArea = " . $areaP;
        
        $AAS = $db->fetchAll($sqlComponenteAreaSegmento);

        // Se n�o tiver componente com a Area e Segmento do projeto ele faz...
        if (count($ASS)==0)
        {

            //aqui j� est� buscando o id do agente que tem a menor quantidade de projetos
            $sqlMenor = "SELECT TOP 1 TC.idAgente as agente,
					       PXC.Qtd
					FROM agentes.dbo.tbTitulacaoConselheiro TC
					INNER JOIN (SELECT ATC.idAgente, COUNT(DPC.idPronac) Qtd
					            FROM  agentes.dbo.tbTitulacaoConselheiro ATC
					            LEFT JOIN bdcorporativo.scsac.tbDistribuicaoProjetoComissao DPC ON ATC.idAgente = DPC.idAgente
					            WHERE ATC.stConselheiro = 'A'
					            AND DPC.stDistribuicao = 'A'
					            OR DPC.stDistribuicao IS NULL
					            GROUP BY ATC.idAgente
					            UNION
					            SELECT ATC.idAgente, COUNT(DPC.idPronac) - COUNT(DPCI.idPronac) Qtd
					            FROM  agentes.dbo.tbTitulacaoConselheiro ATC
					            LEFT JOIN bdcorporativo.scsac.tbDistribuicaoProjetoComissao DPC ON ATC.idAgente = DPC.idAgente
					            LEFT JOIN bdcorporativo.scsac.tbDistribuicaoProjetoComissao DPCI ON ATC.idAgente = DPCI.idAgente
					            WHERE ATC.stConselheiro = 'A'
					            AND DPCI.stDistribuicao = 'I'
					            AND ATC.idAgente NOT in (SELECT DISTINCT ATC.idAgente
					                                     FROM  agentes.dbo.tbTitulacaoConselheiro ATC
					                                     LEFT JOIN bdcorporativo.scsac.tbDistribuicaoProjetoComissao DPC ON ATC.idAgente = DPC.idAgente
					                                     WHERE ATC.stConselheiro = 'A'
					                                     AND DPC.stDistribuicao = 'A'
					                                     OR DPC.stDistribuicao IS NULL)
					            GROUP BY ATC.idAgente) PXC ON PXC.idAgente = TC.idAgente
					WHERE TC.cdArea = " . $areaP . "
					ORDER BY PXC.Qtd, TC.idAgente ";

            $projetos = $db->fetchAll($sqlMenor);

            foreach ($projetos as $dados)
            {
                $menor = $dados->agente;
            }

            $objAcesso= new Acesso();
            $dados = "Insert into bdcorporativo.scsac.tbDistribuicaoProjetoComissao " .
                    "(idPRONAC, idAgente, dtDistribuicao, idResponsavel)" .
                    "values" .
                    "($idPronac, $menor, {$objAcesso->getDate()}, 7522);
                    UPDATE sac.dbo.Projetos SET dtSituacao= {$objAcesso->getDate()}, Situacao = 'C10' WHERE IdPRONAC = $idPronac;";

            $insere = $db->query($dados);
            // Se tiver componente com a Area e Segmento do projeto ele faz...
        }
        else
        {

            $objAcesso= new Acesso();
            $dados = "Insert into bdcorporativo.scsac.tbDistribuicaoProjetoComissao " .
                    "(idPRONAC, idAgente, dtDistribuicao, idResponsavel)" .
                    "values" .
                    "($idPronac, ".$AAS[0]->idAgente.", {$objAcesso->getDate()}, 7522);
                    UPDATE sac.dbo.Projetos SET dtSituacao={$objAcesso->getDate()}, Situacao = 'C10',  WHERE IdPRONAC = $idPronac;";
            $insere = $db->query($dados);
        }
        }
        catch(Exception $e){
            echo $e->getMessage();
            echo $sqlComponenteAreaSegmento;
            die;
        }
        // atualiza a situa��o do projeto
//        $atualizarProjeto = "UPDATE sac.dbo.Projetos SET Situacao = 'C10' WHERE IdPRONAC = $idPronac";
//        $db->fetchAll($atualizarProjeto);
    }

    /*     * ************************************************************************************************************************
     * Altera a situa��o do projeto para C10 para n�o aparecer na tela
     * Situa��o de enviado para o componente
     * *********************************************************************************************************************** */

    public static function alteraProjeto($idPronac)
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_DB :: FETCH_OBJ);
        $dados = array('Situacao' => 'C10');
        $where = "IdPRONAC =" . $idPronac;
        $n = $db->update('sac.dbo.Projetos', $dados, $where);
        $db->closeConnection();
    }

    public static function alterarDadosProjeto($dados, $idpronac)
    {
        try
        {
            $db= Zend_Db_Table::getDefaultAdapter();
            $db->setFetchMode(Zend_DB::FETCH_OBJ);
            $where = "idpronac = $idpronac";
            $alterar = $db->update("sac.dbo.Projetos", $dados, $where);
        }
        catch (Exception $e)
        {
            die("ERRO: AlterarDadosProjeto-ProjetoDAO. ".$e->getMessage());
        }
    }

}