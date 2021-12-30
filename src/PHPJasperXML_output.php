<?php

namespace simitsdk\phpjasperxml;

trait PHPJasperXML_output
{                    
    protected array $pageproperties=[];
    protected $output = null;
    protected int $currentRow=0;
    protected int $reducerowno=0;
    protected array $descgroupnames=[];
    protected array $row = [];
    protected bool $isgroupchanged=false;
    protected bool $islastrow = false;
    protected string $creator = 'phpjasperxml';
    protected string $author = 'simitsdk';
    protected string $keywords = 'pdf, phpjasperxml, simitsdk';
    protected string $title = '';
    protected string $subject = '';    

    // protected $bandsequence = [];
    public function export(string $type,string $filename='')
    {                
        $this->pageproperties['creator']=$this->creator;
        $this->pageproperties['author']= $this->author;
        $this->pageproperties['keywords']=$this->keywords;
        $this->pageproperties['title']= !empty($this->title) ? $this->title : $this->pageproperties['name'];
        $this->pageproperties['subject']= !empty($this->subject) ? $this->subject : $this->pageproperties['name'];
        // print_r($this->pageproperties);
        
        $classname = '\\simitsdk\\phpjasperxml\\Exports\\'.ucfirst($type)."_driver";

        // $this->console($this->pageproperties);die;
        $this->output  = new $classname($this->pageproperties);
        
        // print_r($this->bands);die;
        $this->output->defineBands($this->bands,$this->elements,$this->groups);
        // echo "rowcount $this->rowcount";die;
        if($this->rowcount>0)
        {
            $this->sortData();
            $this->output->defineColumns($this->columnCount,$this->columnWidth);
            foreach($this->rows as $i=>$r)
            {                
                $this->setRow($i);
                if($i==0)
                {
                   $this->newPage(true);
                }
                $postfix='';
                if($this->printOrder=='Horizontal')
                {
                    $postfix='Horizontal';                                    
                }
                call_user_func([$this,'draw_groupsHeader'.$postfix]);
                call_user_func([$this,'draw_detail'.$postfix]);
                call_user_func([$this,'draw_groupsFooter'.$postfix]);
                // call_user_func([$this.'draw_groupsFooter'.$postfix]);
                
            }
            $this->endPage();
        }
        else
        {
            if($this->bands['noData']['height'])
            {
                $this->draw_noData();
            }
            else
            {
                die('No data found, and noData band undefined');
            }
            
        }
        // echo "export";die;
        if(!empty($filename))
        {
            // $filename = '/tmp/'.str_replace('.jrxml','.pdf',$this->filename);
            
            $this->output->export($filename);
        }
        else
        {
            // echo 'export';die;
            $this->output->export();
        }
        
    }

    protected function sortData()
    {
        if(count($this->sortFields)>0)
        {
            $sortcols=[];
            foreach($this->sortFields as $fieldname =>$setting)
            {
                $this->sortFields[$fieldname]['order'] = $this->sortFields[$fieldname]['order']??'Ascending';
                $sortcols[$fieldname]=[];
            }
            
            foreach($this->rows as $i => $r)
            {
                // $globaluser_id[$i]=$r;
                foreach($sortcols as $fieldname =>$tmp)
                {                    
                    $sortcols[$fieldname][$i]=$r[$fieldname];
                }
            }
            
            $sortparas=[];
            foreach($sortcols as $fieldname =>$tmp)
            {
                $sort=SORT_ASC;
                if(isset($this->sortFields[$fieldname]['order']) && $this->sortFields[$fieldname]['order'] == 'Descending')
                {
                    $sort = SORT_DESC;
                }
                array_push($sortparas,$tmp,$sort,SORT_REGULAR);
            }
                        
            $arr = $this->rows;
            $sortparas[]=&$arr;            
            call_user_func_array('array_multisort',$sortparas);
            $this->rows = $arr;
        }
        
    }
    protected function newBlankPage()
    {
        $this->output->AddPage();     
        $this->draw_background();     
    }
    protected function newPage($withTitle=false)
    {
        
        // $this->console("newpage withTitle:".$withTitle);
        if(!$withTitle)
        {
            $this->reducerowno = 1;
            $this->draw_columnFooter();
            $this->draw_pageFooter(); //if no content, it will call draw_pageFooter
            $this->reducerowno=0;
        }    
        // echo "\nAdd Page\n";
        $this->output->AddPage();          
        $this->draw_background();
        if($withTitle)
        {
            $this->draw_title();
        }        
        $this->draw_pageHeader();
        $this->prepareColumn();
        $this->draw_columnHeader();
        
        
    }
    protected function nextColumn()
    {
        if($this->columnCount==1)
        {
            $this->newPage();
        }
        else
        {
            if($this->output->getColumnNo()<$this->columnCount-1 )
            {
                $this->draw_columnFooter();
                $this->output->nextColumn();
                $this->draw_columnHeader();
            }
            else
            {
                $this->output->setColumnNo(0);
            }
        }
        
        
        
    }
    protected function prepareColumn()
    {
        // $this->console('output prepareColumn');
        $this->output->prepareColumn();
        
    }
    protected function endPage()
    {        
        $this->draw_columnFooter();
        $this->draw_summary();
        $this->draw_lastPageFooter(); //if no content, it will call draw_pageFooter
    }

