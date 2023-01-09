<?php

//version 1
class PHPJasperXML {
    private $adjust=1.2;
    public $version="1.0";
    private $pdflib;
    private $lang;
    private $previousarraydata;
    public $debugsql=false;
    private $myconn;
    private $con;
    public $sql;
    public $group_name;
    public $newPageGroup = false;
    private $curgroup=0;
    public $grouplist=array();
    private $groupno=0;
    public $totalgroup=0;
    private $footershowed=true;
    private $groupnochange=0; //use for detect record change till which level of grouping (grouping support multilevel)
    private $titleheight=0;
    private $fontdir="";
    public $bypassnofont=true;
    public $titlewithpagebreak=false;
    private $detailallowtill=0;
    private $offsetposition=0;
    private $detailbandqty=0;
    public $arraysqltable=array();
    private $chartscaling=1.35;
    public $elementid=0;
    private $autofetchpara=true;
    private $report_count=0;        //### New declaration (variable exists in original too)
    private $group_count = array(); //### New declaration
        public $generatestatus=false;       
        public $lastrowresult=array();
        private $dbpara=array();
    public function __construct($lang="en",$pdflib="TCPDF") {
        $this->lang=$lang;
        // $this->setErrorReport(2);
        ini_set('display_errors', 'Off');
       
        
        $this->pdflib=$pdflib;
        if($this->fontdir=="")
        $this->fontdir=dirname(__FILE__)."/../../tcpdf/fonts";

    }
    
    public function setErrorReport($error_report=0){
        
        error_reporting($error_report);
    }

    //new library either submit cndriver as mysql, else will use pdo
    public function connect($db_host='',$db_user='',$db_pass='',$dbname='',$cndriver="mysql") {
        $this->db_host=$db_host;
        $this->db_user=$db_user;
        $this->db_pass=$db_pass;
        $this->dbname=$dbname;
        $this->cndriver=$cndriver;
      
        if($cndriver=="mysql" ||  $cndriver=="mysqli") 
        {

            if(!$this->con) {
                $this->myconn = @mysqli_connect($db_host,$db_user,$db_pass,$dbname);
                
                if($this->myconn) 
                {
                        return true;
                }
                else 
                {
                    return false;
                }
            } 
            else 
            {
                return true;
            }
            return true;
        }
        elseif($cndriver=="psql") {
            global $pgport;
            if($pgport=="" || $pgport==0)
                $pgport=5432;
            $conn_string = "host=$db_host port=$pgport dbname=$db_or_dsn_name user=$db_user password=$db_pass";
            $this->myconn = pg_connect($conn_string);
            if($this->myconn) {
                $this->con = true;
                return true;
            }else
                return false;
        }
        elseif($cndriver=="sqlsrv") {
 
             if(!$this->con) {
               $connectionInfo = array( "Database"=>$db_or_dsn_name, "UID"=>$db_user, "PWD"=>"$db_pass");
                 $this->myconn = @sqlsrv_connect($db_host,$connectionInfo);
                 if($this->myconn) {
                   $this->con = true;
                     return true;
                 } else {
                     return false;
                 }
             } else {
                 return true;
             }
             return true;
          }        
        else 
        {
            if(!$this->con) {
                try {
                    $this->myconn = new PDO ($cndriver.":host=$db_host;dbname=$dbname",$db_user,$db_pass);
                    } catch (PDOException $e) {
                    echo "Failed to get DB handle: " . $e->getMessage() . "\n";
                    exit;
                  }

                if( $this->myconn) {
                    $this->con = true;
                    return true;
                } else {
                    return false;
                }
            } else {
                return true;
            }
        }
    }

    public function disconnect($cndriver="mysql") {
        if($cndriver=="mysql" || $cndriver=="mysqli") {
            if($this->con) {
                if(@mysqli_close($this->myconn)) {
                    $this->con = false;
                    return true;
                }
                else {
                    return false;
                }
            }
        }
        elseif($cndriver=="sqlsrv") {
             $this->con = false;
            sqlsrv_close( $this->myconn );
          }               
        else {
            unset($this->myconn);
            $this->con = false;
            return true;            
        }
    }

