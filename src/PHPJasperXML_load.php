<?php
namespace simitsdk\phpjasperxml;

use SimpleXMLElement;

trait PHPJasperXML_load
{
    protected array $pageproperties=[];
    protected array $variables=[];
    protected array $parameters=[];
    protected array $fields=[];
    protected array $groups=[];
    protected int $groupcount = 0;
    protected int $columnCount = 1;    
    protected int $columnWidth = 0;
    protected string $printOrder = '';
    protected array $elements = [];
    protected string $filename ='';
    protected string $querystring = '';
    protected array $subdatasets=[];
    protected array $styles=[];
    protected string $groupbandprefix = 'report_group_';
    protected array $sortFields=[];
    protected string $path = '';
    protected array $scriptlets=[];
    /**
     * read jrxml file and load into memeory
     * @param string $filename
     * @return self
     */
    public function load_xml_file(string $file ): self
    {           
        $pathinfo = pathinfo($file);
        $this->filename = $pathinfo['basename'];     
        $this->path = $pathinfo['dirname'];
        $xml =  file_get_contents($file);                  
        $this->load_xml_string($xml);      
        // print_r($this->bandelements);
        return $this;
    }

    /**
     * distribute jrxml contents into different attributes categories
     * @param string $jrxml content
     * @return self
     */
    public function load_xml_string(string $jrxml): self
    {
        $obj = simplexml_load_string($jrxml,SimpleXMLElement::class,LIBXML_NOCDATA);
        // $obj = simplexml_load_string($jrxml,SimpleXMLElement::class,LIBXML_DTDVALID);
        // $obj = simplexml_load_string($jrxml,SimpleXMLElement::class,LIBXML_DTDLOAD);
        // $obj = simplexml_load_string($jrxml);
        // $obj = simplexml_load_string($jrxml,SimpleXMLElement::class,LIBXML_HTML_NOIMPLIED);
        // $obj = simplexml_load_string($jrxml,SimpleXMLElement::class,LIBXML_HTML_NODEFDTD);
        // $obj = simplexml_load_string($jrxml,SimpleXMLElement::class,LIBXML_DTDATTR);
        // $obj = simplexml_load_string($jrxml,SimpleXMLElement::class,LIBXML_COMPACT);
        // echo gettype($obj);
        $this->pageproperties = $this->prop($obj);
        $this->columnWidth=$this->pageproperties['columnWidth'] ?? $this->pageproperties['pageWidth'];
        $this->columnCount=$this->pageproperties['columnCount']??1;
        $this->printOrder = $this->pageproperties['printOrder']?? 'Vertical' ;
        foreach ($obj as $k=>$out)
        {            
            $setting = $this->prop($out);
            $name= isset($setting['name']) ? $setting['name'] : '';            
            switch($k)
            {
                case 'property':
                    $this->pageproperties[$name]=$setting;                                        
                    break;
                case 'field':                
                case 'parameter':
                case 'variable':
                    $attributename = $k.'s';                                        
                    foreach($out as $key=>$value)
                    {
                        $setting[$key]=(string)$value;
                    }
                    $setting['datatype']=$this->getDataType($setting);
                    
                    if($k=='variable')
                    {
                        // print_r($setting);
                        if(empty($setting['variableExpression']))
                        {
                            die("variable $name undefined expression");
                        }
                        $setting['value']=null;
                    }
                    else if($k=='parameter')
                    {
                        $setting['value']=null;
                    }
                    $this->$attributename[$name]=$setting;                    
                    break;                    
                case 'scriptlet':
                    $this->scriptlets[$name]=(string)$out->scriptletDescription;
                    break;
                case 'sortField':
                    $this->sortFields[$name]=$setting;
                    break;
                case 'queryString':
                    $this->setQueryString($out);
                    break;
                case "subDataset":
                    $this->addSubDataSets($name,$out);
                    break;
                case 'group':                                        
                    $this->addGroup($out);             
                    break;
                //all bands
                case 'background':
                case 'title':
                case 'pageHeader':
                case 'columnHeader':
                case 'detail':
                case 'columnFooter':
                case 'pageFooter':
                case 'lastPageFooter':
                case 'summary':
                case 'noData':
                    $this->addBand($k,$out);
                    break;
                case 'style':
                    $this->addStyle($name,$out);
                    break;
                default:                    
                    echo "$k is not supported, rendering stop\n";                    
                    die;
                    break;
            }
        }                    
        return $this;
    }

