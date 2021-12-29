<?php
namespace simitsdk\phpjasperxml\datadrivers;

class Postgresql_driver implements DataInterface
{
    protected $conn;
    public function __construct(array $config)
    {
        
        
        $this->conn = $this->connect($config);
        if(!$this->conn)
        {
            die('Cannot connect to postgresql');
        }
    }

    
        
    public function fetchData(mixed $querypara):array
    {
        $q = $this->query($querypara);
        $data=[];
        while($r=$this->fetchArray($q))
        {
            array_push($data,$r);
        }

        return $data;
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
            $cnstring = sprintf("host=%s user=%s password=%s dbname=%s options='--client_encoding=UTF8 '",$host,$user,$pass,$name);        
            $cn = pg_connect($cnstring,PGSQL_CONNECT_FORCE_NEW);        
        }
        
        return $cn;
    }

    protected function query(string $sql) 
    {   
        $query = pg_query($this->conn, $sql);        
        return $query;
    }

    protected function fetchArray($q)
    {
        try 
        {
                $row = pg_fetch_assoc($q);
                return $row;                
        }
        catch (\Throwable $e)
        {
            // Throw error.
        }
    }
    
}