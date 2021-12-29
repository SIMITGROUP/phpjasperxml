<?php
namespace simitsdk\phpjasperxml\datadrivers;

use mysqli;
use mysqli_result;
class Mysql_driver implements DataInterface
{
    protected $conn;
    public function __construct(array $config)
    {
        
        
        $this->conn = $this->connect($config);
        
        $this->conn->set_charset('utf8');
        if(!$this->conn)
        {
            die('Cannot connect to mysql');
        }
    }

    
        
    public function fetchData(mixed $querypara):array 
    {
        $mode=MYSQLI_ASSOC;
        $q = $this->query($querypara);
        // echo 'mysql';
        try 
        {
            if(is_object($q) && method_exists($q, 'fetch_array'))
            {
                $data = [];
                
                while($row = $q->fetch_array($mode))
                {
                    array_push($data, $row);
                }
                // echo 'asdad';
                return $data;
            }
            else
            {
                
                // echo 'aaa';
                return [];
            }
            
        }
        catch (\Throwable $e)
        {
            echo ($e->getMessage());
            die;
            return [];
        }

    }

    /**************************** internal method ******************************/
    /**************************** internal method ******************************/
    /**************************** internal method ******************************/
    protected function connect(array $config)
    {
        $host = $config['host'];
        $user =  $config['user'];
        $pass =  $config['pass'];
        $name =  $config['name'];
        
        if(gettype($config['connection'])=='object')
        {
            $cn = $config['connection'];
        }
        else
        {
            $cn = new mysqli($host, $user, $pass, $name);
        }
        return $cn;
    }

    protected function query(string $sql) 
    {   
        
        $query = $this->conn->query($sql);        
        // $query = pg_query($this->conn, $sql);        
        return $query;
    }
    
}