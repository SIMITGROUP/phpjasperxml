<?php

// global $phpjasperversion;
include_once( __DIR__.'/tcpdf/tcpdf.php');
// echo $phpjasperversion;die;
if($phpjasperversion=="")
{
    $phpjasperversion='1.1';
}

 $path =  __DIR__.'/version/'.$phpjasperversion."/PHPJasperXML.inc.php";

include $path;