    /**
     * loop through every row, and compute variable of each row
     * @param int $i row number
     */
    protected function setRow(int $i)
    {
        $this->currentRow=$i;
        $this->row = $this->rows[$i];
        $this->computeVariables($i);
        $this->output->setRowNumber($i);
        if($this->rowcount == $i+1 )
        {
            $this->islastrow=true;
            $this->output->islastrow=true;
        }
        
    }

    
    
    protected function drawBand(string $bandname, mixed $callback=null)
    {

        
        $offsets = $this->output->prepareBand($bandname,$callback);
        // $this->console($bandname);
        // $this->console($offsets);
        $offsetx=(int)$offsets['x'];
        $offsety=(int)$offsets['y'];
        
        if($this->bands[$bandname]['height']==0)
        {
            return ;
        }


        if(isset($this->bands[$bandname]['printWhenExpression']))
        {
            $banddisplayexpression = $this->bands[$bandname]['printWhenExpression'];
            $isdisplay = $this->isDisplay($banddisplayexpression);
            if(!$isdisplay)
            {
                $height=0;
                $this->bands[$bandname]['height']=0;
            }
            else
            {
                $this->bands[$bandname]['height'] = $this->bands[$bandname]['originalheight'];
            }
        }


        $height = $this->bands[$bandname]['height'];
        if($height>0)
        {
         
           foreach($this->elements[$bandname] as $uuid =>$element)
            {
                $tmp = $element;
                $isdisplayelement = true;
                $framedisplay = true;
                if(isset($element['printWhenExpression']))
                {                   
                    $printWhenExpression = (string)$element['printWhenExpression'];
                    $isdisplayelement = $this->isDisplay($printWhenExpression);
                    
                }
                $this->elements[$bandname][$uuid]['show']=$isdisplayelement;

                //if this element is within frame, depend on frame appear or not
                if(isset($this->elements[$bandname][$uuid]['frame']))
                {
                    $frameuuid =$this->elements[$bandname][$uuid]['frame'];
                    $isdisplayelement = $this->elements[$bandname][$frameuuid]['show']??true;
                }

                //only match printWhenExpression will draw element
                if($isdisplayelement && $framedisplay)
                {
                    
                    $this->drawElement($uuid,$tmp,$offsetx,$offsety);
                }                            
            }
        }
        $this->output->endBand($bandname);
        
    }
    
    protected function draw_noData()
    {        
        $this->output->AddPage();
        $this->drawBand('noData');
    }

    protected function draw_background()
    {        
        $this->drawBand('background');
    }

    protected function draw_title()
    {
        $this->drawBand('title');
    }

    protected function draw_pageHeader()
    {
        $this->drawBand('pageHeader');
    }

    protected function draw_columnHeader()
    {        
        // echo "\nprint column header $this->printOrder\n";
        if($this->printOrder=='Vertical')
        {
            $this->draw_columnHeaderVertical();
        }
        else
        {
            $this->draw_columnHeaderHorizontal();
        }

    }
    protected function draw_columnFooter()
    {
        if($this->printOrder=='Vertical')
        {
            $this->draw_columnFooterVertical();
        }
        else
        {
            $this->draw_columnFooterHorizontal();
        }
        
    }
    protected function draw_columnHeaderVertical()
    {
        $this->drawBand('columnHeader');
    }
    protected function draw_columnFooterVertical()
    {
        $this->drawBand('columnFooter');
    }
    
    protected function draw_groupsHeader()
    {
        foreach($this->groups as $groupname=>$groupsetting)
        {
            $bandname = $this->groupbandprefix.$groupname.'_header';            
            if($groupsetting['ischange'])
            {
                
                if($groupsetting['isStartNewPage'] && $this->currentRow>0)
                {

                    $this->newPage();
                }
                else if($groupsetting['isStartNewColumn'] && $this->currentRow>0)
                {
                    $currentcolumn = $this->output->getColumnNo();
                    $pageno = $this->output->PageNo();
                    // echo "\n isStartNewColumn ($pageno) $this->columnCount == $currentcolumn + 1 \n";
                    if($this->columnCount == $currentcolumn + 1 )
                    {
                        // echo "\n new page:\n";
                        $this->newPage();
                        $pageno = $this->output->PageNo();
                        // echo "\n new page with no $pageno\n";
                    }
                    else
                    {
                        // echo "\nnew column\n";
                        $this->nextColumn();
                    }
                    
                }
                $this->groups[$groupname]['ischange']=false;
                // echo "\ngroup $groupname\n";
                // print_r($this->groups[$groupname]);
                $this->output->groups[$groupname]['ischange']=false;
                $groupExpression = $groupsetting['groupExpression'];
                $newgroupvalue = $this->parseExpression($groupExpression);
                $this->groups[$groupname]['value'] = $newgroupvalue;                

                $this->drawBand($bandname,function(){
                    
                    if($this->printOrder=='Vertical')
                    {
                        $columnno = $this->output->getColumnNo();
                        if($columnno == $this->columnCount -1)
                        {
                            //by right shall create new page, but dun understand why it no need, tmp disable it
                            // $this->newPage();
                        }
                        else
                        {
                            $this->nextColumn();
                        }
                        
                    }
                    else
                    {
                        $this->maxDetailEndY=0;
                        $this->newPage();
                    }
                    $this->nextColumn();
                });
            }
            
        }
        
    }

