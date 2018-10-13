<?php


include_once __DIR__.'/abstractPHPJasperXML.inc.php';
include_once __DIR__."/../../pchart2/pChart/pCharts.php";
include_once __DIR__."/../../pchart2/pChart/pData.php";
include_once __DIR__."/../../pchart2/pChart/pDraw.php";
include_once __DIR__."/../../pchart2/pChart/pColor.php";
include_once __DIR__."/../../pchart2/pChart/pPie.php";
include_once __DIR__."/../../pchart2/pChart/pException.php";

//version 1.1

use pChart\pColor;
use pChart\pDraw;
use pChart\pCharts;
use pChart\pData;
use pChart\pPie;
class PHPJasperXMLChart extends abstractPHPJasperXML
{
	private $defaultchartfont='Times New Roman';

	private $tmpchartfolder='';

	public function __construct()
	{
			$this->pchartfolder= __DIR__."/../../pchart2";			
			$this->tmpchartfolder=sys_get_temp_dir().'/chart';			
			if(!file_exists($this->tmpchartfolder))
			{
				mkdir($this->tmpchartfolder);
			}
	}

	
	public function showChart($data=[],$y_axis=0,$type='barChart',&$pdf)
    {              

    	// echo $type.'<br/>';
	    // if($type=='pieChart')
	    // {
	    //   	include_once("$this->pchartfolder/class/pPie.php");
	    // }                			
 		// $type='barChart';

        if(!is_writable($this->tmpchartfolder))
        {
            echo "$this->tmpchartfolder is not writable for generate chart, please contact software developer or system adminstrator";
            die;        
        }

        // print_r($data);
        
     $w=(int)($data['width']*$this->chartscaling);
     $h=(int)($data['height']*$this->chartscaling);
     $legendpos=$data['chartLegendPos'];
     $seriesexp=$data['seriesexp'];
     $catexp=$data['catexp'];
     $valueexp=$data['valueexp'];
     $labelexp=$data['labelexp'];
     $ylabel=(string)$data['ylabel'];
     $xlabel=(string)$data['xlabel'];
     $ylabel = str_replace(array('"',"'"),'',$ylabel);
     $xlabel = str_replace(array('"',"'"),'',$xlabel);
     $scalesetting=$data['scalesetting'];
     $x=$data['x'];
     $y1=$data['y'];
     $legendx=0;
     $legendy=0;

     $titlefontname=$data['titlefontname'].'';
     $titlefontsize=(int)$data['titlefontsize'];
     // echo $w.'//'.$h;
     $this->pdraw = new pDraw($w,$h);    
    if($type=='pieChart')
    {
    	
    	$this->fetchPieChartDataSet($catexp,$seriesexp,$valueexp,$labelexp, $xlabel,$ylabel,$data);	
    }    
    else
    {    	    	
	    $this->fetchChartDataSet($catexp,$seriesexp,$valueexp,$labelexp, $xlabel,$ylabel,$data);	
    }
     
     // echo '<pre>'.print_r($DataSet,true).'</pre>';
    

        
    // if($type=='pieChart')
    // {
    //         $this->pieChart = new pPie($this->pdraw,$DataSet);
    // }

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
    {
    	$valuexaxisfontsize=10;	
    }
    
    $valuexaxisfontname=$valueAxisFormat['fontname'];
    $valuexaxisfontpath=$this->getTTFFontPath($valuexaxisfontname);
    $this->pdraw->setFontProperties(array('FontName'=>$valuexaxisfontpath,'FontSize'=>$valuexaxisfontsize,"R"=>$valuexaxislabelcolor['r'],"G"=>$valuexaxislabelcolor['g'],"B"=>$valuexaxislabelcolor['b'])) ;
     

    $scalesetting=array("GridR"=>200, "GridG"=>200,"GridB"=>200,//'ScaleSpacing'=>100,
            'AxisR'=>$valuexaxislinecolor['r'],'AxisG'=>$valuexaxislinecolor['g'],'AxisB'=>$valuexaxislinecolor['b'],
		    'TickR'=>255,'TickG'=>0,'TickB'=>0,"DrawSubTicks"=>TRUE, "CycleBackground"=>TRUE);
                            
        if($type=='stackedBarChart')
        {
          $scalesetting['Mode']=  SCALE_MODE_ADDALL;
        }
        else
        {
             $scalesetting['Mode']=  SCALE_MODE_FLOATING;
        }
             
        if($type!='pieChart')           
        {
            $this->pdraw->drawScale($scalesetting);
        }
                
        $chartfontpath= $this->getTTFFontPath($this->defaultchartfont);

        $this->pdraw->setFontProperties(array('FontName'=>$chartfontpath,'FontSize'=>7));

        $pCharts = new pCharts($this->pdraw);

        if($type=='stackedBarChart')
        {
            $pCharts->drawStackedBarChart();
        }
        elseif($type=='barChart')
        {
            $pCharts->drawBarChart();
        }
        elseif($type=='lineChart')
        {
         	$pCharts->drawLineChart();
        }
        elseif($type=='pieChart')
        {
        	$PieChart = new pPie($this->pdraw);

            $PieChart->draw2DPie(($w/2),($h/2+10),
            		[
            			'WriteValues'=>true, 
            			"Border"=>TRUE,
            			"Radius"=>($h/2-20),
            			"ValueColor"=>$this->getColor(),
            		]
            	);
        }
     
       $randomchartno=rand();
       $photofile="$this->tmpchartfolder/chart$randomchartno.png";
       	// echo 'before draw image';
       $this->pdraw->Render($photofile);
		// echo 'after draw image';
         if(file_exists($photofile)){
            
            $pdf->Image($photofile,$x+$this->arrayPageSetting["leftMargin"],$y_axis+$y1,$w/$this->chartscaling,$h/$this->chartscaling,"PNG");

           unlink($photofile);
         }


    }
    /*
	public function showAreaChart($data=[],$y_axis=0,$type='',&$pdf)
	{	     
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
	    {
	        $sql=$this->sql;
	    }

	    $result = $this->dbQuery($sql); //query from db
	    $chartdata=array();
	    $i=0;
		// echo $sql."<br/><br/>";
		// print_r($result);
	    $seriesname=array();
	    // print_r($this->dbFetchData($result));
	    // echo '<hr/>';

	    while ($row = $this->dbFetchData($result)) 
	    {
	   	 
	   	 // print_r($row);
	   	 // echo '<hr/>';

	                $j=0;
	                foreach($row as $key => $value)
	                {

	                    //$chartdata[$j][$i]=$value;
	                    if($value=='')
	                    {	                    	
	                        $value=0;
	                    }
	                    if($key==str_replace(array('$F{','}'),'',$seriesexp[0]))
	                    {
	                    	array_push($seriesname,$value);	
	                    }
	                    
	                    else
	                    {
	                    	foreach($valueexp as $v => $y)
	                    	{
			                     if($key==str_replace(array('$F{','}'),'',$y))
			                     {
			                         $chartdata[$i][$j]=(int)$value;

			                           $j++;
			                     }
			                }	
	                    }
	                    


	                }
	            $i++;
	            // echo 'end loop<br/>';

	   		 }


		    // echo $i;
	        if($i==0)
	        {
	            return 0;
	        }
	        foreach($seriesname as $s=>$v)
	        {
	        	// echo $s.'='.$v.'<hr/>';
	                $DataSet->addPoints($chartdata[$s],$v);
	          //  $DataSet->AddSerie("$v");
	        }
	        // echo $ylabel;
	        $DataSet->setAxisName(0,$ylabel);        
		    $this->pdraw = new pImage($w,$h,$DataSet);
		    // echo '<pre>'.print_r($this->pdraw,true ).'</pre>';
		    $this->pdraw->drawRectangle(1,1,$w-2,$h-2);
		    $legendfontsize=8;
		    $this->pdraw->setFontProperties(array('FontName'=>$chartfontpath,'FontSize'=>$legendfontsize));
			$Title=(string)$data['charttitle']['text'];


		      switch($legendpos){
		             case "Top":
		                 $legendmode=array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL);
		                 $lgsize=$this->pdraw->getLegendSize($legendmode);
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
		                 $lgsize=$this->pdraw->getLegendSize($legendmode);
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
		                 $lgsize=$this->pdraw->getLegendSize($legendmode);
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
		                 $lgsize=$this->pdraw->getLegendSize($legendmode);
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
		                 $lgsize=$this->pdraw->getLegendSize($legendmode);
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


		       
		    

		    $this->pdraw->setGraphArea($graphareax1,$graphareay1,$graphareax2,$graphareay2);
		    $this->pdraw->setFontProperties(array('FontName'=>$chartfontpath,'FontSize'=>8));
		    
		    $ScaleSpacing=5;
		    $scalesetting= $scaleSettings = array("XMargin"=>10,"YMargin"=>10,"Floating"=>TRUE,"GridR"=>200,"GridG"=>200,
		            "GridB"=>200,"DrawSubTicks"=>TRUE,"CycleBackground"=>TRUE,"Mode"=>SCALE_MODE_ADDALL_START0,'ScaleSpacing'=>$ScaleSpacing);

		    $this->pdraw->drawScale($scalesetting);

		    $this->pdraw->drawLegend($legendx,$legendy,$legendmode);


		    $Title = str_replace(array('"',"'"),'',$data['charttitle']['text']);

		    if($Title!=''){
		        $titlefontsize+0;
		    if($titlefontsize==0)
		        $titlefontsize=8;
		     if($titlefontname=='')
		        $titlefontname='calibri';
			$titlefontname=strtolower($titlefontname);


		    $textsetting=array('DrawBox'=>FALSE,'FontSize'=>$titlefontsize,'FontName'=>"$this->pchartfolder/fonts/".$titlefontname.".ttf",'align'=>TEXT_ALIGN_TOPMIDDLE);

		    $this->pdraw->drawText($w/3,($titlefontsize+10),$Title,$textsetting);
		    }

		    $this->pdraw->setFontProperties(array('FontName'=>$chartfontpath,'FontSize'=>7));
			$this->pdraw->drawStackedAreaChart(array("Surrounding"=>60));
		   	$randomchartno=rand();
		    $photofile="$this->tmpchartfolder/chart$randomchartno.png";

		    $this->pdraw->Render($photofile);

	         if(file_exists($photofile)){
		           $pdf->Image($photofile,$x+$this->arrayPageSetting["leftMargin"],$y_axis+$y1,$w,$h,"PNG");

	    	        unlink($photofile);
	         }
	}
	*/



