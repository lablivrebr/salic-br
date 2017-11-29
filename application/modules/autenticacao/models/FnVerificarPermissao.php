<?php

class Autenticacao_Model_FnVerificarPermissao extends MinC_Db_Table_Abstract {

    protected $_name = 'SGCacesso';
    protected $_schema = 'controledeacesso';
    protected $_primary = 'Cpf';

    public function verificarPermissaoProjeto($idPronac, $idUsuarioLogado) {
        $select = new Zend_Db_Expr("SELECT sac.fnVerificarPermissao(2,'',$idUsuarioLogado,$idPronac) as Permissao");
        try {
            $db= Zend_Db_Table::getDefaultAdapter();
            $db->setFetchMode(Zend_DB::FETCH_OBJ);
        } catch (Zend_Exception_Db $e) {
            $this->view->message = $e->getMessage();
        }
        return $db->fetchRow($select);
    }

    /**
     * verificarPermissaoProposta
     *
     * @param mixed $idPreProjeto
     * @param mixed $idUsuarioLogado
     * @access public
     * @return void
     * @todo Verificar local para metodo.
     * sac.fnVerificarPermissao --> SP removida
     */
    public function verificarPermissaoProposta($idPreProjeto, $idUsuarioLogado)
    {
        $db = Zend_Db_Table::getDefaultAdapter();

        $acao = 1;
        $cpfCnpjProponente = 0;
        $permissao = 0;

        $PreProjeto = new Proposta_Model_DbTable_PreProjeto();
        $PreProjeto = $PreProjeto->buscaCompleta(array('idPreProjeto = ?' => $idPreProjeto));
        $cpfCnpjProponente = $PreProjeto[0]->CNPJCPF;

        //SELECT @CPF_Logado = CPF FROM controledeacesso.SGCacesso WHERE IdUsuario = @idUsuario_Logado
        $sql = $this->select()
            ->from('SGCacesso', 'Cpf', $this->getSchema('controledeacesso'))
            ->where('IdUsuario = ?', $idUsuarioLogado)
        ;
        $cpfLogado = $db->fetchOne($sql);

        //VERIFICAR SE O CPF LOGADO ESTA CADASTRADO NO BANCO AGENTES
        $sql = $this->select()
            ->from('Agentes', 'idAgente', $this->getSchema('agentes'))
            ->where('CNPJCPF = ?', $cpfLogado)
        ;
        $idAgenteLogado = $db->fetchRow($sql);

        $idAgenteLogado = empty($idAgenteLogado) ? 0 : $idAgenteLogado;

         //PEGAR ID DO PROPONENTE E O TIPO DE PESSOA
        $sql = $this->select()
            ->from('Agentes', array('idAgente','TipoPessoa'), $this->getSchema('agentes'))
            ->where('CNPJCPF = ?', $cpfLogado)
        ;

        $proponente = $db->fetchRow($sql);
        $proponente = empty($proponente) ? 0 :$proponente ;


        switch($acao){
        //-- CHECAR PERMISSAO PARA ADMINISTRATIVO
        case 0 :
            if ($proponente['TipoPessoa'] == 0) {
                if ($cpfLogado == $cpfCnpjProponente) {
                    $permissao = 1;
                } else {
                    $permissao = 0;
                }
            } else {
                $sql = $this->select()
                    ->from(array('a' => 'Vinculacao'), null, $this->getSchema('agentes'))
                    ->join(array('b' => 'Agentes'), '(a.idAgente = b.idAgente)', 'b.CNPJCPF' ,$this->getSchema('agentes'))
                    ->join(array('c' => 'Agentes'), '(a.idVinculoprincipal = c.idAgente)', null, $this->getSchema('agentes'))
                    ->join(array('d' => 'Visao'), '(d.idAgente = a.idAgente)', null,$this->getSchema('agentes'))
                    ->where('b.CNPJCPF = ?', $cpfLogado)
                    ->where('c.CNPJCPF = ?', $cpfCnpjProponente)
                    ->where('d.Visao = 198')
                    ;

                $cpfDirigente = $db->fetchRow($sql);

                if (!empty($cpfDirigente)) {
                    if($cpfLogado == $cpfDirigente['CNPJCPF']) {
                        $permissao = 1;
                    } else {
                        $permissao = 0;
                    }
                } else {
                    $permissao = 0;
                }
            }
            break;
        case 1:
            $sql = $this->select()
                ->from(array('a' => 'PreProjeto'), array('a.idAgente', 'a.idUsuario'), $this->getSchema('sac'))
                ->join(array('b' => 'Agentes'), 'a.idAgente = b.idAgente', array('b.CNPJCPF', 'b.TipoPessoa'), $this->getSchema('agentes'))
                ->where('idPreProjeto = ?', $idPreProjeto)
                ;
//xd($sql->assemble());
            $agente = $db->fetchRow($sql);

            $permissao = 0;
            if (is_object($agente) && $agente->TipoPessoa == 0) {
                if ($cpfLogado == $agente->CNPJCPF ||  $agente->idUsuario == $idUsuarioLogado) {
                    $permissao = 1;
                }
            } elseif (is_array($agente) && $agente['TipoPessoa'] == 0) {
                if ($cpfLogado == $agente['CNPJCPF'] ||  $agente['idUsuario'] == $idUsuarioLogado) {
                    $permissao = 1;
                }
            } else {
                $sql = $this->select()
                    ->from(array('a' => 'Vinculacao'), null, $this->getSchema('agentes'))
                    ->join(array('b' => 'Agentes'), '(a.idAgente = b.idAgente)', 'b.CNPJCPF', $this->getSchema('agentes'))
                    ->join(array('c' => 'Agentes'), '(a.idVinculoprincipal = c.idAgente)', null, $this->getSchema('agentes'))
                    ->join(array('d' => 'Visao'), '(d.idAgente = a.idAgente)', null, $this->getSchema('agentes'))
                    ->where('b.CNPJCPF = ?', $cpfLogado )
                    ->where('c.CNPJCPF = ?', $cpfCnpjProponente)
                    ->where('d.Visao = 198')
                    ;

                    $dirigenteCpf = $db->fetchOne($sql);

                    if (!empty($dirigenteCpf)) {
                       //IF @CPF_Logado = @CPF_Dirigente or @idUsuario_Responsavel = @idUsuario_Logado
                        if ($cpfLogado == $dirigenteCpf || $agente['idUsuario'] == $idUsuarioLogado) {
                            $permissao = 1;
                        } else {
                            $permissao = 0;
                        }
                    } else {
                        $permissao = 0;
                    }

                    if ($permissao == 0) {
                        $sql = $this->select()
                            ->from(array('a' => 'PreProjeto'), 'a.idAgente', $this->getSchema('sac'))
                            ->join(array('b' => 'Agentes'), '(a.idAgente = b.idAgente)', null, $this->getSchema('agentes'))
                            ->join(array('c' => 'tbVinculoProposta'), '(a.idPreProjeto = c.idPreProjeto)', null, $this->getSchema('agentes'))
                            ->join(array('d' => 'tbVinculo'), '(c.idVinculo = d.idVinculo)', null, $this->getSchema('agentes'))
                            ->join(array('e' => 'SGCacesso'), '(d.idUsuarioResponsavel = e.idUsuario)', null, $this->getSchema('controledeacesso'))
                            ->where('c.siVinculoProposta = 2')
                            ->where('e.IdUsuario = ?', $idUsuarioLogado)
                            ->where('a.idPreProjeto = ?', $idPreProjeto)
                        ;

                        $idAgente = $db->fetchRow($sql);

                        if (!empty($idAgente)) {
                            $permissao = 1;
                        }
                    }
            }
            break;
        case 2 :

            $sql = $this->select()
                ->from(array('a' => 'Projetos'), '(a.AnoProjeto+a.Sequencial) as pronac', $this->getSchema('sac'))
                ->join(array('b' => 'Agentes'), '(a.CgcCpf = b.CNPJCPF)', null , $this->getSchema('agentes'))
                ->join(array('c' => 'sgcacesso'), '(a.CgcCpf = c.Cpf)', null, $this->getSchema('controledeacesso'))
                ->where('c.IdUsuario = ?', $idUsuarioLogado)
                ->where('a.IdPRONAC = ?', $idPreProjeto)
                ;
            $pronac = $db->fetchRow($sql);

            if (!empty($pronac)){
                $permissao = 1;
            } else {

                $sql = $this->select()
                    ->from(array('a' => 'Projetos'), '(a.AnoProjeto+a.Sequencial) as pronac', $this->getSchema('sac'))
                    ->join(array('b' => 'Agentes'), '(a.CgcCpf = b.CNPJCPF)', null , $this->getSchema('agentes'))
                    ->join(array('c' => 'tbProcuradorProjeto'), '(a.IdPRONAC = c.idPronac)', null , $this->getSchema('agentes'))
                    ->join(array('d' => 'tbProcuracao'), '(c.idProcuracao = d.idProcuracao)', null , $this->getSchema('agentes'))
                    ->join(array('f' => 'Agentes'), '(d.idAgente = f.idAgente)', null , $this->getSchema('agentes'))
                    ->join(array('e' => 'SGCacesso'), '(f.CNPJCPF = e.Cpf)', null , $this->getSchema('controledeacesso'))
                    ->where('c.siEstado = 2')
                    ->where('e.IdUsuario = ?', $idUsuarioLogado)
                    ->where('a.IdPRONAC = ?', $idPreProjeto)
                    ;

                $pronac = $db->fetchRow($sql);
                if (!empty($pronac)){
                    $permissao =1;
                } else {
                    $sql = $this->select()
                        ->from(array('a' => 'Projetos'), '(a.AnoProjeto+a.Sequencial) as pronac', $this->getSchema('sac'))
                        ->join(array('b' => 'Agentes'), '(a.CgcCpf = b.CNPJCPF)', null , $this->getSchema('agentes'))
                        ->join(array('c' => 'vinculacao'), '(b.idAgente = c.idVinculoprincipal)', null , $this->getSchema('agentes'))
                        ->join(array('d' => 'Agentes'), '(c.idAgente = d.idAgente)', null , $this->getSchema('agentes'))
                        ->join(array('e' => 'SGCacesso'), '(d.CNPJCPF = e.Cpf)', null , $this->getSchema('controledeacesso'))
                        ->where('e.IdUsuario = ?', $idUsuarioLogado)
                        ->where('a.IdPRONAC = ?', $idPreProjeto)
                        ;

                    $pronac = $db->fetchRow($sql);

                    if (!empty($pronac)){
                        $permissao = 1;
                    } else {
                        $permissao = 0;
                    }
                }
            }
            break;
        case 3 :
            $sql = $this->select()
                ->from(array('vwUsuariosOrgaosGrupos'), 'usu_codigo', $this->getSchema('tabelas'))
                ->where('sis_codigo = 21')
                ->where('usu_codigo = ?', $idUsuarioLogado)
                ->where('uog_status = 1')
                ->limit(1)
                ;

            $codigo = $db->fetchRow($sql);

            if (!empty($codigo)) {
                $permissao = 1;
            } else {
                $permissao = 0;
            }
            break;
        }
        $perm = new stdClass();
        $perm->Permissao = ($permissao == 0) ? 0 : 1;

        return  $perm;
    }

    public function verificarPermissaoAdministrativo($idUsuarioLogado) {
        $select = new Zend_Db_Expr("SELECT sac.fnVerificarPermissao(0,'',$idUsuarioLogado,'') as Permissao");
        try {
            $db= Zend_Db_Table::getDefaultAdapter();
            $db->setFetchMode(Zend_DB::FETCH_OBJ);
        } catch (Zend_Exception_Db $e) {
            $this->view->message = $e->getMessage();
        }
        return $db->fetchRow($select);
    }

}

