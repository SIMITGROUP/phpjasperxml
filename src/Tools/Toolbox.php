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
        
    }

    public function console(mixed $txt='')
    {
        if (php_sapi_name() == "cli") {
            echo "\n$txt\n";
        } else {
            if(gettype($txt) == 'array')
            {
                echo "<pre/>",print_r($txt,true)."<pre/>";
            }
            else
            {
                echo "<br/>$txt<br/>";
            }
            
        }
        
    }

    public function failed(mixed $txt='')
    {
        $this->console($txt);
        die;        
    }

}