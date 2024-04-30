# Change Log


# 2.1.x
2.1.x summary:
1. support php 8.2
2. mongodb query
3. eperimental support table element
4. added ->debugsql(true)
5. more reliable algorithm to parse `expression`

## 2.1.9
- fix padding not effect in textfield and static text

## 2.1.8
- added ->debugData(true) to verify data loaded
- wont cause error for some unsupport component like charts or list
- better algorithm to avoid error come from string '+' 
- reduce dependend on latest mongodb ^1.18, use ^1.17 instead


## 2.1.7
- fix subdata set map parameter wrongly, while render table

## 2.1.6
- added experimental support on table element (render as sub report)
- modify mongodb driver to avoid split nested object become flat fields

## 2.1.5
- fix barcode print wrong position
- fix undetected field cause page die
- updat tcpdf from 6.6.5 to 6.7.4

## 2.1.3
- support more mongodb queries beside aggregate
- support use file name 'return' to obtain pdf string
- some bug fix for subreport with mongodb
- better algorithm to solve randomly can't eval data during execute expression
- allow set current working directory

## 2.1.1
- added experiment support of mongodb

## 2.1.0
- support php 8.2
- allow debugsql(true) to show query string