<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Regularidade
 *
 * @author 00794800122
 */
class Regularidade extends Zend_Db_Table
{



	public static function buscarSalic($CgcCpf)
	{
		$sql = "select Habilitado from sac.dbo.Inabilitado where CgcCpf = '$CgcCpf' AND Habilitado='N' ";
		
			$db = Zend_Db_Table::getDefaultAdapter();
			$db->setFetchMode(Zend_DB::FETCH_OBJ);



		return $db->fetchAll($sql);
        }
        	public static function buscarCQTE($CgcCpf)
	{
		$sql = "select DtValidade from sac.dbo.CertidoesNegativas where CgcCpf = '$CgcCpf' and CodigoCertidao = 70 ";
		$db = Zend_Db_Table::getDefaultAdapter();
			$db->setFetchMode(Zend_DB::FETCH_OBJ);



		return $db->fetchAll($sql);
        }
        	public static function buscarCQTF($CgcCpf)
	{
		$sql = "select DtValidade from sac.dbo.CertidoesNegativas where CgcCpf = '$CgcCpf' and CodigoCertidao = 49 ";

			$db = Zend_Db_Table::getDefaultAdapter();
			$db->setFetchMode(Zend_DB::FETCH_OBJ);



		return $db->fetchAll($sql);
        }
        	public static function buscarFGTS($CgcCpf)

	{
		$sql = "select DtValidade from sac.dbo.CertidoesNegativas where CgcCpf = '$CgcCpf 'and CodigoCertidao = 51 ";

			$db = Zend_Db_Table::getDefaultAdapter();
			$db->setFetchMode(Zend_DB::FETCH_OBJ);



		return $db->fetchAll($sql);
        }
        	public static function buscarINSS($CgcCpf)
	{
		$sql = "select DtValidade,cdSituacaoCertidao from sac.dbo.CertidoesNegativas where CgcCpf = '$CgcCpf' and CodigoCertidao = 52 ";
        
                $db = Zend_Db_Table::getDefaultAdapter();
			$db->setFetchMode(Zend_DB::FETCH_OBJ);



		return $db->fetchAll($sql);
        }
            	public static function buscarCADIN($CgcCpf)
	{
		$sql = "select DtEmissao,  cdSituacaoCertidao from sac.dbo.CertidoesNegativas where CgcCpf = '$CgcCpf' and CodigoCertidao = 244";
			$db = Zend_Db_Table::getDefaultAdapter();
			$db->setFetchMode(Zend_DB::FETCH_OBJ);

		return $db->fetchAll($sql);
        }
}