    /**
     * define / override jrxml querystring into memory
     * @param string $sql
     * @return $this
     */
    public function setQueryString($sql):self
    {
        $this->querystring=$sql;
        return $this;
    }

    public function addStyle(string $name, object $element)
    {
        
        $prop=$this->prop($element);        
        $this->styles[$name] = $prop;
        // <style name="Table_CH" mode="Opaque" backcolor="#BFE1FF">
    }
    /**
     * register different band into band array
     * @param string $bandname
     * @param object $elements
     */
    protected function addBand(string $bandname,object $elements,bool $isgroup = false)
    {               
        
        $offsety=0;               
        $count=0;
        foreach($elements->band as $bandobj)
        {            
            if($bandname == 'detail')
            {
                $newbandname = $bandname.'_'.$count;
            }
            else
            {
                $newbandname = $bandname;
            }
            $prop = $this->prop($bandobj);
            foreach($prop as $k=>$v)
            {
                $this->bands[$newbandname][$k]  = $v;
            }            
            if(isset($bandobj->printWhenExpression))
            {
                $this->bands[$newbandname]['printWhenExpression'] = (string)$bandobj->printWhenExpression;
            }
            $this->bands[$newbandname]['endY'] = $this->bands[$newbandname]['endY']??0;
            $this->bands[$newbandname]['height'] = $this->bands[$newbandname]['height']??0;
            $this->bands[$newbandname]['originalheight'] = $this->bands[$newbandname]['height']; //hide is dynamically change depends on printwhen expression or scale.
            $this->elements[$newbandname] = $this->getBandChildren($bandobj,$newbandname);
            $this->sortElements($newbandname);          
            $count++;
        }  
        

    }
    protected function sortElements(string $bandname)
    {
        $ypositions =[];        
        foreach($this->elements[$bandname] as $uuid => $prop)
        {            
            $y = $prop['y'];
            $ypositions[$uuid]= $y;         
        }
        
        array_multisort($ypositions, SORT_ASC, SORT_NUMERIC, $this->elements[$bandname]);
        
        
        
    }

    /**
     * register groups and groupband
     * 
     */
    protected function addGroup($obj)
    {
        $prop = $this->prop($obj);
        $name = (string)$prop['name'];
        $groupExpression = (string)$obj->groupExpression;
        $bandname = 'report_group_'.$name;
        $groupExpression = $obj->groupExpression;
        $prop['value']=0;
        $prop['count']=1;
        $prop['groupExpression']=$groupExpression;
        $prop['groupno']=$this->groupcount;
        $prop['ischange']=true;
        $prop['isStartNewPage']= $prop['isStartNewPage']??'';
        $prop['isStartNewColumn']= $prop['isStartNewColumn']??'';
        $this->groups[$name]=$prop;//[ 'value'=>'NOVALUE','count'=>0,'groupExpression'=>$groupExpression, 'groupno'=>$this->groupcount,'ischange'=>true];
        $this->addBand($bandname.'_header',$obj->groupHeader,true);
        $this->addBand($bandname.'_footer',$obj->groupFooter,true);
        $this->groupcount++;
    }

    protected function addSubDataSets(string $name, string $sql)
    {        
        $this->subdatasets[$name]=$sql;
    }
  
    
    protected function toValue(mixed $data): mixed
    {
        return json_decode(json_encode($data),true);
    }

    

