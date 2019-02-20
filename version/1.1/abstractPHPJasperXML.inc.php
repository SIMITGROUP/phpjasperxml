<?php

class abstractPHPJasperXML
{
        protected $adjust=1.2;
        protected $chartscaling=1.35;
        protected $version="1.1";
        protected $pdflib;
        protected $lang;
        public $debugsql=false;
        protected $myconn;       
        // protected $detail_yposition = 0;
        protected $global_pointer;
        protected $arraysubdataset;
        protected $offsetposition=0;
        protected $detailbandqty=0;
        protected $hideheader=false;
        public $arraysqltable;
        protected $detailallowtill=0;
        public $sql;
        public $arrayParameter;
        protected $group_count=[];
        public $arrayVariable;
        protected $lastrowresult=[];
        protected $currentuuid;
        protected $report_count=0;        //### New declaration (variable exists in original too)
        protected $elementid=0;
        public $arrayfield=[];
        protected $pchartfolder= __DIR__ . '/../../pchart2';
        protected $chartobj;
        protected $fontdir = __DIR__ . "/../../tcpdf/fonts";
        protected $groupnochange=0; //use for detect record change till which level of grouping (grouping support multilevel)
        protected $addedttffont=[];
        public function setErrorReport($error_report=0)
        {
            
             error_reporting($error_report);             
        }

        public function setData($data=[])
        {
            $this->arraysqltable=$data;
            $this->m=count($data);
        }

