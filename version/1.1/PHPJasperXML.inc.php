<?php

include_once __DIR__.'/abstractPHPJasperXML.inc.php';


//version 1.1
class PHPJasperXML extends abstractPHPJasperXML{
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
    
    private $titleheight=0;
    // private $fontdir="";
    public $bypassnofont=true;
    public $titlewithpagebreak=false;

    // private $detailallowtill=0;
    // private $offsetposition=0;
    // protected $detailbandqty=0;
    // public $arraysqltable=array();
    
    // public $elementid=0;
    protected $autofetchpara=true;

    // private $group_count = array(); //### New declaration
    public $generatestatus=false;       
    // public $lastrowresult=array();
    private $dbpara=array();

    public function __construct($lang="en",$pdflib="TCPDF") {
        $this->lang=$lang;

        // ini_set('display_errors', 'Off');
         error_reporting(E_ERROR | E_PARSE );
       // $this->setErrorReport(2);
       // echo 'sdsdd';die;
                           
        $this->pdflib=$pdflib;
        // if($this->fontdir=="")
       

    }
    

 
//read level 0,Jasperreport page setting
    public function page_setting($xml_path=[]) {
        $this->arrayPageSetting["orientation"]="P";
        $this->arrayPageSetting["name"]=$xml_path["name"];
        $this->arrayPageSetting["language"]=$xml_path["language"];
        $this->arrayPageSetting["pageWidth"]=$xml_path["pageWidth"];
        $this->arrayPageSetting["pageHeight"]=$xml_path["pageHeight"];
        if(isset($xml_path["orientation"])) {
            $this->arrayPageSetting["orientation"]=substr($xml_path["orientation"],0,1);
        }
        $this->arrayPageSetting["columnWidth"]=$xml_path["columnWidth"];
        $this->arrayPageSetting["leftMargin"]=$xml_path["leftMargin"];
        $this->arrayPageSetting["rightMargin"]=$xml_path["rightMargin"];
        $this->arrayPageSetting["topMargin"]=$xml_path["topMargin"];
        $this->y_axis=$xml_path["topMargin"];
        $this->arrayPageSetting["bottomMargin"]=$xml_path["bottomMargin"];
    }

    

    public function group_handler($xml_path=[]) {

        $this->arraygroup=$xml_path;
    
    
        if($xml_path["isStartNewPage"]=="true")
            $newPageGroup=true;
        else
            $newPageGroup="";
        
       
        
        //echo print_r($this->arraygrouphead,true)."<br/><br/>";
        
        
        foreach($xml_path as $tag=>$out) {
            switch ($tag) {
                case "groupHeader":
                    $this->pointer=&$this->arraygroup[$xml_path["name"]]["groupHeader"];
                    $headercontent=&$this->pointer;
                    $groupheadheight=$out->band["height"];
                    
                    $this->arrayband[]=array("name"=>"group", "gname"=>$xml_path["name"],"isStartNewPage"=>$xml_path["isStartNewPage"],"groupExpression"=>substr($xml_path->groupExpression,3,-1));
                    $this->pointer[]=array("type"=>"band","height"=>$out->band["height"]+0,"y_axis"=>"","printWhenExpression"=>$out->band->printWhenExpression."","groupExpression"=>substr($xml_path->groupExpression,3,-1),"elementid"=>$this->elementid);
//### Modification for group count
                    $gnam=$xml_path["name"]."";             
                    $this->gnam=$xml_path["name"]."";
//                                         $this->group_count[$this->grouplist[$this->groupnochange+1]["name"]]++;
                    $this->group_count[$gnam]=1; // Count rows of groups, we're on the first row of the group.    
                    //### End of modification
                    foreach($out as $band) {
                        $this->default_handler($band);

                    }

                    $this->y_axis=$this->y_axis+$out->band["height"];       //after handle , then adjust y axis
                    break;
                case "groupFooter":
                     
                    $this->pointer=&$this->arraygroup[$xml_path["name"]]["groupFooter"];
                    $footercontent=&$this->pointer;
                    $groupfootheight=$out->band["height"];
                    $this->pointer[]=array("type"=>"band","printWhenExpression"=>$out->band->printWhenExpression."","height"=>$out->band["height"]+0,"y_axis"=>"","groupExpression"=>substr($xml_path->groupExpression,3,-1),"elementid"=>$this->elementid);
                    foreach($out as $b=>$band) {
                        $this->default_handler($band);

                    }
                    break;
                default:

                    break;
            }

        }
         $this->grouplist[$this->totalgroup]=array(
                        "name"=>$xml_path["name"]."",
                        "isnewpage"=>$newPageGroup,
                        "groupheadheight"=>$groupheadheight,
                        "groupfootheight"=> $groupfootheight,
                        "headercontent"=>$headercontent,
                        "footercontent"=>$footercontent
             
            );
         
         

                            $this->arrayVariable[$xml_path["name"]."_COUNT"] = 0;
                                    
                                            
                             
                                            
        $this->totalgroup++;
    }

    public function element_frame($data){
        $x=$data->reportElement["x"]+0;
        $y=$data->reportElement["y"]+0;
        foreach($data as $k=>$el) {
            if($k!="reportElement"){
                $this->isframe=1;
                //echo $k.'<br/>';
                $el->reportElement["x"]+=$x;
                $el->reportElement["y"]+=$y;
                // echo "<br>--";
                $this->default_handler($data->$k);
                // echo '<br>--';
            }
        }
    }




    public function element_subReport($data) {
//        $b=$data->subreportParameter;
                $srsearcharr=array('.jasper','"',"'",' ','$P{SUBREPORT_DIR}+');
                $srrepalcearr=array('.jrxml',"","",'',$this->arrayParameter['SUBREPORT_DIR']);

                if(strpos($data->subreportExpression, '$P{repheader_') === false){
                    if (strpos($data->subreportExpression,'$P{SUBREPORT_DIR}') === false){
                        $subreportExpression=str_replace($srsearcharr,$srrepalcearr,$data->subreportExpression);
                    }
                    else{
                        $subreportExpression=str_replace($srsearcharr,$srrepalcearr,$data->subreportExpression);
                    }
                }
                else{
                    $subreportExpression=$this->analyse_expression(str_replace('.jasper','.jrxml',$data->subreportExpression));
                }

                $b=array();
                foreach($data as $name=>$out){
                        if($name=='subreportParameter'){
                            $b[$out['name'].'']=$out->subreportParameterExpression;
                        }
                }//loop to let multiple parameter pass to subreport pass to subreport
                $this->pointer[]=array("type"=>"subreport", "x"=>$data->reportElement["x"], "y"=>$data->reportElement["y"],
                        "width"=>$data->reportElement["width"], "height"=>$data->reportElement["height"],
                        "subreportparameterarray"=>$b,"connectionExpression"=>$data->connectionExpression,
                        "subreportExpression"=>$subreportExpression,"hidden_type"=>"subreport","elementid"=>$this->elementid);
    }
    public function passDBEnvVariable($paraname='',$paravalue='')
    {
        $this->dbpara[$paraname]=$paravalue;
    }
    public function setDBEnvVariable()
    {
        
        foreach($this->dbpara as $k => $v)
        {
            $this->dbQuery("CALL setEnvPara('$k','$v')");    
        }        

    }

    public function transferDBtoArray($host='',$user='',$password='',$db_name='',$cndriver="mysqli")
    {
        $this->m=0;
    
        if(!$this->connect($host,$user,$password,$db_name,$cndriver))    //connect database
        {
            echo "Fail to connect database";
            exit(0);
        }

        if($this->chartobj)
        {
            $this->chartobj->myconn=$this->myconn;    
        }
        
        if($this->debugsql==true) {
            
            echo "<textarea cols='100' rows='40'>$this->sql</textarea>";
            if($_GET['showhtmldata']=='1')
            {

                $this->drawHTMLTable($this->sql);    
            }
            
            die;
        }

            $this->setDBEnvVariable();
             if($this->datafromphp == 1)
             {
               for($k=0;$k<$this->totalline;$k++){
                        //foreach($this->arrayfield as $out) {
                          //  if($this->recordinfo[$k]["$out"] == ""){
                            //    continue;
                            //}
                            $this->arraysqltable[$this->m]=$this->recordinfo[$k];
                            //[$out]=$this->recordinfo[$k][$out];  
                        //}
                      $this->m++;
               }
             }
             else
             {
                
                $result=$this->dbQuery($this->sql);

                    while ($row = $this->dbFetchData($result))
                    {                       
                      //  foreach($this->arrayfield as $out) 
                        //{
                          //  $fieldvalue = str_replace("\\", "\\\\", $row[$out]);
                            $this->arraysqltable[$this->m]=$row;
                            //[$out]=$fieldvalue;
                        //}
                        
                        $this->m++;
                   }
               

                    

             }     
    }

    public function time_to_sec($time=120) {
        $hours = substr($time, 0, -6);
        $minutes = substr($time, -5, 2);
        $seconds = substr($time, -2);

        return $hours * 3600 + $minutes * 60 + $seconds;
    }

    public function sec_to_time($seconds=120) {
        $hours = floor($seconds / 3600);
        $minutes = floor($seconds % 3600 / 60);
        $seconds = $seconds % 60;

        return sprintf("%d:%02d:%02d", $hours, $minutes, $seconds);
    }

