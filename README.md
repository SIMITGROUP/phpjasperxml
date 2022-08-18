# Introduction
This is php library read jasper report designed file (.jrxml) and generate pdf file. 

The goal of this project is to allow php developer design reasonable good printable pdf easily with concept WYSIWYG. However, since the .jrxml file design for java project, phpjasperxml not able to make it 100% compatible in php environment. Refer compatibility description to know what you can do and what you cannot do.

It completely rewrite since version 1.x, if you use version 1.x before please verify your output carefully since it is more compatible to jasper studio, but may not work perfectly at last version.




# Install
Latest phpjasperxml require php 7.4, and php extension like php-curl, php-intl, php-simplexml. 
```php
composer require simitgroup/phpjasperxml
```

# How to use
```php
<?php
require __DIR__."/vendor/autoload.php";

use simitsdk\phpjasperxml\PHPJasperXML;
$filename = __DIR__.'/sample.jrxml';

$data=[ ['user_id'=>0, 'fullname' => 'name1','email'=>'email1@a.com','gender'=>'M' ], 
        ['user_id'=>1, 'fullname' => 'name2','email'=>'email2@a.com','gender'=>'F' ], 
        ['user_id'=>2, 'fullname' => 'name3','email'=>'email3@a.com','gender'=>'M' ], ];

$config = ['driver'=>'array','data'=>$data];

$report = new PHPJasperXML();
$report->load_xml_file($filename)    
    ->setDataSource($config)
    ->export('Pdf'); 
```

Refer https://github.com/SIMITGROUP/phpjasperxml/blob/master/examples/databasesample.php if you want to use database driver instead of prepare array.


# Samples
Refer sample: https://github.com/SIMITGROUP/phpjasperxml/wiki/Sample-output

# Compatibility:
Generally, phpjasperxml provide below compatiblity result of:

## Bands
Band support both print order:
* vertical
* horizontal

Band Name | Status | Description
--------- | ------ | -----------
title     | :white_check_mark: | First page only
page header | :white_check_mark: |
column header | :white_check_mark: |multiple column supported
detail(s) | :white_check_mark: |multiple band supported
column footer| :white_check_mark: |
page footer| :white_check_mark: |
last page footer| :white_check_mark: |
summary| :white_check_mark: |
no data| :white_check_mark: |
groups | :white_check_mark: | multiple group supported, in both vertical/horizontal print order

:exclamation: According try & error, there is some band like page header, column footer, page footer not allow grow according textField "stretchHeight". To make life easier phpjasperxml rules:
1. only detail band will grow
2. position type (default "Fix relative to top")
3. stretch type (default "use Not stretch")



Element   | Status | Description
--------- | ------ | -----------
textField | :white_check_mark: | 
staticText | :white_check_mark: | 
line | :white_check_mark: | Double line not supported
rectangle | :white_check_mark: | 
circle | :white_check_mark: | 
image | :white_check_mark: | :exclamation: Some scaleImage is not supported (Clip,RealHeight,RealSize). You can define image expression with base64 string too
barcode | :white_check_mark: |  :exclamation: some standard is not supported, refer barcode example.
break | :white_check_mark: | :exclamation: column break not work nicely, in single page multple column also may error
subreport | :white_check_mark: | :exclamation: support basic fixed height sub report (the subreport will simply draw at current location and expand according data without consider band limits)
frame | :white_check_mark: | 
chart | :x: | 
spiderchart | :x: | 
table | :x: |  
list | :x: | 
generic | :x: | 
custom visualzation | :x: | 
map | :x: | 

## TextField and StaticText
TextField and Static Text is most important element in report. Below is the compatibility detail.

Setting   | Status | Description
--------- | ------ | -----------
x | :white_check_mark: | 
y | :white_check_mark: | 
w | :white_check_mark: | 
h | :white_check_mark: | 
Forecolor | :white_check_mark: | 
Backcolor | :white_check_mark: | 
Font  | :white_check_mark: | :exclamation: Changing font is configurable, but upstream (tcpdf) not support lot of fonts. Developer shall manually add font into yourproject/vendor/tecnickcom/tcpdf/fonts. Unicode character for Chinese, Japanese, Korean detected will replace as fixed font(So it display the content instead of show '?'. However, you have no way to change the their font ).
Transparent | :white_check_mark: | 
Print Repeated Value | :x: | Default = True
Label | :x: |
Key | :x: |
Remove Line When Blank | :x: |
Print First Whole Band | :x: |
Detail Overflow | :x: |
Group Changes | :x: |
Print When Expression | :white_check_mark: | 
Paddings | :white_check_mark: | 
Borders | :white_check_mark: | 
Expressions | :white_check_mark: | 
Text Adjust | :white_check_mark: |  ScaleFont look differently compare to jasperreport
Text Align Horizontal | :white_check_mark: | 
Text Align Vertical | :white_check_mark: | not work when Stretch Type = StretchHeight
Text Rotation | :white_check_mark: | 
Pattern | :white_check_mark: | :exclamation: only support number
Pattern Expression | :white_check_mark: | :exclamation: only support number
Markup | :white_check_mark: | :exclamation: No markup, or html only
Hyperlink Reference Expression | :white_check_mark: | Link Type = Reference, will convert become html cell with hyperlink.  :exclamation: Some format may lose