    public function load_xml_string($jrxml=''){
        $keyword="<queryString>
        <![CDATA[";
        $jrxml = str_replace($keyword, "<queryString><![CDATA[", $jrxml);

        //Replace group element string
        $elementGroupH = '<elementGroup>';
        $elementGroupT = '</elementGroup>';
        $jrxml = str_replace($elementGroupH, '', $jrxml);
        $jrxml = str_replace($elementGroupT, '', $jrxml);      
        
        $xml = simplexml_load_string($jrxml);
        $this->xml_dismantle($xml);
    }
    
    public function load_xml_file($file=''){
            $xml=  file_get_contents($file);
            $this->load_xml_string($xml);            
    }
    
    public function xml_dismantle($xml='') {   
        $this->page_setting($xml);
        $i=0;
       // echo $i++."<br/>";
        foreach ($xml as $k=>$out) {
            //echo $i++."$k<br/>";
            switch($k) {
                case "parameter":  

                    $this->parameter_handler($out);
                    break;
                case "queryString":

                    $this->queryString_handler($out);
                    break;
                case "field":
                    $this->field_handler($out);
                    break;
                case "variable":
                    $this->variable_handler($out);
                    break;
                case "subDataset":
                       $this->subDataset_handler($out);
                    break;
                case "background":
                    $this->pointer=&$this->arraybackground;
                    $this->pointer[]=array("height"=>$out->band["height"],"splitType"=>$out->band["splitType"],"elementid"=>$this->elementid);
                    foreach ($out as $bg) {
                        $this->default_handler($bg);

                    }
                    break;
                default:
                    
                    foreach ($out as $b=>$object) {

                      //  eval("\$this->pointer=&"."\$this->array$k".";");
                        $this->arrayband[]=array("name"=>$k);
                        
                        if($k=='detail'){
                        
                        $this->pointer=&$this->arraydetail[$this->detailbandqty];
                        $this->detailbandheight[$this->detailbandqty]=$object["height"]+0;
                        $this->detailbandqty++;
                        }
                        elseif($k=='pageHeader'){
                                $this->pointer=&$this->arraypageHeader;
                                $this->headerbandheight=$object["height"]+0;
                        }
                         elseif($k=='title'){
                              $this->pointer=&$this->arraytitle;
                        $this->titlebandheight=$object["height"]+0;
                        $this->orititlebandheight=$object["height"]+0;
                         }
                        elseif($k=='pageFooter'){
                             $this->pointer=&$this->arraypageFooter;
                        $this->footerbandheight=$object["height"]+0;
                        }
                        elseif($k=='lastPageFooter'){
                             $this->pointer=&$this->arraylastPageFooter;
                        $this->lastfooterbandheight=$object["height"]+0;
                        }
                        elseif($k=='columnHeader'){
                             $this->pointer=&$this->arraycolumnHeader;
                            $this->columnheaderbandheight=$object["height"]+0;
                        }
                        elseif($k=='columnFooter'){
                             $this->pointer=&$this->arraycolumnFooter;
                            $this->columnfooterbandheight=$object["height"]+0;
                        }
                        elseif($k=='summary'){
                             $this->pointer=&$this->arraysummary;
                        $this->summarybandheight=$object["height"]+0;
                        }
                        elseif($k=='noData'){

                          $this->pointer=&$this->arraynoData;
                        $this->nodatabandheight=$object["height"]+0;   
                        }
                        elseif($k=="group"){
                            $this->group_handler($out);                                     
                        }
                        
                        $this->pointer[]=array("type"=>"band","printWhenExpression"=>$out->band->printWhenExpression."","height"=>$object["height"],"splitType"=>$object["splitType"],"y_axis"=>$this->y_axis,"elementid"=>$this->elementid);                        
                        $this->default_handler($object);
                    }
                    
                    $this->y_axis=$this->y_axis+$out->band["height"];   //after handle , then adjust y axis
                        $this->detailallowtill=$this->arrayPageSetting["pageHeight"]-$this->footerbandheight-$this->arrayPageSetting["bottomMargin"]-$this->columnfooterbandheight;

                          
                    break;

            }
                                  


        }

    }

    public function subDataset_handler($data=[]){
    $this->subdataset[$data['name'].'']= $data->queryString;
    }


    protected function aggArray($arr=[],$aggtype='sum')
    {
      $total=0;
      foreach($arr as $i=>$no)
      {
        switch($aggtype)
        {
          case 'sum':
            $total+=$no;
          break;  
        }
        
      }
      return $total;
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

    public function parameter_handler($xml_path=[]) {
        //    $defaultValueExpression=str_replace('"','',$xml_path->defaultValueExpression);
      // if($defaultValueExpression!='')
      //  $this->arrayParameter[$xml_path["name"].'']=$defaultValueExpression;
      // else
        //echo $xml_path["name"].'';
        //echo $xml_path["name"].'='.$this->arrayParameter[$xml_path["name"]];
        //print_r($this->arrayParameter);echo "<br/>"."<br/>";
        $this->arrayParameter[$xml_path["name"].''];//=$_REQUEST[$xml_path["name"].''];        
       if(isset($_REQUEST[$xml_path["name"].'']) &&  $this->autofetchpara==true){
            if($this->arrayParameter[$xml_path["name"].'']=="")
               $this->arrayParameter[$xml_path["name"].'']     =$_REQUEST[$xml_path["name"].''];                  
       }

    }

    public function queryString_handler($xml_path=[]) {
        $this->sql =$xml_path;

        if(isset($this->arrayParameter)) {   
            foreach($this->arrayParameter as  $v => $a) {              
                $this->sql = str_replace('$P{'.$v.'}', $a, $this->sql);
            }
        }
    }

    public function field_handler($xml_path=[]) {
        $this->arrayfield[]=$xml_path["name"];
        $this->arrayfieldtype["$xml_path[name]"] = "$xml_path[class]";
        // echo "<pre>".var_export($this->arrayfieldtype,true)."</pre>";
    }

    public function variable_handler($xml_path=[]) {

        $this->arrayVariable["$xml_path[name]"]=array("calculation"=>$xml_path["calculation"]."",
            "target"=>$xml_path->variableExpression ,
            "class"=>$xml_path["class"] ."",
            "resetType"=>$xml_path["resetType"]."",
            "resetGroup"=>$xml_path["resetGroup"].""
            );

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

    public function showPlusSymbol()
    {
        return '+';
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



  public function default_handler($xml_path=[]) {
  
        foreach($xml_path as $k=>$out) {
        $this->elementid++;
            switch($k) {
                case "staticText":
                    $this->element_staticText($out);
                    break;
                case "image":
                    $this->element_image($out);
                    break;
                case "line":
                    $this->element_line($out);
                    break;
                case "frame":
                    $this->element_frame($out);
                    // echo "this";
                    break;
                case "rectangle":
                    $this->element_rectangle($out);
                    break;
            case "ellipse":
                    $this->element_ellipse($out);
                    break;
                    case "textField":
                            
                    $this->element_textField($out);
                    break;
//                case "stackedBarChart":
//                    $this->element_barChart($out,'StackedBarChart');
//                    break;
//                case "barChart":
//                    $this->element_barChart($out,'BarChart');
//                    break;
           //     case "pieChart":
             //       $this->element_pieChart($out);
//                    break;
//                case "pie3DChart":
//                    $this->element_pie3DChart($out);
//                    break;
//                case "lineChart":
//                    $this->element_lineChart($out);
//                    break;
//                case "stackedAreaChart":
//                    $this->element_areaChart($out,'stackedAreaChart');
//                    break;
                    case "stackedBarChart":
                    $this->element_Chart($out,'stackedBarChart');
                    break;
                case "barChart":
                    $this->element_Chart($out,'barChart');
                    break;
                case "pieChart":
                    $this->element_Chart($out,'pieChart');
                    break;
                case "pie3DChart":
                    $this->element_Chart($out,'pie3DChart');
                    break;
                case "lineChart":
                    $this->element_Chart($out,'lineChart');
                    break;
                case "stackedAreaChart":
                    $this->element_Chart($out,'stackedAreaChart');
                    break;
                case "subreport":
                    $this->element_subReport($out);
                    break;
                case "break":
                    $this->element_break($out);
                    break;
                case "componentElement":
                    $this->element_componentElement($out);
                    break;
                case "crosstab":
                    $this->element_crossTab($out);
                default:
                    
                    break;
            }
        };      
    }

    
    public function recommendFont($utfstring='',$defaultfont='',$pdffont=""){
        
        /*\p{Common}
\p{Arabic}
\p{Armenian}
\p{Bengali}
\p{Bopomofo}
\p{Braille}
\p{Buhid}
\p{CanadianAboriginal}
\p{Cherokee}
\p{Cyrillic}
\p{Devanagari}
\p{Ethiopic}
\p{Georgian}
\p{Greek}
\p{Gujarati}
\p{Gurmukhi}
\p{Han}
\p{Hangul}
\p{Hanunoo}
\p{Hebrew}
\p{Hiragana}
\p{Inherited}
\p{Kannada}
\p{Katakana}
\p{Khmer}
\p{Lao}
\p{Latin}
\p{Limbu}
\p{Malayalam}
\p{Mongolian}
\p{Myanmar}
\p{Ogham}
\p{Oriya}
\p{Runic}
\p{Sinhala}
\p{Syriac}
\p{Tagalog}
\p{Tagbanwa}
\p{TaiLe}
\p{Tamil}
\p{Telugu}
\p{Thaana}
\p{Thai}
\p{Tibetan}
\p{Yi}*/

        if($pdffont!="")
            return $pdffont;
        if(preg_match("/\p{Han}+/u", $utfstring))
                $font="cid0cs";
          elseif(preg_match("/\p{Katakana}+/u", $utfstring) || preg_match("/\p{Hiragana}+/u", $utfstring))
                  $font="cid0jp";
          elseif(preg_match("/\p{Hangul}+/u", $utfstring))
              $font="cid0kr";
          else
              $font=$defaultfont;
          //echo "$utfstring $font".mb_detect_encoding($utfstring)."<br/>";
          
              return $font;//mb_detect_encoding($utfstring);
    }
    
    public function element_staticText($data) {
        $align="L";
        $fill=0;
        $border=0;
        $fontsize=10;
        $font="helvetica";
        $fontstyle="";
        $textcolor = array("r"=>0,"g"=>0,"b"=>0);
        $fillcolor = array("r"=>255,"g"=>255,"b"=>255);
        $txt="";
        $rotation="";
        $drawcolor=array("r"=>0,"g"=>0,"b"=>0);
        $height=$data->reportElement["height"];
        $stretchoverflow="true";
        $printoverflow="false";
        $data->hyperlinkReferenceExpression=$this->analyse_expression($data->hyperlinkReferenceExpression);
        $data->hyperlinkReferenceExpression=trim(str_replace(array(" ",'"'),"",$data->hyperlinkReferenceExpression));
        if(isset($data->reportElement["forecolor"])) {
            
            $textcolor = array('forecolor'=>$data->reportElement["forecolor"],"r"=>hexdec(substr($data->reportElement["forecolor"],1,2)),"g"=>hexdec(substr($data->reportElement["forecolor"],3,2)),"b"=>hexdec(substr($data->reportElement["forecolor"],5,2)));
        }
        if(isset($data->reportElement["backcolor"])) {
            $fillcolor = array('backcolor'=>$data->reportElement["backcolor"],"r"=>hexdec(substr($data->reportElement["backcolor"],1,2)),"g"=>hexdec(substr($data->reportElement["backcolor"],3,2)),"b"=>hexdec(substr($data->reportElement["backcolor"],5,2)));
        }
        if($data->reportElement["mode"]=="Opaque") {
            $fill=1;
        }
        if(isset($data["isStretchWithOverflow"])&&$data["isStretchWithOverflow"]=="true" || isset($data["textAdjust"]) && $data["textAdjust"] == "StretchHeight") {
            $stretchoverflow="true";
        }
        if(isset($data->reportElement["isPrintWhenDetailOverflows"])&&$data->reportElement["isPrintWhenDetailOverflows"]=="true") {
            $printoverflow="true";
            $stretchoverflow="false";
        }
        if(isset($data->box)) {
             $topattr=$data->box->topPen->attributes();
              $bottomattr=$data->box->bottomPen->attributes();
              $leftattr=$data->box->leftPen->attributes();
              $rightattr=$data->box->rightPen->attributes();

              // echo "<br/><br/>";
               
          $borderstyles=[];
          $arrborder=[];

        if($topattr["lineWidth"]>0)
        {
            $borderstyles['T']=['width'=>$topattr["lineWidth"],'style'=>(string)$topattr["lineStyle"], 'hexcolor'=>(string)$topattr["lineColor"]];
            array_push($arrborder, "T");
        }
        if($bottomattr["lineWidth"]>0)
        {
            $borderstyles['B']=['width'=>$bottomattr["lineWidth"],'style'=>(string)$bottomattr["lineStyle"],'hexcolor'=>(string)$bottomattr["lineColor"]];
            array_push($arrborder, "B");
        }
        if($leftattr["lineWidth"]>0)
        {
            $borderstyles['L']=['width'=>$leftattr["lineWidth"],'style'=>(string)$leftattr["lineStyle"],'hexcolor'=>(string)$leftattr["lineColor"]];
            array_push($arrborder, "L");
        }
        if($rightattr["lineWidth"]>0)
        {
            $borderstyles['R']=['width'=>$rightattr["lineWidth"],'style'=>(string)$rightattr["lineStyle"], 'hexcolor'=>(string)$rightattr["lineColor"]];
            array_push($arrborder, "R");
        }
        
                //     print_r($borderstyles);
                // echo "<br/><br/>";
                foreach($borderstyles as $key=>$borderstylearr)
                {
                    if($borderstylearr['style'] && $borderstylearr['style']=='Dotted')
                    {
                        $borderstyles[$key]['dash']='0,1';
                    }
                    else if($borderstylearr['style'] && $borderstylearr['style']=='Dashed')
                    {
                        $borderstyles[$key]['dash']='4,2';
                    }
                    else
                    {
                        $borderstyles[$key]['dash']='';   
                    }
                    $hexcolor=$borderstyles[$key]['hexcolor'];
                    $borderstyles[$key]['color']=[
                            hexdec(substr($hexcolor, 1,2)),
                            hexdec(substr($hexcolor, 3,2)),
                            hexdec(substr($hexcolor, 5,2))
                        ];
                    
                }
           
                $border=[];
                foreach($arrborder as $borderno =>$bordername)
                {
                    $border[$bordername]= ['width' => $borderstyles[$bordername]["width"],'cap' => 'butt', 'join' => 'miter', 
                                        'dash' =>$borderstyles[$bordername]['dash'],'phase'=>0,'color' =>$borderstyles[$bordername]['color']];
                }
            
    
        }
        if(isset($data->textElement["textAlignment"])) {
            $align=$this->get_first_value($data->textElement["textAlignment"]);
        }
        if(isset($data->textElement["verticalAlignment"])) {
                        $valign="T";
            if($data->textElement["verticalAlignment"]=="Bottom")
                $valign="B";
            elseif($data->textElement["verticalAlignment"]=="Middle")
                $valign="C";
            else
                $valign="T";

        }
        if(isset($data->textElement["rotation"])) {
            $rotation=$data->textElement["rotation"];
        }
        if(isset($data->textElement->font["fontName"])) {
          
          //else
            //$data->text=$data->textElement->font["pdfFontName"];//$this->recommendFont($data->text);
            $font=$this->recommendFont($data->text,$data->textElement->font["fontName"],$data->textElement->font["pdfFontName"]);
                
        }
        if(isset($data->textElement->font["size"])) {
            $fontsize=$data->textElement->font["size"];
        }
        if(isset($data->textElement->font["isBold"])&&$data->textElement->font["isBold"]=="true") {
            $fontstyle=$fontstyle."B";
        }
        if(isset($data->textElement->font["isItalic"])&&$data->textElement->font["isItalic"]=="true") {
            $fontstyle=$fontstyle."I";
        }
        if(isset($data->textElement->font["isUnderline"])&&$data->textElement->font["isUnderline"]=="true") {
            $fontstyle=$fontstyle."U";
        }
        if(isset($data->reportElement["key"])) {
            $height=$fontsize*$this->adjust;
        }
        $this->pointer[]=array("type"=>"SetXY","x"=>$data->reportElement["x"]+0,"y"=>$data->reportElement["y"]+0,"hidden_type"=>"SetXY","elementid"=>$this->elementid);
        $this->pointer[]=array("type"=>"SetTextColor",'forecolor'=>$data->reportElement["forecolor"].'',"r"=>$textcolor["r"],"g"=>$textcolor["g"],"b"=>$textcolor["b"],"hidden_type"=>"textcolor","elementid"=>$this->elementid);
        $this->pointer[]=array("type"=>"SetDrawColor","r"=>$drawcolor["r"],"g"=>$drawcolor["g"],"b"=>$drawcolor["b"],"hidden_type"=>"drawcolor","elementid"=>$this->elementid);
        $this->pointer[]=array("type"=>"SetFillColor",'backcolor'=>$data->reportElement["backcolor"].'',"r"=>$fillcolor["r"],"g"=>$fillcolor["g"],"b"=>$fillcolor["b"],"hidden_type"=>"fillcolor","elementid"=>$this->elementid);
        $this->pointer[]=array("type"=>"SetFont","font"=>$font,"pdfFontName"=>$data->textElement->font["pdfFontName"],"fontstyle"=>$fontstyle,"fontsize"=>$fontsize,"hidden_type"=>"font","elementid"=>$this->elementid);
        //"height"=>$data->reportElement["height"]
        
//### UTF-8 characters, a must for me.  
        $txtEnc=$data->text; 
                
        $this->pointer[]=array("type"=>"MultiCell","width"=>$data->reportElement["width"],"height"=>$height,"uuid"=>$data->reportElement['uuid'],
                    "txt"=>$txtEnc,"border"=>$border,"align"=>$align,"fill"=>$fill,"hidden_type"=>"statictext",
                    "soverflow"=>$stretchoverflow,"poverflow"=>$printoverflow,"rotation"=>$rotation,"valign"=>$valign,
                    "x"=>$data->reportElement["x"]+0,"y"=>$data->reportElement["y"]+0,"elementid"=>$this->elementid);
//### End of modification, below is the original line       
//        $this->pointer[]=array("type"=>"MultiCell","width"=>$data->reportElement["width"],"height"=>$height,"txt"=>$data->text,"border"=>$border,"align"=>$align,"fill"=>$fill,"hidden_type"=>"statictext","soverflow"=>$stretchoverflow,"poverflow"=>$printoverflow,"rotation"=>$rotation);

    }

    public function element_image($data) {
        $imagepath=$data->imageExpression;
        //$imagepath= substr($data->imageExpression, 1, -1);
        //$imagetype= substr($imagepath,-3);
//$data->hyperlinkReferenceExpression=$this->analyse_expression($data->hyperlinkReferenceExpression);
//$data->hyperlinkReferenceExpression=trim(str_replace(array(" ",'"'),"",$data->hyperlinkReferenceExpression));
         switch($data[scaleImage]) {
            case "FillFrame":
                $scaleType = "FillFrame";
                break;
            case "RetainShape":
                $scaleType = "RetainShape";
                break;
            case "Clip":
                $scaleType = "Clip";
                break;
            case "RealHeight":
                $scaleType = "RealHeight";
                break;
            case "RealSize":
                $scaleType = "RealSize";
                break;
            default:
                $scaleType = ""; 
                break;
        }
        $this->pointer[]=array("type"=>"Image","path"=>$imagepath,"x"=>$data->reportElement["x"]+0,"y"=>$data->reportElement["y"]+0,"width"=>$data->reportElement["width"]+0, "height"=>$data->reportElement["height"]+0,"imgtype"=>$imagetype,"link"=>$data->hyperlinkReferenceExpression,"uuid"=>$data->reportElement['uuid'], "hidden_type"=>"image","linktarget"=>$data["hyperlinkTarget"]."","elementid"=>$this->elementid, "scale_type" => $scaleType);

    }
    
    public function element_componentElement($data) {
//        $imagepath=$data->imageExpression;
//        //$imagepath= substr($data->imageExpression, 1, -1);
//        //$imagetype= substr($imagepath,-3);
//        $data->hyperlinkReferenceExpression=" ".$this->analyse_expression($data->hyperlinkReferenceExpression);

        $x=$data->reportElement["x"];
        $y=$data->reportElement["y"];
        $width=$data->reportElement["width"];
        $height=$data->reportElement["height"];
        
               //simplexml_tree( $data);
       // echo "<br/><br/>";
       //simplexml_tree( $data->children('jr',true));
        //echo "<br/><br/>";
//SimpleXML object (1 item) [0] // ->codeExpression[0] ->attributes('xsi', true) ->schemaLocation ->attributes('', true) ->type ->drawText ->checksumRequired barbecue: 
       foreach($data->children('jr',true) as $barcodetype =>$content){
           
           
           $barcodemethod="";
           $textposition="";
            if($barcodetype=="barbecue"){
                $barcodemethod=$data->children('jr',true)->attributes('', true) ->type;
                $textposition="";
                $checksum=$data->children('jr',true)->attributes('', true) ->checksumRequired;
                $code=$content->codeExpression;
                if($content->attributes('', true) ->drawText=='true')
                        $textposition="bottom";
                
                $modulewidth=$content->attributes('', true) ->moduleWidth;
                
            }else{
                
                 $barcodemethod=$barcodetype;
                 $textposition=$content->attributes('', true)->textPosition;
                 //$data->children('jr',true)->textPosition;
//$content['textPosition'];
                  $code=$content->codeExpression;
                $modulewidth=$content->attributes('', true)->moduleWidth;
                 

                
            }
            if($modulewidth=="")
                $modulewidth=0.4;

//                            echo "Barcode: $code,position: $textposition <br/><br/>";
            $this->pointer[]=array("type"=>"Barcode","barcodetype"=>$barcodemethod,"x"=>$x,"y"=>$y,"width"=>$width,"height"=>$height,'textposition'=>$textposition,'code'=>$code,'modulewidth'=>$modulewidth,"elementid"=>$this->elementid,"uuid"=>$data->reportElement['uuid'],);
                            
                    /*
                        <jr:barbecue xmlns:jr="http://jasperreports.sourceforge.net/jasperreports/components" 
                     * xsi:schemaLocation="http://jasperreports.sourceforge.net/jasperreports/components http://jasperreports.sourceforge.net/xsd/components.xsd" 
                     * type="2of7" drawText="false" checksumRequired="false">
                    <jr:codeExpression><![CDATA["1234"]]></jr:codeExpression>
                </jr:barbecue>
                     * <jr:Code128 xmlns:jr="http://jasperreports.sourceforge.net/jasperreports/components" 
                     * xsi:schemaLocation="http://jasperreports.sourceforge.net/jasperreports/components http://jasperreports.sourceforge.net/xsd/components.xsd"
                     *  textPosition="bottom">
                    <jr:codeExpression><![CDATA[]]></jr:codeExpression>
                </jr:Code128>
                     */

           
       }
       
       
        //if(isset(  $data->children('jr',true)->barbecue)){
           
       //}
       //elseif(isset(  $data->children('jr',true)->barbecue))
      // print_r( $data->children('jr',true));
      // type="2of7" drawText="false" checksumRequired="false"
       /*
        *               
        * <jr:barbecue xmlns:jr="http://jasperreports.sourceforge.net/jasperreports/components" xsi:schemaLocation="http://jasperreports.sourceforge.net/jasperreports/components http://jasperreports.sourceforge.net/xsd/components.xsd" type="2of7" drawText="false" checksumRequired="false">
                    <jr:codeExpression><![CDATA["1234"]]></jr:codeExpression>
        </jr:barbecue>

        */
        //die;                
//        switch($data[scaleImage]) {
//            case "FillFrame":
//                $this->pointer[]=array("type"=>"Image","path"=>$imagepath,"x"=>$data->reportElement["x"]+0,"y"=>$data->reportElement["y"]+0,"width"=>$data->reportElement["width"]+0,"height"=>$data->reportElement["height"]+0,"imgtype"=>$imagetype,"link"=>substr($data->hyperlinkReferenceExpression,1,-1),"hidden_type"=>"image");
//                break;
//            default:
//                $this->pointer[]=array("type"=>"Image","path"=>$imagepath,"x"=>$data->reportElement["x"]+0,"y"=>$data->reportElement["y"]+0,"width"=>$data->reportElement["width"]+0,"height"=>$data->reportElement["height"]+0,"imgtype"=>$imagetype,"link"=>substr($data->hyperlinkReferenceExpression,1,-1),"hidden_type"=>"image");
//                break;
//        }
    }

    
    public function element_break($data) {
                $this->pointer[]=array("type"=>"break","hidden_type"=>"break","elementid"=>$this->elementid);//,"path"=>$imagepath,"x"=>$data->reportElement["x"]+0,"y"=>$data->reportElement["y"]+0,"width"=>$data->reportElement["width"]+0,"height"=>$data->reportElement["height"]+0,"imgtype"=>$imagetype,"link"=>substr($data->hyperlinkReferenceExpression,1,-1),"hidden_type"=>"image");
    }

    public function element_crossTab($data){
        //var_dump($data);die;
        $x=$data->reportElement['x']+0;
        $y=$data->reportElement['w']+0;
        $ctwidth=$data->reportElement['width']+0;
        $height=$data->reportElement['height']+0;
        $dataset=$data->crosstabDataset->dataset->datasetRun['subDataset']."";
        
        $rowgroup=array();
        
        /*
         *  <crosstab>
                <reportElement uuid="6a55f366-b4f8-41a1-b89b-3c826c9e282f" x="0" y="0" width="555" height="60"/>
                                * <crosstabDataset>
                    <dataset>
                        <datasetRun subDataset="ds2" uuid="7e3eef20-67ea-4d56-b5bc-15df22a79303">
                            <connectionExpression><![CDATA[$P{REPORT_CONNECTION}]]></connectionExpression>
                        </datasetRun>
                    </dataset>
                </crosstabDataset>
                <rowGroup name="itemtype_name" width="70" totalPosition="End">
                    <bucket class="java.lang.String">
                        <bucketExpression><![CDATA[$F{itemtype_name}]]></bucketExpression>
                    </bucket>
                    <crosstabRowHeader>
                        <cellContents backcolor="#F0F8FF" mode="Opaque">
                            <box>
                                <pen lineWidth="0.5" lineStyle="Solid" lineColor="#000000"/>
                            </box>
                            <textField>
                                <reportElement uuid="049d6b20-f72a-467e-9fa3-9dffb8c6cb74" style="Crosstab Data Text" x="0" y="0" width="70" height="25"/>
                                <textElement/>
                                <textFieldExpression><![CDATA[$V{itemtype_name}]]></textFieldExpression>
                            </textField>
                        </cellContents>
                    </crosstabRowHeader>
                    <crosstabTotalRowHeader>
                        <cellContents backcolor="#BFE1FF" mode="Opaque">
                            <box>
                                <pen lineWidth="0.5" lineStyle="Solid" lineColor="#000000"/>
                            </box>
                            <staticText>
                                <reportElement uuid="4590d088-819b-4a6b-a073-8a1e7a75dbd3" x="0" y="0" width="70" height="25"/>
                                <textElement textAlignment="Center" verticalAlignment="Middle"/>
                                <text><![CDATA[Total itemtype_name]]></text>
                            </staticText>
                        </cellContents>
                    </crosstabTotalRowHeader>
                </rowGroup>
                <columnGroup name="category_name" height="30" totalPosition="End">
                    <bucket class="java.lang.String">
                        <bucketExpression><![CDATA[$F{category_name}]]></bucketExpression>
                    </bucket>
                    <crosstabColumnHeader>
                        <cellContents backcolor="#F0F8FF" mode="Opaque">
                            <box>
                                <pen lineWidth="0.5" lineStyle="Solid" lineColor="#000000"/>
                            </box>
                            <textField>
                                <reportElement uuid="9bb33545-6749-494c-91c6-8b258d697187" style="Crosstab Data Text" x="0" y="0" width="92" height="30"/>
                                <textElement/>
                                <textFieldExpression><![CDATA[$V{category_name}]]></textFieldExpression>
                            </textField>
                        </cellContents>
                    </crosstabColumnHeader>
                    <crosstabTotalColumnHeader>
                        <cellContents backcolor="#BFE1FF" mode="Opaque">
                            <box>
                                <pen lineWidth="0.5" lineStyle="Solid" lineColor="#000000"/>
                            </box>
                            <staticText>
                                <reportElement uuid="04da0212-3020-4200-8996-885b38a7a7a3" x="0" y="0" width="50" height="30"/>
                                <textElement textAlignment="Center" verticalAlignment="Middle"/>
                                <text><![CDATA[Total category_name]]></text>
                            </staticText>
                        </cellContents>
                    </crosstabTotalColumnHeader>
                </columnGroup>
                <measure name="item_idMeasure" class="java.lang.Integer" calculation="Count">
                    <measureExpression><![CDATA[$F{item_id}]]></measureExpression>
                </measure>

                <crosstabCell width="93" height="25">
                    <cellContents>
                        <box>
                            <pen lineWidth="0.5" lineStyle="Solid" lineColor="#000000"/>
                        </box>
                        <textField>
                            <reportElement uuid="04c4b685-118a-4f2b-a458-c1da42e3d78e" style="Crosstab Data Text" x="0" y="0" width="92" height="25"/>
                            <textElement/>
                            <textFieldExpression><![CDATA[$V{item_idMeasure}]]></textFieldExpression>
                        </textField>
                    </cellContents>
                </crosstabCell>
                <crosstabCell width="93" height="25" rowTotalGroup="itemtype_name">
                    <cellContents backcolor="#BFE1FF" mode="Opaque">
                        <box>
                            <pen lineWidth="0.5" lineStyle="Solid" lineColor="#000000"/>
                        </box>
                        <textField>
                            <reportElement uuid="74e517a1-2f81-4f74-8c7c-5991b0d9a7f6" style="Crosstab Data Text" x="0" y="0" width="92" height="25"/>
                            <textElement/>
                            <textFieldExpression><![CDATA[$V{item_idMeasure}]]></textFieldExpression>
                        </textField>
                    </cellContents>
                </crosstabCell>
                <crosstabCell width="50" columnTotalGroup="category_name">
                    <cellContents backcolor="#BFE1FF" mode="Opaque">
                        <box>
                            <pen lineWidth="0.5" lineStyle="Solid" lineColor="#000000"/>
                        </box>
                        <textField>
                            <reportElement uuid="cb2c3525-76bc-4309-afa2-7c6e855eec56" style="Crosstab Data Text" x="0" y="0" width="50" height="25"/>
                            <textElement/>
                            <textFieldExpression><![CDATA[$V{item_idMeasure}]]></textFieldExpression>
                        </textField>
                    </cellContents>
                </crosstabCell>
                <crosstabCell rowTotalGroup="itemtype_name" columnTotalGroup="category_name">
                    <cellContents backcolor="#BFE1FF" mode="Opaque">
                        <box>
                            <pen lineWidth="0.5" lineStyle="Solid" lineColor="#000000"/>
                        </box>
                        <textField>
                            <reportElement uuid="f23635a8-257c-4d64-a116-8fcc3a9b1e2a" style="Crosstab Data Text" x="0" y="0" width="50" height="25"/>
                            <textElement/>
                            <textFieldExpression><![CDATA[$V{item_idMeasure}]]></textFieldExpression>
                        </textField>
                    </cellContents>
                </crosstabCell>
            </crosstab>
         */
        
        foreach($data->rowGroup as $r =>$rd){
       
             /* $bucketexpression=$d->bucket->bucketExpression;
            
            $rowheadertxtheight=$d->crosstabRowHeader->cellContents->textField->reportElement[''];
            $rowheadertxtwidth=$d->crosstabRowHeader->cellContents->textField->reportElement[''];
            $d->crosstabRowHeader->cellContents->textField->textFieldExpression;
            
             * 
             */
          //   echo "--".print_r($rd,true)."--<br/>";
            
            //textAlignment="Center" verticalAlignment="Middle"
            $rowheaderalign=$rd->crosstabRowHeader->cellContents->textField->textElement['textAlignment']."";
            if($rowheaderalign=="")
                    $rowheaderalign="center";
            
            $rowheadervalign=$rd->crosstabRowHeader->cellContents->textField->textElement['verticalAlignment']."";
           
            if($rd->crosstabRowHeader->cellContents['mode'].""=="Opaque")
            $rowheaderbgcolor=$rd->crosstabRowHeader->cellContents['backcolor']."";
             $rowheaderisbold=$rd->cellContents->textField->textElement->font['isBold']."";
            $rowexpression=$rd->bucket->bucketExpression;
            $rowgroupfield=$rd->crosstabRowHeader->cellContents->textField->textFieldExpression;
            $style=array("width"=>$rd["width"]+0,
                    'rowheaderbgcolor'=>$rowheaderbgcolor,"rowheaderalign"=>$rowheaderalign,"rowheadervalign"=>$rowheadervalign,
                    "rowheaderisbold"=>$rowheaderisbold);
            $rowgroup[]=array("name"=>$rd['name']."","field"=>$rowgroupfield."","style"=>$style);
            
        }
        /*
        <columnGroup name="category_name" height="30" totalPosition="End">
                    <bucket class="java.lang.String">
                        <bucketExpression><![CDATA[$F{category_name}]]></bucketExpression>
                    </bucket>
                    <crosstabColumnHeader>
                        <cellContents backcolor="#F0F8FF" mode="Opaque">
                            <box>
                                <pen lineWidth="0.5" lineStyle="Solid" lineColor="#000000"/>
                            </box>
                            <textField>
                                <reportElement uuid="9bb33545-6749-494c-91c6-8b258d697187" style="Crosstab Data Text" x="0" y="0" width="92" height="30"/>
                                <textElement/>
                                <textFieldExpression><![CDATA[$V{category_name}]]></textFieldExpression>
                            </textField>
                        </cellContents>
                    </crosstabColumnHeader>
                    <crosstabTotalColumnHeader>
                        <cellContents backcolor="#BFE1FF" mode="Opaque">
                            <box>
                                <pen lineWidth="0.5" lineStyle="Solid" lineColor="#000000"/>
                            </box>
                            <staticText>
                                <reportElement uuid="04da0212-3020-4200-8996-885b38a7a7a3" x="0" y="0" width="50" height="30"/>
                                <textElement textAlignment="Center" verticalAlignment="Middle"/>
                                <text><![CDATA[Total category_name]]></text>
                            </staticText>
                        </cellContents>
                    </crosstabTotalColumnHeader>
                </columnGroup>
        */
        foreach($data->columnGroup as $c =>$cd){
            $colheaderalign=$cd->crosstabColumnHeader->cellContents->textField->textElement['textAlignment']."";
            if($colheaderalign=="")$colheaderalign="center";
            $colheadervalign=$cd->crosstabColumnHeader->cellContents->textField->textElement['verticalAlignment']."";
            if($cd->crosstabColumnHeader->cellContents['mode'].""=="Opaque")
            $colheaderbgcolor=$cd->crosstabColumnHeader->cellContents['backcolor']."";
             $colheaderisbold=$cd->crosstabColumnHeader->cellContents->textField->textElement->font['isBold']."";
             $height=$cd['height']+0;
             
          $colgroupfield=$cd->crosstabColumnHeader->cellContents->textField->textFieldExpression;
          $style=array("colheaderalign"=>$colheaderalign,"colheadervalign"=>$colheadervalign,"colheaderbgcolor"=>$colheaderbgcolor,
                "colheaderisbold"=>$colheaderisbold,"height"=>$height);
        //   $colexpression=$d->bucket->bucketExpression;
          //  $colgroup[]=array("name"=>$c['name'],"width"=>$c['width'],"totalPosition"=>$r['totalPosition']);
            $colgroup[]=array("name"=>$cd['name']."","field"=>$colgroupfield."","style"=>$style);
            
        }
        $measuremethod=$data->measure['calculation']."";
        $measurefield=$data->measure->measureExpression."";
        
        
        /*<crosstabCell width="50" columnTotalGroup="category_name">
                    <cellContents backcolor="#BFE1FF" mode="Opaque">
                        <box>
                            <pen lineWidth="0.5" lineStyle="Solid" lineColor="#000000"/>
                        </box>
                        <textField>
                            <reportElement uuid="cb2c3525-76bc-4309-afa2-7c6e855eec56" style="Crosstab Data Text" x="0" y="0" width="50" height="25"/>
                            <textElement/>
                            <textFieldExpression><![CDATA[$V{item_idMeasure}]]></textFieldExpression>
                        </textField>
                    </cellContents>
                </crosstabCell>*/
        $crosstabcell=array();
        $i=0;
         foreach($data->crosstabCell as $ce =>$cecontent){
            // print_r($cecontent);echo "<br/>";
            $ceheaderalign=$cecontent->cellContents->textField->textElement['textAlignment']."";
            $ceheadervalign=$cecontent->cellContents->textField->textElement['verticalAlignment']."";
            if($cecontent->cellContents['mode'].""=="Opaque")
             $ceheaderbgcolor=$cecontent->cellContents['backcolor']."";
             $ceheaderisbold=$cecontent->cellContents->textField->textElement->font['isBold']."";
             $width=$cecontent['width']+0;
             $style=array("ceheaderalign"=>$ceheaderalign,"ceheadervalign"=>$ceheadervalign,"ceheaderbgcolor"=>$ceheaderbgcolor,
                 "ceheaderisbold"=>$ceheaderisbold,"width"=>$width);
        //   $colexpression=$d->bucket->bucketExpression;
          //  $colgroup[]=array("name"=>$c['name'],"width"=>$c['width'],"totalPosition"=>$r['totalPosition']);
            $crosstabcell[]=array("no"=>$i,"style"=>$style);
            $i++;
            
        }
        
           $this->pointer[]=array("type"=>"CrossTab","x"=>$x,"y"=>$y,"width"=>$ctwidth,"height"=>$height,"dataset"=>$dataset,
               'rowgroup'=>$rowgroup,'colgroup'=>$colgroup,'measuremethod'=>$measuremethod,'measurefield'=>$measurefield,'crosstabcell'=>$crosstabcell,"elementid"=>$this->elementid);


        
    }
    
    public function element_line($data) {   //default line width=0.567(no detect line width)
        $drawcolor=array("r"=>0,"g"=>0,"b"=>0);
        $hidden_type="line";
         if($data->graphicElement->pen["lineWidth"]>0)
            $linewidth=$data->graphicElement->pen["lineWidth"];
         if($linewidth=="")
            $linewidth=0.5;
     
        /*
           $borderset="";
            if($data->box->topPen["lineWidth"]>0)
                $borderset.="T";
            if($data->box->leftPen["lineWidth"]>0)
                $borderset.="L";
            if($data->box->bottomPen["lineWidth"]>0)
                $borderset.="B";
            if($data->box->rightPen["lineWidth"]>0)
                $borderset.="R";
             if(isset($data->box->pen["lineColor"])) {
                $drawcolor=array("r"=>hexdec(substr($data->box->pen["lineColor"],1,2)),"g"=>hexdec(substr($data->box->pen["lineColor"],3,2)),"b"=>hexdec(substr($data->box->pen["lineColor"],5,2)));
            }
              */
            if(isset($data->graphicElement->pen["lineStyle"])) {
                if($data->graphicElement->pen["lineStyle"]=="Dotted")
                    $dash="0,1";
                elseif($data->graphicElement->pen["lineStyle"]=="Dashed")
                    $dash="4,2"; 
                else
                    $dash="";
                //Dotted Dashed
            }
           
            
          
        if(isset($data->reportElement["forecolor"])) {
            $drawcolor=array("r"=>hexdec(substr($data->reportElement["forecolor"],1,2)),"g"=>hexdec(substr($data->reportElement["forecolor"],3,2)),"b"=>hexdec(substr($data->reportElement["forecolor"],5,2)));
        }
//        $this->pointer[]=array("type"=>"SetDrawColor","r"=>$drawcolor["r"],"g"=>$drawcolor["g"],"b"=>$drawcolor["b"],"hidden_type"=>"drawcolor");
        if(isset($data->reportElement["positionType"])&&$data->reportElement["positionType"]=="FixRelativeToBottom") {
            $hidden_type="relativebottomline";
        }
        
        $style=array('color'=>$drawcolor,'width'=>$linewidth,'dash'=>$dash);
//        
        
        if($data->reportElement["width"][0]+0 > $data->reportElement["height"][0]+0)    //width > height means horizontal line
        {
            $this->pointer[]=array("type"=>"Line", "x1"=>$data->reportElement["x"]+0,"y1"=>$data->reportElement["y"]+0,
                "x2"=>$data->reportElement["x"]+$data->reportElement["width"],"y2"=>$data->reportElement["y"]+$data->reportElement["height"]-1,
                "hidden_type"=>$hidden_type,"style"=>$style,"forecolor"=>$data->reportElement["forecolor"]."","printWhenExpression"=>$data->reportElement->printWhenExpression,"elementid"=>$this->elementid);
        }
        elseif($data->reportElement["height"][0]+0>$data->reportElement["width"][0]+0)      //vertical line
        {
            $this->pointer[]=array("type"=>"Line", "x1"=>$data->reportElement["x"],"y1"=>$data->reportElement["y"],
                "x2"=>$data->reportElement["x"]+$data->reportElement["width"]-1,"y2"=>$data->reportElement["y"]+$data->reportElement["height"],"hidden_type"=>$hidden_type,"style"=>$style,
                "forecolor"=>$data->reportElement["forecolor"]."","printWhenExpression"=>$data->reportElement->printWhenExpression,"elementid"=>$this->elementid);
        }
        
        
        $this->pointer[]=array("type"=>"SetDrawColor","r"=>0,"g"=>0,"b"=>0,"hidden_type"=>"drawcolor","elementid"=>$this->elementid);
        $this->pointer[]=array("type"=>"SetFillColor","r"=>255,"g"=>255,"b"=>255,"hidden_type"=>"fillcolor","elementid"=>$this->elementid);
    }

    public function element_rectangle($data) {
        
                
        $radius=$data['radius']+0;
                $mode=$data->reportElement["mode"]."";
        $drawcolor=array("r"=>0,"g"=>0,"b"=>0);
       // if($data['mode']=='Opaque')
     //   $fillcolor=array("r"=>255,"g"=>255,"b"=>255);
        $borderwidth=1;
           
           if(isset($data->graphicElement->pen["lineWidth"]))
                 $borderwidth=$data->graphicElement->pen["lineWidth"];
            
             if(isset($data->graphicElement->pen["lineColor"]))
                 $drawcolor=array("r"=>hexdec(substr($data->graphicElement->pen["lineColor"],1,2)),"g"=>hexdec(substr($data->graphicElement->pen["lineColor"],3,2)),"b"=>hexdec(substr($data->graphicElement->pen["lineColor"],5,2)));
            
            $dash="";
                    if($data->graphicElement->pen["lineStyle"]=="Dotted")
                    $dash="0,1";
                elseif($data->graphicElement->pen["lineStyle"]=="Dashed")
                    $dash="4,2"; 
                elseif($data->graphicElement->pen["lineStyle"]=="Solid")
                    $dash="";
//echo "$borderwidth,";
            if(isset($data->reportElement["forecolor"])) {
            $drawcolor=array("r"=>hexdec(substr($data->reportElement["forecolor"],1,2)),"g"=>hexdec(substr($data->reportElement["forecolor"],3,2)),"b"=>hexdec(substr($data->reportElement["forecolor"],5,2)));         
        } 
            $border=array("LTRB" => array('width' => $borderwidth+0,'color' =>$drawcolor,'cap'=>'square',
                            'join'=>'miter','dash'=>$dash));
            
            
            //array($borderset=>array('width'=>$data->box->pen["lineWidth"],
                //(butt, round, square),'join'=>'miter' (miter, round,bevel),
                //'dash'=>2 ("2,1","2"),
              //  'colour'=>array(110,20,30)  ));
            //&&$data->box->pen["lineWidth"]>0
            //border can be array('LTRB' => array('width' => 2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0))
            
            
           
            //elseif()
            
        
      
        
        if(isset($data->reportElement["backcolor"])  && ($mode=='Opaque'|| $mode=='')) { 
           
            $fillcolor=array("r"=>hexdec(substr($data->reportElement["backcolor"],1,2)),"g"=>hexdec(substr($data->reportElement["backcolor"],3,2)),"b"=>hexdec(substr($data->reportElement["backcolor"],5,2)));         
        }
        else
               $fillcolor=array("r"=>255,"g"=>255,"b"=>255);

//print_r($border);
        //$this->pointer[]=array("type"=>"SetDrawColor","r"=>$drawcolor["r"],"g"=>$drawcolor["g"],"b"=>$drawcolor["b"],"hidden_type"=>"drawcolor");
       // $this->pointer[]=array("type"=>"SetFillColor","r"=>$fillcolor["r"],"g"=>$fillcolor["g"],"b"=>$fillcolor["b"],"hidden_type"=>"fillcolor");
        
//       if($radius=='')
//        $this->pointer[]=array("type"=>"Rect","x"=>$data->reportElement["x"]+0,"y"=>$data->reportElement["y"]+0,"width"=>$data->reportElement["width"]+0,
//                "height"=>$data->reportElement["height"]+0,"hidden_type"=>"rect",
//                "fillcolor"=>$fillcolor."","mode"=>$data->reportElement["mode"]."",'border'=>0);
//        else
             //                     echo "OK";print_r($border);die;
             
             //array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 4, 'color' => array(255, 0, 0))
//             print_r($border);die;
             //Array ( [LTRB] => Array ( [width] => 1 [color] => Array ( [r] => 51 [g] => 255 [b] => 102 ) [cap] => square [join] => miter [dash] => ) ) 
        $printWhenExpression= $data->reportElement->printWhenExpression."";
        $this->pointer[]=array("type"=>"RoundedRect","x"=>$data->reportElement["x"]+0,
                "y"=>$data->reportElement["y"]+0,"width"=>$data->reportElement["width"]+0,
            "height"=>$data->reportElement["height"]+0,"hidden_type"=>"roundedrect","radius"=>$radius,
                "fillcolor"=>$fillcolor,
                "mode"=>$mode,
                'border'=>array("LRTB"=>$border),
                "elementid"=>$this->elementid,"printWhenExpression"=>$printWhenExpression);
        
        
//        $this->pointer[]=array("type"=>"SetDrawColor","r"=>0,"g"=>0,"b"=>0,"hidden_type"=>"drawcolor");
  //      $this->pointer[]=array("type"=>"SetFillColor","r"=>255,"g"=>255,"b"=>255,"hidden_type"=>"fillcolor");
    }

  public function element_ellipse($data) {
        $drawcolor=array("r"=>0,"g"=>0,"b"=>0);
        $fillcolor=array("r"=>255,"g"=>255,"b"=>255);
         $width=1;
           
            
                
           if(isset($data->graphicElement->pen["lineWidth"]))
                 $borderwidth=$data->graphicElement->pen["lineWidth"];
            
             if(isset($data->graphicElement->pen["lineColor"]))
                 $drawcolor=array("r"=>hexdec(substr($data->graphicElement->pen["lineColor"],1,2)),"g"=>hexdec(substr($data->graphicElement->pen["lineColor"],3,2)),"b"=>hexdec(substr($data->graphicElement->pen["lineColor"],5,2)));
            
            $dash="";
                    if($data->graphicElement->pen["lineStyle"]=="Dotted")
                    $dash="0,1";
                elseif($data->graphicElement->pen["lineStyle"]=="Dashed")
                    $dash="4,2"; 
                elseif($data->graphicElement->pen["lineStyle"]=="Solid")
                    $dash="";
//echo "$borderwidth,";
           
            $border=array("LTRB" => array('width' => $borderwidth,'color' =>$drawcolor,'cap'=>'square',
                            'join'=>'miter','dash'=>$dash));
           
        if(isset($data->reportElement["forecolor"])) {
            $drawcolor=array("r"=>hexdec(substr($data->reportElement["forecolor"],1,2)),"g"=>hexdec(substr($data->reportElement["forecolor"],3,2)),"b"=>hexdec(substr($data->reportElement["forecolor"],5,2)));         
        }
        if(isset($data->reportElement["backcolor"])) {
            $fillcolor=array("r"=>hexdec(substr($data->reportElement["backcolor"],1,2)),"g"=>hexdec(substr($data->reportElement["backcolor"],3,2)),"b"=>hexdec(substr($data->reportElement["backcolor"],5,2)));         
        }

        $printWhenExpression = "";
        if(isset($data->reportElement->printWhenExpression))
        {
            $printWhenExpression = $data->reportElement->printWhenExpression."";
        }
        //$color=array("r"=>$drawcolor["r"],"g"=>$drawcolor["g"],"b"=>$drawcolor["b"]);
        $this->pointer[]=array("type"=>"SetFillColor","r"=>$fillcolor["r"],"g"=>$fillcolor["g"],"b"=>$fillcolor["b"],"hidden_type"=>"fillcolor","elementid"=>$this->elementid,"printWhenExpression"=>$printWhenExpression);
        $this->pointer[]=array("type"=>"SetDrawColor","r"=>$drawcolor["r"],"g"=>$drawcolor["g"],"b"=>$drawcolor["b"],"hidden_type"=>"drawcolor","elementid"=>$this->elementid,"printWhenExpression"=>$printWhenExpression);
        $this->pointer[]=array("type"=>"Ellipse","x"=>$data->reportElement["x"],"y"=>$data->reportElement["y"],"width"=>$data->reportElement["width"],"height"=>$data->reportElement["height"],"hidden_type"=>"ellipse","drawcolor"=>$drawcolor,"fillcolor"=>$fillcolor,'border'=>$border,"elementid"=>$this->elementid,"printWhenExpression"=>$printWhenExpression);
        $this->pointer[]=array("type"=>"SetDrawColor","r"=>0,"g"=>0,"b"=>0,"hidden_type"=>"drawcolor","elementid"=>$this->elementid,"printWhenExpression"=>$printWhenExpression);
        $this->pointer[]=array("type"=>"SetFillColor","r"=>255,"g"=>255,"b"=>255,"hidden_type"=>"fillcolor","elementid"=>$this->elementid,"printWhenExpression"=>$printWhenExpression);
    }
    
    public function element_textField($data) {
        $align="L";
        $fill=0;
        $border=0;
        $fontsize=10;
        $font="helvetica";
        $rotation="";
        $fontstyle="";
        $textcolor = array("r"=>0,"g"=>0,"b"=>0);
        $fillcolor = array("r"=>255,"g"=>255,"b"=>255);
        $stretchoverflow="false";
        $printoverflow="false";
        $height=$data->reportElement["height"];
        $drawcolor=array("r"=>0,"g"=>0,"b"=>0);
        $data->hyperlinkReferenceExpression=$data->hyperlinkReferenceExpression;
        
        //SimpleXML object (1 item) [0] // ->codeExpression[0] ->attributes('xsi', true) ->schemaLocation ->attributes('', true) ->type ->drawText ->checksumRequired barbecue: 
        //SimpleXMLElement Object ( [@attributes] => Array ( [hyperlinkType] => Reference [hyperlinkTarget] => Blank ) [reportElement] => SimpleX
        //print_r( $data["@attributes"]);
        
        if(isset($data->reportElement["forecolor"])) {
            $textcolor = array("r"=>hexdec(substr($data->reportElement["forecolor"],1,2)),"g"=>hexdec(substr($data->reportElement["forecolor"],3,2)),"b"=>hexdec(substr($data->reportElement["forecolor"],5,2)));
        }
        if(isset($data->reportElement["backcolor"])) {
            $fillcolor = array("r"=>hexdec(substr($data->reportElement["backcolor"],1,2)),"g"=>hexdec(substr($data->reportElement["backcolor"],3,2)),"b"=>hexdec(substr($data->reportElement["backcolor"],5,2)));
        }
        if($data->reportElement["mode"]=="Opaque") {
            $fill=1;
        }
        if(isset($data["isStretchWithOverflow"])&&$data["isStretchWithOverflow"]=="true" || isset($data["textAdjust"]) && $data["textAdjust"] == "StretchHeight") {
            $stretchoverflow="true";
        }
        if(isset($data->reportElement["isPrintWhenDetailOverflows"])&&$data->reportElement["isPrintWhenDetailOverflows"]=="true") {
            $printoverflow="true";
        }
        if(isset($data->box)) {
           $topattr=$data->box->topPen->attributes();
          $bottomattr=$data->box->bottomPen->attributes();
          $leftattr=$data->box->leftPen->attributes();
          $rightattr=$data->box->rightPen->attributes();
          
          // echo "<br/><br/>";
          
          $borderstyles=[];
          $arrborder=[];

        if($topattr["lineWidth"]>0)
        {
            $borderstyles['T']=['width'=>$topattr["lineWidth"],'style'=>(string)$topattr["lineStyle"], 'hexcolor'=>(string)$topattr["lineColor"]];
            array_push($arrborder, 'T');
        }
        if($bottomattr["lineWidth"]>0)
        {
            $borderstyles['B']=['width'=>$bottomattr["lineWidth"],'style'=>(string)$bottomattr["lineStyle"],'hexcolor'=>(string)$bottomattr["lineColor"]];
            array_push($arrborder, 'B');
        }
        if($leftattr["lineWidth"]>0)
        {
            $borderstyles['L']=['width'=>$leftattr["lineWidth"],'style'=>(string)$leftattr["lineStyle"],'hexcolor'=>(string)$leftattr["lineColor"]];
            array_push($arrborder, 'L');
        }
        if($rightattr["lineWidth"]>0)
        {
            $borderstyles['R']=['width'=>$rightattr["lineWidth"],'style'=>(string)$rightattr["lineStyle"], 'hexcolor'=>(string)$rightattr["lineColor"]];
            array_push($arrborder, 'R');
        }
        
                // print_r($borderstyles);
                // echo "<br/><br/>";
            foreach($borderstyles as $key=>$borderstylearr)
            {
                if($borderstylearr['style'] && $borderstylearr['style']=='Dotted')
                {
                    $borderstyles[$key]['dash']='0,1';
                }
                else if($borderstylearr['style'] && $borderstylearr['style']=='Dashed')
                {
                    $borderstyles[$key]['dash']='4,2';
                }
                else
                {
                    $borderstyles[$key]['dash']='';   
                }
                $hexcolor=$borderstyles[$key]['hexcolor'];
                $borderstyles[$key]['color']=[
                        hexdec(substr($hexcolor, 1,2)),
                        hexdec(substr($hexcolor, 3,2)),
                        hexdec(substr($hexcolor, 5,2))
                    ];
                
            }
       
            $border=[];
            foreach($arrborder as $borderno =>$bordername)
            {
                $border[$bordername]= ['width' => $borderstyles[$bordername]["width"],'cap' => 'butt', 'join' => 'miter', 
                                    'dash' =>$borderstyles[$bordername]['dash'],'phase'=>0,'color' =>$borderstyles[$bordername]['color']];
            }
            
        }
        if(isset($data->reportElement["key"])) {
            $height=$fontsize*$this->adjust;
        }
        if(isset($data->textElement["textAlignment"])) {
            $align=$this->get_first_value($data->textElement["textAlignment"]);
        }
        if(isset($data->textElement["verticalAlignment"])) {
            
            $valign="T";
            if($data->textElement["verticalAlignment"]=="Bottom")
                $valign="B";
            elseif($data->textElement["verticalAlignment"]=="Middle")
                $valign="C";
            else
                $valign="T";
            
            
        }
        if(isset($data->textElement["rotation"])) {
            $rotation=$data->textElement["rotation"];
        }
        if(isset($data->textElement->font["fontName"])) {
         //   $font=$this->recommendFont($data->textFieldExpression,$data->textElement->font["fontName"],$data->textElement->font["pdfFontName"]);
                //$data->textFieldExpression=$font;//$data->textElement->font["pdfFontName"];
$font=$data->textElement->font["fontName"];
        }
        if(isset($data->textElement->font["size"])) {
            $fontsize=$data->textElement->font["size"];
        }
        if(isset($data->textElement->font["isBold"])&&$data->textElement->font["isBold"]=="true") {
            $fontstyle=$fontstyle."B";
        }
        if(isset($data->textElement->font["isItalic"])&&$data->textElement->font["isItalic"]=="true") {
            $fontstyle=$fontstyle."I";
        }
        if(isset($data->textElement->font["isUnderline"])&&$data->textElement->font["isUnderline"]=="true") {
            $fontstyle=$fontstyle."U";
        }
        
$this->pointer[]=array("type"=>"SetFont","font"=>$font."",
            "pdfFontName"=>$data->textElement->font["pdfFontName"]."","fontstyle"=>$fontstyle."","fontsize"=>$fontsize+0,"hidden_type"=>"font","elementid"=>$this->elementid);        
        $this->pointer[]=array("type"=>"SetXY","x"=>$data->reportElement["x"]+0,"y"=>$data->reportElement["y"]+0,"hidden_type"=>"SetXY","elementid"=>$this->elementid);
        $this->pointer[]=array("type"=>"SetTextColor","forecolor"=>$data->reportElement["forecolor"],"r"=>$textcolor["r"],"g"=>$textcolor["g"],"b"=>$textcolor["b"],"hidden_type"=>"textcolor","elementid"=>$this->elementid);
        $this->pointer[]=array("type"=>"SetFillColor","backcolor"=>$data->reportElement["backcolor"]."","r"=>$fillcolor["r"],"g"=>$fillcolor["g"],"b"=>$fillcolor["b"],"hidden_type"=>"fillcolor","fill"=>$fill,"elementid"=>$this->elementid);
        $this->pointer[]=array("type"=>"SetDrawColor","r"=>$drawcolor["r"],"g"=>$drawcolor["g"],"b"=>$drawcolor["b"],"hidden_type"=>"drawcolor","border"=>$border,"elementid"=>$this->elementid);        
         //$data->hyperlinkReferenceExpression=$this->analyse_expression($data->hyperlinkReferenceExpression);
        //if( $data->hyperlinkReferenceExpression!=''){echo "$data->hyperlinkReferenceExpression";die;}

//echo '$V{'.$this->grouplist[0]["name"].'_COUNT}';
//echo '$V{'.$this->grouplist[1]["name"].'_COUNT}';
//echo '$V{'.$this->grouplist[2]["name"].'_COUNT}';
//echo '$V{'.$this->grouplist[3]["name"].'_COUNT}';
//        
//        echo $data->textFieldExpression."<br/>";//
//echo $data->textFieldExpression."///////".var_dump($this->grouplist)."<br/>";
//echo $this->grouplist[2]["name"];
$data->reportElement['uuid']=$data->reportElement['uuid']."";
// echo $data->reportElement['uuid'].":".$data->textFieldExpression."";
  //echo "<br/>-------------------------------------";
        switch ($data->textFieldExpression) {
            case 'new java.util.Date()':
//### New: =>date("Y.m.d.",....
                $this->pointer[]=array ("type"=>"MultiCell","width"=>$data->reportElement["width"],"height"=>$height,"txt"=>date("Y-m-d H:i:s"),"border"=>$border,"align"=>$align,"fill"=>$fill,"hidden_type"=>"date","soverflow"=>$stretchoverflow,"poverflow"=>$printoverflow,"link"=>$data->hyperlinkReferenceExpression,"valign"=>$valign,
                  "uuid"=>$data->reportElement['uuid'],  "x"=>$data->reportElement["x"]+0,"y"=>$data->reportElement["y"]+0,"elementid"=>$this->elementid);
//### End of modification               
                break;
            case '"Page "+$V{PAGE_NUMBER}+" of"':
                $this->pointer[]=array("type"=>"MultiCell","width"=>$data->reportElement["width"],"height"=>$height,"txt"=>'Page $this->PageNo() of',"border"=>$border,"align"=>$align,"fill"=>$fill,"hidden_type"=>"pageno","soverflow"=>$stretchoverflow,"poverflow"=>$printoverflow,"link"=>$data->hyperlinkReferenceExpression,"pattern"=>$data["pattern"],"valign"=>$valign,
                   "uuid"=>$data->reportElement['uuid'], "x"=>$data->reportElement["x"]+0,"y"=>$data->reportElement["y"]+0,"elementid"=>$this->elementid);
                break;
            case '$V{PAGE_NUMBER}':
                
                // $this->pdf->getAliasNbPages();
                if(isset($data["evaluationTime"])&&$data["evaluationTime"]=="Report") {
                    $this->pointer[]=array("type"=>"MultiCell","width"=>$data->reportElement["width"],"height"=>$height,"txt"=>'{{:ptp:}}',"border"=>$border,"align"=>$align,"fill"=>$fill,"hidden_type"=>"pageno","soverflow"=>$stretchoverflow,"poverflow"=>$printoverflow,"link"=>$data->hyperlinkReferenceExpression,"pattern"=>$data["pattern"],"valign"=>$valign,
                      "uuid"=>$data->reportElement['uuid'],  "x"=>$data->reportElement["x"]+0,"y"=>$data->reportElement["y"]+0,"elementid"=>$this->elementid);
                }
                else {
                    $this->pointer[]=array("type"=>"MultiCell","width"=>$data->reportElement["width"],"height"=>$height,"txt"=>'$this->PageNo()',"border"=>$border,"align"=>$align,"fill"=>$fill,"hidden_type"=>"pageno","soverflow"=>$stretchoverflow,"poverflow"=>$printoverflow,"link"=>$data->hyperlinkReferenceExpression,"pattern"=>$data["pattern"],"valign"=>$valign,
                        "uuid"=>$data->reportElement['uuid'],"x"=>$data->reportElement["x"]+0,"y"=>$data->reportElement["y"]+0,"elementid"=>$this->elementid);
                }
                break;
            case '" " + $V{PAGE_NUMBER}':
                $this->pointer[]=array("type"=>"MultiCell","width"=>$data->reportElement["width"],"height"=>$height,"txt"=>' {{:ptp:}}',
                    "border"=>$border,"align"=>$align,"fill"=>$fill,"hidden_type"=>"nb","soverflow"=>$stretchoverflow,"poverflow"=>$printoverflow,"link"=>$data->hyperlinkReferenceExpression,"pattern"=>$data["pattern"],"valign"=>$valign,
                    "uuid"=>$data->reportElement['uuid'],"x"=>$data->reportElement["x"]+0,"y"=>$data->reportElement["y"]+0,"elementid"=>$this->elementid);
                break;

            default:
                $writeHTML=false;
               
                if($data->reportElement->property["name"]=="writeHTML" || $data->textElement['markup']=='html')
                    $writeHTML=1;
                if(isset($data->reportElement["isPrintRepeatedValues"]))
                    $isPrintRepeatedValues=$data->reportElement["isPrintRepeatedValues"];

               
                $this->pointer[]=array("type"=>"MultiCell","width"=>$data->reportElement["width"]+0,"height"=>$height+0,"txt"=>$data->textFieldExpression."",
                        "border"=>$border,"align"=>$align,"fill"=>$fill,
                        "hidden_type"=>"field","soverflow"=>$stretchoverflow,"poverflow"=>$printoverflow,"uuid"=>$data->reportElement['uuid'],
                        "printWhenExpression"=>$data->reportElement->printWhenExpression."",
                        "link"=>$data->hyperlinkReferenceExpression."","pattern"=>$data["pattern"],"linktarget"=>$data["hyperlinkTarget"]."",
                        "writeHTML"=>$writeHTML,"isPrintRepeatedValues"=>$isPrintRepeatedValues,"rotation"=>$rotation,"valign"=>$valign,
                    "x"=>$data->reportElement["x"]+0,"y"=>$data->reportElement["y"]+0,"elementid"=>$this->elementid);
                
                
                break;
        }
        //echo  "*".$data->textFieldExpression.":".$data->reportElement['uuid']."<br/>";

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

    public function drawHTMLTable($sql='')
    {
        $q=$this->dbQuery($sql);
        $header='<style>td,th{border:solid 1px;}</style><table><thead>';
        $body='<tbody>';
        $i=0;
        $colheader=array();
        while($r=$this->dbFetchData($q))
        {
            if($i==0)
            {
                $header.='<tr>';
                foreach($r as $col=>$colvalue)
                {
                    array_push($colheader,$col);
                    $header.='<th>'.$col.'</th>';
                }
                $header.='</tr></thead>';    
            }

            $body.='<tr>';
            foreach($r as $col=>$colvalue)
            {
                if(strpos($col,'pass')!==false)
                {
                    $body.='<td style="color:red">protected</td>';
                }
                else
                {
                    $body.='<td>'.$colvalue.'</td>';    
                }
                
            }
            $body.='</tr>';
            $i++;
        }

        if($body=='<tbody>')
        {
            echo 'No data found';
        }
       else
        {
            echo $header.$body.'</tbody></table>';    
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

        if($this->debugsql==true) {
            
            echo "<textarea cols='100' rows='40'>$this->sql</textarea>";
            if($_GET['showhtmldata']=='1')
            {

                $this->drawHTMLTable($this->sql);    
            }
            
            die;
        }

            $this->setDBEnvVariable();
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

                // if($this->cndriver=='mysql'||$this->cndriver=='mysqli')
              //  {
                    while ($row = $this->dbFetchData($result))
                    {                       
                        foreach($this->arrayfield as $out) 
                        {
                            $fieldvalue = str_replace("\\", "\\\\", $row["$out"]);
                            $this->arraysqltable[$this->m]["$out"]=$fieldvalue;
                        }
                        
                        $this->m++;
                   }
                // }

                    

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


      public function variable_calculation($rowno='') {


        foreach($this->arrayVariable as $k=>$out) {

            if($out["calculation"]!=""){
                      $out['target']=str_replace(array('$F{','}'),'',$out['target']);//,  (strlen($out['target'])-1) ); 

                
            }
                
         //   echo $out['resetType']. "<br/><br/>";
            switch($out["calculation"]) {
                case "Sum":

                        $value=$this->arrayVariable[$k]["ans"];
                    
                    
                    if($out['resetType']=='' || $out['resetType']=='None' ){
                            if(isset($this->arrayVariable[$k]['class'])&&$this->arrayVariable[$k]['class']=="java.sql.Time") {
                            //    foreach($this->arraysqltable as $table) {
                                    $value=$this->time_to_sec($value);

                                    $value+=$this->time_to_sec($this->arraysqltable[$rowno]["$out[target]"]);
                                    //$sum=$sum+substr($table["$out[target]"],0,2)*3600+substr($table["$out[target]"],3,2)*60+substr($table["$out[target]"],6,2);
                               // }
                                //$sum= floor($sum / 3600).":".floor($sum%3600 / 60);
                                //if($sum=="0:0"){$sum="00:00";}
                                $value=$this->sec_to_time($value);
                            }
                            else {
                                //resetGroup
                               // foreach($this->arraysqltable as $table) {
                              
                                         $value=round($value,10)+$this->arraysqltable[$rowno]["$out[target]"];
                                        //echo "k=$k, $value<br/>";
                              //      $table[$out["target"]];
                             //   }
                            }
                         
                    }// finisish resettype=''
                    elseif($out['resetType']=='Group') //reset type='group'
                    {
                  
                        
//                       print_r($this->grouplist);
//                       echo "<br/>";
//                       echo $out['resetGroup'] ."<br/>";
//                       //                        if( $this->arraysqltable[$this->global_pointer][$this->group_pointer]!=$this->arraysqltable[$this->global_pointer-1][$this->group_pointer])
//                        if( $this->arraysqltable[$this->global_pointer][$this->group_pointer]!=$this->arraysqltable[$this->global_pointer-1][$this->group_pointer])
  //                           $value=0;
  //            
                       if($this->groupnochange>=0){
                            
                            
                       //     for($g=$this->groupnochange;$g<4;$g++){
                         //        $value=0;    
//                                  $this->arrayVariable[$k]["ans"]=0;
  //                                echo $this->grouplist[$g]["name"].":".$this->groupnochange."<br/>";
                           // }
                       }
                      //    echo $this->global_pointer.",".$this->group_pointer.",".$this->arraysqltable[$this->global_pointer][$this->group_pointer].",".$this->arraysqltable[$this->global_pointer-1][$this->group_pointer].",".$this->arraysqltable[$rowno]["$out[target]"];
                                 if(isset($this->arrayVariable[$k]['class'])&&$this->arrayVariable[$k]['class']=="java.sql.Time") {
                                      $value+=$this->time_to_sec($this->arraysqltable[$rowno]["$out[target]"]);
                                //$sum= floor($sum / 3600).":".floor($sum%3600 / 60);
                                //if($sum=="0:0"){$sum="00:00";}
                                $value=$this->sec_to_time($value);
                                 }
                                else {
                                    
                                      $value+=$this->arraysqltable[$rowno]["$out[target]"];
                                                           
 
                                }
                                  
                    }

                        
                    $this->arrayVariable[$k]["ans"]=$value;
                    
              //      echo ",$value<br/>";
                    break;
                case "Average":
    $value=$this->arrayVariable[$k]["ans"];
                    
                    
                    if($out['resetType']==''|| $out['resetType']=='None' ){
                            if(isset($this->arrayVariable[$k]['class'])&&$this->arrayVariable[$k]['class']=="java.sql.Time") {
                                    $value=$this->time_to_sec($value);
                                    $value+=$this->time_to_sec($this->arraysqltable[$rowno]["$out[target]"]);
                                $value=$this->sec_to_time($value);
                            }
                            else {
                                         $value=($value*($this->report_count-1)+$this->arraysqltable[$rowno]["$out[target]"])/$this->report_count;
                            }
                         
                    }// finisish resettype=''
                    elseif($out['resetType']=='Group') //reset type='group'
                    {
                       if($this->groupnochange>=0){
                       }
                                 if(isset($this->arrayVariable[$k]['class'])&&$this->arrayVariable[$k]['class']=="java.sql.Time") {
                                      $value+=$this->time_to_sec($this->arraysqltable[$rowno]["$out[target]"]);
                                $value=$this->sec_to_time($value);
                                 }
                                else {
                                    $previousgroupcount=$this->group_count[$out['resetGroup']]-2;
                                    $newgroupcount=$this->group_count[$out['resetGroup']]-1;
                                    $previoustotal=$value*$previousgroupcount;
                                    $newtotal=$previoustotal+$this->arraysqltable[$rowno]["$out[target]"];
                                    $value=($newtotal)/$newgroupcount;
                                }
                                  
                    }

                        
                    $this->arrayVariable[$k]["ans"]=$value;

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
                case "":
                   // $out["target"]=0;
                    if(strpos( $out["target"], "_COUNT")==-1)
                     $this->arrayVariable[$k]["ans"]=$this->analyse_expression( $out['target'], true);
                    
//                     $out["target"]= $this->analyse_expression( $out['target'], true);
                    
                    //other cases needed, temporary leave 0 if not suitable case
                    break;

            }
              
        }
    }


    public function outpage($out_method="I",$filename="", $othername="", $pdf_password="") {
    if($_REQUEST['forceexceloutput']!=""){
        $this->pdflib="XLS";
        $filename=($othername!="")?($othername.".xls"):"file1.xls";
    }
    
            if($this->pdflib=="TCPDF") {
                if($this->arrayPageSetting["orientation"]=="P")
                {
                    $this->pdf=new TCPDF($this->arrayPageSetting["orientation"],'pt',array(intval($this->arrayPageSetting["pageWidth"]),intval($this->arrayPageSetting["pageHeight"])),true);
                }
                else {
                    $this->pdf=new TCPDF($this->arrayPageSetting["orientation"],'pt',array( intval($this->arrayPageSetting["pageHeight"]),intval($this->arrayPageSetting["pageWidth"])),true);
                }

                if(!empty($pdf_password))
                {
                    $permissions = array('print', 'copy', 'modify');
                    $owner_pass = null;
                    $mode = 0;
                    $pubkeys = null;
                    $this->pdf->SetProtection($permissions, $pdf_password, $owner_pass, $mode, $pubkeys);
                }
                
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
public function element_pieChart($data){
          $height=$data->chart->reportElement["height"];
          $width=$data->chart->reportElement["width"];
         $x=$data->chart->reportElement["x"];
         $y=$data->chart->reportElement["y"];
          $charttitle['position']=$data->chart->chartTitle['position'];

           $charttitle['text']=$data->chart->chartTitle->titleExpression;
          $chartsubtitle['text']=$data->chart->chartSubTitle->subtitleExpression;
          $chartLegendPos=$data->chart->chartLegend['position'];



         // $ylabel=$data->linePlot->valueAxisLabelExpression;


          $param=array();
          foreach($data->categoryDataset->dataset->datasetRun->datasetParameter as $tag=>$value){
              $param[]=  array("$value[name]"=>$value->datasetParameterExpression);
          }
//          print_r($param);

         $this->pointer[]=array('type'=>'PieChart','x'=>$x,'y'=>$y,'height'=>$height,'width'=>$width,'charttitle'=>$charttitle,
            'chartsubtitle'=> $chartsubtitle,
               'chartLegendPos'=> $chartLegendPos,'dataset'=>$dataset,'seriesexp'=>$seriesexp,
            'valueexp'=>$valueexp,'param'=>$param,'sql'=>$sql,'ylabel'=>$ylabel,"elementid"=>$this->elementid);

    }
    public function element_pie3DChart($data){


    }

    public function element_Chart($data,$type=''){
    
          $height=$data->chart->reportElement["height"]+0;
          $width=$data->chart->reportElement["width"]+0;
         $x=$data->chart->reportElement["x"]+0;
         $y=$data->chart->reportElement["y"]+0;
         $charttitle=array();
         $chartsubtitle=array();
         $chartlegend=array();
         $theme=$data->chart['theme'];
         $defaultfont="times new roman";
         
         if($data->chart->chartTitle->titleExpression.""!=""){
          $charttitle['text']=$data->chart->chartTitle->titleExpression."";
          $charttitle['position']= ($data->chart->chartTitle['position']."" !="" ? $data->chart->chartTitle['position']."" :'');
          $charttitle['fontname']=($data->chart->chartTitle->font['fontName']."" ? $data->chart->chartTitle->font['fontName']."":$defaultfont);
          $charttitle['fontsize']=($data->chart->chartTitle->font['size']+0?$data->chart->chartTitle->font['size']+0:10);
          $charttitle['color']=($data->chart->chartTitle['color'].""?$data->chart->chartTitle['color']."":'000000');
          $charttitle['isBold']=($data->chart->chartTitle['isBold'].""?true:false);
          $charttitle['isUnderline']=($data->chart->chartTitle['isUnderline'].""?true:false);
          $charttitle['isItalic']=($data->chart->chartTitle['isItalic'].""?true:false);
         }
         else{
             $charttitle=null;
         }
         
          if($data->chart->chartSubtitle->subtitleExpression.""!=''){
              
             $chartsubtitle['fontname']=($data->chart->chartSubtitle->font['fontName'].""?"":$defaultfont);
             
            $chartsubtitle['text']=($data->chart->chartSubtitle->subtitleExpression.""?"":'');
             $chartsubtitle['fontsize']=($data->chart->chartSubtitle->font['size']+10?"":9);
            $chartsubtitle['color']=($data->chart->chartSubtitle['color'].""?"":'000000');
          $chartsubtitle['isBold']=($data->chart->chartSubtitle['isBold'].""?true:false);
          $chartsubtitle['isUnderline']=($data->chart->chartSubtitle['isUnderline'].""?true:false);
          $chartsubtitle['isItalic']=($data->chart->chartSubtitle['isItalic'].""?true:false);
          }
          else{
              $chartsubtitle=null;
          }
          
          if($data->chart['isShowLegend']=='true' || $data->chart['isShowLegend']==''){
          $chartlegend['position']=($data->chart->chartLegend['position'].""?"":"Right");
          $chartlegend['color']=($data->chart->chartLegend['textColor'].""?"":'#000000');
if($data->chart->chartLegend['backgroundColor'].""=="")
    $chartlegend['backgroundColor']='#FFFFFF';
else
    $chartlegend['backgroundColor']=$data->chart->chartLegend['backgroundColor']."";

          $chartlegend['size']=($data->chart->chartLegend['size']+0?"":9);
          $chartlegend['fontname']=($data->chart->chartLegend['fontName'].""?"":$defaultfont);
          $chartlegend['isUnderline']=($data->chart->chartLegend['isUnderline'].""?true:false);
          $chartlegend['isBold']=($data->chart->chartLegend['isBold'].""?true:false);
          $chartlegend['isItalic']=($data->chart->chartLegend['isItalic'].""?true:false);
         }else{
             
             $chartlegend=null;
             
         }
         
          /*
           * <chartLegend textColor="#666666" backgroundColor="#CCCCFF" position="Left">
                        <font fontName="Times New Roman" size="12" isUnderline="true"/>
                    </chartLegend>
           */
          $dataset=$data->categoryDataset->dataset->datasetRun['subDataset']."";


if($type=='pieChart'){
          $dataset=$data->pieDataset->dataset->datasetRun['subDataset'];
          $seriesexp=$data->pieDataset->keyExpression;
          $valueexp=$data->pieDataset->valueExpression;
          $bb=$data->pieDataset->dataset->datasetRun['subDataset'];
           $sql=$this->arraysubdataset["$bb"]['sql'];
}
else{
          $i=0;
           $seriesexp=array();
          $catexp=array();
          $valueexp=array();
          $labelexp=array();

          $subcatdataset=$data->categoryDataset;
          foreach($subcatdataset as $cat => $catseries){
            foreach($catseries as $a => $series){
               if("$series->categoryExpression"!=''){

                    $series->seriesExpression=  str_replace(array('"',"'"), '',$series->seriesExpression);
                    $series->categoryExpression=  str_replace(array('"',"'"), '',$series->categoryExpression);
                    $series->valueExpression=  str_replace(array('"',"'"), '',$series->valueExpression);
                    $series->labelExpression=  str_replace(array('"',"'"), '',$series->labelExpression);
       
                array_push( $seriesexp,"$series->seriesExpression");
                array_push( $catexp,"$series->categoryExpression");
                array_push( $valueexp,"$series->valueExpression");
                array_push( $labelexp,"$series->labelExpression");
                
               }

            }

          }
          $bb=$data->categoryDataset->dataset->datasetRun['subDataset'];
          $sql=$this->arraysubdataset[$bb]['sql'];

}

          switch($type){
             case "stackedBarChart":
            case "barChart":
                $ylabel=$data->barPlot->valueAxisLabelExpression;
                $xlabel=$data->barPlot->categoryAxisLabelExpression;
                $maxy=$data->barPlot->rangeAxisMaxValueExpression;
                $miny=$data->barPlot->rangeAxisMinValueExpression;
                $valueAxisFormat['linecolor']=$data->barPlot->valueAxisFormat->axisFormat['axisLineColor']."";
                $valueAxisFormat['labelcolor']=$data->barPlot->valueAxisFormat->axisFormat['tickLabelColor']."";
                $valueAxisFormat['fontname']=$data->barPlot->valueAxisFormat->axisFormat->tickLabelFont->font['fontName']."";
                $categoryAxisFormat['linecolor']=$data->barPlot->categoryAxisFormat->axisFormat['axisLineColor']."";
                $categoryAxisFormat['labelcolor']=$data->barPlot->categoryAxisFormat->axisFormat['tickLabelColor']."";
                $categoryAxisFormat['fontname']=$data->barPlot->categoryAxisFormat->axisFormat->tickLabelFont->font['fontName']."";
                break;
            case "lineChart":
                $ylabel=$data->linePlot->valueAxisLabelExpression;
                $xlabel=$data->linePlot->categoryAxisLabelExpression;
                $maxy=$data->linePlot->rangeAxisMaxValueExpression;
                $miny=$data->linePlot->rangeAxisMinValueExpression;
                $valueAxisFormat['linecolor']=$data->linePlot->valueAxisFormat->axisFormat['axisLineColor']."";
                $valueAxisFormat['labelcolor']=$data->linePlot->valueAxisFormat->axisFormat['tickLabelColor']."";
                $valueAxisFormat['fontname']=$data->linePlot->valueAxisFormat->axisFormat->tickLabelFont->font['fontName']."";
                $categoryAxisFormat['linecolor']=$data->linePlot->categoryAxisFormat->axisFormat['axisLineColor']."";
                $categoryAxisFormat['labelcolor']=$data->linePlot->categoryAxisFormat->axisFormat['tickLabelColor']."";
                $categoryAxisFormat['fontname']=$data->linePlot->categoryAxisFormat->axisFormat->tickLabelFont->font['fontName']."";

                $showshape=$data->linePlot["isShowShapes"];
                break;
             case "stackedAreaChart":
                      $ylabel=$data->areaPlot->valueAxisLabelExpression;
                        $xlabel=$data->areaPlot->categoryAxisLabelExpression;
                        $maxy=$data->areaPlot->rangeAxisMaxValueExpression;
                        $miny=$data->areaPlot->rangeAxisMinValueExpression;
                $valueAxisFormat['linecolor']=$data->areaPlot->valueAxisFormat->axisFormat['axisLineColor']."";
                $valueAxisFormat['labelcolor']=$data->areaPlot->valueAxisFormat->axisFormat['tickLabelColor']."";
                $valueAxisFormat['fontname']=$data->areaPlot->valueAxisFormat->axisFormat->tickLabelFont->font['fontName']."";
                $valueAxisFormat['size']=$data->areaPlot->valueAxisFormat->axisFormat->tickLabelFont->font['size']+0;
                $categoryAxisFormat['linecolor']=$data->areaPlot->categoryAxisFormat->axisFormat['axisLineColor']."";
                $categoryAxisFormat['labelcolor']=$data->areaPlot->categoryAxisFormat->axisFormat['tickLabelColor']."";
                $categoryAxisFormat['fontname']=$data->areaPlot->categoryAxisFormat->axisFormat->tickLabelFont->font['fontName']."";
                $categoryAxisFormat['size']=$data->areaPlot->categoryAxisFormat->axisFormat->tickLabelFont->font['size']+0;
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
            'chartsubtitle'=> $chartsubtitle,'chartlegend'=> $chartlegend,
             'dataset'=>$dataset,'seriesexp'=>$seriesexp,
             'catexp'=>$catexp,'valueexp'=>$valueexp,'labelexp'=>$labelexp,'param'=>$param,'sql'=>$sql,
             'xlabel'=>$xlabel,'showshape'=>$showshape,  'ylabel'=>$ylabel,
             'scalesetting'=>$scalesetting,'valueAxisFormat'=>$valueAxisFormat,'categoryAxisFormat'=>$categoryAxisFormat,
             "elementid"=>$this->elementid);

    }



public function setChartColor(){

    $k=0;
$this->chart->setColorPalette($k,0,255,88);$k++;
$this->chart->setColorPalette($k,121,88,255);$k++;
$this->chart->setColorPalette($k,255,91,99);$k++;
$this->chart->setColorPalette($k,255,0,0);$k++;
$this->chart->setColorPalette($k,0,0,100);$k++;
$this->chart->setColorPalette($k,200,0,100);$k++;
$this->chart->setColorPalette($k,0,100,0);$k++;
$this->chart->setColorPalette($k,100,0,0);$k++;
$this->chart->setColorPalette($k,200,0,0);$k++;
$this->chart->setColorPalette($k,0,0,200);$k++;
$this->chart->setColorPalette($k,50,0,0);$k++;
$this->chart->setColorPalette($k,100,0,50);$k++;
$this->chart->setColorPalette($k,0,50,0);$k++;
$this->chart->setColorPalette($k,100,50,0);$k++;
$this->chart->setColorPalette($k,50,100,50);$k++;
$this->chart->setColorPalette($k,0,255,0);$k++;
$this->chart->setColorPalette($k,100,50,0);$k++;
$this->chart->setColorPalette($k,200,100,50);$k++;
$this->chart->setColorPalette($k,100,50,200);$k++;
$this->chart->setColorPalette($k,0,200,0);$k++;
$this->chart->setColorPalette($k,200,100,0);$k++;
$this->chart->setColorPalette($k,200,50,50);$k++;
$this->chart->setColorPalette($k,50,50,50);$k++;
$this->chart->setColorPalette($k,200,100,100);$k++;
$this->chart->setColorPalette($k,50,50,100);$k++;
$this->chart->setColorPalette($k,100,0,200);$k++;
$this->chart->setColorPalette($k,200,50,100);$k++;
$this->chart->setColorPalette($k,100,100,200);$k++;
$this->chart->setColorPalette($k,0,0,50);$k++;
$this->chart->setColorPalette($k,50,250,200);$k++;
$this->chart->setColorPalette($k,100,250,200);$k++;
$this->chart->setColorPalette($k,10,10,10);$k++;
$this->chart->setColorPalette($k,20,30,50);$k++;
$this->chart->setColorPalette($k,80,150,200);$k++;
$this->chart->setColorPalette($k,30,70,20);$k++;
$this->chart->setColorPalette($k,33,60,0);$k++;
$this->chart->setColorPalette($k,150,0,200);$k++;
$this->chart->setColorPalette($k,20,60,50);$k++;
$this->chart->setColorPalette($k,50,250,250);$k++;
$this->chart->setColorPalette($k,33,250,70);$k++;

}
public function fetchPieChartDataSet($catexp=[],$seriesexp=[],$valueexp=[],$labelexp=[],$xlabel='',$ylabel='',$data=[]){
global $pchartfolder;
$categorymethod="";
//echo "$catexp,$seriesexp,$valueexp,$labelexp";
include_once("$pchartfolder/class/pData.class.php");
$DataSet = new pData();
    $n=0;
    $ds=trim($data['dataset']);
    if($ds!=""){
              $sql=$this->subdataset[$ds];
        $param=$data['param'];
        foreach($param as $p)
            foreach($p as $tag =>$value)
                $sql=str_replace('$P{'.$tag.'}',$value, $sql);
          $sql=$this->changeSubDataSetSql($sql);
         //  die;
        }
    else
        $sql=$this->sql;
    $result = $this->dbQuery($sql); //query from db
    
    
   $chartdata=array();
    $seriesname=array();
    $i=0;
    while ($row = $this->dbFetchData($result)) {

                $j=0;
                foreach($row as $key => $value){
                    if($value=='')
                        $value=0;
                    

                    
                    
                    //get possible series
                    if( $key==str_replace(array('$F{','}'),'',$seriesexp[0])){

                           array_push($seriesname,$value);
                       }
                    
                        foreach($valueexp as $v => $y){
                         if($key==str_replace(array('$F{','}'),'',$y)){
                             $chartdata[$i]=(int)$value;
                           $j++;
                          }
                        }   
             }
             
            $i++;
            }

        $DataSet->addPoints($chartdata,"valuepoint");   
        $DataSet->setSerieDescription('valuepoint',"Value");

         $DataSet->addPoints($seriesname,"label");
        $DataSet->setAbscissa('label');
        //  $DataSet->setAxisName(0,$ylabel);
  if($i==0)
      return 0;
      
        return  $DataSet;

}
public function analyse_dsexpression($data=[],$txt=''){
        $i=0;
        $backcurl='___';
        $singlequote="|_q_|";
        $doublequote="|_qq_|";
        $fm=str_replace('{',"_",$txt);
        $fm=str_replace('}',$backcurl,$fm);
        $isstring=false;
        $tmpplussymbol='|_plus_|';
       foreach($data as $key=> $datavalue){
            $tmpfieldvalue=str_replace("+",$tmpplussymbol,$datavalue);
            $tmpfieldvalue=str_replace("'", $singlequote,$tmpfieldvalue);
            $tmpfieldvalue=str_replace('"', $doublequote,$tmpfieldvalue);
           if(is_numeric($tmpfieldvalue) && $tmpfieldvalue!="" && ($this->left($tmpfieldvalue,1)>0||$this->left($tmpfieldvalue,1)=='-')){
            $fm =str_replace('$F_'.$key.$backcurl,$tmpfieldvalue,$fm);
            
           }
           else{
               $fm =str_replace('$F_'.$key.$backcurl,"'".$tmpfieldvalue."'",$fm);
            $isstring=true;
           }
           
       }



   /*
        $pointerposition=$this->global_pointer+$this->offsetposition;
       
       
       foreach($this->arrayVariable as $vv=>$av){
            $i++;
            $vv=str_replace('$V{',"",$vv);
            $vv=str_replace('}',$backcurl,$vv);
            $vv=str_replace("'", $singlequote,$vv);
            $vv=str_replace('"', $doublequote,$vv);
             if(strpos($fm,'_COUNT')!==false){
                 $fm=str_replace('$V_'.$this->grouplist[0]["name"].'_COUNT'.$backcurl,($this->group_count[$this->grouplist[0]["name"]]-1),$fm);
                 $fm=str_replace('$V_'.$this->grouplist[1]["name"].'_COUNT'.$backcurl,($this->group_count[$this->grouplist[1]["name"]]-1),$fm);
                 $fm=str_replace('$V_'.$this->grouplist[2]["name"].'_COUNT'.$backcurl,($this->group_count[$this->grouplist[2]["name"]]-1),$fm);
                 $fm=str_replace('$V_'.$this->grouplist[3]["name"].'_COUNT'.$backcurl,($this->group_count[$this->grouplist[3]["name"]]-1),$fm);
                 
                 
             }
           else{               
            if($av["ans"]!="" && is_numeric($av["ans"]) && ($this->left($av["ans"],1)||$this->left($av["ans"],1)=='-' )>0){
                 $av["ans"]=str_replace("+",$tmpplussymbol,$av["ans"]);
                 $fm=str_replace('$V_'.$vv.$backcurl,$av["ans"],$fm);
            }
            else{
                $av["ans"]=str_replace("+",$tmpplussymbol,$av["ans"]);
                 $fm=str_replace('$V_'.$vv.$backcurl,"'".$av["ans"]."'",$fm);
            $isstring=true;
            }
           }
       }
      
       
     $fm=str_replace('$V_REPORT_COUNT'.$backcurl,$this->report_count,$fm);
       foreach($this->arrayParameter as  $pv => $ap) {
           $ap=str_replace("+",$tmpplussymbol,$ap);
                       $ap=str_replace("'", $singlequote,$ap);
                       $ap=str_replace('"', $doublequote,$ap);
           if(is_numeric($ap)&&$ap!=''&& ($this->left($ap,1)>0 || $this->left($ap,1)=='-')){
                  $fm = str_replace('$P_'.$pv.$backcurl, $ap,$fm);
           }
           else{
            $fm = str_replace('$P_'.$pv.$backcurl, "'".$ap."'",$fm);
               $isstring=true;
           }
        }
*/
     //  echo $fm.",";
       if($fm=='')
           return "";
       else
       {
          if(strpos($fm, '"')!==false)
            $fm=str_replace('+'," . ",$fm);
          if(strpos($fm, "'")!==false)
            $fm=str_replace('+'," . ",$fm);
                       $fm=str_replace($tmpplussymbol,"+",$fm);
     $fm=str_replace('$this->PageNo()',"''",$fm);
                       $fm=str_replace($singlequote,"\'" ,$fm);
                       $fm=str_replace( $doublequote,'"',$fm);
                       
                       if((strpos('"',$fm)==false) || (strpos("'",$fm)==false)){
                           $fm=str_replace('--', '- -', $fm);
                           $fm=str_replace('++', '+ +', $fm);
                       }

      eval("\$result= ".$fm.";");
    
      return $result;
      
       }
      
      
      


}
public function fetchChartDataSet($catexp=[],$seriesexp=[],$valueexp=[],$labelexp='',$xlabel='',$ylabel='',$data=[]){
global $pchartfolder;
$categorymethod="";
if(count($catexp)>1){
    if($catexp[0]==$catexp[1] ){
        $categorymethod="s";
        
    }
    else{
        $categorymethod="c";

    }
}
else
        $categorymethod="c";

          include_once("$pchartfolder/class/pData.class.php");
    $catarr=array();
    $DataSet = new pData();
    $n=0;
    $ds=trim($data['dataset']);
    if($ds!=""){
              $sql=$this->subdataset[$ds];
        $param=$data['param'];
        foreach($param as $p)
            foreach($p as $tag =>$value)
                $sql=str_replace('$P{'.$tag.'}',$value, $sql);
          $sql=$this->changeSubDataSetSql($sql);
         //  die;
        }
    else
        $sql=$this->sql;
    $result = $this->dbQuery($sql); //query from db
//    echo $sql;
    $categoryname=array();
   $chartdata=array();
    $seriesname=array();
    $i=0;
    $maxnumber=-9999;
    while ($row = $this->dbFetchData($result)) {

                $j=0;
                foreach($row as $key => $value){
                    if($value=='')
                        $value=0;
                                                        
                    foreach($catexp as $cindex =>$cvalue){//loop category
                                        $cvalue=$this->analyse_dsexpression($row,$cvalue);
                        array_push($categoryname,$cvalue);

                    //get possible series
                //    foreach($seriesexp as $sindex=>$svalue){
                             
                  //     }
                    $kk=0;
                        foreach($valueexp as $vindex => $vvalue){
                             $svalue=$this->analyse_dsexpression($row,$seriesexp[$kk]);
                             
                             array_push($seriesname,$svalue);
                             $vvalue=$this->analyse_dsexpression($row,$vvalue);
                             if($vvalue=='')
                                 $vvalue=VOID;
                                 
                             $chartdata[$cvalue][$svalue]=$vvalue;

                             if($vvalue !=VOID && $maxnumber<$vvalue){
                            // echo "$maxnumber<$vvalue =  ".($maxnumber<$vvalue)."<br/>"; 
                                     $maxnumber=$vvalue;
                                     }
                             $kk++;

                          }
                        }
                        
                }//finish loop category
            $i++;
            }

//echo $maxnumber;
            $categoryname= array_unique($categoryname);
            $seriesname= array_unique($seriesname);
            $DataSet->addPoints($categoryname,"categoryaxis");  
            $DataSet->setSerieDescription('categoryaxis',$xlabel);
            $DataSet->setAbscissa('categoryaxis');
            $newchartdata=array();
$devidevalue=1;
$devidelabel='';

if($maxnumber>1000000){
$devidevalue= 1000000;
$devidelabel=' (KK)';
}elseif($maxnumber>1000){
$devidevalue= 1000;
$devidelabel=' (K)';
}

            foreach($seriesname as $sindex=>$svalue){
                
                foreach($categoryname as $c =>$cv){
                    if($chartdata[$cv][$svalue]!=VOID)
                        array_push($newchartdata,($chartdata[$cv][$svalue]/$devidevalue));   
                    else
                         array_push($newchartdata,VOID);   
                }
              //  echo "$sindex,$svalue=".print_r($newchartdata,true).$chartdata[$cv][$svalue]."<br/><br/>";
                
             // echo "$sindex=$svalue | $cv ";
        //    print_r($newchartdata);echo "<br/>";
            
                $DataSet->addPoints($newchartdata,$svalue);
                $newchartdata=array();
                
            }
            
//            print_r($chartdata);die;
//         $categoryname=   array_unique($categoryname);
        // print_r($categoryname);
        // die;
        //get possible x axis
  //          
            //loop till end of array
            
            
/*
if($categorymethod=="s"){
//separate xaxis by series

            for($tmpi=0;$tmpi<count($chartdata);$tmpi++){
                foreach($chartdata[$tmpi] as $datakey=>$datavalue){             
                        $newchartdata[$datakey][]=$datavalue;
                }
            }
$j=0;
foreach( $newchartdata as $c =>$cv){
        if($seriesexp[$j]!="")
        $c=$seriesexp[$j];
         $DataSet->addPoints($cv,$c);
         $j++;
        $n=$n+1;
     }
        $catarr= array_unique($catarr);
        $DataSet->addPoints($catarr,"xaxis");   
        $DataSet->setSerieDescription('xaxis',$xlabel);
        $DataSet->setAbscissa('xaxis');
}
else{

//separate xaxis by category
$newcategoryarr=array();
//print_r($chartdata);
            foreach($seriesname as $sid =>$serie){      
        //      echo "$sid =>$serie<br/>";
        $ii=0;
              foreach($chartdata[$sid] as $ind=>$indv){
                
                    
                //      print_r($catarr);
                         
                        $newchartdata[$serie][$ii]=$indv;
                        if($catexp[$ii]!="")
                        $ind=$catexp[$ii];
                        array_push($newcategoryarr,$ind);
                        $ii++;
                        
                }
            }
//print_r($newchartdata);

foreach( $newchartdata as $c =>$cv){
//echo $c."=>".print_r($cv,true)."<br/>";

         $DataSet->addPoints($cv,$c);
        $n=$n+1;

     }
     $newcategoryarr=    array_unique($newcategoryarr);
//   print_r($newcategoryarr);
        $DataSet->addPoints($newcategoryarr,"xaxis");   

        $DataSet->setSerieDescription('xaxis',$xlabel);
        $DataSet->setAbscissa('xaxis');
//print_r($newchartdata);echo "ASDSAD";die;  
}
         */
  $DataSet->setAxisName(0,$ylabel.$devidelabel);
  
  if($i==0)
      return 0;
      
        return  $DataSet;

}

public function drawChartFramework($w=0,$h=0,$legendpos=0,$type='',$data=[])
{

    $titlesetting=$data['charttitle'];
    $subtitlesetting=$data['chartsubtitle'];
    $legendsetting=$data['chartlegend'];

    $titlecolor=$this->hex_code_color($titlesetting['color']);
    $subtitlecolor=$this->hex_code_color($subtitlesetting['color']);

    $Title = str_replace(array('"',"'"),'',$titlesetting['text']);
    $subTitle = str_replace(array('"',"'"),'',$subtitlesetting['text']);



        
         //$this->chart->setFontProperties(array('FontName'=>$charttitlefontpath,'FontSize'=>7));

        //echo $h.",".$titlesetting['fontsize'];
        $titletextsetting=array('DrawBox'=>false,"Align"=>TEXT_ALIGN_TOPMIDDLE);
        switch($titlesetting['position']){
            case "Bottom":
                $titlew=$w/2;
                $titleh=$h-($titlesetting['fontsize']*1.3);
                $subtitlew=$w/2;
                
                break;
            case "Left":
                $titlew=0;
                $titleh=$h/2;
                
                $subtitleh=$h/2;
                
                $titletextsetting=array('DrawBox'=>false,"Align"=>TEXT_ALIGN_TOPMIDDLE,"Angle"=>90);
                
            break;
            case "Right":
                $titlew=$w;
                $titleh=$h/2;
                
                $subtitleh=$h/2;
                $titletextsetting=array('DrawBox'=>false,"Align"=>TEXT_ALIGN_TOPMIDDLE,"Angle"=>270);
                
                
            break;
            case "Top":
            default:
                $titlew=$w/2;
                $titleh=0;
                $subtitlew=$w/2;
                
                break;
            
        }
              //  $titletextsetting=array('DrawBox'=>false,"Align"=>TEXT_ALIGN_TOPMIDDLE);
                
        if($Title){
            $charttitlefontpath=$this->getTTFFontPath($titlesetting['fontname']);

            $this->chart->setFontProperties(array('FontName'=>$charttitlefontpath,'FontSize'=>$titlesetting['fontsize'],"R"=>$titlecolor['r'],"G"=>$titlecolor['g'],"B"=>$titlecolor['b'],'Align'=>TEXT_ALIGN_TOPMIDDLE,));

            $this->chart->drawText($titlew,$titleh,$Title,$titletextsetting);
        }

          switch($legendsetting['position']){
                 case "Top":
                     $legendmode=array("Style"=>LEGEND_BOX,"Mode"=>LEGEND_HORIZONTAL);
                     $lgsize=$this->chart->getLegendSize($legendmode);
                     $diffx=$w-$lgsize['Width'];
                     if($diffx>0)
                     $legendx=$diffx/2;
                     else
                     $legendx=0;

                     if($legendy<0)
                         $legendy=0;

                     if($Title==''){

                         $graphareay1=15;
                         $legendy=$graphareay1+5;
                        $graphareax1=40;
                         $graphareax2=$w-10 ;
                         $graphareay2=$h-$legentsetting['fontsize']-15;
                    }
                    else{
                        $graphareay1=30;
                        $legendy=$graphareay1+5;
                        $graphareax1=40;

                         $graphareax2=$w-10 ;
                         $graphareay2=$h-$legentsetting['fontsize']-15;

                    }
                     break;
                 case "Left":
                  //   $legendmode=array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_VERTICAL);
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
                 //    echo "ASDASD";
                 $legendmode=array("Style"=>LEGEND_BOX,"Mode"=>LEGEND_VERTICAL);
                     $lgsize=$this->chart->getLegendSize($legendmode);
                     $legendx=$w-$lgsize['Width']-10;
                     if($Title==''){
                        $legendy=10;
                         $graphareay1=5;
                        $graphareax1=50;
                         $graphareax2=$legendx-5 ;
                         $graphareay2=$h-30;
                    }
                    else{
                         $legendy=30;
                         $graphareay1=30;
                        $graphareax1=50;
                         $graphareax2=$legendx-5 ;
                         $graphareay2=$h-30;
                    }
                     break;
                 case "Bottom":
                     
                    $legendmode=array("Style"=>LEGEND_BOX,"Mode"=>LEGEND_HORIZONTAL);
                     $lgsize=$this->chart->getLegendSize($legendmode);
                     $diffx=$w-$lgsize['Width'];
                     if($diffx>0)
                     $legendx=$diffx/2;
                     else
                     $legendx=0;
                     $legendy=$h-$lgsize['Height']+$legentsetting['fontsize'];

                     if($legendy<0)$legendy=0;

                     if($Title==''){

                         $graphareay1=15;
                        $graphareax1=40;
                         $graphareax2=$w-10 ;
                         $graphareay2=$legendy-$legentsetting['fontsize']-15;
                    }
                    else{
                        $graphareay1=30;
                        $graphareax1=40;
                         $graphareax2=$w-10 ;
                         $graphareay2=$legendy-$legentsetting['fontsize']-15;
                    }
                     break;
                 default:
                     
                  $legendmode=array("Style"=>LEGEND_BOX,"Mode"=>LEGEND_VERTICAL);
                     $lgsize=$this->chart->getLegendSize($legendmode);
                     $legendx=$w-$lgsize['Width'];
                     if($Title==''){
                        $legendy=10;
                         $graphareay1=5;
                        $graphareax1=50;
                         $graphareax2=$legendx-5 ;
                         $graphareay2=$h-30;
                    }
                    else{
                         $legendy=30;
                         $graphareay1=30;
                        $graphareax1=50;
                         $graphareax2=$legendx-5 ;
                         $graphareay2=$h-30;
                    }
                  
                     break;

             }
             
             

        

       $chartlegendfontpath=$this->getTTFFontPath($legendsetting['fontname']);
       if($legendsetting['fontsize']=='')
       $legendsetting['fontsize']=10;
            $legendcolor=$this->hex_code_color($legendsetting['color']);
            $legendBGcolor=$this->hex_code_color($legendsetting['backgroundColor']);
            $this->chart->setFontProperties(array('FontName'=>$chartlegendfontpath,'FontSize'=>$legendsetting['fontsize'],"R"=>$legendcolor['r'],"G"=>$legendcolor['g'],"B"=>$legendcolor['b'],'Align'=>TEXT_ALIGN_TOPMIDDLE));        
            $legendmode["R"]=$legendBGcolor['r'];
            $legendmode["G"]=$legendBGcolor['g'];
            $legendmode["B"]=$legendBGcolor['b'];
        
        if($type!='pieChart') {
        $this->chart->setGraphArea($graphareax1,$graphareay1,$graphareax2,$graphareay2);
        
        if($legendsetting){
            
            $this->chart->drawLegend($legendx,$legendy,$legendmode);
        }
        }else{
                $this->pieChart->drawPieLegend($legendx,$legendy,$legendmode);
        }
        
        
       
        switch($titlesetting['position']){
            case "Bottom":
               if($legendsetting['position']=='Bottom'){
                $subtitleh=$h-$titlesetting['fontsize']*1.3-$lgsize['Height']*1.3;//top
                $graphareay1=$subtitlesetting['fontsize']*1.3;
               }
               else{
                   $subtitleh=$h-$titlesetting['fontsize']*1.3;
                   $graphareay1=$subtitlesetting['fontsize']*1.3;
               }
                

               break;
            case "Left":
                if($legendsetting['position']=='Left'){
                        $subtitlew=$graphareax1+$lgsize['Width'];//left
                        $graphareax1=$subtitlew+$subtitlesetting['fontsize'];
                }
                else{
                    $subtitlew=$graphareax1;
                    $graphareax1=$subtitlew+$subtitlesetting['fontsize'];
                    
                    //$graphareax1+=$subtitlesetting['fontsize'];
                }
                
                
            break;
            case "Right":
                     if($legendsetting['position']=='Right'){
                        $subtitlew=$graphareax2-$lgsize['Width']-$subtitlesetting['fontsize']*1.3;//left
                        //$graphareax2=$subtitlew
                }
                else{
                    $subtitlew=$graphareax2;
                    $graphareax2=$subtitlew+$subtitlesetting['fontsize'];
                    
                    //$graphareax1+=$subtitlesetting['fontsize'];
                }
                
            break;
            case "Top":
            default:
               if($legendsetting['position']=='Top'){
                $subtitleh=$titlesetting['fontsize']*1.3+$lgsize['Height']*1.3;//top
                $graphareay1+=$subtitlesetting['fontsize']*1.3+$lgsize['Height']*1.3;
               }
               else{
                   $subtitleh=$titlesetting['fontsize']*1.3;
                   $graphareay1+=$subtitlesetting['fontsize']*1.3;
               }
                
                break;
            
        }
        
        if($subTitle){
            
            $chartsubtitlefontpath=$this->getTTFFontPath($subtitlesetting['fontname']);
            $this->chart->setFontProperties(array('FontName'=>$chartsubtitlefontpath,'FontSize'=>$subtitlesetting['fontsize'],"R"=>$subtitlecolor['r'],"G"=>$subtitlecolor['g'],"B"=>$subtitlecolor['b'],'Align'=>TEXT_ALIGN_TOPMIDDLE,));
            $this->chart->drawText($subtitlew,$subtitleh,$subTitle,$titletextsetting);
        }
        
        //echo "ASDSAD";die;
        
        return array(  $graphareax1, $graphareay1,  $graphareax2, $graphareay2);
}



    public function showBarChart($data=[],$y_axis=0,$type='barChart')
    {
          global $tmpchartfolder,$pchartfolder;
              include_once("$pchartfolder/class/pData.class.php");
        if($pchartfolder=="")
            $pchartfolder=dirname(__FILE__)."/pchart2";
            include_once("$pchartfolder/class/pDraw.class.php");
      if($type=='pieChart')
                include_once("$pchartfolder/class/pPie.class.php");

            include_once("$pchartfolder/class/pImage.class.php");
        if($tmpchartfolder=="")
             $tmpchartfolder=$pchartfolder."/cache";

        if(!is_writable($tmpchartfolder)){
            echo "$tmpchartfolder is not writable for generate chart, please contact software developer or system adminstrator";
            die;        
        }
            
         $w=$data['width']*$this->chartscaling;
         $h=$data['height']*$this->chartscaling;
         $legendpos=$data['chartLegendPos'];
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
    if($type=='pieChart')
    $DataSet=$this->fetchPieChartDataSet($catexp,$seriesexp,$valueexp,$labelexp, $xlabel,$ylabel,$data);
    else
     $DataSet=$this->fetchChartDataSet($catexp,$seriesexp,$valueexp,$labelexp, $xlabel,$ylabel,$data);
     
        $this->chart = new pImage($w,$h,$DataSet);
        
        //$c = new pChart($w,$h);
        //$this->setChartColor();
      if($type=='pieChart')
            $this->pieChart = new pPie($this->chart,$DataSet);

        $arrarea=$this->drawChartFramework($w,$h,$legendpos,$type,$data);
        $graphareax1=$arrarea[0];
        $graphareay1=$arrarea[1];
        $graphareax2=$arrarea[2];
        $graphareay2=$arrarea[3];

            $valueAxisFormat=$data['valueAxisFormat'];

            $categoryAxisFormat=$data['categoryAxisFormat'];
            $valuexaxislinecolor=$this->hex_code_color($valueAxisFormat['linecolor']);
            $valuexaxislabelcolor=$this->hex_code_color($valueAxisFormat['tickLabelFont']);
            
            $valuexaxisfontsize=$valueAxisFormat['size'];
            if($valuexaxisfontsize=='')
            $valuexaxisfontsize=10;
            $valuexaxisfontname=$valueAxisFormat['fontname'];

            $valuexaxisfontpath=$this->getTTFFontPath($valuexaxisfontname);
            $this->chart->setFontProperties(array('FontName'=>$valuexaxisfontpath,'FontSize'=>$valuexaxisfontsize,"R"=>$valuexaxislabelcolor['r'],"G"=>$valuexaxislabelcolor['g'],"B"=>$valuexaxislabelcolor['b'])) ;
     

            $scalesetting=array("GridR"=>200, "GridG"=>200,"GridB"=>200,//'ScaleSpacing'=>100,
            'AxisR'=>$valuexaxislinecolor['r'],'AxisG'=>$valuexaxislinecolor['g'],'AxisB'=>$valuexaxislinecolor['b'],

            'TickR'=>255,'TickG'=>0,'TickB'=>0,"DrawSubTicks"=>TRUE, "CycleBackground"=>TRUE);
                            
        if($type=='stackedBarChart')
          $scalesetting['Mode']=  SCALE_MODE_ADDALL;
        else
             $scalesetting['Mode']=  SCALE_MODE_FLOATING;
             
        if($type!='pieChart')           
            $this->chart->drawScale($scalesetting);

        
        
        $chartfontpath= $this->getTTFFontPath('Times New Roman');

        $this->chart->setFontProperties(array('FontName'=>$chartfontpath,'FontSize'=>7));


        if($type=='stackedBarChart')
            $this->chart->drawStackedBarChart();
        elseif($type=='barChart')
            $this->chart->drawBarChart();
        elseif($type=='lineChart')
         $this->chart->drawLineChart();
        elseif($type=='pieChart'){


            $this->pieChart->draw2DPie(($w/2),($h/2+10),array("Border"=>TRUE,"Radius"=>($h/2-20)));

    // $this->chart->draw2DPie();
         }
     
       $randomchartno=rand();
          $photofile="$tmpchartfolder/chart$randomchartno.png";

                 $this->chart->Render($photofile);

                 if(file_exists($photofile)){
                    
                    $this->pdf->Image($photofile,$x+$this->arrayPageSetting["leftMargin"],$y_axis+$y1,$w/$this->chartscaling,$h/$this->chartscaling,"PNG");
                   unlink($photofile);
                 }


    }

    public function dbQuery($sql='')
    {
        if($this->cndriver=="mysql" || $this->cndriver=="mysqli")
        {

            $a=$this->myconn->query("set names 'utf8'");
            
            $q=$this->myconn->query($sql);

            return $q;
         }
        elseif($this->cndriver=="psql")
        {
            pg_send_query($this->myconn,$sql);
            return pg_get_result($this->myconn);
        }
        elseif($this->cndriver=="sqlsrv")
        {
            return @sqlsrv_query( $this->myconn,$sql);
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
        elseif($this->cndriver=="psql")
        {
           return pg_fetch_array($query,NULL,PGSQL_ASSOC);
        
        }
        elseif($this->cndriver=="sqlsrv")
        {
            return sqlsrv_fetch_array($query,SQLSRV_FETCH_ASSOC);
        }
        else
        {                
            $stmt= $query->fetch(PDO::FETCH_ASSOC);        
            return $stmt;
        }
    }

public function showPieChart($data=[],$y_axis=0){
      global $tmpchartfolder,$pchartfolder;


    if($pchartfolder=="")
        $pchartfolder=dirname(__FILE__)."/pchart2";
//echo "$pchartfolder/class/pData.class.php";die;

        include_once("$pchartfolder/class/pData.class.php");
        include_once("$pchartfolder/class/pDraw.class.php");
        include_once("$pchartfolder/class/pPie.class.php");
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




public function showAreaChart($data=[],$y_axis=0,$type=''){
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
            $DataSet->setAxisName(0,$ylabel);




    $this->chart = new pImage($w,$h,$DataSet);
    //$c = new pChart($w,$h);
    //$this->setChartColor();
    $this->chart->drawRectangle(1,1,$w-2,$h-2);
    $legendfontsize=8;
    $this->chart->setFontProperties(array('FontName'=>$chartfontpath,'FontSize'=>$legendfontsize));


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
    

    $this->chart->setGraphArea($graphareax1,$graphareay1,$graphareax2,$graphareay2);
    $this->chart->setFontProperties(array('FontName'=>$chartfontpath,'FontSize'=>8));



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

      $this->chart->setFontProperties(array('FontName'=>$chartfontpath,'FontSize'=>7));

$this->chart->drawStackedAreaChart(array("Surrounding"=>60));


   $randomchartno=rand();
      $photofile="$tmpchartfolder/chart$randomchartno.png";

             $this->chart->Render($photofile);

             if(file_exists($photofile)){
                $this->pdf->Image($photofile,$x+$this->arrayPageSetting["leftMargin"],$y_axis+$y1,$w,$h,"PNG");
                unlink($photofile);
             }

}




private function changeSubDataSetSql($sql=''){

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
    

    public function printlongtext($fontfamily='freeserif',$fontstyle='',$fontsize=12){
                    //$this->gotTextOverPage=false;
                        $this->columnFooter();
                        $this->pageFooter();
                        $this->pageHeader();
                                                $this->columnHeader();
                    $this->hideheader==true;
                    
                    $this->currentband='detail';  
                      $fontfile=$this->fontdir.'/'.$fontfamily.'.php';
                                        
             if(file_exists($fontfile) || $this->bypassnofont==false){
               $fontfile=$this->fontdir.'/'.$arraydata["font"].'.php';
                $this->pdf->SetFont($fontfamily,$fontstyle,$fontsize,$fontfile);
           }
           else{
             $fontfamily="freeserif";
                                if($fontstyle=="")
                                    $this->pdf->SetFont('freeserif',$fontstyle,$fontsize,$this->fontdir.'/freeserif.php');
                                elseif($fontstyle=="B")
                                    $this->pdf->SetFont('freeserifb',$fontstyle,$fontsize,$this->fontdir.'/freeserifb.php');
                                elseif($fontstyle=="I")
                                    $this->pdf->SetFont('freeserifi',$fontstyle,$fontsize,$this->fontdir.'/freeserifi.php');
                                elseif($fontstyle=="BI")
                                    $this->pdf->SetFont('freeserifbi',$fontstyle,$fontsize,$this->fontdir.'/freeserifbi.php');
                                elseif($fontstyle=="BIU")
                                    $this->pdf->SetFont('freeserifbi',"BIU",$fontsize,$this->fontdir.'/freeserifbi.php');
                                elseif($fontstyle=="U")
                                    $this->pdf->SetFont('freeserif',"U",$fontsize,$this->fontdir.'/freeserif.php');
                                elseif($fontstyle=="BU")
                                    $this->pdf->SetFont('freeserifb',"U",$fontsize,$this->fontdir.'/freeserifb.php');
                                elseif($fontstyle=="IU")
                                    $this->pdf->SetFont('freeserifi',"IU",$fontsize,$this->fontdir.'/freeserifbi.php');
                    
                
            }
                                        
                    //$this->pdf->SetFont($fontfamily,$fontstyle,$fontsize,$this->fontdir.'/'.$fontfamily.'php');
                                        
                    $this->pdf->SetTextColor($this->forcetextcolor_r,$this->forcetextcolor_g,$this->forcetextcolor_b);
                    //$this->pdf->SetTextColor(44,123,4);
                    $this->pdf->SetFillColor($this->forcefillcolor_r,$this->forcefillcolor_g,$this->forcefillcolor_b);

                    $bltxt=$this->continuenextpageText; 
                                     //       print_r($this->continuenextpageText);
                                            
                                       //                         echo "longtext:$fontfamily,$fontstyle,$fontsize<br/><br/>";
                    $this->pdf->SetY($this->arraypageHeader[0]["height"]+$this->columnheaderbandheight+$this->arrayPageSetting["topMargin"]);
                    $this->pdf->SetX($bltxt['x']);
                    $maxheight=$this->arrayPageSetting["pageHeight"]-$this->arraypageFooter[0]["height"]-$this->pdf->GetY()-$bltxt['height'];

                                                        $this->pdf->MultiCell($bltxt['width'],$bltxt['height'],$bltxt['txt'],
                                $bltxt['border'],
                                $bltxt['align'],$bltxt['fill'],$bltxt['ln'],'','',$bltxt['reset'],
                                $bltxt['streth'],$bltxt['ishtml'],$bltxt['autopadding'],$maxheight-$bltxt['height'],$bltxt['valign']);
                            
                       if($this->pdf->balancetext!=''){
                            $this->continuenextpageText=array('width'=>$bltxt["width"], 'height'=>$bltxt["height"], 
                                'txt'=>$this->pdf->balancetext, 'border'=>$bltxt["border"] ,'align'=>$bltxt["align"], 'fill'=>$bltxt["fill"],'ln'=>1,
                                        'x'=>$bltxt['x'],'y'=>'','reset'=>true,'streth'=>0,'ishtml'=>false,'autopadding'=>true,'valign'=>$bltxt['valign']);
                                $this->pdf->balancetext='';
                                                            
                                $this->printlongtext($fontfamily,$fontstyle,$fontsize);
                                                                
                      }
                    //echo $this->currentband;  
                if( $this->pdf->balancetext=='' && $this->currentband=='detail'){
                    if($this->maxpagey['page_'.($this->pdf->getPage()-1)]=='')
                        $this->maxpagey['page_'.($this->pdf->getPage()-1)]=$this->pdf->GetY();
                    else{
                        if($this->maxpagey['page_'.($this->pdf->getPage()-1)]<$this->pdf->GetY())
                                $this->maxpagey['page_'.($this->pdf->getPage()-1)]=$this->pdf->GetY();
                    }
                    
                }
        }
        
        
    public function detail() {
        $currentpage= $this->pdf->getNumPages();
        $this->maxpagey=array();
        $this->currentband='detail';

        $this->arraydetail[0][0]["y_axis"]=$this->arraydetail[0]["y_axis"];//- $this->titleheight;
        $field_pos_y=$this->arraydetail[0][0]["y_axis"];
        $biggestY=0;
        $tempY=$this->arraydetail[0][0]["y_axis"];
        
       if(isset($this->SubReportCheckPoint))
        $checkpoint=$this->SubReportCheckPoint;


             $colheader=$this->columnHeader($this->arrayPageSetting["topMargin"]+$this->titlebandheight+$this->arraypageHeader[0]["height"]);           
        //if($this->pdf->getPage()>1)
             
                if($this->pdf->getPage()>1){
                    $checkpoint=$this->arrayPageSetting["topMargin"]+$this->titlebandheight+
                                $this->arraypageHeader[0]["height"]+$this->columnheaderbandheight;
                    
                  //  for($i=0;$i<$this->totalgroup;$i++){
                    $this->groupnochange=0;
                     $checkpoint= $this->showGroupHeader($checkpoint,false);
                    //}
                }
                else{
                    
                    $checkpoint=$this->arrayPageSetting["topMargin"]+$this->orititlebandheight+$this->arraypageHeader[0]["height"]+
                                $this->columnheaderbandheight;
                  //  for($i=0;$i<$this->totalgroup;$i++){
                     $checkpoint=$this->showGroupHeader( $checkpoint,false);
                   // }
                }
                
            
            if($this->pdf->getPage()>1)
               $this->titlebandheight=0;
            
        $isgroupfooterprinted=false;
                
            if($this->titlewithpagebreak==false)
        $this->maxpagey=array('page_0'=>$checkpoint);
            else
                $this->maxpagey=array('page_1'=>$checkpoint);
        $rownum=0; 
        
        if($this->arraysqltable) {
            
        $n=0;
            foreach($this->arraysqltable as $row) {
           
   
                   
    
                                $n++;
                                $this->report_count++;
                $currentpage= $this->pdf->getNumPages();
                $this->pdf->lastPage();
                $this->hideheader==false;
                if($n>1)
                    $checkpoint=$this->maxpagey['page_'.($this->pdf->getNumPages()-1)];

        $pageheight=$this->arrayPageSetting["pageHeight"];
        $footerheight=$this->footerbandheight;
        $headerheight=$this->headerbandheight;
        $bottommargin=$this->arrayPageSetting["bottomMargin"];
        
    
        //print_r( $this->arrayVariable);
//echo $g["name"]."<br/>";
            if($this->checkSwitchGroup("header") ){     
                      
                                $checkpoint=$this->showGroupHeader($checkpoint,true);
                                
                                //echo "Switch Group: $checkpoint<br/>";
                            $currentpage= $this->pdf->getNumPages();
                $this->maxpagey[($this->pdf->getPage()-1)]=$checkpoint;
                              
                    $this->pdf->SetY($checkpoint); 
        
                }
                                $this->group_count[$this->grouplist[0]["name"]]++;
                                $this->group_count[$this->grouplist[1]["name"]]++;
                                $this->group_count[$this->grouplist[2]["name"]]++;
                                $this->group_count[$this->grouplist[3]["name"]]++;
        if(isset($this->arrayVariable)) //if self define variable existing, go to do the calculation
                                    $this->variable_calculation($rownum, $this->arraysqltable[$this->global_pointer][$this->group_pointer]);

//begin page handling
            for($d=0;$d<$this->detailbandqty;$d++){
                $detailheight=$this->detailbandheight[$d];
                $this->pdf->setPage($this->pdf->getNumPages());
                $currentpage= $this->pdf->getNumPages();
              //  echo $this->pdf->getPage().",".$this->pdf->getNumPages().",";
                //echo "Row:$this->report_count checkpoint:$checkpoint detail height:$detailheight> allow till:$this->detailallowtill,page:".$this->pdf->getNumPages()."<br/>";
                
//                echo "Row:$this->report_count :
//                    if(($checkpoint +$detailheight >$this->detailallowtill) && ({$this->pdf->getPage()}>1) || <br/>
//                        ($checkpoint +$detailheight >$this->detailallowtill-$this->orititleheight) && ({$this->pdf->getNumPages()}==1)<br/><br/>
//                    ";
                     if(($checkpoint +$detailheight >$this->detailallowtill) && ($this->pdf->getPage()>1) ||
                        ($checkpoint +$detailheight >$this->detailallowtill-$this->orititleheight) && ($this->pdf->getNumPages()==1) 
                             ){
                    
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

                
                foreach ($this->arraydetail[$d] as $out) {
                    $this->currentrow=$this->arraysqltable[$this->global_pointer];
              
//                      echo $out["hidden_type"]."<br/>";
                    switch ($out["hidden_type"]) {
                        case "field":
                     //        $txt=$this->analyse_expression($compare["txt"]);
                       //  $out["txt"].":".print_r($out,true)."<br/>";
                 $maxheight=$this->detailallowtill-$checkpoint;//$this->arrayPageSetting["pageHeight"]-$this->arraypageFooter[0]["height"]-$this->pdf->GetY()+2-$this->columnheaderbandheight-$this->columnfooterbandheight;
                            $this->prepare_print_array=array("type"=>"MultiCell","width"=>$out["width"],"height"=>$out["height"],"txt"=>$out["txt"],
                                    "border"=>$out["border"],"align"=>$out["align"],"fill"=>$out["fill"],"hidden_type"=>$out["hidden_type"],
                                    "printWhenExpression"=>$out["printWhenExpression"],"soverflow"=>$out["soverflow"],"poverflow"=>$out["poverflow"],"link"=>$out["link"],
                                    "pattern"=>$out["pattern"],"writeHTML"=>$out["writeHTML"],"isPrintRepeatedValues"=>$out["isPrintRepeatedValues"],"valign"=>$out["valign"],
                                                                  "uuid"=>$out["uuid"],  "linktarget"=>$out['linktarget']);
                            $this->display($this->prepare_print_array,0,true,$maxheight);
                            
              //                                  $checkpoint=$this->arraydetail[0]["y_axis"];

                            break;
                        case "relativebottomline":
                        //$this->relativebottomline($out,$tempY);
                            $this->relativebottomline($out,$biggestY);
                            break;
                          case "subreport":
                            $checkpoint=$this->display($out,$checkpoint);
                              //$this->arraydetail[0]["y_axis"]=$checkpoint;
                              //$biggestY=$checkpoint;
                              if($this->maxpagey['page_'.($this->pdf->getNumPages()-1)]<$checkpoint)
                              $this->maxpagey['page_'.($this->pdf->getNumPages()-1)]=$checkpoint;
                         break;
                        default:
                            //echo $out["hidden_type"]."=".print_r($out,true)."<br/><br/>";
                            $this->display($out,$checkpoint);
               $maxheight=$this->detailallowtill-$checkpoint;

                            //$checkpoint=$this->pdf->GetY();
                            break;
                    }
                    
                    if($this->pdf->getNumPages()>1){
                       
                    $this->pdf->setPage($currentpage);
                    
                    }

                }
                //$this->pdf->lastPage();
                $checkpoint=$this->maxpagey['page_'.($this->pdf->getNumPages()-1)];

                
            }//end loop detail band[]
             $this->pdf->setPage($this->pdf->getNumPages());
                
                
                
                
                $this->global_pointer++;
                   $rownum++;           
                  $headerY=$checkpoint;      
            
            
            }
        
        
                    $this->global_pointer--;
        }else {
            if($this->blockdisplaynodata != 1)
            {
                echo "No data found";
                exit(0);
            } 
        }
 
 
            

      
                  
  if($this->totalgroup>0){
        $totalgroupheight=0;
  
        $this->report_count++;
  $this->global_pointer++;      
            $checkpoint=$this->showGroupFooter($totalgroupheight+$this->maxpagey['page_'.($this->pdf->getNumPages()-1)]);
              $totalgroupheight+=$this->grouplist[$i]["groupfootheight"];
  
  }

  $this->totalrowcount=$this->report_count-1;
                  $this->summary($checkpoint);
             

 
            
    }


    public function showGroupHeader($y=0,$printgroupfooter=false) {
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


    public function display($arraydata=[],$y_axis=0,$fielddata=false,$maxheight=0) {
        $this->currentuuid=$arraydata["uuid"];
    $this->Rotate($arraydata["rotation"]);
    
    if($arraydata["rotation"]!=""){
            if($arraydata["rotation"]=="Left"){
                 $w=$arraydata["width"];
                $arraydata["width"]=$arraydata["height"];
                $arraydata["height"]=$w;
                    $this->pdf->SetXY($this->pdf->GetX()-$arraydata["width"],$this->pdf->GetY());
            }
            elseif($arraydata["rotation"]=="Right"){
                 $w=$arraydata["width"];
                $arraydata["width"]=$arraydata["height"];
                $arraydata["height"]=$w;
                    $this->pdf->SetXY($this->pdf->GetX(),$this->pdf->GetY()-$arraydata["height"]);
            }
            elseif($arraydata["rotation"]=="UpsideDown"){
                //soverflow"=>$stretchoverflow,"poverflow"
                $arraydata["soverflow"]=true;
                $arraydata["poverflow"]=true;
               //   $w=$arraydata["width"];
               // $arraydata["width"]=$arraydata["height"];
                //$arraydata["height"]=$w;
                $this->pdf->SetXY($this->pdf->GetX()- $arraydata["width"],$this->pdf->GetY()-$arraydata["height"]);
            }
    }
    if($arraydata["type"]=="SetFont") {
        //echo $arraydata["font"]."<br/>";
                       $arraydata["font"]=  strtolower(str_replace(' ', '', $arraydata["font"]));

                        if($arraydata["fontstyle"]=="BI")
                            $fontfile=$this->fontdir.'/'.$arraydata["font"].'bi.php';
                        elseif($arraydata["fontstyle"]=="I")
                            $fontfile=$this->fontdir.'/'.$arraydata["font"].'i.php';
                        elseif($arraydata["fontstyle"]=="B")
                            $fontfile=$this->fontdir.'/'.$arraydata["font"].'b.php';
                        else
                             $fontfile=$this->fontdir.'/'.$arraydata["font"].'.php';
            //echo $fontfile." : ";
            if(!file_exists($fontfile))
                $fontfile=$this->fontdir.'/'.$arraydata["font"].'.php';
            //echo $fontfile."<br/>";
          if(file_exists($fontfile) || $this->bypassnofont==false){

                $this->pdf->SetFont($arraydata["font"],$arraydata["fontstyle"],$arraydata["fontsize"],$fontfile);
           }
           else{
                $arraydata["font"]="freeserif";
                                if($arraydata["fontstyle"]=="")
                                    $this->pdf->SetFont('freeserif',$arraydata["fontstyle"],$arraydata["fontsize"],$this->fontdir.'/freeserif.php');
                                elseif($arraydata["fontstyle"]=="B")
                                    $this->pdf->SetFont('freeserifb',$arraydata["fontstyle"],$arraydata["fontsize"],$this->fontdir.'/freeserifb.php');
                                elseif($arraydata["fontstyle"]=="I")
                                    $this->pdf->SetFont('freeserifi',$arraydata["fontstyle"],$arraydata["fontsize"],$this->fontdir.'/freeserifi.php');
                                elseif($arraydata["fontstyle"]=="BI")
                                    $this->pdf->SetFont('freeserifbi',$arraydata["fontstyle"],$arraydata["fontsize"],$this->fontdir.'/freeserifbi.php');
                                elseif($arraydata["fontstyle"]=="BIU")
                                    $this->pdf->SetFont('freeserifbi',"BIU",$arraydata["fontsize"],$this->fontdir.'/freeserifbi.php');
                                elseif($arraydata["fontstyle"]=="U")
                                    $this->pdf->SetFont('freeserif',"U",$arraydata["fontsize"],$this->fontdir.'/freeserif.php');
                                elseif($arraydata["fontstyle"]=="BU")
                                    $this->pdf->SetFont('freeserifb',"U",$arraydata["fontsize"],$this->fontdir.'/freeserifb.php');
                                elseif($arraydata["fontstyle"]=="IU")
                                    $this->pdf->SetFont('freeserifi',"IU",$arraydata["fontsize"],$this->fontdir.'/freeserifbi.php');
                    
                
            }

        }
        elseif($arraydata["type"]=="subreport") {   
        

            return $this->runSubReport($arraydata,$y_axis);

        }
        elseif($arraydata["type"]=="MultiCell") {
          
           // echo $arraydata["txt"].':'. $this->currenttextfield."<br>"; 
//echo " $this->report_count $this->currenttextfield".print_r($arraydata,true)."<br/><br/>";
            if($fielddata==false) {
                $this->checkoverflow($arraydata,$this->updatePageNo($arraydata["txt"]),'',$maxheight);
            }
            elseif($fielddata==true) {
            
                 $res=$this->analyse_expression($arraydata["txt"],$arraydata["isPrintRepeatedValues"]);

                $this->checkoverflow($arraydata,$this->updatePageNo($res),$maxheight);
            }
            

        }
        elseif($arraydata["type"]=="SetXY") {
            $this->pdf->SetXY($arraydata["x"]+$this->arrayPageSetting["leftMargin"],$arraydata["y"]+$y_axis);
        }
        elseif($arraydata["type"]=="Cell") {
//                print_r($arraydata);
  //              echo "<br/>";

            $this->pdf->Cell($arraydata["width"],$arraydata["height"],$this->updatePageNo($arraydata["txt"]),$arraydata["border"],$arraydata["ln"],
                       $arraydata["align"],$arraydata["fill"],$arraydata["link"]."",0,true,"T",$arraydata["valign"]);


        }
        elseif($arraydata["type"]=="Rect"){
        if($arraydata['mode']=='Transparent')
        $style='';
        else
        $style='FD';
          //      $this->pdf->SetLineStyle($arraydata['border']);
            $this->pdf->Rect($arraydata["x"]+$this->arrayPageSetting["leftMargin"],$arraydata["y"]+$y_axis,$arraydata["width"],$arraydata["height"],
            $style,$arraydata['border'],$arraydata['fillcolor']);
                }
        elseif($arraydata["type"]=="RoundedRect"){
            if($arraydata['mode']=='Transparent')
                $style='';
            else
            $style='FD';
            //
                //        $this->pdf->SetLineStyle($arraydata['border']);
                        
                          if($arraydata['printWhenExpression']==""  ||  $this->print_expression($arraydata)){
                              foreach($arraydata['border'] as $bs=>$ba){
                                foreach($ba as $bbc)
                                     $this->pdf->SetLineStyle($bbc) ;
                                  
                              }

             $this->pdf->RoundedRect($arraydata["x"]+$this->arrayPageSetting["leftMargin"], $arraydata["y"]+$y_axis, 
                                 $arraydata["width"], $arraydata["height"], $arraydata["radius"], '1111', 
            $style, array(),$arraydata['fillcolor']);
                          }
            }
        elseif($arraydata["type"]=="Ellipse"){
            //$this->pdf->SetLineStyle($arraydata['border']);
            if(isset($arraydata['printWhenExpression']) && ($arraydata['printWhenExpression']=='' || $this->analyse_expression($arraydata['printWhenExpression'])))
            {
                $this->pdf->Ellipse($arraydata["x"]+$arraydata["width"]/2+$this->arrayPageSetting["leftMargin"], $arraydata["y"]+$y_axis+$arraydata["height"]/2, $arraydata["width"]/2,$arraydata["height"]/2,0,0,360,'FD',$arraydata['border'],$arraydata['fillcolor']);
            }
        }
        else if($arraydata["type"]=="Image")
        {
            $path = $this->analyse_expression($arraydata["path"], "true", $arraydata["type"]);
            $imgtype=substr($path,-3);
            $arraydata["link"]=$arraydata["link"]."";
            
            $arraydata["link"]=$this->analyse_expression($arraydata["link"]);
            
            
            if($imgtype=='jpg' || right($path,3)=='jpg' || right($path,4)=='jpeg')
                 $imgtype="JPEG";
            elseif($imgtype=='png'|| $imgtype=='PNG')
                  $imgtype="PNG";
          //echo $path;
        if(file_exists($path) || $this->left($path,4)=='http' ){  
                    $this->pdf->Image($path,$arraydata["x"]+$this->arrayPageSetting["leftMargin"],$arraydata["y"]+$y_axis,
                          $arraydata["width"],$arraydata["height"],$imgtype,$arraydata["link"]);                        
        }
        elseif($this->left($path,22)==  "data:image/jpeg;base64"){
            $imgtype="JPEG";
            $img=  str_replace('data:image/jpeg;base64,', '', $path);
            $imgdata = base64_decode($img);

            $sizedata = $this->setDisplayImageSize($arraydata, $imgdata);
            if(array_key_exists("scale_type", $arraydata))
            {
                if($arraydata["scale_type"] == "Clip")
                {
                    $realImage = imagecreatefromstring($imgdata);
                    $cropImage = imagecrop($realImage, ['x' => 0, 'y' => 0, 'width' => $sizedata["width"], 'height' => $sizedata['height']]);
                    ob_start();
                    imagepng($cropImage);
                    $imgdata = ob_get_contents();

                    ob_end_clean();
                }
            }
            $this->pdf->Image('@'.$imgdata,$arraydata["x"]+$this->arrayPageSetting["leftMargin"],$arraydata["y"]+$y_axis,
                $sizedata["width"],$sizedata["height"],'',$arraydata["link"]); 
            
        }
        elseif($this->left($path,22)==  "data:image/png;base64,"){
                  $imgtype="PNG";
                 // $this->pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

                 $img= str_replace('data:image/png;base64,', '', $path);
                 $imgdata = base64_decode($img);

                 $sizedata = $this->setDisplayImageSize($arraydata, $imgdata);
                if(array_key_exists("scale_type", $arraydata))
                {
                    if($arraydata["scale_type"] == "Clip")
                    {
                        $realImage = imagecreatefromstring($imgdata);
                        $cropImage = imagecrop($realImage, ['x' => 0, 'y' => 0, 'width' => $sizedata["width"], 'height' => $sizedata['height']]);
 
                        ob_start();
                        imagepng($cropImage);
                        $imgdata = ob_get_contents();

                        ob_end_clean();
                    }
                }
            
                            
            $this->pdf->Image('@'.$imgdata,$arraydata["x"]+$this->arrayPageSetting["leftMargin"],$arraydata["y"]+$y_axis, 
                $sizedata["width"],$sizedata["height"],'',$arraydata["link"]); 
    
            
        }

        }

        elseif($arraydata["type"]=="SetTextColor") {
            $this->textcolor_r=$arraydata['r'];
            $this->textcolor_g=$arraydata['g'];
            $this->textcolor_b=$arraydata['b'];
            
            if($this->hideheader==true && $this->currentband=='pageHeader')
                $this->pdf->SetTextColor(100,33,30);
            else
                $this->pdf->SetTextColor($arraydata["r"],$arraydata["g"],$arraydata["b"]);
        }
        elseif($arraydata["type"]=="SetDrawColor") {
            $this->drawcolor_r=$arraydata['r'];
            $this->drawcolor_g=$arraydata['g'];
            $this->drawcolor_b=$arraydata['b'];
            $this->pdf->SetDrawColor($arraydata["r"],$arraydata["g"],$arraydata["b"]);
        }
        elseif($arraydata["type"]=="SetLineWidth") {
            $this->pdf->SetLineWidth($arraydata["width"]);
        }
        elseif($arraydata["type"]=="break"){
      
          
        }
        elseif($arraydata["type"]=="Line") {
            $printline=false;
            if($arraydata['printWhenExpression']=="")
                $printline=true;
            else
                $printline=$this->analyse_expression($arraydata['printWhenExpression']);
            if($printline)
            $this->pdf->Line($arraydata["x1"]+$this->arrayPageSetting["leftMargin"],$arraydata["y1"]+$y_axis,$arraydata["x2"]+$this->arrayPageSetting["leftMargin"],$arraydata["y2"]+$y_axis,$arraydata["style"]);
        }
        elseif($arraydata["type"]=="SetFillColor") {
            $this->fillcolor_r=$arraydata['r'];
            $this->fillcolor_g=$arraydata['g'];
            $this->fillcolor_b=$arraydata['b'];
            $this->pdf->SetFillColor($arraydata["r"],$arraydata["g"],$arraydata["b"]);
        }
      elseif($arraydata["type"]=="lineChart") {

          $this->showBarChart($arraydata, $y_axis,'lineChart');
        }
      elseif($arraydata["type"]=="barChart") {

            $this->showBarChart($arraydata, $y_axis,'barChart');
        }
      elseif($arraydata["type"]=="pieChart") {

            $this->showBarChart($arraydata, $y_axis,'pieChart');
        }
      elseif($arraydata["type"]=="stackedBarChart") {

            $this->showBarChart($arraydata, $y_axis,'stackedBarChart');
        }
      elseif($arraydata["type"]=="stackedAreaChart") {

            $this->showAreaChart($arraydata, $y_axis,$arraydata["type"]);
        }
        elseif($arraydata["type"]=="Barcode"){
            
            $this->showBarcode($arraydata, $y_axis);
        }
         elseif($arraydata["type"]=="CrossTab"){
            
            $this->showCrossTab($arraydata, $y_axis);
        }

             $this->currentuuid="";
    }

    
    
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
    
   
    
    public function showBarcode($data=[],$y=0){
        
        $type=  strtoupper($data['barcodetype']);
        $height=$data['height'];
        $width=$data['width'];
        $x=$data['x'];
        $y=$data['y']+$y;
        $textposition=$data['textposition'];
        $code=$data['code'];
        $code=$this->analyse_expression($code);
        $modulewidth=$data['modulewidth'];
        if($textposition=="" || $textposition=="none")
         $withtext = false;
        else
            $withtext = true;
        
     $style = array(
    'border' => false,
    'vpadding' => 'auto',
    'hpadding' => 'auto',
         'text'=>$withtext,
    'fgcolor' => array(0,0,0),
    'bgcolor' => false, //array(255,255,255)
    'module_width' => 1, // width of a single module in points
    'module_height' => 1 // height of a single module in points
);

        
//[2D barcode section]        
//DATAMATRIX
//QRCODE,H or Q or M or L (H=high level correction, L=low level correction)
// -------------------------------------------------------------------
// PDF417 (ISO/IEC 15438:2006)

/*

 The $type parameter can be simple 'PDF417' or 'PDF417' followed by a
 number of comma-separated options:

 'PDF417,a,e,t,s,f,o0,o1,o2,o3,o4,o5,o6'

 Possible options are:

     a  = aspect ratio (width/height);
     e  = error correction level (0-8);

     Macro Control Block options:

     t  = total number of macro segments;
     s  = macro segment index (0-99998);
     f  = file ID;
     o0 = File Name (text);
     o1 = Segment Count (numeric);
     o2 = Time Stamp (numeric);
     o3 = Sender (text);
     o4 = Addressee (text);
     o5 = File Size (numeric);
     o6 = Checksum (numeric).

 Parameters t, s and f are required for a Macro Control Block, all other parametrs are optional.
 To use a comma character ',' on text options, replace it with the character 255: "\xff".

*/ 
        switch($type){
          case "PDF417":
               $this->pdf->write2DBarcode($code, 'PDF417', $x, $y, $width, $height, $style, 'N');
              break;
          case "DATAMATRIX":
              
              //$this->pdf->Cell( $width,10,$code);
              //echo $this->left($code,3);
              if($this->left($code,3)=="QR:"){
                  
              $code=  $this->right($code,strlen($code)-3);
              
              $this->pdf->write2DBarcode($code, 'QRCODE', $x, $y, $width, $height, $style, 'N');
              }
              else
                  $this->pdf->write2DBarcode($code, 'DATAMATRIX', $x, $y, $width, $height, $style, 'N');
              break;
            case "CODE128":
                $this->pdf->write1DBarcode($code, 'C128',  $x, $y, $width, $height, 1, $style, 'N');

              // $this->pdf->write1DBarcode($code, 'C128', $x, $y, $width, $height,"", $style, 'N');
              break;
          case  "EAN8":
                 $this->pdf->write1DBarcode($code, 'EAN8', $x, $y, $width, $height, 1,$style, 'N');
              break;
          case  "EAN13":
                 $this->pdf->write1DBarcode($code, 'EAN13', $x, $y, $width, $height, 1,$style, 'N');
              break;
          case  "CODE39":
                 $this->pdf->write1DBarcode($code, 'C39', $x, $y, $width, $height, 1,$style, 'N');
              break;
           case  "CODE93":
                 $this->pdf->write1DBarcode($code, 'C93', $x, $y, $width, $height, 1,$style, 'N');
              break;
        }
        

    }
    public function relativebottomline($path=[],$y=0) {
        $extra=$y-$path["y1"];
        $this->display($path,$extra);
    }

    public function updatePageNo($s='') {
        return str_replace('$this->PageNo()', $this->pdf->PageNo(),$s);
    }

    public function staticText($xml_path=[]) {
//$this->pointer[]=array("type"=>"SetXY","x"=>$xml_path->reportElement["x"],"y"=>$xml_path->reportElement["y"]);
    }
    


    public function checkoverflow($arraydata=[],$txt="",$maxheight=0) {
    $newfont=    $this->recommendFont($txt, $arraydata["font"],$arraydata["pdfFontName"]);
    $this->pdf->SetFont($newfont,$this->pdf->getFontStyle(),$this->pdf->getFontSize());
        $this->print_expression($arraydata);
        
        if($this->print_expression_result==true) {
           // echo $arraydata["link"];
            if($arraydata["link"]) {
                //print_r($arraydata);
                
                //$this->debughyperlink=true;
              //  echo $arraydata["link"].",print:".$this->print_expression_result;
                $arraydata["link"]=$this->analyse_expression($arraydata["link"],"");
                //$this->debughyperlink=false;
            }
            //print_r($arraydata);
            
            
            if($arraydata["writeHTML"]==1 && $this->pdflib=="TCPDF") {
             // $this->pdf->writeHTML($txt);
                $this->pdf->writeHTML($txt, true, false, false, true);
                // $html, $ln=true, $fill=false, $reseth=false, $cell=false, $align=''
            $this->pdf->Ln();
                    if($this->currentband=='detail'){
                    if($this->maxpagey['page_'.($this->pdf->getPage()-1)]=='')
                        $this->maxpagey['page_'.($this->pdf->getPage()-1)]=$this->pdf->GetY();
                    else{
                        if($this->maxpagey['page_'.($this->pdf->getPage()-1)]<$this->pdf->GetY())
                            $this->maxpagey['page_'.($this->pdf->getPage()-1)]=$this->pdf->GetY();
                    }
                }
            
            }
            
            elseif($arraydata["poverflow"]=="false"&&$arraydata["soverflow"]=="false") {
                            if($arraydata["valign"]=="M")
                                    $arraydata["valign"]="C";
                                if($arraydata["valign"]=="")
                                    $arraydata["valign"]="T";                
                                
                while($this->pdf->GetStringWidth($txt) > $arraydata["width"]) {
                    if($txt!=$this->pdf->getAliasNbPages() && $txt!=' '.$this->pdf->getAliasNbPages())
                    $txt=substr_replace($txt,"",-1);
                }
                            

                $x=$this->pdf->GetX();
                $y=$this->pdf->GetY();
                foreach($this->arrayParameter as  $pv => $ap)
                {

                    if($arraydata["pattern"]=='$P{'.$pv.'}')
                        $arraydata["pattern"]=$ap;    
                }

                $text=$this->formatText($txt, $arraydata["pattern"]);
                $this->pdf->Cell($arraydata["width"], $arraydata["height"],$text,
                        $arraydata["border"],"",$arraydata["align"],$arraydata["fill"],
                        $arraydata["link"],
                        0,true,"T",$arraydata["valign"]);
                      
//                if($arraydata["link"]) { //
//                    $tmpalign="Left";
//                    if($arraydata["valign"]=="R")
//                        $tmpalign="Right";
//                    elseif($arraydata["valign"]=="C")
//                        $tmpalign="Center";
//                    $textlen=strlen($text);
//                    $hidetxt="";
//                    for($l=0;$l<$textlen*2;$l++)
//                    $hidetxt.="&nbsp;";
//                              $imagehtml='<a style="text-decoration: none;" href="'.$arraydata["link"].'">'.
//                                      '<div style="text-decoration: none;text-align:$tmpalign;float:left;width:'.$arraydata["width"].';margin:0px">'.$hidetxt.'</div></a>';
//                         //     $this->pdf->writeHTMLCell($arraydata["width"],$arraydata["height"], $x,$y-$arraydata["height"],$imagehtml);//,1,0,true);
//                }
//                
                
                $this->pdf->Ln();
                    if($this->currentband=='detail'){
                    if($this->maxpagey['page_'.($this->pdf->getPage()-1)]=='')
                        $this->maxpagey['page_'.($this->pdf->getPage()-1)]=$this->pdf->GetY();
                    else{
                        if($this->maxpagey['page_'.($this->pdf->getPage()-1)]<$this->pdf->GetY())
                            $this->maxpagey['page_'.($this->pdf->getPage()-1)]=$this->pdf->GetY();
                    }
                }
        
            }
             elseif($arraydata["soverflow"]=="true") {
                if($arraydata["valign"]=="C")
                                    $arraydata["valign"]="M";
                                if($arraydata["valign"]=="")
                                    $arraydata["valign"]="T";
                                
                $x=$this->pdf->GetX();
                $y=$this->pdf->GetY();
                             //if($arraydata["link"])   echo $arraydata["linktarget"].",".$arraydata["link"]."<br/><br/>";
                $this->pdf->MultiCell($arraydata["width"], $arraydata["height"], $this->formatText($txt, $arraydata["pattern"]),$arraydata["border"] 
                                ,$arraydata["align"], $arraydata["fill"],1,'','',true,0,false,true,$maxheight);//,$arraydata["valign"]);
        
                if( $this->pdf->balancetext=='' && $this->currentband=='detail'){
                    if($this->maxpagey['page_'.($this->pdf->getPage()-1)]=='')
                        $this->maxpagey['page_'.($this->pdf->getPage()-1)]=$this->pdf->GetY();
                    else{
                        if($this->maxpagey['page_'.($this->pdf->getPage()-1)]<$this->pdf->GetY())
                            $this->maxpagey['page_'.($this->pdf->getPage()-1)]=$this->pdf->GetY();
                    }
                }
                
            //$this->pageFooter();
            if($this->pdf->balancetext!='' ){
                $this->continuenextpageText=array('width'=>$arraydata["width"], 'height'=>$arraydata["height"], 'txt'=>$this->pdf->balancetext,
                        'border'=>$arraydata["border"] ,'align'=>$arraydata["align"], 'fill'=>$arraydata["fill"],'ln'=>1,
                            'x'=>$x,'y'=>'','reset'=>true,'streth'=>0,'ishtml'=>false,'autopadding'=>true);
                    $this->pdf->balancetext='';
                    $this->forcetextcolor_b=$this->textcolor_b;
                    $this->forcetextcolor_g=$this->textcolor_g;
                    $this->forcetextcolor_r=$this->textcolor_r;
                    $this->forcefillcolor_b=$this->fillcolor_b;
                    $this->forcefillcolor_g=$this->fillcolor_g;
                    $this->forcefillcolor_r=$this->fillcolor_r;
                    if($this->continuenextpageText)
                        $this->printlongtext($this->pdf->getFontFamily(),$this->pdf->getFontStyle(),$this->pdf->getFontSize());
                    
                    }          
                
                    
         

            }
            elseif($arraydata["poverflow"]=="true") {
           
                            if($arraydata["valign"]=="M")
                                    $arraydata["valign"]="C";
                                if($arraydata["valign"]=="")
                                    $arraydata["valign"]="T"; 
                                
                $this->pdf->Cell($arraydata["width"], $arraydata["height"],  $this->formatText($txt, $arraydata["pattern"]),$arraydata["border"],"",$arraydata["align"],$arraydata["fill"],$arraydata["link"]."",0,true,"T",
                                $arraydata["valign"]);
                $this->pdf->Ln();
                    if($this->currentband=='detail'){
                    if($this->maxpagey['page_'.($this->pdf->getPage()-1)]=='')
                        $this->maxpagey['page_'.($this->pdf->getPage()-1)]=$this->pdf->GetY();
                    else{
                        if($this->maxpagey['page_'.($this->pdf->getPage()-1)]<$this->pdf->GetY())
                            $this->maxpagey['page_'.($this->pdf->getPage()-1)]=$this->pdf->GetY();
                    }
                }
            
            }
           
            else {
                //MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0) {   
                $this->pdf->MultiCell($arraydata["width"], $arraydata["height"], $this->formatText($txt, $arraydata["pattern"]), $arraydata["border"], 
                            $arraydata["align"], $arraydata["fill"],1,'','',true,0,true,true,$maxheight);
                if( $this->pdf->balancetext=='' && $this->currentband=='detail'){
                    if($this->maxpagey['page_'.($this->pdf->getPage()-1)]=='')
                        $this->maxpagey['page_'.($this->pdf->getPage()-1)]=$this->pdf->GetY();
                    else{
                        if($this->maxpagey['page_'.($this->pdf->getPage()-1)]<$this->pdf->GetY())
                            $this->maxpagey['page_'.($this->pdf->getPage()-1)]=$this->pdf->GetY();
                    }
                }
            if($this->pdf->balancetext!=''){
                $this->continuenextpageText=array('width'=>$arraydata["width"], 'height'=>$arraydata["height"], 'txt'=>$this->pdf->balancetext,
                        'border'=>$arraydata["border"] ,'align'=>$arraydata["align"], 'fill'=>$arraydata["fill"],'ln'=>1,
                            'x'=>$x,'y'=>'','reset'=>true,'streth'=>0,'ishtml'=>false,'autopadding'=>true);
                    $this->pdf->balancetext='';
                    $this->forcetextcolor_b=$this->textcolor_b;
                    $this->forcetextcolor_g=$this->textcolor_g;
                    $this->forcetextcolor_r=$this->textcolor_r;
                    $this->forcefillcolor_b=$this->fillcolor_b;
                    $this->forcefillcolor_g=$this->fillcolor_g;
                    $this->forcefillcolor_r=$this->fillcolor_r;
                    $this->gotTextOverPage=true;
                    if($this->continuenextpageText)
                        $this->printlongtext($this->pdf->getFontFamily(),$this->pdf->getFontStyle(),$this->pdf->getFontSize());
                    
                    }          



            }
        }
        $this->print_expression_result=false;
        


    }

    public function hex_code_color($value='') {
        $r=hexdec(substr($value,1,2));
        $g=hexdec(substr($value,3,2));
        $b=hexdec(substr($value,5,2));
        return array("r"=>$r,"g"=>$g,"b"=>$b,"R"=>$r,"G"=>$g,"B"=>$b);
    }

    public function get_first_value($value='') {
        return (substr($value,0,1));
    }

    function right($value='', $count=0) {

        return substr($value, ($count*-1));

    }

    function left($string='', $count=0) {
        return substr($string, 0, $count);
    }

//    public function analyse_expression($data,$isPrintRepeatedValue="true") {
//        $arrdata=explode("+",$data);
//        $pointerposition=$this->global_pointer+$this->offsetposition;
//        $i=0;
//             
//        foreach($arrdata as $num=>$out) {
//            $i++;
//            $arrdata[$num]=str_replace('"',"",$out);
//            $this->arraysqltable[$pointerposition][substr($out,3,-1)];
//
//            if(substr($out,0,3)=='$F{') {
//                
//                if($isPrintRepeatedValue=="true" ||$isPrintRepeatedValue=="") {
//                    $arrdata[$num]=$this->arraysqltable[$pointerposition][substr($out,3,-1)];
//                    
//                }
//                else {
//
//                    if($this->previousarraydata[$arrdata[$num]]==$this->arraysqltable[$pointerposition][substr($out,3,-1)]) {
//
//                        $arrdata[$num]="";
//                    }
//                    else {
//                        $arrdata[$num]=$this->arraysqltable[$pointerposition][substr($out,3,-1)];
//                        $this->previousarraydata[$out]=$this->arraysqltable[$pointerposition][substr($out,3,-1)];
//                    }
//                }
//              //  echo $arrdata[$num]."==";
//            }
//               elseif($out.""=='$V{REPORT_COUNT}'){
//           //        $this->pointer[]=array("type"=>"MultiCell","width"=>$data->reportElement["width"],"height"=>$height,"txt"=>&$this->report_count,"border"=>$border,"align"=>$align,"fill"=>$fill,"hidden_type"=>"report_count","soverflow"=>$stretchoverflow,"poverflow"=>$printoverflow,"link"=>substr($data->hyperlinkReferenceExpression,1,-1),"pattern"=>$data["pattern"],"valign"=>$valign);
//                                                        $arrdata[$num]=$this->report_count;
//               }
//            elseif($out.""=='$V{'.$this->grouplist[0]["name"].'_COUNT}'){
//                                                  $arrdata[$num]=$this->group_count[$this->grouplist[0]["name"]]-1;
//                                                          
//                }elseif($out.""=='$V{'.$this->grouplist[1]["name"].'_COUNT}'){
//                                            $arrdata[$num]=$this->group_count[$this->grouplist[1]["name"]]-1;
//                                            
//                }
//                elseif($out.""=='$V{'.$this->grouplist[2]["name"].'_COUNT}'){
//                                            $arrdata[$num]=$this->group_count[$this->grouplist[2]["name"]]-1;
//                }
//                elseif($out.""=='$V{'.$this->grouplist[3]["name"].'_COUNT}'){
//                                            $arrdata[$num]=$this->group_count[$this->grouplist[3]["name"]]-1;
//                }
//            elseif(substr($out,0,3)=='$V{') {
////### A new function to handle iReport's "+-/*" expressions.
//// It works like a cheap calculator, without precedences, so 1+2*3 will be 9, NOT 7.
//          
//              $p1=3;
//              $p2=strpos($out,"}");
//              if ($p2!==false){ 
//                  $total=&$this->arrayVariable[substr($out,$p1,$p2-$p1)]["ans"];
//                  $p1=$p2+1;
//                                    
//                                        //echo $out . "-><br/>".'$V{'.$this->grouplist[1]["name"].'_COUNT}'."<br/><br/>";
//                                       
//                                      
//                                            
//                                            while ($p1<strlen($out)){
//                      if (strpos("+-/*",substr($out,$p1,1))!==false) $opr=substr($out,$p1,1);
//                      else $opr="";
//                      $p1=strpos($out,'$V{',$p1)+3;
//                      $p2=strpos($out,"}",$p1);
//                                                
//                      if ($p2!==false){
//                                                        
//                                                        $nbr=&$this->arrayVariable[substr($out,$p1,$p2-$p1)]["ans"];
//                                                       
//                                                        
////            case '$V{'.$this->grouplist[0]["name"].'_COUNT}':
////         $gnam=$this->arrayband[0][$this->grouplist[0]["name"]];                                                                
////                $this->pointer[]=array("type"=>"MultiCell","width"=>$data->reportElement["width"],"height"=>$height,"txt"=>&$this->group_count[$this->grouplist[0]["name"]],"border"=>$border,"align"=>$align,"fill"=>$fill,"hidden_type"=>"group_count","soverflow"=>$stretchoverflow,"poverflow"=>$printoverflow,"link"=>substr($data->hyperlinkReferenceExpression,1,-1),"pattern"=>$data["pattern"],"valign"=>$valign);
////                break;
////            case '$V{'.$this->grouplist[1]["name"].'_COUNT}':
////        $gnam=$this->arrayband[0][$this->grouplist[1]["name"]];                                                             
////                $this->pointer[]=array("type"=>"MultiCell","width"=>$data->reportElement["width"],"height"=>$height,"txt"=>&$this->group_count[$this->grouplist[1]["name"]],"border"=>$border,"align"=>$align,"fill"=>$fill,"hidden_type"=>"group_count","soverflow"=>$stretchoverflow,"poverflow"=>$printoverflow,"link"=>substr($data->hyperlinkReferenceExpression,1,-1),"pattern"=>$data["pattern"],"valign"=>$valign);
////                break;
////            case '$V{'.$this->grouplist[2]["name"].'_COUNT}':
////        $gnam=$this->arrayband[0][$this->grouplist[2]["name"]];                                                             
////                $this->pointer[]=array("type"=>"MultiCell","width"=>$data->reportElement["width"],"height"=>$height,"txt"=>&$this->group_count[$this->grouplist[2]["name"]],"border"=>$border,"align"=>$align,"fill"=>$fill,"hidden_type"=>"group_count","soverflow"=>$stretchoverflow,"poverflow"=>$printoverflow,"link"=>substr($data->hyperlinkReferenceExpression,1,-1),"pattern"=>$data["pattern"],"valign"=>$valign);
////                break;
////            case '$V{'.$this->grouplist[3]["name"].'_COUNT}':
////        $gnam=$this->arrayband[0][$this->grouplist[3]["name"]];                                                             
////                $this->pointer[]=array("type"=>"MultiCell","width"=>$data->reportElement["width"],"height"=>$height,"txt"=>&$this->group_count[$this->grouplist[3]["name"]],"border"=>$border,"align"=>$align,"fill"=>$fill,"hidden_type"=>"group_count","soverflow"=>$stretchoverflow,"poverflow"=>$printoverflow,"link"=>substr($data->hyperlinkReferenceExpression,1,-1),"pattern"=>$data["pattern"],"valign"=>$valign);
////                break;
//                                                         
//                          switch ($opr){
//                              case "+": $total+=$nbr;
//                                        break;
//                              case "-": $total-=$nbr;
//                                        break;
//                              case "*": $total*=$nbr;
//                                        break;
//                              case "/": $total/=$nbr;
//                                        break;
//                          }
//                      }
//                      $p1=$p2+1;
//                  }
//                                
//              }
//              $arrdata[$num]=$total;
////### End of modifications, below is the original line.               
////                $arrdata[$num]=&$this->arrayVariable[substr($out,3,-1)]["ans"];
//            }
//            elseif(substr($out,0,3)=='$P{') {
//                $arrdata[$num]=$this->arrayParameter[substr($out,3,-1)];
//            }
//          //  echo "<br/>";
//        }
//
//        if($this->left($data,3)=='"("' && $this->right($data,3)=='")"') {
//            $total=0;
//
//            foreach($arrdata as $num=>$out) {
//                if($num>0 && $num<$i)
//                    $total+=$out;
//
//            }
//            return $total;
//
//        }
//        else {
//
//            return implode($arrdata);
//        }
//    }
   public function analyse_expression($data=[],$isPrintRepeatedValue="true",$datatype='')
   {
       //echo $data."<br/>";
       $tmpplussymbol='|_plus_|';
        $pointerposition=$this->global_pointer+$this->offsetposition;
        $i=0;
        $backcurl='___';
        $singlequote="|_q_|";
        $doublequote="|_qq_|";
       $fm=str_replace('{',"_",$data);
       $fm=str_replace('}',$backcurl,$fm);
       
        //$fm=str_replace('$V_REPORT_COUNT',$this->report_count,$fm);
       $isstring=false;
       
        
//        if($this->report_count>10 && $data=='$F{qty}' || $data=='$V{qty2}')  {
//               echo "$data =  $fm<br/>";
//             }
       foreach($this->arrayVariable as $vv=>$av){
            $i++;
            $vv=str_replace('$V{',"",$vv);
            $vv=str_replace('}',$backcurl,$vv);
            $vv=str_replace("'", $singlequote,$vv);
            $vv=str_replace('"', $doublequote,$vv);
           //if(strpos($fm,'REPORT_COUNT')){
             //      echo $fm;die;}
            //echo $vv.' to become '.$this->grouplist[1]["name"]."_COUNT <br/  >";
//           if($vv==$this->grouplist[0]["name"]."_COUNT" ){
//               
//             $fm=str_replace('$V_'.$vv."_COUNT",39992,$fm1);
//             //echo 39992 . "<br/>";
//           }
//           elseif($vv==$this->grouplist[1]["name"]."_COUNT"){
//             $fm=str_replace('$V_'.$vv."_COUNT",$this->group_count[$this->grouplist[1]["name"]],$fm1);
//             //echo 39992 . "<br/>";
//           }
//           elseif($vv==$this->grouplist[2]["name"]."_COUNT"){
//               $fm=str_replace('$V_'.$vv."_COUNT",$this->group_count[$this->grouplist[2]["name"]],$fm1);
//           }
//           elseif($vv==$this->grouplist[3]["name"]."_COUNT"){
//               $fm=str_replace('$V_'.$vv."_COUNT",$this->group_count[$this->grouplist[3]["name"]],$fm1);
//           }
             if(strpos($fm,'_COUNT')!==false){
                 $fm=str_replace('$V_'.$this->grouplist[0]["name"].'_COUNT'.$backcurl,($this->group_count[$this->grouplist[0]["name"]]-1),$fm);
                 $fm=str_replace('$V_'.$this->grouplist[1]["name"].'_COUNT'.$backcurl,($this->group_count[$this->grouplist[1]["name"]]-1),$fm);
                 $fm=str_replace('$V_'.$this->grouplist[2]["name"].'_COUNT'.$backcurl,($this->group_count[$this->grouplist[2]["name"]]-1),$fm);
                 $fm=str_replace('$V_'.$this->grouplist[3]["name"].'_COUNT'.$backcurl,($this->group_count[$this->grouplist[3]["name"]]-1),$fm);
                 
                 
             }
           else{
           
            if($av["ans"]!="" && is_numeric($av["ans"]) && ($this->left($av["ans"],1)||$this->left($av["ans"],1)=='-' )>0){
                 $av["ans"]=str_replace("+",$tmpplussymbol,$av["ans"]);
                 $fm=str_replace('$V_'.$vv.$backcurl,$av["ans"],$fm);
            }
            else{
                $av["ans"]=str_replace("+",$tmpplussymbol,$av["ans"]);

                // If is 0 then it will not go to upside if
                // So when 100 + 0, it will become 1000, which is not correct
                if(is_numeric($av["ans"]) && $av["ans"] == "0")
                {
                    $fm=str_replace('$V_'.$vv.$backcurl,$av["ans"],$fm);
                }
                else
                {
                    $fm=str_replace('$V_'.$vv.$backcurl,"'".$av["ans"]."'",$fm);
                }
                
                $isstring=true;
            }
                
            
            

           }
       }
        $fieldSetArr = array();
        foreach ($this->arraydetail as $k => $adetail)
        {
            foreach ($adetail as $index => $arrayset)
            {
                $type = $arrayset['type'];
                if($type == "MultiCell")
                {
                    $name = $arrayset["txt"];
                    $name = str_replace('$F{',"",$name);
                    $name = str_replace('}',"",$name);
                    $fieldSetArr["$name"] = array(
                        "pattern" => "$arrayset[pattern]",
                    );
                }
            }
        }
        // echo "<pre>".var_export($this->arraydetail,true);
        // echo "<pre>".var_export($fieldSetArr,true);
      
       
     $fm=str_replace('$V_REPORT_COUNT'.$backcurl,$this->report_count,$fm);
       foreach($this->arrayParameter as  $pv => $ap) {
           $ap=str_replace("+",$tmpplussymbol,$ap);
                       $ap=str_replace("'", $singlequote,$ap);
                       $ap=str_replace('"', $doublequote,$ap);
           if(is_numeric($ap)&&$ap!=''&& ($this->left($ap,1)>0 || $this->left($ap,1)=='-')){
                  $fm = str_replace('$P_'.$pv.$backcurl, $ap,$fm);
           }
           else{
            $fm = str_replace('$P_'.$pv.$backcurl, "'".$ap."'",$fm);
               $isstring=true;
           }
        }
            
       //     print_r($this->arrayfield);
        // echo "<pre>".var_export($this->arrayfieldtype,true)."</pre>";
       foreach($this->arrayfield as $af){
           $tmpfieldvalue=str_replace("+",$tmpplussymbol,$this->arraysqltable[$pointerposition][$af.""]);
                       $tmpfieldvalue=str_replace("'", $singlequote,$tmpfieldvalue);
                       $tmpfieldvalue=str_replace('"', $doublequote,$tmpfieldvalue);
           if(is_numeric($tmpfieldvalue) && $tmpfieldvalue!="" && ($this->left($tmpfieldvalue,1)>0||$this->left($tmpfieldvalue,1)=='-')){
            $fieldtype = $this->arrayfieldtype["$af"];
            $stringTypeArr = array("java.lang.String");
            $pattern = $fieldSetArr["$af"]["pattern"];
            if(in_array($fieldtype, $stringTypeArr))
            {
               // $fm =str_replace('$F_'.$af.$backcurl,"'".$tmpfieldvalue."'",$fm);
                // echo "$fm / $af / $pattern</br>";
               // if(empty($pattern))
               // {
               //      $dotstart = strpos($tmpfieldvalue, ".");
               //      $separateStr = substr($tmpfieldvalue, $dotstart+1);
               //      $totaldecimal = strlen($separateStr);
               //      $tmpfieldvalue = number_format($tmpfieldvalue,$totaldecimal,".",",");
               //      $fm =str_replace('$F_'.$af.$backcurl,"'".$tmpfieldvalue."'",$fm);
               // }
               // else
               // {
                $fm =str_replace('$F_'.$af.$backcurl,"'".$tmpfieldvalue."'",$fm);
               // }
            }
            else
            {
                $fm =str_replace('$F_'.$af.$backcurl,$tmpfieldvalue,$fm);                
            }
           }
           else{
               $fm =str_replace('$F_'.$af.$backcurl,"'".$tmpfieldvalue."'",$fm);
            $isstring=true;
           }
           
       }
       // echo "$fm<br>";
       
       if($fm=='')
           return "";
       else
       {
           
     
           //echo $fm."<br/>";
             
             
//              $fm=str_replace('+',".",$fm);
             // echo $fm."<br/>";
          if(strpos($fm, '"')!==false)
            $fm=str_replace('+'," . ",$fm);
          if(strpos($fm, "'")!==false)
            $fm=str_replace('+'," . ",$fm);
          
          
                       $fm=str_replace($tmpplussymbol,"+",$fm);

                       
     $fm=str_replace('$this->PageNo()',"''",$fm);


                       $fm=str_replace($singlequote,"\'" ,$fm);
                       $fm=str_replace( $doublequote,'"',$fm);
                       
                       if((strpos('"',$fm)==false) || (strpos("'",$fm)==false)){
                           $fm=str_replace('--', '- -', $fm);
                           $fm=str_replace('++', '+ +', $fm);
                       }
                   /* if(strpos($fm, "124.99")){
                        
                        echo $fm."<br/><br/>";
                    }*/

            $jpgkey = "data:image/jpeg;base64";
            $pngkey = "data:image/png;base64,";

            if($datatype == "Image" && ($this->left($data, 22) == $jpgkey || $this->left($data, 22) == $pngkey))
            {
                // for upload image one
                eval("\$result= '".$fm."';");
            }
            else
            {
             $fm=str_replace('convertNumber', '', $fm);
               eval("\$result= ".$fm.";");
            }

            if($isPrintRepeatedValue=="true" ||$isPrintRepeatedValue=="")
            {
                return $result;
            }
            else
            {
                if($this->lastrowresult["$this->currentuuid"]==$result)
                {

                    $this->lastrowresult["$this->currentuuid"]=$result;
                    return "";
                }
                else
                {

                    $this->lastrowresult["$this->currentuuid"] = $result;
                    return $result;
                }
            }
        }


    }
  
    
    public function formatText($txt='',$pattern='') {
        // echo "$txt $pattern</br>";
        if($pattern=="###0")
            return number_format($txt,0,"","");
        elseif($pattern=="#,##0")
            return number_format($txt,0,".",",");
        elseif($pattern=="###0.0")
            return number_format($txt,1,".","");
        elseif($pattern=="#,##0.0" || $pattern=="#,##0.0;-#,##0.0")
            return number_format($txt,1,".",",");
        elseif($pattern=="###0.00" || $pattern=="###0.00;-###0.00")
            return number_format($txt,2,".","");
        elseif($pattern=="#,##0.00" || $pattern=="#,##0.00;-#,##0.00")
            return number_format($txt,2,".",",");
        elseif($pattern=="###0.00;(###0.00)")
            return ($txt<0 ? "(".number_format(abs($txt),2,".","").")" : number_format($txt,2,".",""));
        elseif($pattern=="#,##0.00;(#,##0.00)")
            return ($txt<0 ? "(".number_format(abs($txt),2,".",",").")" : number_format($txt,2,".",","));
        elseif($pattern=="#,##0.00;(-#,##0.00)")
            return ($txt<0 ? "(".number_format($txt,2,".",",").")" : number_format($txt,2,".",","));
        
        elseif($pattern=="###0.000")
            return number_format($txt,3,".","");
        elseif($pattern=="#,##0.000")
            return number_format($txt,3,".",",");
        elseif($pattern=="#,##0.0000")
            return number_format($txt,4,".",",");
        elseif($pattern=="###0.0000")
            return number_format($txt,4,".","");
        elseif($pattern=="#,##0.00000")
            return number_format($txt,5,".",",");
        elseif($pattern=="#,##0.000000")
            return number_format($txt,6,".",",");
        elseif($pattern=="#,##0.0000000")
            return number_format($txt,7,".",",");
        elseif($pattern=="#,##0.00000000")
            return number_format($txt,8,".",",");
        elseif($pattern=="###0.00000")
            return number_format($txt,5,".","");        
        elseif($pattern=="dd/MM/yyyy" && $txt !="")
            return date("d/m/Y",strtotime($txt));
        elseif($pattern=="MM/dd/yyyy" && $txt !="")
            return date("m/d/Y",strtotime($txt));
        elseif($pattern=="yyyy/MM/dd" && $txt !="")
            return date("Y/m/d",strtotime($txt));
        elseif($pattern=="dd-MMM-yy" && $txt !="")
            return date("d-M-Y",strtotime($txt));
        elseif($pattern=="dd-MMM-yy" && $txt !="")
            return date("d-M-Y",strtotime($txt));
        elseif($pattern=="dd/MM/yyyy h.mm a" && $txt !="")
            return date("d/m/Y h:i a",strtotime($txt));
        elseif($pattern=="dd/MM/yyyy HH.mm.ss" && $txt !="")
            return date("d-m-Y H:i:s",strtotime($txt));
        elseif($pattern=="d/m/Y" && $txt !="")
            return date("d/m/Y",strtotime($txt));
        elseif($pattern=="m/d/Y" && $txt !="")
            return date("m/d/Y",strtotime($txt));
        elseif($pattern=="Y/m/d" && $txt !="")
            return date("Y/m/d",strtotime($txt));
        elseif($pattern=="d-M-Y" && $txt !="")
            return date("d-M-Y",strtotime($txt));
        elseif($pattern=="d-m-Y" && $txt !="")
            return date("d-m-Y",strtotime($txt));
        elseif($pattern=="d-M-Y" && $txt !="")
            return date("d-M-Y",strtotime($txt));
        elseif($pattern=="d/m/Y h:i a" && $txt !="")
            return date("d/m/Y h:i a",strtotime($txt));
        elseif($pattern=="d-m-Y H:i:s" && $txt !="")
            return date("d-m-Y H:i:s",strtotime($txt));
        elseif($pattern=="d.m.Y" && $txt !="")
            return date("d.m.Y",strtotime($txt));
        elseif($pattern=="#,##0.xx")
            return $this->convertNumberWithDynamicDecimal($txt,".",",");
        elseif($pattern=="AMTTOTEXT" && $txt !="")
            return $this->convertNumber($txt);
        else
            return $txt;


    }

    public function print_expression($data=[]) {
         $expression=$data["printWhenExpression"];
        $this->print_expression_result=false;
        if($expression!=""){
        //echo $expression."-";
            $expression=$this->analyse_expression($expression);
        //echo $expression."<br/>";       
            if($expression!='')
            eval('if('.$expression.'){$this->print_expression_result=true;}');
        }
        else
            $this->print_expression_result=true;
        return  $this->print_expression_result;
        

    }

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
    
    public function transferXMLtoArray($fileName='') {
        if(!file_exists($fileName))
            echo "File - $fileName does not exist";
        else {

            $xmlAry = $this->xmlobj2arr(simplexml_load_file($fileName));
            
            foreach($xmlAry[header] as $key => $value)
                $this->arraysqltable["$this->m"]["$key"]=$value;

            foreach($xmlAry[detail][record]["$this->m"] as $key2 => $value2)
                $this->arraysqltable["$this->m"]["$key2"]=$value2;
        }

      //  if(isset($this->arrayVariable))   //if self define variable existing, go to do the calculation
       //     $this->variable_calculation();

    }

    public function includeSubReport($d=[],$arrdata=[],$current_y=0){ 
        
               include_once ("PHPJasperXMLSubReport.inc.php");
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
//wrote by huzursuz at mailinator dot com on 02-Feb-2009 04:44
//http://hk.php.net/manual/en/function.get-object-vars.php
    public function xmlobj2arr($Data) {
        if (is_object($Data)) {
            foreach (get_object_vars($Data) as $key => $val)
                $ret[$key] = $this->xmlobj2arr($val);
            return $ret;
        }
        elseif (is_array($Data)) {
            foreach ($Data as $key => $val)
                $ret[$key] = $this->xmlobj2arr($val);
            return $ret;
        }
        else
            return $Data;
    }


private function Rotate($type, $x=-1, $y=-1)
{
    if($type=="")
    $angle=0;
    elseif($type=="Left")
    $angle=90;
    elseif($type=="Right")
    $angle=270;
    elseif($type=="UpsideDown")
    $angle=180;

    if($x==-1)
        $x=$this->pdf->getX();
    if($y==-1)
        $y=$this->pdf->getY();
    if($this->angle!=0)
        $this->pdf->_out('Q');
    $this->angle=$angle;
    if($angle!=0)
    {
        $angle*=M_PI/180;
        $c=cos($angle);
        $s=sin($angle);
        $cx=$x*$this->pdf->k;
        $cy=($this->pdf->h-$y)*$this->pdf->k;
        $this->pdf->_out(sprintf('q %.5f %.5f %.5f %.5f %.2f %.2f cm 1 0 0 1 %.2f %.2f cm', $c, $s, -$s, $c, $cx, $cy, -$cx, -$cy));
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
       
       
      // if($this->groupnochange==-1)
           return false;
       //else{
         //  $this->groupnochange++;
           //return  true; //return got change group
       //}
      } 
    
}

public function getTTFFontPath($fontname=''){
global  $pchartfolder;
       if( $pchartfolder =="")
          $pchartfolder=dirname(__FILE__)."/pchart2";

$fontpatharr=array("$pchartfolder/fonts");
$defaultfont="MankSans";
if(PHP_OS=='Linux'){
    array_push($fontpatharr,"/usr/share/fonts/truetype/freefont");
        
}
elseif(PHP_OS=='Darwin'){
    array_push($fontpatharr,"/Library/Fonts","/Network/Library/Fonts");
        
}
else{
    array_push($fontpatharr,"c:/windows/fonts");
        
}

foreach($fontpatharr as $folder){
 $smallfontname=$folder."/".$fontname.".ttf";
  $bigfontname=$folder."/".strtolower($fontname).".ttf";
//  echo "$smallfontname<br/>$bigfontname<br/>";
if(file_exists($smallfontname)){
//echo $smallfontname;die;
return $smallfontname;
}
if(file_exists($bigfontname)){
//echo $bigfontname;die;
return $bigfontname;
}


}

 return "$pchartfolder/fonts/GeosansLight.ttf";
 }
 
public function convertNumberWithDynamicDecimal($txt='',$decimalpoint='.',$thousandseparator=',')
{
    $tmptxt = preg_replace("/(\"|'|[[:blank:]]|[[:space:]])/", "", $txt);
    $strSplit = preg_split("/(?<=[0-9])(?=[a-zA-Z]+)|(?=[0-9]+)(?<=[a-zA-Z])/i",$tmptxt);
    $replaceArr = array();
    foreach ($strSplit as $key => $value)
    {
        $originalVal = $value;
        if(is_numeric($value))
        {
            $totaldecimal = 0;
            $arr = preg_split("/(\..*\d)/", $value, -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
            foreach ($arr as $k => $val)
            {
                if(preg_match("/(\..*\d)/", $val))
                {
                    $tmpval = substr($val, 0, -1);
                    $totaldecimal = strlen($tmpval);
                    $value = number_format($value,$totaldecimal,$decimalpoint,$thousandseparator);
                }
            }
        }
        $replaceArr[$originalVal] = $value;
    }

    foreach ($replaceArr as $key => $value)
    {
        $txt = str_replace($key, $value, $txt);

    }
    return $txt;
}

public function convertNumber($str='')
{
    global $simbizDB, $defaultorganization_id, $defaultbranch_id;
    $arr = preg_split('/(?<=[a-zA-Z])(?=[0-9]+)/i',$str);
    $txt = "";
    if(isset($arr[1]) && count($arr) > 1)
    {
        $txt = $arr[0];
        $num = $arr[1];
    }
    else
    {
        $num = $arr[0];
    }
   list($num, $dec) = explode(".", $num);

   $output = "";

   if($num{0} == "-")
   {
      $output = "negative ";
      $num = ltrim($num, "-");
   }
   else if($num{0} == "+")
   {
      $output = "positive ";
      $num = ltrim($num, "+");
   }

   if($num{0} == "0")
   {
      $output .= "zero";
   }
   else
   {
      $num = str_pad($num, 36, "0", STR_PAD_LEFT);
      $group = rtrim(chunk_split($num, 3, " "), " ");
      $groups = explode(" ", $group);

      $groups2 = array();
      foreach($groups as $g) $groups2[] = $this->convertThreeDigit($g{0}, $g{1}, $g{2});

      for($z = 0; $z < count($groups2); $z++)
      {
         if($groups2[$z] != "")
         {
            $output .= $groups2[$z].$this->convertGroup(11 - $z).($z < 11 && !array_search('', array_slice($groups2, $z + 1, -1))
             && $groups2[11] != '' && $groups[11]{0} == '0' ? " " : " ");
//             && $groups2[11] != '' && $groups[11]{0} == '0' ? " and " : ", ");
         }
      }

      $output = rtrim($output, ", ");
   }

   if($dec > 0)
   {
    $output .= " and cents ".$this->convertTwoDigit($dec{0},$dec{1});
   }

   $converttxt = '';
   if(!empty($txt))
   {
    $converttxt = $txt.' ';
   }

   $output = $converttxt.$output;

   return strtoupper($output . " only");
}

public  function convertGroup($index=0)
{
   switch($index)
   {
      case 11: return " decillion";
      case 10: return " nonillion";
      case 9: return " octillion";
      case 8: return " septillion";
      case 7: return " sextillion";
      case 6: return " quintrillion";
      case 5: return " quadrillion";
      case 4: return " trillion";
      case 3: return " billion";
      case 2: return " million";
      case 1: return " thousand";
      case 0: return "";
   }
}

public  function convertThreeDigit($dig1=0, $dig2=0, $dig3=0)
{
   $output = "";

   if($dig1 == "0" && $dig2 == "0" && $dig3 == "0") return "";

   if($dig1 != "0")
   {
      $output .= $this->convertDigit($dig1)." hundred";
      if($dig2 != "0" || $dig3 != "0") $output .= " ";
   }

   if($dig2 != "0") $output .= $this->convertTwoDigit($dig2, $dig3);
   else if($dig3 != "0") $output .= $this->convertDigit($dig3);

   return $output;
}

public function convertTwoDigit($dig1=0, $dig2=0)
{
    $dig1 = isset($dig1) && !empty($dig1)? $dig1: 0;
    $dig2 = isset($dig2) && !empty($dig2)? $dig2: 0;
   if($dig2 == "0")
   {
      switch($dig1)
      {
         case "1": return "ten";
         case "2": return "twenty";
         case "3": return "thirty";
         case "4": return "forty";
         case "5": return "fifty";
         case "6": return "sixty";
         case "7": return "seventy";
         case "8": return "eighty";
         case "9": return "ninety";
      }
   }
   else if($dig1 == "1")
   {
      switch($dig2)
      {
         case "1": return "eleven";
         case "2": return "twelve";
         case "3": return "thirteen";
         case "4": return "fourteen";
         case "5": return "fifteen";
         case "6": return "sixteen";
         case "7": return "seventeen";
         case "8": return "eighteen";
         case "9": return "nineteen";
      }
   }
   else
   {
      $temp = $this->convertDigit($dig2);
      switch($dig1)
      {
         case "0": return "$temp";
         case "2": return "twenty-$temp";
         case "3": return "thirty-$temp";
         case "4": return "forty-$temp";
         case "5": return "fifty-$temp";
         case "6": return "sixty-$temp";
         case "7": return "seventy-$temp";
         case "8": return "eighty-$temp";
         case "9": return "ninety-$temp";
      }
   }
}

public function convertDigit($digit=0)
{
   switch($digit)
   {
      case "0": return "zero";
      case "1": return "one";
      case "2": return "two";
      case "3": return "three";
      case "4": return "four";
      case "5": return "five";
      case "6": return "six";
      case "7": return "seven";
      case "8": return "eight";
      case "9": return "nine";
   }
}


  public function getCentSalesPoint($value=''){

    $retval = $value;
    $pointstr = strpos($value,".");

    $number1 = substr($retval,0,strpos($value,".")+2);

    if($pointstr>0){
    $lastpointer = substr($retval,strpos($value,".")+2,strpos($value,".")+3);
    //echo $lastpointer = substr($retval,strpos($value,".")+2,strpos($value,"."));

    if($lastpointer > 5)
    $number1 = $number1 + 0.1;

    if($lastpointer != 5)
    $retval = $number1;
    }

    return $retval;
  } 

  public function setDisplayImageSize($arraydata=[], $imgdata='')
  {
    if(array_key_exists("scale_type", $arraydata))
     {
        $imgdetails = getimagesizefromstring($imgdata);
     // echo var_export($imgdetails);
        $imgwidth = $imgdetails[0];
        $imgheight = $imgdetails[1];

        // echo "<pre>Scale Type: ".$arraydata["scale_type"]."</pre>";
        switch($arraydata["scale_type"])
        {
            case "RetainShape":
                if($imgwidth > $imgheight)
                {
                    if($imgwidth > $arraydata["width"])
                    {
                         $imgheight *= $arraydata["width"] / $imgwidth;
                         $imgwidth = $arraydata["width"];
                    }
                    else if($imgwidth < $arraydata["width"])
                    {
                        $imgheight *= $arraydata["width"] / $imgwidth;
                        $imgwidth = $arraydata["width"];
                    }

                    if($imgheight > $arraydata["height"])
                    {
                        $imgwidth *= $arraydata["height"] / $imgheight;
                        $imgheight = $arraydata["height"];
                    }
                }
                else if($imgwidth < $imgheight)
                {
                    if($imgheight > $arraydata["height"])
                    {
                        $imgwidth *= $arraydata["height"] / $imgheight;
                        $imgheight = $arraydata["height"];
                    }
                    else if($imgheight < $arraydata["height"])
                    {
                        $imgwidth *= $arraydata["height"] / $imgheight;
                        $imgheight = $arraydata["height"];
                    }

                    
                    if($imgwidth > $arraydata["width"])
                    {
                         $imgheight *= $arraydata["width"] / $imgwidth;
                         $imgwidth = $arraydata["width"];
                    }

                }
                else if($imgwidth == $imgheight)
                {
                    if($imgwidth > $arraydata["width"])
                    {
                         $imgheight *= $arraydata["width"] / $imgwidth;
                         $imgwidth = $arraydata["width"];
                    }

                    if($imgheight > $arraydata["height"])
                    {
                        $imgwidth *= $arraydata["height"] / $imgheight;
                        $imgheight = $arraydata["height"];
                    }
                }
                break;
            case "Clip":
                if($imgwidth > $arraydata["width"])
                {
                    $imgwidth = $arraydata["width"];
                }
                if($imgheight > $arraydata["height"])
                {
                    $imgheight = $arraydata["height"];
                }
                break;
            case "RealHeight":
                if($imgheight > $arraydata["height"])
                {
                    $imgwidth *= $arraydata["height"] / $imgheight;
                    $imgheight = $arraydata["height"];
                }
                if($imgwidth > $arraydata["width"])
                {
                    $imgheight *= $arraydata["width"] / $imgwidth;
                    $imgwidth = $arraydata["width"];
                }
                break;
            case "RealSize":
                    if($imgwidth < $arraydata["width"] && $imgheight < $arraydata["height"])
                    {
                        $imgwidth = $imgdetails[0];
                        $imgheight = $imgdetails[1];
                    }
                    else
                    {
                        if($imgwidth > $imgheight)
                        {
                            if($imgwidth > $arraydata["width"])
                            {
                                 $imgheight *= $arraydata["width"] / $imgwidth;
                                 $imgwidth = $arraydata["width"];
                            }
                            if($imgheight > $arraydata["height"])
                            {
                                $imgwidth *= $arraydata["height"] / $imgheight;
                                $imgheight = $arraydata["height"];
                            }
                        }
                        else
                        {
                            if($imgheight > $arraydata["height"])
                            {
                                $imgwidth *= $arraydata["height"] / $imgheight;
                                $imgheight = $arraydata["height"];
                            }
                            if($imgwidth > $arraydata["width"])
                            {
                                 $imgheight *= $arraydata["width"] / $imgwidth;
                                 $imgwidth = $arraydata["width"];
                            }
                        }
                    }
                break;
            case "FillFrame": default:
                $imgwidth = $arraydata["width"];
                $imgheight = $arraydata["height"];
                break;
        }
     }
     else
     {
        $imgwidth = $arraydata["width"];
        $imgheight = $arraydata["height"];
     }
           // echo "<pre>Real Width: ".$imgdetails[0]."</pre>";
           // echo "<pre>Real Height: ".$imgdetails[1]."</pre>";
           // echo "<pre>Img Width: $imgwidth</pre>";
           // echo "<pre>Img Height: $imgheight</pre>";
           // echo '<pre>Size Width:'.$arraydata["width"].'</pre>';
           // echo '<pre>Size Height:'.$arraydata["height"].'</pre>';

     return array("width" => $imgwidth, "height" => $imgheight);
  }
}
