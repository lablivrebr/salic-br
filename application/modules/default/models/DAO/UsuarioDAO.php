<?php

class UsuarioDAO extends MinC_Db_Table_Abstract
{
    protected $_name = 'Usuarios';
    protected $_primary = 'usu_codigo';
    protected $_schema = 'tabelas';

    /**
     * @param @username (cpf ou cnpj do usu�rio)
     * @param @password (senha do usu�rio criptografada)
     * @return bool
     */
    public static function login($username, $password)
    {
        $sql = "SELECT usu_codigo
					,usu_nome
					,usu_identificacao
					,usu_senha

				FROM TABELAS.Usuarios

				WHERE usu_identificacao = '" . $username . "'
					AND usu_senha = (SELECT TABELAS.fnEncriptaSenha('" . $username . "', '" . $password . "')
									 FROM TABELAS.Usuarios
									 WHERE usu_identificacao = '" . $username . "')
					AND usu_status = 1";

        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_DB::FETCH_OBJ);
        $buscar = $db->fetchAll($sql);

        if ($buscar)
        {
            $authAdapter = new Zend_Auth_Adapter_DbTable(Zend_Registry::get('db'));
            $authAdapter->setTableName('Usuarios')// TABELAS.Usuarios
            ->setIdentityColumn('usu_identificacao')
                ->setCredentialColumn('usu_senha');

            $authAdapter
                ->setIdentity($buscar[0]->usu_identificacao)
                ->setCredential($buscar[0]->usu_senha);

            $auth = Zend_Auth::getInstance();
            $acesso = $auth->authenticate($authAdapter);

            if ($acesso->isValid()) {
                // pega os dados do usu�rio com exce��o da senha
                $authData = $authAdapter->getResultRowObject(null, 'usu_senha');

                // armazena os dados do usu�rio
                $objAuth = $auth->getStorage()->write($authData);

                return true;
            }
        }
        return false;
    }


    /**
     * @param $username (cpf ou cnpj do usu�rio)
     * @param $password (nova senha do usu�rio)
     * @return object
     */
    public function alterarSenha($username, $password)
    {
        $sql = "UPDATE tabelas.Usuarios
					SET usu_senha = TABELAS.fnEncriptaSenha('" . $username . "', '" . $password . "')
				WHERE usu_identificacao = '" . $username . "'";

        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_DB::FETCH_OBJ);
        return $db->fetchAll($sql);
    }


    public function buscarUnidadesAutorizadas($usu_codigo, $sis_codigo = null, $gru_codigo = null, $uog_orgao = null)
    {
        $sql = "SELECT usu_orgao
					,usu_orgaolotacao
					,uog_orgao
					,org_siglaautorizado
					,org_nomeautorizado
					,gru_codigo
					,gru_nome
					,org_superior
					,uog_status

				FROM TABELAS.vwUsuariosOrgaosGrupos

				WHERE usu_codigo = $usu_codigo ";

        if (!empty($sis_codigo)) {
            $sql .= "AND sis_codigo = $sis_codigo ";
        }
        if (!empty($gru_codigo)) {
            $sql .= "AND gru_codigo = $gru_codigo ";
        }
        if (!empty($uog_orgao)) {
            $sql .= "AND uog_orgao = $uog_orgao ";
        }

        $sql .= "ORDER BY org_siglaautorizado";

        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_DB::FETCH_OBJ);
        return $db->fetchAll($sql);
    }

    public static function getIdUsuario($usu_codigo = null, $idUsuario = null)
    {
        $usuario = new UsuarioDAO();
        $query = $usuario->select();
        $query->from(
            ['usuarios' => $usuario->_name],
            [
                'usu_codigo'
            ],
            $usuario->getSchema('tabelas')
        );
        $query->joinInner(
            ['agentes' => 'Agentes'],
            'usuarios.usu_identificacao = agentes.CNPJCPF',
            ['idAgente'],
            $usuario->getSchema('agentes')
        );
        if($usu_codigo) {
            $query->where('usuarios.usu_codigo = ?', $usu_codigo);
        }
        if($idUsuario) {
            $query->where('usuarios.usu_codigo = ?', $idUsuario);
        }

        try {
            $db = Zend_Db_Table::getDefaultAdapter();
            $db->setFetchMode(Zend_DB::FETCH_ASSOC);
            return $db->fetchRow($query);
        } catch (Zend_Exception_Db $objException) {
            throw new Exception($objException->getMessage(), 0, $objException);
        }
    }

    public static function buscarUsuarioScriptcase($idusuario)
    {
        $objSGCAcesso = new Autenticacao_Model_Sgcacesso();
        return $objSGCAcesso->buscar(array('IdUsuario = ?' => $idusuario));
    }

    public static function buscarUsuario($cod)
    {
        $sql = "SELECT *
				FROM " . UsuarioDAO::getStaticTableName('tabelas', '.usuarios') . "
				WHERE usu_codigo = $cod";

        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_DB::FETCH_OBJ);

        return $db->fetchAll($sql);
    }

    public static function buscarUsuarioCpf($cpf)
    {
        $sql = "SELECT *
				FROM " . UsuarioDAO::getStaticTableName('tabelas', 'usuarios') . "
				WHERE usu_identificacao = $cpf";

        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_DB::FETCH_OBJ);
        return $db->fetchAll($sql);
    }

    public static function loginScriptcase($cod)
    {
        $arrUser = UsuarioDAO::buscarUsuarioScriptcase($cod)->current();
        if ($arrUser) {
            $arrUser = array_change_key_case(UsuarioDAO::buscarUsuarioScriptcase($cod)->current()->toArray());
        }

        if ($arrUser) {

            $authAdapter = new Zend_Auth_Adapter_DbTable();
            $objSgcAcesso = new Autenticacao_Model_Sgcacesso();
            $authAdapter->setTableName($objSgcAcesso->getTableName())
                ->setIdentityColumn('Cpf')
                ->setCredentialColumn('Senha');

            $authAdapter
                ->setIdentity($arrUser['cpf'])
                ->setCredential($arrUser['senha']);

            $auth = Zend_Auth::getInstance();
            $acesso = $auth->authenticate($authAdapter);

            if ($acesso->isValid()) {
                $authData = $authAdapter->getResultRowObject(null, 'senha');
                $auth->getStorage()->write($authData);

                return true;
            }
        }
    }
}
