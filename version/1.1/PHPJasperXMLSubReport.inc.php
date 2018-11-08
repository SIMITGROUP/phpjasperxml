<?php


//version 1.1
class PHPJasperXMLSubReport extends abstractPHPJasperXML{
    // private $adjust=1.2;
    // public $version="1.1";
    // private $pdflib;
    // private $lang;

    private $previousarraydata;
    // public $debugsql=false;
    // private $myconn;
    // private $con;
    // public $sql;
    public $group_name;
    public $newPageGroup = false;
    private $curgroup=0;
    public $grouplist=array();
    private $groupno=0;
    public $totalgroup=0;
    private $footershowed=true;
    // private $groupnochange=0; //use for detect record change till which level of grouping (grouping support multilevel)
    private $titleheight=0;
    public $allowprintuntill=0;
    public $maxy=0;
    public $maxpage=0;
    public $parentcurrentband="";
    // private $report_count=0;        //### New declaration (variable exists in original too)
    // private $group_count = array(); //### New declaration
    private $xoffset=0;
    public function __construct($lang="en",$pdflib="TCPDF",$xoffset=0){
        $this->lang=$lang;
        //error_reporting(1);
        $this->pdflib=$pdflib;
        $this->xoffset=$xoffset;
        // if($this->fontdir=="")
        // $this->fontdir=dirname(__FILE__)."/tcpdf/fonts";

    }

    

    public function subDataset_handler($data){
    $this->subdataset[$data['name'].'']= $data->queryString;

    }
//read level 0,Jasperreport page setting
    public function page_setting($xml_path) {
        $this->arrayPageSetting["orientation"]="P";
        $this->arrayPageSetting["name"]=$xml_path["name"];
        $this->arrayPageSetting["language"]=$xml_path["language"];
        $this->arrayPageSetting["pageWidth"]=$xml_path["pageWidth"];
        $this->arrayPageSetting["pageHeight"]=$xml_path["pageHeight"];
        if(isset($xml_path["orientation"])) {
            $this->arrayPageSetting["orientation"]=substr($xml_path["orientation"],0,1);
        }
        $this->arrayPageSetting["columnWidth"]=$xml_path["columnWidth"];
       $this->arrayPageSetting["leftMargin"]=$xml_path["leftMargin"]+$this->xoffset;
    
        $this->arrayPageSetting["rightMargin"]=$xml_path["rightMargin"];
        $this->arrayPageSetting["topMargin"]=$xml_path["topMargin"];
        $this->y_axis=$xml_path["topMargin"];
        $this->arrayPageSetting["bottomMargin"]=$xml_path["bottomMargin"];
    }


    public function group_handler($xml_path) {

//        $this->arraygroup=$xml_path;


        if($xml_path["isStartNewPage"]=="true")
            $this->newPageGroup=true;
        else
            $this->newPageGroup="";

        foreach($xml_path as $tag=>$out) {
            switch ($tag) {
                case "groupHeader":
                    $this->pointer=&$this->arraygroup[$xml_path["name"]]["groupHeader"];
                    $this->pointer=&$this->arraygrouphead;
                    $this->arraygroupheadheight=$out->band["height"];
                    $this->arrayband[]=array("name"=>"group", "gname"=>$xml_path["name"],"isStartNewPage"=>$xml_path["isStartNewPage"],"groupExpression"=>substr($xml_path->groupExpression,3,-1));
                    $this->pointer[]=array("type"=>"band","height"=>$out->band["height"]+0,"y_axis"=>"","groupExpression"=>substr($xml_path->groupExpression,3,-1));
//### Modification for group count
                    $gnam=$xml_path["name"];                
                    $this->gnam=$xml_path["name"];
                    $this->group_count["$gnam"]=1; // Count rows of groups, we're on the first row of the group.
//### End of modification
                    foreach($out as $band) {
                        $this->default_handler($band);

                    }

                    $this->y_axis=$this->y_axis+$out->band["height"];       //after handle , then adjust y axis
                    break;
                case "groupFooter":

                    $this->pointer=&$this->arraygroup[$xml_path["name"]]["groupFooter"];
                    $this->pointer=&$this->arraygroupfoot;
                    $this->arraygroupfootheight=$out->band["height"];
                    $this->pointer[]=array("type"=>"band","height"=>$out->band["height"]+0,"y_axis"=>"","groupExpression"=>substr($xml_path->groupExpression,3,-1));
                    foreach($out as $b=>$band) {
                        $this->default_handler($band);

                    }
                    break;
                default:

                    break;
            }

        }
    }


    public function element_subReport($data) {
//        $b=$data->subreportParameter;
                $srsearcharr=array('.jasper','"',"'",' ','$P{SUBREPORT_DIR}+');
                $srrepalcearr=array('.jrxml',"","",'',$this->arrayParameter['SUBREPORT_DIR']);

                if (strpos($data->subreportExpression,'$P{SUBREPORT_DIR}') === false){
                    $subreportExpression=str_replace($srsearcharr,$srrepalcearr,$data->subreportExpression);
                }
                else{
                    $subreportExpression=str_replace($srsearcharr,$srrepalcearr,$data->subreportExpression);
                }

                foreach($data as $name=>$out){
                        if($name=='subreportParameter'){
                            $b[$out['name'].'']=$out;
                        }
                }//loop to let multiple parameter pass to subreport pass to subreport

                $this->pointer[]=array("type"=>"subreport", "x"=>$data->reportElement["x"], "y"=>$data->reportElement["y"],
                        "width"=>$data->reportElement["width"], "height"=>$data->reportElement["height"],
                        "subreportparameterarray"=> $b,"connectionExpression"=>$data->connectionExpression,
                        "subreportExpression"=>$subreportExpression);
    }


    public function dbQuery($sql)
    {

        if($this->cndriver=="mysql" || $this->cndriver=="mysqli")
        {
            $a=$this->myconn->query("set names 'utf8'");        
            $q=$this->myconn->query($sql);

            return $q;

        }
        else
        {
              return $this->myconn->query($sql);            
        }    
    }

    public function dbFetchData($query='',$option='')
    {

        if($this->cndriver=="mysql" || $this->cndriver=="mysqli")
        {
           return mysqli_fetch_array($query,MYSQLI_ASSOC);
        }
        else
        {                
            $stmt= $query->fetch(PDO::FETCH_ASSOC);        
            return $stmt;
        }
    }

    public function transferDBtoArray($host,$user,$password,$db_name,$cndriver="mysqli")
    {
        $this->m=0;
    
        if(!$this->connect($host,$user,$password,$db_name,$cndriver))    //connect database
        {
            echo "Fail to connect database";
            exit(0);
        }


        if($this->debugsql==true) {
            
            echo "<textarea cols='100' rows='40'>$this->sql</textarea>";
            die;
        }


             if($this->datafromphp == 1){
               for($k=0;$k<$this->totalline;$k++){
                        foreach($this->arrayfield as $out) {
                            if($this->recordinfo[$k]["$out"] == ""){
                                continue;
                            }
                            $this->arraysqltable[$this->m]["$out"]=$this->recordinfo[$k]["$out"];  
                        }
                      $this->m++;
               }
             }else{

                $result=$this->dbQuery($this->sql);

                while ($row = $this->dbFetchData($result))
                {

                    foreach($this->arrayfield as $out) 
                    {
                        $this->arraysqltable[$this->m]["$out"]=$row["$out"];
                    }
                    $this->m++;
               }
             }     
    }



    public function time_to_sec($time) {
        $hours = substr($time, 0, -6);
        $minutes = substr($time, -5, 2);
        $seconds = substr($time, -2);

        return $hours * 3600 + $minutes * 60 + $seconds;
    }

    public function sec_to_time($seconds) {
        $hours = floor($seconds / 3600);
        $minutes = floor($seconds % 3600 / 60);
        $seconds = $seconds % 60;

        return sprintf("%d:%02d:%02d", $hours, $minutes, $seconds);
    }

//     public function orivariable_calculation() {

//         foreach($this->arrayVariable as $k=>$out) {
//          //   echo $out['resetType']. "<br/><br/>";
//             switch($out["calculation"]) {
//                 case "Sum":
//                     $sum=0;
//                     if(isset($this->arrayVariable[$k]['class'])&&$this->arrayVariable[$k]['class']=="java.sql.Time") {
//                         foreach($this->arraysqltable as $table) {
//                             $sum=$sum+$this->time_to_sec($table["$out[target]"]);
//                             //$sum=$sum+substr($table["$out[target]"],0,2)*3600+substr($table["$out[target]"],3,2)*60+substr($table["$out[target]"],6,2);
//                         }
//                         //$sum= floor($sum / 3600).":".floor($sum%3600 / 60);
//                         //if($sum=="0:0"){$sum="00:00";}
//                         $sum=$this->sec_to_time($sum);
//                     }
//                     else {
//                         foreach($this->arraysqltable as $table) {
//                             $sum=$sum+$table[$out["target"]];
//                             $table[$out["target"]];
//                         }
//                     }

//                     $this->arrayVariable[$k]["ans"]=$sum;
//                     break;
//                 case "Average":

//                     $sum=0;

//                     if(isset($this->arrayVariable[$k]['class'])&&$this->arrayVariable[$k]['class']=="java.sql.Time") {
//                         $m=0;
//                         foreach($this->arraysqltable as $table) {
//                             $m++;

//                             $sum=$sum+$this->time_to_sec($table["$out[target]"]);


//                         }

//                         $sum=$this->sec_to_time($sum/$m);
//                         $this->arrayVariable[$k]["ans"]=$sum;

//                     }
//                     else {
//                         $this->arrayVariable[$k]["ans"]=$sum;
//                         $m=0;
//                         foreach($this->arraysqltable as $table) {
//                             $m++;
//                             $sum=$sum+$table["$out[target]"];
//                         }
//                         $this->arrayVariable[$k]["ans"]=$sum/$m;


//                     }


//                     break;
//                 case "DistinctCount":
//                     break;
//                 case "Lowest":

//                     foreach($this->arraysqltable as $table) {
//                         $lowest=$table[$out["target"]];
//                         if($table[$out["target"]]<$lowest) {
//                             $lowest=$table[$out["target"]];
//                         }
//                         $this->arrayVariable[$k]["ans"]=$lowest;
//                     }
//                     break;
//                 case "Highest":
//                     $out["ans"]=0;
//                     foreach($this->arraysqltable as $table) {
//                         if($table[$out["target"]]>$out["ans"]) {
//                             $this->arrayVariable[$k]["ans"]=$table[$out["target"]];
//                         }
//                     }
//                     break;
// //### A Count for groups, as a variable. Not tested yet, but seemed to work in print_r()
//                 case "Count":
//                     $value=$this->arrayVariable[$k]["ans"];
//                     if( $this->arraysqltable[$this->global_pointer][$this->group_pointer]!=$this->arraysqltable[$this->global_pointer-1][$this->group_pointer])
//                        $value=0;
//                     $value++;
//                     $this->arrayVariable[$k]["ans"]=$value;
//                 break;  
// //### End of modification               
//                 default:
//                     $out["target"]=0;       //other cases needed, temporary leave 0 if not suitable case
//                     break;

//             }
//         }
//     }


//       public function variable_calculation($rowno) 
//       {
//         //   $this->variable_calculation($rownum, $this->arraysqltable[$this->global_pointer][$this->group_pointer]);
//         //   print_r($this->arraysqltable);


//         foreach($this->arrayVariable as $k=>$out) {
//          //   echo $out['resetType']. "<br/><br/>";
//             switch($out["calculation"]) {
//                 case "Sum":

//                          $value=$this->arrayVariable[$k]["ans"];
//                     if($out['resetType']==''){
//                             if(isset($this->arrayVariable[$k]['class'])&&$this->arrayVariable[$k]['class']=="java.sql.Time") {
//                             //    foreach($this->arraysqltable as $table) {
//                                     $value=$this->time_to_sec($value);

//                                     $value+=$this->time_to_sec($this->arraysqltable[$rowno]["$out[target]"]);
//                                     //$sum=$sum+substr($table["$out[target]"],0,2)*3600+substr($table["$out[target]"],3,2)*60+substr($table["$out[target]"],6,2);
//                                // }
//                                 //$sum= floor($sum / 3600).":".floor($sum%3600 / 60);
//                                 //if($sum=="0:0"){$sum="00:00";}
//                                 $value=$this->sec_to_time($value);
//                             }
//                             else {
//                                // foreach($this->arraysqltable as $table) {
//                                          $value+=$this->arraysqltable[$rowno]["$out[target]"];

//                               //      $table[$out["target"]];
//                              //   }
//                             }
//                     }// finisish resettype=''
//                     else //reset type='group'
//                     {if( $this->arraysqltable[$this->global_pointer][$this->group_pointer]!=$this->arraysqltable[$this->global_pointer-1][$this->group_pointer])
//                              $value=0;
//                       //    echo $this->global_pointer.",".$this->group_pointer.",".$this->arraysqltable[$this->global_pointer][$this->group_pointer].",".$this->arraysqltable[$this->global_pointer-1][$this->group_pointer].",".$this->arraysqltable[$rowno]["$out[target]"];
//                                  if(isset($this->arrayVariable[$k]['class'])&&$this->arrayVariable[$k]['class']=="java.sql.Time") {
//                                       $value+=$this->time_to_sec($this->arraysqltable[$rowno]["$out[target]"]);
//                                 //$sum= floor($sum / 3600).":".floor($sum%3600 / 60);
//                                 //if($sum=="0:0"){$sum="00:00";}
//                                 $value=$this->sec_to_time($value);
//                             }
//                             else {
//                                       $value+=$this->arraysqltable[$rowno]["$out[target]"];
//                             }
//                     }


//                     $this->arrayVariable[$k]["ans"]=$value;
//               //      echo ",$value<br/>";
//                     break;
//                 case "Average":

//                     $sum=0;

//                     if(isset($this->arrayVariable[$k]['class'])&&$this->arrayVariable[$k]['class']=="java.sql.Time") {
//                         $m=0;
//                         //$value=$this->arrayVariable[$k]["ans"];
//                         //$value=$this->time_to_sec($value);
//                         //$value+=$this->time_to_sec($this->arraysqltable[$rowno]["$out[target]"]);

//                         foreach($this->arraysqltable as $table) {
//                             $m++;

//                              $sum=$sum+$this->time_to_sec($table["$out[target]"]);
//                            // echo ",".$table["$out[target]"]."<br/>";

//                         }


//                         $sum=$this->sec_to_time($sum/$m);
//                      // echo "Total:".$sum."<br/>";
//                          $this->arrayVariable[$k]["ans"]=$sum;


//                     }
//                     else {
//                         $this->arrayVariable[$k]["ans"]=$sum;
//                         $m=0;
//                         foreach($this->arraysqltable as $table) {
//                             $m++;
//                             $sum=$sum+$table["$out[target]"];
//                         }
//                         $this->arrayVariable[$k]["ans"]=$sum/$m;


//                     }


//                     break;
//                 case "DistinctCount":
//                     break;
//                 case "Lowest":

//                     foreach($this->arraysqltable as $table) {
//                         $lowest=$table[$out["target"]];
//                         if($table[$out["target"]]<$lowest) {
//                             $lowest=$table[$out["target"]];
//                         }
//                         $this->arrayVariable[$k]["ans"]=$lowest;
//                     }
//                     break;
//                 case "Highest":
//                     $out["ans"]=0;
//                     foreach($this->arraysqltable as $table) {
//                         if($table[$out["target"]]>$out["ans"]) {
//                             $this->arrayVariable[$k]["ans"]=$table[$out["target"]];
//                         }
//                     }
//                     break;
// //### A Count for groups, as a variable. Not tested yet, but seemed to work in print_r()                    
//                 case "Count":
//                     $value=$this->arrayVariable[$k]["ans"];
//                     if( $this->arraysqltable[$this->global_pointer][$this->group_pointer]!=$this->arraysqltable[$this->global_pointer-1][$this->group_pointer])
//                        $value=0;
//                     $value++;
//                     $this->arrayVariable[$k]["ans"]=$value;
//                 break;
// //### End of modification
//                 default:
//                     $out["target"]=0;       //other cases needed, temporary leave 0 if not suitable case
//                     break;

//             }
//         }
//     }


