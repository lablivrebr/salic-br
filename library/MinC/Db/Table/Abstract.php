<?php
require_once 'Zend/Db/Table/Abstract.php';

abstract class MinC_Db_Table_Abstract extends Zend_Db_Table_Abstract
{

    private $_config;
    protected $_rowClass = "MinC_Db_Table_Row";
    protected $debugMode = false;

    public function init()
    {
        $this->setName($this->getName($this->_name));
        if(isset($this->_banco)) {
            $this->setDatabase($this->getBanco($this->_banco));
        }
        $this->setSchema($this->getSchema($this->_schema));
    }

    public function setSchema($strSchema)
    {
        $this->_schema = $strSchema;
    }

    public function setName($name)
    {
        $this->_name = $name;
    }

    public function setDatabase($banco)
    {
        $this->_banco = $banco;
    }

    public function getSchema($strSchema = null, $isReturnDb = true, $strNameDb = null)
    {
        $db = Zend_Db_Table::getDefaultAdapter();

        if ($db instanceof Zend_Db_Adapter_Pdo_Mssql) {
            if (is_null($strNameDb)) {
                $strNameDb = 'dbo';
            }

            if ($isReturnDb && strpos($strSchema, '.') === false) {
                if ($strSchema) {
                    $strSchema = $strSchema . "." . $strNameDb;
                } else {
                    $strSchema = $strNameDb;
                }
            } elseif (strpos($strSchema, '.') === false) {
                $strSchema = $strNameDb;
            }
        } else if (!$strSchema) {
            $strSchema = $this->_schema;
        }

        return $strSchema;
    }


    public function setDebugMode($boolean)
    {
        $this->debugMode = $boolean;
    }

    public function getPrimary()
    {
        return (isset($this->_primary)) ? $this->_primary : '';
    }

    public function getSequence()
    {
        return (isset($this->_sequence)) ? $this->_sequence : true;
    }

    /**
     * @todo verificar um tipo de SET TEXTSIZE 2147483647 para usar com o Postgres tambem.
     */
    public function __construct()
    {
        $this->createConnection();
        parent::__construct();
    }

    protected function _setupTableName()
    {
        if (! $this->_name) {
            $this->_name = get_class($this);
        }
    }

    private function createConnection()
    {
        $dbAdapter = Zend_Db_Table::getDefaultAdapter();

        $db = Zend_Db_Table::getDefaultAdapter();
        $arrConfig = $db->getConfig();
        if ($dbAdapter instanceof Zend_Db_Adapter_Pdo_Mssql && strtolower($arrConfig['dbname']) != strtolower($this->_schema)) {
            if (!empty($dbAdapter)) {
                $dbAdapter->closeConnection();
                unset ($dbAdapter);
            }

            if (!($this->_config instanceof Zend_Config_Ini)) {

                $db = Zend_Db_Table::getDefaultAdapter();
                $arrConfig = $db->getConfig();
                $strDb = str_replace(array('.scAGENTES', '.scSAC', '.dbo', '.scdne', '.scsac', '.scQuiz'), '', $this->_schema);
                $strDb = str_replace('.sccorp', '', strtolower($strDb));

                $arrConfig['dbname'] = strtoupper($strDb);

                $this->_config = new Zend_Config(
                    array_merge(
                        Zend_Registry::get('config')->toArray(),
                        array(
                            'db' => array(
                                'adapter' => 'PDO_MSSQL',
                                'params' => array(
                                    'username' => $arrConfig['username'],
                                    'password' => $arrConfig['password'],
                                    'dbname' => $arrConfig['dbname'],
                                    'host' => $arrConfig['host'],
                                    'port' => $arrConfig['port'],
                                    'charset' => $arrConfig['charset'],
                                )
                            )
                        )
                    )
                );

                Zend_Registry::getInstance()->set('config', $this->_config);
                Zend_Db_Table::setDefaultAdapter(Zend_Db::factory($this->_config->db));

                # Setar o campo texto maior que 4096 caracteres aceitaveis por padrao no PHP
                //$db->query(' SET TEXTSIZE 2147483647 ');
                ini_set('memory_limit', '-1');
            }
        } else if (is_int(strpos($this->_schema, 'scdne'))) {
            $this->_schema = str_replace('.scdne', '', $this->_schema);
        } else if (is_int(strpos($this->_schema, 'scCorp'))) {
            $this->_schema = str_replace('.scCorp', '', $this->_schema);
        } else if (is_int(strpos($this->_schema, 'scsac'))) {
            $this->_schema = str_replace('.scsac', '', $this->_schema);
        } else if (is_int(strpos($this->_schema, 'scSAC'))) {
            $this->_schema = str_replace('.scSAC', '', $this->_schema);
        }
    }

