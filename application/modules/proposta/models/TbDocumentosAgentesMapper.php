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
            $arquivoTamanho = $files['arquivo']['size'];
            $tamanhoMaximoUploadArquivo = $config['upload']['maxUploadFileSize'];

            if ($arquivoTamanho > $tamanhoMaximoUploadArquivo) {
                throw new Exception('O arquivo n&atilde;o pode ser maior do que 10MB!');
            }

            $tbPreProjeto = new Proposta_Model_DbTable_PreProjeto();
            $dadosProjeto = $tbPreProjeto->findBy(array('idPreProjeto' => $arrPost['idPreProjeto']));
            $where = array();
            $where['CodigoDocumento'] = $arrPost['documento'];
            $diretorioUpload = (object)$config['upload']['diretorio']['proposta']['agente'];

            $this->tratarExistenciaDiretorio($diretorioUpload->absoluto);

            $mapper = $this;
            $model = new Proposta_Model_TbDocumentosAgentes();
            $where['idAgente'] = $dadosProjeto['idAgente'];
            $dadosArquivo = array(
                'CodigoDocumento' => $arrPost['documento'],
                'Data' => date('Y-m-d'),
                'NoArquivo' => $arquivoNome,
                'TaArquivo' => $arquivoTamanho,
                'idAgente' => $dadosProjeto['idAgente'],
            );

            if ($arrPost['tipoDocumento'] != 1) {
                $mapper = new Proposta_Model_TbDocumentosPreProjetoMapper();
                $model = new Proposta_Model_TbDocumentosPreProjeto();
                $dadosArquivo['idProjeto'] = $arrPost['idPreProjeto'];
                $dadosArquivo['dsDocumento'] = $arrPost['observacao'];
                $where['idProjeto'] = $arrPost['idPreProjeto'];
                unset($where['idAgente']);
            }

            $documentoCadastrado = $mapper->findBy($where);
            if ($documentoCadastrado) {
                throw new Exception('Tipo de documento j&aacute; cadastrado!');
            }

            $strId = md5(uniqid(rand(), true));
            $fileName = $strId . '.' . array_pop(explode('.', $file->getFileName()));
            $dadosArquivo['imDocumento'] = $diretorioUpload->absoluto . $fileName;
            $model->setOptions($dadosArquivo);
            $mapper->save($model);
            $file->receive();
            copy($file->getFileName(), $diretorioUpload->absoluto . $fileName);

            $this->removerPendenciasDocumento($arrPost['idPreProjeto'], $arrPost['documento']);

            return true;
        } catch (Exception $objException) {
            $this->setMessage($objException->getMessage());
        }
    }

    private function tratarExistenciaDiretorio($diretorio)
    {
        if (!is_dir($diretorio)) {
            mkdir($diretorio, 0744, true);
        }
    }

    private function removerPendenciasDocumento($idPreProjeto, $documento)
    {
        $tblDocumentosPendentesProjeto = new Proposta_Model_DbTable_DocumentosProjeto();
        $tblDocumentosPendentesProponente = new Proposta_Model_DbTable_DocumentosProponente();
        $tblDocumentosPendentesProjeto->delete("idProjeto = {$idPreProjeto} AND CodigoDocumento = {$documento}");
        $tblDocumentosPendentesProponente->delete("IdProjeto = {$idPreProjeto} AND CodigoDocumento = {$documento}");
    }

    public function save(Proposta_Model_TbDocumentosAgentes $model)
    {
        return parent::save($model);
    }
}