    public function outpage($out_method="I",$filename="") {

        //$this->arrayPageSetting["language"]=$xml_path["language"];
        $this->pdf->SetLeftMargin($this->arrayPageSetting["leftMargin"]);
        $this->pdf->SetRightMargin($this->arrayPageSetting["rightMargin"]);
        $this->pdf->SetTopMargin($this->arrayPageSetting["topMargin"]);
        $this->pdf->SetAutoPageBreak(true,$this->arrayPageSetting["bottomMargin"]/2);
        $this->pdf->AliasNbPages();


        $this->global_pointer=0;

        foreach ($this->arrayband as $band) {
            $this->currentband=$band["name"]; // to know current where current band in!
            switch($band["name"]) {
                case "title":
                  if($this->arraytitle[0]["height"]>0)
                    $this->title();
                    break;
                case "pageHeader":
                    if(!$this->newPageGroup) {
                        $headerY = $this->arrayPageSetting["topMargin"]+$this->arraypageHeader[0]["height"];
                        $this->pageHeader($headerY);
                    }else {
                        $this->pageHeaderNewPage();
                    }
                    break;
                case "detail":
                    if(!$this->newPageGroup) {

                        $this->detail();

                    }else {
                        $this->detailNewPage();
                        //$this->groupNewPage();
                    }
                    break;


                case "group":

                    $this->group_pointer=$band["groupExpression"];
                    $this->group_name=$band["gname"];

                    break;

                    default:
                    break;

            }

        }
        $this->subreportcheckpoint='';
//        if($filename=="")
//            $filename=$this->arrayPageSetting["name"].".pdf";

//         $this->disconnect($this->cndriver);
//        return true;  //send out the complete page
//        return $this->pdf->Output($filename,$out_method); //send out the complete page

    }
public function element_pieChart($data){

          $height=$data->chart->reportElement["height"];
          $width=$data->chart->reportElement["width"];
         $x=$data->chart->reportElement["x"];
         $y=$data->chart->reportElement["y"];
          $charttitle['position']=$data->chart->chartTitle['position'];

           $charttitle['text']=$data->chart->chartTitle->titleExpression;
          $chartsubtitle['text']=$data->chart->chartSubTitle->subtitleExpression;
          $chartLegendPos=$data->chart->chartLegend['position'];

          $dataset=$data->pieDataset->dataset->datasetRun['subDataset'];

          $seriesexp=$data->pieDataset->keyExpression;
          $valueexp=$data->pieDataset->valueExpression;
          $bb=$data->pieDataset->dataset->datasetRun['subDataset'];
          $sql=$this->arraysubdataset["$bb"]['sql'];

         // $ylabel=$data->linePlot->valueAxisLabelExpression;


          $param=array();
          foreach($data->categoryDataset->dataset->datasetRun->datasetParameter as $tag=>$value){
              $param[]=  array("$value[name]"=>$value->datasetParameterExpression);
          }
//          print_r($param);

         $this->pointer[]=array('type'=>'PieChart','x'=>$x,'y'=>$y,'height'=>$height,'width'=>$width,'charttitle'=>$charttitle,
            'chartsubtitle'=> $chartsubtitle,
               'chartLegendPos'=> $chartLegendPos,'dataset'=>$dataset,'seriesexp'=>$seriesexp,
            'valueexp'=>$valueexp,'param'=>$param,'sql'=>$sql,'ylabel'=>$ylabel);

    }
    public function element_pie3DChart($data){


    }

    public function element_Chart($data,$type){
   $seriesexp=array();
          $catexp=array();
          $valueexp=array();
          $labelexp=array();
          $height=$data->chart->reportElement["height"];
          $width=$data->chart->reportElement["width"];
         $x=$data->chart->reportElement["x"];
         $y=$data->chart->reportElement["y"];
          $charttitle['position']=$data->chart->chartTitle['position'];
                    $titlefontname=$data->chart->chartTitle->font['pdfFontName'];
          $titlefontsize=$data->chart->chartTitle->font['size'];
           $charttitle['text']=$data->chart->chartTitle->titleExpression;
          $chartsubtitle['text']=$data->chart->chartSubTitle->subtitleExpression;
          $chartLegendPos=$data->chart->chartLegend['position'];
          $dataset=$data->categoryDataset->dataset->datasetRun['subDataset'];
          $subcatdataset=$data->categoryDataset;
          //echo $subcatdataset;
          $i=0;
          foreach($subcatdataset as $cat => $catseries){
            foreach($catseries as $a => $series){
               if("$series->categoryExpression"!=''){
                array_push( $seriesexp,"$series->seriesExpression");
                array_push( $catexp,"$series->categoryExpression");
                array_push( $valueexp,"$series->valueExpression");
                array_push( $labelexp,"$series->labelExpression");
               }

            }

          }


          $bb=$data->categoryDataset->dataset->datasetRun['subDataset'];
          $sql=$this->arraysubdataset[$bb]['sql'];
          switch($type){
            case "barChart":
                $ylabel=$data->barPlot->valueAxisLabelExpression;
                $xlabel=$data->barPlot->categoryAxisLabelExpression;
                $maxy=$data->barPlot->rangeAxisMaxValueExpression;
                $miny=$data->barPlot->rangeAxisMinValueExpression;
                break;
            case "lineChart":
                $ylabel=$data->linePlot->valueAxisLabelExpression;
                $xlabel=$data->linePlot->categoryAxisLabelExpression;
                $maxy=$data->linePlot->rangeAxisMaxValueExpression;
                $miny=$data->linePlot->rangeAxisMinValueExpression;
                $showshape=$data->linePlot["isShowShapes"];
                break;
             case "stackedAreaChart":
                      $ylabel=$data->areaPlot->valueAxisLabelExpression;
                        $xlabel=$data->areaPlot->categoryAxisLabelExpression;
                        $maxy=$data->areaPlot->rangeAxisMaxValueExpression;
                        $miny=$data->areaPlot->rangeAxisMinValueExpression;
                        
                
                 break;
          }
          


          $param=array();
          foreach($data->categoryDataset->dataset->datasetRun->datasetParameter as $tag=>$value){
              $param[]=  array("$value[name]"=>$value->datasetParameterExpression);
          }
          if($maxy!='' && $miny!=''){
              $scalesetting=array(0=>array("Min"=>$miny,"Max"=>$maxy));
          }
          else
              $scalesetting="";

         $this->pointer[]=array('type'=>$type,'x'=>$x,'y'=>$y,'height'=>$height,'width'=>$width,'charttitle'=>$charttitle,
            'chartsubtitle'=> $chartsubtitle,
               'chartLegendPos'=> $chartLegendPos,'dataset'=>$dataset,'seriesexp'=>$seriesexp,
             'catexp'=>$catexp,'valueexp'=>$valueexp,'labelexp'=>$labelexp,'param'=>$param,'sql'=>$sql,'xlabel'=>$xlabel,'showshape'=>$showshape,
             'titlefontsize'=>$titlefontname,'titlefontsize'=>$titlefontsize,'scalesetting'=>$scalesetting);


    }


public function showLineChart($data,$y_axis){
    global $tmpchartfolder,$pchartfolder;


    if($pchartfolder=="")
        $pchartfolder="./pchart2";
//echo "$pchartfolder/class/pData.class.php";die;

        include_once("$pchartfolder/class/pData.class.php");
        include_once("$pchartfolder/class/pDraw.class.php");
        include_once("$pchartfolder/class/pImage.class.php");

    if($tmpchartfolder=="")
         $tmpchartfolder=$pchartfolder."/cache";

     $w=$data['width']+0;
     $h=$data['height']+0;



     $legendpos=$data['chartLegendPos'];
     //$legendpos="Right";
     $seriesexp=$data['seriesexp'];
     $catexp=$data['catexp'];
     $valueexp=$data['valueexp'];
     $labelexp=$data['labelexp'];
     $ylabel=$data['ylabel'].'';
     $xlabel=$data['xlabel'].'';
     $ylabel = str_replace(array('"',"'"),'',$ylabel);
     $xlabel = str_replace(array('"',"'"),'',$xlabel);
     $scalesetting=$data['scalesetting'];


     $x=$data['x'];
     $y1=$data['y'];
     $legendx=0;
     $legendy=0;

    $titlefontname=$data['titlefontname'].'';
    $titlefontsize=$data['titlefontsize']+0;


    $DataSet = new pData();

    foreach($catexp as $a=>$b)
       $catexp1[]=  str_replace(array('"',"'"), '',$b);

    $n=0;

    $DataSet->addPoints($catexp1,'S00');
    $DataSet->setSerieDescription('S00','asdasd');

    //$DataSet->AddSerie('S0');
    //$DataSet->SetSerieName('S0',"Cat");
    $DataSet->setAbscissa('S00');
    $n=$n+1;

    $ds=trim($data['dataset']);


    if($ds!=""){
              $sql=$this->subdataset[$ds];
        $param=$data['param'];
        foreach($param as $p)
            foreach($p as $tag =>$value)
                $sql=str_replace('$P{'.$tag.'}',$value, $sql);
            $sql=$this->changeSubDataSetSql($sql);

        }
    else
        $sql=$this->sql;

    $result = @mysql_query($sql); //query from db
    $chartdata=array();
    $i=0;
//echo $sql."<br/><br/>";
    $seriesname=array();
    while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {

                $j=0;
                foreach($row as $key => $value){
                    //$chartdata[$j][$i]=$value;
                    if($value=='')
                        $value=0;
                    if($key==str_replace(array('$F{','}'),'',$seriesexp[0]))
                    array_push($seriesname,$value);
                    else
                    foreach($valueexp as $v => $y){
                     if($key==str_replace(array('$F{','}'),'',$y)){
                         $chartdata[$i][$j]=(int)$value;

                           $j++;
                     }
                    }





                }
            $i++;

            }
            if($i==0)
                return 0;
            foreach($seriesname as $s=>$v){

                    $DataSet->addPoints($chartdata[$s],"$v");
              //  $DataSet->AddSerie("$v");
            }
            $DataSet->setAxisName(0,$ylabel);




    $this->chart = new pImage($w,$h,$DataSet);
    //$c = new pChart($w,$h);
    //$this->setChartColor();
    $this->chart->drawRectangle(1,1,$w-2,$h-2);
    $legendfontsize=8;
    $this->chart->setFontProperties(array('FontName'=>"$pchartfolder/fonts/calibri.ttf",'FontSize'=>$legendfontsize));


$Title=$data['charttitle']['text'];


      switch($legendpos){
             case "Top":
                 $legendmode=array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL);
                 $lgsize=$this->chart->getLegendSize($legendmode);
                 $diffx=$w-$lgsize['Width'];
                 if($diffx>0)
                 $legendx=$diffx/2;
                 else
                 $legendx=0;

                 //$legendy=$h-$lgsize['Height']+$legendfontsize;

                 if($legendy<0)$legendy=0;

                 if($Title==''){

                     $graphareay1=5;
                     $legendy=$graphareay1+5;
                    $graphareax1=40;
                     $graphareax2=$w-10 ;
                     $graphareay2=$h-$legendfontsize-15;
                }
                else{
                    $graphareay1=30;
                    $legendy=$graphareay1+5;
                    $graphareax1=40;

                     $graphareax2=$w-10 ;
                     $graphareay2=$h-$legendfontsize-15;

                }
                 break;
             case "Left":
                 $legendmode=array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_VERTICAL);
                 $lgsize=$this->chart->getLegendSize($legendmode);
                 $legendx=$lgsize['Width'];
                 if($Title==''){
                    $legendy=10;
                     $graphareay1=5;
                    $graphareax1=$legendx+5;
                     $graphareax2=$w-5;
                     $graphareay2=$h-20;
                }
                else{
                     $legendy=30;
                     $graphareay1=40;
                    $graphareax1=$legendx+5;
                     $graphareax2=$w-5 ;
                     $graphareay2=$h-20;
                }
                 break;
             case "Right":
             $legendmode=array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_VERTICAL);
                 $lgsize=$this->chart->getLegendSize($legendmode);
                 $legendx=$w-$lgsize['Width'];
                 if($Title==''){
                    $legendy=10;
                     $graphareay1=5;
                    $graphareax1=40;
                     $graphareax2=$legendx-5 ;
                     $graphareay2=$h-20;
                }
                else{
                     $legendy=30;
                     $graphareay1=30;
                    $graphareax1=40;
                     $graphareax2=$legendx-5 ;
                     $graphareay2=$h-20;
                }
                 break;
             case "Bottom":
                 $legendmode=array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL);
                 $lgsize=$this->chart->getLegendSize($legendmode);
                 $diffx=$w-$lgsize['Width'];
                 if($diffx>0)
                 $legendx=$diffx/2;
                 else
                 $legendx=0;

                 $legendy=$h-$lgsize['Height']+$legendfontsize;

                 if($legendy<0)$legendy=0;

                 if($Title==''){

                     $graphareay1=5;
                    $graphareax1=40;
                     $graphareax2=$w-10 ;
                     $graphareay2=$legendy-$legendfontsize-15;
                }
                else{
                    $graphareay1=30;
                    $graphareax1=40;
                     $graphareax2=$w-10 ;
                     $graphareay2=$legendy-$legendfontsize-15;
                }
                 break;
             default:
               $legendmode=array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL);
                 $lgsize=$this->chart->getLegendSize($legendmode);
                 $diffx=$w-$lgsize['Width'];
                 if($diffx>0)
                 $legendx=$diffx/2;
                 else
                 $legendx=0;

                 $legendy=$h-$lgsize['Height']+$legendfontsize;

                 if($legendy<0)$legendy=0;

                 if($Title==''){

                     $graphareay1=5;
                    $graphareax1=40;
                     $graphareax2=$w-10 ;
                     $graphareay2=$legendy-$legendfontsize-15;
                }
                else{
                    $graphareay1=30;
                    $graphareax1=40;
                     $graphareax2=$w-10 ;
                     $graphareay2=$legendy-$legendfontsize-15;
                }
                 break;

         }


         //echo "$graphareax1,$graphareay1,$graphareax2,$graphareay2";die;
    //print_r($lgsize);die;

    $this->chart->setGraphArea($graphareax1,$graphareay1,$graphareax2,$graphareay2);
    $this->chart->setFontProperties(array('FontName'=>"$pchartfolder/fonts/calibri.ttf",'FontSize'=>8));



    //if($type=='StackedBarChart')
      //  $scalesetting=array("Floating"=>TRUE,"GridR"=>200, "GridG"=>200,"GridB"=>200,"DrawSubTicks"=>TRUE, "CycleBackground"=>TRUE,
        //    "DrawSubTicks"=>TRUE,"Mode"=>SCALE_MODE_ADDALL_START0,"DrawArrows"=>TRUE,"ArrowSize"=>6);
    //else
    $ScaleSpacing=5;
        $scalesetting= $scaleSettings = array("XMargin"=>10,"YMargin"=>10,"Floating"=>TRUE,"GridR"=>200,"GridG"=>200,
            "GridB"=>200,"DrawSubTicks"=>TRUE,"CycleBackground"=>TRUE,"Mode"=>SCALE_MODE_START0,'ScaleSpacing'=>$ScaleSpacing);

    $this->chart->drawScale($scalesetting);

    $this->chart->drawLegend($legendx,$legendy,$legendmode);


    $Title = str_replace(array('"',"'"),'',$data['charttitle']['text']);

    if($Title!=''){
        $titlefontsize+0;
    if($titlefontsize==0)
        $titlefontsize=8;
     if($titlefontname=='')
        $titlefontname='calibri';
$titlefontname=strtolower($titlefontname);


    $textsetting=array('DrawBox'=>FALSE,'FontSize'=>$titlefontsize,'FontName'=>"$pchartfolder/fonts/".$titlefontname.".ttf",'align'=>TEXT_ALIGN_TOPMIDDLE);

    $this->chart->drawText($w/3,($titlefontsize+10),$Title,$textsetting);
    }

      $this->chart->setFontProperties(array('FontName'=>"$pchartfolder/fonts/calibri.ttf",'FontSize'=>7));

         $this->chart->drawLineChart();


   $randomchartno=rand();
      $photofile="$tmpchartfolder/chart$randomchartno.png";

             $this->chart->Render($photofile);

             if(file_exists($photofile)){
                $this->pdf->Image($photofile,$x+$this->arrayPageSetting["leftMargin"],$y_axis+$y1,$w,$h,"PNG");
                unlink($photofile);
             }

}



