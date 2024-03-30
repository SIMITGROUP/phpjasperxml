<?php
namespace simitsdk\phpjasperxml\datadrivers;

use Exception;
use MongoDB\Client;
use MongoDB\Driver\ServerApi;
class Mongodb_driver implements DataInterface
{
    protected $conn;
    protected $dbname='';
    public function __construct(array $config)
    {
        
        
        $this->conn = $this->connect($config);
        if(!$this->conn)
        {
            die('Cannot connect to Mongodb');
        }
    }

    
        
    public function fetchData(mixed $querypara):array
    {
        $dbname = $this->dbname;
        $query = \OviDigital\JsObjectToJson\JsConverter::convertToArray($querypara);
        $collectionName = $query['collectionName'];
        $aggregate = $query['aggregate'];
        // print_r($this->conn->$collectionName);
        $result = $this->conn->$dbname->$collectionName->aggregate($aggregate);
        $array = json_decode(json_encode($result->toArray(),true), true);
        // echo "<pre>".print_r($array,true)."</pre>";
        $newarr=[];
        for($i=0;$i<count($array);$i++){
            $l = $array[$i];
            $tmp = $this->convertObjectToArray($l);
            array_push($newarr,$tmp);
        }
        return $newarr;
    }

    protected function convertObjectToArray($obj){
        $newobj = [];
        foreach($obj as $k=>$v){
            if(gettype($v)=='array'){
                $tmp = $this->convertObjectToArray($v);
                foreach($tmp as $k2=>$v2){
                    $newobj[$k.".".$k2]=$v2 ;  
                }

            }else{
                $newobj[$k]=$v;
            }
            
        }

        return $newobj;
    }
    
    /**************************** internal method ******************************/
    /**************************** internal method ******************************/
    /**************************** internal method ******************************/
    protected function connect(array $config)
    {
        

        // $host = $config['host'];
        // $user =  $config['user'];
        // $pass =  $config['pass'];
        $this->dbname =  $config['name'];
        $connectionString="mongodb://127.0.0.1:27017/" . $this->dbname;
        if($config['connectionString']) $connectionString=$config['connectionString'];
        
        if(isset($config['connection']) && gettype($config['connection'])=='object')
        {
            $cn = $config['connection'];
        }
        else
        {
            $cn = new Client($connectionString);
            // $cn = new MongoDB\Driver\Manager($connectionString);
            // $cnstring = sprintf("host=%s user=%s password=%s dbname=%s options='--client_encoding=UTF8 '",$host,$user,$pass,$name);        
            // $cn = pg_connect($cnstring,PGSQL_CONNECT_FORCE_NEW);        
            // $apiVersion = new ServerApi(ServerApi::V1);

            // $cn = new MongoDB\Client($connectionString, [], ['serverApi' => $apiVersion]);

        }
        
        return $cn;
    }

    
    // protected function fetchArray($q)
    // {
        // print_r($q);
        // try 
        // {
        //         $row = pg_fetch_assoc($q);
        //         return $row;                
        // }
        // catch (\Throwable $e)
        // {
        //     // Throw error.
        // }
    // }
    
    
}