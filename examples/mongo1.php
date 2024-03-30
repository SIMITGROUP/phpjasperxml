<?php
include "main.php";

use simitsdk\phpjasperxml\PHPJasperXML;
error_reporting(0);
$filename = __DIR__.'/mongo.jrxml';


$config = [
    'driver'=>'mongodb',
    'name'=>'test',
    'connectionString'=>'mongodb://localhost:27017/test',   
];

$report = new PHPJasperXML();
$report->load_xml_file($filename)    
    ->setParameter(['reporttitle'=>'Database Report With Driver : '.$config['driver']])
    ->setDataSource($config)
    ->export('Pdf');

