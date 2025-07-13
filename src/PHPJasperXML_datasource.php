<?php

namespace simitsdk\phpjasperxml;

trait PHPJasperXML_datasource{
    protected bool $uselastserializedata=false;
    protected $db = null;
    protected $rows = [];
    protected $rowcount = 0 ;
    protected $dataloaded=false;
    protected array $connectionsetting;
    protected string $cachefile = '';
    protected bool $debugdata = false;
    
    public function setDataSource(array $setting):self
    {
        $this->cachefile = sys_get_temp_dir().'/phpjasperxml.cache';
        
        if(empty($setting['driver']))
        {
            die('undefined db driver');
        }
        else
        {
            $driver = $setting['driver'];
            $this->connectionsetting = $setting;
            $driverfile = __DIR__.'/datadrivers/'.ucfirst($driver).'_driver.php';
            if(!file_exists($driverfile))
            {
                die("$driverfile does not exists");
            }
            else
            {
                $classname = '\\simitsdk\\phpjasperxml\\datadrivers\\' . ucfirst($driver).'_driver';
                $this->db = new $classname($setting);     
                $this->fetchData();
                return $this;           
            }
        }   
    }

    public function debugsql(string $sql) :self {        
        echo '<textarea rows=30 cols=100>' . print_r($sql,true) . '</textarea>';
        die;
    }
    public function fetchData() : self
    {
        
        $sql = $this->parseExpression($this->querystring);
        if(!empty($_REQUEST['debugsql'])){
            $this->debugsql($sql);
        }
        $data =$this->db->fetchData($sql);
        if($this->debugdata){
            echo "<pre>".print_r($data,true)."</pre>";
        }
        $this->loadData($data);        
        return $this;
    }
    public function debugData(bool $isdebugdata):self{
        $this->debugdata = $isdebugdata;
        return $this;
    }
    public function loadData(array $data):self
    {
        $this->dataloaded=true;
        $this->rowcount = count($data);
        
        if($this->uselastserializedata  == false)
        {
            $this->rows = $data;
            $this->storeCache();
        }
        else
        {
            if(file_exists($this->cachefile))
            {
                // $this->console("use cache file $this->cachefile");
                $file = file_get_contents($this->cachefile);
                $this->rows = unserialize($file);
                // print_r($this->rows);
            }
            else
            {
                $this->rows = $data;
                $this->storeCache();
            }
        }
        
        return $this;
    }

    protected function storeCache()
    {
        $storecache = serialize($this->rows);
        file_put_contents($this->cachefile, $storecache, LOCK_EX);
    }

}