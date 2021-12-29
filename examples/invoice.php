<?php
include "main.php";

use simitsdk\phpjasperxml\PHPJasperXML;
$filename = __DIR__.'/invoice.jrxml';

$type = $_GET['type'];
$data = [];
$faker = Faker\Factory::create('en_US');

$email = $faker->email();
$customername = $faker->company();
$phoneNumber = $faker->phoneNumber();
$customeraddress = $faker->address()."\n".$faker->city()." ".$faker->postcode()."\n".$faker->country();
$invoiceno="IV00001";
$totalamount = 0;
$totalqty = 0;
$invoicedate = date('Y-m-d');
$salesagent = $faker->name;
for($i=0;$i<20;$i++)
{
    $qty = $faker->numberBetween(1,20);
    $unitprice = $faker->numberBetween(10,300).'.00';
    $lineamount = $qty * $unitprice;
    $totalamount +=$lineamount;
    $totalqty +=$qty;
    $attn = $faker->name;
    $tmp=[
        'customername'=>$customername,
        'customeraddress'=>$customeraddress,
        'customercontact'=>"\nAttn: $attn\nTel: $phoneNumber Email: $email",
        'invoiceno'=>$invoiceno,
        'salesagent'=>$salesagent ,
        'termname'=>'30 Days',
        'invoicedate'=> $invoicedate,
        'itemname'=>'Item '. ($i+1),
        'description'=>$faker->text( $faker->numberBetween(200,2000)),
        'qty'=>$qty,
        'unit'=>'Ea',
        'unitprice'=> $unitprice,
        'linetotal'=> $lineamount,
        'totalamount'=>$totalamount,
        'totalqty'=>$totalqty,
        'statustxt'=>'Draft',
    ];
    $data[$i]=$tmp;
}


$config = ['driver'=>'array','data'=>$data];
$paras = [
    'companyname'=>$faker->company(),
    'address'=>"10, block 10, Street 1, Street 2, Street3, 112345, MY",
    'registrationno'=>'Company Reg. No: AB-00998877-UUU',
    'contacts'=>'Tel:'.$faker->phoneNumber(). ' Email: '.$faker->email(),
    'documenttitle'=>'INVOICE',
];

$report = new PHPJasperXML();
$report->load_xml_file($filename)    
    ->setParameter($paras)
    ->setDataSource($config)
    ->setCreator('invoice.php')
    ->setAuthor($salesagent)
    ->setTitle("Invoice " .$invoiceno)
    ->setSubject("Invoice - $customername-" .$invoiceno)
    ->setKeywords("$invoiceno,$salesagent,$customername, $invoicedate")
    ->export(ucfirst($type));

