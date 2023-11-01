<?php
include "main.php";

use simitsdk\phpjasperxml\PHPJasperXML;
$filename = __DIR__.'/formating.jrxml';

$data = [];
$faker = Faker\Factory::create('en_US');
for($i=0;$i<20;$i++)
{
    $tmp=[
        'fullname' => $faker->name(),
        'email' => $faker->email(),
        'gender' => $faker->randomElement(['M', 'F']),
        'globaluser_id'=> $i+100008,
        'description'=>"Begin $i.\n".$faker->realText(70)."\n".$faker->realText() ."\n Ending",
        'country_code'=>$faker->randomElement(['SG','AU','US','MY']),
        'created'=>$faker->date("Y-m-d H:i:s")

    ];
    $data[$i]=$tmp;
}


$config = ['driver'=>'array','data'=>$data];

$report = new PHPJasperXML();
$report->load_xml_file($filename)    
    ->setDataSource($config)->debugsql(true)
    ->export('Pdf');