    protected function getBandChildren($els,string $bandname)
    {
        $data=[];
        foreach($els as $elementtype => $obj)
        {
            // $this->console($elementtype);
            // print_r($obj);
            if(str_contains($elementtype,'Chart'))
            {
                $methodname = 'element_chart';//all chart share same
                $objvalue = $obj->chart;                    
                $type='chart';
                
            }
            else
            {
                $methodname = 'element_'.$elementtype; //prepare elements setting    
                $objvalue = $obj ;                
                $type=$elementtype;
            }
            
            $reportelement = $objvalue->reportElement;
            $setting = $this->prop($reportelement);
            
            if(!empty($setting['uuid']))
            {                                                
                $uuid = $setting['uuid'];
                $objvalue->elementtype = $type;             
                if(method_exists($this,$methodname))
                {
                    $prop = $this->prop($reportelement);
                    $prop = $this->appendprop($prop,$objvalue);
                    $prop['elementtype']=$type;

                    

                    foreach($objvalue as $k=>$values)
                    {
                        $prop = $this->appendprop($prop,$values);
                    }                                                
                    
                    if(isset($reportelement->printWhenExpression))
                    {
                        $prop['printWhenExpression']=(string)$reportelement->printWhenExpression;
                    }
                    if(isset($objvalue->hyperlinkReferenceExpression))
                    {
                        $prop['hyperlinkReferenceExpression']=(string)$objvalue->hyperlinkReferenceExpression;
                    }

                    
                    $prop = call_user_func([$this,$methodname],$prop,$objvalue);      
                    $prop['band']=$bandname;
                    $data[$uuid] = $prop;
                    if($type=='frame')                    
                    {
                        unset($obj->reportElement);
                        unset($obj->box);
                        $subdata=$this->getBandChildren($obj->children(),$bandname);
                        foreach($subdata as $subuuid => $subprop)
                        {
                            $subprop['frame']=$uuid;
                            $subprop['x']+=$prop['x'];
                            $subprop['y']+=$prop['y'];
                            $data[$subuuid]=$subprop;
                        }
                        // $this->console('subdata');
                        // print_r($obj->children());
                        // print_r($subdata);
                    }
                    
                    
                }
                else
                {
                    echo "\nElement $elementtype is not supported due to $methodname() is not exists\n";
                }
                
                
            }
            
        }            
        
        return $data;
    }








    /************** misc functions *******************/
    /************** misc functions *******************/
    /************** misc functions *******************/
    /**
     * convert java datatype name to php datatype name
     */
    protected function getDataType(array $setting): string
    {
        $type='';
        switch($setting['class'])
        {
            case 'java.lang.Boolean':
                $type='boolean';
            break;            
            case 'java.lang.Integer':
            case 'java.lang.Long':                    
            case 'java.lang.Short':
            case 'java.lang.Double':            
            case 'java.lang.Float':
            case 'java.math.BigDecimal':
                $type='number';
            break;
            case 'java.sql.Connection':
                $type='array';
            break;
            case 'java.sql.Timestamp':
            case 'java.lang.String':
            default:
                $type='string';
            break;
        }
        return $type;
    }
     /** 
     * get property of simplexml ofbject
     * @param SimpleXMLElement $obj
     * @return array $attributes
     */
    protected function prop(\SimpleXMLElement $obj):array
    {
        $attributes=[];
        if(!is_null($obj->attributes()))
        {            
            foreach($obj->attributes() as $k=>$v)
            {
                $attributes[$k]=json_decode(json_encode($v),true)[0];
            }        
        }
        return $attributes;
    }

    protected function appendprop(array $prop, object $obj, string $prefix=''):array
    {
        $subprop = $this->prop($obj);
        foreach($subprop as $k=>$v)
        {
            $key = $prefix.$k;

            $prop[$key]=$v;
        }
        return $prop;
    }

}