		private function changeSubDataSetSql($sql=''){

			foreach($this->currentrow as $name =>$value)
			{
		        $sql=str_replace('$F{'.$name.'}',$value,$sql);
			}

			foreach($this->arrayParameter as $name=>$value)
			{
			    $sql=str_replace('$P{'.$name.'}',$value,$sql);
			}

			foreach($this->arrayVariable as $name=>$value)
			{
			    $sql=str_replace('$V{'.$value['target'].'}',$value['ans'],$sql);
			}
		     return $sql;


	}


	public function fetchChartDataSet($catexp=[],$seriesexp=[],$valueexp=[],$labelexp='',$xlabel='',$ylabel='',$data=[])
	{

		 
		$catexp=array_unique($catexp);
		$catcount=count($catexp);
		$seriescount=count($valueexp);
		$categoryname=$catexp[0];
		if($catcount==0)
		{
			echo 'You shall define at least 1 category expression in chart.';
			die;
		}
		else if($catcount>1)
		{
			echo 'You defined > 1 category expression, at the moment phpjasperxml only support 1 unique category expression.';
			die;	
		}

		if($seriescount==0)
		{
			echo 'You shall define at least 1 series expression in chart.';
			die;
		}
		
		//=[],$seriesexp=[],$valueexp=[],$labelexp=''

	    // include_once("$this->pchartfolder/pChart/pData.php");
	    $catarr=array();
	    // $DataSet = new pData();

	    $n=0;
	    $ds=trim($data['dataset']);
	    if($data['sql']!="")
	    {
	        $sql=$data['sql'];
	        $param=$data['param'];
	        foreach($param as $p)
	        {
	        	foreach($p as $tag =>$value)
	            {
	                $sql=str_replace('$P{'.$tag.'}',$value, $sql);
	            }
	        }		            		        
	    }
	    else
	    {
	        $sql=$this->sql;
	    }
		    
			// echo $sql;
	    $result = $this->dbQuery($sql); //query from db

	   
	    foreach($catexp as $cat_no =>$catname)
	    {
	    	$catexp[$cat_no]=(string)$catname;	
	    	$catexp[$cat_no]=str_replace(['$F{','}','$P{','$V{'], '', $catexp[$cat_no]);

	    }

	 
	   
	    $categoryfield='';
	    $categoryarr=array();
	    $chartdata=array();
	    $seriesnamearr=array();
	    $i=0;
	    $maxnumber=-9999;
	    $minnumber=9999;
	    
	    $arrdata=[];
	    //loop category of record, it use to draw axis
	    while ($row = $this->dbFetchData($result)) 
	    {
	    	$arrdata[]=$row;	    	  
            array_push($categoryarr,$row[$categoryname]);
            $i++;
	    }
	    
	    $seriesarr=[];
    	foreach($arrdata as $row_id => $row)
    	{
    		foreach($valueexp as $series_no =>$seriesname)
    		{
    			if(!isset($seriesarr[$seriesname]))
    			{
    				$seriesarr[$seriesname]=[];
    			}

	    		array_push($seriesarr[$seriesname],$row[$seriesname]);
    		}	
    	}	   	

        $this->pdraw->myData->addPoints($categoryarr,"categoryaxis");  
        $this->pdraw->myData->setSerieDescription('categoryaxis',$xlabel);
        $this->pdraw->myData->setAbscissa('categoryaxis');
        $newchartdata=array();
        $devidevalue=1;
        $devidelabel='';

        if($maxnumber>1000000)
        {
        	$devidevalue= 1000000;
        	$devidelabel=' (KK)';
        }
        elseif($maxnumber>1000)
        {
        	$devidevalue= 1000;
        	$devidelabel=' (K)';
        }
                    
        foreach($seriesarr as $seriesname=>$seriesdata)
        {
        	 $this->pdraw->myData->addPoints($seriesdata,$seriesname);
        }
		          		    
		$this->pdraw->myData->setAxisName(0,$ylabel.$devidelabel);
  
	  	if($i==0)
	  	{
	  		return 0;	
	  	}
	    return  true;
	}