public function showBarChart($data,$y_axis,$type='barChart'){
      global $tmpchartfolder,$pchartfolder;


    if($pchartfolder=="")
        $pchartfolder="./pchart2";
//echo "$pchartfolder/class/pData.class.php";die;

        include_once("$pchartfolder/class/pData.class.php");
        include_once("$pchartfolder/class/pDraw.class.php");
        include_once("$pchartfolder/class/pImage.class.php");

    if($tmpchartfolder=="")
         $tmpchartfolder=$pchartfolder."/cache";

     $w=$data['width']+0;
     $h=$data['height']+0;



     $legendpos=$data['chartLegendPos'];
     //$legendpos="Right";
     $seriesexp=$data['seriesexp'];
     $catexp=$data['catexp'];
     $valueexp=$data['valueexp'];
     $labelexp=$data['labelexp'];
     $ylabel=$data['ylabel'].'';
     $xlabel=$data['xlabel'].'';
     $ylabel = str_replace(array('"',"'"),'',$ylabel);
     $xlabel = str_replace(array('"',"'"),'',$xlabel);
     $scalesetting=$data['scalesetting'];


     $x=$data['x'];
     $y1=$data['y'];
     $legendx=0;
     $legendy=0;
    $titlefontname=$data['titlefontname'].'';
    $titlefontsize=$data['titlefontsize']+0;


    $DataSet = new pData();

    foreach($catexp as $a=>$b)
       $catexp1[]=  str_replace(array('"',"'"), '',$b);

    $n=0;

    $DataSet->addPoints($catexp1,'S00');
    $DataSet->setSerieDescription('S00','asdasd');

    //$DataSet->AddSerie('S0');
    //$DataSet->SetSerieName('S0',"Cat");
    $DataSet->setAbscissa('S00');
    $n=$n+1;

    $ds=trim($data['dataset']);


    if($ds!=""){
              $sql=$this->subdataset[$ds];
        $param=$data['param'];
        foreach($param as $p)
            foreach($p as $tag =>$value)
                $sql=str_replace('$P{'.$tag.'}',$value, $sql);
            $sql=$this->changeSubDataSetSql($sql);

        }
    else
        $sql=$this->sql;

    $result = @mysql_query($sql); //query from db
    $chartdata=array();
    $i=0;
//echo $sql."<br/><br/>";
    $seriesname=array();
    while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {

                $j=0;
                foreach($row as $key => $value){
                    //$chartdata[$j][$i]=$value;
                    if($value=='')
                        $value=0;
                    if($key==str_replace(array('$F{','}'),'',$seriesexp[0]))
                    array_push($seriesname,$value);
                    else
                    foreach($valueexp as $v => $y){
                     if($key==str_replace(array('$F{','}'),'',$y)){
                         $chartdata[$i][$j]=(int)$value;

                           $j++;
                     }
                    }





                }
            $i++;

            }
            if($i==0)
                return 0;
            foreach($seriesname as $s=>$v){

                    $DataSet->addPoints($chartdata[$s],"$v");
              //  $DataSet->AddSerie("$v");
            }
            $DataSet->setAxisName(0,$ylabel);




    $this->chart = new pImage($w,$h,$DataSet);
    //$c = new pChart($w,$h);
    //$this->setChartColor();
    $this->chart->drawRectangle(1,1,$w-2,$h-2);
    $legendfontsize=8;
    $this->chart->setFontProperties(array('FontName'=>"$pchartfolder/fonts/calibri.ttf",'FontSize'=>$legendfontsize));


 $Title=$data['charttitle']['text'];


      switch($legendpos){
             case "Top":
                 $legendmode=array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL);
                 $lgsize=$this->chart->getLegendSize($legendmode);
                 $diffx=$w-$lgsize['Width'];
                 if($diffx>0)
                 $legendx=$diffx/2;
                 else
                 $legendx=0;

                 //$legendy=$h-$lgsize['Height']+$legendfontsize;

                 if($legendy<0)$legendy=0;

                 if($Title==''){

                     $graphareay1=15;
                     $legendy=$graphareay1+5;
                    $graphareax1=40;
                     $graphareax2=$w-10 ;
                     $graphareay2=$h-$legendfontsize-15;
                }
                else{
                    $graphareay1=30;
                    $legendy=$graphareay1+5;
                    $graphareax1=40;

                     $graphareax2=$w-10 ;
                     $graphareay2=$h-$legendfontsize-15;

                }
                 break;
             case "Left":
                 $legendmode=array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_VERTICAL);
                 $lgsize=$this->chart->getLegendSize($legendmode);
                 $legendx=$lgsize['Width'];
                 if($Title==''){
                    $legendy=10;
                     $graphareay1=10;
                    $graphareax1=$legendx+5;
                     $graphareax2=$w-5;
                     $graphareay2=$h-20;
                }
                else{
                     $legendy=30;
                     $graphareay1=40;
                    $graphareax1=$legendx+5;
                     $graphareax2=$w-5 ;
                     $graphareay2=$h-20;
                }
                 break;
             case "Right":
             $legendmode=array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_VERTICAL);
                 $lgsize=$this->chart->getLegendSize($legendmode);
                 $legendx=$w-$lgsize['Width'];
                 if($Title==''){
                    $legendy=10;
                     $graphareay1=5;
                    $graphareax1=40;
                     $graphareax2=$legendx-5 ;
                     $graphareay2=$h-20;
                }
                else{
                     $legendy=30;
                     $graphareay1=30;
                    $graphareax1=40;
                     $graphareax2=$legendx-5 ;
                     $graphareay2=$h-20;
                }
                 break;
             case "Bottom":
                 $legendmode=array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL);
                 $lgsize=$this->chart->getLegendSize($legendmode);
                 $diffx=$w-$lgsize['Width'];
                 if($diffx>0)
                 $legendx=$diffx/2;
                 else
                 $legendx=0;

                 $legendy=$h-$lgsize['Height']+$legendfontsize;

                 if($legendy<0)$legendy=0;

                 if($Title==''){

                     $graphareay1=15;
                    $graphareax1=40;
                     $graphareax2=$w-10 ;
                     $graphareay2=$legendy-$legendfontsize-15;
                }
                else{
                    $graphareay1=30;
                    $graphareax1=40;
                     $graphareax2=$w-10 ;
                     $graphareay2=$legendy-$legendfontsize-15;
                }
                 break;
             default:
               $legendmode=array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL);
                 $lgsize=$this->chart->getLegendSize($legendmode);
                 $diffx=$w-$lgsize['Width'];
                 if($diffx>0)
                 $legendx=$diffx/2;
                 else
                 $legendx=0;

                 $legendy=$h-$lgsize['Height']+$legendfontsize;

                 if($legendy<0)$legendy=0;

                 if($Title==''){

                     $graphareay1=15;
                    $graphareax1=40;
                     $graphareax2=$w-10 ;
                     $graphareay2=$legendy-$legendfontsize-15;
                }
                else{
                    $graphareay1=30;
                    $graphareax1=40;
                     $graphareax2=$w-10 ;
                     $graphareay2=$legendy-$legendfontsize-15;
                }
                 break;

         }


         //echo "$graphareax1,$graphareay1,$graphareax2,$graphareay2";die;
    //print_r($lgsize);die;

    $this->chart->setGraphArea($graphareax1,$graphareay1,$graphareax2,$graphareay2);
    $this->chart->setFontProperties(array('FontName'=>"$pchartfolder/fonts/calibri.ttf",'FontSize'=>8));


if($type=='stackedBarChart')
        $scalesetting=array("Floating"=>TRUE,"GridR"=>200, "GridG"=>200,"GridB"=>200,"DrawSubTicks"=>TRUE, "CycleBackground"=>TRUE,
            "DrawSubTicks"=>TRUE,"Mode"=>SCALE_MODE_ADDALL_START0,"ArrowSize"=>6);
    else
            $scalesetting=array("Floating"=>TRUE,"GridR"=>200, "GridG"=>200,"GridB"=>200,"DrawSubTicks"=>TRUE, "CycleBackground"=>TRUE,
            "DrawSubTicks"=>TRUE,"Mode"=>SCALE_MODE_START0,"ArrowSize"=>6);
    $this->chart->drawScale($scalesetting);

    $this->chart->drawLegend($legendx,$legendy,$legendmode);


    $Title = str_replace(array('"',"'"),'',$data['charttitle']['text']);

    if($Title!=''){
        $titlefontsize+0;
    if($titlefontsize==0)
        $titlefontsize=8;
     if($titlefontname=='')
        $titlefontname='calibri';
$titlefontname=strtolower($titlefontname);

    $textsetting=array('DrawBox'=>FALSE,'FontSize'=>$titlefontsize,'FontName'=>"$pchartfolder/fonts/".$titlefontname.".ttf",'align'=>TEXT_ALIGN_TOPMIDDLE);
//print_r($textsetting);die;
    $this->chart->drawText($w/3,($titlefontsize+10),$Title,$textsetting);
    }

      $this->chart->setFontProperties(array('FontName'=>"$pchartfolder/fonts/calibri.ttf",'FontSize'=>7));


    if($type=='stackedBarChart')
        $this->chart->drawStackedBarChart();
    else
        $this->chart->drawBarChart();


   $randomchartno=rand();
      $photofile="$tmpchartfolder/chart$randomchartno.png";

             $this->chart->Render($photofile);

             if(file_exists($photofile)){
                $this->pdf->Image($photofile,$x+$this->arrayPageSetting["leftMargin"],$y_axis+$y1,$w,$h,"PNG");
                unlink($photofile);
             }


}