    public function orivariable_calculation() {

        foreach($this->arrayVariable as $k=>$out) {
         //   echo $out['resetType']. "<br/><br/>";
            switch($out["calculation"]) {
                case "Sum":
                    $sum=0;
                    if(isset($this->arrayVariable[$k]['class'])&&$this->arrayVariable[$k]['class']=="java.sql.Time") {
                        foreach($this->arraysqltable as $table) {
                            $sum=$sum+$this->time_to_sec($table["$out[target]"]);
                            //$sum=$sum+substr($table["$out[target]"],0,2)*3600+substr($table["$out[target]"],3,2)*60+substr($table["$out[target]"],6,2);
                        }
                        //$sum= floor($sum / 3600).":".floor($sum%3600 / 60);
                        //if($sum=="0:0"){$sum="00:00";}
                        $sum=$this->sec_to_time($sum);
                    }
                    else {
                        foreach($this->arraysqltable as $table) {
                            $sum=$sum+$table[$out["target"]];
                            $table[$out["target"]];
                        }
                    }

                    $this->arrayVariable[$k]["ans"]=$sum;
                    break;
                case "Average":

                    $sum=0;

                    if(isset($this->arrayVariable[$k]['class'])&&$this->arrayVariable[$k]['class']=="java.sql.Time") {
                        $m=0;
                        foreach($this->arraysqltable as $table) {
                            $m++;

                            $sum=$sum+$this->time_to_sec($table["$out[target]"]);


                        }

                        $sum=$this->sec_to_time($sum/$m);
                        $this->arrayVariable[$k]["ans"]=$sum;

                    }
                    else {
                        $this->arrayVariable[$k]["ans"]=$sum;
                        $m=0;
                        foreach($this->arraysqltable as $table) {
                            $m++;
                            $sum=$sum+$table["$out[target]"];
                        }
                        $this->arrayVariable[$k]["ans"]=$sum/$m;


                    }


                    break;
                case "DistinctCount":
                    break;
                case "Lowest":

                    foreach($this->arraysqltable as $table) {
                        $lowest=$table[$out["target"]];
                        if($table[$out["target"]]<$lowest) {
                            $lowest=$table[$out["target"]];
                        }
                        $this->arrayVariable[$k]["ans"]=$lowest;
                    }
                    break;
                case "Highest":
                    $out["ans"]=0;
                    foreach($this->arraysqltable as $table) {
                        if($table[$out["target"]]>$out["ans"]) {
                            $this->arrayVariable[$k]["ans"]=$table[$out["target"]];
                        }
                    }
                    break;
//### A Count for groups, as a variable. Not tested yet, but seemed to work in print_r()
                case "Count":
                    $value=$this->arrayVariable[$k]["ans"];
                    if( $this->arraysqltable[$this->global_pointer][$this->group_pointer]!=$this->arraysqltable[$this->global_pointer-1][$this->group_pointer])
                       $value=0;
                    $value++;
                    $this->arrayVariable[$k]["ans"]=$value;
                break;  
//### End of modification               
                default:
                    $out["target"]=0;       //other cases needed, temporary leave 0 if not suitable case
                    break;

            }
        }
    }


//       public function variable_calculation($rowno='') {


//         foreach($this->arrayVariable as $k=>$out) {

//             if($out["calculation"]!=""){
//                       $out['target']=str_replace(array('$F{','}'),'',$out['target']);//,  (strlen($out['target'])-1) ); 

                
//             }
                
//          //   echo $out['resetType']. "<br/><br/>";
//             switch($out["calculation"]) {
//                 case "Sum":

//                         $value=$this->arrayVariable[$k]["ans"];
                    
                    
//                     if($out['resetType']=='' || $out['resetType']=='None' ){
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
//                                 //resetGroup
//                                // foreach($this->arraysqltable as $table) {
                              
//                                          $value=round($value,10)+$this->arraysqltable[$rowno]["$out[target]"];
//                                         //echo "k=$k, $value<br/>";
//                               //      $table[$out["target"]];
//                              //   }
//                             }
                         
//                     }// finisish resettype=''
//                     elseif($out['resetType']=='Group') //reset type='group'
//                     {
                  
                        
// //                       print_r($this->grouplist);
// //                       echo "<br/>";
// //                       echo $out['resetGroup'] ."<br/>";
// //                       //                        if( $this->arraysqltable[$this->global_pointer][$this->group_pointer]!=$this->arraysqltable[$this->global_pointer-1][$this->group_pointer])
// //                        if( $this->arraysqltable[$this->global_pointer][$this->group_pointer]!=$this->arraysqltable[$this->global_pointer-1][$this->group_pointer])
//   //                           $value=0;
//   //            
//                        if($this->groupnochange>=0){
                            
                            
//                        //     for($g=$this->groupnochange;$g<4;$g++){
//                          //        $value=0;    
// //                                  $this->arrayVariable[$k]["ans"]=0;
//   //                                echo $this->grouplist[$g]["name"].":".$this->groupnochange."<br/>";
//                            // }
//                        }
//                       //    echo $this->global_pointer.",".$this->group_pointer.",".$this->arraysqltable[$this->global_pointer][$this->group_pointer].",".$this->arraysqltable[$this->global_pointer-1][$this->group_pointer].",".$this->arraysqltable[$rowno]["$out[target]"];
//                                  if(isset($this->arrayVariable[$k]['class'])&&$this->arrayVariable[$k]['class']=="java.sql.Time") {
//                                       $value+=$this->time_to_sec($this->arraysqltable[$rowno]["$out[target]"]);
//                                 //$sum= floor($sum / 3600).":".floor($sum%3600 / 60);
//                                 //if($sum=="0:0"){$sum="00:00";}
//                                 $value=$this->sec_to_time($value);
//                                  }
//                                 else {
                                    
//                                       $value+=$this->arraysqltable[$rowno]["$out[target]"];
                                                           
 
//                                 }
                                  
//                     }

                        
//                     $this->arrayVariable[$k]["ans"]=$value;
                    
//               //      echo ",$value<br/>";
//                     break;
//                 case "Average":
//                     $value=$this->arrayVariable[$k]["ans"];
                    
                    
//                     if($out['resetType']==''|| $out['resetType']=='None' ){
//                             if(isset($this->arrayVariable[$k]['class'])&&$this->arrayVariable[$k]['class']=="java.sql.Time") {
//                                     $value=$this->time_to_sec($value);
//                                     $value+=$this->time_to_sec($this->arraysqltable[$rowno]["$out[target]"]);
//                                 $value=$this->sec_to_time($value);
//                             }
//                             else {
//                                          $value=($value*($this->report_count-1)+$this->arraysqltable[$rowno]["$out[target]"])/$this->report_count;
//                             }                         
//                     }// finisish resettype=''
//                     elseif($out['resetType']=='Group') //reset type='group'
//                     {
//                        if($this->groupnochange>=0){
//                        }
//                                  if(isset($this->arrayVariable[$k]['class'])&&$this->arrayVariable[$k]['class']=="java.sql.Time") {
//                                       $value+=$this->time_to_sec($this->arraysqltable[$rowno]["$out[target]"]);
//                                 $value=$this->sec_to_time($value);
//                                  }
//                                 else {
//                                     $previousgroupcount=$this->group_count[$out['resetGroup']]-2;
//                                     $newgroupcount=$this->group_count[$out['resetGroup']]-1;
//                                     $previoustotal=$value*$previousgroupcount;
//                                     $newtotal=$previoustotal+$this->arraysqltable[$rowno]["$out[target]"];
//                                     $value=($newtotal)/$newgroupcount;
//                                 }
                                  
//                     }
                        
//                     $this->arrayVariable[$k]["ans"]=$value;

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
//                 case "":
//                    // $out["target"]=0;
//                     if(strpos( $out["target"], "_COUNT")==-1)
//                      $this->arrayVariable[$k]["ans"]=$this->analyse_expression( $out['target'], true);
                    
// //                     $out["target"]= $this->analyse_expression( $out['target'], true);
                    
//                     //other cases needed, temporary leave 0 if not suitable case
//                     break;

//             }
              
//         }
//     }


    public function outpage($out_method="I",$filename="", $othername="") {
            $this->detail_yposition = 0;
            if($_REQUEST['forceexceloutput']!=""){
                $this->pdflib="XLS";
                $filename=($othername!="")?($othername.".xls"):"file1.xls";
            }
    
            if($this->pdflib=="TCPDF") {
                if($this->arrayPageSetting["orientation"]=="P")
                    $this->pdf=new TCPDF($this->arrayPageSetting["orientation"],'pt',array(intval($this->arrayPageSetting["pageWidth"]),intval($this->arrayPageSetting["pageHeight"])),true);
                else
                    $this->pdf=new TCPDF($this->arrayPageSetting["orientation"],'pt',array( intval($this->arrayPageSetting["pageHeight"]),intval($this->arrayPageSetting["pageWidth"])),true);
                $this->pdf->setPrintHeader(false);
                $this->pdf->setPrintFooter(false);
                
            }elseif($this->pdflib=="FPDF") {
                if($this->arrayPageSetting["orientation"]=="P")
                    $this->pdf=new FPDF($this->arrayPageSetting["orientation"],'pt',array(intval($this->arrayPageSetting["pageWidth"]),intval($this->arrayPageSetting["pageHeight"])));
                else
                    $this->pdf=new FPDF($this->arrayPageSetting["orientation"],'pt',array(intval($this->arrayPageSetting["pageHeight"]),intval($this->arrayPageSetting["pageWidth"])));
            }
            elseif($this->pdflib=="XLS"){
                

            
                 include_once __DIR__."/ExportXLS.inc.php";
                $xls= new ExportXLS($this,$filename, 'Excel5',$out_method);
                // die;


            }elseif($this->pdflib == 'CSV'){
                
                 include __DIR__."/ExportXLS.inc.php";
                $xls= new ExportXLS($this,$filename, 'CSV',$out_method);
                die;
            }elseif($this->pdflib == 'XLST' || $this->pdflib == 'XLSX'){
                
                include __DIR__."/ExportXLS.inc.php";
                $xls= new ExportXLS($this,$filename, 'Excel2007',$out_method);
                die;
            }
            elseif($this->pdflib == 'HTML'){
//                echo "SADSAD";die;
//                echo $this->pdflib."SADSAD";die;
//
        
                include __DIR__."/ExportHTML.inc.php";
                
                 //  echo $this->pdflib."aaa";die;               
//  echo $this->pdflib."bb";die;               
                 $ht= new ExportHTML($this,$filename, 'HTML',$out_method);
                
  //              die;
            }
         //   }
            //$this->arrayPageSetting["language"]=$xml_path["language"];
//            }
         
        if($this->pdflib=="TCPDF" || $this->pdflib=="FPDF" || $this->pdflib == 'HTML')
        {
            $this->pdf->SetLeftMargin($this->arrayPageSetting["leftMargin"]);
            $this->pdf->SetRightMargin($this->arrayPageSetting["rightMargin"]);
            $this->pdf->SetTopMargin($this->arrayPageSetting["topMargin"]);
            $this->pdf->SetAutoPageBreak(true,$this->arrayPageSetting["bottomMargin"]/2);
            // $this->pdf->AliasNbPages();


            $this->global_pointer=0;
            $detailbandprinted=false;
            foreach ($this->arrayband as $band) {
         
            
//            $this->currentband=$band["name"]; // to know current where current band in!
                switch($band["name"]) {
                    case "title":
                      if($this->arraytitle[0]["height"]>0)
                        $this->title();
                        break;
                    case "pageHeader":
                        //if(!$this->newPageGroup) {
                            
                            if($this->titlewithpagebreak==false)
                            $headerY = $this->arrayPageSetting["topMargin"]+$this->titlebandheight;
                            else 
                            $headerY = $this->arrayPageSetting["topMargin"];
                        
                            $this->pageHeader($headerY);
                            $this->titlebandheight=0;
                        //}else {
                          //  $this->pageHeaderNewPage();
                       // }
                        break;
                  
                    case "detail":

    //                    if(!$this->newPageGroup) {
                        if($detailbandprinted==false){
                            $detailbandprinted=true;
                            $this->detail();
                        }
                        
                        $totalpage=$this->pdf->getNumPages();
                        $lastpagefooterpageno=$this->pdf->getPage();
                        if($totalpage>$lastpagefooterpageno && $totalpage> $this->summaryBandAtPage)
                            $this->pdf->deletePage($totalpage);
                    //$this->pdf->getNumPages();

                        break;

                    case "group":
                        $this->group_pointer=$band["groupExpression"];
                        
                        $this->group_name=$band["gname"];
                        
                        break;
                    case "nodata":
                        echo 'no data';
                        die;
                    break;

                    default:
                        break;

                }

            }

       
        
            if($filename=="")
                $filename=$this->arrayPageSetting["name"].".pdf";

            $this->disconnect($this->cndriver);
            $this->pdf->SetXY(10,10);
             //$this->pdf->IncludeJS($this->createJS());
             //($name, $w, $h, $caption, $action, $prop=array(), $opt=array(), $x='', $y='', $js=false)
             //$this->pdf->Button('print', 100, 10, 'Print', 'Print()',null,null,20,20,true);
header("Access-Control-Allow-Origin: ".$_SERVER['HTTP_ORIGIN']);
header('Access-Control-Allow-Credentials: true');
            return $this->pdf->Output($filename,$out_method);   //send out the complete page
        }
    }

