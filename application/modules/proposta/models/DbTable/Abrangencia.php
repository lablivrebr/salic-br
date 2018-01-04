<?php

class Proposta_Model_DbTable_Abrangencia extends MinC_Db_Table_Abstract
{
    protected $_schema = 'sac';
    protected $_name = 'Abrangencia';
    protected $_primary = 'idAbrangencia';

    /**
     * Retorna registros do banco de dados
     * @param array $where - array com dados where no formato "nome_coluna_1"=>"valor_1","nome_coluna_2"=>"valor_2"
     * @param array $order - array com orders no formado "coluna_1 desc","coluna_2"...
     * @param int $tamanho - numero de registros que deve retornar
     * @param int $inicio - offset
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function buscar($where = array(), $order = array(), $tamanho = -1, $inicio = -1)
    {
        $sql = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('a' => 'Abrangencia'), $this->_getCols(), $this->_schema)
            ->join(array('p' => 'Pais'), 'a.idPais = p.idPais and a.stAbrangencia = true', 'p.Descricao as pais', $this->getSchema('agentes'))
            ->joinLeft(array('u' => 'UF'), 'a.idUF = u.idUF', 'u.Descricao as uf', $this->getSchema('agentes'))
            ->joinLeft(array('m' => 'Municipios'), 'a.idMunicipioIBGE::varchar(6) = m.idMunicipioIBGE::varchar(6)', 'm.Descricao as cidade', $this->getSchema('agentes'));
        foreach ($where as $coluna => $valor) {
            $sql->where($coluna . ' = ?', $valor);
        }
        $result = $this->fetchAll($sql);
        return ($result) ? $result->toArray() : array();
    }

    public function verificarIgual($idPais, $idUF, $idMunicipio, $idPreProjeto)
    {
        $select = $this->select();
        $select->setIntegrityCheck(false);
        $select->from(
            array('Ab' => $this->_name),
            $this->_getCols(),
            $this->_schema
        );
        $select->where('idProjeto = ?', $idPreProjeto);
        $select->where('idPais = ?', $idPais);
        $select->where("idUF = '?'", $idUF);
        $select->where("idMunicipioibge = '?'", $idMunicipio);
        $select->where('stAbrangencia = ?', 1);
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_DB::FETCH_OBJ);
        return $db->fetchAll($select);
    }

    /**
     * @param array $dados - array com dados referentes as colunas da tabela no formato "nome_coluna_1"=>"valor_1","nome_coluna_2"=>"valor_2"
     * @return ID do registro inserido/alterado ou FALSE em caso de erro
     */
    public function salvar($dados)
    {
        $dados['stAbrangencia'] = true;
        if (isset($dados['idAbrangencia']) && !empty ($dados['idAbrangencia'])) {
            $rsAbrangencia = $this->find($dados['idAbrangencia'])->current();
        } else {
            $dados['idAbrangencia'] = null;
            return $this->insert($dados);
        }

        if (!empty($dados['idProjeto'])) {
            $rsAbrangencia->idProjeto = $dados['idProjeto'];
        }
        if (!empty($dados['idPais'])) {
            $rsAbrangencia->idPais = $dados['idPais'];
        }
        $rsAbrangencia->idUF = $dados['idUF']; //if(!empty($dados['idUF'])) { $rsAbrangencia->idUF = $dados['idUF']; }
        $rsAbrangencia->idMunicipioIBGE = $dados['idMunicipioIBGE'];//if(!empty($dados['idMunicipioIBGE'])) { $rsAbrangencia->idMunicipioIBGE = $dados['idMunicipioIBGE']; }
        if (!empty($dados['Usuario'])) {
            $rsAbrangencia->Usuario = $dados['Usuario'];
        }
        $rsAbrangencia->stAbrangencia = 1;
        $id = $rsAbrangencia->save();

        if ($id) {
            return $id;
        } else {
            return false;
        }
    }

