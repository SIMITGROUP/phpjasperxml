<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
include_once("../PHPJasperXML.inc.php");
include_once ('setting.php');

$data=[
    [
            'sample1_no' => 1,
            'sample1_date' => '2999-12-31',
            'sample1_itemname' => 'override item 1',
            'sample1_qty' => 10,
            'sample1_uom' => 'PCS',
       ],
       [
        
            'sample1_no' => 2,
            'sample1_date' => '3999-12-31',
            'sample1_itemname' => 'override item 2',
            'sample1_qty' => 30,
            'sample1_uom' => 'pair',
        ],
      

];




$PHPJasperXML = new PHPJasperXML();
//$PHPJasperXML->debugsql=true;
$PHPJasperXML->arrayParameter=array("parameter1"=>1);
$PHPJasperXML->load_xml_file("sample1.jrxml");

$PHPJasperXML->transferDBtoArray($server,$user,$pass,$db);
// echo '<pre>'.print_r($PHPJasperXML->arraysqltable,true).'</pre>';
// $PHPJasperXML->setData($data);
$PHPJasperXML->arraysqltable=$data;

$PHPJasperXML->outpage("I");    //page output method I:standard output  D:Download file


?>