    /**
     * @param string $strName
     * @return string
     *
     * @todo melhorar e amadurecer codigo
     */
    public function getBanco($strName = '')
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $strName = 'dbo';

        if (!($db instanceof Zend_Db_Adapter_Pdo_Mssql)) {
            $arrayConfiguracaoes = $db->getConfig();
            $strName = $arrayConfiguracaoes['dbname'];
        }

        return $strName;
    }

    /**
     * @param string $strName
     * @param string $strSchema
     * @return string
     * @todo melhorar e amadurecer codigo
     */
    public function getName($strName = '', $strSchema = '')
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        if (!($db instanceof Zend_Db_Adapter_Pdo_Pgsql)) {
            $strName = strtolower($strName);
        }
        return $strName;
    }

    public function getTableName($schema = null, $tableName = null, $isReturnDb = true)
    {
        if ($schema === null) $schema = $this->getSchema($schema, $isReturnDb);
        if ($tableName === null) $tableName = $this->_name;

        return $schema . '.' . $this->getName($tableName);
    }

    public static function getStaticTableName($schema = null, $tableName = null)
    {
        $db = Zend_Db_Table::getDefaultAdapter();

        if ($db instanceof Zend_Db_Adapter_Pdo_Mssql && $schema) {
            $arrayPedacos = explode('.', $schema);
            if (count($arrayPedacos) <= 1) {
                $schema = $schema . '.dbo';
            }
        }

        return $schema . $tableName;
    }

    public function __destruct()
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->closeConnection();
    }

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
        $select = $this->select()->from($this->_name, $this->_getCols(), $this->_schema);
        $db = Zend_Db_Table::getDefaultAdapter();
        //adiciona quantos filtros foram enviados
        foreach ($where as $condicao => $valor) {
            if (!is_null($valor)) {
                $select->where($condicao, $valor);
            }
        }

        $select->order($order);

        // paginacao
        if ($tamanho > -1) {
            $tmpInicio = 0;
            if ($inicio > -1) {
                $tmpInicio = $inicio;
            }
            $select->limit($tamanho, $tmpInicio);
        }

        if ($this->debugMode === true) {
            xd($select->assemble());
        }

        return $this->fetchAll($select);
    }

    public function alterar($dados, $where)
    {
        if ($this->debugMode === true) {
            xd($this->dbg($dados, $where));
        }
        $update = $this->update($dados, $where);
        return $update;
    }

    public function apagar($where)
    {
        $delete = $this->delete($where);
        return $delete;
    }

    public function inserir($dados, $dbg = null)
    {
        if ($dbg) {
            xd($this->dbg($dados));
        }
        $insert = $this->insert($dados);
        return $insert;
    }

    public function dbg($dados, $where = null)
    {
        if (!$where) {
            $sql = "INSERT INTO " . $this->_name . " (";
            $keys = array_keys($dados);
            $sql .= implode(',', $keys);
            $sql .= ")\n values ('";
            $values = array_values($dados);
            $sql .= implode("','", $values);
            $sql .= "');";
        } else {
            $sql = "UPDATE " . $this->_name . " SET ";
            foreach ($dados as $coluna => $valor) {
                $sql .= $coluna . " = '" . $valor . "', \n";
            }
            $sql .= "\n" . $where;
        }
        xd($sql);
    }

    /**
     * @param Zend_Db_Table_Abstract ::SELECT_WITHOUT_FROM_PART $withFromPart
     * @return MinC_Db_Table_Select
     */
    public function select($withFromPart = self::SELECT_WITHOUT_FROM_PART)
    {
        require_once 'Zend/Db/Table/Select.php';
        $select = new MinC_Db_Table_Select($this);
        if ($withFromPart == self::SELECT_WITH_FROM_PART) {
            $select->from($this->info(self::NAME), $this->_getCols(), $this->info(self::SCHEMA));
        }

        return $select;
    }

    /**
     * @name findBy
     * @param array $where
     * @return array
     */
    public function findBy($where)
    {
        $select = $this->select();
        $select->setIntegrityCheck(false);
        $select->from($this->_name, $this->_getCols(), $this->_schema);
        self::setWhere($select, $where);
        $result = $this->fetchRow($select);
        return ($result) ? $result->toArray() : array();
    }

    /**
     * Metodo responsavel por adicionar o where dinamicamente no objeto select.
     * Altera o proprio select, usando o select como referencia e nao retornando nada.
     *
     * @name setWhere
     * @param $select - Objeto select da query montada para no final por os parametros do where.
     * @param $where - Array ou string onde a string e considerado uma pk.
     * @return void
     */
    public function setWhere(
        &$select,
        $where,
        $typeWhere = 'where'
    )
    {
        if (is_array($where)) {
            foreach ($where as $columnName => $columnValue) {
                if (is_int(strpos($columnName, '?'))) {
                    if (is_array($columnValue)) {
                        $select->{$typeWhere}($columnName, $columnValue);
                    } else {
                        $select->{$typeWhere}($columnName, trim($columnValue));
                    }
                } elseif (!$columnValue) {
                    $select->{$typeWhere}($columnName);
                } else {
                    $select->{$typeWhere}($columnName . ' = ?', trim($columnValue));
                }
            }
        } else {
            $select->{$typeWhere}(reset($this->getPrimary()) . ' = ?', trim($where));
        }
    }

    /**
     * Deleta varios registros conforme o where.
     * @name deleteBy
     * @param array $arrWhere - Array com os parametros para o where, onde chave do array e a coluna da tabela e o valor do array e o valor da coluna.
     * @return int - Quantidade de rows deletadas.
     * @throws Exception
     */
    public function deleteBy(array $arrWhere)
    {

        if (empty($arrWhere)) throw new Exception('Parametro where vazio, voce nao vai querer deletar tudo da tabela vai?');
        $arrWhereNew = array();
        foreach ($arrWhere as $columnName => $columnValue) {
            $arrWhereNew[] = $this->getAdapter()->quoteInto($columnName . ' = ?', trim($columnValue));
        }
        return $this->delete($arrWhereNew);
    }

    /**
     * @name findAll
     * @param array $where
     * @return array
     */
    public function findAll(array $where = array(), array $order = array())
    {
        $select = $this->select();
        $select->setIntegrityCheck(false);

        self::setWhere($select, $where);
        if ($order) {
            $select->order($order);
        }
        $result = $this->fetchAll($select);
        return ($result) ? $result->toArray() : array();
    }

    /**
     * @param Zend_Db_Table_Abstract ::SELECT_WITHOUT_FROM_PART $withFromPart
     * @return MinC_Db_Table_Select
     * @return string
     */
    public static function getConcatExpression()
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        if ($db instanceof Zend_Db_Adapter_Pdo_Mssql) {
            return ' + ';
        }
        return " || ";
    }

    public function getExpressionDate()
    {
        return new Zend_Db_Expr($this->getDate());
    }

    public function getDate()
    {
        if ($this->getAdapter() instanceof Zend_Db_Adapter_Pdo_Mssql) {
            return 'GETDATE()';
        }
        return 'NOW()';
    }

    public function getExpressionConcat()
    {
        if ($this->getAdapter() instanceof Zend_Db_Adapter_Pdo_Mssql) {
            return ' + ';
        }
        return ' || ';
    }

    public function getExpressionToChar($strColumn, $strFormat = 'DD/MM/YYYY ')
    {
        if ($this->getAdapter() instanceof Zend_Db_Adapter_Pdo_Mssql) {
            if (is_int($strFormat)) {
                return new Zend_Db_Expr('CONVERT(CHAR(30), ' . $strColumn . ' , ' . $strFormat . ')');
            } else {
                return new Zend_Db_Expr('CONVERT(CHAR(10), ' . $strColumn . ' , 103)');
            }
        } else {
            return new Zend_Db_Expr('to_char("' . $strColumn . '", \'' . $strFormat . '\')');
        }
    }

    public function getExpressionLimit($intLimit = 1)
    {
        if ($this->getAdapter() instanceof Zend_Db_Adapter_Pdo_Mssql) {
            return new Zend_Db_Expr("TOP {$intLimit}");
        } else {
            return new Zend_Db_Expr("LIMIT {$intLimit}");
        }
    }

    public function getExpressionTrim($string, $strAlias = null)
    {
        $strTrim = 'ltrim';
        if (!($this->getAdapter() instanceof Zend_Db_Adapter_Pdo_Mssql)) {
            $strTrim = 'trim';
            if (strpos($string, '.')) {
                $string = preg_replace("/(.*)\\.(.*)/", '$1"."$2', $string);
            }
        }

        if (!is_null($strAlias)) {
            $strAlias = "  as {$strAlias}";
        } 
        
        return new Zend_Db_Expr($strTrim . '("' . $string . '")' . $strAlias);
    }

    public function getExpressionDateDiff(
        $strColumn,
        $strColumnTwo,
        $strDatePart = 'day'
    )
    {
        if ($this->getAdapter() instanceof Zend_Db_Adapter_Pdo_Mssql) {
            return new Zend_Db_Expr("DATEDIFF ( {$strDatePart} , {$strColumn} , {$strColumnTwo})");
        }
        return new Zend_Db_Expr("DATE_PART('{$strDatePart}', \"{$strColumn}\") - DATE_PART('{$strDatePart}', \"{$strColumnTwo}\")");
    }

    public function getExpressionDatePart($strColumn, $strDatePart = 'month')
    {
        if ($this->getAdapter() instanceof Zend_Db_Adapter_Pdo_Mssql) {
            return new Zend_Db_Expr("{$strDatePart}({$strColumn})");
        }
        return new Zend_Db_Expr("DATE_PART('{$strDatePart}', \"{$strColumn}\")");
    }


    /**
     * Retorna o resultado com chave e valor apenas.
     * @name fetchPairs
     * @param string $key
     * @param string $value
     * @param array $where
     * @param string $order
     * @return array
     */
    public function fetchPairs(
        $key,
        $value,
        array $where = [],
        $order = ''
    )
    {
        if (empty($order)) $order = $value;

        $select = $this->select();
        if (empty($select->assemble())) {
            $select->from($this->_name, $select::SQL_WILDCARD, $this->_schema);
        }
        $select->setIntegrityCheck(false);
        $select = $this->_order($select, $order);

        foreach ($where as $column => $columnValue) {
            if (is_array($columnValue)) {
                $select->where($column . ' in (?)', $columnValue);
            } else {
                $select->where($column . ' = ?', $columnValue);
            }
        }

        $resultSet = $this->fetchAll($select);
        $resultSet = ($resultSet) ? $resultSet->toArray() : array();
        $entries = array();
        foreach ($resultSet as $row) {
            $entries[$row[$key]] = $row[$value];
        }
        return $entries;
    }

    public function fetchRow($where = null, $order = null)
    {
        if (!($where instanceof Zend_Db_Table_Select)) {
            $select = $this->select();

            if ($where !== null) {
                $select = $this->_where($select, $where);
            }

            if ($order !== null) {
                $select = $this->_order($select, $order);
            }

            if (empty($select->assemble())) {
                $select->from($this->_name, $select::SQL_WILDCARD, $this->_schema);
            }
            $select->limit(1);
        } else {
            $select = $where->limit(1);
        }
        $rows = $this->_fetch($select);

        if (count($rows) == 0) {
            return null;
        }

        $data = array(
            'table' => $this,
            'data' => $rows[0],
            'readOnly' => $select->isReadOnly(),
            'stored' => true
        );

        $rowClass = $this->getRowClass();
        if (!class_exists($rowClass)) {
            require_once 'Zend/Loader.php';
            Zend_Loader::loadClass($rowClass);
        }
        return new $rowClass($data);
    }

    /**
     * Deletes existing rows.
     *
     * @param  array|string $where SQL WHERE clause(s).
     * @return int          The number of rows deleted.
     */
    public function delete($where)
    {
        if ($this->getAdapter() instanceof MinC_Db_Adapter_Pdo_Pgsql) {
            $where = $this->getAdapter()->treatConditionDoubleQuotes($where);
        }
        return parent::delete($where);
    }

    /**
     * Updates existing rows.
     *
     * @param  array        $data  Column-value pairs.
     * @param  array|string $where An SQL WHERE clause, or an array of SQL WHERE clauses.
     * @return int          The number of rows updated.
     */
    public function update(array $data, $where)
    {
        if ($this->getAdapter() instanceof MinC_Db_Adapter_Pdo_Pgsql) {
            $where = $this->getAdapter()->treatConditionDoubleQuotes($where);
            $data = $this->getAdapter()->treatBooleanValues($data);
        }
        return parent::update($data, $where);
    }

    /**
     * Inserts a new row.
     *
     * @param  array  $data  Column-value pairs.
     * @return mixed         The primary key of the row inserted.
     */