## Line
Setting   | Status | Description
--------- | ------ | -----------
x | :white_check_mark: | 
y | :white_check_mark: | 
w | :white_check_mark: | 
h | :white_check_mark: | 
Width | :white_check_mark: | 
Color | :white_check_mark: | 
Style | :white_check_mark: | Double line is not supported
Print When Expression | :white_check_mark: | 

## Rectangle
Setting   | Status | Description
--------- | ------ | -----------
x | :white_check_mark: | 
y | :white_check_mark: | 
w | :white_check_mark: | 
h | :white_check_mark: | 
Print When Expression | :white_check_mark: | 
Forecolor | :white_check_mark: | Line color override Forecolor
Backcolor | :white_check_mark: | 
Transparent | :white_check_mark: | 
Line Color | :white_check_mark: |  Line color override Forecolor
Line Style | :white_check_mark: | 
Line Width | :white_check_mark: | 
Border Radius | :white_check_mark: | :exclamation: radius will cause line style/color/width weird due to bugs in tcpdf. dont use radius if you wish to change line style.

## Ellipse
Setting   | Status | Description
--------- | ------ | -----------
x | :white_check_mark: | 
y | :white_check_mark: | 
w | :white_check_mark: | 
h | :white_check_mark: | 
Print When Expression | :white_check_mark: | 
Forecolor | :white_check_mark: | Line color override Forecolor
Backcolor | :white_check_mark: | 
Transparent | :white_check_mark: | 
Line Color | :white_check_mark: |  Line color override Forecolor
Line Style | :white_check_mark: | 
Line Width | :white_check_mark: | 


## Outputs
PHPJasperxml going to output report into several format.

Output   | Status | Description
--------- | ------ | -----------
PDF | :white_check_mark: | done, not stable yet
XLSX | :white_check_mark: | Only support staticText and TextField
HTML | :x: |  coming future


# Expressions
jrxml use a lot of expression which is defined as java(groovy) syntax. It not fit into php environment perfectly. Sometimes the report look nice in jasperstudio, but not exactly same in php. It is important to know how PHPJasperxml evaluate the expression, and the flow. Below is the flow:
1. phpjasperxml extract expression string from specific element
2. analyse expression using preg_match, and replace desire value into $F{},$V{},$P{}.
3. If value data type is text/string kinds (Such as java.lang.String), it will apply quote/escape the string
4. if quote exists, it will replace '+' become '.', cause php combine string using '.'
5. then use eval() to evaluate it, get the final value. (Since eval() is not secure, you shall not allow untrusted developer define expression).

Expression used at many places, included present the value, set hyperlink, set image location, show/hide specific element or band. It is To make report present as expected, you shall define expression according below rules:
1. Use more php style syntax: $F{fieldname} == "COMPAREME", instead of $F{fieldname}.equal("COMPAREME")
2. If you perform some operation/comparison with expression, make sure you double check, compare result from jasperstudio and generated pdf from phpjasperxml.
3. There is plenty of effort to make expression accurate, but I still recommend you perform calculation within sql, php level. Example:
    use sql calculate is more guarantee :
        SELECT a+b+c as result1 from mytable (assume a=1,b=2,c=3, then result1=6)
    then
        $F{a}+$F{b}+$F{c}  // the result1 most probably = 6, but also possible become 123 (concate 3 string)
        


## Variables
Variable is important, but very language dependent. 
Below is unsupported features:
* Increment Type

## Calculation Function

Calculation   | Status | Description
--------- | ------ | -----------
No Calculation Function | :white_check_mark: | 
Sum | :white_check_mark: | 
Average | :white_check_mark: | 
Highest | :white_check_mark: | 
Lowest | :white_check_mark: | 
First | :white_check_mark: | 
Variance | :x: |  coming future
Standard Deviation | :x: |  coming future
Count | :x: |  coming future
Distinct Count | :x: |  coming future

## Reset Types

Reset Type   | Status | Description
------------ | ------ | -----------
Report | :white_check_mark: |
Page | :white_check_mark: |
Column | :white_check_mark: |
Groupxxx | :white_check_mark: |
None | :white_check_mark: |
Master | :x: | No plan


# Sort Fields
SortField support fields ASC and DESC. Variables/Function is not support

# Scriptlet
Scriptlet is a method to allow report fetch specific value from existing functions. To compatible with jasperstudio as much as possible, we use expression method to define php code in Scriptlet description so in jasperstudio not complain. Then in phpjasperxml we will execute and put the value into scriptlet parameter. Refer script from jasperreport to know more.

How to use:
1. Create scriptlet: "replace_as_alias" 
2. Define description in scriptlet: str_replace("@",'_alias_',$F{email})
3. textField define value from scriptlet's parameter "$P{replace_as_alias_SCRIPTLET}"

Refer
https://github.com/SIMITGROUP/phpjasperxml/blob/master/examples/groups.jrxml

# :x: Styles
Style template is ignore, and not effect element at the moment.


# Supported Datasource:
[samples](https://github.com/SIMITGROUP/phplibs/blob/main/examples/databasesample.php)
1. Postgresql
2. Mysql
3. PDO (the rest of database)
4. Array (prepare associate array outside of lib)