public function showAreaChart($data,$y_axis,$type){
    global $tmpchartfolder,$pchartfolder;


    if($pchartfolder=="")
        $pchartfolder="./pchart2";
//echo "$pchartfolder/class/pData.class.php";die;

        include_once("$pchartfolder/class/pData.class.php");
        include_once("$pchartfolder/class/pDraw.class.php");
        include_once("$pchartfolder/class/pImage.class.php");

    if($tmpchartfolder=="")
         $tmpchartfolder=$pchartfolder."/cache";

     $w=$data['width']+0;
     $h=$data['height']+0;



     $legendpos=$data['chartLegendPos'];
     //$legendpos="Right";
     $seriesexp=$data['seriesexp'];
     $catexp=$data['catexp'];
     $valueexp=$data['valueexp'];
     $labelexp=$data['labelexp'];
     $ylabel=$data['ylabel'].'';
     $xlabel=$data['xlabel'].'';
     $ylabel = str_replace(array('"',"'"),'',$ylabel);
     $xlabel = str_replace(array('"',"'"),'',$xlabel);
     $scalesetting=$data['scalesetting'];


     $x=$data['x'];
     $y1=$data['y'];
     $legendx=0;
     $legendy=0;

    $titlefontname=$data['titlefontname'].'';
    $titlefontsize=$data['titlefontsize']+0;


    $DataSet = new pData();

    foreach($catexp as $a=>$b)
       $catexp1[]=  str_replace(array('"',"'"), '',$b);

    $n=0;

    $DataSet->addPoints($catexp1,'S00');
    $DataSet->setSerieDescription('S00','asdasd');

    //$DataSet->AddSerie('S0');
    //$DataSet->SetSerieName('S0',"Cat");
    $DataSet->setAbscissa('S00');
    $n=$n+1;

    $ds=trim($data['dataset']);


    if($ds!=""){
              $sql=$this->subdataset[$ds];
        $param=$data['param'];
        foreach($param as $p)
            foreach($p as $tag =>$value)
                $sql=str_replace('$P{'.$tag.'}',$value, $sql);
            $sql=$this->changeSubDataSetSql($sql);

        }
    else
        $sql=$this->sql;

    $result = @mysql_query($sql); //query from db
    $chartdata=array();
    $i=0;
//echo $sql."<br/><br/>";
    $seriesname=array();
    while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {

                $j=0;
                foreach($row as $key => $value){
                    //$chartdata[$j][$i]=$value;
                    if($value=='')
                        $value=0;
                    if($key==str_replace(array('$F{','}'),'',$seriesexp[0]))
                    array_push($seriesname,$value);
                    else
                    foreach($valueexp as $v => $y){
                     if($key==str_replace(array('$F{','}'),'',$y)){
                         $chartdata[$i][$j]=(int)$value;

                           $j++;
                     }
                    }





                }
            $i++;

            }
            if($i==0)
                return 0;
            foreach($seriesname as $s=>$v){

                    $DataSet->addPoints($chartdata[$s],"$v");
              //  $DataSet->AddSerie("$v");
            }
            $DataSet->setAxisName(0,$ylabel);




    $this->chart = new pImage($w,$h,$DataSet);
    //$c = new pChart($w,$h);
    //$this->setChartColor();
    $this->chart->drawRectangle(1,1,$w-2,$h-2);
    $legendfontsize=8;
    $this->chart->setFontProperties(array('FontName'=>"$pchartfolder/fonts/calibri.ttf",'FontSize'=>$legendfontsize));


$Title=$data['charttitle']['text'];


      switch($legendpos){
             case "Top":
                 $legendmode=array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL);
                 $lgsize=$this->chart->getLegendSize($legendmode);
                 $diffx=$w-$lgsize['Width'];
                 if($diffx>0)
                 $legendx=$diffx/2;
                 else
                 $legendx=0;

                 //$legendy=$h-$lgsize['Height']+$legendfontsize;

                 if($legendy<0)$legendy=0;

                 if($Title==''){

                     $graphareay1=5;
                     $legendy=$graphareay1+5;
                    $graphareax1=40;
                     $graphareax2=$w-10 ;
                     $graphareay2=$h-$legendfontsize-15;
                }
                else{
                    $graphareay1=30;
                    $legendy=$graphareay1+5;
                    $graphareax1=40;

                     $graphareax2=$w-10 ;
                     $graphareay2=$h-$legendfontsize-15;

                }
                 break;
             case "Left":
                 $legendmode=array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_VERTICAL);
                 $lgsize=$this->chart->getLegendSize($legendmode);
                 $legendx=$lgsize['Width'];
                 if($Title==''){
                    $legendy=10;
                     $graphareay1=5;
                    $graphareax1=$legendx+5;
                     $graphareax2=$w-5;
                     $graphareay2=$h-20;
                }
                else{
                     $legendy=30;
                     $graphareay1=40;
                    $graphareax1=$legendx+5;
                     $graphareax2=$w-5 ;
                     $graphareay2=$h-20;
                }
                 break;
             case "Right":
             $legendmode=array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_VERTICAL);
                 $lgsize=$this->chart->getLegendSize($legendmode);
                 $legendx=$w-$lgsize['Width'];
                 if($Title==''){
                    $legendy=10;
                     $graphareay1=5;
                    $graphareax1=40;
                     $graphareax2=$legendx-5 ;
                     $graphareay2=$h-20;
                }
                else{
                     $legendy=30;
                     $graphareay1=30;
                    $graphareax1=40;
                     $graphareax2=$legendx-5 ;
                     $graphareay2=$h-20;
                }
                 break;
             case "Bottom":
                 $legendmode=array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL);
                 $lgsize=$this->chart->getLegendSize($legendmode);
                 $diffx=$w-$lgsize['Width'];
                 if($diffx>0)
                 $legendx=$diffx/2;
                 else
                 $legendx=0;

                 $legendy=$h-$lgsize['Height']+$legendfontsize;

                 if($legendy<0)$legendy=0;

                 if($Title==''){

                     $graphareay1=5;
                    $graphareax1=40;
                     $graphareax2=$w-10 ;
                     $graphareay2=$legendy-$legendfontsize-15;
                }
                else{
                    $graphareay1=30;
                    $graphareax1=40;
                     $graphareax2=$w-10 ;
                     $graphareay2=$legendy-$legendfontsize-15;
                }
                 break;
             default:
               $legendmode=array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL);
                 $lgsize=$this->chart->getLegendSize($legendmode);
                 $diffx=$w-$lgsize['Width'];
                 if($diffx>0)
                 $legendx=$diffx/2;
                 else
                 $legendx=0;

                 $legendy=$h-$lgsize['Height']+$legendfontsize;

                 if($legendy<0)$legendy=0;

                 if($Title==''){

                     $graphareay1=5;
                    $graphareax1=40;
                     $graphareax2=$w-10 ;
                     $graphareay2=$legendy-$legendfontsize-15;
                }
                else{
                    $graphareay1=30;
                    $graphareax1=40;
                     $graphareax2=$w-10 ;
                     $graphareay2=$legendy-$legendfontsize-15;
                }
                 break;

         }


         //echo "$graphareax1,$graphareay1,$graphareax2,$graphareay2";die;
    //print_r($lgsize);die;

    $this->chart->setGraphArea($graphareax1,$graphareay1,$graphareax2,$graphareay2);
    $this->chart->setFontProperties(array('FontName'=>"$pchartfolder/fonts/calibri.ttf",'FontSize'=>8));



    //if($type=='StackedBarChart')
      //  $scalesetting=array("Floating"=>TRUE,"GridR"=>200, "GridG"=>200,"GridB"=>200,"DrawSubTicks"=>TRUE, "CycleBackground"=>TRUE,
        //    "DrawSubTicks"=>TRUE,"Mode"=>SCALE_MODE_ADDALL_START0,"DrawArrows"=>TRUE,"ArrowSize"=>6);
    //else
    $ScaleSpacing=5;
        $scalesetting= $scaleSettings = array("XMargin"=>10,"YMargin"=>10,"Floating"=>TRUE,"GridR"=>200,"GridG"=>200,
            "GridB"=>200,"DrawSubTicks"=>TRUE,"CycleBackground"=>TRUE,"Mode"=>SCALE_MODE_ADDALL_START0,'ScaleSpacing'=>$ScaleSpacing);

    $this->chart->drawScale($scalesetting);

    $this->chart->drawLegend($legendx,$legendy,$legendmode);


    $Title = str_replace(array('"',"'"),'',$data['charttitle']['text']);

    if($Title!=''){
        $titlefontsize+0;
    if($titlefontsize==0)
        $titlefontsize=8;
     if($titlefontname=='')
        $titlefontname='calibri';
$titlefontname=strtolower($titlefontname);


    $textsetting=array('DrawBox'=>FALSE,'FontSize'=>$titlefontsize,'FontName'=>"$pchartfolder/fonts/".$titlefontname.".ttf",'align'=>TEXT_ALIGN_TOPMIDDLE);

    $this->chart->drawText($w/3,($titlefontsize+10),$Title,$textsetting);
    }

      $this->chart->setFontProperties(array('FontName'=>"$pchartfolder/fonts/calibri.ttf",'FontSize'=>7));

$this->chart->drawStackedAreaChart(array("Surrounding"=>60));


   $randomchartno=rand();
      $photofile="$tmpchartfolder/chart$randomchartno.png";

             $this->chart->Render($photofile);

             if(file_exists($photofile)){
                $this->pdf->Image($photofile,$x+$this->arrayPageSetting["leftMargin"],$y_axis+$y1,$w,$h,"PNG");
                unlink($photofile);
             }

}