        protected function connect($db_host='',$db_user='',$db_pass='',$dbname='',$cndriver="mysql") {
            $this->db_host=$db_host;
            $this->db_user=$db_user;
            $this->db_pass=$db_pass;
            $this->dbname=$dbname;
            $this->cndriver=$cndriver;        
                
            $this->chartobj->sql=$this->sql;
            $this->chartobj->cndriver=$this->cndriver;                   
            
            
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
                 $conn_string = "host=$db_host port=$pgport dbname=$dbname user=$db_user password=$db_pass";
                $this->myconn = pg_connect($conn_string);

                if($this->myconn) {
                    $this->con = true;
                    return true;
                }else
                    return false;
            }
            elseif($cndriver=="sqlsrv") {
     
                 if(!$this->con) {
                   $connectionInfo = array( "Database"=>$dbname, "UID"=>$db_user, "PWD"=>"$db_pass");
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



    protected function setFont($arraydata)
    {
        $font=  strtolower(str_replace(' ', '', $arraydata["font"]));

        if($arraydata["fontstyle"]=="BI")
        {
            $fontfile=$this->fontdir.'/'.$font.'bi.php';
        }
        elseif($arraydata["fontstyle"]=="I")
        {
            $fontfile=$this->fontdir.'/'.$font.'i.php';
        }
        elseif($arraydata["fontstyle"]=="B")
        {
            $fontfile=$this->fontdir.'/'.$font.'b.php';
        }
        else
        {
             $fontfile=$this->fontdir.'/'.$font.'.php';
        }
            
            // if(!file_exists($fontfile))
            // {
            //     $fontfile=$this->fontdir.'/'.$font.'.php';
            // }
            
            //can get font from php code
           if(file_exists($fontfile) )
           {
            
                $this->pdf->SetFont($font,$arraydata["fontstyle"],$arraydata["fontsize"],$fontfile);
           }
           else
           {
            //check server side have font or not
                // $fontfile=$this->getTTFFontPath($font);
                if(file_exists($fontfile))
                {
                    
                    if(!in_array($fontfile, $this->addedttffont))
                    {
                        $fontname = TCPDF_FONTS::addTTFfont($fontfile, 'TrueTypeUnicode', '', 96);                        
                        // array_push($this->addedttffont,$fontfile);
                    }
                    
                    $this->pdf->SetFont($fontname, $arraydata["fontstyle"], $arraydata["fontsize"], '', false);

                    
                }
                else
                {
                    $arraydata["font"]="freeserif";
                    if($arraydata["fontstyle"]=="")
                    {
                        $this->pdf->SetFont('freeserif',$arraydata["fontstyle"],$arraydata["fontsize"],$this->fontdir.'/freeserif.php');
                    }
                    elseif($arraydata["fontstyle"]=="B")
                    {
                        $this->pdf->SetFont('freeserifb',$arraydata["fontstyle"],$arraydata["fontsize"],$this->fontdir.'/freeserifb.php');
                    }
                    elseif($arraydata["fontstyle"]=="I")
                    {
                        $this->pdf->SetFont('freeserifi',$arraydata["fontstyle"],$arraydata["fontsize"],$this->fontdir.'/freeserifi.php');
                    }
                    elseif($arraydata["fontstyle"]=="BI")
                    {
                        $this->pdf->SetFont('freeserifbi',$arraydata["fontstyle"],$arraydata["fontsize"],$this->fontdir.'/freeserifbi.php');
                    }
                    elseif($arraydata["fontstyle"]=="BIU")
                    {
                        $this->pdf->SetFont('freeserifbi',"BIU",$arraydata["fontsize"],$this->fontdir.'/freeserifbi.php');
                    }
                    elseif($arraydata["fontstyle"]=="U")
                    {
                        $this->pdf->SetFont('freeserif',"U",$arraydata["fontsize"],$this->fontdir.'/freeserif.php');
                    }
                    elseif($arraydata["fontstyle"]=="BU")
                    {
                        $this->pdf->SetFont('freeserifb',"U",$arraydata["fontsize"],$this->fontdir.'/freeserifb.php');
                    }
                    elseif($arraydata["fontstyle"]=="IU")
                    {
                        $this->pdf->SetFont('freeserifi',"IU",$arraydata["fontsize"],$this->fontdir.'/freeserifbi.php');
                    }
                }
                
                    
                
            }

    }

    protected function display($arraydata=[],$y_axis=0,$fielddata=false,$maxheight=0) {

        if(!isset($arraydata["uuid"]))
        {
            $arraydata["uuid"]='';
        }
        $this->currentuuid=$arraydata["uuid"];            
        if(!isset($arraydata["rotation"]))
        {
            $arraydata["rotation"]='';
        }
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
                // echo $arraydata['x'];
                $this->pdf->k=1;
                $initrightpos=204;
                $pagewidth=$this->arrayPageSetting['pageWidth'];
                //$pageWidth=595;
                $leftpos=$this->arrayPageSetting["leftMargin"]+$arraydata['width']/2+$arraydata['x']/2;
                //$pagewidth-$this->arrayPageSetting["rightMargin"]+$arraydata['width'];
                //$pagewidth;
                //;-$initrightpos;
                //-$this->arrayPageSetting["rightMargin"]-$arraydata["x"]+$arraydata['width'];

                //$this->arrayPageSetting["leftMargin"]+$arraydata['x'];
                $this->Rotate($arraydata["rotation"],$leftpos);
                //soverflow"=>$stretchoverflow,"poverflow"
                $arraydata["soverflow"]=true;
                $arraydata["poverflow"]=true;
                
               //   $w=$arraydata["width"];
               // $arraydata["width"]=$arraydata["height"];
                //$arraydata["height"]=$w;
                // echo $this->pdf->GetX().':';
                // echo $arraydata["width"];die;
                // print_r($arraydata);
                // echo '<hr/>';
                $this->pdf->SetY(                    
                       // $leftpos,
                        $this->pdf->GetY()-$arraydata["height"]
                    );

                // $this->pdf->SetX(
                   // $leftpos+$arraydata["width"]
                       // $this->pdf->GetX()+$arraydata["width"]
                        // 0
                    // );
            }
            else
            {

            }
        }
        if($arraydata["type"]=="SetFont") {
            $this->setFont($arraydata);
          //              $arraydata["font"]=  strtolower(str_replace(' ', '', $arraydata["font"]));

          //               if($arraydata["fontstyle"]=="BI")
          //                   $fontfile=$this->fontdir.'/'.$arraydata["font"].'bi.php';
          //               elseif($arraydata["fontstyle"]=="I")
          //                   $fontfile=$this->fontdir.'/'.$arraydata["font"].'i.php';
          //               elseif($arraydata["fontstyle"]=="B")
          //                   $fontfile=$this->fontdir.'/'.$arraydata["font"].'b.php';
          //               else
          //                    $fontfile=$this->fontdir.'/'.$arraydata["font"].'.php';
            
          //   if(!file_exists($fontfile))
          //   {
          //       $fontfile=$this->fontdir.'/'.$arraydata["font"].'.php';
          //   }
            
          //   //echo $fontfile."<br/>";
          // if(file_exists($fontfile) ){
            
          //       $this->pdf->SetFont($arraydata["font"],$arraydata["fontstyle"],$arraydata["fontsize"],$fontfile);
          //  }
          //  else{

          //       $arraydata["font"]="freeserif";
          //                       if($arraydata["fontstyle"]=="")
          //                           $this->pdf->SetFont('freeserif',$arraydata["fontstyle"],$arraydata["fontsize"],$this->fontdir.'/freeserif.php');
          //                       elseif($arraydata["fontstyle"]=="B")
          //                           $this->pdf->SetFont('freeserifb',$arraydata["fontstyle"],$arraydata["fontsize"],$this->fontdir.'/freeserifb.php');
          //                       elseif($arraydata["fontstyle"]=="I")
          //                           $this->pdf->SetFont('freeserifi',$arraydata["fontstyle"],$arraydata["fontsize"],$this->fontdir.'/freeserifi.php');
          //                       elseif($arraydata["fontstyle"]=="BI")
          //                           $this->pdf->SetFont('freeserifbi',$arraydata["fontstyle"],$arraydata["fontsize"],$this->fontdir.'/freeserifbi.php');
          //                       elseif($arraydata["fontstyle"]=="BIU")
          //                           $this->pdf->SetFont('freeserifbi',"BIU",$arraydata["fontsize"],$this->fontdir.'/freeserifbi.php');
          //                       elseif($arraydata["fontstyle"]=="U")
          //                           $this->pdf->SetFont('freeserif',"U",$arraydata["fontsize"],$this->fontdir.'/freeserif.php');
          //                       elseif($arraydata["fontstyle"]=="BU")
          //                           $this->pdf->SetFont('freeserifb',"U",$arraydata["fontsize"],$this->fontdir.'/freeserifb.php');
          //                       elseif($arraydata["fontstyle"]=="IU")
          //                           $this->pdf->SetFont('freeserifi',"IU",$arraydata["fontsize"],$this->fontdir.'/freeserifbi.php');
                    
                
          //   }

        }
        elseif($arraydata["type"]=="subreport") {   
        

            return $this->runSubReport($arraydata,$y_axis);

        }
        elseif($arraydata["type"]=="MultiCell") {
          
         
            if($arraydata["hidden_type"]=='statictext' || $fielddata==false) 
            {
                $this->checkoverflow($arraydata,$this->updatePageNo($arraydata["txt"]),'',$maxheight);
            }
            elseif($fielddata==true) 
            {
            
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
            {
                $style='';
            }
            else
            {
                $style='FD';    
            }
            

                // echo   $arraydata['printWhenExpression'].',wait result';
            //
                //        $this->pdf->SetLineStyle($arraydata['border']);
                        // $this->print_expression($arraydata)
            if(isset($arraydata['printWhenExpression']) && ($arraydata['printWhenExpression']=='' || $this->analyse_expression($arraydata['printWhenExpression'])))
            {
                foreach($arraydata['border'] as $bs=>$ba){
                    foreach($ba as $bbc){
                        $this->pdf->SetLineStyle($bbc) ;
                    }
                }

                $this->pdf->RoundedRect($arraydata["x"]+$this->arrayPageSetting["leftMargin"], $arraydata["y"]+$y_axis, $arraydata["width"], $arraydata["height"], $arraydata["radius"], '1111', $style, array(),$arraydata['fillcolor']);
            }

        }
        elseif($arraydata["type"]=="Ellipse"){
            //$this->pdf->SetLineStyle($arraydata['border']);
             $this->pdf->Ellipse($arraydata["x"]+$arraydata["width"]/2+$this->arrayPageSetting["leftMargin"], $arraydata["y"]+$y_axis+$arraydata["height"]/2, $arraydata["width"]/2,$arraydata["height"]/2,
                0,0,360,'FD',$arraydata['border'],$arraydata['fillcolor']);
        }
        else if($arraydata["type"]=="Image")
        {
            $path = $this->analyse_expression($arraydata["path"], "true", $arraydata["type"]);

            $imgtype=substr($path,-3);
            $arraydata["link"]=$arraydata["link"]."";
            
            $arraydata["link"]=$this->analyse_expression($arraydata["link"]);
            // echo $imgtype.': '. $path ."<hr/>";
    
           //### Print with expression for image ####			
            //Function Reference: Image($file, $x='', $y='', $w=0, $h=0, $type='', $link='', $align='', $resize=false, $dpi=300, $palign='', 
	    //$ismask=false, $imgmask=false, $border=0, $fitbox=false, $hidden=false, $fitonpage=false, $alt=false, $altimgs=array())     
                
            if($imgtype=='jpg' || $this->right($path,3)=='jpg' || $this->right($path,4)=='jpeg')
            {
                 $imgtype="JPEG";
                    $printimage=false;
				$this->pdf->Image($path,$arraydata["x"]+$this->arrayPageSetting["leftMargin"],$arraydata["y"]+$y_axis,
                                  $arraydata["width"],$arraydata["height"],$imgtype,$arraydata["link"],'',false,300,'',false,false,0,false,true); 
				if($arraydata['printWhenExpression']=="")
					$printimage=true;
				else
					$printimage=$this->analyse_expression($arraydata['printWhenExpression']);
				if($printimage)
                 $this->pdf->Image($path,$arraydata["x"]+$this->arrayPageSetting["leftMargin"],$arraydata["y"]+$y_axis,
                                  $arraydata["width"],$arraydata["height"],$imgtype,$arraydata["link"],'',false,300,'',false,false,0,false,false);                                    
            }
            elseif($imgtype=='png'|| $imgtype=='PNG')
            {
                  $imgtype="PNG";$printimage=false;
				$this->pdf->Image($path,$arraydata["x"]+$this->arrayPageSetting["leftMargin"],$arraydata["y"]+$y_axis,
								  $arraydata["width"],$arraydata["height"],$imgtype,$arraydata["link"],'',false,300,'',false,false,0,false,true); 
				if($arraydata['printWhenExpression']=="")
					$printimage=true;
				else
					$printimage=$this->analyse_expression($arraydata['printWhenExpression']);
				if($printimage)
                  $this->pdf->Image($path,$arraydata["x"]+$this->arrayPageSetting["leftMargin"],$arraydata["y"]+$y_axis,
                                  $arraydata["width"],$arraydata["height"],$imgtype,$arraydata["link"],'',false,300,'',false,false,0,false,false);            
            }
                //### Print with expression for image Ends ####	
            else
            {
                
                 if(file_exists($path) || $this->left($path,4)=='http' )
                {  
                            $this->pdf->Image($path,$arraydata["x"]+$this->arrayPageSetting["leftMargin"],$arraydata["y"]+$y_axis,
                                  $arraydata["width"],$arraydata["height"],$imgtype,$arraydata["link"]);                        
                }
                elseif($this->left($path,22)==  "data:image/jpeg;base64")
                {
                    $imgtype="JPEG";
                    $img_base64_encoded = $path;
                    // $imageContent = file_get_contents($img_base64_encoded);
                    // $newpath = tempnam(sys_get_temp_dir(), 'prefix');
                   
                    // $this->pdf->Image($newpath,$arraydata["x"]+$this->arrayPageSetting["leftMargin"],$arraydata["y"]+$y_axis,
                    //     $sizedata["width"],$sizedata["height"],'',$arraydata["link"]); 

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
                        $sizedata["width"],$sizedata["height"],'',$arraydata["link"],'',false,300,'',false,false,0,false,false);
                    
                }
                elseif($this->left($path,22)==  "data:image/png;base64,")
                {
                    $imgtype="PNG";
                     $img_base64_encoded = $path;
                       $img=  str_replace('data:image/png;base64,', '', $path);

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
                        $sizedata["width"],$sizedata["height"],'',$arraydata["link"],'',false,300,'',false,false,0,false,false);  
                    // echo $path;
                    // echo '<img src="'.$path.'"/>';die;
                     // $imageContent = file_get_contents($img_base64_encoded);
                  
                  //  $newpath = tempnam(sys_get_temp_dir(), 'prefix');
                  // die;
                    // $this->pdf->Image($newpath,$arraydata["x"]+$this->arrayPageSetting["leftMargin"],$arraydata["y"]+$y_axis,
                    //     $sizedata["width"],$sizedata["height"],'',$arraydata["link"]); 
                    //       $imgtype="PNG";
                    //      // $this->pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

                    //      $img= str_replace('data:image/png;base64,', '', $path);
                    //      $imgdata = base64_decode($img);
         
                    //      $sizedata = $this->setDisplayImageSize($arraydata, $imgdata);
                    //     if(array_key_exists("scale_type", $arraydata))
                    //     {
                    //         if($arraydata["scale_type"] == "Clip")
                    //         {
                    //             $realImage = imagecreatefromstring($imgdata);
                    //             $cropImage = imagecrop($realImage, ['x' => 0, 'y' => 0, 'width' => $sizedata["width"], 'height' => $sizedata['height']]);
         
                    //             ob_start();
                    //             imagepng($cropImage);
                    //             $imgdata = ob_get_contents();

                    //             ob_end_clean();
                    //         }
                    //     }
                    
                                    
                    // $this->pdf->Image('@'.$imgdata,$arraydata["x"]+$this->arrayPageSetting["leftMargin"],$arraydata["y"]+$y_axis, 
                    //     $sizedata["width"],$sizedata["height"],'',$arraydata["link"]); 
            
                    
                } 
            }
            // echo $arraydata['path'];
            
           

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
        // echo 'lineChart';
          $this->chartobj->showChart($arraydata, $y_axis,'lineChart',$this->pdf);
        }
      elseif($arraydata["type"]=="barChart") 
      {
        // echo 'barChart';
            $this->chartobj->showChart($arraydata, $y_axis,'barChart',$this->pdf);
        }
      elseif($arraydata["type"]=="pieChart") {

            $this->chartobj->showChart($arraydata, $y_axis,'pieChart',$this->pdf);
        }
      elseif($arraydata["type"]=="stackedBarChart") 
      {
          // echo 'stackbarChart';
            $this->chartobj->showChart($arraydata, $y_axis,'stackedBarChart',$this->pdf);
        }
      elseif($arraydata["type"]=="stackedAreaChart") 
      {
        // echo 'stackareaChart';
            $this->chartobj->showAreaChart($arraydata, $y_axis,$arraydata["type"],$this->pdf);
        }
        elseif($arraydata["type"]=="Barcode"){
            
            $this->showBarcode($arraydata, $y_axis);
        }
         elseif($arraydata["type"]=="CrossTab"){
            
            $this->showCrossTab($arraydata, $y_axis);
        }

