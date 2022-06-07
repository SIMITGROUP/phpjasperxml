<?php
namespace simitsdk\phpjasperxml\datadrivers;

use PDO;

class Pdo_driver implements DataInterface
{
    protected $conn;
    public function __construct(array $config)
    {
        
        
        $this->conn = $this->connect($config);
        
        
        if(!$this->conn)
        {
            die('Cannot connect to mysql');
        }
    }

    
        
    public function fetchData(mixed $querypara):array 
    {
        
        $q = $this->query($querypara);
        // echo 'mysql';
        try 
        {
            if(is_object($q) && method_exists($q, 'fetch'))
            {
                $data = [];
                $rows = $q->fetchAll(PDO::FETCH_ASSOC);
                foreach($rows as $row)
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
        $dsn = $config['dsn'];
        $user = $config['user'];
        $password = $config['pass'];
        

        if(gettype($config['connection'])=='object')
        {
            $cn = $config['connection'];
        }
        else
        {            
            $cn = new PDO($dsn,$user,$password);
        }

        return $cn;
    }

    protected function query(string $sql) 
    {   
        
        $query = $this->conn->query($sql,PDO::FETCH_ASSOC);        
        
        // $query = pg_query($this->conn, $sql);        
        return $query;
    }
    
}