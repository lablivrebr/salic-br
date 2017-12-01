<?php

class Proposta_Model_DbTable_TbDeslocamento extends MinC_Db_Table_Abstract
{
    protected $_schema = 'sac';
    protected $_name = 'tbDeslocamento';
    protected $_primary = 'idDeslocamento';

    public function buscarDeslocamentosGeral($where = array(), $order = array(), $arrNot = array())
    {
        $select = $this->select();
        $select->setIntegrityCheck(false);
        $select->from(array('de' => $this->_name), '*', $this->_schema)
            ->joinLeft(array('pao' => 'Pais'), 'de.idPaisOrigem = pao.idPais', array('pao.Continente as continenteorigem', 'pao.Descricao as paisorigem'), $this->getSchema('agentes'))
            ->joinLeft(array('ufo' => 'UF'), 'de.idUFOrigem = ufo.idUF', array('ufo.Descricao as uforigem'), $this->getSchema('agentes'))
            ->joinLeft(array('muo' => 'Municipios'), 'de.idMunicipioOrigem::varchar(6) = muo.idMunicipioIBGE', array('muo.Descricao as municipioorigem'), $this->getSchema('agentes'))
            ->joinLeft(array('pad' => 'Pais'), 'de.idPaisDestino = pad.idPais', array('pad.Continente as continentedestino', 'pad.Descricao as paisodestino'), $this->getSchema('agentes'))
            ->joinLeft(array('ufd' => 'UF'), 'de.idUFDestino = ufd.idUF', array('ufd.Descricao as ufdestino'), $this->getSchema('agentes'))
            ->joinLeft(array('mud' => 'Municipios'), 'de.idMunicipioDestino::varchar(6) = mud.idMunicipioIBGE', array('mud.Descricao as municipiodestino'), $this->getSchema('agentes'));
        foreach ($where as $coluna => $valor) {
            $select->where($coluna . ' = ?', $valor);
        }
        foreach ($arrNot as $coluna => $valor) {
            if (!empty($valor)) {
                $select->where($coluna . ' <> ?', $valor);
            }
        }
        if ($order) {
            $select->order($order);
        }
        $arrResult = $this->fetchAll($select);
        return ($arrResult) ? $arrResult->toArray() : array();
    }

    public function buscarDeslocamento($idProjeto, $idDeslocamento = null)
    {
        $agenteSchema = $this->getSchema('agentes');
        $de = array(
            'de.idDeslocamento',
            'de.idProjeto',
            'de.idPaisOrigem',
            'de.idUFOrigem',
            'de.idMunicipioOrigem',
            'de.idPaisDestino',
            'de.idUFDestino',
            'de.Qtde',
            'de.idUsuario',
            'de.idMunicipioDestino'
        );

        $sql = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('de' => $this->_name), $de, $this->_schema)
            ->joinLeft(array('pao' => 'Pais'), 'de.idPaisOrigem = pao.idPais', 'pao.Descricao as po', $agenteSchema)
            ->joinLeft(array('ufo' => 'UF'), 'de.idUFOrigem = ufo.idUF', 'ufo.Descricao as ufo', $agenteSchema)
            ->joinLeft(array('muo' => 'Municipios'), 'de.idMunicipioOrigem::varchar(6) = muo.idMunicipioIBGE', 'muo.Descricao as muo', $agenteSchema)
            ->joinLeft(array('pad' => 'Pais'), 'de.idPaisDestino = pad.idPais', 'pad.Descricao as pd', $agenteSchema)
            ->joinLeft(array('ufd' => 'UF'), 'de.idUFDestino = ufd.idUF', 'ufd.Descricao as ufd', $agenteSchema)
            ->joinLeft(array('mud' => 'Municipios'), 'de.idMunicipioDestino::varchar(6) = mud.idMunicipioIBGE', 'mud.Descricao as mud', $agenteSchema)
            ->where("idProjeto = ?", $idProjeto);

        if ($idDeslocamento != null) {
            $sql->where('de.idDeslocamento = ?', $idDeslocamento);
        }
//xd($sql->assemble());
        $resultado = $this->fetchAll($sql);

        return ($resultado) ? $resultado->toArray() : array();
    }
}