private function changeSubDataSetSql($sql){

foreach($this->currentrow as $name =>$value)
        $sql=str_replace('$F{'.$name.'}',$value,$sql);

foreach($this->arrayParameter as $name=>$value)
    $sql=str_replace('$P{'.$name.'}',$value,$sql);

foreach($this->arrayVariable as $name=>$value){
    $sql=str_replace('$V{'.$value['target'].'}',$value['ans'],$sql);


}


//print_r($this->arrayparameter);


//variable not yet implemented
     return $sql;


}
    public function background() {
        foreach ($this->arraybackground as $out) {
            switch($out["hidden_type"]) {
                case "field":
                    $this->display($out,$this->arrayPageSetting["topMargin"],true);
                    break;
                default:
                    $this->display($out,$this->arrayPageSetting["topMargin"],false);
                    break;
            }

        }
    }

    public function pageHeader($headerY) {
//        $this->pdf->AddPage();
        $this->background();
        if(isset($this->arraypageHeader)) {
            $this->arraypageHeader[0]["y_axis"]=$this->arrayPageSetting["topMargin"]+$this->TopHeightFromMainPage;
//            if($this->MainPageCurrentY>0){$this->arraypageHeader[0]["y_axis"]=$this->arrayPageSetting["topMargin"]+$this->MainPageCurrentY;}
        }
        foreach ($this->arraypageHeader as $out) {
            switch($out["hidden_type"]) {
                case "field":
                    $this->display($out,$this->arraypageHeader[0]["y_axis"],true);
                    break;
                default:
                    $this->display($out,$this->arraypageHeader[0]["y_axis"],false);
                    break;
            }
        }

    }

    public function pageHeaderNewPage() {
        $this->pdf->AddPage();
        $this->background();
        if(isset($this->arraypageHeader)) {
            $this->arraypageHeader[0]["y_axis"]=$this->arrayPageSetting["topMargin"]+$this->arrayMainPageSetting['topMargin'];
        }
        foreach ($this->arraypageHeader as $out) {
            switch($out["hidden_type"]) {
                case "textfield":
                    $this->display($out,$this->arraypageHeader[0]["y_axis"],true);
                    break;
                default:
                    $this->display($out,$this->arraypageHeader[0]["y_axis"],true);
                    break;
            }
        }
        $this->showGroupHeader($this->arrayPageSetting["topMargin"]+$this->arraypageHeader[0]["height"]);
    }


    public function title() {
        $this->pdf->AddPage();
        $this->background();

            $this->titleheight=$this->arraytitle[0]["height"];

            //print_r($this->arraytitle);die;
        if(isset($this->arraytitle)) {
            $this->arraytitle[0]["y_axis"]=$this->arrayPageSetting["topMargin"];
        }

        foreach ($this->arraytitle as $out) {

            switch($out["hidden_type"]) {
                case "field":
                    $this->display($out,$this->arraytitle[0]["y_axis"],true);
                    break;
                default:
                    $this->display($out,$this->arraytitle[0]["y_axis"],false);
                    break;
            }
        }


    }

      public function summary($y) {
        //$this->pdf->AddPage();
        //$this->background();

            $this->titlesummary=$this->arraysummary[0]["height"];

            //print_r($this->arraytitle);die;

        foreach ($this->arraysummary as $out) {

            switch($out["hidden_type"]) {
                case "field":
                    $this->display($out,$y,true);
                    break;
                default:
                    $this->display($out,$y,false);
                    break;
            }
        }


    }

    public function group($headerY) {

        $gname=$this->arrayband[0]["gname"]."";
        if(isset($this->arraypageHeader)) {
            $this->arraygroup[$gname]["groupHeader"][0]["y_axis"]=$headerY;
        }
        if(isset($this->arraypageFooter)) {
            $this->arraygroup[$gname]["groupFooter"][0]["y_axis"]=$this->arrayPageSetting["pageHeight"]-$this->arraypageFooter[0]["height"]-$this->arrayPageSetting["bottomMargin"]-$this->arraygroup[$gname]["groupFooter"][0]["height"];
        }
        else {
            $this->arraygroup[$gname]["groupFooter"][0]["y_axis"]=$this->arrayPageSetting["pageHeight"]-$this->arrayPageSetting["bottomMargin"]-$this->arraygroup[$gname]["groupFooter"][0]["height"];
        }

        if(isset($this->arraygroup)) {

            foreach($this->arraygroup[$gname] as $name=>$out) {


                switch($name) {
                    case "groupHeader":
//###                        $this->group_count=0;
                        foreach($out as $path) { //print_r($out);
                            switch($path["hidden_type"]) {
                                case "field":

                                    $this->display($path,$this->arraygroup[$gname]["groupHeader"][0]["y_axis"],true);
                                    break;
                                default:

                                    $this->display($path,$this->arraygroup[$gname]["groupHeader"][0]["y_axis"],false);
                                    break;
                            }
                        }
                        break;
                    case "groupFooter":
                        foreach($out as $path) {
                            switch($path["hidden_type"]) {
                                case "field":
                                    $this->display($path,$this->arraygroup[$gname]["groupFooter"][0]["y_axis"],true);
                                    break;
                                default:
                                    $this->display($path,$this->arraygroup[$gname]["groupFooter"][0]["y_axis"],false);
                                    break;
                            }
                        }
                        break;
                    default:
                        break;
                }
            }
        }
    }


    public function groupNewPage() {
        $gname=$this->arrayband[0]["gname"]."";

        if(isset($this->arraypageHeader)) {
            $this->arraygroup[$gname]["groupHeader"][0]["y_axis"]=$this->arrayPageSetting["topMargin"]+$this->arraypageHeader[0]["height"];
        }
        if(isset($this->arraypageFooter)) {
            $this->arraygroup[$gname]["groupFooter"][0]["y_axis"]=$this->arrayPageSetting["pageHeight"]-$this->arraypageFooter[0]["height"]-$this->arrayPageSetting["bottomMargin"]-$this->arraygroup[$gname]["groupFooter"][0]["height"];
        }
        else {
            $this->arraygroup[$gname]["groupFooter"][0]["y_axis"]=$this->arrayPageSetting["pageHeight"]-$this->arrayPageSetting["bottomMargin"]-$this->arraygroup[$gname]["groupFooter"][0]["height"];
        }

        if(isset($this->arraygroup)) {
            foreach($this->arraygroup[$gname] as $name=>$out) {
                switch($name) {
                    case "groupHeader":
                        foreach($out as $path) {
                            switch($path["hidden_type"]) {
                                case "field":
                                    $this->display($path,$this->arraygroup[$gname]["groupHeader"][0]["y_axis"],true);
                                    break;
                                default:

                                    $this->display($path,$this->arraygroup[$gname]["groupHeader"][0]["y_axis"],false);
                                    break;
                            }
                        }
                        break;
                    case "groupFooter":
                        foreach($out as $path) {
                            switch($path["hidden_type"]) {
                                case "field":
                                    $this->display($path,$this->arraygroup[$gname]["groupFooter"][0]["y_axis"],true);
                                    break;
                                default:
                                    $this->display($path,$this->arraygroup[$gname]["groupFooter"][0]["y_axis"],false);
                                    break;
                            }
                        }
                        break;
                    default:
                        break;
                }
            }
        }
    }

    public function pageFooter($checkpoint) {
        $this->SubReportCheckPoint=0;
        if(isset($this->arraypageFooter)) {
            foreach ($this->arraypageFooter as $out) {
                switch($out["hidden_type"]) {
                    case "field":
                        $this->display($out,$checkpoint,true);
                        if($this->pdf->getY()>$this->SubReportCheckPoint){$this->SubReportCheckPoint=$this->pdf->getY();}
//                        $this->display($out,$this->arrayPageSetting["pageHeight"]-$this->arraypageFooter[0]["height"]-$this->arrayPageSetting["bottomMargin"],true);
                        break;
                    default:
                        $this->display($out,$checkpoint,false);
                        if($this->pdf->getY()>$this->SubReportCheckPoint){$this->SubReportCheckPoint=$this->pdf->getY();}
//                        $this->display($out,$this->arrayPageSetting["pageHeight"]-$this->arraypageFooter[0]["height"]-$this->arrayPageSetting["bottomMargin"],false);
                        break;
                }
            }
        }
        else {
            $this->lastPageFooter();
        }
    }

    public function lastPageFooter($checkpoint) {
        if(isset($checkpoint)){$Y=$checkpoint;}else{$Y=$this->arrayPageSetting["pageHeight"]-$this->arraylastPageFooter[0]["height"]-$this->arrayPageSetting["bottomMargin"];}
        if(isset($this->arraylastPageFooter)) {
            foreach ($this->arraylastPageFooter as $out) {
                switch($out["hidden_type"]) {
                    case "field":
                        $this->display($out,$Y,true);
                        if($this->pdf->getY()>$this->SubReportCheckPoint){$this->SubReportCheckPoint=$this->pdf->getY();}
                        break;
                    default:
                        $this->display($out,$Y,false);
                        if($this->pdf->getY()>$this->SubReportCheckPoint){$this->SubReportCheckPoint=$this->pdf->getY();}
                        break;
                }
            }
        }
    }

    public function NbLines($w,$txt) {
        //Computes the number of lines a MultiCell of width w will take
        $cw=&$this->pdf->CurrentFont['cw'];
        if($w==0)
            $w=$this->pdf->w-$this->pdf->rMargin-$this->pdf->x;
        $wmax=($w-2*$this->pdf->cMargin)*1000/$this->pdf->FontSize;
        $s=str_replace("\r",'',$txt);
        $nb=strlen($s);
        if($nb>0 and $s[$nb-1]=="\n")
            $nb--;
        $sep=-1;
        $i=0;
        $j=0;
        $l=0;
        $nl=1;
        while($i<$nb) {
            $c=$s[$i];
            if($c=="\n") {
                $i++;
                $sep=-1;
                $j=$i;
                $l=0;
                $nl++;
                continue;
            }
            if($c==' ')
                $sep=$i;
            $l+=$cw[$c];
            if($l>$wmax) {
                if($sep==-1) {
                    if($i==$j)
                        $i++;
                }
                else
                    $i=$sep+1;
                $sep=-1;
                $j=$i;
                $l=0;
                $nl++;
            }
            else
                $i++;
        }
        return $nl;
    }

    public function detail() {
        $this->arraydetail[0]["y_axis"]=$this->arraydetail[0]["y_axis"]- $this->titleheight+$this->TopHeightFromMainPage;
        //echo $this->arraydetail[0]["y_axis"]."- $this->titleheight+$this->TopHeightFromMainPage;<br/>";
        $field_pos_y=$this->arraydetail[0]["y_axis"];

        $biggestY=0;
        $checkpoint=$this->arraydetail[0]["y_axis"];
        $tempY=$this->arraydetail[0]["y_axis"];
        $this->showGroupHeader($this->arrayPageSetting["topMargin"]+$this->arraypageHeader[0]["height"]+$this->TopHeightFromMainPage);
        $rownum=0;

        if($this->arraysqltable) {
  
            foreach($this->arraysqltable as $row) {

                if(isset($this->arrayVariable)) //if self define variable existing, go to do the calculation
                {
                    $this->variable_calculation($rownum, $this->arraysqltable[$this->global_pointer][$this->group_pointer]);
                }
             if(isset($this->arraygroup)&&($this->global_pointer>0)&&
                        ($this->arraysqltable[$this->global_pointer][$this->group_pointer]!=$this->arraysqltable[$this->global_pointer-1][$this->group_pointer]))   //check the group's groupExpression existed and same or not
                {
                    if($this->footershowed==false)
                    $ghfoot= $this->showGroupFooter($compare["height"]+$this->pdf->getY());
                    $this->footershowed=true;
                    $this->pdf->SetY($newY+$checkpoint+$ghfoot); 
                    $headerY = $biggestY;//+40;
                    $checkpoint=$headerY;//+40;
                    $biggestY = $headerY;//+40;
                    $tempY=$this->arraydetail[0]["y_axis"];
//###                     $this->group_count=0;
                    $this->group_count["$this->group_name"]=1;  // We're on the first row of the group.              
//### End of modification
                    if($this->arrayPageSetting["pageHeight"]< (($this->pdf->getY()) + ($this->arraygroupfootheight)+($this->arrayPageSetting["bottomMargin"])+($this->arraypageFooter[0]["height"])+($ghfoot)+($ghhead))){
                      //echo "aaa";

                          $this->pageFooter($checkpoint);
                          $this->pageHeader();

                          $checkpoint=$headerY;
                          $biggestY=$this->arrayPageSetting["topMargin"]+$this->arraypageHeader[0]["height"];
                          $tempY=$headerY;
//###                          $this->group_count=0;
                    $this->group_count["$this->group_name"]=1; // We're on the first row of the group.
//### End of modification                   
                    }

                    $ghheight=$this->showGroupHeader($this->pdf->getY()+$ghfoot);
                    $checkpoint=$this->pdf->getY()+$ghfoot+$ghhead+$this->arraygroupheadheight; //after group header add height band, so detail no crash with group header.

                }
                $detailcontentid=0;
                foreach($this->arraydetail as $compare) //this loop is to count possible biggest Y of the coming row
                {
                    $detailcontentid++;
                    
                    $this->currentrow=$this->arraysqltable[$this->global_pointer]; 
//echo $compare['txt'];

                    switch($compare["hidden_type"]) {
                        case "field":
                            $txt=$this->analyse_expression($compare["txt"]);

                            if(isset($this->arraygroup[$this->group_name]["groupFooter"])&&(($checkpoint+($compare["height"]*$txt))>($this->arrayPageSetting["subreportpageHeight"]-$this->arraygroupfootheight-$this->arrayPageSetting["bottomMargin"]-$this->BottomHeightFromMainPage)))//check group footer existed or not
                            {      
                                $this->pageFooter($checkpoint);
//                                $PHPJasperXML->pageFooter();
                                $checkpoint=$this->arraydetail[0]["y_axis"];
                                $biggestY=0;
                                $tempY=$this->arraydetail[0]["y_axis"];
                            }
                            elseif(isset($this->arraypageFooter)&&(($checkpoint+($compare["height"]*($this->NbLines($compare["width"],$txt))))>
                                                                       ($this->arrayPageSetting["subreportpageHeight"]-$this->arraypageFooter[0]["height"]-$this->arrayPageSetting["bottomMargin"]-$this->BottomHeightFromMainPage)))//check pagefooter existed or not
                            { //  $this->showGroupFooter($compare["height"]+$biggestY);
                            
                                //echo "arraypagefooter";
//echo ($checkpoint+($compare["height"]*($this->NbLines($compare["width"],$txt)))).'a'.($this->arrayPageSetting["pageHeight"]-$this->arraypageFooter[0]["height"]-$this->arrayPageSetting["bottomMargin"]-$this->BottomHeightFromMainPage).'b';
                                $this->pageFooter($checkpoint);
//echo $this->arrayPageSetting["subreportpageHeight"];
//                                $PHPJasperXML->pageFooter();
//                                        $this->pdf->AddPage();
                                $headerY = $this->arrayPageSetting["topMargin"]+$this->arraypageHeader[0]["height"];
//                                $this->pageHeader();
                                $this->pageHeaderNewPage();
//                                $checkpoint=$this->arraydetail[0]["y_axis"]+$this->TopHeightFromMainPage;
                                $checkpoint=$this->arraypageHeader[0]["y_axis"]+$this->arrayMainPageSetting['topMargin'];//$this->TopHeightFromMainPage;
                                $biggestY=0;
                                $tempY=$this->arraydetail[0]["y_axis"]+$this->TopHeightFromMainPage;
                          //       $this->showGroupHeader($checkpoint-$compare["height"]);
                            }
                            elseif(isset($this->arraylastPageFooter)&&(($checkpoint+($compare["height"]*($this->NbLines($compare["width"],$txt))))>($this->arrayPageSetting["subreportpageHeight"]-$this->arraylastPageFooter[0]["height"]-$this->arrayPageSetting["bottomMargin"]-$this->BottomHeightFromMainPage)))//check lastpagefooter existed or not
                            {   //$this->showGroupFooter($compare["height"]+$biggestY);
                                
                                $this->lastPageFooter($checkpoint);

                                $checkpoint=$this->arraydetail[0]["y_axis"];
                                $biggestY=0;
                                $tempY=$this->arraydetail[0]["y_axis"];
                            }

                            if(($checkpoint+($compare["height"]*($this->NbLines($compare["width"],$txt))))>$tempY) {
                                
                                $tempY=$checkpoint+($compare["height"]*($this->NbLines($compare["width"],$txt)));
                            }
                            break;
                        case "relativebottomline":
                            break;
                        case "report_count":
//###                            $this->report_count++;
                            break;
                        case "group_count":
//###                            $this->group_count++;
                            break;
                        default:

                            $this->display($compare,$checkpoint);
                            break;
                    }

                }

                if($checkpoint+$this->arraydetail[0]["height"]>($this->arrayPageSetting["pageHeight"]-$this->arraypageFooter[0]["height"]-$this->arrayPageSetting["bottomMargin"] - $ghheight -$ghfoot))    //check the upcoming band is greater than footer position or not
                {
                    $this->pageFooter($checkpoint); // open for every page got page footer.
                    //$this->pdf->AddPage();
                    $this->background();
                    $headerY = $this->arrayPageSetting["topMargin"]+$this->arraypageHeader[0]["height"];
                    $this->pageHeader($headerY);
                    $checkpoint=$this->arraydetail[0]["y_axis"];
                    $biggestY=0;
                    $tempY=$this->arraydetail[0]["y_axis"];
                }

                foreach ($this->arraydetail as $out) {
                    switch ($out["hidden_type"]) {
                        case "field":

                            // $this->prepare_print_array=array("type"=>"MultiCell","width"=>$out["width"],"height"=>$out["height"],"txt"=>$out["txt"],"border"=>$out["border"],"align"=>$out["align"],"fill"=>$out["fill"],"hidden_type"=>$out["hidden_type"],"printWhenExpression"=>$out["printWhenExpression"],"soverflow"=>$out["soverflow"],"poverflow"=>$out["poverflow"],"link"=>$out["link"],"pattern"=>$out["pattern"],"writeHTML"=>$out["writeHTML"],"isPrintRepeatedValues"=>$out["isPrintRepeatedValues"]);
                          $this->prepare_print_array=array(
                                    "type"=>"MultiCell",
                                    "width"=>$out["width"],
                                    "height"=>$out["height"],
                                    "txt"=>$out["txt"],
                                    "border"=>$out["border"],
                                    "align"=>$out["align"],
                                    "fill"=>$out["fill"],
                                    "hidden_type"=>$out["hidden_type"],
                                    "printWhenExpression"=>$out["printWhenExpression"],
                                    "soverflow"=>$out["soverflow"],
                                    "font"=>$out['font'],
                                    "pdfFontName"=>$out['pdfFontName'],
                                    "fontstyle"=>$out['fontstyle'],
                                    "fontsize"=>$out['fontsize'],
                                    "poverflow"=>$out["poverflow"],
                                    "link"=>$out["link"],
                                    "pattern"=>$out["pattern"],
                                    "writeHTML"=>$out["writeHTML"],
                                    "isPrintRepeatedValues"=>$out["isPrintRepeatedValues"],
                                    "valign"=>$out["valign"],
                                    "x"=>$out["x"],
                                    "y"=>$out["y"],
                                    "rotation"=>$out["rotation"],
                                    "uuid"=>$out["uuid"], 
                                    "linktarget"=>$out['linktarget']);
                            $this->display($this->prepare_print_array,0,true);

                            if($this->pdf->GetY()>$biggestY) {
                            $biggestY=$this->pdf->GetY();
                            }
                            break;
                        case "relativebottomline":
                        //$this->relativebottomline($out,$tempY);
                            $this->relativebottomline($out,$biggestY);
                            break;
                        default:

                            $this->display($out,$checkpoint);

                            //$checkpoint=$this->pdf->GetY();
                            break;
                    }
                }
                $this->pdf->SetY($biggestY);
                if($biggestY>$checkpoint+$this->arraydetail[0]["height"]) {
                    $checkpoint=$biggestY;
                }
                elseif($biggestY<$checkpoint+$this->arraydetail[0]["height"]) {
                    $checkpoint=$checkpoint+$this->arraydetail[0]["height"];
                }
                else {
                    $checkpoint=$biggestY;
                }
                //Remove $this->global_pointer>0 , because when only one row data will cause group footer no show.
        if(isset($this->arraygroup)&&
                        ($this->arraysqltable[$this->global_pointer][$this->group_pointer]!=$this->arraysqltable[$this->global_pointer+1][$this->group_pointer])){
                         $a= $this->showGroupFooter($compare["height"]+$biggestY);
                                $checkpoint=$this->pdf->getY()+$a;
                                //$ghfoot
                                $biggestY=0;
//                                  $tempY=$checkpoint+($compare["height"]*($this->NbLines($compare["width"],$txt)));
                                //$tempY=$this->arraydetail[0]["y_axis"];
                        }
                //if(isset($this->arraygroup)){$this->global_pointer++;}

//### New: Count group-rows (in groups)
                foreach($this->group_count as &$cntval) {
                    $cntval++;
                }
//### New: Count the report rows
                $this->report_count++;
//### End of modifications
                
                $this->global_pointer++;
                   $rownum++;              
                   
            }
//  $ghfoot= $this->showGroupFooter($compare["height"]+$this->pdf->getY());
        }else {
//            echo "No data found";
//            exit(0);
        }
        $this->global_pointer--;
        if($this->arraysummary[0]["height"]>0)
                    $this->summary($checkpoint);
        if(isset($this->arraylastPageFooter)) {
         //  $this->showGroupFooter($compare["height"]+$biggestY);

            $this->lastPageFooter($checkpoint);
        }
        else {
         //    $this->showGroupFooter($compare["height"]+$biggestY);

            $this->pageFooter($checkpoint);
        }
        $this->pdf->Ln();
         if($this->maxy<$this->pdf->GetY())
             $this->maxy=$this->pdf->GetY();
    }

    public function detailNewPage() {
        $this->arraydetail[0]["y_axis"]=$this->arraydetail[0]["y_axis"]- $this->titleheight;

        $field_pos_y=$this->arraydetail[0]["y_axis"];
        $biggestY=0;
        $checkpoint=$this->arraydetail[0]["y_axis"];
        $tempY=$this->arraydetail[0]["y_axis"];
        $i=0;


        if($this->arraysqltable) {
            $oo=0;

            foreach($this->arraysqltable as $row) {
                $oo++;

                //check the group's groupExpression existed and same or not
                if(isset($this->arraygroup)&&($this->global_pointer>0)&&($this->arraysqltable[$this->global_pointer][$this->group_pointer]!=$this->arraysqltable[$this->global_pointer-1][$this->group_pointer])) {

                if(isset($this->arrayVariable)) //if self define variable existing, go to do the calculation
                {
                    $this->variable_calculation($rownum, $this->arraysqltable[$this->global_pointer][$this->group_pointer]);
                }
                    $this->pageFooter();
                    $this->pageHeaderNewPage();
                    $checkpoint=$this->arraydetail[0]["y_axis"];
                    $biggestY = 0;
                    $tempY=$this->arraydetail[0]["y_axis"];
//###                     $this->group_count=0;
                    $this->group_count["$this->group_name"]=1;
//### End of modification
                }

                foreach($this->arraydetail as $compare) //this loop is to count possible biggest Y of the coming row
                {$this->currentrow=$this->arraysqltable[$this->global_pointer];
                    switch($compare["hidden_type"]) {
                        case "field":
                            $txt=$this->analyse_expression($row["$compare[txt]"]);
                            //check group footer existed or not

                            if(isset($this->arraygroup[$this->group_name]["groupFooter"])&&(($checkpoint+($compare["height"]*$txt))>($this->arrayPageSetting[pageHeight]-$this->arraygroup["$this->group_name"][groupFooter][0]["height"]-$this->arrayPageSetting["bottomMargin"]))) {
                             //   $this->showGroupHeader();
                                $this->showGroupFooter();
                                $this->pageFooter();
                               // $this->pdf->AddPage();
                             //   $this->background();
                                $this->pageHeaderNewPage();

                                $checkpoint=$this->arraydetail[0]["y_axis"];
                                $biggestY=0;
                                $tempY=$this->arraydetail[0]["y_axis"];
                            }
                            //check pagefooter existed or not
                            elseif(isset($this->arraypageFooter)&&(($checkpoint+($compare["height"]*($this->NbLines($compare["width"],$txt))))>($this->arrayPageSetting["pageHeight"]-$this->arraypageFooter[0]["height"]-$this->arrayPageSetting["bottomMargin"]))) {
                                $this->showGroupFooter();
                                $this->pageFooter();
                              //  $this->pdf->AddPage();
                                $this->pageHeaderNewPage();
                           //     $this->showGroupHeader();
                             //   $this->background();
                                $headerY = $this->arrayPageSetting["topMargin"]+$this->arraypageHeader[0]["height"];

                                $checkpoint=$this->arraydetail[0]["y_axis"];
                                $biggestY=0;
                                $tempY=$this->arraydetail[0]["y_axis"];
                            }
                            //check lastpagefooter existed or not
                            elseif(isset($this->arraylastPageFooter)&&(($checkpoint+($compare["height"]*($this->NbLines($compare["width"],$txt))))>($this->arrayPageSetting["pageHeight"]-$this->arraylastPageFooter[0]["height"]-$this->arrayPageSetting["bottomMargin"]))) {

                                $this->showGroupFooter();
                                $this->lastPageFooter();
                             //   $this->pdf->AddPage();
                               // $this->background();
                                $this->pageHeaderNewPage();

                              //  $this->showGroupHeader();
                                $checkpoint=$this->arraydetail[0]["y_axis"];
                                $biggestY=0;
                                $tempY=$this->arraydetail[0]["y_axis"];
                            }

                            if(($checkpoint+($compare["height"]*($this->NbLines($compare["width"],$txt))))>$tempY) {
                                $tempY=$checkpoint+($compare["height"]*($this->NbLines($compare["width"],$txt)));
                            }
                            break;
                        case "relativebottomline":
                            break;
                        case "report_count":
//                            $this->report_count++;
                            break;
                               case "group_count":
//###                            $this->group_count++;
                            break;
                        default:
                            $this->display($compare,$checkpoint);

                            break;
                    }
                }



                if($checkpoint+$this->arraydetail[0]["height"]>($this->arrayPageSetting["pageHeight"]-$this->arraypageFooter[0]["height"]-$this->arrayPageSetting["bottomMargin"])) //check the upcoming band is greater than footer position or not
                {
                    $this->pageFooter();

              //      $this->pdf->AddPage();
                //    $this->background();
                    $headerY = $this->arrayPageSetting["topMargin"]+$this->arraypageHeader[0]["height"];
                    $this->pageHeaderNewPage();
                  //  $this->showGroupHeader();

                    $checkpoint=$this->arraydetail[0]["y_axis"];
                    $biggestY=0;
                    $tempY=$this->arraydetail[0]["y_axis"];
                }

                foreach ($this->arraydetail as $out) {
                  $this->currentrow=$this->arraysqltable[$this->global_pointer];
                    switch ($out["hidden_type"]) {
                        case "field":

                            $this->prepare_print_array=array("type"=>"MultiCell","width"=>$out["width"],"height"=>$out["height"],"txt"=>$out["txt"],"border"=>$out["border"],"align"=>$out["align"],"fill"=>$out["fill"],"hidden_type"=>$out["hidden_type"],"printWhenExpression"=>$out["printWhenExpression"],"soverflow"=>$out["soverflow"],"poverflow"=>$out["poverflow"],"link"=>$out["link"],"pattern"=>$out["pattern"]);
                            $this->display($this->prepare_print_array,0,true);

                            if($this->pdf->GetY() > $biggestY) {
                                $biggestY = $this->pdf->GetY();
                            }
                            break;
                        case "relativebottomline":
                        //$this->relativebottomline($out,$tempY);
                            $this->relativebottomline($out,$biggestY);
                            break;
                        default:

                            $this->display($out,$checkpoint);

                            //$checkpoint=$this->pdf->GetY();
                            break;
                    }
                }
                $this->pdf->SetY($biggestY);
                if($biggestY>$checkpoint+$this->arraydetail[0]["height"]) {
                    $checkpoint=$biggestY;
                }
                elseif($biggestY<$checkpoint+$this->arraydetail[0]["height"]) {
                    $checkpoint=$checkpoint+$this->arraydetail[0]["height"];
                }
                else {
                    $checkpoint=$biggestY;
                }
if(isset($this->arraygroup)&&($this->global_pointer>0)&&($this->arraysqltable[$this->global_pointer][$this->group_pointer]!=$this->arraysqltable[$this->global_pointer+1][$this->group_pointer]))
      $this->showGroupFooter($tempY);
                //if(isset($this->arraygroup)){$this->global_pointer++;}
                $this->global_pointer++;
            }
        }else {
            echo utf8_decode("Sorry cause there is not result from this query.");
            exit(0);
        }
        $this->global_pointer--;
                    $rownum++;
                       if($this->arraysummary[0]["height"]>0)
                    $this->summary();
        if(isset($this->arraylastPageFooter)) {
        //     $this->showGroupFooter();
            $this->lastPageFooter();
        }
        else {
        //     $this->showGroupFooter();
            $this->pageFooter();
        }


    }

    public function showGroupHeader($y) {

        $bandheight=$this->arraygrouphead[0]['height'];
        foreach ($this->arraygrouphead as $out) {
            $this->display($out,$y,true);
        }
        return $bandheight;
    }
    public function showGroupFooter($y) {

        //$this->pdf->MultiCell(100,10,"???1-$y,XY=". $this->pdf->GetX().",". $this->pdf->GetY());
        $bandheight=$this->arraygroupfoot[0]['height'];
        foreach ($this->arraygroupfoot as $out) {
            $this->display($out,$y,true);

        }
        $this->footershowed=true;
        return $bandheight;
        //$this->pdf->MultiCell(100,10,"???1-$y,XY=". $this->pdf->GetX().",". $this->pdf->GetY());

    }


