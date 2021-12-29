<?php
include "main.php";

use simitsdk\phpjasperxml\PHPJasperXML;
$filename = __DIR__.'/databasesample.jrxml';


$config = [
    'driver'=>'array',
    'data'=>[ 
        ['user_id'=>0, 'fullname' => 'name1','email'=>'email1@a.com','gender'=>'M' ], 
        ['user_id'=>1, 'fullname' => 'name2','email'=>'email2@a.com','gender'=>'F' ], 
        ['user_id'=>2, 'fullname' => 'name3','email'=>'email3@a.com','gender'=>'M' ], 
        ]
];

// $config = [
//     'driver'=>'postgresql',
//     'host'=>'127.0.0.1',
//     'user'=>'postgres',
//     'pass'=>'postgres',
//     'name'=>'demo',
// ];
// $config = [
//     'driver'=>'mysql',
//     'host'=>'127.0.0.1',
//     'user'=>'root',
//     'pass'=>'root',
//     'name'=>'demo',
// ];
// $config = [
//     'driver'=>'pdo',
//     'dsn'=>'mysql:host=127.0.0.1;dbname=demo;',
//     'user'=>'root',
//     'pass'=>'root'
// ];


$report = new PHPJasperXML();
$report->load_xml_file($filename)    
    ->setParameter(['reporttitle'=>'Database Report With Driver : '.$config['driver']])
    ->setDataSource($config)
    ->export('Pdf');

