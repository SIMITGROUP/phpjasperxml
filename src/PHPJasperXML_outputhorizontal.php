<?php

namespace simitsdk\phpjasperxml;

trait PHPJasperXML_outputhorizontal{
    protected $currentRowTop=0;
    protected $maxDetailEndY=0;
    protected $page_isdrawcolumns=[];
    protected $lastdetailpage = 1;
    protected $lastdetailcolumn = 0;
    protected $lastdetailbeginingY = null;
    protected function draw_columnHeaderHorizontal()
    {
        $pageno = $this->output->PageNo();
        $key = 'colheader_'.$pageno;        
        
        if(!isset($this->page_isdrawcolumns[$key]))
        {
            
            $currentcolumn=$this->output->getColumnNo();
            $this->page_isdrawcolumns[$key]=true;
            
            for($i=0;   $i< $this->columnCount ; $i++)
            {
                $this->output->setColumnNo($i);
                
                $this->drawBand('columnHeader');
            }
            $this->output->setColumnNo($currentcolumn);
        }
        
    }
    protected function draw_columnFooterHorizontal()
    {
        $pageno = $this->output->PageNo();
        $key = 'colfooter_'.$pageno;        
        
        if(!isset($this->page_isdrawcolumns[$key]))
        {
            
            $currentcolumn=$this->output->getColumnNo();
            $this->page_isdrawcolumns[$key]=true;
            
            for($i=0; $i< $this->columnCount ; $i++)
            {
                $this->output->setColumnNo($i);
                
                $this->drawBand('columnFooter');
            }
            $this->output->setColumnNo($currentcolumn);
        }
                
    }
    protected function draw_groupsHeaderHorizontal()
    {
        
        //set lastX at first column        
        // $this->output->setColumnNo(0);
        $lastcolumno = $this->output->getColumnNo();
        $this->output->setColumnNo(0);
        $this->draw_groupsHeader();
        $this->output->setColumnNo($lastcolumno);
        $this->currentRowTop = $this->output->getLastBandEndY();
        
        //output print group
            //set callback use next page instead
        // echo 'a';   
    }
    protected function draw_detailHorizontal()
    {           
        
        if($this->isgroupchanged || $this->currentRow==0) //group changed, detail no continue next column
        {            
            $mycolumn=0;            
        }
        else
        {
            $this->lastdetailcolumn += 1;
            //mean have balance slot at previous page detail band
            if($this->columnCount > $this->lastdetailcolumn)
            {
                
                $this->output->SetPage($this->lastdetailpage);
                $mycolumn = $this->lastdetailcolumn;                
                // $this->output->setLastBandEndY($this->lastdetailbeginingY);
            }   
            else
            {
                $mycolumn=0;            
            }
        }
            $this->output->setColumnNo($mycolumn);

            $this->lastdetailpage = $this->output->PageNo();;
            $this->lastdetailcolumn = $this->output->getColumnNo();        
            
    
        foreach($this->bands as $bandname=>$setting)
        {
            if(str_contains($bandname,'detail_'))    
            {                
                $this->drawBand($bandname,function() use ($mycolumn)
                {
                    $currentpage = $this->output->PageNo();
                    $totalpage = $this->output->getNumPages();                            
                    $this->maxDetailEndY=0;                    
                    // if($currentpage == $this->lastdetailpage)
                    {
                        // echo "\nnew page $currentpage == $totalpage\n"; 
                        $this->newPage();
                        $this->output->setColumnNo($mycolumn);
                    }
                    // else
                    {
                        // echo "\nset page $currentpage == $totalpage\n"; 
                        // $this->setPage(($currentpage-1));
                    }                                                            
                });
                // $this->output->setPage($initdetailpage);
            }
        }   
        $currentEndY= $this->output->getLastBandEndY();
        if($currentEndY > $this->maxDetailEndY)
        {
            $this->maxDetailEndY =  $currentEndY;
        }
        $this->lastdetailbeginingY=$this->output->getLastBandEndY();
        // echo "\ndraw_detailHorizontal maxDetailEndY $this->maxDetailEndY\n";
        $this->setNextAvailableSlot();
        
    }
    protected function setNextAvailableSlot()
    {
        $this->output->nextColumn();
        if($this->output->getColumnNo() == $this->columnCount)
        {
            $this->output->setColumnNo(0);            
            $this->currentRowTop = $this->maxDetailEndY;
        }
        $this->output->setLastBandEndY($this->currentRowTop);
    }

    protected function draw_groupsFooterHorizontal()
    {
        $endY=$this->maxDetailEndY;
        // echo "\ndraw_groupsFooterHorizontal begin with  $endY\n";
        $isgroupchange = $this->identifyGroupChange();
        if($isgroupchange)
        {
            $this->output->setColumnNo(0);
            $this->output->setLastBandEndY($endY);
        }
        for($i=count($this->descgroupnames)-1;$i>=0;$i--)
        {                        
            $groupname = $this->descgroupnames[$i];
            $bandname = $this->groupbandprefix.$groupname.'_footer';

            // echo "\n currentRow $this->currentRow ==  ($this->rowcount-1) rowcount-1 \n";
            if($this->groups[$groupname]['ischange'] || $this->currentRow == ($this->rowcount-1) )
            {                                
                // $lastdetailendy = $this->bands[$this->lastbandname]['endY'];
                // $this->output->setLastBandEndY($this->maxDetailEndY);
                $this->drawBand($bandname,function(){
                    $this->newPage();
                    $this->nextColumnHorizontal();
                });                
            }
            
        }   


        // $lastcolumno = $this->output->getColumnNo();
        // $this->output->setColumnNo(0);
        // $this->draw_groupsFooter();
        // $this->output->setColumnNo($lastcolumno);
        // $this->currentRowTop = $this->maxDetailEndY;// $this->output->getLastBandEndY();
        // $this->output->setLastBandEndY($this->currentRowTop);
        
        
       
        
    }

    protected function nextColumnHorizontal()
    {
        $this->output->nextColumn();
        if($this->output->getColumnNo()<$this->columnCount -1)
        {
            // $this->draw_columnFooter();
            $this->output->setLastBandEndY($this->currentRowTop);            
            $this->output->nextColumn();
            
            // $this->draw_columnHeader();
        }
        else
        {
            $this->currentRowTop = $this->output->getLastBandEndY();
            $this->output->setColumnNo(0);
            
        }
        // die($this->output->getColumnNo().":$this->columnCount nomore");
        
    }
}