	public function fetchPieChartDataSet($catexp=[],$seriesexp=[],$valueexp=[],$labelexp=[],$xlabel='',$ylabel='',$data=[])
	{

		$categorymethod="";
		//echo "$catexp,$seriesexp,$valueexp,$labelexp";
		
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


		        $this->pdraw->myData->addPoints($chartdata,"valuepoint");   
		        $this->pdraw->myData->setSerieDescription('valuepoint',"Value");

		        $this->pdraw->myData->addPoints($seriesname,"label");
		        $this->pdraw->myData->setAbscissa("label");
		        // $DataSet->setAbscissa('label');
		        //  $DataSet->setAxisName(0,$ylabel);
		  if($i==0)
		  {
		  		  return 0;
		  }
		    
		      
		        return  true;

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



		        
		         //$this->pdraw->setFontProperties(array('FontName'=>$charttitlefontpath,'FontSize'=>7));

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
		                
		                // echo 'font='.$titlesetting['fontname'].':';
		        if(isset($Title)&& $Title!=''){
		            $charttitlefontpath=$this->getTTFFontPath($titlesetting['fontname']);
		            // echo "'$charttitlefontpath'<br/>";
		            $this->pdraw->setFontProperties(array('FontName'=>$charttitlefontpath,'FontSize'=>$titlesetting['fontsize'],"R"=>$titlecolor['r'],"G"=>$titlecolor['g'],"B"=>$titlecolor['b'],'Align'=>TEXT_ALIGN_TOPMIDDLE,));

		            $this->pdraw->drawText($titlew,$titleh,$Title,$titletextsetting);
		        }

		          switch($legendsetting['position']){
		                 case "Top":
		                     $legendmode=array("Style"=>LEGEND_BOX,"Mode"=>LEGEND_HORIZONTAL);
		                     $lgsize=$this->pdraw->getLegendSize($legendmode);
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
		                     $lgsize=$this->pdraw->getLegendSize($legendmode);
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
		                     $lgsize=$this->pdraw->getLegendSize($legendmode);
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
		                     $lgsize=$this->pdraw->getLegendSize($legendmode);
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
		                     $lgsize=$this->pdraw->getLegendSize($legendmode);
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
		            $this->pdraw->setFontProperties(array('FontName'=>$chartlegendfontpath,'FontSize'=>$legendsetting['fontsize'],"R"=>$legendcolor['r'],"G"=>$legendcolor['g'],"B"=>$legendcolor['b'],'Align'=>TEXT_ALIGN_TOPMIDDLE));        
		            $legendmode["R"]=$legendBGcolor['r'];
		            $legendmode["G"]=$legendBGcolor['g'];
		            $legendmode["B"]=$legendBGcolor['b'];
		        
		        if($type!='pieChart') {
		        $this->pdraw->setGraphArea($graphareax1,$graphareay1,$graphareax2,$graphareay2);
		        
		        if($legendsetting){
		            
		            $this->pdraw->drawLegend($legendx,$legendy,$legendmode);
		        }
		        }else{
		           //     $this->pieChart->drawPieLegend($legendx,$legendy,$legendmode);
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
		            $this->pdraw->setFontProperties(array('FontName'=>$chartsubtitlefontpath,'FontSize'=>$subtitlesetting['fontsize'],"R"=>$subtitlecolor['r'],"G"=>$subtitlecolor['g'],"B"=>$subtitlecolor['b'],'Align'=>TEXT_ALIGN_TOPMIDDLE,));
		            $this->pdraw->drawText($subtitlew,$subtitleh,$subTitle,$titletextsetting);
		        }
		        
		        //echo "ASDSAD";die;
		        
		        return array(  $graphareax1, $graphareay1,  $graphareax2, $graphareay2);
	}


	/**
	*	define chart parameter, not yet render chart
	*	@param $data array/object of data
	*	@param $type string, chart type
	*	@return array of elements
	*/
	public function element_Chart($data,$type='',$elementid)
    {

        //check can write chart image or not
        if(file_exists($this->tmpchartfolder))
        {
            if(!is_writable($this->tmpchartfolder))
            {
                echo $this->tmpchartfolder.' is not writable.' ;die ;
            }
        }
        else
        {
            echo $this->tmpchartfolder.' does not exists.'  ;die;
        }

        //define general chart size and position
         $mydata=[];
         $this->elementid=$elementid;
         $height=(int)$data->chart->reportElement["height"];
         $width=(int)$data->chart->reportElement["width"];
         $x=(int)$data->chart->reportElement["x"];
         $y=(int)$data->chart->reportElement["y"];
         $charttitle=[];
         $chartsubtitle=[];
         $chartlegend=array();
         $theme=$data->chart['theme'];
         
         

         //prepare chart title setting
         if($data->chart->chartTitle->titleExpression!="")
         {
	          $charttitle['text']=$data->chart->chartTitle->titleExpression."";
	          $charttitle['position']= ( (string)$data->chart->chartTitle['position'] !="" ? 
	                        (string)$data->chart->chartTitle['position']:'');
	          $charttitle['fontname']=( (string)$data->chart->chartTitle->font['fontName'] ? 
	                    (string)$data->chart->chartTitle->font['fontName']: $this->defaultchartfont);

	          $charttitle['fontsize']=( (int)$data->chart->chartTitle->font['size'] ?
	                            (int)$data->chart->chartTitle->font['size']: 10);
	          $charttitle['color']=((string)$data->chart->chartTitle['color'] ? 
	          				(string)$data->chart->chartTitle['color'] : '000000');
	          $charttitle['isBold']=((bool)$data->chart->chartTitle['isBold'] ? true:false);
	          $charttitle['isUnderline']=((bool)$data->chart->chartTitle['isUnderline'] ? true : false);
	          $charttitle['isItalic']=((bool)$data->chart->chartTitle['isItalic'] ? true : false);
         }
         else
         {
             $charttitle=null;
         }
         

         //prepare chart sub title setting
          if((string)$data->chart->chartSubtitle->subtitleExpression!='')
          {
              
            $chartsubtitle['fontname']=((string)$data->chart->chartSubtitle->font['fontName'] ?
            			 "": $this->defaultchartfont);             
            $chartsubtitle['text']=((string)$data->chart->chartSubtitle->subtitleExpression ? "":'');
            $chartsubtitle['fontsize']=((int)$data->chart->chartSubtitle->font['size']+10 ? "":9);
            $chartsubtitle['color']=((string)$data->chart->chartSubtitle['color'] ? "":'000000');
            $chartsubtitle['isBold']=((bool)$data->chart->chartSubtitle['isBold'] ?true:false);
            $chartsubtitle['isUnderline']=((bool)$data->chart->chartSubtitle['isUnderline'] ?true:false);
            $chartsubtitle['isItalic']=((bool)$data->chart->chartSubtitle['isItalic']?true:false);
          }
          else
          {
              $chartsubtitle=null;
          }
          

          //prepare chart legend
          if($data->chart['isShowLegend']=='true' || $data->chart['isShowLegend']=='')
          {
            $chartlegend['position']=( (string)$data->chart->chartLegend['position'] ? "" : "Right" );
            $chartlegend['color']=(  (string) $data->chart->chartLegend['textColor'] ? "" : '#000000');
            if($data->chart->chartLegend['backgroundColor'].""=="")
            {
                $chartlegend['backgroundColor']='#FFFFFF';
            }
            else
            {
                $chartlegend['backgroundColor']=  (string)$data->chart->chartLegend['backgroundColor'];
            }

            $chartlegend['size']=( (int)$data->chart->chartLegend['size'] ? "": 9);
            $chartlegend['fontname']=(  (string)$data->chart->chartLegend['fontName']? : $this->defaultchartfont);
            $chartlegend['isUnderline']=($data->chart->chartLegend['isUnderline'] ? true : false);
            $chartlegend['isBold']=( (bool) $data->chart->chartLegend['isBold'] ? true : false);
            $chartlegend['isItalic']=( (bool)$data->chart->chartLegend['isItalic'] ? true : false);
         }
         else
         {
             
             $chartlegend=null;
             
         }
         // echo '<pre>'.print_r($data->categoryDataset,true).'</pre>';

        $dataset='';
                
        if($type=='pieChart')
        {
          if(isset($data->pieDataset->dataset))
          {
        	$dataset=(string)$data->pieDataset->dataset->datasetRun['subDataset'];
          }
          $dataset=$data->pieDataset->dataset->datasetRun['subDataset'];
          $seriesexp=$data->pieDataset->keyExpression;
          $valueexp=$data->pieDataset->valueExpression;
          $bb=$data->pieDataset->dataset->datasetRun['subDataset'];
          $sql=$this->arraysubdataset[$bb]['sql'];
        }
        else
        {
          $i=0;
          $seriesexp=array();
          $catexp=array();
          $valueexp=array();
          $labelexp=array();
          if(isset($data->categoryDataset->dataset))
          {
        	$dataset=(string)$data->categoryDataset->dataset->datasetRun['subDataset'];
          }
          $subcatdataset=$data->categoryDataset;
          
          foreach($subcatdataset as $cat => $catseries)
          {
            foreach($catseries as $a => $series)
            {
            	
            	$series->categoryExpression=(string)$series->categoryExpression;            	
               if(isset($series->categoryExpression))
               {
               		$seriesExpression=(string)$series->seriesExpression.'';
               		$categoryExpression=(string)$series->categoryExpression.'';
               		$valueExpression=(string)$series->valueExpression.'';
               		$labelExpression=(string)$series->labelExpression.'';

                    $seriesExpression=  str_replace( ['$F{','}','$P{','$V{'], '',$seriesExpression.'');
                    $categoryExpression=  str_replace(['$F{','}','$P{','$V{'], '',$categoryExpression.'');
                    $valueExpression=  str_replace(['$F{','}','$P{','$V{'], '',$valueExpression);
                    $labelExpression=  str_replace(['$F{','}','$P{','$V{'], '',$labelExpression);
                    array_push( $seriesexp,$seriesExpression);
                    array_push( $catexp,$categoryExpression);
                    array_push( $valueexp,$valueExpression);
                    array_push( $labelexp,$labelExpression);
               }

            }

          }
          // $seriesexp=array_unique($seriesexp);
          // $catexp=array_unique($catexp);
          // $valueexp=array_unique($valueexp);
          // $labelexp=array_unique($labelexp);
          // print_r($catexp);
          // echo "<hr/>";
          // print_r($seriesexp);
          // echo "<hr/>";
          // print_r($valueexp);
          // echo "<hr/>";
          // print_r($labelexp);
          // echo "<hr/>";
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
                $valueAxisFormat['linecolor']=(string)$data->barPlot->valueAxisFormat->axisFormat['axisLineColor'];
                $valueAxisFormat['labelcolor']=(string)$data->barPlot->valueAxisFormat->axisFormat['tickLabelColor'];
                $valueAxisFormat['fontname']=(string)$data->barPlot->valueAxisFormat->axisFormat->tickLabelFont->font['fontName'];
                $categoryAxisFormat['linecolor']=(string)$data->barPlot->categoryAxisFormat->axisFormat['axisLineColor'];
                $categoryAxisFormat['labelcolor']=(string)$data->barPlot->categoryAxisFormat->axisFormat['tickLabelColor'];
                $categoryAxisFormat['fontname']=(string)$data->barPlot->categoryAxisFormat->axisFormat->tickLabelFont->font['fontName'];
                break;
            case "lineChart":
                $ylabel=$data->linePlot->valueAxisLabelExpression;
                $xlabel=$data->linePlot->categoryAxisLabelExpression;
                $maxy=$data->linePlot->rangeAxisMaxValueExpression;
                $miny=$data->linePlot->rangeAxisMinValueExpression;
                $valueAxisFormat['linecolor']=(string)$data->linePlot->valueAxisFormat->axisFormat['axisLineColor'];
                $valueAxisFormat['labelcolor']=(string)$data->linePlot->valueAxisFormat->axisFormat['tickLabelColor'];
                $valueAxisFormat['fontname']=(string)$data->linePlot->valueAxisFormat->axisFormat->tickLabelFont->font['fontName'];
                $categoryAxisFormat['linecolor']=(string)$data->linePlot->categoryAxisFormat->axisFormat['axisLineColor'];
                $categoryAxisFormat['labelcolor']=(string)$data->linePlot->categoryAxisFormat->axisFormat['tickLabelColor'];
                $categoryAxisFormat['fontname']=(string)$data->linePlot->categoryAxisFormat->axisFormat->tickLabelFont->font['fontName'];

                $showshape=$data->linePlot["isShowShapes"];
                break;
             case "stackedAreaChart":
                $ylabel=$data->areaPlot->valueAxisLabelExpression;
                $xlabel=$data->areaPlot->categoryAxisLabelExpression;
                $maxy=$data->areaPlot->rangeAxisMaxValueExpression;
                $miny=$data->areaPlot->rangeAxisMinValueExpression;
                $valueAxisFormat['linecolor']=(string)$data->areaPlot->valueAxisFormat->axisFormat['axisLineColor'];
                $valueAxisFormat['labelcolor']=(string)$data->areaPlot->valueAxisFormat->axisFormat['tickLabelColor'];
                $valueAxisFormat['fontname']=(string)$data->areaPlot->valueAxisFormat->axisFormat->tickLabelFont->font['fontName'];
                $valueAxisFormat['size']=(int)$data->areaPlot->valueAxisFormat->axisFormat->tickLabelFont->font['size']+0;
                $categoryAxisFormat['linecolor']=(string)$data->areaPlot->categoryAxisFormat->axisFormat['axisLineColor'];
                $categoryAxisFormat['labelcolor']=(string)$data->areaPlot->categoryAxisFormat->axisFormat['tickLabelColor'];
                $categoryAxisFormat['fontname']=(string)$data->areaPlot->categoryAxisFormat->axisFormat->tickLabelFont->font['fontName'];
                $categoryAxisFormat['size']=(int)$data->areaPlot->categoryAxisFormat->axisFormat->tickLabelFont->font['size']+0;
                 break;
          }
          


          $param=array();
          if(isset($data->categoryDataset->dataset->datasetRun->datasetParameter ))
          {
            foreach($data->categoryDataset->dataset->datasetRun->datasetParameter as $tag=>$value)
            {
              $param[]=  array($value['name']=>$value->datasetParameterExpression);
            }  
          }
          
          if($maxy!='' && $miny!='')
          {
              $scalesetting=array(0=>array("Min"=>$miny,"Max"=>$maxy));
          }
          else
          {
              $scalesetting="";
          }

        $mydata[]=array('type'=>$type,'x'=>$x,'y'=>$y,'height'=>$height,'width'=>$width,'charttitle'=>$charttitle,
            'chartsubtitle'=> $chartsubtitle,'chartlegend'=> $chartlegend, 'dataset'=>$dataset,'seriesexp'=>$seriesexp,
             'catexp'=>$catexp,'valueexp'=>$valueexp,'labelexp'=>$labelexp,'param'=>$param,'sql'=>$sql,
             'xlabel'=>$xlabel,'showshape'=>$showshape,  'ylabel'=>$ylabel,'scalesetting'=>$scalesetting,
             'valueAxisFormat'=>$valueAxisFormat,'categoryAxisFormat'=>$categoryAxisFormat,"elementid"=>$this->elementid);
        
        return $mydata;

    }

   private function getColor($hexcolor='000000',$alpha=100)
   {
   	    $c1=(Float)hexdec(substr($hexcolor, 1,2));
        $c2=(Float)hexdec(substr($hexcolor, 3,2));
		$c3=(Float)hexdec(substr($hexcolor, 5,2));
   		return new pChart\pColor($c1,$c2,$c3,(Float)$alpha);
   }
}