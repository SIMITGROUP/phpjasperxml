<?php

namespace simitsdk\phpjasperxml;

trait PHPJasperXML_subreport{
    protected $parentobj=null;
    public function runSubReport(array $prop,object $outputobj)
    {                
        $this->pageproperties['creator']=$this->creator;
        $this->pageproperties['author']= $this->author;
        $this->pageproperties['keywords']=$this->keywords;
        $this->pageproperties['title']= !empty($this->title) ? $this->title : $this->pageproperties['name'];
        $this->pageproperties['subject']= !empty($this->subject) ? $this->subject : $this->pageproperties['name'];
        
        $this->pageproperties['topMargin']+=$prop['y'];
        $this->pageproperties['leftMargin']+=$prop['x'];
        $fullclassname =  $outputobj::Class;//  = $outputobj;
        $arrclassname =explode('\\',$fullclassname);
        $classname = $arrclassname[array_key_last($arrclassname)];
        $classname = '\\Simitsdk\\phpjasperxml\\Exports\\'.$classname;
        $this->output  = new $classname($this->pageproperties);        
        $this->output->defineBands($this->bands,$this->elements,$this->groups);
        $this->output->setParentObj($outputobj);

        $this->output->defineBands($this->bands,$this->elements,$this->groups);
        // echo "$this->rowcount";die;
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
            $this->draw_noData();
        }        

        // $this->console( "subreport run");
        // die;
        
    }
}
