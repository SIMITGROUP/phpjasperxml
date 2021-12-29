<?php

namespace simitsdk\phpjasperxml;

use SimpleXMLElement;

trait PHPJasperXML_elements
{    

    /************************************************************************************/
    /*************************** supported elements *************************************/
    /************************************************************************************/
    /**
     * initialize line element's parameter in report, combine simple xml object attribute into $prop
     * @param array $prop properties setting
     * @param object $obj element object in simplexml 
     * @return array $prop 
     */
    protected function element_line(array $prop, object $obj): array
    {        
        if(gettype($obj->graphicElement->pen)=='object')
        {
            $prop=$this->appendprop($prop,$obj->graphicElement->pen);
        }
        return $prop;
    }

    /**
     * draw line element in report
     * @param string $uuid unique id
     * @param array $prop
     */
    protected function draw_line(string $uuid,array $prop)
    {
        $this->output->draw_line($uuid,$prop);
    }

    /**
     * initialize rectangle element's parameter in report, combine simple xml object attribute into $prop
     * @param array $prop properties setting
     * @param object $obj element object in simplexml 
     * @return array $prop 
     */
    protected function element_rectangle(array $prop, object $obj): array
    {
        if(isset($obj->graphicElement->pen))
        {
            $prop=$this->appendprop($prop,$obj->graphicElement->pen);
        }        
        return $prop;
    }

    /**
     * draw rectangle element in report
     * @param string $uuid unique id
     * @param array $prop
     */
    public function draw_rectangle(string $uuid,array $prop){
        $this->output->draw_rectangle($uuid,$prop);
    }

    /**
     * initialize ellipse element's parameter in report, combine simple xml object attribute into $prop
     * @param array $prop properties setting
     * @param object $obj element object in simplexml 
     * @return array $prop 
     */
    protected function element_ellipse(array $prop, object $obj): array
    {
        if(isset($obj->graphicElement->pen))
        {
            $prop=$this->appendprop($prop,$obj->graphicElement->pen);        
        }
        
        return $prop;
    }

    /**
     * draw ellipse element in report
     * @param string $uuid unique id
     * @param array $prop
     */
    public function draw_ellipse(string $uuid,array $prop)
    {
        
        $this->output->draw_ellipse($uuid,$prop);
    }

    /**
     * initialize image element's parameter in report, combine simple xml object attribute into $prop
     * @param array $prop properties setting
     * @param object $obj element object in simplexml 
     * @return array $prop 
     */
    protected function element_image(array $prop, object $obj): array
    {
        $prop['imageExpression']= (string)$obj->imageExpression;        
        $prop = $this->addBorders($prop,$obj);
        return $prop;
    }

    /**
     * draw image element in report
     * @param string $uuid unique id
     * @param array $prop
     */
    protected function draw_image(string $uuid,array $prop)
    {
        $imgsrc = $this->executeExpression($prop['imageExpression']);
        // $imgsrc = $prop['imageExpression'];
        $this->path;
        // $this->console(strlen($imgsrc));
        $testpath = $this->path.'/'.$imgsrc;
        if($this->left($imgsrc,4)=='http')
        {
            $prop['imageExpression'] = $imgsrc;
        }
        else if(file_exists($imgsrc))
        {
            $prop['imageExpression'] = $imgsrc;
        }
        else if(file_exists($testpath))
        {
            $prop['imageExpression']= $testpath;
        }
        else if(strlen($imgsrc) > 500) //highly possible base64image
        {
            $replace_plus = '----plus-----';
            $tmpstr = str_replace('+',$replace_plus,$prop['imageExpression']);
            $tmpstr = $this->executeExpression($tmpstr);
            $tmpstr = str_replace($replace_plus,'+',$tmpstr);
            $tmpbase64 = str_replace(['data:image/jpeg;base64,','data:image/png;base64,','data:image/png;base64,'],'',$tmpstr);
            $imgdata = base64_decode($tmpbase64);
            $prop['imageExpression'] ='@'.$imgdata;
        }        
        $this->output->draw_image($uuid,$prop);
    }

    /**
     * initialize page break element's parameter in report, combine simple xml object attribute into $prop
     * @param array $prop properties setting
     * @param object $obj element object in simplexml 
     * @return array $prop 
     */
    protected function element_break(array $prop, object $obj): array
    {
        print_r($prop);
        return $prop;
    }
    
    /**
     * add page break element in report
     * @param string $uuid unique id
     * @param array $prop
     */
    public function draw_break(string $uuid,array $prop){
        $type = $prop['type'];
        $this->output->draw_break($uuid,$prop,function() use ($type)
        {
            if($type=='Column')
            {
                $this->nextColumn();
            }
            else
            {
                $this->newPage();
            }

            
        });
        
    }
    