//     public function display($arraydata,$y_axis=0,$fielddata=false) {
//   //print_r($arraydata);echo "<br/>";
//     //    $this->pdf->Cell(10,10,"SSSS");
    
            
            
//     $this->Rotate($arraydata["rotation"]);

//     if($arraydata["rotation"]!=""){
                        
//     if($arraydata["rotation"]=="Left"){
//          $w=$arraydata["width"];
//         $arraydata["width"]=$arraydata["height"];
//         $arraydata["height"]=$w;
//             $this->pdf->SetXY($this->pdf->GetX()-$arraydata["width"],$this->pdf->GetY());
//     }
//     elseif($arraydata["rotation"]=="Right"){
//          $w=$arraydata["width"];
//         $arraydata["width"]=$arraydata["height"];
//         $arraydata["height"]=$w;
//             $this->pdf->SetXY($this->pdf->GetX(),$this->pdf->GetY()-$arraydata["height"]);
//     }
//     elseif($arraydata["rotation"]=="UpsideDown"){
//         //soverflow"=>$stretchoverflow,"poverflow"
//         $arraydata["soverflow"]=true;
//         $arraydata["poverflow"]=true;
//        //   $w=$arraydata["width"];
//        // $arraydata["width"]=$arraydata["height"];
//         //$arraydata["height"]=$w;
//         $this->pdf->SetXY($this->pdf->GetX()- $arraydata["width"],$this->pdf->GetY()-$arraydata["height"]);
//     }

//     }

//         if($arraydata["type"]=="SetFont") {


// /*            if($arraydata["font"]=='uGB')
//                 $this->pdf->isUnicode=true;
//             else
//                 $this->pdf->isUnicode=false;

//             $this->pdf->SetFont($arraydata["font"],$arraydata["fontstyle"],$arraydata["fontsize"]);*/
//                   $arraydata["font"]=  strtolower($arraydata["font"]);

//                     $fontfile=$this->fontdir.'/'.$arraydata["font"].'.php';
//           if(file_exists($fontfile) ){
          
//              $fontfile=$this->fontdir.'/'.$arraydata["font"].'.php';
//                 //echo $arraydata["font"].",".$arraydata["fontstyle"].','.$arraydata["fontsize"].','.$fontfile;
//                 $this->pdf->SetFont($arraydata["font"],$arraydata["fontstyle"],$arraydata["fontsize"],$fontfile);
//            }
//            else{
//                 $arraydata["font"]="freeserif";
//                                 if($arraydata["fontstyle"]=="")
//                                     $this->pdf->SetFont('freeserif',$arraydata["fontstyle"],$arraydata["fontsize"],$this->fontdir.'/freeserif.php');
//                                 elseif($arraydata["fontstyle"]=="B")
//                                     $this->pdf->SetFont('freeserifb',$arraydata["fontstyle"],$arraydata["fontsize"],$this->fontdir.'/freeserifb.php');
//                                 elseif($arraydata["fontstyle"]=="I")
//                                     $this->pdf->SetFont('freeserifi',$arraydata["fontstyle"],$arraydata["fontsize"],$this->fontdir.'/freeserifi.php');
//                                 elseif($arraydata["fontstyle"]=="BI")
//                                     $this->pdf->SetFont('freeserifbi',$arraydata["fontstyle"],$arraydata["fontsize"],$this->fontdir.'/freeserifbi.php');
//                                 elseif($arraydata["fontstyle"]=="BIU")
//                                     $this->pdf->SetFont('freeserifbi',"BIU",$arraydata["fontsize"],$this->fontdir.'/freeserifbi.php');
//                                 elseif($arraydata["fontstyle"]=="U")
//                                     $this->pdf->SetFont('freeserif',"U",$arraydata["fontsize"],$this->fontdir.'/freeserif.php');
//                                 elseif($arraydata["fontstyle"]=="BU")
//                                     $this->pdf->SetFont('freeserifb',"U",$arraydata["fontsize"],$this->fontdir.'/freeserifb.php');
//                                 elseif($arraydata["fontstyle"]=="IU")
//                                     $this->pdf->SetFont('freeserifi',"IU",$arraydata["fontsize"],$this->fontdir.'/freeserifbi.php');
                    
                
//             }

//         }
//         elseif($arraydata["type"]=="subreport") {

