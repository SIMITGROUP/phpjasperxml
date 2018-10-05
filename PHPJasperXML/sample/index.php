<?php
include "setting.php";
echo <<< EOF

<html>
<image src="simitlogo.jpg"><br>
Developer: Ng Jey Ruey (jeyruey@gmail.com)<br>
Project Leader: KS Tan (kstan@simit.com.my)<br>
Organization: <a href='http://www.simit.com.my'>Sim IT Sdn Bhd</a><br>
    <h1>PHP Jasper XML ($version) Example</h1><br>
	
    <p><B>Example:</B></p>
    <ul>
    <li><a href='sample1.php' target='_blank'>Sample 1 <a> (Standard Parent and Child Report)</li>
    <li><a href='sample2.php' target='_blank'>Sample 2</a> Charts (pie chart not supported)</li>
    <li><a href='sample3.php' target='_blank'>Sample 3</a> Sub Reports</li>
    <li><a href='sample4.php' target='_blank'>Sample 4</a>Export as Excel</li>
    <li><a href='sample5.php?id=1' target='_blank'>Sample 5</a> (Use TCPDF, with writeHTML output) (markup=html)</li>
    <li><a href='sample6.php' target='_blank'>Sample 6</a> Grouping, hide repeated value (UOM), with asian fonts</li>
    <li><a href='sample7.php' target='_blank'>Sample 7</a> Complex Layout</li>
    </ul>
<br/>* Thanks rachman sponsored for new function!
</html>
EOF;
?>
