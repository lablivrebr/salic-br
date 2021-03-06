<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of GerenciarPautaReuniao
 *
 * @author 01373930160
 */
class GerenciarPautaReuniaoDAO
{

    public static function consultaAgenteUsuario($usu_codigo)
    {
        $sql = "select usu_codigo,
                idAgente from tabelas.dbo.Usuarios u
                inner join agentes.dbo.Agentes a on (u.usu_identificacao=a.CNPJCPF)
                where usu_codigo=$usu_codigo
                ";

        try
        {
            $db= Zend_Db_Table::getDefaultAdapter();
            $db->setFetchMode(Zend_DB::FETCH_ASSOC);
            return $db->fetchRow($sql);
        }
        catch (Zend_Exception_Db $e)
        {
            $this->view->message = $e->getMessage();
        }
    }

    public static function consultaIdAgenteUsuario($usu_codigo)
    {
        $sql = "select usu_codigo,
                idAgente from tabelas.dbo.Usuarios u
                inner join agentes.dbo.Agentes a on (u.usu_identificacao=a.CNPJCPF)
                where usu_codigo=$usu_codigo
                ";
        return $sql;
    }

    public static function consultaAreaCultural()
    {
        $sql = "SELECT * FROM sac.dbo.Area";

        try
        {
            $db= Zend_Db_Table::getDefaultAdapter();
            $db->setFetchMode(Zend_DB::FETCH_OBJ);
        }
        catch (Zend_Exception_Db $e)
        {
            $this->view->message = $e->getMessage();
        }

        return $db->fetchAll($sql);
    }

    public static function consultaProjetosPautaReuniao()
    {

        $sql = "SELECT Pauta.idNrReuniao,
                       Pauta.dtEnvioPauta,
                       Pauta.stEnvioPlenario,
                       Pauta.tpPauta,
                       Pauta.stAnalise,
                       Pauta.dsAnalise,
                       Projetos.IdPRONAC,
                       Projetos.Area,
                       Projetos.Segmento,
                       Projetos.NomeProjeto,
                       Segmento.Descricao,
                       Parecer.Atendimento
                FROM   scsac.tbPauta AS Pauta
                       LEFT JOIN
                       sac.dbo.Projetos AS Projetos
                       ON Projetos.IdPRONAC = Pauta.IdPRONAC
                       LEFT JOIN
                       sac.dbo.Segmento AS Segmento
                       ON Segmento.Codigo = Projetos.Segmento
                       LEFT JOIN
                       sac.dbo.Parecer AS Parecer
                       ON Parecer.idPRONAC = Projetos.IdPRONAC
                       LEFT JOIN
                       sac.dbo.Area AS Area
                       ON Area.Codigo = Projetos.Area";
        try
        {
            $db= Zend_Db_Table::getDefaultAdapter();
            $db->setFetchMode(Zend_DB::FETCH_OBJ);
        }
        catch (Zend_Exception_Db $e)
        {
            $this->view->message = $e->getMessage();
        }

        return $db->fetchAll($sql);
    }

    public static function VerificaConsolidacaoReuniao($idnrreuniao, $idpronac)
    {
        $sql = "
                select
                pr.idpronac,
                pr.AnoProjeto+pr.Sequencial as pronac,
                pr.NomeProjeto,
                tp.stAnalise,
                CAST(tcv.dsConsolidacao AS TEXT) as dsConsolidacao
                from bdcorporativo.scsac.tbpauta tp
                JOIN sac..Projetos pr on pr.IdPRONAC = tp.IdPRONAC
                JOIN bdcorporativo.scsac.tbConsolidacaoVotacao tcv on tcv.IdPRONAC = tp.IdPRONAC and tcv.idNrReuniao = tp.idNrReuniao
                where tp.idNrReuniao = $idnrreuniao
                and tp.idpronac = $idpronac
                and tp.stAnalise in ('AS','IS')";
        try
        {
            $db= Zend_Db_Table::getDefaultAdapter();
            $db->setFetchMode(Zend_DB::FETCH_OBJ);
        }
        catch (Zend_Exception_Db $e)
        {
            $this->view->message = $e->getMessage();
        }

        return $db->fetchAll($sql);
    }
    
    public static function BuscarConsolidacaoReuniao($idnrreuniao, $idpronac)
    {
        $sql = "select dsConsolidacao
                from bdcorporativo.scsac.tbconsolidacaovotacao
                where idnrreuniao = $idnrreuniao
                and idpronac = $idpronac";
        try
        {
            $db= Zend_Db_Table::getDefaultAdapter();
            $db->setFetchMode(Zend_DB::FETCH_OBJ);
            return $db->fetchAll($sql);
        }
        catch (Zend_Exception_Db $e)
        {
            $this->view->message = $e->getMessage();
        }
    }

}
?>
