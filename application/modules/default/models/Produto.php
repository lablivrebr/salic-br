<?php
class Produto extends MinC_Db_Table_Abstract
{
    protected $_schema = "sac";
    protected $_name = "Produto";
    protected $_primary = "Codigo";

    public function find()
    {
        $args = func_get_args();
        $rowset = parent::find($args);
        if (1 == count($args) && 0 == $args[0]) {
            $rowset = new Zend_Db_Table_Rowset(
                    array(
                        'data' => array(
                            array(
                                'Codigo' => 0,
                                'Descricao' => 'Livro',
                                'Area' => 0,
                                'Sintese' => 'Administra&ccedil;&atilde;o do Projeto',
                                'Idorgao' => 0,
                                'stEstado' => 0,
                            )
                        )
                    )
                );
        }
        return $rowset;
    }

    public function buscarProdutosContrato($idpronac){
        $select = $this->select();
        $select->setIntegrityCheck(false);
        $select->from(
                        array('prod'=>$this->_name),
                        array(
                                'id'=>'prod.Codigo','nome'=>'prod.Descricao'
                              )
                      );

        $select->joinInner(
                            array('pAprovacao'=>'tbPlanilhaAprovacao'),
                            'pAprovacao.idProduto = prod.Codigo',
                            array(),
                            'SAC'
                           );
        $select->joinLeft(
                            array('cxpa'=>'tbContratoxPlanilhaAprovacao'),
                            'pAprovacao.idPlanilhaAprovacao = cxpa.idPlanilhaAprovacao',
                            array(),
                            'bdcorporativo.scSAC'
                           );

        $select->where('pAprovacao.IdPRONAC = ?', $idpronac);
        $select->where('pAprovacao.stAtivo = ?','S');
        $select->where('cxpa.idContrato is null');

        $select->order('prod.Descricao');

        $select->group('prod.Codigo');
        $select->group('prod.Descricao');

        return $this->fetchAll($select);

    }
    public function buscarProdutosComprovacao($idpronac,$ckItens){
        $select = $this->select();
        $select->setIntegrityCheck(false);
        $select->from(
                        array('prod'=>$this->_name),
                        array(
                                'id'=>'prod.Codigo','nome'=>'prod.Descricao'
                              )
                      );
        $select->joinInner(
                            array('pAprovacao'=>'tbPlanilhaAprovacao'),
                            'pAprovacao.idProduto = prod.Codigo',
                            array(),
                            'SAC'
                           );

        $select->where('pAprovacao.IdPRONAC = ?', $idpronac);
        $select->where('pAprovacao.stAtivo = ?','S');

        /* erro 26
        if(is_array($ckItens) and count(is_array($ckItens))>0)
            $select->where('pAprovacao.idPlanilhaItem in ('.implode(',',$ckItens).')');
        */
        if(is_array($ckItens) and count(is_array($ckItens))>0)
            $select->where('pAprovacao.idPlanilhaAprovacao in ('.implode(',',$ckItens).')');

        $select->order('prod.Descricao');

        $select->group('prod.Codigo');
        $select->group('prod.Descricao');

        return $this->fetchAll($select);

    }

    public function buscarProdutos($idpronac){
        $select = $this->select();
        $select->setIntegrityCheck(false);
        $select->from(
                        array('prod'=>$this->_name),
                        array(
                                'id'=>'prod.Codigo','nome'=>'prod.Descricao'
                              )
                      );

        $select->joinInner(
                            array('pAprovacao'=>'tbPlanilhaAprovacao'),
                            'pAprovacao.idProduto = prod.Codigo',
                            array(),
                            'SAC'
                           );
        $select->joinLeft(
                            array('cxpa'=>'tbCotacaoxPlanilhaAprovacao'),
                            'pAprovacao.idPlanilhaAprovacao = cxpa.idPlanilhaAprovacao',
                            array(),
                            'bdcorporativo.scSAC'
                           );
        $select->joinLeft(
                            array('dlxpa'=>'tbDispensaLicitacaoxPlanilhaAprovacao'),
                            'pAprovacao.idPlanilhaAprovacao = dlxpa.idPlanilhaAprovacao',
                            array(),
                            'bdcorporativo.scSAC'
                           );
        $select->joinLeft(
                            array('lxpa'=>'tbLicitacaoxPlanilhaAprovacao'),
                            'pAprovacao.idPlanilhaAprovacao = lxpa.idPlanilhaAprovacao',
                            array(),
                            'bdcorporativo.scSAC'
                           );

        $select->where('pAprovacao.IdPRONAC = ?', $idpronac);
        $select->where('pAprovacao.stAtivo = ?','S');
        $select->where('cxpa.idCotacao is null');
        $select->where('dlxpa.idDispensaLicitacao is null');
        $select->where('lxpa.idLicitacao is null');

        $select->order('prod.Descricao');

        $select->group('prod.Codigo');
        $select->group('prod.Descricao');

        return $this->fetchAll($select);

    }


    public function carregarProdutos($idpronac,$idCotacao,$idDispensaLicitacao,$idLicitacao,$idContrato){
        $select = $this->select();
        $select->setIntegrityCheck(false);
        $select->from(
                        array('prod'=>$this->_name),
                        array(
                                'id'=>'prod.Codigo','nome'=>'prod.Descricao'
                              )
                      );

        $select->joinInner(
                            array('pAprovacao'=>'tbPlanilhaAprovacao'),
                            'pAprovacao.idProduto = prod.Codigo',
                            array(),
                            'SAC'
                           );
        if($idCotacao)
        $select->joinInner(
                            array('ctxpa'=>'tbCotacaoxPlanilhaAprovacao'),
                            "pAprovacao.idPlanilhaAprovacao = ctxpa.idPlanilhaAprovacao and ctxpa.idCotacao = '$idCotacao' ",
                            array(),
                            'bdcorporativo.scSAC'
                           );
        if($idDispensaLicitacao)
        $select->joinInner(
                            array('dlxpa'=>'tbDispensaLicitacaoxPlanilhaAprovacao'),
                            "pAprovacao.idPlanilhaAprovacao = dlxpa.idPlanilhaAprovacao and dlxpa.idDispensaLicitacao  = '$idDispensaLicitacao' ",
                            array(),
                            'bdcorporativo.scSAC'
                           );
        if($idLicitacao)
        $select->joinInner(
                            array('lxpa'=>'tbLicitacaoxPlanilhaAprovacao'),
                            "pAprovacao.idPlanilhaAprovacao = lxpa.idPlanilhaAprovacao and lxpa.idLicitacao  = '$idLicitacao' ",
                            array(),
                            'bdcorporativo.scSAC'
                           );
        if($idContrato)
        $select->joinInner(
                            array('cnxpa'=>'tbContratoxPlanilhaAprovacao'),
                            "pAprovacao.idPlanilhaAprovacao = cnxpa.idPlanilhaAprovacao and cnxpa.idContrato = '$idContrato' ",
                            array(),
                            'bdcorporativo.scSAC'
                           );


        $select->where('pAprovacao.IdPRONAC = ?',$idpronac);

        $select->order('prod.Descricao');

        $select->group('prod.Codigo');
        $select->group('prod.Descricao');

        return $this->fetchAll($select);

    }




}