    public function element_pie3DChart($data){


    }








    


public function showPieChart($data=[],$y_axis=0){
      global $tmpchartfolder;


    
//echo "$this->pchartfolder/class/pData.class.php";die;

        include_once("$this->pchartfolder/class/pData.class.php");
        include_once("$this->pchartfolder/class/pDraw.class.php");
        include_once("$this->pchartfolder/class/pPie.class.php");
        include_once("$this->pchartfolder/class/pImage.class.php");

    if($tmpchartfolder=="")
         $tmpchartfolder=$this->pchartfolder."/cache";

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
//echo $sql;die;
        }
    else
        $sql=$this->sql;
    
    
//echo $sql;die;
    $result = $this->dbQuery($sql); //query from db
    $chartdata=array();
    $i=0;
//echo $sql."<br/><br/>";
    $seriesname=array();
    while ($row = $this->dbFetchData($result)) {

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
          //  $DataSet->setAxisName(0,$ylabel);




    $this->chart = new pImage($w,$h,$DataSet);
    //$c = new pChart($w,$h);
    //$this->setChartColor();
    $this->chart->drawRectangle(1,1,$w-2,$h-2);
    $legendfontsize=8;
    $this->chart->setFontProperties(array($chartfontpath,'FontSize'=>$legendfontsize));


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
    $this->chart->setFontProperties(array('FontName'=>$chartfontpath,'FontSize'=>8));


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
//$titlefontname=strtolower($titlefontname);

    $textsetting=array('DrawBox'=>FALSE,'FontSize'=>$titlefontsize,'FontName'=>$titlefontpath,'align'=>TEXT_ALIGN_TOPMIDDLE);
//print_r($textsetting);die;
    $this->chart->drawText($w/3,($titlefontsize+10),$Title,$textsetting);
    }

      $this->chart->setFontProperties(array('FontName'=>$chartfontpath,'FontSize'=>7));


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

    public function pageHeader($headerY='',$newpage=false) {
        
        $this->currentband='pageHeader';// to know current where current band in!
                    
        if(($headerY==""||$this->titleheight==0) || $newpage==true){
        //echo "add page ($headerY";
        //if($this->titlebandheight==0 || $this->titlebandheight=="" ){
            $this->pdf->AddPage();
            $this->background();
                $this->arraypageHeader[0]["y_axis"]=$this->arrayPageSetting["topMargin"];      
                $headerY=$this->arrayPageSetting["topMargin"];      
                
        }
        else{
                    
                    $this->arraypageHeader[0]["y_axis"]=$this->arrayPageSetting["topMargin"];
        }
        
        
        
          
        
            
        foreach ($this->arraypageHeader as $out) {
            
            switch($out["hidden_type"]) {
                case "field":
                    
                    $this->display($out,$headerY,true);
                    
                    break;
                default:
                    
                    $this->display($out,$headerY,false);
                    
                    break;
            }
        }
        
        $this->currentband='';
    }


    
    
