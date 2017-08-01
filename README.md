PHPJasperXML
============

>>> PHPJasperXML is actively in use in our product, however we have very tight project plan and a lot of development is running now. We'll once a while transfer our update into github. We won't able to use github to maintain our code phpjasperxml project due to it is part of our another big project and we shall maintain there.


About PHPJasperXML
------------------
We initialize this project to ease the develop web/printer friendly report in php. After many year development this project is stable and created a lot of web report. You can design your report via jasper report studio (http://community.jaspersoft.com/project/jaspersoft-studio) and then use phpjasperxml to render it in php. Now javabridge is required at server side. We borrow tcpdf and phpexcel to export our report in pdf/excel. So far it support up to php7 (only tested in 7.0, it might work in 7.1 but we havent test it)

If export excel is crucial and you required fast export excel engine (phpexcel is painfully slow), you can get commercial driver from:
1. Cross platform Excel Library (written in c/c++) http://www.libxl.com/
2. PHP extension to for libxl https://github.com/iliaal/php_excel

Once excel library loaded phpjasperxml should detect it and use that to export excel, the performance will be 6-7x faster!

PHPJasperXML is not complete replacement of jaspersoft, it have some limitation. We'd use it many years and it is proven stable but it is not 100% features ready. There is known issue which is:
1. Subreport not render perfectly in detail band (Header and Footer I think should be ok), technically it seems very hard to calculate the  height of subreport at the same time balance with all others element in same detail band
2. Some barcode is not supported yet (QR code is supported)
3. Some situation text might not ablet to vertical align properly due to limitation of tcpdf
4. There is some others limitation, so far we change our way to accomodate the limitation
5. PHPJasperXML allow you do once and generate pdf/excel, however it is not suitable to run report with 100 pages long.
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