//             $this->runSubReport($arraydata);
//         }
//         elseif($arraydata["type"]=="MultiCell") { 
//             $currenty=$this->pdf->GetY();
//             if($fielddata==false) {
//         if(($this->allowprintuntill>=$currenty))            
//                 $this->checkoverflow($arraydata,$this->updatePageNo($arraydata["txt"]));
//             }
//             elseif($fielddata==true) {
                  
                    
//                   if(($this->allowprintuntill>=$currenty) )
//                       $this->checkoverflow($arraydata,$this->updatePageNo($this->analyse_expression($arraydata["txt"],$arraydata["isPrintRepeatedValues"] )));
//                   elseif($this->parentcurrentband=="detail")
//                       $this->pdf->Cell(40,10,"SADSD");
// //                  echo $arraydata["txt"]."+\"|(".$y_axis.",".print_r($arraydata,true)."),$this->allowprintuntill,$newy\"<br/><br/>";
                  
                  
//             }
//         }
//         elseif($arraydata["type"]=="SetXY") {

//             $this->pdf->SetXY($arraydata["x"]+$this->arrayPageSetting["leftMargin"],$arraydata["y"]+$y_axis);
//         }
//         elseif($arraydata["type"]=="Cell") {

//            $currenty=$this->pdf->GetY();
//            if(($this->allowprintuntill>=$currenty))                
//             $this->pdf->Cell($arraydata["width"],$arraydata["height"],$this->updatePageNo($arraydata["txt"]),$arraydata["border"],$arraydata["ln"],$arraydata["align"],$arraydata["fill"],$arraydata["link"]);
//            elseif($this->parentcurrentband=="detail")
//             $this->pdf->Cell(40,10,"SADSD");
           
//         }
//         elseif($arraydata["type"]=="Rect") {
//             $this->pdf->Rect($arraydata["x"]+$this->arrayPageSetting["leftMargin"],$arraydata["y"]+$y_axis,$arraydata["width"],$arraydata["height"]);
//         }
//         elseif($arraydata["type"]=="Image") {
            
           
//             $path = $this->analyse_expression($arraydata["path"], "true", $arraydata["type"]);
//             $imgtype=substr($path,-3);
//             // var_export( $arraydata);
//             $arraydata["link"]=$arraydata["link"]."";
//             $arraydata["link"]=$this->analyse_expression($arraydata["link"]);
//             // $path=$this->analyse_expression($arraydata["path"]);
//             // $imgtype=substr($path,-3);
                            

//             if($imgtype=='jpg' || right($path,3)=='jpg' || right($path,4)=='jpeg')
//                  $imgtype="JPEG";
//             elseif($imgtype=='png'|| $imgtype=='PNG')
//                   $imgtype="PNG";
          
//             if(file_exists($path) || left($path,4)=='http' ){ 
//                         $this->pdf->Image($path,$arraydata["x"]+$this->arrayPageSetting["leftMargin"],$arraydata["y"]+$y_axis,
//                               $arraydata["width"],$arraydata["height"],$imgtype,$arraydata["link"]);                                    
//             }
//             elseif($this->left($path,22)== "data:image/jpeg;base64"){
//                 $imgtype="JPEG";
//                 $img=  str_replace('data:image/jpeg;base64,', '', $path);
//                 $imgdata = base64_decode($img);
//                 // echo $path;
//                 $this->pdf->Image('@'.$imgdata,$arraydata["x"]+$this->arrayPageSetting["leftMargin"],$arraydata["y"]+$y_axis,$arraydata["width"],
//                         $arraydata["height"],'',$arraydata["link"]); 
                
//             }
//             elseif($this->left($path,22)==  "data:image/png;base64,"){
//                       $imgtype="PNG";
//                      // $this->pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
//                      $img= str_replace('data:image/png;base64,', '', $path);
//                                  $imgdata = base64_decode($img);
//                     $this->pdf->Image('@'.$imgdata,$arraydata["x"]+$this->arrayPageSetting["leftMargin"],$arraydata["y"]+$y_axis,
//                         $arraydata["width"],$arraydata["height"],'',$arraydata["link"]);             
//             }
        
//         }

//         elseif($arraydata["type"]=="SetTextColor") {
//             $this->pdf->SetTextColor($arraydata["r"],$arraydata["g"],$arraydata["b"]);
//         }
//         elseif($arraydata["type"]=="SetDrawColor") {
//             $this->pdf->SetDrawColor($arraydata["r"],$arraydata["g"],$arraydata["b"]);
//         }
//         elseif($arraydata["type"]=="SetLineWidth") {
//             $this->pdf->SetLineWidth($arraydata["width"]);
//         }
//         elseif($arraydata["type"]=="Line") {
//             $this->pdf->Line($arraydata["x1"]+$this->arrayPageSetting["leftMargin"],$arraydata["y1"]+$y_axis,$arraydata["x2"]+$this->arrayPageSetting["leftMargin"],$arraydata["y2"]+$y_axis);
//         }
//         elseif($arraydata["type"]=="SetFillColor") {
//             $this->pdf->SetFillColor($arraydata["r"],$arraydata["g"],$arraydata["b"]);
//         }
//       elseif($arraydata["type"]=="lineChart") {

//             $this->showLineChart($arraydata, $y_axis);
//         }
//       elseif($arraydata["type"]=="barChart") {

//             $this->showBarChart($arraydata, $y_axis,'barChart');
//         }
//       elseif($arraydata["type"]=="stackedBarChart") {

//             $this->showBarChart($arraydata, $y_axis,'stackedBarChart');
//         }
//       elseif($arraydata["type"]=="stackedAreaChart") {

//             $this->showAreaChart($arraydata, $y_axis,$arraydata["type"]);
//         }
//           elseif($arraydata["type"]=="Barcode"){
            
//             $this->showBarcode($arraydata, $y_axis);
//         }

//     }
    
    public function printParentHeaderFooter(){
        
    }
    
    
    // public function showBarcode($data,$y){
    //     $type=  strtoupper($data['barcodetype']);
    //     $height=$data['height'];
    //     $width=$data['width'];
    //     $x=$data['x'];
    //     $y=$data['y']+$y;
    //     $textposition=$data['textposition'];
    //     $code=$data['code'];
    //     $code=$this->analyse_expression($code);
    //     $modulewidth=$data['modulewidth'];
    //     if($textposition=="" || $textposition=="none")
    //      $withtext = false;
    //     else
    //         $withtext = true;
    //          $style = array(
    //         'border' => false,
    //         'vpadding' => 'auto',
    //         'hpadding' => 'auto',
    //              'text'=>$withtext,
    //         'fgcolor' => array(0,0,0),
    //         'bgcolor' => false, //array(255,255,255)
    //         'module_width' => 1, // width of a single module in points
    //         'module_height' => 1 // height of a single module in points
    //     );

                

    //     switch($type){
    //       case "PDF417":
    //            $this->pdf->write2DBarcode($code, 'PDF417', $x, $y, $width, $height, $style, 'N');
    //           break;
    //       case "DATAMATRIX":
    //           //$this->pdf->Cell( $width,10,$code);
    //           if(left($code,3)=="QR:"){
    //           $code=  right($code,strlen($code)-3);
    //           $this->pdf->write2DBarcode($code, 'QRCODE', $x, $y, $width, $height, $style, 'N');
    //           }
    //           else
    //               $this->pdf->write2DBarcode($code, 'DATAMATRIX', $x, $y, $width, $height, $style, 'N');
    //           break;
    //         case "CODE128":
    //             $this->pdf->write1DBarcode($code, 'C128',  $x, $y, $width, $height, $modulewidth, $style, 'N');

    //           // $this->pdf->write1DBarcode($code, 'C128', $x, $y, $width, $height,"", $style, 'N');
    //           break;
    //       case  "EAN8":
    //              $this->pdf->write1DBarcode($code, 'EAN8', $x, $y, $width, $height, $modulewidth,$style, 'N');
    //           break;
    //       case  "EAN13":
    //              $this->pdf->write1DBarcode($code, 'EAN13', $x, $y, $width, $height, $modulewidth,$style, 'N');
    //           break;
    //       case  "CODE39":
    //              $this->pdf->write1DBarcode($code, 'C39', $x, $y, $width, $height, $modulewidth,$style, 'N');
    //           break;
    //        case  "CODE93":
    //              $this->pdf->write1DBarcode($code, 'C93', $x, $y, $width, $height, $modulewidth,$style, 'N');
    //           break;
    //     }
        

    // }

    // public function relativebottomline($path,$y) {
    //     $extra=$y-$path["y1"];
    //     $this->display($path,$extra);
    // }

    // public function updatePageNo($s) {
    //     return str_replace('$this->PageNo()', $this->pdf->PageNo(),$s);
    // }

    // public function staticText($xml_path) {//$this->pointer[]=array("type"=>"SetXY","x"=>$xml_path->reportElement["x"],"y"=>$xml_path->reportElement["y"]);
    // }

    // public function checkoverflow($arraydata,$txt="") {
    //      $newfont=    $this->recommendFont($txt, $arraydata["font"],$arraydata["pdfFontName"]);
    // $this->pdf->SetFont($newfont,$this->pdf->getFontStyle(),$this->pdf->getFontSize());

    //     $this->print_expression($arraydata);

      
    //     if($this->print_expression_result==true) {

    //         if($arraydata["link"]) {
    //             $arraydata["link"]=$this->analyse_expression($arraydata["link"],"");

    //         }

    //         if($arraydata["writeHTML"]==1 && $this->pdflib=="TCPDF") {
    //             $this->pdf->writeHTML($txt,true, false, true, false, '');
    //         }
    //         elseif($arraydata["poverflow"]=="true"&&$arraydata["soverflow"]=="false") {
                
    //             $this->pdf->Cell($arraydata["width"], $arraydata["height"], $this->formatText($txt, $arraydata["pattern"]),$arraydata["border"],"",$arraydata["align"],$arraydata["fill"],$arraydata["link"]);
                

    //         }
    //         elseif($arraydata["poverflow"]=="false"&&$arraydata["soverflow"]=="false") {
    //             while($this->pdf->GetStringWidth($txt) > $arraydata["width"]) {
    //                 $txt=substr_replace($txt,"",-1);
    //             }
    //             $this->pdf->Cell($arraydata["width"], $arraydata["height"],$this->formatText($txt, $arraydata["pattern"]),$arraydata["border"],"",$arraydata["align"],$arraydata["fill"],$arraydata["link"]);
    

    //         }
    //         elseif($arraydata["poverflow"]=="false"&&$arraydata["soverflow"]=="true") {
    //             $this->pdf->MultiCell($arraydata["width"], $arraydata["height"], $this->formatText($txt, $arraydata["pattern"]), $arraydata["border"], $arraydata["align"], $arraydata["fill"]);
         

    //         }
    //         else {
    //             $this->pdf->MultiCell($arraydata["width"], $arraydata["height"], $this->formatText($txt, $arraydata["pattern"]), $arraydata["border"], $arraydata["align"], $arraydata["fill"]);


    //         }
    //     }
    //     $this->print_expression_result=false;
        


    // }

    // public function hex_code_color($value) {
    //     $r=hexdec(substr($value,1,2));
    //     $g=hexdec(substr($value,3,2));
    //     $b=hexdec(substr($value,5,2));
    //     return array("r"=>$r,"g"=>$g,"b"=>$b);
    // }

    // public function get_first_value($value) {
    //     return (substr($value,0,1));
    // }

    // function right($value, $count) {

    //     return substr($value, ($count*-1));

    // }

    // function left($string, $count) {
    //     return substr($string, 0, $count);
    // }

//     public function analyse_expression($data,$isPrintRepeatedValue="true",$datatype) {
        
//         $arrdata=explode("+",$data);
//         $i=0;

//         foreach($arrdata as $num=>$out){    
//             $i++;

//             $arrdata[$num]=str_replace('"',"",$out);
//             $this->arraysqltable[$this->global_pointer][substr($out,3,-1)];

//             if(substr($out,0,3)=='$F{') {

//                 if($isPrintRepeatedValue=="true" ||$isPrintRepeatedValue=="") {
//                     $arrdata[$num]=$this->arraysqltable[$this->global_pointer][substr($out,3,-1)];
//                 }
//                 else {

//                     if($this->previousarraydata[$arrdata[$num]]==$this->arraysqltable[$this->global_pointer][substr($out,3,-1)]) {
//                         $arrdata[$num]="";

//                     }
//                     else {
//                         $arrdata[$num]=$this->arraysqltable[$this->global_pointer][substr($out,3,-1)];
//                         $this->previousarraydata[$out]=$this->arraysqltable[$this->global_pointer][substr($out,3,-1)];
//                     }
//                 }
//               //  echo $arrdata[$num]."==";
//             }
//             elseif(substr($out,0,3)=='$V{') {
// //###   A new function to handle iReport's "+-/*" expressions.
// // It works like a cheap calculator, without precedences, so 1+2*3 will be 9, NOT 7.
//                 $p1=3;
//                 $p2=strpos($out,"}");
//                 if ($p2!==false){
//                     $total=&$this->arrayVariable[substr($out,$p1,$p2-$p1)]["ans"];
//                     $p1=$p2+1;
//                     while ($p1<strlen($out)){
//                         if (strpos("+-/*",substr($out,$p1,1))!==false) $opr=substr($out,$p1,1);
//                         else $opr="";
//                         $p1=strpos($out,'$V{',$p1)+3;
//                         $p2=strpos($out,"}",$p1);
//                         if ($p2!==false){ $nbr=&$this->arrayVariable[substr($out,$p1,$p2-$p1)]["ans"];
//                             switch ($opr){
//                                 case "+": $total+=$nbr;
//                                           break;
//                                 case "-": $total-=$nbr;
//                                           break;
//                                 case "*": $total*=$nbr;
//                                           break;
//                                 case "/": $total/=$nbr;
//                                           break;
//                             }
//                         }
//                         $p1=$p2+1;
//                     }
//                 }
//                 $arrdata[$num]=$total;
// //### End of modifications, below is the original line.
// //                $arrdata[$num]=&$this->arrayVariable[substr($out,3,-1)]["ans"];
//             }
//             elseif(substr($out,0,3)=='$P{') {
//                 $arrdata[$num]=$this->arrayParameter[substr($out,3,-1)];
//             }
//           //  echo "<br/>";
//         }

//         if($this->left($data,3)=='"("' && $this->right($data,3)=='")"') {
//             $total=0;
//             foreach($arrdata as $num=>$out) {
//                 if($num>0 && $num<$i)
//                     $total+=$out;

//             }
//             return $total;

//         }
//         else {
//               return implode($arrdata);