    public function pageHeaderNewPage() {
        $this->currentband='pageHeader';
        $this->pdf->AddPage();
        $this->background();
        if(isset($this->arraypageHeader)) {
               //$headerY = $this->arrayPageSetting["topMargin"]+$this->titlebandheight+$this->arraypageHeader[0]["height"];
            $this->arraypageHeader[0]["y_axis"]=$this->arrayPageSetting["topMargin"]+$this->titlebandheight;
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


      public function columnHeader($y='') {
        //$this->pdf->AddPage();
        //$this->background();
            $this->currentband='columnHeader';
            //$this->titlesummary=$this->arraycolumnHeader[0]["height"];
            if($this->titlewithpagebreak==false && $this->pdf->getPage() ==1)
                $y=$this->titleheight+$this->headerbandheight+$this->arrayPageSetting["topMargin"];
                else
            $y=$this->arrayPageSetting["topMargin"]+$this->headerbandheight;
            //print_r($this->arraytitle);die;

        foreach ($this->arraycolumnHeader as $out) {

            switch($out["hidden_type"]) {
                case "field":
                    $this->display($out,$y,true);
                    break;
                default:
                    $this->display($out,$y,false);
                    break;
            }
        }

                $this->currentband='';
    }
  public function columnFooter() {
        //$this->pdf->AddPage();
        //$this->background();
            $this->currentband='columnFooter';
            //$this->titlesummary=$this->arraycolumnHeader[0]["height"];

            //print_r($this->arraytitle);die;
        $y= $this->arrayPageSetting["pageHeight"]-$this->arraypageFooter[0]["height"]-$this->arrayPageSetting["bottomMargin"]-$this->columnfooterbandheight;
       foreach ($this->arraycolumnFooter as $out) {

            switch($out["hidden_type"]) {
                case "field":
                    $this->display($out,$y,true);
                    break;
                default:
                    $this->display($out,$y,false);
                    break;
            }
        }

                $this->currentband='';
    }

    
    public function title() {
          $this->currentband='title';

            
        if(isset($this->arraytitle)) {
            
            $this->pdf->AddPage();
            $this->background();
            $this->titleheight=$this->arraytitle[0]["height"];
            $this->arraytitle[0]["y_axis"]=$this->arrayPageSetting["topMargin"];
            
        foreach ($this->arraytitle as $out) {

            switch($out["hidden_type"]) {
                case "field":
                    $this->display($out,$this->arraytitle[0]["y_axis"],true);
                    break;
                case "break":
               
                     $this->pdf->AddPage();
                    //  $this->background();                  
                      $this->titlewithpagebreak=true;
                    break;
                default:
                    $this->display($out,$this->arraytitle[0]["y_axis"],false);
                    break;
            }
        }
        
        }else{
             $this->titleheight=0;
            
        }


        $this->currentband='';
    }

      public function summary($y='') {
       if($this->global_pointer>$this->totalrowcount){
          $this->report_count--;
          $this->offsetposition=-1;
        }
            $this->currentband='summary';
            $this->titlesummary=$this->arraysummary[0]["height"];
            $currentPage=$this->pdf->GetPage();
                        $currenty=$y+$this->summarybandheight;
    $this->detailallowtill=$this->arrayPageSetting["pageHeight"]-$this->lastfooterbandheight-$this->arrayPageSetting["bottomMargin"]-$this->columnfooterbandheight;

             if($this->detailallowtill <  $currenty ){

                $this->columnFooter();
                $this->pageFooter();
                              $this->pageHeader();
                 $y=$this->arrayPageSetting["topMargin"]+$this->arraypageHeader[0]["height"];
               
            }
                  $this->summaryBandAtPage=$this->pdf->getNumPages();
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
        
        
       
            $this->pdf->SetPage($currentPage);
           $this->report_count++;
             $this->offsetposition=0;
                
        if(isset($this->arraylastPageFooter)){
            $this->columnFooter();
            $this->lastPageFooter();
        }
        
       
        $this->currentband='';
           
    }

    
    
    public function group($headerY='') {
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


    public function pageFooter() {
        $this->currentband='pageFooter';
        if(isset($this->arraypageFooter)) {
            foreach ($this->arraypageFooter as $out) {
                switch($out["hidden_type"]) {
                    case "field":
                        $this->display($out,$this->arrayPageSetting["pageHeight"]-$this->arraypageFooter[0]["height"]-$this->arrayPageSetting["bottomMargin"],true);
                        break;
                    default:
                        $this->display($out,$this->arrayPageSetting["pageHeight"]-$this->arraypageFooter[0]["height"]-$this->arrayPageSetting["bottomMargin"],false);
                        break;
                }
            }
        }
        else {
            $this->lastPageFooter();
        }
        $this->currentband='';
    }

        public function lastPageFooter() {
        $this->currentband='lastPageFooter';
          if($this->global_pointer>$this->totalrowcount){
          $this->report_count--;
          $this->offsetposition=-1;
        }
         $this->pdf->lastPage();
        if(isset($this->arraylastPageFooter)) {
            foreach ($this->arraylastPageFooter as $out) {
                switch($out["hidden_type"]) {
                    case "break":
               
                     $this->pdf->AddPage();
                     $this->pdf->SetY($this->arrayPageSetting["pageHeight"]);                
                      $this->lastpagefooterwithpagebreak=true;
                    break;
                
                    case "field":
                        $this->display($out,
                                $this->arrayPageSetting["pageHeight"]- $this->arraylastPageFooter[0]["height"]-$this->arrayPageSetting["bottomMargin"],true);
                        break;
                    default:
                        $this->display($out,
                                 $this->arrayPageSetting["pageHeight"]-$this->arraylastPageFooter[0]["height"]-$this->arrayPageSetting["bottomMargin"],false);
                        break;
                }
            }
        }
        $this->currentband='';
        $this->noData();
        $this->offsetposition=0;
        
        
    }

    public function noData(){
        $this->currentband='noData';
         
        if(isset($this->arraynoData)) {
            $this->pdf->AddPage();
         $this->nodatawithpagebreak=true;
            foreach ($this->arraynoData as $out) {
                switch($out["hidden_type"]) {
                    case "break":
                        $this->pdf->AddPage();
                        $this->pdf->lastPage();
                     //$this->pageHeader();
                     //$this->pdf->SetY($this->arrayPageSetting["pageHeight"]);                
                     //$this->pdf->SetY();
                      $this->nodatawithpagebreak=true;
                    break;
                
                    case "field":
                    if($this->nodatawithpagebreak==true){
                        $myy=$this->arrayPageSetting["topMargin"];
                        $this->nodatawithpagebreak=false;
                    }
                        $this->display($out,$myy,true);
                        break;
                    default:
                    if($this->nodatawithpagebreak==true){
                        $myy=$this->arrayPageSetting["topMargin"];
                        $this->nodatawithpagebreak=false;
                    }
                        $this->display($out,$myy,false);
                        
                        break;
                }
            }
        }
        $this->currentband='';
        
    }

    public function NbLines($w=0,$txt='') {
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
    

    public function printlongtext($fontfamily='freeserif',$fontstyle='',$fontsize=12)
    {

        //$this->gotTextOverPage=false;
        $this->columnFooter();
        $this->pageFooter();
        $this->pageHeader();
        $this->columnHeader();
        $this->hideheader==true;                    
        $this->currentband='detail';  
       // $fontfile=$this->fontdir.'/'.$fontfamily.'.php';
       //|| $this->bypassnofont==false 
       //   if(file_exists($fontfile) )
       //   {
       //      $fontfile=$this->fontdir.'/'.$arraydata["font"].'.php';
       //      $this->pdf->SetFont($fontfamily,$fontstyle,$fontsize,$fontfile);
       // }
       // else{
       //   $fontfamily="freeserif";
       //                      if($fontstyle=="")
       //                          $this->pdf->SetFont('freeserif',$fontstyle,$fontsize,$this->fontdir.'/freeserif.php');
       //                      elseif($fontstyle=="B")
       //                          $this->pdf->SetFont('freeserifb',$fontstyle,$fontsize,$this->fontdir.'/freeserifb.php');
       //                      elseif($fontstyle=="I")
       //                          $this->pdf->SetFont('freeserifi',$fontstyle,$fontsize,$this->fontdir.'/freeserifi.php');
       //                      elseif($fontstyle=="BI")
       //                          $this->pdf->SetFont('freeserifbi',$fontstyle,$fontsize,$this->fontdir.'/freeserifbi.php');
       //                      elseif($fontstyle=="BIU")
       //                          $this->pdf->SetFont('freeserifbi',"BIU",$fontsize,$this->fontdir.'/freeserifbi.php');
       //                      elseif($fontstyle=="U")
       //                          $this->pdf->SetFont('freeserif',"U",$fontsize,$this->fontdir.'/freeserif.php');
       //                      elseif($fontstyle=="BU")
       //                          $this->pdf->SetFont('freeserifb',"U",$fontsize,$this->fontdir.'/freeserifb.php');
       //                      elseif($fontstyle=="IU")
       //                          $this->pdf->SetFont('freeserifi',"IU",$fontsize,$this->fontdir.'/freeserifbi.php');
                
            
       //  }
                                    
                $this->pdf->SetFont($fontfamily,$fontstyle,$fontsize);//,$this->fontdir.'/'.$fontfamily.'php');
                                        
        $this->pdf->SetTextColor($this->forcetextcolor_r,$this->forcetextcolor_g,$this->forcetextcolor_b);
        //$this->pdf->SetTextColor(44,123,4);
        $this->pdf->SetFillColor($this->forcefillcolor_r,$this->forcefillcolor_g,$this->forcefillcolor_b);
        $bltxt=$this->continuenextpageText;                                      
        $this->pdf->SetY($this->arraypageHeader[0]["height"]+$this->columnheaderbandheight+$this->arrayPageSetting["topMargin"]);
        $this->pdf->SetX($bltxt['x']);
        $maxheight=$this->arrayPageSetting["pageHeight"]-$this->arraypageFooter[0]["height"]-$this->pdf->GetY()-$bltxt['height'];

        $this->pdf->MultiCell($bltxt['width'],$bltxt['height'],$bltxt['txt'],
        $bltxt['border'],
        $bltxt['align'],$bltxt['fill'],$bltxt['ln'],'','',$bltxt['reset'],
        $bltxt['streth'],$bltxt['ishtml'],$bltxt['autopadding'],$maxheight-$bltxt['height'],$bltxt['valign']);
                            
       if($this->pdf->balancetext!='')
       {
            $this->continuenextpageText=array('width'=>$bltxt["width"], 'height'=>$bltxt["height"], 
                'txt'=>$this->pdf->balancetext, 'border'=>$bltxt["border"] ,'align'=>$bltxt["align"], 'fill'=>$bltxt["fill"],'ln'=>1,
                        'x'=>$bltxt['x'],'y'=>'','reset'=>true,'streth'=>0,'ishtml'=>false,'autopadding'=>true,'valign'=>$bltxt['valign']);
                $this->pdf->balancetext='';
                                            
                $this->printlongtext($fontfamily,$fontstyle,$fontsize);
                                                
      }
        //echo $this->currentband;  
        if( $this->pdf->balancetext=='' && $this->currentband=='detail')
        {
            if($this->maxpagey['page_'.($this->pdf->getPage()-1)]=='')
            {
                $this->maxpagey['page_'.($this->pdf->getPage()-1)]=$this->pdf->GetY();
            }
            else
            {
                if($this->maxpagey['page_'.($this->pdf->getPage()-1)]<$this->pdf->GetY())
                {
                        $this->maxpagey['page_'.($this->pdf->getPage()-1)]=$this->pdf->GetY();
                }
            }
            
        }
    }
        
        
    public function detail() 
    {
        $currentpage= $this->pdf->getNumPages();
        $this->maxpagey=array();
        $this->currentband='detail';
        $this->arraydetail[0][0]["y_axis"]=$this->arraydetail[0]["y_axis"];//- $this->titleheight;
        $field_pos_y=$this->arraydetail[0][0]["y_axis"];
        $biggestY=0;
        $tempY=$this->arraydetail[0][0]["y_axis"];
        
        if(isset($this->SubReportCheckPoint))
        {
            $checkpoint=$this->SubReportCheckPoint;    
        }
        
        $columnheadery=$this->arrayPageSetting["topMargin"]+$this->titlebandheight+$this->arraypageHeader[0]["height"];
        $colheader=$this->columnHeader($columnheadery);
        
             
        if($this->pdf->getPage()>1)
        {
            $checkpoint=$this->arrayPageSetting["topMargin"]+$this->titlebandheight+ $this->arraypageHeader[0]["height"] +
                            $this->columnheaderbandheight;                      
            $this->groupnochange=0;
             $checkpoint= $this->showGroupHeader($checkpoint,false);
        }
        else
        {
            
            $checkpoint=$this->arrayPageSetting["topMargin"]+$this->orititlebandheight+$this->arraypageHeader[0]["height"] +
                        $this->columnheaderbandheight;          
             $checkpoint=$this->showGroupHeader( $checkpoint,false);
        }
                
            
        if($this->pdf->getPage()>1)
        {
           $this->titlebandheight=0;
        }
            
        $isgroupfooterprinted=false;
                
        if($this->titlewithpagebreak==false)
        {
            $this->maxpagey=array('page_0'=>$checkpoint);        
        }
        else
        {
                $this->maxpagey=array('page_1'=>$checkpoint);
        }

        $rownum=0; 
        
        if($this->arraysqltable) 
        {    
            $n=0;
            foreach($this->arraysqltable as $row) 
            {
           
                $n++;
                $this->report_count++;
                $currentpage= $this->pdf->getNumPages();
                $this->pdf->lastPage();
                $this->hideheader==false;
                if($n>1)
                {
                    $checkpoint=$this->maxpagey['page_'.($this->pdf->getNumPages()-1)];
                }

                $pageheight=$this->arrayPageSetting["pageHeight"];
                $footerheight=$this->footerbandheight;
                $headerheight=$this->headerbandheight;
                $bottommargin=$this->arrayPageSetting["bottomMargin"];
        
                if($this->checkSwitchGroup("header") )
                {
                              
                    $checkpoint=$this->showGroupHeader($checkpoint,true);
                    $currentpage= $this->pdf->getNumPages();
                    $this->maxpagey[($this->pdf->getPage()-1)]=$checkpoint;
                    $this->pdf->SetY($checkpoint); 
                }
                $this->group_count[$this->grouplist[0]["name"]]++;
                $this->group_count[$this->grouplist[1]["name"]]++;
                $this->group_count[$this->grouplist[2]["name"]]++;
                $this->group_count[$this->grouplist[3]["name"]]++;
                if(isset($this->arrayVariable)) //if self define variable existing, go to do the calculation
                {
                    $this->variable_calculation($rownum, $this->arraysqltable[$this->global_pointer][$this->group_pointer]);
                }
                                    

                //begin page handling
                for($d=0;$d<$this->detailbandqty;$d++)
                {
                    $detailheight=$this->detailbandheight[$d];
                    $this->pdf->setPage($this->pdf->getNumPages());
                    $currentpage= $this->pdf->getNumPages();
                
                    if(($checkpoint +$detailheight >$this->detailallowtill) && ($this->pdf->getPage()>1) ||
                            ($checkpoint +$detailheight >$this->detailallowtill-$this->orititleheight) && ($this->pdf->getNumPages()==1) 
                                 )
                    {
                        
                        $this->columnFooter();
                        $this->pageFooter();
                        $this->pageHeader();
                        $colheader=$this->columnHeader($this->arrayPageSetting["topMargin"]+$this->titlebandheight+$this->arraypageHeader[0]["height"]);           
                        $currentpage= $this->pdf->getNumPages();
                        $checkpoint=$this->arrayPageSetting["topMargin"]+$this->arraypageHeader[0]["height"]+$this->titlebandheight+$this->columnheaderbandheight;//$this->arraydetail[0]["y_axis"]- $this->titleheight;
                        $this->maxpagey[($this->pdf->getPage()-1)]=$checkpoint;
                    }
                    
                    $this->currentband='detail';
        
                    /* begin page handling*/

                    
                    foreach ($this->arraydetail[$d] as $out) 
                    {

                        $this->currentrow=$this->arraysqltable[$this->global_pointer];
                  
                        switch ($out["hidden_type"]) 
                        {
                            case "field":
                                $maxheight=$this->detailallowtill-$checkpoint;//                            
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
                                        "poverflow"=>$out["poverflow"],
                                        "link"=>$out["link"],
                                        "pattern"=>$out["pattern"],
                                        "font"=>$out['font'],
                                        "pdfFontName"=>$out['pdfFontName'],
                                        "fontstyle"=>$out['fontstyle'],
                                        "fontsize"=>$out['fontsize'],
                                        "writeHTML"=>$out["writeHTML"],
                                        "isPrintRepeatedValues"=>$out["isPrintRepeatedValues"],
                                        "valign"=>$out["valign"],
                                        "x"=>$out["x"],
                                        "y"=>$out["y"],
                                        "rotation"=>$out["rotation"],
                                        "uuid"=>$out["uuid"], 
                                        "linktarget"=>$out['linktarget']);
                                $this->display($this->prepare_print_array,0,true,$maxheight);

                                break;
                            case "relativebottomline":                        
                                $this->relativebottomline($out,$biggestY);
                                break;
                            case "subreport":
                                    $checkpoint=$this->display($out,$checkpoint);
                                  if($this->maxpagey['page_'.($this->pdf->getNumPages()-1)]<$checkpoint)
                                  {
                                    $this->maxpagey['page_'.($this->pdf->getNumPages()-1)]=$checkpoint;  
                                  }
                                  
                            break;
                            default:
                            $this->detail_yposition=$checkpoint;
                                $this->display($out,$checkpoint);
                                $maxheight=$this->detailallowtill-$checkpoint;
                            break;
                        }
                        
                        if($this->pdf->getNumPages()>1)
                        {                       
                            $this->pdf->setPage($currentpage);                    
                        }

                    }
                    //$this->pdf->lastPage();
                    $checkpoint=$this->maxpagey['page_'.($this->pdf->getNumPages()-1)];
                    
                } //end loop detail band[]
                $this->pdf->setPage($this->pdf->getNumPages());                
                $this->global_pointer++;
                $rownum++;           
                $headerY=$checkpoint;                              
            }                
             $this->global_pointer--;
        }
        else 
        {
            if($this->blockdisplaynodata != 1)
            {
                echo "No data found";
                exit(0);
            } 
        }
                  
        if($this->totalgroup>0)
        {
            $totalgroupheight=0;
            $this->report_count++;
            $this->global_pointer++;      
            $checkpoint=$this->showGroupFooter($totalgroupheight+$this->maxpagey['page_'.($this->pdf->getNumPages()-1)]);
            $totalgroupheight+=$this->grouplist[$i]["groupfootheight"];  
        }
        $this->totalrowcount=$this->report_count-1;
        $this->summary($checkpoint);
    }


    public function showGroupHeader($y=0,$printgroupfooter=false) 
    {
        $atnewpage=0;
        $groupno=$this->groupnochange;
         

       
        if($printgroupfooter==true){
            
            $y=$this->showGroupFooter($y);
            if($isnewpage==true){
                $this->columnFooter();
        $this->pageFooter();
                $this->pageHeader();
                $colheader=$this->columnHeader($this->arrayPageSetting["topMargin"]+$this->titlebandheight+$this->arraypageHeader[0]["height"]);           
                $y=$this->arrayPageSetting["topMargin"]+$this->arraypageHeader[0]["height"]+$this->columnheaderbandheight;
            }
        }else{
            $this->groupnochange=-1;
            
        }
            
        
        $this->currentband='groupHeader';


                    
        for($groupno=$this->groupnochange+1; $groupno  <$this->totalgroup;$groupno++){
          //  echo "$groupno=$this->groupnochange; $groupno  <" . ($this->totalgroup-1).";groupno++<br/>";
            $groupname=$this->grouplist[$groupno]["name"];
            
            foreach($this->arrayVariable as $v=>$a){

                
//                echo "/Reset Group:".$a["resetGroup"].", current group=$groupname:";
//                
                if($a["resetGroup"]!=""&& $a["resetGroup"]==$groupname){
//                    echo  $this->arrayVariable[$v]["ans"]."-";
                 $this->arrayVariable[$v]["ans"]=0;
//                echo"<br/><br/>";
                }
            }
            
            
           $isnewpage = $this->grouplist[$groupno]["isnewpage"];
           $headercontent=$this->grouplist[$groupno]["headercontent"];
           $bandheight=$this->grouplist[$groupno]["groupheadheight"];
           $yplusbandheight=$y+$bandheight;
           if( ($printgroupfooter==true && $isnewpage==1 && $atnewpage==0) || $yplusbandheight>$this->detailallowtill){
            
                $this->columnFooter();
        $this->pageFooter();
        $this->pageHeader();
                $colheader=$this->columnHeader($this->arrayPageSetting["topMargin"]+$this->titlebandheight+$this->arraypageHeader[0]["height"]);           
        $y=$this->arrayPageSetting["topMargin"]+$this->arraypageHeader[0]["height"]+$this->columnheaderbandheight;
                $atnewpage=1;
            }
        
            $rr=$this->analyse_expression($headercontent[0]["printWhenExpression"]);
            //echo "Header:".print_r($headercontent[0],true)."<br/><br/>";
         if($headercontent[0]["printWhenExpression"]!=""){
                if(!$rr){
                    $yplusbandheight-=$y;
                    continue;
                }
         }
            foreach ($headercontent as $out){
                
                    $this->display($out,$y,true);
            }
            
            
            $y=$y+$bandheight;

            }
        
         $this->currentband='';
         $bandheight=$this->grouplist[$groupno]["groupheadheight"];
        // echo "header finish at: $y, max till $this->detailallowtill<br/>";
      if($printgroupfooter==false)
         $this->report_count=0;
      else
          $this->report_count++;
        return $y;//+$bandheight;
    }
    
    public function showGroupFooter($y=0) {
       // return $y;
      // for($i=0;$i<$this->totalgroup;$i++){
     $this->report_count--;
     $this->offsetposition=-1;
        $this->currentband='groupFooter';
       
        //
// 
//     echo     print_r( $this->group_count,true)."<br/>";

        for($groupno=$this->totalgroup;$groupno  >$this->groupnochange;$groupno--){
        
        $bandheight=$this->grouplist[$groupno]['groupfootheight'];
        $yplusbandheight=$y+$bandheight;
        $footercontent=$this->grouplist[$groupno]["footercontent"];
        
        
         $rr=$this->analyse_expression($footercontent[0]["printWhenExpression"]);
         
         //echo $this->analyse_expression('$F{classtype}').",".$footercontent[0]["printWhenExpression"]."hard code result:".($this->analyse_expression('$F{classtype}')!='5A').",result:$rr<br/><br/>";
         if($footercontent[0]["printWhenExpression"]!=""){
                if(!$rr){
                    $yplusbandheight-=$y;
                    continue;
                }
         }
        
        if($yplusbandheight>$this->detailallowtill){
            
                                                $this->columnFooter();
                        $this->pageFooter();
                        $this->pageHeader();
                                                $colheader=$this->columnHeader($this->arrayPageSetting["topMargin"]+$this->titlebandheight+$this->arraypageHeader[0]["height"]);           
                $y=$this->arrayPageSetting["topMargin"]+$this->arraypageHeader[0]["height"]+$this->columnheaderbandheight;
            
        }

        foreach ($footercontent as $out){
            $this->display($out,$y,true);
        }
        
        $y=$y+$bandheight;
        
        
    }
     
        $this->currentband='';
         $this->offsetposition=0;
         
         for($i=$this->groupnochange+1;$i<$this->totalgroup; $i++){
                             $this->group_count[$this->grouplist[$i]["name"]]=1;
                        }
      
        return $y;

     

    }


//     public function display($arraydata=[],$y_axis=0,$fielddata=false,$maxheight=0) {

//         $this->currentuuid=$arraydata["uuid"];
//         $this->Rotate($arraydata["rotation"]);
    
//         if($arraydata["rotation"]!=""){
//             if($arraydata["rotation"]=="Left"){
//                  $w=$arraydata["width"];
//                 $arraydata["width"]=$arraydata["height"];
//                 $arraydata["height"]=$w;
//                     $this->pdf->SetXY($this->pdf->GetX()-$arraydata["width"],$this->pdf->GetY());
//             }
//             elseif($arraydata["rotation"]=="Right"){
//                  $w=$arraydata["width"];
//                 $arraydata["width"]=$arraydata["height"];
//                 $arraydata["height"]=$w;
//                     $this->pdf->SetXY($this->pdf->GetX(),$this->pdf->GetY()-$arraydata["height"]);
//             }
//             elseif($arraydata["rotation"]=="UpsideDown"){
//                 //soverflow"=>$stretchoverflow,"poverflow"
//                 $arraydata["soverflow"]=true;
//                 $arraydata["poverflow"]=true;
//                //   $w=$arraydata["width"];
//                // $arraydata["width"]=$arraydata["height"];
//                 //$arraydata["height"]=$w;
//                 $this->pdf->SetXY($this->pdf->GetX()- $arraydata["width"],$this->pdf->GetY()-$arraydata["height"]);
//             }
//         }
//         if($arraydata["type"]=="SetFont") {
//         //echo $arraydata["font"]."<br/>";
//                        $arraydata["font"]=  strtolower(str_replace(' ', '', $arraydata["font"]));

//                         if($arraydata["fontstyle"]=="BI")
//                             $fontfile=$this->fontdir.'/'.$arraydata["font"].'bi.php';
//                         elseif($arraydata["fontstyle"]=="I")
//                             $fontfile=$this->fontdir.'/'.$arraydata["font"].'i.php';
//                         elseif($arraydata["fontstyle"]=="B")
//                             $fontfile=$this->fontdir.'/'.$arraydata["font"].'b.php';
//                         else
//                              $fontfile=$this->fontdir.'/'.$arraydata["font"].'.php';
//             //echo $fontfile." : ";
//             if(!file_exists($fontfile))
//                 $fontfile=$this->fontdir.'/'.$arraydata["font"].'.php';
//             //echo $fontfile."<br/>";
//           if(file_exists($fontfile) || $this->bypassnofont==false){

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
        

//             return $this->runSubReport($arraydata,$y_axis);

//         }
//         elseif($arraydata["type"]=="MultiCell") {
          
         
//            // echo $arraydata["txt"].':'. $this->currenttextfield."<br>"; 
// //echo " $this->report_count $this->currenttextfield".print_r($arraydata,true)."<br/><br/>";
//             if($arraydata["hidden_type"]=='statictext' || $fielddata==false) {
//                 $this->checkoverflow($arraydata,$this->updatePageNo($arraydata["txt"]),'',$maxheight);
//             }
//             elseif($fielddata==true) {
            
//                  $res=$this->analyse_expression($arraydata["txt"],$arraydata["isPrintRepeatedValues"]);

//                 $this->checkoverflow($arraydata,$this->updatePageNo($res),$maxheight);
//             }
            

//         }
//         elseif($arraydata["type"]=="SetXY") {
//             $this->pdf->SetXY($arraydata["x"]+$this->arrayPageSetting["leftMargin"],$arraydata["y"]+$y_axis);
//         }
//         elseif($arraydata["type"]=="Cell") {
// //                print_r($arraydata);
//   //              echo "<br/>";

//             $this->pdf->Cell($arraydata["width"],$arraydata["height"],$this->updatePageNo($arraydata["txt"]),$arraydata["border"],$arraydata["ln"],
//                        $arraydata["align"],$arraydata["fill"],$arraydata["link"]."",0,true,"T",$arraydata["valign"]);


//         }
//         elseif($arraydata["type"]=="Rect"){
//         if($arraydata['mode']=='Transparent')
//         $style='';
//         else
//         $style='FD';
//           //      $this->pdf->SetLineStyle($arraydata['border']);
//             $this->pdf->Rect($arraydata["x"]+$this->arrayPageSetting["leftMargin"],$arraydata["y"]+$y_axis,$arraydata["width"],$arraydata["height"],
//             $style,$arraydata['border'],$arraydata['fillcolor']);
//                 }
//         elseif($arraydata["type"]=="RoundedRect"){
//             if($arraydata['mode']=='Transparent')
//                 $style='';
//             else
//             $style='FD';
//             //
//                 //        $this->pdf->SetLineStyle($arraydata['border']);
                        
//                           if($arraydata['printWhenExpression']==""  ||  $this->print_expression($arraydata)){
//                               foreach($arraydata['border'] as $bs=>$ba){
//                                 foreach($ba as $bbc)
//                                      $this->pdf->SetLineStyle($bbc) ;
                                  
//                               }

//              $this->pdf->RoundedRect($arraydata["x"]+$this->arrayPageSetting["leftMargin"], $arraydata["y"]+$y_axis, 
//                                  $arraydata["width"], $arraydata["height"], $arraydata["radius"], '1111', 
//             $style, array(),$arraydata['fillcolor']);
//                           }
//             }
//         elseif($arraydata["type"]=="Ellipse"){
//             //$this->pdf->SetLineStyle($arraydata['border']);
//              $this->pdf->Ellipse($arraydata["x"]+$arraydata["width"]/2+$this->arrayPageSetting["leftMargin"], $arraydata["y"]+$y_axis+$arraydata["height"]/2, $arraydata["width"]/2,$arraydata["height"]/2,
//                 0,0,360,'FD',$arraydata['border'],$arraydata['fillcolor']);
//         }
//         else if($arraydata["type"]=="Image")
//         {
//             $path = $this->analyse_expression($arraydata["path"], "true", $arraydata["type"]);
//             $imgtype=substr($path,-3);
//             $arraydata["link"]=$arraydata["link"]."";
            
//             $arraydata["link"]=$this->analyse_expression($arraydata["link"]);
            
            
//             if($imgtype=='jpg' || right($path,3)=='jpg' || right($path,4)=='jpeg')
//                  $imgtype="JPEG";
//             elseif($imgtype=='png'|| $imgtype=='PNG')
//                   $imgtype="PNG";
//           //echo $path;
//         if(file_exists($path) || $this->left($path,4)=='http' ){  
//                     $this->pdf->Image($path,$arraydata["x"]+$this->arrayPageSetting["leftMargin"],$arraydata["y"]+$y_axis,
//                           $arraydata["width"],$arraydata["height"],$imgtype,$arraydata["link"]);                        
//         }
//         elseif($this->left($path,22)==  "data:image/jpeg;base64"){
//             $imgtype="JPEG";
//             $img=  str_replace('data:image/jpeg;base64,', '', $path);
//             $imgdata = base64_decode($img);

//             $sizedata = $this->setDisplayImageSize($arraydata, $imgdata);
//             if(array_key_exists("scale_type", $arraydata))
//             {
//                 if($arraydata["scale_type"] == "Clip")
//                 {
//                     $realImage = imagecreatefromstring($imgdata);
//                     $cropImage = imagecrop($realImage, ['x' => 0, 'y' => 0, 'width' => $sizedata["width"], 'height' => $sizedata['height']]);
//                     ob_start();
//                     imagepng($cropImage);
//                     $imgdata = ob_get_contents();

//                     ob_end_clean();
//                 }
//             }
//             $this->pdf->Image('@'.$imgdata,$arraydata["x"]+$this->arrayPageSetting["leftMargin"],$arraydata["y"]+$y_axis,
//                 $sizedata["width"],$sizedata["height"],'',$arraydata["link"]); 
            
//         }
//         elseif($this->left($path,22)==  "data:image/png;base64,"){
//                   $imgtype="PNG";
//                  // $this->pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

//                  $img= str_replace('data:image/png;base64,', '', $path);
//                  $imgdata = base64_decode($img);

//                  $sizedata = $this->setDisplayImageSize($arraydata, $imgdata);
//                 if(array_key_exists("scale_type", $arraydata))
//                 {
//                     if($arraydata["scale_type"] == "Clip")
//                     {
//                         $realImage = imagecreatefromstring($imgdata);
//                         $cropImage = imagecrop($realImage, ['x' => 0, 'y' => 0, 'width' => $sizedata["width"], 'height' => $sizedata['height']]);
 
//                         ob_start();
//                         imagepng($cropImage);
//                         $imgdata = ob_get_contents();

//                         ob_end_clean();
//                     }
//                 }
            
                            
//             $this->pdf->Image('@'.$imgdata,$arraydata["x"]+$this->arrayPageSetting["leftMargin"],$arraydata["y"]+$y_axis, 
//                 $sizedata["width"],$sizedata["height"],'',$arraydata["link"]); 
    
            
//         }

//         }

//         elseif($arraydata["type"]=="SetTextColor") {
//             $this->textcolor_r=$arraydata['r'];
//             $this->textcolor_g=$arraydata['g'];
//             $this->textcolor_b=$arraydata['b'];
            
//             if($this->hideheader==true && $this->currentband=='pageHeader')
//                 $this->pdf->SetTextColor(100,33,30);
//             else
//                 $this->pdf->SetTextColor($arraydata["r"],$arraydata["g"],$arraydata["b"]);
//         }
//         elseif($arraydata["type"]=="SetDrawColor") {
//             $this->drawcolor_r=$arraydata['r'];
//             $this->drawcolor_g=$arraydata['g'];
//             $this->drawcolor_b=$arraydata['b'];
//             $this->pdf->SetDrawColor($arraydata["r"],$arraydata["g"],$arraydata["b"]);
//         }
//         elseif($arraydata["type"]=="SetLineWidth") {
//             $this->pdf->SetLineWidth($arraydata["width"]);
//         }
//         elseif($arraydata["type"]=="break"){
      
          
//         }
//         elseif($arraydata["type"]=="Line") {
//             $printline=false;
//             if($arraydata['printWhenExpression']=="")
//                 $printline=true;
//             else
//                 $printline=$this->analyse_expression($arraydata['printWhenExpression']);
//             if($printline)
//             $this->pdf->Line($arraydata["x1"]+$this->arrayPageSetting["leftMargin"],$arraydata["y1"]+$y_axis,$arraydata["x2"]+$this->arrayPageSetting["leftMargin"],$arraydata["y2"]+$y_axis,$arraydata["style"]);
//         }
//         elseif($arraydata["type"]=="SetFillColor") {
//             $this->fillcolor_r=$arraydata['r'];
//             $this->fillcolor_g=$arraydata['g'];
//             $this->fillcolor_b=$arraydata['b'];
//             $this->pdf->SetFillColor($arraydata["r"],$arraydata["g"],$arraydata["b"]);
//         }
//       elseif($arraydata["type"]=="lineChart") {
//         // echo 'lineChart';
//           $this->chartobj->showChart($arraydata, $y_axis,'lineChart',$this->pdf);
//         }
//       elseif($arraydata["type"]=="barChart") 
//       {
//         // echo 'barChart';
//             $this->chartobj->showChart($arraydata, $y_axis,'barChart',$this->pdf);
//         }
//       elseif($arraydata["type"]=="pieChart") {

//             $this->chartobj->showChart($arraydata, $y_axis,'pieChart',$this->pdf);
//         }
//       elseif($arraydata["type"]=="stackedBarChart") 
//       {
//           // echo 'stackbarChart';
//             $this->chartobj->showChart($arraydata, $y_axis,'stackedBarChart',$this->pdf);
//         }
//       elseif($arraydata["type"]=="stackedAreaChart") 
//       {
//         // echo 'stackareaChart';
//             $this->chartobj->showAreaChart($arraydata, $y_axis,$arraydata["type"],$this->pdf);
//         }
//         elseif($arraydata["type"]=="Barcode"){
            
//             $this->showBarcode($arraydata, $y_axis);
//         }
//          elseif($arraydata["type"]=="CrossTab"){
            
//             $this->showCrossTab($arraydata, $y_axis);
//         }

//              $this->currentuuid="";
//     }

    
    
    public function showCrossTab($a=[], $y_axis=0){
        /*
         * 
        "type"=>"CrossTab","x"=>$x,"y"=>$y,"width"=>$width,"height"=>$height,"dataset"=>$dataset
               'rowgroup'=>$rowgroup,'colgroup'=>$colgroup,'measuremethod'=>$measuremethod,'measurefield'=>$measurefield
         * crosstabcell
         * 
         */
        $x =$a['x'];
        $y =$a['y'];
        $width =$a['width'];
        $height =$a['height'];
        $dataset =$a['dataset'];
        $rowgroup =$a['rowgroup'][0];
        
        $colgroup =$a['colgroup'][0];
        $measuremethod =$a['measuremethod'];
        $measurefield =str_replace(array('$F{','}'),"",$a['measurefield']);
        $ce=$a['crosstabcell'];
       $this->pdf->SetXY($x+$this->arrayPageSetting["leftMargin"],$y+$y_axis);
       //set default font
          $this->pdf->SetFont('freeserif','',10,$this->fontdir.'/freeserif.php');
       foreach($ce as $no =>$v){
          // echo $no.$v["style"]["ceheaderalign"];
           if($no==0){  
//               echo $no."<br/>";
  //             echo $no.$v["style"]["ceheaderalign"];
                $cellbgcolor=$v["style"]["ceheaderbgcolor"];
                $cellvalign=$v["style"]["ceheadervalign"];
                $cellalign=$v["style"]["ceheaderalign"];
             if($cellalign=="")
                    $cellalign="Center";
                $cellisbold=$v["style"]["ceheaderisbold"];
               /*array("ceheaderalign"=>$ceheaderalign,"ceheadervalign"=>$ceheadervalign,"ceheaderbgcolor"=>$ceheaderbgcolor,
                 "ceheaderisbold"=>$ceheaderisbold,"width"=>$width);*/
           }
           if($no==1){    
         //      echo $no.$v["style"]["ceheaderalign"];
                $coltotalbgcolor=$v["style"]["ceheaderbgcolor"];
                $coltotalvalign=$v["style"]["ceheadervalign"];
                $coltotalalign=$v["style"]["ceheaderalign"];
                $coltotalisbold=$v["style"]["ceheaderisbold"];
             if($coltotalalign=="")
                    $coltotalalign="Center";
               /*array("ceheaderalign"=>$ceheaderalign,"ceheadervalign"=>$ceheadervalign,"ceheaderbgcolor"=>$ceheaderbgcolor,
                 "ceheaderisbold"=>$ceheaderisbold,"width"=>$width);*/
           }
           if($no==2){    
                $rowtotalbgcolor=$v["style"]["ceheaderbgcolor"];
                $rowtotalvalign=$v["style"]["ceheadervalign"];
                $rowtotalalign=$v["style"]["ceheaderalign"];
                $rowtotalisbold=$v["style"]["ceheaderisbold"];
             if($rowtotalalign=="")
                    $rowtotalalign="Center";
        //       echo $no.$v["style"]["ceheaderalign"];
               /*array("ceheaderalign"=>$ceheaderalign,"ceheadervalign"=>$ceheadervalign,"ceheaderbgcolor"=>$ceheaderbgcolor,
                 "ceheaderisbold"=>$ceheaderisbold,"width"=>$width);*/
           }
           if($no==3){    
            //   echo $no.$v["style"]["ceheaderalign"];
                $alltotalbgcolor=$v["style"]["ceheaderbgcolor"];
                $alltotalvalign=$v["style"]["ceheadervalign"];
                $alltotalalign=$v["style"]["ceheaderalign"];
                $alltotalisbold=$v["style"]["ceheaderisbold"];
             if($alltotalalign=="")
                    $alltotalalign="Center";
               /*array("ceheaderalign"=>$ceheaderalign,"ceheadervalign"=>$ceheadervalign,"ceheaderbgcolor"=>$ceheaderbgcolor,
                 "ceheaderisbold"=>$ceheaderisbold,"width"=>$width);*/
           }
           
           
       }


       
        $rowtitle=  $rowgroup['name'];
        $coltitle=  $colgroup['name'];
        

        
        $sql=$this->sql;

    $q = $this->dbQuery($sql); //query from db
    $arrdata=array();
    $colarr=array();
    while($row=$this->dbFetchData( $q)){
        $rowname=$row[$rowtitle];
        $colname=$row[$coltitle];
        array_push($colarr,$colname);
        if($arrdata[$rowname][$colname]=="")
              $arrdata[$rowname][$colname]=0;
        
      if($measuremethod=='Count')
       $arrdata[$rowname][$colname]+=1;
      elseif($measuremethod=='Sum')
          $arrdata[$rowname][$colname]+=$row[$measurefield];
        
        //$this->pdf->Cell(10,10,print_r($row,true));
        //$this->pdf->Ln();
    }
    $colarr=array_unique($colarr);
    
    $table='<table border="1" cellspacing="0" cellpadding="2" width="'.$width.'px"><tr><td style="background-color:'.$colgroup['style']['colheaderbgcolor'].'"></td>';
    $rowtotal=0;
    $coltotal=0;
    $grantotal=0;
    foreach($colarr as $cv)
          $table.='<td style="background-color:'.$colgroup['style']['colheaderbgcolor'].';
              text-align:'.$colgroup['style']['colheaderalign'].'">'. $cv.'</td>';  
          
    $table.='<td style="background-color:'.$rowtotalbgcolor.';text-align:right">Total</td></tr>';
    
    
    foreach($arrdata as $r =>$v){
        //$rowgroup $rowheaderbgcolor
        
        
    $table.='<tr><td style="background-color:'.$rowgroup['style']['rowheaderbgcolor'].';
                        text-align:'.$rowgroup['style']['rowheaderalign'].';'.
            '">'.$r.'</td>';
        foreach($colarr as $cv){
            if($arrdata[$r][$cv]==""){
                $arrdata[$r][$cv]=0;
            }
            
              if($measuremethod=='Sum')
            $table.='<td style="text-align:right;background-color:'.$cellbgcolor.'">'. number_format($arrdata[$r][$cv],2,'.',',').'</td>'; 
              else
                    $table.='<td style="text-align:right;background-color:'.$cellbgcolor.'">'. $arrdata[$r][$cv].'</td>'; 
           $rowtotal+= $arrdata[$r][$cv];
           
           if( $coltotalarr[$cv]=="") 
                $coltotalarr[$cv]=0;
           $coltotalarr[$cv]+= $arrdata[$r][$cv];
           $grantotal+=$arrdata[$r][$cv];
           
        }
        
            
    if($measuremethod=='Sum')
    $table.='<td style="background-color:'.$rowtotalbgcolor.'; text-align:right">'.number_format($rowtotal,2,'.',',').'</td></tr>';
    else
        $table.='<td style="background-color:'.$rowtotalbgcolor.'; text-align:right">'.$rowtotal.'</td></tr>';
    
    $rowtotal=0;
    }
    
    $table.='<tr><td style="background-color:'.$rowtotalbgcolor.'; text-align:right">Total</td>';
    foreach($colarr as $cv){
        if($measuremethod=='Sum')
                  $table.='<td style="background-color:'.$coltotalbgcolor.';text-align:right">'. number_format($coltotalarr[$cv],2,'.',',')."</td>";  
    
        else
                 $table.='<td style="background-color:'.$coltotalbgcolor.';text-align:right">'. $coltotalarr[$cv]."</td>";  
    
    
    
    }
            if($measuremethod=='Sum')
    $table.='<td style="text-align:right;background-color:'.$rowtotalbgcolor.'">'.number_format($grantotal,2,'.',',').'</td></tr></table>';
            else
$table.='<td style="text-align:right;background-color:'.$rowtotalbgcolor.'">'.$grantotal.'</td></tr></table>';  
$this->pdf->SetXY($x+$this->arrayPageSetting["leftMargin"],$this->arrayPageSetting["topMargin"]+$y+$y_axis+20);
$this->pdf->writeHTML($table);
    
    
         
    }
    
   

//     public function checkoverflow($arraydata=[],$txt="",$maxheight=0) {
//     $newfont=    $this->recommendFont($txt, $arraydata["font"],$arraydata["pdfFontName"]);
    
    
//     $this->pdf->SetFont($newfont,$this->pdf->getFontStyle(),$this->pdf->getFontSize());
    
//        $this->print_expression_result = $this->analyse_expression($arraydata['printWhenExpression']);

//         if($this->print_expression_result ==false)
//         {
//             echo 'print_expression_result:false <br/>';
//         }
//         else if($this->print_expression_result =='')
//         {
//             echo 'print_expression_result:empty <br/>';
//         }
//         else
//         {

        
//             echo 'print_expression_result:"'.$this->print_expression_result.'"<br/>';
        
//         }
//         if($this->print_expression_result!=false   ) {
//            // echo $arraydata["link"];
//             if($arraydata["link"]) {
//                 //print_r($arraydata);
                
//                 //$this->debughyperlink=true;
//               //  echo $arraydata["link"].",print:".$this->print_expression_result;
//                 $arraydata["link"]=$this->analyse_expression($arraydata["link"],"");
//                 //$this->debughyperlink=false;
//             }
//             //print_r($arraydata);
            
            
//             if($arraydata["writeHTML"]==1 && $this->pdflib=="TCPDF") {
//              // $this->pdf->writeHTML($txt);
//                 $this->pdf->writeHTML($txt, true, false, false, true);
//                 // $html, $ln=true, $fill=false, $reseth=false, $cell=false, $align=''
//             $this->pdf->Ln();
//                     if($this->currentband=='detail'){
//                     if($this->maxpagey['page_'.($this->pdf->getPage()-1)]=='')
//                         $this->maxpagey['page_'.($this->pdf->getPage()-1)]=$this->pdf->GetY();
//                     else{
//                         if($this->maxpagey['page_'.($this->pdf->getPage()-1)]<$this->pdf->GetY())
//                             $this->maxpagey['page_'.($this->pdf->getPage()-1)]=$this->pdf->GetY();
//                     }
//                 }
            
//             }
            
//             elseif($arraydata["poverflow"]=="false"&&$arraydata["soverflow"]=="false") {
//                             if($arraydata["valign"]=="M")
//                                     $arraydata["valign"]="C";
//                                 if($arraydata["valign"]=="")
//                                     $arraydata["valign"]="T";                
                                
//                 while($this->pdf->GetStringWidth($txt) > $arraydata["width"]) {
//                     if($txt!=$this->pdf->getAliasNbPages() && $txt!=' '.$this->pdf->getAliasNbPages())
//                     $txt=substr_replace($txt,"",-1);
//                 }
                            

//                 $x=$this->pdf->GetX();
//                 $y=$this->pdf->GetY();
//                 foreach($this->arrayParameter as  $pv => $ap)
//                 {

//                     if($arraydata["pattern"]=='$P{'.$pv.'}')
//                         $arraydata["pattern"]=$ap;    
//                 }

//                 $text=$this->formatText($txt, $arraydata["pattern"]);
//                 $this->pdf->Cell($arraydata["width"], $arraydata["height"],$text,
//                         $arraydata["border"],"",$arraydata["align"],$arraydata["fill"],
//                         $arraydata["link"],
//                         0,true,"T",$arraydata["valign"]);
                      
// //                if($arraydata["link"]) { //
// //                    $tmpalign="Left";
// //                    if($arraydata["valign"]=="R")
// //                        $tmpalign="Right";
// //                    elseif($arraydata["valign"]=="C")
// //                        $tmpalign="Center";
// //                    $textlen=strlen($text);
// //                    $hidetxt="";
// //                    for($l=0;$l<$textlen*2;$l++)
// //                    $hidetxt.="&nbsp;";
// //                              $imagehtml='<a style="text-decoration: none;" href="'.$arraydata["link"].'">'.
// //                                      '<div style="text-decoration: none;text-align:$tmpalign;float:left;width:'.$arraydata["width"].';margin:0px">'.$hidetxt.'</div></a>';
// //                         //     $this->pdf->writeHTMLCell($arraydata["width"],$arraydata["height"], $x,$y-$arraydata["height"],$imagehtml);//,1,0,true);
// //                }
// //                
                
//                 $this->pdf->Ln();
//                     if($this->currentband=='detail'){
//                     if($this->maxpagey['page_'.($this->pdf->getPage()-1)]=='')
//                         $this->maxpagey['page_'.($this->pdf->getPage()-1)]=$this->pdf->GetY();
//                     else{
//                         if($this->maxpagey['page_'.($this->pdf->getPage()-1)]<$this->pdf->GetY())
//                             $this->maxpagey['page_'.($this->pdf->getPage()-1)]=$this->pdf->GetY();
//                     }
//                 }
        
//             }
//              elseif($arraydata["soverflow"]=="true") {
//                 if($arraydata["valign"]=="C")
//                                     $arraydata["valign"]="M";
//                                 if($arraydata["valign"]=="")
//                                     $arraydata["valign"]="T";
                                
//                 $x=$this->pdf->GetX();
//                 $y=$this->pdf->GetY();
//                              //if($arraydata["link"])   echo $arraydata["linktarget"].",".$arraydata["link"]."<br/><br/>";
//                 $this->pdf->MultiCell($arraydata["width"], $arraydata["height"], $this->formatText($txt, $arraydata["pattern"]),$arraydata["border"] 
//                                 ,$arraydata["align"], $arraydata["fill"],1,'','',true,0,false,true,$maxheight);//,$arraydata["valign"]);
        
//                 if( $this->pdf->balancetext=='' && $this->currentband=='detail'){
//                     if($this->maxpagey['page_'.($this->pdf->getPage()-1)]=='')
//                         $this->maxpagey['page_'.($this->pdf->getPage()-1)]=$this->pdf->GetY();
//                     else{
//                         if($this->maxpagey['page_'.($this->pdf->getPage()-1)]<$this->pdf->GetY())
//                             $this->maxpagey['page_'.($this->pdf->getPage()-1)]=$this->pdf->GetY();
//                     }
//                 }
                
//             //$this->pageFooter();
//             if($this->pdf->balancetext!='' ){
//                 $this->continuenextpageText=array('width'=>$arraydata["width"], 'height'=>$arraydata["height"], 'txt'=>$this->pdf->balancetext,
//                         'border'=>$arraydata["border"] ,'align'=>$arraydata["align"], 'fill'=>$arraydata["fill"],'ln'=>1,
//                             'x'=>$x,'y'=>'','reset'=>true,'streth'=>0,'ishtml'=>false,'autopadding'=>true);
//                     $this->pdf->balancetext='';
//                     $this->forcetextcolor_b=$this->textcolor_b;
//                     $this->forcetextcolor_g=$this->textcolor_g;
//                     $this->forcetextcolor_r=$this->textcolor_r;
//                     $this->forcefillcolor_b=$this->fillcolor_b;
//                     $this->forcefillcolor_g=$this->fillcolor_g;
//                     $this->forcefillcolor_r=$this->fillcolor_r;
//                     if($this->continuenextpageText)
//                         $this->printlongtext($this->pdf->getFontFamily(),$this->pdf->getFontStyle(),$this->pdf->getFontSize());
                    
//                     }          
                
                    
         

//             }
//             elseif($arraydata["poverflow"]=="true") {
           
//                             if($arraydata["valign"]=="M")
//                                     $arraydata["valign"]="C";
//                                 if($arraydata["valign"]=="")
//                                     $arraydata["valign"]="T"; 
                                
//                 $this->pdf->Cell($arraydata["width"], $arraydata["height"],  $this->formatText($txt, $arraydata["pattern"]),$arraydata["border"],"",$arraydata["align"],$arraydata["fill"],$arraydata["link"]."",0,true,"T",
//                                 $arraydata["valign"]);
//                 $this->pdf->Ln();
//                     if($this->currentband=='detail'){
//                     if($this->maxpagey['page_'.($this->pdf->getPage()-1)]=='')
//                         $this->maxpagey['page_'.($this->pdf->getPage()-1)]=$this->pdf->GetY();
//                     else{
//                         if($this->maxpagey['page_'.($this->pdf->getPage()-1)]<$this->pdf->GetY())
//                             $this->maxpagey['page_'.($this->pdf->getPage()-1)]=$this->pdf->GetY();
//                     }
//                 }
            
//             }
           
//             else {
//                 //MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0) {   
//                 $this->pdf->MultiCell($arraydata["width"], $arraydata["height"], $this->formatText($txt, $arraydata["pattern"]), $arraydata["border"], 
//                             $arraydata["align"], $arraydata["fill"],1,'','',true,0,true,true,$maxheight);
//                 if( $this->pdf->balancetext=='' && $this->currentband=='detail'){
//                     if($this->maxpagey['page_'.($this->pdf->getPage()-1)]=='')
//                         $this->maxpagey['page_'.($this->pdf->getPage()-1)]=$this->pdf->GetY();
//                     else{
//                         if($this->maxpagey['page_'.($this->pdf->getPage()-1)]<$this->pdf->GetY())
//                             $this->maxpagey['page_'.($this->pdf->getPage()-1)]=$this->pdf->GetY();
//                     }
//                 }
//             if($this->pdf->balancetext!=''){
//                 $this->continuenextpageText=array('width'=>$arraydata["width"], 'height'=>$arraydata["height"], 'txt'=>$this->pdf->balancetext,
//                         'border'=>$arraydata["border"] ,'align'=>$arraydata["align"], 'fill'=>$arraydata["fill"],'ln'=>1,
//                             'x'=>$x,'y'=>'','reset'=>true,'streth'=>0,'ishtml'=>false,'autopadding'=>true);
//                     $this->pdf->balancetext='';
//                     $this->forcetextcolor_b=$this->textcolor_b;
//                     $this->forcetextcolor_g=$this->textcolor_g;
//                     $this->forcetextcolor_r=$this->textcolor_r;
//                     $this->forcefillcolor_b=$this->fillcolor_b;
//                     $this->forcefillcolor_g=$this->fillcolor_g;
//                     $this->forcefillcolor_r=$this->fillcolor_r;
//                     $this->gotTextOverPage=true;
//                     if($this->continuenextpageText)
//                         $this->printlongtext($this->pdf->getFontFamily(),$this->pdf->getFontStyle(),$this->pdf->getFontSize());
                    
//                     }          



//             }
//         }
//         $this->print_expression_result=false;
        


//     }

   

    public function runSubReport($d=[],$current_y=0) {

            $this->insubReport=1;
        foreach($d["subreportparameterarray"] as $name=>$b) {
            $t = $b->subreportParameterExpression;
            if($t=='')
                $t=$b;
             $t=$this->analyse_expression($t,true);
            //echo "$name:$b,$t<br/>";
           // $arrdata=explode("+",$t);
            //$i=0;
            $arrdata2[$name.""]=$t;
           /* foreach($arrdata as $num=>$out) {
                $i++;
//                $arrdata[$num]=str_replace('"',"",$out);
                if(substr($b,0,3)=='$F{') {
                    $arrdata2[$name.'']=$this->arraysqltable[$this->global_pointer][substr($b,3,-1)];
                }
                elseif(substr($b,0,3)=='$V{') {
                    $arrdata2[$name.'']=&$this->arrayVariable[substr($b,3,-1)]["ans"];
                }
                elseif(substr($b,0,3)=='$P{') {
                    $arrdata2[$name.'']=$this->arrayParameter[substr($b,3,-1)];
                }
            }*/
           // $t=implode($arrdata);
        }
           
           //$current_y=100;
             $a= $this->includeSubReport($d,$arrdata2,$current_y);
            $this->insubReport=0;
            return $current_y;
    }
    

    public function includeSubReport($d=[],$arrdata=[],$current_y=0){ 
        
               include_once __DIR__."/PHPJasperXMLSubReport.inc.php";
               $srxml=  simplexml_load_file($d['subreportExpression']);
               $PHPJasperXMLSubReport= new PHPJasperXMLSubReport($this->lang,$this->pdflib,$d['x']);
               $PHPJasperXMLSubReport->arrayParameter=$arrdata;
               $PHPJasperXMLSubReport->debugsql=$this->debugsql;
               $PHPJasperXMLSubReport->xml_dismantle($srxml);
               
               
              $this->passAllArrayDatatoSubReport($PHPJasperXMLSubReport,$d,$current_y,$arrdata);
               
               $PHPJasperXMLSubReport->transferDBtoArray($this->db_host,$this->db_user,$this->db_pass,$this->dbname,$this->cndriver);
               $PHPJasperXMLSubReport->pdf=$this->pdf;

               $PHPJasperXMLSubReport->outpage();    //page output method I:standard output  D:Download file
  
               $this->SubReportCheckPoint=$PHPJasperXMLSubReport->SubReportCheckPoint;
               //echo $this->SubReportCheckPoint."<br/>";
               $PHPJasperXMLSubReport->MainPageCurrentY=0;
               return $PHPJasperXMLSubReport->maxy;
    }

    public function passAllArrayDatatoSubReport($PHPJasperXMLSubReport,$d,$current_y,$data){
        
                $PHPJasperXMLSubReport->arrayMainPageSetting=$this->arrayPageSetting;
                if(isset($this->arraypageHeader)) {
                    $PHPJasperXMLSubReport->arrayPageSetting["subreportpageHeight"]=$PHPJasperXMLSubReport->arrayPageSetting["pageHeight"];
                    $PHPJasperXMLSubReport->arrayMainpageHeader=$this->arraypageHeader;
                    $PHPJasperXMLSubReport->arrayMainpageFooter=$this->arraypageFooter;

                    if($this->currentband=='pageHeader'){ ///here need to add more conditions to fulfill different band subreport
                        $PHPJasperXMLSubReport->TopHeightFromMainPage=$PHPJasperXMLSubReport->arrayMainPageSetting["topMargin"]+$d['y'];
                    }
                    else{      
                        $PHPJasperXMLSubReport->TopHeightFromMainPage=$PHPJasperXMLSubReport->arrayMainPageSetting["topMargin"]
                                                                                                +$PHPJasperXMLSubReport->arrayMainpageHeader[0]["height"]+$d['y'];
                    }
###set different initial Y for subreport of each detail loop of main report
                if($current_y>$PHPJasperXMLSubReport->TopHeightFromMainPage)
                    {$PHPJasperXMLSubReport->TopHeightFromMainPage=$current_y+$d['y'];}
###
                $PHPJasperXMLSubReport->BottomHeightFromMainPage=$PHPJasperXMLSubReport->arrayMainPageSetting["bottomMargin"]
                                                                                                +$PHPJasperXMLSubReport->arrayMainpageFooter[0]["height"];
                $PHPJasperXMLSubReport->arrayPageSetting["leftMargin"]=$PHPJasperXMLSubReport->arrayPageSetting["leftMargin"]+$this->arrayPageSetting["leftMargin"];
###Set fixed pageHeight constant despite the changes of $PHPJasperXMLSubReport->TopHeightFromMainPage due to subreport in Detail band
                $PHPJasperXMLSubReport->arrayPageSetting["pageHeight"]=$this->arrayPageSetting["pageHeight"]
                              -($PHPJasperXMLSubReport->arrayMainPageSetting["topMargin"]
                                +$PHPJasperXMLSubReport->arrayMainpageHeader[0]["height"]+$d['y'])
                                  -$this->arraypageFooter[0]["height"]
                                 -$PHPJasperXMLSubReport->arrayMainPageSetting["bottomMargin"]-$d['y'];
                }
                if(isset($this->arraypageFooter)) {
                    $PHPJasperXMLSubReport->arrayMainpageFooter=$this->arraypageFooter;
                }
                if(isset($this->arraygroup)) {
                    $PHPJasperXMLSubReport->arrayMaingroup=$this->arraygroup;
                }
                if(isset($this->arraylastPageFooter)) {
                    $PHPJasperXMLSubReport->arrayMainlastPageFooter=$this->arraylastPageFooter;
                }
                if(isset($this->arraytitle)) {
                    $PHPJasperXMLSubReport->arrayMaintitle=$this->arraytitle;
                }
               $PHPJasperXMLSubReport->parentcurrentband=$this->currentband;
                switch($this->currentband){
                    case "detail":
                         $PHPJasperXMLSubReport->allowprintuntill=$this->detailallowtill;
                        break;
                    default:
                        $PHPJasperXMLSubReport->allowprintuntill=$current_y+$d['height'];
                   //         echo print_r($d,true)."<br/>";
//
                        break;
                    
                }

    }



private function checkSwitchGroup($type="header"){

    
    /*
     * 1. loop record
     * 2. start loop group check (for i)
     *      if current last group no difference, return false
     *      if last group have difference, print that last group footer set changegroupno=i
     *    stop loop group check
     * 3. print all new group header start from i to totalgroup
     */
     $this->groupnochange=-1;
//       echo sizeof($this->grouplist).",$this->global_pointer,$type<br/>";
      if(sizeof($this->grouplist)>0 && ($this->global_pointer>0)){
  
          $i=-1;
          
          foreach($this->grouplist as $g){
             
              if($type=="header"){
                  
                  //echo ->groupExpression."<br/>";
                  
                 if($this->arraysqltable[$this->global_pointer][$g['headercontent'][0]["groupExpression"]] != 
                    $this->arraysqltable[$this->global_pointer-1][$g['headercontent'][0]["groupExpression"]] ){
                     
                    
                             //   if($this->groupnochange=="")
                               //     $this->groupnochange=0;
                               // else
                    
                     
                     
                                   
             $this->groupnochange=$i;
             
            //  echo  $this->arraysqltable[$this->global_pointer][$g["name"]] ." match ". $this->arraysqltable[$this->global_pointer-1][$g["name"]] .":".$this->groupnochange."<br/>"; 
                               return true;
                 
          }
          $i++;
          }
       }
       
       
      
           return false;
      
      } 
    
}

}