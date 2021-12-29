<?php

namespace simitsdk\phpjasperxml\datadrivers;

interface DataInterface
{
    
    public function fetchData(mixed $querypara):array;

}