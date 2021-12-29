<?php
include "main.php";

use simitsdk\phpjasperxml\PHPJasperXML;
$filename = __DIR__.'/column_verticalorder.jrxml';

$data = [];
$faker = Faker\Factory::create('en_US');
for($i=0;$i<25;$i++)
{
    $tmp=[
        'fullname' => $faker->name(),
        'email' => $faker->email(),
        'gender' => $faker->randomElement(['M', 'F']),
        'user_id'=> $i+10000,
        'description'=>"Begin $i.\n".$faker->realText(70)."\n".$faker->realText() ."\n Ending",
        'country_code'=>$faker->randomElement(['SG','AU','US','MY']),
        'created'=>$faker->date("Y-m-d H:i:s")

    ];
    $data[$i]=$tmp;
}


$config = ['driver'=>'array','data'=>$data];

$report = new PHPJasperXML();
$report->load_xml_file($filename)    
    ->setDataSource($config)
    ->export('Pdf');

