<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

include_once('class/tcpdf/tcpdf.php');
include_once("class/PHPJasperXML.inc.php");
include_once ('setting.php');
error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);


$xml =  simplexml_load_file("sample7.jrxml");

$PHPJasperXML = new PHPJasperXML();
//$PHPJasperXML->debugsql=true;
$PHPJasperXML->arrayParameter=array("parameter1"=>1);
$PHPJasperXML->xml_dismantle($xml);

$PHPJasperXML->transferDBtoArray($server,$user,$pass,$db);// * use this line if you want to connect with mysql

//if you want to use universal odbc connection, please create a dsn connection in odbc first
//$PHPJasperXML->transferDBtoArray($server,$user,$pass,"phpjasperxml"); //(default:mysql, accept value=mysql,odbc,psql)
$PHPJasperXML->outpage("I");    //page output method I:standard output  D:Download file


?>
