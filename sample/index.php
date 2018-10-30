<?php
include "setting.php";
echo <<< HTML

<html>
<head>
    <style>
        li {
           list-style:none; 
        }
    </style>
</head>
<body>
<div style='text-align: center'>
<h1>PHP Jasper XML ($version) Example</h1><br>
<image src="simitlogo.png">
    
	
    <p><B>Example:</B></p>
    <div >
        <ul>
            <li><a href='sample1.php' target='_blank'>Sample 1 <a> (Standard Parent and Child Report)</li>
            <li><a href='sample2.php' target='_blank'>Sample 2</a> Charts (pie chart not supported)</li>
            <li><a href='sample3.php' target='_blank'>Sample 3</a> Sub Reports (not yet fix)</li>
            <li><a href='sample4.php' target='_blank'>Sample 4</a>Export as Excel</li>
            <li><a href='sample5.php?id=1' target='_blank'>Sample 5</a> (Use TCPDF, with writeHTML output) (markup=html)</li>
            <li><a href='sample6.php' target='_blank'>Sample 6</a> Grouping, hide repeated value (UOM), with asian fonts</li>
            <li><a href='sample7.php' target='_blank'>Sample 7</a> Complex Layout</li>
            <li><a href='sample8.php' target='_blank'>Sample 8</a> Postgresql (you required postgresql intead of mysql)</li>
            <li><a href='sample9.php' target='_blank'>Sample 9</a> Replace array with php array (with sample1.jrxml layout)</li>
        </ul>
    </div>
    Organization: <a href='http://www.simitgroup.com'>Sim IT Sdn Bhd</a>
</div>

</body>
</html>
HTML;
?>
