<?php

class Proposta_Model_DbTable_PlanoDistribuicaoProduto extends MinC_Db_Table_Abstract {
    protected $_schema = 'sac';
    protected $_name   = 'PlanoDistribuicaoProduto';
    protected $_primary = 'idPlanoDistribuicao';

    /**
     * Metodo para cadastrar
     * @access public
     * @param array $dados
     * @return integer (retorna o ultimo id cadastrado)
     */
    public function cadastrarDados($dados) {
        return $this->insert($dados);
    } // fecha metodo cadastrarDados()


    public function buscarPlanoDeDistribuicao($idPronac) {
        $a = $this->select();
        $a->setIntegrityCheck(false);
        $a->from(
                array('a' => $this->_name),
                array('idPlanoDistribuicao','idProduto','QtdePatrocinador','QtdeProponente','QtdeOutros')
        );
        $a->joinInner(
                array('b' => 'Projetos'), "a.idProjeto = b.idProjeto",
                array('IdPRONAC'), 'SAC'
        );
        $a->joinInner(
                array('c' => 'Produto'), "a.idProduto = c.Codigo",
                array('Descricao as Produto'), 'SAC'
        );
        $a->where('b.IdPRONAC = ?', $idPronac);
        return $this->fetchAll($a);
    }

    public function buscarProdutosProjeto($idPronac) {
        $a = $this->select();
        $a->setIntegrityCheck(false);
        $a->from(
                array('a' => $this->_name),
                array('stPrincipal','idProduto')
        );
        $a->joinInner(
                array('b' => 'Projetos'), "a.idProjeto = b.idProjeto",
                array(''), 'SAC'
        );
        $a->joinInner(
                array('c' => 'Produto'), "a.idProduto = c.Codigo",
                array('Descricao as Produto'), 'SAC'
        );
        $a->joinInner(
                array('d' => 'tbAnaliseDeConteudo'), "a.idProduto = d.idProduto AND b.IdPRONAC = d.idPronac",
                array('*'), 'SAC'
        );
        $a->where('b.IdPRONAC = ?', $idPronac);
        $a->where('d.idPronac = ?', $idPronac);
        $a->order(array('1 DESC', '3 ASC'));

        return $this->fetchAll($a);
    }

    public function comboProdutosParaInclusaoReadequacao($idPronac) {
        $a = $this->select();
        $a->setIntegrityCheck(false);
        $a->from(
            array('a' => $this->_name),
            array('idProduto')
        );
        $a->joinInner(
            array('b' => 'Produto'), "a.idProduto = b.codigo",
            array('Descricao AS Produto'), 'SAC'
        );
        $a->joinInner(
            array('c' => 'Projetos'), "a.idProjeto = c.idProjeto",
            array(''), 'SAC'
        );
        $a->where('c.IdPRONAC = ?', $idPronac);


        $b = $this->select();
        $b->setIntegrityCheck(false);
        $b->from(
            array('a' => $this->_name),
            array(
                new Zend_Db_Expr("'0', 'Administra&ccedil;&atilde;o do Projeto'")
            )
        );
        $b->joinInner(
            array('c' => 'Projetos'), "a.idProjeto = c.idProjeto",
            array(''), 'SAC'
        );
        $b->where('c.IdPRONAC = ?', $idPronac);


        $slctUnion = $this->select()
            ->union(array('('.$a.')', '('.$b.')'))
            ->order('1','2');

        return $this->fetchAll($slctUnion);
    }

    public function buscarDadosCadastrarProdutos($idPreProjeto, $idProduto) {
        $select = $this->select();

        $select->setIntegrityCheck(false);

        $select->distinct(true);

        $select->from(
            array('pd'=>$this->getName('PlanoDistribuicaoProduto')),
            array('CodigoProduto'=>'pd.idProduto',
                'idProposta'=> 'pd.idProjeto'
            ),
            $this->_schema
        );

        $select->where('idProduto = ?',$idProduto);

        $select->where('idProjeto = ?',$idPreProjeto);

        $select->where('pd.stPlanoDistribuicaoProduto = ?', 't');

        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_DB::FETCH_OBJ);

