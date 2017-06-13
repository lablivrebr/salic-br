<?php

class MinC_Db_Adapter_Pdo_Pgsql extends Zend_Db_Adapter_Pdo_Pgsql
{
    public function query($sql, $bind = array())
    {
        if (empty($bind) && $sql instanceof Zend_Db_Select) {
            $bind = $sql->getBind();
        }

        if (is_array($bind)) {
            foreach ($bind as $name => $value) {
                if (!is_int($name) && !preg_match('/^:/', $name)) {
                    $newName = ":$name";
                    unset($bind[$name]);
                    $bind[$newName] = $value;
                }
            }
        }

        try {
            $this->_connect();
            if ($sql instanceof Zend_Db_Select) {
                if (empty($bind)) {
                    $bind = $sql->getBind();
                }
                $sql = $sql->assemble();
            }

            if (!is_array($bind)) {
                $bind = array($bind);
            }

            $sql = str_ireplace('.dbo', '', $sql);

            $stmt = $this->prepare($sql);
            $stmt->execute($bind);
            $stmt->setFetchMode($this->_fetchMode);

            return $stmt;
        } catch (PDOException $e) {
            require_once 'Zend/Db/Statement/Exception.php';
            throw new Zend_Db_Statement_Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function assemble()
    {
        $sql = self::SQL_SELECT;
        foreach (array_keys(self::$_partsInit) as $part) {
            $method = '_render' . ucfirst($part);
            if (method_exists($this, $method)) {
                $sql = $this->$method($sql);
            }
        }
        $sql = str_ireplace('.dbo', '', $sql);
        return $sql;
    }

    public function treatWhereConditionsDoubleQuotes($condition)
    {
        $colunaLimpa = trim($condition);
        $separator = '=';
        if ($colunaLimpa && is_int(strpos($colunaLimpa, $separator))) {
            $arrayColumn = explode($separator, $condition);
            if (substr(trim($arrayColumn[0]), 0, 1) != '"') {
                $column = '"' . trim($arrayColumn[0]) . '"';
                $condition = "{$column} {$separator} {$arrayColumn[1]}";
            }
        } elseif ($colunaLimpa && is_int(strpos($colunaLimpa, 'in'))) {
            $separator = 'in';
            $arrayColumn = explode($separator, $condition);
            if (substr(trim($arrayColumn[1]), 0, 1) == '(' && substr(trim($arrayColumn[0]), 0, 1) != '"') {
                $column = '"' . trim($arrayColumn[0]) . '"';
                $condition = "{$column} {$separator} {$arrayColumn[1]}";
            }
        }

        return $condition;
    }

    public function treatJoinConditionDoubleQuotes($condicao)
    {
        $arrayConditions = explode('AND', $condicao);
        if (count($arrayConditions) > 1) {
            $condicao = "";
            foreach ($arrayConditions as $arrayCondition) {
                if (!empty($condicao)) {
                    $condicao .= ' AND ';
                }
                $condicao .= $this->treatJoinConditionDoubleQuotes($arrayCondition);
            }
        } else {
            $condicaoLimpa = trim($condicao);
            $separator = '=';
            if ($condicaoLimpa && is_int(strpos($condicaoLimpa, $separator))) {
                $arrayColunas = explode($separator, $condicao);
                $coluna1 = $this->addDoubleQuote(trim($arrayColunas[0]));
                $coluna2 = $this->addDoubleQuote(trim($arrayColunas[1]));
                $condicao = "{$coluna1} {$separator} {$coluna2}";
            }

        }
        return $condicao;
    }

    protected function addDoubleQuote($field)
    {
        if (substr($field, 0, 1) != '"') {
            $hasDot = strpos($field, '.');
            if ($hasDot !== false) {
                $fieldPieces = explode('.', $field);
                $field = '';
                foreach ($fieldPieces as $fieldPiece) {
                    if (!empty($field)) {
                        $field .= '.';
                    }
                    $field .= $this->addDoubleQuote($fieldPiece);
                }
            } else {
                $field = '"' . $field . '"';
            }
        }
        return $field;
    }
}