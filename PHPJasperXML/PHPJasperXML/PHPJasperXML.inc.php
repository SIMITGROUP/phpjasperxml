<?php

// global $phpjasperversion;

// echo $phpjasperversion;die;
if($phpjasperversion=="")
{
    $phpjasperversion='1.1';
}

 $path =  __DIR__.'/version/'.$phpjasperversion."/PHPJasperXML.inc.php";

include $path;