        return $db->fetchAll($select);
    }

    public function insertConsolidacaoPlanoDeDistribuicao($idPlanoDistribuicao)
    {
        $cols = array(
            'sum(qtExemplares) as QtdeProduzida',
            'sum(qtGratuitaDivulgacao) as qQtdeProponente',
            'sum(qtGratuitaPatrocinador) as QtdePatrocinador',
            'sum(qtGratuitaPopulacao) as QtdeOutros',
            'sum(qtPopularIntegral) as QtdeVendaPopularNormal',
            'sum(qtPopularParcial) as QtdeVendaPopularPromocional',
            'sum(vlUnitarioPopularIntegral) as vlUnitarioPopularNormal',
            'sum(vlReceitaPopularIntegral) ReceitaPopularNormal',
            'sum(vlReceitaPopularParcial) as ReceitaPopularPromocional',
            'sum(qtProponenteIntegral) as QtdeVendaNormal',
            'sum(qtProponenteParcial) as QtdeVendaPromocional',
            'avg(vlUnitarioProponenteIntegral) vlUnitarioNormal',
            'sum(vlReceitaProponenteIntegral) as PrecoUnitarioNormal',
            'sum(vlReceitaProponenteParcial) as PrecoUnitarioPromocional',
            '(sum(vlReceitaPopularParcial) + sum(vlReceitaPopularIntegral)+  sum(vlReceitaProponenteIntegral)+ sum(vlReceitaProponenteParcial)) as  PrecoUnitarioPromocional'
        );

        $sql = $this->select()
            ->from(
                array('tbDetalhaPlanoDistribuicao'),
                $cols,
                'sac'
            )
            ->where('idPlanoDistribuicao = ?', $idPlanoDistribuicao);
        echo $sql;die;
        return $this->fetchRow($sql);
    }

    /**
     * Grava registro. Se seja passado um ID ele altera um registro existente
     * @param array $dados - array com dados referentes as colunas da tabela no formato "nome_coluna_1"=>"valor_1","nome_coluna_2"=>"valor_2"
     * @return ID do registro inserido/alterado ou FALSE em caso de erro
     */
    public function salvar($dados)
    {
        //INSTANCIANDO UM OBJETO DE ACESSO AOS DADOS DA TABELA
        $tmpTblPlanoDistribuicao = new Proposta_Model_DbTable_PlanoDistribuicaoProduto();

        //DECIDINDO SE SERA FEITA UM INSERT OU UPDATE
        if(!empty($dados['idPlanoDistribuicao'])){
            $tmpRsPlanoDistribuicao = $tmpTblPlanoDistribuicao->find($dados['idPlanoDistribuicao'])->current();
        }else{
            $tmpRsPlanoDistribuicao = $tmpTblPlanoDistribuicao->createRow();
        }
        //ATRIBUINDO VALORES AOS CAMPOS QUE FORAM PASSADOS
        if(isset($dados['idProjeto'])){ $tmpRsPlanoDistribuicao->idProjeto = $dados['idProjeto']; }
        if(isset($dados['idProduto'])){ $tmpRsPlanoDistribuicao->idProduto = $dados['idProduto']; }
        if(isset($dados['Area'])){ $tmpRsPlanoDistribuicao->Area = $dados['Area']; }
        if(isset($dados['Segmento'])){ $tmpRsPlanoDistribuicao->Segmento = $dados['Segmento']; }
        if(isset($dados['idPosicaoDaLogo'])){ $tmpRsPlanoDistribuicao->idPosicaoDaLogo = $dados['idPosicaoDaLogo']; }
        if(isset($dados['QtdeProduzida'])){ $tmpRsPlanoDistribuicao->QtdeProduzida = $dados['QtdeProduzida']; }
        if(isset($dados['QtdePatrocinador'])){ $tmpRsPlanoDistribuicao->QtdePatrocinador = $dados['QtdePatrocinador']; }
        if(isset($dados['QtdeProponente'])){ $tmpRsPlanoDistribuicao->QtdeProponente = $dados['QtdeProponente']; }
        if(isset($dados['QtdeOutros'])){ $tmpRsPlanoDistribuicao->QtdeOutros = $dados['QtdeOutros']; }
        if(isset($dados['QtdeVendaNormal'])){ $tmpRsPlanoDistribuicao->QtdeVendaNormal = $dados['QtdeVendaNormal']; }
        if(isset($dados['QtdeVendaPromocional'])){ $tmpRsPlanoDistribuicao->QtdeVendaPromocional = $dados['QtdeVendaPromocional']; }
        if(isset($dados['PrecoUnitarioNormal'])){ $tmpRsPlanoDistribuicao->PrecoUnitarioNormal = $dados['PrecoUnitarioNormal']; }
        if(isset($dados['PrecoUnitarioPromocional'])){ $tmpRsPlanoDistribuicao->PrecoUnitarioPromocional = $dados['PrecoUnitarioPromocional']; }
        if(isset($dados['stPrincipal'])){ $tmpRsPlanoDistribuicao->stPrincipal = $dados['stPrincipal']; }
        if(isset($dados['Usuario'])){ $tmpRsPlanoDistribuicao->Usuario = $dados['Usuario']; }
        if(isset($dados['dsJustificativaPosicaoLogo'])){ $tmpRsPlanoDistribuicao->dsJustificativaPosicaoLogo = $dados['dsJustificativaPosicaoLogo'] ; }
        if(isset($dados['stPlanoDistribuicaoProduto'])){ $tmpRsPlanoDistribuicao->stPlanoDistribuicaoProduto = $dados['stPlanoDistribuicaoProduto'] ; }

//        echo "<pre>";

        //SALVANDO O OBJETO CRIADO

        $id = $tmpRsPlanoDistribuicao->save();

        if($id){
            return $id;
        }else{
            return false;
        }
    }

    /**
     * Retorna registros do banco de dados
     * @param array $where - array com dados where no formato "nome_coluna_1"=>"valor_1","nome_coluna_2"=>"valor_2"
     * @param array $order - array com orders no formado "coluna_1 desc","coluna_2"...
     * @param int $tamanho - numero de registros que deve retornar
     * @param int $inicio - offset
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function buscar($where=array(), $order=array(), $tamanho=-1, $inicio=-1)
    {
        // criando objeto do tipo select
        $slct = $this->select();

        $slct->setIntegrityCheck(false);

        $cols = array_merge($this->_getCols(), array(
//                "FORMAT(a.QtdeProponente, '0,0','pt-br') as QtdeProponente",
//                "FORMAT(a.QtdeProduzida, '0,0','pt-br') as QtdeProduzida",
//                "FORMAT(a.QtdePatrocinador, '0,0','pt-br') as QtdePatrocinador",
//                "FORMAT(a.QtdeOutros, '0,0','pt-br') as QtdeOutros",
//                "FORMAT(a.QtdeVendaPopularPromocional, '0,0','pt-br') as QtdeVendaPopularPromocional",
//                "FORMAT(a.QtdeVendaNormal, '0,0','pt-br') as QtdeVendaNormal",
//                "FORMAT(a.QtdeVendaPromocional, '0,0','pt-br') as QtdeVendaPromocional",
//                "FORMAT(a.QtdeVendaPopularNormal, '0,0','pt-br') as QtdeVendaPopularNormal",
//                "FORMAT(a.vlUnitarioPopularNormal, 'N','pt-br') as vlUnitarioPopularNormal",
//                "FORMAT( a.vlUnitarioNormal, 'N','pt-br') AS vlUnitarioNormal",
//                "FORMAT( a.ReceitaPopularNormal, 'N','pt-br') AS ReceitaPopularNormal",
//                "FORMAT( a.ReceitaPopularPromocional, 'N','pt-br') AS ReceitaPopularPromocional",
//                "FORMAT( a.PrecoUnitarioPromocional, 'N','pt-br') AS PrecoUnitarioPromocional",
//                "FORMAT( a.PrecoUnitarioNormal, 'N', 'pt-br') AS PrecoUnitarioNormal",
            new Zend_Db_Expr('("a"."ReceitaPopularPromocional" + "a"."ReceitaPopularNormal" + "a"."PrecoUnitarioNormal" + "a"."PrecoUnitarioPromocional") as  Receita'),
        ));

        $slct->from(array("a"=> $this->_name), $cols, $this->_schema);
        $slct->joinInner(array("b"=>"Produto"),
            "a.idProduto = b.Codigo",
            array("Produto"=>"b.Descricao"),
            $this->_schema);
        $slct->joinInner(array("ar"=>"Area"),
            "a.Area = ar.Codigo",
            array("DescricaoArea"=>"ar.Descricao"),  $this->_schema);
        $slct->joinInner(array("s"=>"Segmento"),
            "a.Segmento = s.Codigo",
            array("DescricaoSegmento"=>"s.Descricao"),  $this->_schema);

        $slct->where('a.stPlanoDistribuicaoProduto = ?', 'true');

        // adicionando clausulas where
        foreach ($where as $coluna=>$valor)
        {
            $slct->where($coluna, $valor);
        }

        // adicionando linha order ao select
        $slct->order($order);

        // paginacao
        if ($tamanho > -1)
        {
            $tmpInicio = 0;
            if ($inicio > -1)
            {
                $tmpInicio = $inicio;
            }
            $slct->limit($tamanho, $tmpInicio);
        }

        //SETANDO A QUANTIDADE DE REGISTROS
        $this->_totalRegistros = $this->pegaTotal($where);
        //$this->_totalRegistros = 100;
        // retornando os registros conforme objeto select
        return $this->fetchAll($slct);
    }

    /**
     * Retorna registros do banco de dados
     * @param array $where - array com dados where no formato "nome_coluna_1"=>"valor_1","nome_coluna_2"=>"valor_2"
     * @param array $order - array com orders no formado "coluna_1 desc","coluna_2"...
     * @param int $tamanho - numero de registros que deve retornar
     * @param int $inicio - offset
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function buscarPlanoDistribuicao($where=array())
    {
        $slct = $this->select();
        $slct->setIntegrityCheck(false);
        $slct->from(
            array('a' => $this->_name),
            array(
                'a.idPlanoDistribuicao',
                'a.idProjeto',
                'a.idProduto',
                'a.Area',
                'a.Segmento',
                'a.idPosicaoDaLogo',
                'a.QtdeProduzida',
                'a.QtdePatrocinador',
                'a.QtdeProponente',
                'a.QtdeOutros',
                'a.QtdeVendaNormal',
                'a.QtdeVendaPromocional',
                'a.PrecoUnitarioNormal',
                'a.PrecoUnitarioPromocional',
                'a.stPrincipal',
                'a.Usuario',
                new Zend_Db_Expr('CAST("a"."dsJustificativaPosicaoLogo" AS TEXT) AS dsJustificativaPosicaoLogo'),
                'a.Usuario'
            ),
            $this->_schema
        );

        foreach ($where as $coluna=>$valor)
        {
            $slct->where($coluna, $valor);
        }

        return $this->fetchRow($slct);
    }

    /**
     * Retorna registros do banco de dados
     * @param array $where - array com dados where no formato "nome_coluna_1"=>"valor_1","nome_coluna_2"=>"valor_2"
     * @param array $order - array com orders no formado "coluna_1 desc","coluna_2"...
     * @param int $tamanho - numero de registros que deve retornar
     * @param int $inicio - offset
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function pegaTotal($where=array(), $order=array(), $tamanho=-1, $inicio=-1)
    {
        // criando objeto do tipo select
        $slct = $this->select();

        $slct->setIntegrityCheck(false);

        $slct->from(array("a"=> $this->_name), '*', $this->_schema);
        $slct->joinInner(array("b"=>"Produto"),
            "a.idProduto = b.Codigo",
            array("Produto"=>"b.Descricao"),  $this->_schema);
        $slct->joinLeft(array("c"=>"Verificacao"),
            "a.idPosicaoDaLogo = c.idVerificacao",
            array("PosicaoLogomarca"=>"c.Descricao"),  $this->_schema);

        $slct->where('a.stPlanoDistribuicaoProduto = ?', 'true');

        // adicionando clausulas where
        foreach ($where as $coluna=>$valor)
        {
            $slct->where($coluna, $valor);
        }

        // adicionando linha order ao select
        $slct->order($order);

        // paginacao
        if ($tamanho > -1)
        {
            $tmpInicio = 0;
            if ($inicio > -1)
            {
                $tmpInicio = $inicio;
            }
            $slct->limit($tamanho, $tmpInicio);
        }
        try{
            $rows = $this->fetchAll($slct);
            return $rows->count();
        }catch(Exception $e){
            xd($e->getMessage(), $slct->assemble());
        }
    }

    public function apagar($id){
        $objApagar = $this->find($id)->current();

        if ($objApagar) {
            $TbDetalhamentoPlanoDistribuicaoProduto = new Proposta_Model_DbTable_TbDetalhamentoPlanoDistribuicaoProduto();
            $TbDetalhamentoPlanoDistribuicaoProduto->delete(["idPlanoDistribuicao = ? " => $id]);

            if(!empty($objApagar->idProduto) && !empty($objApagar->idProduto)) {
                $TbPlanilhaProposta = new Proposta_Model_DbTable_TbPlanilhaProposta();
                $TbPlanilhaProposta->delete(
                    [
                        "idProjeto = ?" => $objApagar->idProjeto,
                        "idProduto = ?" => $objApagar->idProduto
                    ]
                );
            }

        }

        return $objApagar->delete();
    }

    public function atualizarAreaESegmento($area, $segmento, $idProjeto) {
        try {

            $arrayDadosPlanoDistribuicaoProduto = array(
                'Area' => $area,
                'Segmento' => $segmento
            );

            $arrayWherePlanoDistribuicaoProduto = array(
                'idProjeto = ?' => $idProjeto,
                'stPrincipal = ?' => '1'
            );
            $this->update($arrayDadosPlanoDistribuicaoProduto, $arrayWherePlanoDistribuicaoProduto);
        } catch (Exception $objException) {
            throw new Exception($objException->getMessage(), 0, $objException);
        }
    }

    public function buscarPlanoDistribuicaoDetalhadoByIdProjeto($idPreProjeto, $where = array(), $order = null)
    {

        $slct = $this->select();
        $slct->setIntegrityCheck(false);
        $slct->from(array("p" => 'PlanoDistribuicaoProduto'), $this->_getCols(), $this->_schema);

        $slct->joinInner(array("d" => "tbDetalhaPlanoDistribuicao"),
            "p.idPlanoDistribuicao = d.idPlanoDistribuicao",
            '*', $this->_schema);

        $slct->joinInner(array('uf' => 'uf'), 'uf.CodUfIbge = d.idUF', 'uf.Descricao AS DescricaoUf', $this->_schema);

        $slct->joinInner(array('mun' => 'municipios'), 'mun.idMunicipioIBGE = d.idMunicipio','mun.Descricao as DescricaoMunicipio', $this->getSchema('agentes'));

        $slct->joinInner(array("b"=>"produto"),
            "p.idProduto = b.codigo",
            array("Produto"=>"b.Descricao"),
            $this->_schema);

        $slct->joinInner(array("ar"=>"Area"),
            "p.area = ar.codigo",
            array("DescricaoArea"=>"ar.Descricao"),  $this->_schema);
        $slct->joinInner(array("s"=>"segmento"),
            "p.segmento = s.codigo",
            array("DescricaoSegmento"=>"s.Descricao"),  $this->_schema);

        $slct->where('p.idProjeto = ?', $idPreProjeto);

        foreach ($where as $coluna => $valor) {
            $slct->where($coluna, $valor);
        }

        $slct->order($order);

        try {
            return $this->fetchAll($slct)->toArray();

        } catch (Exception $e) {
            echo($slct->assemble());
            die;
        }
    }

    public function updateConsolidacaoPlanoDeDistribuicao($idPlanoDistribuicao)
    {
        $cols = array(
            new Zend_Db_Expr('sum("qtExemplares") as "QtdeProduzida"'),
            new Zend_Db_Expr('sum("qtGratuitaDivulgacao") as "QtdeProponente"'),
            new Zend_Db_Expr('sum("qtGratuitaPatrocinador") as "QtdePatrocinador"'),
            new Zend_Db_Expr('sum("qtGratuitaPopulacao") as "QtdeOutros"'),
            new Zend_Db_Expr('sum("qtPopularIntegral") as "QtdeVendaPopularNormal"'),
            new Zend_Db_Expr('sum("qtPopularParcial") as "QtdeVendaPopularPromocional"'),
            new Zend_Db_Expr('sum("vlUnitarioPopularIntegral") as "vlUnitarioPopularNormal"'),
            new Zend_Db_Expr('sum("vlReceitaPopularIntegral") "ReceitaPopularNormal"'),
            new Zend_Db_Expr('sum("vlReceitaPopularParcial") as "ReceitaPopularPromocional"'),
            new Zend_Db_Expr('sum("qtProponenteIntegral") as "QtdeVendaNormal"'),
            new Zend_Db_Expr('sum("qtProponenteParcial") as "QtdeVendaPromocional"'),
            new Zend_Db_Expr('avg("vlUnitarioProponenteIntegral") "vlUnitarioNormal"'),
            new Zend_Db_Expr('sum("vlReceitaProponenteIntegral") as "PrecoUnitarioNormal"'),
            new Zend_Db_Expr('sum("vlReceitaProponenteParcial") as "PrecoUnitarioPromocional"'),
            //'(sum(vlReceitaPopularParcial) + sum(vlReceitaPopularIntegral)+  sum(vlReceitaProponenteIntegral)+ sum(vlReceitaProponenteParcial)) as  PrecoUnitarioPromocional'
        );

        $sql = $this->select()
            ->setIntegrityCheck(false)
            ->from(
                array('tbDetalhaPlanoDistribuicao'),
                $cols,
                $this->_schema
            )
            ->where('idPlanoDistribuicao = ?', $idPlanoDistribuicao);
        $dados =  $this->fetchRow($sql);
        $dados = $dados->toArray();

        $this->update($dados, "idPlanoDistribuicao = " . $idPlanoDistribuicao);
    }

    public function buscarIdVinculada($idPreProjeto) {
        $sqlVinculada = "SELECT idOrgao as idVinculada
                                    FROM sac.dbo.PlanoDistribuicaoProduto t
                                    INNER JOIN vSegmento s on (t.Segmento = s.Codigo)
                                    WHERE t.stPrincipal = 1 and idProjeto = '{$idPreProjeto}'";

        $db = Zend_Db_Table::getDefaultAdapter();
        return $db->fetchOne($sqlVinculada);
    }
}