    /**
     * initialize static text element's parameter in report, combine simple xml object attribute into $prop
     * @param array $prop properties setting
     * @param object $obj element object in simplexml 
     * @return array $prop 
     */
    protected function element_staticText(array $prop, object $obj): array
    {
        if(isset($obj->textElement->font))
        {
            $prop = $this->appendprop($prop,$obj->textElement->font);
        }        
        $prop = $this->addBorders($prop,$obj);
        if(isset($obj->text))
        {
            $prop['text']=(string)$obj->text;
        }        
        return $prop;
    }


    /**
     * draw static text in report
     * @param string $uuid unique id
     * @param array $prop
     */
    public function draw_staticText(string $uuid,array $prop,bool $isTextField=false){
        // $link = $prop['hyperlinkReferenceExpression']??'';
        
        // if(!empty($link))
        // {
        //     // $this->console("link $link");
        //     $prop['hyperlinkReferenceExpression'] = $this->executeExpression($link);
        // }
        $this->output->draw_staticText($uuid,$prop);
    }

    /**
     * initialize textField element's parameter in report, combine simple xml object attribute into $prop
     * @param array $prop properties setting
     * @param object $obj element object in simplexml 
     * @return array $prop 
     */
    protected function element_textField(array $prop, object $obj): array
    {      
        $prop = $this->element_staticText($prop,$obj);          
        $prop['textFieldExpression']=$obj->textFieldExpression;  
        if(isset($obj->patternExpression))      
        {
            $prop['patternExpression']=(string)$obj->patternExpression;
        }
        return $prop;
    }

    /**
     * draw line textField in report
     * @param string $uuid unique id
     * @param array $prop
     */
    public function draw_textField(string $uuid,array $prop)
    {
        $prop['evaluationTime'] = $prop['evaluationTime']?? '';    
        $prop['textFieldExpression']=$this->executeExpression($prop['textFieldExpression'],0,$prop['evaluationTime']);
        // $link = $prop['hyperlinkReferenceExpression']??'';        
        if(!empty($prop['patternExpression']))
        {
            $prop['pattern']= $this->executeExpression($prop['patternExpression']);
        }
        // if(!empty($link))
        // {
        //     $prop['hyperlinkReferenceExpression'] = $this->executeExpression($link);
        // }
        $this->output->draw_textField($uuid,$prop,function(){
            $this->newPage();
        });
    }

    /**
     * initialize frame element's parameter in report, combine simple xml object attribute into $prop
     * @param array $prop properties setting
     * @param object $obj element object in simplexml 
     * @return array $prop 
     */
    protected function element_frame(array $prop, object $obj): array
    {
        $prop = $this->addBorders($prop,$obj);
        // if(isset($obj->box))
        // {
        //     $prop=$this->appendprop($prop,$obj->box);
        //     if(isset($obj->box->pen))
        //     {
        //         $prop=$this->appendprop($prop,$obj->box->pen);
        //     }         
        // }        
        return $prop;
    }

    /**
     * draw line frame in report
     * @param string $uuid unique id
     * @param array $prop
     */
    /**
     * draw rectangle element in report
     * @param string $uuid unique id
     * @param array $prop
     */
    public function draw_frame(string $uuid,array $prop){
        $this->output->draw_frame($uuid,$prop);
    }
    

    /**************************************************************************************/
    /*************************** unsupported elements *************************************/
    /**************************************************************************************/
    protected function element_genericElement(array $prop, object $obj): array
    {
        return $prop;
    }    
    public function draw_genericElement(string $uuid,array $prop)
    {
        $this->output->draw_unsupportedElement($uuid,$prop);
    }