    /**
     * @param number $idProjeto - ID do PerProjeto ao qual as lcoaliza��es est�o vinculadas
     * @return true or false
     * @todo colocar padrao ORM
     */
    public function excluirPeloProjeto($idProjeto)
    {
        $sql = "DELETE FROM sac.dbo.Abrangencia WHERE idProjeto = " . $idProjeto . " AND stAbrangencia = 1";

        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_DB::FETCH_OBJ);
        if ($db->query($sql)) {
            return true;
        } else {
            return false;
        }

    }

    /**
     * @param bool $retornaSelect
     * @access public
     * @return void
     * @todo retirar Zend_Db_Expr
     */
    public function AbrangenciaProjeto($retornaSelect = false)
    {

        $selectAbrangencia = $this->select();
        $selectAbrangencia->setIntegrityCheck(false);
        $selectAbrangencia->from(
            array($this->_name),
            array(
                'idAbrangencia' => new Zend_Db_Expr('min(idAbrangencia)'),
                'idProjeto',
                'idUF',
                'idMunicipioIBGE'
            )
        );
        $selectAbrangencia->group('idProjeto');
        $selectAbrangencia->group('idUF');
        $selectAbrangencia->group('idMunicipioIBGE');

        if ($retornaSelect)
            return $selectAbrangencia;
        else
            return $this->fetchAll($selectAbrangencia);
    }

    /**
     * @param bool $retornaSelect
     * @param bool $where
     * @access public
     * @return void
     * @todo retirar Zend_Db_Expr
     */
    public function AbrangenciaProjetoPesquisa($retornaSelect = false, $where = array())
    {

        $selectAbrangencia = $this->select();
        $selectAbrangencia->setIntegrityCheck(false);
        $selectAbrangencia->from(
            array('abr' => $this->_name),
            array(
                'idAbrangencia' => new Zend_Db_Expr('min(idAbrangencia)'),
                'idProjeto'
            )
        );

        $selectAbrangencia->joinInner(
            array('mun' => 'Municipios'),
            "mun.idUFIBGE = abr.idUF and mun.idMunicipioIBGE = abr.idMunicipioIBGE",
            array(),
            'agentes'
        );
        $selectAbrangencia->joinInner(
            array('uf' => 'UF'),
            "uf.idUF = abr.idUF",
            array(),
            'agentes'
        );
        $selectAbrangencia->where('abr.stAbrangencia = ?', 1);

        foreach ($where as $coluna => $valor) {
            $selectAbrangencia->where($coluna, $valor);
        }

        $selectAbrangencia->group('idProjeto');

        if ($retornaSelect)
            return $selectAbrangencia;
        else
            return $this->fetchAll($selectAbrangencia);
    }

    /**
     * M�todo para cadastrar
     * @access public
     * @
     * @param array $dados
     * @return bool
     */
    public function cadastrar($dados)
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_DB::FETCH_OBJ);

        $cadastrar = $db->insert("sac.dbo.Abrangencia", $dados);

        if ($cadastrar) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * @access public
     * @
     * @param array $dados
     * @return bool
     */
    public function excluir($where)
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_DB::FETCH_OBJ);

        $where = array("idAbrangencia = ? " => $where, "stAbrangencia = ?" => 1);

        $alterar = $db->update("sac.dbo.tbAbrangencia", array("idAbrangenciaAntiga" => NULL), array("idAbrangenciaAntiga = ? " => $where));
        $excluir = $db->delete("sac.dbo.Abrangencia", $where);

        if ($excluir) {
            return true;
        }

        return false;
    }


    /**
     * @param array $dados
     * @return bool
     * @todo verificar impactos e remover, faz a mesma coisa da abstract
     */
    public function alterar($dados, $where, $dbg = false)
    {

        $where = "idAbrangencia = $where";
        $alterar = $this->update($dados, $where);

        if ($alterar) {
            return true;
        } else {
            return false;
        }
    } // fecha metodo alterar()


    public function buscarAbrangenciasAtuais($idProjeto, $idPais, $idUF, $idMunicipioIBGE)
    {
        $sql = "SELECT * from sac.dbo.Abrangencia
                    WHERE
                        idProjeto = $idProjeto
                        and idPais = $idPais
                        and idUF = $idUF
                        and idMunicipioIBGE = $idMunicipioIBGE
                        and stAbrangencia = 1
                    ";
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_DB::FETCH_OBJ);
        $resultado = $db->fetchAll($sql);

        return $resultado;
    }


    public function buscarDadosAbrangenciaAlteracao($idpedidoalteracao, $avaliacao)
    {
        if ($avaliacao == "SEM_AVALIACAO") {
            $sql = "
            SELECT *, CAST(dsjustificativa AS text) as dsjustificativa FROM
            (
            SELECT
                    distinct (abran.idAbrangencia),
                    pais.Descricao pais,
                    uf.Descricao as uf,
                    mun.Descricao as mun,
                    abran.tpAcao as tpoperacao,
                    tpa.dsjustificativa,
taipa.idAvaliacaoItemPedidoAlteracao,
asipa.idAvaliacaoSubItemPedidoAlteracao,
asipa.stAvaliacaoSubItemPedidoAlteracao as avaliacao,
--CAST(asipa.dsAvaliacaoSubItemPedidoAlteracao AS TEXT) as dsAvaliacao
asipa.dsAvaliacaoSubItemPedidoAlteracao as dsAvaliacao,
abran.dsExclusao
                FROM
                    sac.dbo.tbAbrangencia abran
                    INNER JOIN bdcorporativo.scsac.tbPedidoAlteracaoProjeto proj on proj.idPedidoAlteracao = abran.idPedidoAlteracao
                    INNER JOIN sac.dbo.Projetos pr on pr.IdPRONAC = proj.IdPRONAC
                    INNER JOIN bdcorporativo.scsac.tbPedidoAlteracaoXTipoAlteracao tpa on tpa.idPedidoAlteracao = abran.idPedidoAlteracao
                    INNER JOIN bdcorporativo.scsac.tbTipoAlteracaoProjeto ta on ta.tpAlteracaoProjeto = tpa.tpAlteracaoProjeto
                    INNER JOIN sac.dbo.Abrangencia ab on ab.idProjeto = pr.idProjeto AND ab.stAbrangencia = 1
                    INNER JOIN agentes.dbo.Pais	pais on pais.idPais = abran.idPais
            LEFT JOIN agentes.dbo.Uf uf on uf.idUF = abran.idUF
            LEFT JOIN agentes.dbo.Municipios mun on mun.idMunicipioIBGE = abran.idMunicipioIBGE
--INNER JOIN bdcorporativo.scsac.tbAvaliacaoItemPedidoAlteracao taipa ON taipa.idPedidoAlteracao = tpa.idPedidoAlteracao
--INNER JOIN bdcorporativo.scsac.tbAcaoAvaliacaoItemPedidoAlteracao taaipa ON taipa.idAvaliacaoItemPedidoAlteracao = taaipa.idAvaliacaoItemPedidoAlteracao
LEFT JOIN bdcorporativo.scsac.tbAvaliacaoItemPedidoAlteracao taipa ON taipa.idPedidoAlteracao = tpa.idPedidoAlteracao
LEFT JOIN bdcorporativo.scsac.tbAcaoAvaliacaoItemPedidoAlteracao taaipa ON taipa.idAvaliacaoItemPedidoAlteracao = taaipa.idAvaliacaoItemPedidoAlteracao

LEFT JOIN bdcorporativo.scsac.tbAvaliacaoSubItemPedidoAlteracao asipa ON (taipa.idAvaliacaoItemPedidoAlteracao = asipa.idAvaliacaoItemPedidoAlteracao
	AND asipa.idAvaliacaoSubItemPedidoAlteracao = abran.idAbrangencia )
                WHERE
                    proj.IdPRONAC = $idpedidoalteracao and tpa.tpAlteracaoProjeto = 4 and taipa.tpAlteracaoProjeto = 4 and abran.tpAcao != 'N'
               --ORDER BY pais.Descricao, uf.Descricao, mun.Descricao, taipa.idAvaliacaoItemPedidoAlteracao DESC
            ) as minhaTabela
            ORDER BY pais, uf, mun, idAvaliacaoItemPedidoAlteracao DESC
            ";
        } else {

            $sql = "
            SELECT *, CAST(dsjustificativa AS text) as dsjustificativa FROM
            (
            SELECT
                    distinct (abran.idAbrangencia),
                    pais.Descricao pais,
                    uf.Descricao as uf,
                    mun.Descricao as mun,
                    abran.tpAcao as tpoperacao,
                    tpa.dsjustificativa,
taipa.idAvaliacaoItemPedidoAlteracao,
abran.dsExclusao,
tasia.idAvaliacaoSubItemPedidoAlteracao,
tasipa.stAvaliacaoSubItemPedidoAlteracao as avaliacao,
tasipa.dsAvaliacaoSubItemPedidoAlteracao as dsAvaliacao,
taipa.stAvaliacaoItemPedidoAlteracao
                FROM
                    sac.dbo.tbAbrangencia abran
                    INNER JOIN bdcorporativo.scsac.tbPedidoAlteracaoProjeto proj on proj.idPedidoAlteracao = abran.idPedidoAlteracao
                    INNER JOIN sac.dbo.Projetos pr on pr.IdPRONAC = proj.IdPRONAC
                    INNER JOIN bdcorporativo.scsac.tbPedidoAlteracaoXTipoAlteracao tpa on tpa.idPedidoAlteracao = abran.idPedidoAlteracao
                    INNER JOIN bdcorporativo.scsac.tbTipoAlteracaoProjeto ta on ta.tpAlteracaoProjeto = tpa.tpAlteracaoProjeto
                    INNER JOIN sac.dbo.Abrangencia ab on ab.idProjeto = pr.idProjeto AND ab.stAbrangencia = 1
                    INNER JOIN agentes.dbo.Pais	pais on pais.idPais = abran.idPais
            LEFT JOIN agentes.dbo.Uf uf on uf.idUF = abran.idUF
            LEFT JOIN agentes.dbo.Municipios mun on mun.idMunicipioIBGE = abran.idMunicipioIBGE
--INNER JOIN bdcorporativo.scsac.tbAvaliacaoItemPedidoAlteracao taipa ON taipa.idPedidoAlteracao = tpa.idPedidoAlteracao
--INNER JOIN bdcorporativo.scsac.tbAcaoAvaliacaoItemPedidoAlteracao taaipa ON taipa.idAvaliacaoItemPedidoAlteracao = taaipa.idAvaliacaoItemPedidoAlteracao
LEFT JOIN bdcorporativo.scsac.tbAvaliacaoItemPedidoAlteracao taipa ON taipa.idPedidoAlteracao = tpa.idPedidoAlteracao
LEFT JOIN bdcorporativo.scsac.tbAcaoAvaliacaoItemPedidoAlteracao taaipa ON taipa.idAvaliacaoItemPedidoAlteracao = taaipa.idAvaliacaoItemPedidoAlteracao

LEFT JOIN bdcorporativo.scsac.tbAvaliacaoSubItemPedidoAlteracao asipa ON (taipa.idAvaliacaoItemPedidoAlteracao = asipa.idAvaliacaoItemPedidoAlteracao
	AND asipa.idAvaliacaoSubItemPedidoAlteracao = abran.idAbrangencia )

LEFT JOIN bdcorporativo.scsac.tbAvaliacaoSubItemAbragencia tasia ON (tasia.idAbrangencia = abran.idAbrangencia AND tasia.idAvaliacaoItemPedidoAlteracao = taipa.idAvaliacaoItemPedidoAlteracao)
LEFT JOIN bdcorporativo.scsac.tbAvaliacaoSubItemPedidoAlteracao tasipa ON (tasipa.idAvaliacaoSubItemPedidoAlteracao = tasia.idAvaliacaoSubItemPedidoAlteracao AND tasipa.idAvaliacaoItemPedidoAlteracao = taipa.idAvaliacaoItemPedidoAlteracao)
                WHERE
                    proj.IdPRONAC = $idpedidoalteracao and tpa.tpAlteracaoProjeto = 4 and taipa.tpAlteracaoProjeto = 4 and abran.tpAcao != 'N'
                    --AND taipa.stAvaliacaoItemPedidoAlteracao in ('EA', 'AG')
               --ORDER BY pais.Descricao, uf.Descricao, mun.Descricao, taipa.idAvaliacaoItemPedidoAlteracao DESC
            ) as minhaTabela
            ORDER BY pais, uf, mun, idAvaliacaoItemPedidoAlteracao DESC
            ";
        }

        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_DB::FETCH_OBJ);
        $resultado = $db->fetchAll($sql);

        return $resultado;
    }


    public function buscarDadosAbrangenciaAlteracaoCoord($idpedidoalteracao, $avaliacao)
    {
        if ($avaliacao == "SEM_AVALIACAO") {
            $sql = "SELECT * , CAST(dsjustificativa AS text) AS dsjustificativa , CAST(dsjustificativa AS text) AS dsjustificativa  FROM  (
                    SELECT
                    distinct (abran.idAbrangencia),
                    pais.Descricao pais,
                    uf.Descricao as uf,
                    mun.Descricao as mun,
                    abran.tpAcao as tpoperacao,
                    tpa.dsjustificativa,
taipa.idAvaliacaoItemPedidoAlteracao,
asipa.idAvaliacaoSubItemPedidoAlteracao,
asipa.stAvaliacaoSubItemPedidoAlteracao as avaliacao,
asipa.dsAvaliacaoSubItemPedidoAlteracao,
abran.dsExclusao
                FROM
                    sac.dbo.tbAbrangencia abran
                    INNER JOIN bdcorporativo.scsac.tbPedidoAlteracaoProjeto proj on proj.idPedidoAlteracao = abran.idPedidoAlteracao
                    INNER JOIN sac.dbo.Projetos pr on pr.IdPRONAC = proj.IdPRONAC
                    INNER JOIN bdcorporativo.scsac.tbPedidoAlteracaoXTipoAlteracao tpa on tpa.idPedidoAlteracao = abran.idPedidoAlteracao
                    INNER JOIN bdcorporativo.scsac.tbTipoAlteracaoProjeto ta on ta.tpAlteracaoProjeto = tpa.tpAlteracaoProjeto
                    INNER JOIN sac.dbo.Abrangencia ab on ab.idProjeto = pr.idProjeto AND ab.stAbrangencia = 1
                    INNER JOIN agentes.dbo.Pais	pais on pais.idPais = abran.idPais
            LEFT JOIN agentes.dbo.Uf uf on uf.idUF = abran.idUF
            LEFT JOIN agentes.dbo.Municipios mun on mun.idMunicipioIBGE = abran.idMunicipioIBGE
--INNER JOIN bdcorporativo.scsac.tbAvaliacaoItemPedidoAlteracao taipa ON taipa.idPedidoAlteracao = tpa.idPedidoAlteracao
--INNER JOIN bdcorporativo.scsac.tbAcaoAvaliacaoItemPedidoAlteracao taaipa ON taipa.idAvaliacaoItemPedidoAlteracao = taaipa.idAvaliacaoItemPedidoAlteracao
LEFT JOIN bdcorporativo.scsac.tbAvaliacaoItemPedidoAlteracao taipa ON taipa.idPedidoAlteracao = tpa.idPedidoAlteracao
LEFT JOIN bdcorporativo.scsac.tbAcaoAvaliacaoItemPedidoAlteracao taaipa ON taipa.idAvaliacaoItemPedidoAlteracao = taaipa.idAvaliacaoItemPedidoAlteracao

LEFT JOIN bdcorporativo.scsac.tbAvaliacaoSubItemPedidoAlteracao asipa ON (taipa.idAvaliacaoItemPedidoAlteracao = asipa.idAvaliacaoItemPedidoAlteracao
	AND asipa.idAvaliacaoSubItemPedidoAlteracao = abran.idAbrangencia )
                WHERE
                    proj.IdPRONAC = $idpedidoalteracao and tpa.tpAlteracaoProjeto = 4 and abran.tpAcao != 'N'
               ) AS TABELA ORDER BY pais, uf, mun, idAvaliacaoItemPedidoAlteracao DESC ";
        } // fecha if
        else {

            $sql = "SELECT * , CAST(dsjustificativa AS text) AS dsjustificativa, CAST(dsAvaliacao AS text) AS dsAvaliacao  FROM  (
                    SELECT
                    distinct (abran.idAbrangencia),
                    pais.Descricao pais,
                    uf.Descricao as uf,
                    mun.Descricao as mun,
                    abran.tpAcao as tpoperacao,
                    tpa.dsjustificativa,
                    taipa.idAvaliacaoItemPedidoAlteracao,
                    abran.dsExclusao,
                    tasia.idAvaliacaoSubItemPedidoAlteracao,
                    tasipa.stAvaliacaoSubItemPedidoAlteracao as avaliacao,
                    tasipa.dsAvaliacaoSubItemPedidoAlteracao as dsAvaliacao,
                    taipa.stAvaliacaoItemPedidoAlteracao
                FROM
                    sac.dbo.tbAbrangencia abran
                    INNER JOIN bdcorporativo.scsac.tbPedidoAlteracaoProjeto proj on proj.idPedidoAlteracao = abran.idPedidoAlteracao
                    INNER JOIN sac.dbo.Projetos pr on pr.IdPRONAC = proj.IdPRONAC
                    INNER JOIN bdcorporativo.scsac.tbPedidoAlteracaoXTipoAlteracao tpa on tpa.idPedidoAlteracao = abran.idPedidoAlteracao
                    INNER JOIN bdcorporativo.scsac.tbTipoAlteracaoProjeto ta on ta.tpAlteracaoProjeto = tpa.tpAlteracaoProjeto
                    INNER JOIN sac.dbo.Abrangencia ab on ab.idProjeto = pr.idProjeto AND ab.stAbrangencia = 1
                    INNER JOIN agentes.dbo.Pais	pais on pais.idPais = abran.idPais
            LEFT JOIN agentes.dbo.Uf uf on uf.idUF = abran.idUF
            LEFT JOIN agentes.dbo.Municipios mun on mun.idMunicipioIBGE = abran.idMunicipioIBGE
--INNER JOIN bdcorporativo.scsac.tbAvaliacaoItemPedidoAlteracao taipa ON taipa.idPedidoAlteracao = tpa.idPedidoAlteracao
--INNER JOIN bdcorporativo.scsac.tbAcaoAvaliacaoItemPedidoAlteracao taaipa ON taipa.idAvaliacaoItemPedidoAlteracao = taaipa.idAvaliacaoItemPedidoAlteracao
LEFT JOIN bdcorporativo.scsac.tbAvaliacaoItemPedidoAlteracao taipa ON taipa.idPedidoAlteracao = tpa.idPedidoAlteracao
LEFT JOIN bdcorporativo.scsac.tbAcaoAvaliacaoItemPedidoAlteracao taaipa ON taipa.idAvaliacaoItemPedidoAlteracao = taaipa.idAvaliacaoItemPedidoAlteracao

LEFT JOIN bdcorporativo.scsac.tbAvaliacaoSubItemPedidoAlteracao asipa ON (taipa.idAvaliacaoItemPedidoAlteracao = asipa.idAvaliacaoItemPedidoAlteracao
	AND asipa.idAvaliacaoSubItemPedidoAlteracao = abran.idAbrangencia )

LEFT JOIN bdcorporativo.scsac.tbAvaliacaoSubItemAbragencia tasia ON (tasia.idAbrangencia = abran.idAbrangencia AND tasia.idAvaliacaoItemPedidoAlteracao = taipa.idAvaliacaoItemPedidoAlteracao)
LEFT JOIN bdcorporativo.scsac.tbAvaliacaoSubItemPedidoAlteracao tasipa ON (tasipa.idAvaliacaoSubItemPedidoAlteracao = tasia.idAvaliacaoSubItemPedidoAlteracao AND tasipa.idAvaliacaoItemPedidoAlteracao = taipa.idAvaliacaoItemPedidoAlteracao)
                WHERE
                    proj.IdPRONAC = $idpedidoalteracao and tpa.tpAlteracaoProjeto = 4  and abran.tpAcao != 'N'
                    --AND taipa.stAvaliacaoItemPedidoAlteracao in ('EA', 'AG')
                ) as tabelas ORDER BY pais, uf, mun, idAvaliacaoItemPedidoAlteracao DESC  ";
        } // fecha else

        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_DB::FETCH_OBJ);
        $resultado = $db->fetchAll($sql);

        return $resultado;
    }


    public function buscarDadosAbrangencia($idpedidoalteracao)
    {
        $sql = "select
                    distinct (abran.idAbrangencia),
                    pais.Descricao pais,
                    uf.Descricao as uf,
                    mun.Descricao as mun,
                    paxta.dsJustificativa
                from
                    sac.dbo.Abrangencia abran
                    INNER JOIN sac.dbo.Projetos pro on pro.idProjeto = abran.idProjeto
                    INNER JOIN bdcorporativo.scsac.tbPedidoAlteracaoProjeto pap on pap.IdPRONAC = pro.IdPRONAC
                    INNER JOIN bdcorporativo.scsac.tbPedidoAlteracaoXTipoAlteracao paxta on paxta.idPedidoAlteracao = pap.idPedidoAlteracao
                    INNER JOIN bdcorporativo.scsac.tbTipoAlteracaoProjeto tap on tap.tpAlteracaoProjeto = paxta.tpAlteracaoProjeto
                    INNER JOIN agentes.dbo.Uf uf on uf.idUF = abran.idUF
                    INNER JOIN agentes.dbo.Municipios mun on mun.idMunicipioIBGE = abran.idMunicipioIBGE
                    INNER JOIN agentes.dbo.Pais	pais on pais.idPais = abran.idPais
                where
                    pro.IdPRONAC  = $idpedidoalteracao and tap.tpAlteracaoProjeto = 4 and abran.stAbrangencia = 1
                ";
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_DB::FETCH_OBJ);
        $resultado = $db->fetchAll($sql);

        return $resultado;
    }

    public function buscarDadosAbrangenciaSolicitada($idpedidoalteracao)
    {
        $sql = "SELECT pais.Descricao pais,
                            uf.Descricao uf,
                            mun.Descricao mun,
                            paxta.dsJustificativa
                    FROM
                        agentes.dbo.Pais pais,
                        agentes.dbo.UF uf,
                        agentes.dbo.Municipios mun,
                        sac.dbo.tbAbrangencia ta,
                        bdcorporativo.scsac.tbPedidoAlteracaoProjeto tpa,
                        bdcorporativo.scsac.tbPedidoAlteracaoXTipoAlteracao paxta
                    WHERE
                        tpa.idPronac = $idpedidoalteracao AND
                        uf.idUF = ta.idUF AND
                        mun.idMunicipioIBGE = ta.idMunicipioIBGE and
                        pais.idPais = ta.idPais AND
                        ta.idPedidoAlteracao = tpa.idPedidoAlteracao AND
                        paxta.idPedidoAlteracao = tpa.idPedidoAlteracao
                        --AND paxta.tpAlteracaoProjeto = 4
                        ";

        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_DB::FETCH_OBJ);
        $resultado = $db->fetchAll($sql);

        return $resultado;
    }

    public function buscarDadosAbrangenciaSolicitadaLocal($idpedidoalteracao, $tpAcao = null)
    {
        $sql = "SELECT tpa.idPedidoAlteracao,
            				pais.Descricao pais,
                            uf.Descricao uf,
                            mun.Descricao mun,
                            paxta.dsJustificativa
                    FROM
                        agentes.dbo.Pais pais,
                        agentes.dbo.UF uf,
                        agentes.dbo.Municipios mun,
                        sac.dbo.tbAbrangencia ta,
                        bdcorporativo.scsac.tbPedidoAlteracaoProjeto tpa,
                        bdcorporativo.scsac.tbPedidoAlteracaoXTipoAlteracao paxta
                    WHERE
                        tpa.idPronac = $idpedidoalteracao AND
                        uf.idUF = ta.idUF AND
                        mun.idMunicipioIBGE = ta.idMunicipioIBGE and
                        pais.idPais = ta.idPais AND
                        ta.idPedidoAlteracao = tpa.idPedidoAlteracao AND
                        paxta.idPedidoAlteracao = tpa.idPedidoAlteracao
                        AND paxta.tpAlteracaoProjeto = 4
                        ";

        if (!empty($tpAcao)) :
            $sql .= " AND ta.tpAcao = '" . $tpAcao . "'";
        endif;


        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_DB::FETCH_OBJ);
        $resultado = $db->fetchAll($sql);

        return $resultado;
    }

    /**
     * M�todo para avaliar o local de realiza��o
     * @access public
     * @
     * @param $dados array
     * @return boolean
     */
    public function avaliarLocalRealizacao($dados)
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_DB::FETCH_OBJ);

        $cadastrar = $db->insert("bdcorporativo.scsac.tbAvaliacaoSubItemPedidoAlteracao", $dados);

        if ($cadastrar) {
            return true;
        } else {
            return false;
        }
    } // fecha m�todo avaliarLocalRealizacao()


    /**
     * M�todo para verificar se o loca de realiza��o j� existe
     */
    public function verificarLocalRealizacao($idProjeto, $idMunicipio)
    {
        $sql = "SELECT idMunicip�oIBGE FROM Abrangencia WHERE idProjeto=$idProjeto AND stAbrangencia = 1 AND idMunicipioIBGE=$idMunicipio";
        return $sql;
    }

    public function AbrangenciaGeografica($id_projeto)
    {
//         Antigo SQL
//        $sql = "SELECT CASE a.idPais
//                WHEN 0 THEN 'N&atilde;o &eacute; possivel informar o local de realiza&ccedil;&atilde;o do projeto'
//                ELSE p.Descricao
//                END as Pais,u.Descricao as UF,m.Descricao as Cidade,x.DtInicioDeExecucao,x.DtFinalDeExecucao
//                FROM  sac.Abrangencia a
//                INNER JOIN sac.PreProjeto x on (a.idProjeto = x.idPreProjeto)
//                LEFT JOIN Agentes.Pais p on (a.idPais=p.idPais)
//                LEFT JOIN Agentes.Uf u on (a.idUF=u.idUF)
//                LEFT JOIN Agentes.Municipios m on (a.idMunicipioIBGE=m.idMunicipioIBGE)
//                WHERE idProjeto=".$id_projeto." AND a.stAbrangencia = 1
//                ORDER BY p.Descricao,u.Descricao,m.Descricao";

        $select = $this->select();
        $select->setIntegrityCheck(false);
        $select->from(
            array('a' => $this->_name),
            array(
                new Zend_Db_Expr("
                    CASE WHEN a.idpais = 0 THEN 'N&atilde;o &eacute; possivel informar o local de realiza&ccedil;&atilde;o do projeto'
                        ELSE p.Descricao
                    END as Pais"),
                new Zend_Db_Expr("CASE a.pais WHEN 0 THEN 'N&atilde;o &eacute; possivel informar o local de realiza&ccedil;&atilde;o do projeto'
                        ELSE p.Descricao END as Pais"),
                'Pais' => new Zend_Db_Expr("CASE WHEN a.idpais = 0 THEN 'N&atilde;o &eacute; possivel informar o local de realiza&ccedil;&atilde;o do projeto'
                     ELSE p.Descricao
                      END"),
                'u.Descricao as UF',
                'm.Descricao as Cidade',
                'x.dtInicioDeExecucao',
                'x.dtfinaldeexecucao'
            ),
            $this->_schema
        );

        $select->joinInner(
            array('x' => 'PreProjeto'), 'a.idProjeto = x.idPreProjeto',
            null,
            $this->_schema
        );

        $select->joinLeft(
            array('p' => $this->getName('Pais')), 'a.idPais = p.idPais',
            null,
            $this->getSchema('agentes')
        );

        $select->joinLeft(
            array('u' => $this->getName('Uf')), 'a.idUF = u.idUF',
            null,
            $this->getSchema('agentes')
        );

        $select->joinLeft(
            array('m' => $this->getName('Municipios')), 'a.idMunicipioIBGE = m.idMunicipioIBGE',
            null,
            $this->getSchema('agentes')
        );

        $select->where('idProjeto= ?', $id_projeto);
        $select->where('a.stAbrangencia = ?', 1);
        $select->order(array('p.Descricao', 'u.Descricao', 'm.Descricao'));

        try {
            $db = Zend_Db_Table::getDefaultAdapter();
            $db->setFetchMode(Zend_DB::FETCH_OBJ);
        } catch (Zend_Exception_Db $e) {
            $this->view->message = $e->getMessage();
        }

        return $db->fetchAll($select);
    }

    public function buscarUfRegionalizacao( $idPreProjeto )
    {
        $select = $this->select();
        $select->setIntegrityCheck(false);
        $select->from(
            array('a' => $this->_name),
            array(
                'idPreProjeto'=>'a.idProjeto',
                'uf.idUF',

            ),
            $this->_schema
        );
        $select->joinInner(array('uf' => 'UF'), 'uf.idUF = a.idUF', array('idUF'=>'uf.idUF', 'UF'=>'uf.Sigla'), $this->getSchema('agentes'));
        $select->joinInner(array('mun' => 'Municipios'), 'mun.idMunicipioIBGE::varchar(6) = a.idMunicipioIBGE::varchar(6)', array('idMunicipio'=>'mun.idMunicipioIBGE', 'Municipio'=>'mun.Descricao'), $this->getSchema('agentes'));
        $select->where('a.idProjeto = ?', $idPreProjeto);
        $select->where("uf.Regiao = 'Sul' OR uf.Regiao = 'Sudeste'");
        $select->order('a.idProjeto DESC');
        $select->limit(1);
        $db= Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_DB::FETCH_OBJ);
        return $db->fetchRow($select);
    }
}