    /**
     * draw detail bands(multiple) element
     */
    protected function draw_detail()
    {
        
        if($this->printOrder=='Vertical')
        {
            $this->draw_detailVertical();
        }
        else
        {
            $this->draw_detailHorizontal();
        }

    }
    protected function draw_detailVertical()
    {
        // $initdetailpage = $this->output->PageNo();;
        // $beginingY=$this->output->getLastBandEndY;
        foreach($this->bands as $bandname=>$setting)
        {
            if(str_contains($bandname,'detail_'))    
            {                
                $this->drawBand($bandname,function() 
                {
                    // $currentpage = $this->output->PageNo();
                    // $totalpage = $this->output->getNumPages();                            
                    $columnno = $this->output->getColumnNo();
                    if(($columnno == $this->columnCount -1) )
                    {                        
                        $this->newPage();
                    }
                    else
                    {
                        $this->nextColumn();
                    }                                                               
                });
            }
        }        
    }


    protected function identifyGroupChange(): bool
    {

        //$this->resetGroupIfRequire(($i+1));        
        $this->descgroupnames = [];
        $resettherest=false;
        foreach($this->groups as $groupname=>$groupsetting)
        {
            $groupExpression = $groupsetting['groupExpression'];
            $lastgroupvalue = $groupsetting['value'];
            $newgroupvalue = $this->parseExpression($groupExpression,1);
            
            if($lastgroupvalue != $newgroupvalue)
            {
                $resettherest=true;
                $this->groups[$groupname]['value'] = $newgroupvalue;                
            }

            if($resettherest)
            {                
                // echo "\nchanged group $groupname\n";
                $this->groups[$groupname]['count']=0;
                $this->groups[$groupname]['ischange']=true;
                $this->output->groups[$groupname]['ischange']=true;
                //reset all variables under this group
                foreach($this->variables as $varname=>$varsetting)
                {
                    if($varsetting['datatype']=='string')
                    {
                        $this->variables[$varname]['value']='--value reset--';
                    }
                    else
                    {
                        $this->variables[$varname]['value']=null;
                    }
                    
                    $this->variables[$varname]['lastresetvalue']='--lastvalue reset--';
                }
                
            }
            else
            {
                $this->groups[$groupname]['ischange']=false;
                $this->output->groups[$groupname]['ischange']=false;
            }
            $this->groups[$groupname]['count']++;

            array_push($this->descgroupnames,$groupname);                        
        }        
        $this->isgroupchanged = $resettherest;
        return $resettherest;
    }
    protected function draw_groupsFooter()
    {
        $this->identifyGroupChange();
        for($i=count($this->descgroupnames)-1;$i>=0;$i--)
        {                        
            $groupname = $this->descgroupnames[$i];
            $bandname = $this->groupbandprefix.$groupname.'_footer';
            // echo "\n currentRow $this->currentRow ==  ($this->rowcount-1) rowcount-1 \n";
            if($this->groups[$groupname]['ischange'] || $this->currentRow == ($this->rowcount-1) )
            {
                $this->drawBand($bandname,function(){
                    $this->nextColumn();
                });
            }
            
        }   
    }
    
    protected function draw_summary()
    {       
        $callback = null;
        if($this->bands['summary']['height']>0)
        {
            $callback = function(){
                $this->newBlankPage();
                return $this->output->getMargin('top');
            };
        } 
        $this->drawBand('summary',$callback);
    }
    protected function draw_lastPageFooter()
    {        
        if($this->bands['lastPageFooter']['height']==0)
        {
            $this->draw_pageFooter();
        }
        else
        {
            $this->drawBand('lastPageFooter');
        }
    }
    protected function draw_pageFooter()
    {
        $this->drawBand('pageFooter');
    }

    public function setCreator(string $creator):self
    {
        $this->creator = $creator;
        return $this;
    }
    public function setAuthor(string $author):self
    {
        $this->author = $author;
        return $this;
    }
    public function setKeywords(string $keywords):self
    {
        $this->keywords = $keywords;
        return $this;
    }
    public function setTitle(string $title):self
    {
        $this->title = $title;
        return $this;
    }
    public function setSubject(string $subject):self
    {
        $this->subject = $subject;
        return $this;
    }


}