//         }
        
  
//     }
   // public function analyse_expression($data='',$isPrintRepeatedValue="true",$datatype='')
   // {
   //      //base 64 image can proceed easily
   //      $jpgkey = "data:image/jpeg;base64";
   //      $pngkey = "data:image/png;base64,";        
   //      if($datatype == "Image" && ($this->left($data, 22) == $jpgkey || $this->left($data, 22) == $pngkey))
   //      {
   //           $evalstr="\$result= '".$fm."';";
   //           eval($evalstr);
   //           return $result;
   //      }


   //      //process using general text expression
   //      $pointerposition=$this->global_pointer+$this->offsetposition;
   //      $fields=$this->arraysqltable[$pointerposition];        
        
   //      //replace quoted string, so that can split symbol '+' later
   //      $matchquote=$this->pregMatch('"','"',$data);
   //      $replacedquotedstr=$data;
   //      //convert quoted string into @quoteno_1,@quoteno_2...
   //      foreach($matchquote[0] as $quoteno=>$quotestr)
   //      {
   //          $replacedquotedstr= str_replace($quotestr, '@quoteno_'.$quoteno, $replacedquotedstr);
   //      }
        
   //      //use '+' to split all segment of text, so that we can analyse either wish to concat or + operation
   //      $arrsplitedstr=explode('+',$replacedquotedstr);
   //      foreach($arrsplitedstr as $splitno => $splitedstr)
   //      {
   //          //draw value of Field, parameter and variable 
   //          $matchesfield=$this->pregMatch('$F{','}',$splitedstr);
   //          $matchesparameter=$this->pregMatch('$P{','}',$splitedstr);
   //          $matchesvariable=$this->pregMatch('$V{','}',$splitedstr);

   //          //draw parameter
   //          foreach($matchesparameter[1] as $parano => $paraname)
   //          {
   //              $paravalue=$this->tweakValue($this->arrayParameter[$paraname],'tweek');
   //              if(!$this->isNumber($paravalue))
   //              {
   //                  $paravalue='"'.$paravalue.'"';
   //              }
   //              $splitedstr=str_replace($matchesparameter[0][$parano],$paravalue,$splitedstr);
   //          }
   //          //draw field
   //          foreach($matchesfield[1] as $fieldno => $fieldname)
   //          {
   //              $fieldvalue="";
   //              if(isset($fields[$fieldname]))
   //              {

   //                  $fieldvalue=$this->tweakValue($fields[$fieldname],'tweek');
   //                  if(!$this->isNumber($fieldvalue))
   //                  {
   //                      $fieldvalue='"'.$fieldvalue.'"';
   //                  }
   //              } 
   //              $splitedstr=str_replace($matchesfield[0][$fieldno],$fieldvalue, $splitedstr);
   //          }
   //          //draw variable
   //           foreach($matchesvariable[1] as $variableno => $variablename)
   //          {                          

   //              $variablevalue='';
               
   //                  // for all kind of report count, group count
   //              if(strpos($fm,'_COUNT')!==false)
   //              {
   //                  switch($variablename)
   //                  {
   //                      case 'REPORT_COUNT':
   //                         $variablevalue =  $this->report_count;
   //                      break;
   //                      case $this->grouplist[0]["name"].'_COUNT':
   //                         $variablevalue = $this->group_count[$this->grouplist[0]["name"]]-1;
   //                      break;
   //                      case $this->grouplist[1]["name"].'_COUNT':
   //                         $variablevalue = $this->group_count[$this->grouplist[1]["name"]]-1;
   //                      break;
   //                      case $this->grouplist[2]["name"].'_COUNT':
   //                         $variablevalue = $this->group_count[$this->grouplist[2]["name"]]-1;
   //                      break;
   //                      case $this->grouplist[3]["name"].'_COUNT':
   //                          $variablevalue =$this->group_count[$this->grouplist[3]["name"]]-1;
   //                      break;
   //                      case $this->grouplist[4]["name"].'_COUNT':
   //                          $variablevalue =$this->group_count[$this->grouplist[4]["name"]]-1;
   //                      break;
   //                      case $this->grouplist[5]["name"].'_COUNT':
   //                         $variablevalue = $this->group_count[$this->grouplist[5]["name"]]-1;
   //                      break;
   //                  }
   //              }
   //              else //others kind of variable
   //              {

   //                   if(isset($this->arrayVariable[$variablename]))
   //                  {
   //                      $variablevalue=$this->arrayVariable[$variablename]['ans'];
   //                  }
   //                  else
   //                  {
   //                      $variablevalue='';
   //                  }
                             
                
   //              }
   //              $variablevalue=$this->tweakValue($variablevalue,'tweek');
   //              if(!$this->isNumber($variablevalue))
   //              {
   //                  $variablevalue='"'.$variablevalue.'"';
   //              }
              
   //              $splitedstr=str_replace($matchesfield[0][$fieldno],$variablevalue, $splitedstr);
   //          }
         
   //          $arrsplitedstr[$splitno]=$splitedstr;
   //      }

   //      //merge back separated string (by symbol '+')
   //      $fm='';
   //      foreach($arrsplitedstr as $pcsno => $pcstring)
   //      {            
   //          if(trim($pcstring)=='')
   //          {
   //              continue ;
   //          }

   //          $pcstring=$this->tweakValue($pcstring,'restore');
   //          if(count($arrsplitedstr)>1)
   //          {
   //              if($pcsno>0)
   //              {
   //                  $fm= $fm . '.'.$pcstring;    
   //              }
   //              else
   //              {
   //                  $fm= $pcstring;  
   //              }                
   //          }
   //          else
   //          {
   //              $fm= $pcstring;
   //          }                        
   //      }    
        
   //      //restore back quoted string
   //      foreach($matchquote[0] as $quoteno=>$quotestr)
   //      {
   //          $fm= str_replace( '@quoteno_'.$quoteno, $quotestr, $fm);
   //      }        
    
       
   //     if($fm=='')
   //     {
   //         return "";
   //     }
   //     else
   //     {                                 
   //             $fm=str_replace('convertNumber', '', $fm);
   //             $evalstr="\$result= ".$fm.";";           
   //             eval($evalstr);
           
   //          if($isPrintRepeatedValue=="true" ||$isPrintRepeatedValue=="")
   //          {
   //              return $result;
   //          }
   //          else
   //          {
   //              if($this->lastrowresult[$this->currentuuid]==$result)
   //              {

   //                  $this->lastrowresult[$this->currentuuid]=$result;
   //                  return "";
   //              }
   //              else
   //              {
   //                  $this->lastrowresult[$this->currentuuid] = $result;
   //                  return $result;
   //              }
   //          }
        

   //      }

   //  }

    // public function formatText($txt,$pattern) {
    //     if($pattern=="###0")
    //         return number_format($txt,0,"","");
    //     elseif($pattern=="#,##0")
    //         return number_format($txt,0,".",",");
    //     elseif($pattern=="###0.0")
    //         return number_format($txt,1,".","");
    //     elseif($pattern=="#,##0.0")
    //         return number_format($txt,1,".",",");
    //     elseif($pattern=="###0.00")
    //         return number_format($txt,2,".","");
    //     elseif($pattern=="#,##0.00")
    //         return number_format($txt,2,".",",");
    //     elseif($pattern=="###0.000")
    //         return number_format($txt,3,".","");
    //     elseif($pattern=="#,##0.000")
    //         return number_format($txt,3,".",",");
    //     elseif($pattern=="#,##0.0000")
    //         return number_format($txt,4,".",",");
    //     elseif($pattern=="###0.0000")
    //         return number_format($txt,4,".","");
    //     elseif($pattern=="dd/MM/yyyy" && $txt !="")
    //         return date("d/m/Y",strtotime($txt));
    //     elseif($pattern=="MM/dd/yyyy" && $txt !="")
    //         return date("m/d/Y",strtotime($txt));
    //     elseif($pattern=="yyyy/MM/dd" && $txt !="")
    //         return date("Y/m/d",strtotime($txt));
    //     elseif($pattern=="dd-MMM-yy" && $txt !="")
    //         return date("d-M-Y",strtotime($txt));
    //     elseif($pattern=="dd-MMM-yy" && $txt !="")
    //         return date("d-M-Y",strtotime($txt));
    //     elseif($pattern=="dd/MM/yyyy h.mm a" && $txt !="")
    //         return date("d/m/Y h:i a",strtotime($txt));
    //     elseif($pattern=="dd/MM/yyyy HH.mm.ss" && $txt !="")
    //         return date("d-m-Y H:i:s",strtotime($txt));
    //     else
    //         return $txt;


    // }

    // public function print_expression($data) {
    //     $expression=$data["printWhenExpression"];
    //     $expression=str_replace('$F{','$this->arraysqltable[$this->global_pointer][',$expression);
    //     $expression=str_replace('$P{','$this->arraysqltable[$this->global_pointer][',$expression);
    //     $expression=str_replace('$V{','$this->arraysqltable[$this->global_pointer][',$expression);
    //     $expression=str_replace('}',']',$expression);
    //     $this->print_expression_result=false;
    //     if($expression!="") {
    //         eval('if('.$expression.'){$this->print_expression_result=true;}');
    //     }
    //     elseif($expression=="") {
    //         $this->print_expression_result=true;
    //     }

    // }

    public function runSubReport($d) {
            $this->insubReport=1;
        foreach($d["subreportparameterarray"] as $b) {

            $t = $b->subreportParameterExpression;
            $arrdata=explode("+",$t);
            $i=0;
            foreach($arrdata as $num=>$out) {
                $i++;
                $arrdata[$num]=str_replace('"',"",$out);
                if(substr($out,0,3)=='$F{') {
                    $arrdata[$num]=$this->arraysqltable[$this->global_pointer][substr($out,3,-1)];
                }
                elseif(substr($out,0,3)=='$V{') {
                    $arrdata[$num]=&$this->arrayVariable[substr($out,3,-1)]["ans"];
                }
                elseif(substr($out,0,3)=='$P{') {
                    $arrdata[$num]=$this->arrayParameter[substr($out,3,-1)];
                }
            }
            $t=implode($arrdata);

//            if($this->currentband=='pageHeader'){
////               include ("../simantz/class/PHPJasperXML.inc.php");
////                echo $d['subreportExpression'];
//
//            }
        }
    }

    // public function transferXMLtoArray($fileName) 
    // {
    //     if(!file_exists($fileName))
    //         echo "File - $fileName does not exist";
    //     else {

    //         $xmlAry = $this->xmlobj2arr(simplexml_load_file($fileName));
            
    //         foreach($xmlAry[header] as $key => $value)
    //             $this->arraysqltable["$this->m"]["$key"]=$value;

    //         foreach($xmlAry[detail][record]["$this->m"] as $key2 => $value2)
    //             $this->arraysqltable["$this->m"]["$key2"]=$value2;
    //     }

    //   //  if(isset($this->arrayVariable))   //if self define variable existing, go to do the calculation
    //    //     $this->variable_calculation();

    // }
//wrote by huzursuz at mailinator dot com on 02-Feb-2009 04:44
//http://hk.php.net/manual/en/function.get-object-vars.php
    // public function xmlobj2arr($Data) {
    //     if (is_object($Data)) {
    //         foreach (get_object_vars($Data) as $key => $val)
    //             $ret[$key] = $this->xmlobj2arr($val);
    //         return $ret;
    //     }
    //     elseif (is_array($Data)) {
    //         foreach ($Data as $key => $val)
    //             $ret[$key] = $this->xmlobj2arr($val);
    //         return $ret;
    //     }
    //     else
    //         return $Data;
    // }


    // private function Rotate($type, $x=-1, $y=-1)
    // {
    //     if($type=="")
    //     $angle=0;
    //     elseif($type=="Left")
    //     $angle=90;
    //     elseif($type=="Right")
    //     $angle=270;
    //     elseif($type=="UpsideDown")
    //     $angle=180;

    //     if($x==-1)
    //         $x=$this->pdf->getX();
    //     if($y==-1)
    //         $y=$this->pdf->getY();
    //     if($this->angle!=0)
    //         $this->pdf->_out('Q');
    //     $this->angle=$angle;
    //     if($angle!=0)
    //     {
    //         $angle*=M_PI/180;
    //         $c=cos($angle);
    //         $s=sin($angle);
    //         $cx=$x*$this->pdf->k;
    //         $cy=($this->pdf->h-$y)*$this->pdf->k;
    //         $this->pdf->_out(sprintf('q %.5f %.5f %.5f %.5f %.2f %.2f cm 1 0 0 1 %.2f %.2f cm', $c, $s, -$s, $c, $cx, $cy, -$cx, -$cy));
    //     }
    // }



    
    // public function recommendFont($utfstring,$defaultfont,$pdffont=""){
        
    //             \p{Common}
    //     \p{Arabic}
    //     \p{Armenian}
    //     \p{Bengali}
    //     \p{Bopomofo}
    //     \p{Braille}
    //     \p{Buhid}
    //     \p{CanadianAboriginal}
    //     \p{Cherokee}
    //     \p{Cyrillic}
    //     \p{Devanagari}
    //     \p{Ethiopic}
    //     \p{Georgian}
    //     \p{Greek}
    //     \p{Gujarati}
    //     \p{Gurmukhi}
    //     \p{Han}
    //     \p{Hangul}
    //     \p{Hanunoo}
    //     \p{Hebrew}
    //     \p{Hiragana}
    //     \p{Inherited}
    //     \p{Kannada}
    //     \p{Katakana}
    //     \p{Khmer}
    //     \p{Lao}
    //     \p{Latin}
    //     \p{Limbu}
    //     \p{Malayalam}
    //     \p{Mongolian}
    //     \p{Myanmar}
    //     \p{Ogham}
    //     \p{Oriya}
    //     \p{Runic}
    //     \p{Sinhala}
    //     \p{Syriac}
    //     \p{Tagalog}
    //     \p{Tagbanwa}
    //     \p{TaiLe}
    //     \p{Tamil}
    //     \p{Telugu}
    //     \p{Thaana}
    //     \p{Thai}
    //     \p{Tibetan}
    //     \p{Yi}

    //     if($pdffont!="")
    //         return $pdffont;
    //     if(preg_match("/\p{Han}+/u", $utfstring))
    //             $font="cid0cs";
    //       elseif(preg_match("/\p{Katakana}+/u", $utfstring) || preg_match("/\p{Hiragana}+/u", $utfstring))
    //               $font="cid0jp";
    //       elseif(preg_match("/\p{Hangul}+/u", $utfstring))
    //           $font="cid0kr";
    //       else
    //           $font=$defaultfont;
    //       //echo "$utfstring $font".mb_detect_encoding($utfstring)."<br/>";
          
    //           return $font;//mb_detect_encoding($utfstring);
    // }
}