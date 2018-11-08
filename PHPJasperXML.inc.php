<?php

// global $phpjasperversion;
$pchartfolder=__DIR__.'/pchart2';

include_once( __DIR__.'/tcpdf/tcpdf.php');
// include_once( __DIR__.'/tcpdf/tcpdf.php');
// echo $phpjasperversion;die;
if(!isset($phpjasperversion) || $phpjasperversion=="")
{
    $phpjasperversion='1.1';
}

 $path =  __DIR__.'/version/'.$phpjasperversion."/PHPJasperXML.inc.php";

include $path;
