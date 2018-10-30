<?php

include_once("../PHPJasperXML.inc.php");
// Creating a workbook
include ('setting.php');
//$xml =  simplexml_load_file("sample9.jrxml");





$PHPJasperXML = new PHPJasperXML("en","XLS");
//$PHPJasperXML->debugsql=true;
$PHPJasperXML->arrayParameter=array("parameter1"=>0);
$PHPJasperXML->load_xml_file("sample4.jrxml");

$PHPJasperXML->transferDBtoArray($server,$user,$pass,$db);
$PHPJasperXML->outpage("I","sample4.xls");    //page output method I:standard output  D:Download file

/*

// sending HTTP headers

// Creating a worksheet

// The actual data
$worksheet->write(0, 0, 'Name');
$worksheet->write(0, 1, 'Age');
$worksheet->write(1, 0, 'John Smith');
$worksheet->write(1, 1, 30);
$worksheet->write(2, 0, 'Johann Schmidt');
$worksheet->write(2, 1, 31);
$worksheet->write(3, 0, 'Juan Herrera');
$worksheet->write(3, 1, 32);

// Let's send the file
$workbook->close();
*/
 
?>
