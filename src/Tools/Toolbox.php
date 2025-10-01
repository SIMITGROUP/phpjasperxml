<?php

namespace simitsdk\phpjasperxml\Tools;

use SimpleXMLElement;

trait Toolbox
{

   

    protected function left(string $str, int $length) : string
    {
        return substr($str, 0, $length);
    }

    protected function right(string $str, int $length) : string
    {
        return substr($str, -$length);
    }

    protected function getHashValueFromIndex(array $arr,int $no): mixed
    {
        $i=0;
        foreach($arr as $k=>$v)
        {
            if($i==$no)
            {
                return $v;
            }
            $i++;
        }
        return null;
    }
    protected function getHashKeyFromIndex(array $arr,int $no): mixed
    {
        $i=0;
        foreach($arr as $k=>$v)
        {
            if($i==$no)
            {
                return $k;
            }
            $i++;
        }
        return null;
    }

    
    public function console(mixed $txt='')
    {
        if (php_sapi_name() == "cli") {
            // echo "\n$txt\n";
            fwrite($this->consoleOut, "$txt.\n");
        } else {
            if(gettype($txt) == 'array')
            {
                fwrite($this->consoleOut, print_r($txt,true)."\n");
                // echo "<pre/>",print_r($txt,true)."<pre/>";
            }
            else
            {
                fwrite($this->consoleOut, "$txt.\n");
                // echo "<br/>$txt<br/>";
            }
            
        }
        
    }

    public function failed(mixed $txt='')
    {
        $this->console($txt);
        die;        
    }

    public function javaToPhpStringConcateExpression(string $expression):string{
        $pattern = '/"(.*?)"/';
        //str_replace("+"," . ",$obj->textFieldExpression);  
        preg_match_all($pattern, $expression, $matchfield);

        $fieldstrings = $matchfield[0];
        $fieldnames = $matchfield[1];        

        
        foreach($fieldstrings as $findex => $str)
        {            
            $tmpexpression = str_replace($str,"<<{'.$findex.'}>>",$expression);
            $tmpexpression = str_replace("+",' . ',$tmpexpression);
            $expression = $tmpexpression = str_replace("<<{'.$findex.'}>>",$str,$tmpexpression);
        }



        return $expression;
    }

}