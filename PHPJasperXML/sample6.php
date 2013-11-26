<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
 $filename=$_GET["filename"];
include_once('class/tcpdf/tcpdf.php');
include_once("class/PHPJasperXML.inc.php");


include_once ('setting.php');


$xml =  simplexml_load_file("sample6.jrxml");

$PHPJasperXML = new PHPJasperXML();
$PHPJasperXML->debugsql=false;
$PHPJasperXML->arrayParameter=array();
$PHPJasperXML->xml_dismantle($xml);

$PHPJasperXML->transferDBtoArray($server,$user,$pass,$db);
$PHPJasperXML->outpage("I"); //page output method I:standard output D:Download file, F =save as filename and submit 2nd parameter as destinate file name 
//$PHPJasperXML->outpage("I");    //page output method I:standard output  D:Download file



?>
