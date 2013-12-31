<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

include_once('class/tcpdf/tcpdf.php');
include_once("class/PHPJasperXML.inc.php");

include_once ('setting.php');



$id=$_GET['id'];
$PHPJasperXML = new PHPJasperXML("en","TCPDF");
$PHPJasperXML->debugsql=false;
$PHPJasperXML->arrayParameter=array("id"=>$id);
$PHPJasperXML->load_xml_file("sample5.jrxml");

$PHPJasperXML->transferDBtoArray($server,$user,$pass,$db);
$PHPJasperXML->outpage("I");    //page output method I:standard output  D:Download file


?>

