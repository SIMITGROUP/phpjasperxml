<?php

namespace simitsdk\phpjasperxml;

use Throwable;

trait PHPJasperXML_variables
{

    /**
     * pre-compute all variables after switch row
     * @param int $rowno
     */
    protected function computeVariables(int $rowno)
    {
        
        foreach($this->variables as $varname=>$setting)
        {            
            $setting['calculation']=$setting['calculation']??'None';
            $setting['incrementType']=$setting['incrementType']??'';
            $setting['initialValueExpression']=$setting['initialValueExpression']??'';
            $setting['variableExpression']=$setting['variableExpression']??'';
            $setting['incrementType']=$setting['incrementType']??'';
            $setting['incrementGroup']=$setting['incrementGroup']??'';
            $setting['resetType']=$setting['resetType']??'None';
            $setting['resetGroup']=$setting['resetGroup']??'';                
            $resettype = $setting['resetType'];            
            $resetGroup = $setting['resetGroup'];
            $initialValueExpression = $setting['initialValueExpression'];
            $isreset = false;



            if(isset($this->variables[$varname]['lastresetvalue']))
            {
                $lastresetvalue=$this->variables[$varname]['lastresetvalue'];
                $resetvalue = $this->getResetValue($varname, $resettype,$resetGroup,$initialValueExpression);                
                // echo "\ncompare reset value $lastresetvalue === $resetvalue \n";
                if($lastresetvalue != $resetvalue)
                {
                    $isreset = true;
    
                    $this->variables[$varname]['lastresetvalue']=$resetvalue;
                }
    
            }
            
            
            $calculation = $setting['calculation'];
            if(!empty($calculation))
            {
                if(in_array($calculation,['StandardDeviation','Variance','DistinctCount','Count']))
                {
                    die("variable $varname using calculation method $calculation which is not supported.");
                }
                $computeMethodName = 'compute_'.$calculation;
            }
            else
            {
                $computeMethodName = 'compute_None';
            }
            // echo "\n var name = $varname $computeMethodName\n";
            // print_r($setting);
            if(!method_exists($this,$computeMethodName))
            {
                $msg = sprintf("Variable '%s' use calculation type '%s' which is not supported, '%s()' not found",$varname,$calculation,$computeMethodName);
                if($this->debugtxt) echo "\n".$msg."\n";
            }
            else
            {
                $computevalue = call_user_func([$this,$computeMethodName],$varname,$setting,$rowno,$isreset);
                $this->variables[$varname]['lastvalue'] = $this->variables[$varname]['value'];
                $this->variables[$varname]['value']=$computevalue;
            }                        
        }        
    }

    protected function getResetValue(string $varname, string $resettype,string $resetgroup,mixed $initialValueExpression)
    {
        $data = null;
        switch($resettype)
        {
            case 'Report': //only reset during first initialization
                $data = $initialValueExpression;
                break;
            case 'Page':
                $data = $this->output->PageNo();
                break;
            case 'Column':
                $data = $this->output->getColumnNo();
                break;
            case 'Group':
                $groupsetting = $this->groups[$resetgroup];
                $data = $groupsetting['value'];
                break;
            case 'None':
                $data = null;
                break;
            case 'Master':                
                die('Variable "$varname" using resetType "Master", which is not support yet');
                break;
        }
        return $data;
    }
    
    /**
     * Design for variable using "No calculation function", this compute method process current row data, without aggregation (like sum, average)
     * @param string $varname variable name
     * @param array $setting all attributes of variable
     * @param int $rowno
     * @param bool $isreset
     * @return mixed $variablevalue
     */
    protected function compute_None(string $varname,array $setting,int $rowno,bool $isreset = false): mixed
    {
        $variablevalue = '';
        $variableExpression = $setting['variableExpression'];
        $variablevalue = $this->executeExpression($variableExpression);        
        return $variablevalue;
    }

    /**
     * Design for variable using "System", this compute method process current row data, without aggregation (like sum, average), expression itself is the value
     * @param string $varname variable name
     * @param array $setting all attributes of variable
     * @param int $rowno
     * @param bool $isreset
     * @return mixed $variablevalue
     */
    protected function compute_System(string $varname,array $setting,int $rowno,bool $isreset = false): mixed
    {
        $variablevalue = $setting['variableExpression'];        
        return $variablevalue;
    }
    

