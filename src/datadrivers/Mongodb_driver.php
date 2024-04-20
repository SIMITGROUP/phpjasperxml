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
        $newarr=[];
        
        if(isset($query['collectionName'])){            
            $collectionName = $query['collectionName'];
            $result = [];
            $projection = [];
            $sort = [];
            $limit = 0;
            if(isset($query['findFields'])) $projection  = $query['findFields'];
            if(isset($query['sort'])) $sort  = $query['sort'];
            if(isset($query['limit'])) $limit  = $query['limit'];
            

            if(isset($query['findQuery'])){
                $findquery = $query['findQuery'];
                $moreoptions =[];
                if($projection) $moreoptions['projection']=$projection;
                if($limit) $moreoptions['limit']=$limit;
                if($sort) $moreoptions['sort']=$sort;                
                $result = $this->conn->$dbname->$collectionName->find($findquery,$moreoptions);
            }else if(isset($query['aggregate'])){
                $aggregate = $query['aggregate'];
                // print_r($this->conn->$collectionName);

                $result = $this->conn->$dbname->$collectionName->aggregate($aggregate);
            }
            
            $array = json_decode(json_encode($result->toArray(),true), true);
            $newarr = $array;
            // echo "<pre>".print_r($array,true)."</pre>";
            
            // for($i=0;$i<count($array);$i++){
            //     $l = $array[$i];
            //     $tmp = $this->convertObjectToArray($l);
            //     array_push($newarr,$tmp);
            // }


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
            try{
                $cn->listDatabases();
            }catch(Exception $e){
                die($e->getMessage());
            }
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