             $this->currentuuid="";
    }
    protected function drawHTMLTable($sql='')
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

    protected function subDataset_handler($data=[]){
        $this->subdataset[$data['name'].'']= $data->queryString;

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
                    $this->pointer[]=array(
                        "height"=>$out->band["height"],
                        "splitType"=>$out->band["splitType"],
                        "elementid"=>$this->elementid);
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
                        
                        $this->pointer[]=array("type"=>"band",
                            "printWhenExpression"=>(string)$out->band->printWhenExpression,
                            "height"=>(int)$object["height"],
                            "splitType"=>$object["splitType"],
                            "y_axis"=>$this->y_axis,
                            "elementid"=>$this->elementid);                        
                        $this->default_handler($object);
                    }
                    
                     $this->y_axis=$this->y_axis+$out->band["height"];   //after handle , then adjust y axis
                    $this->detailallowtill = $this->arrayPageSetting["pageHeight"] - $this->footerbandheight - 
                                                            $this->arrayPageSetting["bottomMargin"] - $this->columnfooterbandheight;

                          
                    break;

            }
                                  


        }

    }
    protected function queryString_handler($xml_path=[]) 
    {
            $this->sql =$xml_path;

            if(isset($this->arrayParameter)) {   
                foreach($this->arrayParameter as  $v => $a) {              
                    $this->sql = str_replace('$P{'.$v.'}', $a, $this->sql);
                }
            }
        }
        protected function disconnect($cndriver="mysql") {
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


  public function showPlusSymbol()
    {
        return '+';
    }

    protected function initChartLibrary()
    {        
        if($this->left(PHP_VERSION,1)=='5')
        {
            echo 'Chart in PHP 5.x is not supported.';
            die;
        }
        else
        {
            if(!$this->chartobj)
            {
                include_once __DIR__.'/PHPJasperXMLChart.inc.php';
                $this->chartobj = new PHPJasperXMLChart();
            }
        }
    }
    protected function default_handler($xml_path=[]) {
  
        $elementpath=__DIR__.'/PHPJasperXMLElement.inc.php';
        // echo $elementpath;
        include_once $elementpath;
        $element= new PHPJasperXMLElement();
        //ensure all chart element have connection

        

        foreach($xml_path as $k=>$out) {

            $this->elementid++;
            $elementres=[];
            
            switch($k) {
                case "staticText":                   
                   $elementres = $element->element_staticText($out,$this->elementid);
                    break;
                case "image":
                    $elementres = $element->element_image($out,$this->elementid);
                    // $this->element_image($out);
                    break;
                case "line":
                    $elementres = $element->element_line($out,$this->elementid);
                    // $this->element_line($out);
                    break;
                case "frame":
                    $elementres = $element->element_frame($out,$this->elementid);
                    // $this->element_frame($out);
                    // echo "this";
                    break;
                case "rectangle":
                    $elementres = $element->element_rectangle($out,$this->elementid);
                    // $this->element_rectangle($out);
                    break;
                case "ellipse":
                    $elementres = $element->element_ellipse($out,$this->elementid);
                    // $this->element_ellipse($out);
                    break;
                case "textField":
                    $elementres = $element->element_textField($out,$this->elementid);
                    // $this->element_textField($out);
                    break;
                case "stackedBarChart":
                    
                    $element->chartobj=$this->chartobj;
                    $elementres = $element->element_Chart($out,'stackedBarChart',$this->elementid);                    
                    // $this->element_Chart($out,'stackedBarChart');
                    break;
                case "barChart":
                    $this->initChartLibrary();
                    $element->chartobj=$this->chartobj;
                    $elementres = $element->element_Chart($out,'barChart',$this->elementid);                    
                    // $this->element_Chart($out,'barChart');
                    break;
                case "pieChart":
                    $this->initChartLibrary();
                    $element->chartobj=$this->chartobj;
                    $elementres = $element->element_Chart($out,'pieChart',$this->elementid);
                    
                    // $this->element_Chart($out,'pieChart');
                    break;
                case "pie3DChart":
                    $this->initChartLibrary();
                    $element->chartobj=$this->chartobj;
                    $elementres = $element->element_Chart($out,'pie3DChart',$this->elementid);
                    
                    // $this->element_Chart($out,'pie3DChart');
                    break;
                case "lineChart":
                    $this->initChartLibrary();
                    $element->chartobj=$this->chartobj;
                    $elementres = $element->element_Chart($out,'lineChart',$this->elementid);
                    
                    // $this->element_Chart($out,'lineChart');
                    break;
                case "stackedAreaChart":
                   $this->initChartLibrary();
                    $element->chartobj=$this->chartobj;
                    $elementres = $element->element_Chart($out,'stackedAreaChart',$this->elementid);
                    
                    // $this->element_Chart($out,'stackedAreaChart');
                    break;
                case "subreport":
                    $elementres = $this->element_subReport($out);

                    // $this->element_subReport($out);
                    break;
                case "break":
                    $elementres = $element->element_break($out,$this->elementid);
                    // $this->element_break($out);
                    break;
                case "componentElement":
                    $elementres = $element->element_componentElement($out,$this->elementid);
                    // $this->element_componentElement($out);
                    break;
                case "crosstab":
                    $elementres = $element->element_crossTab($out,$this->elementid);
                    // $this->element_crossTab($out);
                default:
                    
                    break;
            }

             foreach($elementres as $elementno => $elementobj)
               {
                    $this->pointer[] = $elementobj;
               }
        };      
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




    protected function checkoverflow($arraydata=[],$txt="",$maxheight=0) 
    {

        // if(isset($arraydata["pdfFontName"]) && $arraydata["pdfFontName"]!='')
            $font=$arraydata["font"];
            $pdffont=$arraydata["pdfFontName"];
            $newfont= $this->recommendFont($txt, $font,$pdffont);
            $arraydata['font']=$newfont;
            $arraydata['pdfFontName']=$newfont;
            // $arraydata['pdfFontName']='';
            $this->setFont($arraydata);            
            // echo $txt.':'.$arraydata['font'].',actual='.$this->pdf->getFontFamily().'<hr/>';
        if(isset($arraydata['printWhenExpression']))
        {
            $expressiontxt=(string)$arraydata['printWhenExpression'];        
        }
        else
        {
            $expressiontxt='';
        }
        
        $this->print_expression_result = $this->analyse_expression($expressiontxt);

        
        if(trim($expressiontxt) == '' || $this->print_expression_result!=false   ) {
        
            if($arraydata["link"]) {
                $arraydata["link"]=$this->analyse_expression($arraydata["link"],"");
            }
            
            
            if(isset($arraydata["writeHTML"]) && $arraydata["writeHTML"]==1 && $this->pdflib=="TCPDF") 
            {
                // $this->pdf->writeHTML($txt);
                $this->pdf->writeHTML($txt, true, false, false, true);
                // $html, $ln=true, $fill=false, $reseth=false, $cell=false, $align=''
                $this->pdf->Ln();
                if($this->currentband=='detail')
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
            
            elseif($arraydata["poverflow"]=="false"&&$arraydata["soverflow"]=="false") 
            {
                if($arraydata["valign"]=="M")
                {
                    $arraydata["valign"]="C";
                }
                if($arraydata["valign"]=="")
                {
                    $arraydata["valign"]="T";
                }
                                                                    
                while($this->pdf->GetStringWidth($txt) > $arraydata["width"]) 
                {
                    if($txt!=$this->pdf->getAliasNbPages() && $txt!=' '.$this->pdf->getAliasNbPages())
                    {
                        $txt=substr_replace($txt,"",-1);    
                    }
                    
                }
                            
                $x=$this->pdf->GetX();
                $y=$this->pdf->GetY();

                foreach($this->arrayParameter as  $pv => $ap)
                {
                    if($arraydata["pattern"]=='$P{'.$pv.'}')
                    {
                        $arraydata["pattern"]=$ap;    
                    }
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
                $this->pdf->MultiCell($arraydata["width"], 
                                    $arraydata["height"], 
                                    $this->formatText($txt, $arraydata["pattern"]),$arraydata["border"] ,
                                    $arraydata["align"], 
                                    $arraydata["fill"],1,'','',true,0,false,true,$maxheight);//,$arraydata["valign"]);
        
                if( $this->pdf->balancetext=='' && $this->currentband=='detail')
                {
                    if($this->maxpagey['page_'.($this->pdf->getPage()-1)]=='')
                        $this->maxpagey['page_'.($this->pdf->getPage()-1)]=$this->pdf->GetY();
                    else{
                        if($this->maxpagey['page_'.($this->pdf->getPage()-1)]<$this->pdf->GetY())
                            $this->maxpagey['page_'.($this->pdf->getPage()-1)]=$this->pdf->GetY();
                    }
                }
                
            //$this->pageFooter();
            if($this->pdf->balancetext!='' )
            {
                $this->continuenextpageText=array('width'=>$arraydata["width"], 'height'=>$arraydata["height"], 
                        'txt'=>$this->pdf->balancetext, 'border'=>$arraydata["border"] ,'align'=>$arraydata["align"], 
                        'fill'=>$arraydata["fill"],'ln'=>1,'x'=>$x,'y'=>'','reset'=>true,'streth'=>0,'ishtml'=>false,
                        'autopadding'=>true);
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
            elseif($arraydata["poverflow"]=="true") 
            {
           
                if($arraydata["valign"]=="M")
                {
                    $arraydata["valign"]="C";
                }
                if($arraydata["valign"]=="")
                {
                        $arraydata["valign"]="T"; 
                }
                                
                $this->pdf->Cell($arraydata["width"], $arraydata["height"],  $this->formatText($txt, $arraydata["pattern"]),
                        $arraydata["border"],"",$arraydata["align"],$arraydata["fill"],$arraydata["link"]."",0,true,"T",
                        $arraydata["valign"]);
                $this->pdf->Ln();
                if($this->currentband=='detail')
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
            else 
            {
            
                $this->pdf->MultiCell($arraydata["width"], $arraydata["height"], $this->formatText($txt, $arraydata["pattern"]), 
                        $arraydata["border"], $arraydata["align"], $arraydata["fill"],1,'','',true,0,true,true,$maxheight);
                if( $this->pdf->balancetext=='' && $this->currentband=='detail'){
                    if($this->maxpagey['page_'.($this->pdf->getPage()-1)]=='')
                        $this->maxpagey['page_'.($this->pdf->getPage()-1)]=$this->pdf->GetY();
                    else{
                        if($this->maxpagey['page_'.($this->pdf->getPage()-1)]<$this->pdf->GetY())
                            $this->maxpagey['page_'.($this->pdf->getPage()-1)]=$this->pdf->GetY();
                    }
                }
            if($this->pdf->balancetext!='')
            {
                $this->continuenextpageText=array('width'=>$arraydata["width"], 'height'=>$arraydata["height"], 
                    'txt'=>$this->pdf->balancetext, 'border'=>$arraydata["border"] ,'align'=>$arraydata["align"], 
                    'fill'=>$arraydata["fill"],'ln'=>1, 'x'=>$x,'y'=>'','reset'=>true,'streth'=>0,'ishtml'=>false,
                    'autopadding'=>true);
                $this->pdf->balancetext='';
                $this->forcetextcolor_b=$this->textcolor_b;
                $this->forcetextcolor_g=$this->textcolor_g;
                $this->forcetextcolor_r=$this->textcolor_r;
                $this->forcefillcolor_b=$this->fillcolor_b;
                $this->forcefillcolor_g=$this->fillcolor_g;
                $this->forcefillcolor_r=$this->fillcolor_r;
                $this->gotTextOverPage=true;

                if($this->continuenextpageText)
                {
                    $this->printlongtext($this->pdf->getFontFamily(),$this->pdf->getFontStyle(),$this->pdf->getFontSize());
                }
                
            }          



            }
        }
        $this->print_expression_result=false;
        


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
    
        public function load_xml_file($file='')
        {
                $xml=  file_get_contents($file);
                $this->load_xml_string($xml);            
        }
        protected function Rotate($type, $x=-1, $y=-1)
        {
            if($type=="")
            {
                $angle=0;    
            }            
            elseif($type=="Left")
            {
                $angle=90;    
            }            
            elseif($type=="Right")
            {
                $angle=270;    
            }            
            elseif($type=="UpsideDown")
            {
                $angle=180;    
            }
            

            if($x==-1)
            {
                $x=$this->pdf->getX();
            }
            if($y==-1)
            {
                $y=$this->pdf->getY();
            }
            if($this->angle!=0)
            {
                $this->pdf->_out('Q');
            }
            $this->angle=$angle;
            // if($angle==180 )
            // {

            // }
            // else 
            if( $angle!=0  )
            {
                $angle*=M_PI/180;
                $c=cos($angle);
                $s=sin($angle);
                $cx=$x*$this->pdf->k;
                $cy=($this->pdf->h-$y)*$this->pdf->k;
                $this->pdf->_out(sprintf('q %.5f %.5f %.5f %.5f %.2f %.2f cm 1 0 0 1 %.2f %.2f cm', $c, $s, -$s, $c, $cx, $cy, -$cx, -$cy));
            }
        }

        //  protected function print_expression($data) 
        //  {
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

        protected function field_handler($xml_path=[]) 
        {            
            $name=(string)$xml_path["name"];
            $this->arrayfield[]=$name;
            $this->arrayfieldtype[$name] = (string)$xml_path['class'];        
        }


        protected function parameter_handler($xml_path=[]) {
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
        protected function variable_handler($xml_path=[]) 
        {
            $name=(string)$xml_path['name'];
            $this->arrayVariable[$name]=array(
                "calculation"=>(string)$xml_path["calculation"],
                "target"=>$xml_path->variableExpression ,
                "class"=>$xml_path["class"] ."",
                "resetType"=>$xml_path["resetType"]."",
                "resetGroup"=>$xml_path["resetGroup"].""
                );
        }


        protected function xmlobj2arr($Data) 
        {
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
        protected function transferXMLtoArray($fileName) 
        {
            if(!file_exists($fileName))
            {
                echo "File - $fileName does not exist";
            }
            else
            {
                $xmlAry = $this->xmlobj2arr(simplexml_load_file($fileName));
                
                foreach($xmlAry['header'] as $key => $value)
                    $this->arraysqltable["$this->m"][$key]=$value;

                foreach($xmlAry['detail']['record'][$this->m] as $key2 => $value2)
                    $this->arraysqltable[$this->m][$key2]=$value2;
            }

          //  if(isset($this->arrayVariable))   //if self define variable existing, go to do the calculation
           //     $this->variable_calculation();

        }
        protected function recommendFont($utfstring='',$defaultfont='',$pdffont=""){
        
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

        if(isset($pdffont) && $pdffont !="")
        {            
             // if(file_exists($pdffont))
             // {
            return $font=$pdffont;
             // }

            
        }

        if(preg_match("/\p{Han}+/u", $utfstring))
        {
                $font="cid0cs";
        }
        elseif(preg_match("/\p{Katakana}+/u", $utfstring) || preg_match("/\p{Hiragana}+/u", $utfstring))
        {
                  $font="cid0jp";
        }            
        elseif(preg_match("/\p{Hangul}+/u", $utfstring))
        {
              $font="cid0kr";
        }
        else
        {
              $font=$defaultfont;
        }



          //echo "$utfstring $font".mb_detect_encoding($utfstring)."<br/>";
          
              return $font;//mb_detect_encoding($utfstring);
    }


    protected function getTTFFontPath($fontname='')
    {
      

        $fontpatharr=array("$this->pchartfolder/pChart/fonts",__DIR__.'/../../tcpdf/fonts');

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

        // print_r($fontpatharr);
        // echo "<hr/>";

        foreach($fontpatharr as $folder)
        {
            $smallfontname=$folder."/".$fontname.".ttf";
            $bigfontname=$folder."/".strtolower($fontname).".ttf";
        
            if(file_exists($smallfontname)){
            //echo $smallfontname;die;
                 return $smallfontname;
            }
            if(file_exists($bigfontname)){
            //echo $bigfontname;die;
                 return $bigfontname;
            }


        }

         return "$this->pchartfolder/pChart/fonts/GeosansLight.ttf";
 }
 
protected function convertNumberWithDynamicDecimal($txt='',$decimalpoint='.',$thousandseparator=',')
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

protected function convertNumber($str='')
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

protected  function convertGroup($index=0)
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

protected  function convertThreeDigit($dig1=0, $dig2=0, $dig3=0)
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

protected function convertTwoDigit($dig1=0, $dig2=0)
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

protected function convertDigit($digit=0)
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


  protected function getCentSalesPoint($value=''){

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


  protected function setDisplayImageSize($arraydata=[], $imgdata='')
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


    protected function relativebottomline($path,$y) {
        $extra=$y-$path["y1"];
        $this->display($path,$extra);
    }

    protected function updatePageNo($s) {
        return str_replace('$this->PageNo()', $this->pdf->PageNo(),$s);
    }

    protected function staticText($xml_path) {
        // $this->pointer[]=array("type"=>"SetXY","x"=>$xml_path->reportElement["x"],"y"=>$xml_path->reportElement["y"]);
    }

     protected function showBarcode($data=[],$y=0)
    {
        
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
          case 'QRCODE':
            $this->pdf->write2DBarcode($code, 'QRCODE', $x, $y, $width, $height, $style, 'N');
          break;
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


    protected function drawBorder($data)
    {
         $topattr=$data->box->topPen->attributes();
          $bottomattr=$data->box->bottomPen->attributes();
          $leftattr=$data->box->leftPen->attributes();
          $rightattr=$data->box->rightPen->attributes();
          
          // echo "<br/><br/>";
              $borderstyles=[
                      'T'=>['width'=>$topattr["lineWidth"],'style'=>(string)$topattr["lineStyle"], 'hexcolor'=>(string)$topattr["lineColor"]],
                      'B'=>['width'=>$bottomattr["lineWidth"],'style'=>(string)$bottomattr["lineStyle"],'hexcolor'=>(string)$bottomattr["lineColor"] ],
                      'L'=>['width'=>$leftattr["lineWidth"],'style'=>(string)$leftattr["lineStyle"],'hexcolor'=>(string)$leftattr["lineColor"] ],
                      'R'=>['width'=>$rightattr["lineWidth"],'style'=>(string)$rightattr["lineStyle"],'hexcolor'=>(string)$rightattr["lineColor"]]
                    ];
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
       
           
            $arrborder=['T','R','B','L'];
            $border=[];
            foreach($arrborder as $borderno =>$bordername)
            {
                if($borderstyles[$bordername]["width"]>0)
                {
                    $border[$bordername]= ['width' => $borderstyles[$bordername]["width"],'cap' => 'butt', 'join' => 'miter', 
                                    'dash' =>$borderstyles[$bordername]['dash'],'phase'=>0,'color' =>$borderstyles[$bordername]['color']];    
                }
                
            }
            return $border;

    }
    protected function analyse_expression($data='',$isPrintRepeatedValue="true",$datatype='')
    {        
            // echo $data."<br/>";

            //process using general text expression
            $jpgkey = "data:image/jpeg;base64";
            $pngkey = "data:image/png;base64,";        
            $pointerposition=$this->global_pointer+$this->offsetposition;
            $fields=$this->arraysqltable[$pointerposition];        
            
            //replace quoted string, so that can split symbol '+' later
            $matchquote=$this->pregMatch('"','"',$data);
            $replacedquotedstr=$data;
            //convert quoted string into @quoteno_1,@quoteno_2...
            foreach($matchquote[0] as $quoteno=>$quotestr)
            {
                $replacedquotedstr= str_replace($quotestr, '@quoteno_'.$quoteno, $replacedquotedstr);
            }
            
            //use '+' to split all segment of text, so that we can analyse either wish to concat or + operation
            if( ($this->left($data, 22) == $jpgkey || $this->left($data, 22) == $pngkey))
            {
                // echo '<br/>'.$replacedquotedstr.'<br/>';
            }
            else
            {
                $arrsplitedstr=explode('+',$replacedquotedstr);    
            }
            
            foreach($arrsplitedstr as $splitno => $splitedstr)
            {
                //draw value of Field, parameter and variable 
                $matchesfield=$this->pregMatch('$F{','}',$splitedstr);
                $matchesparameter=$this->pregMatch('$P{','}',$splitedstr);
                $matchesvariable=$this->pregMatch('$V{','}',$splitedstr);

                //draw parameter
                foreach($matchesparameter[1] as $parano => $paraname)
                {
                    $paravalue=$this->tweakValue($this->arrayParameter[$paraname],'tweek');
                    if(!$this->isNumber($paravalue))
                    {
                        $paravalue='"'.$paravalue.'"';
                    }
                    $splitedstr=str_replace('$P{'.$paraname.'}',$paravalue,$splitedstr);
                }
                //draw field
                foreach($matchesfield[1] as $fieldno => $fieldname)
                {
                    $fieldvalue="";
                    if(isset($fields[$fieldname]))
                    {

                        $fieldvalue=$this->tweakValue($fields[$fieldname],'tweek');
                        if(!$this->isNumber($fieldvalue))
                        {
                            $fieldvalue='"'.$fieldvalue.'"';
                        }
                    } 
                    $splitedstr=str_replace($matchesfield[0][$fieldno],$fieldvalue, $splitedstr);
                }
                //draw variable
                 foreach($matchesvariable[1] as $variableno => $variablename)
                {                          
                    
                    $variablevalue='';
                   // echo '<b style="color:red">'.$variablename.':'.$this->arrayVariable[$variablename]['ans'].'</b><br/>';
                        // for all kind of report count, group count
                    if(strpos($variablename,'_COUNT')!==false)
                    {
                        // echo 'with count:'.$variablename.'<br/>';
                        // echo 'count:';
                        switch($variablename)
                        {
                            case 'REPORT_COUNT':
                               $variablevalue =  $this->report_count;
                            break;
                            case $this->grouplist[0]["name"].'_COUNT':
                               $variablevalue = $this->group_count[$this->grouplist[0]["name"]]-1;
                            break;
                            case $this->grouplist[1]["name"].'_COUNT':
                               $variablevalue = $this->group_count[$this->grouplist[1]["name"]]-1;
                            break;
                            case $this->grouplist[2]["name"].'_COUNT':
                               $variablevalue = $this->group_count[$this->grouplist[2]["name"]]-1;
                            break;
                            case $this->grouplist[3]["name"].'_COUNT':
                                $variablevalue =$this->group_count[$this->grouplist[3]["name"]]-1;
                            break;
                            case $this->grouplist[4]["name"].'_COUNT':
                                $variablevalue =$this->group_count[$this->grouplist[4]["name"]]-1;
                            break;
                            case $this->grouplist[5]["name"].'_COUNT':
                               $variablevalue = $this->group_count[$this->grouplist[5]["name"]]-1;
                            break;
                        }

                          
                    }
                    else //others kind of variable
                    {
                        // echo 'others:';
                         if(isset($this->arrayVariable[$variablename]))
                        {
                            $variablevalue=$this->arrayVariable[$variablename]['ans'];
                            // echo '='.$$variablevalue.'<br/>';
                        }
                        else
                        {
                            $variablevalue='';
                        }
                                 
                    
                    }



                    $variablevalue=$this->tweakValue($variablevalue,'tweek');

                    
                    if(!$this->isNumber($variablevalue))
                    {
                        $variablevalue='"'.$variablevalue.'"';
                    }

                    // echo 'final variable value='.$variablevalue.'<hr/>';

                  $splitedstr=str_replace('$V{'.$variablename.'}',$variablevalue, $splitedstr);
                 
                }
             
                $arrsplitedstr[$splitno]=$splitedstr;
            }


            //merge back separated string (by symbol '+')
            $fm='';
            $isnnumber=true;
            foreach($arrsplitedstr as $pcsno => $pcstring)
            {            
                if(trim($pcstring)=='')
                {
                    continue ;
                }

                $pcstring=$this->tweakValue($pcstring,'restore');
                if(count($arrsplitedstr)>1)
                {

                    if(!$this->isNumber($pcstring))
                    {
                      $isnnumber=false;
                    }
                    if($pcsno>0)
                    {
                        $fm= $fm . '__gluestring__'.$pcstring;
                    }
                    else
                    {
                        $fm= $pcstring;  
                    }                
                }
                else
                {
                    $fm= $pcstring;
                }                        
            }    


            if( ($this->left($data, 22) == $jpgkey || $this->left($data, 22) == $pngkey))
            {
               $fm=$replacedquotedstr;
            }
            // echo $arrsplitedstr[0].'<br/>';
            // echo $fm.'<hr/>';
            //restore back quoted string
            foreach($matchquote[0] as $quoteno=>$quotestr)
            {
                $fm= str_replace( '@quoteno_'.$quoteno, $quotestr, $fm);
            }        
        


            //base 64 image can proceed easily
            
            if($datatype == "Image" && ($this->left($data, 22) == $jpgkey || $this->left($data, 22) == $pngkey))
            {
                
                 $evalstr="\$result= '".$fm."';";
                 eval($evalstr);
                 return $result;
            }

           
           if($fm=='')
           {
               return "";
           }
           else 
           {       


                  if($isnnumber==true)
                  {
                    $fm=str_replace('__gluestring__', '+', $fm);
                  }
                  else
                  {
                    // RE-MATCH $fm
                    $fm_r = explode('__gluestring__', $fm);
                    foreach ($fm_r AS $key => $value) {
                      if($this->isNumber($value)){
                        $fm_r[$key] = "'".$value."'";
                      }
                    }
                    $fm = implode('__gluestring__', $fm_r);

                    $fm=str_replace('__gluestring__', '.', $fm);
                  }
                   $fm=str_replace('convertNumber', '', $fm);
                   $firstword=$this->left( ltrim($fm) ,1);
                   // echo ',first word:'.$firstword.'<br/>';
                   if( in_array($firstword, ['.','!','=','>','<']))
                   {
                     $fm='""'.$fm;
                   }
                   // echo $fm.'<br/>';
                   // echo $fm."<br/>";
                  $evalstr="\$result= ".$fm.";";           
                  // echo $evalstr."<br/>";
                   eval($evalstr);
                    // echo $result."<hr/>";
                if($isPrintRepeatedValue=="true" ||$isPrintRepeatedValue=="")
                {
                    return $result;
                }
                else
                {
                    if($this->lastrowresult[$this->currentuuid]==$result)
                    {

                        $this->lastrowresult[$this->currentuuid]=$result;
                        return "";
                    }
                    else
                    {
                        $this->lastrowresult[$this->currentuuid] = $result;
                        return $result;
                    }
                }
            

            }
        }


     protected function variable_calculation($rowno='') {


        foreach($this->arrayVariable as $k=>$out) 
        {
            

            if($out["calculation"]!=""){
                      $out['target']=str_replace(array('$F{','}'),'',$out['target']);//,  (strlen($out['target'])-1) );                 
            }
            // echo $k.':'.$out['calculation'].','.$out['target'].',value'.$this->arraysqltable[$rowno][$out['target']].'<BR/>';    
         //   echo $out['resetType']. "<br/><br/>";
            switch($out["calculation"]) 
            {
                case "Sum":
                     $value=$this->arrayVariable[$k]["ans"];                                        
                    if($out['resetType']=='' || $out['resetType']=='None' ){
                            if(isset($this->arrayVariable[$k]['class'])&&$this->arrayVariable[$k]['class']=="java.sql.Time") 
                            {
                            //    foreach($this->arraysqltable as $table) {
                                    $value=$this->time_to_sec($value);

                                    $value+=$this->time_to_sec($this->arraysqltable[$rowno][$out['target']]);
                                    //$sum=$sum+substr($table["$out[target]"],0,2)*3600+substr($table["$out[target]"],3,2)*60+substr($table["$out[target]"],6,2);
                               // }
                                //$sum= floor($sum / 3600).":".floor($sum%3600 / 60);
                                //if($sum=="0:0"){$sum="00:00";}
                                $value=$this->sec_to_time($value);
                            }
                            else {
                                //resetGroup
                               // foreach($this->arraysqltable as $table) {
                              
                                         $value=round($value,10)+$this->arraysqltable[$rowno][$out['target']];
                                        // echo "k=$k, $value<br/>";
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
                    
                   // echo ",ans:$value<br/>";
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
        protected function formatText($txt='',$pattern='') 
        {
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
        protected function hex_code_color($value='') {
            $r=hexdec(substr($value,1,2));
            $g=hexdec(substr($value,3,2));
            $b=hexdec(substr($value,5,2));
            return array("r"=>$r,"g"=>$g,"b"=>$b,"R"=>$r,"G"=>$g,"B"=>$b);
        }

        protected function get_first_value($value='') {
            return (substr($value,0,1));
        }

        protected function right($value='', $count=0) {

            return substr($value, ($count*-1));

        }

        protected function left($string='', $count=0) {
            return substr($string, 0, $count);
        }

        protected function stringexists($string='',$keyword='')
        {
            if(strpos($string, $keyword) !== false) 
            {
                return true;
            }
            else
            {
                return false;
            }

        }
       protected function tweakValue($value,$type='tweek')
       {                    
            
            $i=0;           
            $singlequote="|_q_|";
            $doublequote="|_qq_|";

            if($type=='tweek')
            {

                    $newvalue=str_replace("'", $singlequote, $value);
                    $newvalue=str_replace('"', $doublequote, $newvalue);
            }
            else
            {
                    $newvalue=str_replace( $singlequote,"'", $value);
                    $newvalue=str_replace( $doublequote,'\"', $newvalue);                       
            }
            return $newvalue;
            
       }

       protected function isNumber($value)
       {
          if(is_numeric($value))
          {
            // ^(^\d\.\d+|^([0-9]|[1-9][0-9]*)$|^\d$|[1-9].*?|^0+$)$
            // if(preg_match("/^(^\d\.\d+|^([0-9]|[1-9][0-9]*)$|^\d$|[1-9].*?|^0+$)$/", $value))

            // Check if value with leading zero then we assume it is string
            // Example: 0, -0, 01, -01 is not number
            if(preg_match("/^(0\d+|\-0\d*)$/", $value))
            {
              return false;
            }
            // Check value is number
            // https://regex101.com/ for reference
            else if(preg_match("/^(^\d+\.?\d+|\-\d+\.?\d*|^0+$)$/", $value))
            {
              return true;
            }
            else
            {
              return false;
            }
              // echo "isNumber $value = true<br/>";
          }
          else
          {
              // echo "isNumber $value = false<br/>"; 
              return false;
          }
            
            
            // if(in_array($this->left($value,1),['1','2','3','4','5','6','7','8','9','0']))
            // {
            //     $newvalue =(double)$value+0;
            //     if($value  === ($newvalue)  )
            //     {
            //         echo "isNumber $value compare $newvalue = true<br/>";
            //         return true;
            //     }
            //     else
            //     {
            //         echo "isNumber $value compare $newvalue = false<br/>";
            //         return false;
            //     }

            // }
            // else
            // {
            //     echo "isNumber $value compare $newvalue = false<br/>";
            //     return false;
            // }
       }
       protected function pregMatch($startspliter,$endspliter,$string)
       {
            $match=[];
            $startspliter=str_replace('$', '\$', $startspliter);
            $startspliter=str_replace('{', '\{', $startspliter);

            $endspliter2=$endspliter;
            $endspliter2=str_replace('$', '\$',$endspliter2);
            $endspliter2=str_replace('}', '\}',$endspliter2);

            $regexstr='/'.$startspliter.'([^'.$endspliter.']+)'.$endspliter2.'/';
            preg_match_all($regexstr,$string,$match);
            return $match;
       }




        protected function setChartColor()
        {

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
}// end abstract class