//    public function insert(array $data)
//    {
//        if ($this->getAdapter() instanceof MinC_Db_Adapter_Pdo_Pgsql) {
////            $newDataArray = [];
////            foreach($data as $key => $value) {
////                $newDataArray[$this->getAdapter()->treatColumnsDoubleQuotes($key, false)] = $value;
////            }
////            $data = $newDataArray;
//
////            $data = $this->getAdapter()->treatColumnsDoubleQuotes($data);
//        }
//        return parent::insert($data);
//    }


    /**
     * Fetches rows by primary key.  The argument specifies one or more primary
     * key value(s).  To find multiple rows by primary key, the argument must
     * be an array.
     *
     * This method accepts a variable number of arguments.  If the table has a
     * multi-column primary key, the number of arguments must be the same as
     * the number of columns in the primary key.  To find multiple rows in a
     * table with a multi-column primary key, each argument must be an array
     * with the same number of elements.
     *
     * The find() method always returns a Rowset object, even if only one row
     * was found.
     *
     * @param  mixed $key The value(s) of the primary keys.
     * @return Zend_Db_Table_Rowset_Abstract Row(s) matching the criteria.
     * @throws Zend_Db_Table_Exception
     */
    public function find()
    {
       $this->_setupPrimaryKey();
        $args = func_get_args();
        $keyNames = array_values((array) $this->_primary);

        if (count($args) < count($keyNames)) {
            require_once 'Zend/Db/Table/Exception.php';
            throw new Zend_Db_Table_Exception("Too few columns for the primary key");
        }

        if (count($args) > count($keyNames)) {
            require_once 'Zend/Db/Table/Exception.php';
            throw new Zend_Db_Table_Exception("Too many columns for the primary key");
        }

        $whereList = array();
        $numberTerms = 0;
        foreach ($args as $keyPosition => $keyValues) {
            $keyValuesCount = count($keyValues);
            if (!is_array($keyValues)) {
                $keyValues = array($keyValues);
            }
            if ($numberTerms == 0) {
                $numberTerms = $keyValuesCount;
            } else if ($keyValuesCount != $numberTerms) {
                require_once 'Zend/Db/Table/Exception.php';
                throw new Zend_Db_Table_Exception("Missing value(s) for the primary key");
            }
            $keyValues = array_values($keyValues);
            for ($i = 0; $i < $keyValuesCount; ++$i) {
                if (!isset($whereList[$i])) {
                    $whereList[$i] = array();
                }
                $whereList[$i][$keyPosition] = $keyValues[$i];
            }
        }

        $whereClause = null;
        if (count($whereList)) {
            $whereOrTerms = array();
            $tableName = $this->_db->quoteTableAs($this->_name, null, true);
            foreach ($whereList as $keyValueSets) {
                $whereAndTerms = array();
                foreach ($keyValueSets as $keyPosition => $keyValue) {
                    $type = $this->_metadata[$keyNames[$keyPosition]]['DATA_TYPE'];
                    $columnName = $this->_db->quoteIdentifier($keyNames[$keyPosition], true);
                    $whereAndTerms[] = $this->_db->quoteInto(
                        $tableName . '.' . $columnName . ' = ?',
                        $keyValue, $type);
                }
                $whereOrTerms[] = implode(' AND ', $whereAndTerms);
            }
            $whereClause = implode(' OR ', $whereOrTerms) ;
        }

        $select = $this->select()
            ->from($this->_name, $this->_getCols(), $this->_schema);
        $select->where($whereClause);

        return $this->fetchAll($select);
    }
}
