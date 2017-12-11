<?php

class Proposta_Model_TbDocumentosAgentesMapper extends MinC_Db_Mapper
{
    public function __construct()
    {
        parent::setDbTable('Proposta_Model_DbTable_TbDocumentosAgentes');
    }

    /**
     * @return bool
     */
    public function saveCustom($arrPost, Zend_File_Transfer $file)
    {
        try {
            $config = Zend_Registry::get("config")->toArray();
            $files = $file->getFileInfo();

            if (!$file->isUploaded()) {
                throw new Exception('Falha ao anexar arquivo! O tamanho m&aacute;ximo permitido &egrave; de 10MB.');
            }

            $arquivoNome = $files['arquivo']['name'];
            $arquivoTemp = $files['arquivo']['tmp_name'];
            $arquivoTipo = $files['arquivo']['type'];
            $arquivoTamanho = $files['arquivo']['size'];
            if (!empty($arquivoNome) && !empty($arquivoTemp)) {
                $arquivoExtensao = Upload::getExtensao($arquivoNome);
                $arquivoBinario = Upload::setBinario($arquivoTemp);
                $arquivoHash = Upload::setHash($arquivoTemp);
            }

            $tamanhoMaximoUploadArquivo = $config['upload']['maxUploadFileSize'];
            if ($arquivoTamanho > $tamanhoMaximoUploadArquivo) {
                throw new Exception('O arquivo n&atilde;o pode ser maior do que 10MB!');
            }

            $tbPreProjeto = new Proposta_Model_DbTable_PreProjeto();
            $dadosProjeto = $tbPreProjeto->findBy(array('idPreProjeto' => $arrPost['idPreProjeto']));

            $where = array();
            $where['CodigoDocumento'] = $arrPost['documento'];
            $where['idProjeto'] = $arrPost['idPreProjeto'];
            if ($arrPost['tipoDocumento'] == 1) {
                $where['idAgente'] = $dadosProjeto['idAgente'];
            }

            $strPath = '/data/proposta/model/tbdocumentoagentes/';
            $strPathFull = APPLICATION_PATH . '/..' . $strPath;
            $dadosArquivo = array(
                'codigodocumento' => $arrPost['documento'],
                'idprojeto' => $arrPost['idPreProjeto'],
                'data' => date('Y-m-d'),
                'noarquivo' => $arquivoNome,
                'taarquivo' => $arquivoTamanho,
                'dsdocumento' => $arrPost['observacao'],
                'idAgente' => $dadosProjeto['idAgente'],
            );

            $table = $this;
            $model = new Proposta_Model_TbDocumentosAgentes();
            if ($arrPost['tipoDocumento'] != 1) {
                $table = new Proposta_Model_TbDocumentosPreProjetoMapper();
                $model = new Proposta_Model_TbDocumentosPreProjeto();
            }

            $docCadastrado = $table->findBy($where);
            if ($docCadastrado) {
                throw new Exception('Tipo de documento j&aacute; cadastrado!');
            }

            if ($this->getDbTable()->getAdapter() instanceof Zend_Db_Adapter_Pdo_Mssql) {
                $dadosArquivo['imDocumento'] = new Zend_Db_Expr("CONVERT(varbinary(MAX), {$arquivoBinario})");
                $model->setOptions($dadosArquivo);
                $table->save($model);
            } else {
                $strId = md5(uniqid(rand(), true));
                $fileName = $strId . '.' . array_pop(explode('.', $file->getFileName()));
                $dadosArquivo['imDocumento'] = $strPath . $fileName;
                $model->setOptions($dadosArquivo);
                $table->save($model);
                $file->receive();
                copy($file->getFileName(), $strPathFull . $fileName);
            }

            # REMOVER AS PENDENCIAS DE DOCUMENTO
            $tblDocumentosPendentesProjeto = new Proposta_Model_DbTable_DocumentosProjeto();
            $tblDocumentosPendentesProponente = new Proposta_Model_DbTable_DocumentosProponente();
            $tblDocumentosPendentesProjeto->delete("idProjeto = {$arrPost['idPreProjeto']} AND CodigoDocumento = {$arrPost['documento']}");
            $tblDocumentosPendentesProponente->delete("idProjeto = {$arrPost['idPreProjeto']} AND CodigoDocumento = {$arrPost['documento']}");

            return true;
        } catch (Exception $objException) {
            $this->setMessage($objException->getMessage());
        }
    }

    public function save(Proposta_Model_TbDocumentosAgentes $model)
    {
        return parent::save($model);
    }
}
