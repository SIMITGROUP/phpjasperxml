<?php
include_once "PHPJasperXML.inc";
include_once('fpdf/fpdf.php');
//include_once "../../mainfile.php";
/*$data=$_POST['a'];
$select=$_POST['select'];
$i=0;
$para="";
	foreach($data as $line){
	$line ."->".$select[$i]."<br>";
	if($select[$i]=="on")
	$para=$para.$line.",";
	$i++;
	}
$para=substr($para,0,-1);
*/

$xml = simplexml_load_file('yearly cso.jrxml'); //file name
$PHPJasperXML = new PHPJasperXML();
$PHPJasperXML->arrayParameter=array("summary_id"=>"1");
$PHPJasperXML->xml_dismantle($xml);
$PHPJasperXML->transferDBtoArray("localhost","root","mysql","simdigi");//$PHPJasperXML->transferDBtoArray(url,dbuser,dbpassword,db);
$PHPJasperXML->outpage("I");	//page output method I:standard output	D:Download file	F:Save to local file	S:Return as a string
//$PHPJasperXML->test();//test's function

?>