    // protected function element_frame(array $prop, object $obj): array
    // {
    //     return $prop;
    // }
    // public function draw_frame(string $uuid,array $prop)
    // {
    //     $this->output->draw_unsupportedElement($uuid,$prop);
    // }
    protected function element_subreport(array $prop, object $obj): array
    {
        $prop['subreportExpression']=(string)$obj->subreportExpression;
        $prop['connectionExpression']=(string)$obj->connectionExpression;  
        $paras = [];
        foreach($obj->subreportParameter as $index=>$paraobj)
        {
            $paraname = $this->prop($paraobj)['name'];
            $paramapto =  (string)$paraobj->subreportParameterExpression;
            $paras[$paraname]=$paramapto;
            
        }
        $prop['paras']=$paras;
        
        return $prop;
    }
    public function draw_subreport(string $uuid,array $prop)
    {
        // echo "draw_subreport<hr>";
        if($this->output->supportSubReport())
        {

            
            $subreport = new PHPJasperXML();        
            $subreportExpression = $this->executeExpression($prop['subreportExpression']);        
            if($this->left($subreportExpression,5)=='<?xml')
            {
                $subreport->load_xml_string($subreportExpression);
            }
            else
            {
                $subreportExpression = str_replace('.jasper','.jrxml',$subreportExpression);
                $filename = $this->path.'/'.$subreportExpression;
                $subreport->load_xml_file($filename);
            }
            

            $connectionExpression =  $this->executeExpression($prop['connectionExpression']);
            $connection = [];
            if($connectionExpression=='REPORT_CONNECTION')
            {
                $connection = $this->connectionsetting;
                if(isset($connection['data']))
                {
                    if(count($connection['data'])>0)
                    {
                        $connection['data'] = [['a'=>1]];
                    }
                    else
                    {
                        $connection['data']=[];
                    }
                    
                }
            }
            else
            {
                $connection = $connectionExpression;            
            }
            
            $paras = [];
            foreach($prop['paras'] as $pname=>$pexpression)
            {
                $paras[$pname]=$this->executeExpression($pexpression);
            }
            
            
            $subreport
                ->setParameter($paras)
                ->setDataSource($connection)
                ->runSubReport($prop,$this->output);                
            ;
        }
    }
    protected function element_componentElement(array $prop, SimpleXMLElement $obj): array
    {        
        $subtype='';
        $childtypes = ['jr','c','sc','cvc'];
        foreach($childtypes as $childtype)
        {
            $children = $obj->children($childtype,true);            
            foreach($children as $k=>$v)
            {
                $subtype=$k;
                $prop['subtype']=$k;
                
                switch($k)
                {   
                    case 'list':
                    case 'table':
                    case 'map':
                    case 'spiderChart':
                    case 'customvisualization':
                        //misc component
                    break;

                    //all the rest is barcode
                    case 'barbecue':                        
                        //checksumRequired
                        //drawText
                        //barWidth
                        //barHeight
                        //rotation : Left, UpsideDown, None, Right,''
                        $barcodeprop = $this->prop($v);
                        $barcodeprop['codeExpression']=(string)$v->codeExpression;                        
                        $barcodeprop['barcodetype']=$barcodeprop['type'];
                        foreach($barcodeprop as $key=>$value)
                        {
                            $prop[$key]=$value;
                        }
                    break;                    
                    default: 

                        //orientation: down, left, right, up,''
                        //drawText
                        $barcodeprop = $this->prop($v);
                        $barcodeprop['barcodetype']=$k;                    
                        $barcodeprop['codeExpression']=(string)$v->codeExpression;
                        $barcodeprop['drawText']=false;
                        foreach($barcodeprop as $key=>$value)
                        {
                            $prop[$key]=$value;
                        }
                    break;
                }
            }  
        }
        
        
        return $prop;
    }
    public function draw_componentElement(string $uuid,array $prop)
    {
        if(isset($prop['barcodetype']) && isset($prop['codeExpression']))
        {
            $prop['codeExpression'] = $this->executeExpression($prop['codeExpression']);
            $this->output->draw_barcode($uuid,$prop);
        }
        else
        {
            $this->output->draw_unsupportedElement($uuid,$prop);
        }
        
    }

    protected function element_crosstab(array $prop, object $obj): array
    {        
        return $prop;
    }
    public function draw_crosstab(string $uuid,array $prop)
    {
        $this->output->draw_unsupportedElement($uuid,$prop);
    }
    
    public function element_chart(array $prop, object $obj): array
    {        
        return $prop;
    }

    public function draw_chart(string $uuid,array $prop)
    {
        $this->output->draw_unsupportedElement($uuid,$prop);
    }

    
    



    
    
    
    /**************************************************************************************/
    /****************************** misc functions ****************************************/
    /**************************************************************************************/

    protected function drawElement(string $uuid,array $prop,int $offsetx,int $offsety)
    {
                // $prop = $this->prop($obj->reportElement);
                $x = $prop['x']+$offsetx;
                $y = $prop['y']+$offsety;//$this->currentY;
                $height = $prop['height'];
                $width = $prop['width'];
        
                // $this->console("early draw element $uuid x=$x, y=$y\n");
                if(isset($prop['hyperlinkReferenceExpression']))
                {
                    $prop['hyperlinkReferenceExpression'] = $this->executeExpression($prop['hyperlinkReferenceExpression']);
                }
                $this->output->setPosition($x,$y,$prop);
                $methodname = 'draw_'.$prop['elementtype'];
                call_user_func([$this,$methodname],$uuid,$prop);
    }

    protected function addBorders(array $prop, object $obj): array
    {
        if(isset($obj->box))
        {
            $prop = $this->appendprop($prop, $obj->box->pen,'pen');
            $prop = $this->appendprop($prop, $obj->box->topPen,'topPen');
            $prop = $this->appendprop($prop, $obj->box->leftPen,'leftPen');
            $prop = $this->appendprop($prop, $obj->box->bottomPen,'bottomPen');
            $prop = $this->appendprop($prop, $obj->box->rightPen,'rightPen');
        }
        return $prop;
    }

    
}