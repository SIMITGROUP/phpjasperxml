PHPJasperXML
============

>>> PHPJasperXML is actively in use in our product, however we have very tight project plan and a lot of development is running now, we dont have much time to improve it (but we change slightly from time to time). Since version 1.1 the structure has been changed, it use move effective algoritm, and it more organize, but it may need sometimes to stabilize.


About PHPJasperXML
------------------
We initialize this project to ease the develop web/printer friendly report in php. After many year development this project is stable and created a lot of web report. You can design your report via jasper report studio (http://community.jaspersoft.com/project/jaspersoft-studio) and then use phpjasperxml to render it in php. No javabridge is required at server side. We borrow tcpdf and phpexcel to export our report in pdf/excel. So far it support up to php7.2 (only tested in 7.0, and 7.2)

If export excel is crucial and you required fast export excel engine (phpexcel is painfully slow), you can get commercial driver from:
1. Cross platform Excel Library (written in c/c++) http://www.libxl.com/
2. PHP extension to for libxl https://github.com/iliaal/php_excel

Once excel library loaded phpjasperxml should detect it and use that to export excel, the performance will be 6-7x faster!

PHPJasperXML is not complete replacement of jaspersoft, it have some limitation. We'd use it many years and it is proven stable but it is not 100% features ready. There is known issue which is:
1. Subreport not render perfectly in detail band (Header and Footer I think should be ok), technically it seems very hard to calculate the  height of subreport at the same time balance with all others element in same detail band
2. Some barcode is not supported yet (QR code is supported)
3. Some situation text might not ablet to vertical align properly due to limitation of tcpdf
4. There is some others limitation, so far we change our way to accomodate the limitation
5. PHPJasperXML allow you do once and generate pdf/excel, however it is not suitable to run report with 100 pages long cause the limitation of algorithm.
6. Best use case of PHPJasperXML is use for design / print document, example Invoice, Purchase Order, Service Order, and etc printable document. Short report run well and allow export excel nicely. Long report you shall use alternative report tool

Installation
------------------
1. Download and extract this project into you website root directory (I assume /var/www)
2. Import sampledb.sql into mysql database, in this project we assume your username=root, password=mysql, database = phpjasperxml. If you use difference user/password/database, you shall change setting in sample1.php and sample2.php.
3. With your favorite web browser, browse into http://localhost/PHPJasperXML/index.php, test report you like.
4. Finish.

How to Use This Class
------------------
1. You can use iReport to edit the sample1.jrxml, sample2.jrxml and see the effect from web browser.
2. You can use any text editor to edit sample1.php and sample2.php, you will found that integrate the report into your project is like peanut.
3. Due to this project still at initial stage, to documentation is ready yet. However for those familiar with PHP and iReport should have no problem for using this class.


PHP Code
```
<?php
include_once 'path_to_phpjasperxml/PHPJasperXML.inc.php';//in this case, phpjasperxml is your submodule from github

$PHPJasperXML = new PHPJasperXML("en","TCPDF"); //if export excel, can use PHPJasperXML("en","XLS OR XLSX"); 
//$PHPJasperXML->debugsql=true;	
$PHPJasperXML->arrayParameter = array('para1'=>'1','para2'=>'2');
$PHPJasperXML->load_xml_file('file1.jrxml'); //if xml content is string, then $PHPJasperXML->load_xml_string($templatestr);
//$PHPJasperXML->sql = $sql;  //if you wish to overwrite sql inside jrxml
$dbdriver="mysql";//natively is 'mysql', 'psql', or 'sqlsrv'. the rest will use PDO driver. for oracle, use 'oci'

$PHPJasperXML->transferDBtoArray(DBSERVER,DBUSER,DBPASS,DBNAME,$dbdriver);
$PHPJasperXML->outpage('I');  //$PHPJasperXML->outpage('I=render in browser/D=Download/F=save as server side filename according 2nd parameter','filename.pdf or filename.xls or filename.xls depends on constructor');
```

License
------------------
1. PHPJasperXML is using MIT license, mean you can do whatever you want. However it use few library from different license.
2. TCPDF (use to draw pdf): GPL V3 https://github.com/tecnickcom/TCPDF
3. PHPEXCEL (when you need to export data into excel without libxl): LGPL https://github.com/PHPOffice/PHPExcel
4. Lib_Excel (when you wish to use commercial libxl library, performance 6x): https://github.com/iliaal/php_excel
5. PChart2 (when you wish to draw chart): GPL V3 http://www.pchart.net/license

Supported Database
------------------
at the moment PHPJasperXML support:
1. mysql (with mysqli)
2. postgres
3. sql server
4. PDO (for others)


How to debug?
-------------
A lot of time we want to debug and what wrong in our report, easiest way is append at report url with string `debugsql=1&showhtmldata=1` as below:

`http://www.mydomain.com/myreport.php?para1=1&debugsql=1&showhtmldata=1`