    /**
     * Design for variable using "Sum", this compute method run aggregation according reset type
     * @param string $varname variable name
     * @param array $setting all attributes of variable
     * @param int $rowno
     * @param bool $isreset
     * @return mixed $variablevalue
     */
    protected function compute_Sum(string $varname,array $setting, int $rowno, bool $isreset=false): mixed
    {        
        $prevvalue=$this->getVariableValue($varname) ?? 0;
        $variableExpression = $setting['variableExpression'];
        // print_r($setting);
        // echo "<hr/>";
        $newvalue = $this->executeExpression($variableExpression) ?? 0;
        // $prevvalue = ($prevvalue == "''") ? 0 : $prevvalue;
        // $newvalue = ($newvalue == "''") ? 0 : $newvalue;
        // echo "\nvariableExpression = $variableExpression, prevvalue = $prevvalue, newvalue = $newvalue, isreset $isreset\n";
        if($isreset)
        {
            $variablevalue=$newvalue;
        }
        else
        {
            // echo "\$variablevalue=$prevvalue + $newvalue;";
            $variablevalue=$prevvalue + $newvalue;
        }        
        return $variablevalue;
    }

    /**
     * Design for variable using "Fist", this compute method run aggregation according reset type
     * @param string $varname variable name
     * @param array $setting all attributes of variable
     * @param int $rowno
     * @param bool $isreset
     * @return mixed $variablevalue
     */
    protected function compute_First(string $varname,array $setting, int $rowno, bool $isreset=false): mixed
    {        
        $variablevalue=$this->getVariableValue($varname);
        $variableExpression = $setting['variableExpression'];
        $newvalue = $this->executeExpression($variableExpression);
        // echo "\nvariableExpression = $variableExpression, prevvalue = $prevvalue, newvalue = $newvalue, isreset $isreset\n";
        if($isreset)
        {
            $variablevalue=$newvalue;
        }
        return $variablevalue;
    }
    /**
     * Design for variable using "Lowest", this compute method run aggregation according reset type
     * @param string $varname variable name
     * @param array $setting all attributes of variable
     * @param int $rowno
     * @param bool $isreset
     * @return mixed $variablevalue
     */
    protected function compute_Lowest(string $varname,array $setting, int $rowno, bool $isreset=false): mixed
    {        
        $prevvalue=$this->getVariableValue($varname);
        $variableExpression = $setting['variableExpression'];
        $newvalue = $this->executeExpression($variableExpression);
        // echo "\nvariableExpression = $variableExpression, prevvalue = $prevvalue, newvalue = $newvalue, isreset $isreset\n";
        if($isreset)
        {
            $variablevalue=$newvalue;
        }
        else
        {
            if($prevvalue >$newvalue)
            {
                $variablevalue=$newvalue;
            }
            else
            {
                $variablevalue=$prevvalue;
            }
        }        
        return $variablevalue;
    }
    /**
     * Design for variable using "Highest", this compute method run aggregation according reset type
     * @param string $varname variable name
     * @param array $setting all attributes of variable
     * @param int $rowno
     * @param bool $isreset
     * @return mixed $variablevalue
     */
    protected function compute_Highest(string $varname,array $setting, int $rowno, bool $isreset=false): mixed
    {        
        $prevvalue=$this->getVariableValue($varname);
        $variableExpression = $setting['variableExpression'];
        $newvalue = $this->executeExpression($variableExpression);
        // echo "\nvariableExpression = $variableExpression, prevvalue = $prevvalue, newvalue = $newvalue, isreset $isreset\n";
        if($isreset)
        {
            $variablevalue=$newvalue;
        }
        else
        {
            if($prevvalue < $newvalue)
            {
                $variablevalue=$newvalue;
            }
            else
            {
                $variablevalue=$prevvalue;
            }
        }        
        return $variablevalue;
    }

    /**
     * Design for variable using "Average", this compute method run aggregation according reset type
     * @param string $varname variable name
     * @param array $setting all attributes of variable
     * @param int $rowno
     * @param bool $isreset
     * @return mixed $variablevalue
     */
    protected function compute_Average(string $varname,array $setting, int $rowno, bool $isreset=false): mixed
    {        
        $prevvalue=$this->getVariableValue($varname);
        $variableExpression = $setting['variableExpression'];
        $newvalue = $this->executeExpression($variableExpression);
        // echo "\nvariableExpression = $variableExpression, prevvalue = $prevvalue, newvalue = $newvalue, isreset $isreset\n";
        $this->variables[$varname]['compute_count'] = $this->variables[$varname]['compute_count'] ?? 0;
        $this->variables[$varname]['compute_sum'] = $this->variables[$varname]['compute_sum'] ?? 0;
        


        if($isreset)
        {
            $this->variables[$varname]['compute_count']=1;
            $this->variables[$varname]['compute_sum']=$newvalue;
            
        }
        else
        {
            $this->variables[$varname]['compute_count'] += 1;
            $this->variables[$varname]['compute_sum'] += $newvalue;
            
        }        
        $variablevalue = $this->variables[$varname]['compute_sum'] / $this->variables[$varname]['compute_count'];
        return $variablevalue;
    }


}
