<?php
include_once __DIR__.'/abstractPHPJasperXML.inc.php';

//version 1.1
class PHPJasperXMLChart extends abstractPHPJasperXML
{

	public function __construct()
	{

	}

	public function showBarChart($data=[],$y_axis=0,$type='barChart')
    {

          
        include_once("$this->pchartfolder/class/pData.class.php");
        if($this->pchartfolder=="")
            $this->pchartfolder=dirname(__FILE__)."/pchart2";
            include_once("$this->pchartfolder/class/pDraw.class.php");
      if($type=='pieChart')
                include_once("$this->pchartfolder/class/pPie.class.php");

            include_once("$this->pchartfolder/class/pImage.class.php");
        if($tmpchartfolder=="")
             $tmpchartfolder=$this->pchartfolder."/cache";

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
        $titlefontsize=(int)$data['titlefontsize'];
        
    if($type=='pieChart')
    {
    	
    	$DataSet=$this->fetchPieChartDataSet($catexp,$seriesexp,$valueexp,$labelexp, $xlabel,$ylabel,$data);	
    }    
    else
    {    	    	
	    $DataSet=$this->fetchChartDataSet($catexp,$seriesexp,$valueexp,$labelexp, $xlabel,$ylabel,$data);	
    }
     
     // print_r($DataSet);

     $w=300;
     $h=300;
     
     
        $this->chart = new pImage(100,300,$DataSet);
        
        //$c = new pChart($w,$h);
        //$this->setChartColor();
      if($type=='pieChart')
      {
            $this->pieChart = new pPie($this->chart,$DataSet);
      }

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
       	echo 'before draw image';
                 $this->chart->Render($photofile);
		echo 'after draw image';
                 if(file_exists($photofile)){
                    
                    $this->pdf->Image($photofile,$x+$this->arrayPageSetting["leftMargin"],$y_axis+$y1,$w/$this->chartscaling,$h/$this->chartscaling,"PNG");

                   unlink($photofile);
                 }


    }

	public function showAreaChart($data=[],$y_axis=0,$type=''){
	    global $tmpchartfolder;

	        include_once("$this->pchartfolder/class/pData.class.php");
	        include_once("$this->pchartfolder/class/pDraw.class.php");
	        include_once("$this->pchartfolder/class/pImage.class.php");

	    
	         $tmpchartfolder=$this->pchartfolder."/cache";

	     $w=(int)$data['width'];
	     $h=(int)$data['height'];



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

	    $titlefontname=(string)$data['titlefontname'];
	    $titlefontsize=(int)$data['titlefontsize'];


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


	    $textsetting=array('DrawBox'=>FALSE,'FontSize'=>$titlefontsize,'FontName'=>"$this->pchartfolder/fonts/".$titlefontname.".ttf",'align'=>TEXT_ALIGN_TOPMIDDLE);

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


	public function fetchChartDataSet($catexp=[],$seriesexp=[],$valueexp=[],$labelexp='',$xlabel='',$ylabel='',$data=[])
	{

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

		    include_once("$this->pchartfolder/class/pData.class.php");
		    $catarr=array();
		    $DataSet = new pData();

		    $n=0;
		    $ds=trim($data['dataset']);

		    if($ds!="")
		    {
		        $sql=$this->subdataset[$ds];
		        $param=$data['param'];
		        foreach($param as $p)
		            foreach($p as $tag =>$value)
		                $sql=str_replace('$P{'.$tag.'}',$value, $sql);
		          $sql=$this->changeSubDataSetSql($sql);
		         //  die;
		    }
		    else
		    {
		        $sql=$this->sql;
		    }
		    
			
		    $result = $this->dbQuery($sql); //query from db
		   
		   
		   
		    $categoryname=array();
		    $chartdata=array();
		    $seriesname=array();
		    $i=0;
		    $maxnumber=-9999;

		    while ($row = $this->dbFetchData($result)) 
		    {

		                $j=0;
		                // echo "init i= $i loop:";

		                foreach($row as $key => $value)
		                {
		                	// echo $key.'<br/>';
		                    if($value=='')
		                    {
		                        $value=0;
		                    }
		                                                        
		                    foreach($catexp as $cindex =>$cvalue)
		                    {//loop category
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

		                // echo 'finish loop<br/>';
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
		    
		  $DataSet->setAxisName(0,$ylabel.$devidelabel);
  
  if($i==0)
      return 0;
      
        return  $DataSet;

}


public function fetchPieChartDataSet($catexp=[],$seriesexp=[],$valueexp=[],$labelexp=[],$xlabel='',$ylabel='',$data=[]){

$categorymethod="";
//echo "$catexp,$seriesexp,$valueexp,$labelexp";
include_once("$this->pchartfolder/class/pData.class.php");
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
}