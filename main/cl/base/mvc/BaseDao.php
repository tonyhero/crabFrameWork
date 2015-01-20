<?php
class BaseDao
{
    public static function staticGetDbConnect($db = 'master'){
        $instance = Loader::getLoader();
        $db_type = Loader::getConfigParams($instance,"DBTYPE");
        $db_cluster = $instance->getDbCluster($db_type);
        return ($db=='master')? $db_cluster->getDbMasterInstance() : $db_cluster->getDbSlaverInstance() ;
    }

    protected function getDbConnect($db = 'master'){
        $instance = Loader::getLoader();
        $db_type = Loader::getConfigParams($instance,"DBTYPE");
        $db_cluster = $instance->getDbCluster($db_type);
        return ($db=='master')? $db_cluster->getDbMasterInstance() : $db_cluster->getDbSlaverInstance() ;
    }

    public function insert($data_array,$tablename = null){
        if($tablename===null){
            $tablename = $this->_tablename;
        }
        $sql = "insert into ".$tablename."(";
        $field = "";
        $value = "";
        foreach($data_array as $key=>$val){
            $field .= $key.",";
            $value .= "'".str_replace("'",'"',$val)."',";
        }
        $sql .= trim($field,",").") values(";
        $sql .= trim($value,",").")";
        //echo($sql."\n");die();
        $db = $this->getDbConnect('master');
        return $db->executeSql($sql);
    }

    public function update($condition,$data_array,$tablename = null){
        if($tablename===null){
            $tablename = $this->_tablename;
        }
        $sql = "update ".$tablename." set ";
        $val_str = "";
        foreach($data_array as $key=>$val){
            $val = str_replace("'",'"',$val);
            if(is_null($val))
            {
            $val_str .= " {$key} = null,";
            }
            else{
                $val_str .= " ".$key."= '".$val."',";
            }
        }
        $val_str = rtrim($val_str,",");
        $sql .= $val_str;
        $where = " where 1=1 ";
        foreach($condition as $key=>$val){
            $where .= " and ".$key." = '".$val."'";
        }
        $sql .= $where;
        $db = $this->getDbConnect('master');
        //echo($sql);echo("\n");die();
        return $db->executeSql($sql);
    }

    public function getLastInsertId($primaryKeyname = null){
        $db = $this->getDbConnect('master');
        if(!is_null($primaryKeyname)){
            return $db->getInsertId($primaryKeyname);
        }
        return $db->getInsertId();
    }

    public function select($field,$condition,$order = null,$limit_str = null,$tablename = null){
        if($tablename===null){
            $tablename = $this->_tablename;
        }
        $db = $this->getDbConnect('slaver');
        $where = " where 1=1 ";
        foreach($condition as $key=>$val){
            $where .= " and ".$key." = '".$val."'";
        }
        $orderstr = "";
        if($order!=null){
            $orderstr = " order by ".$order;
        }
        $sql = "select ".$field." from ".$tablename.$where.$orderstr;
        $sql = (is_null($limit_str))? $sql : $sql.' '.$limit_str;
        //error_log($sql);
        return $db->select($sql);
    }

    public function selectOne($field,$condition,$order = null,$limit_str = null,$tablename = null){
        if($tablename===null){
            $tablename = $this->_tablename;
        }
        $db = $this->getDbConnect('slaver');
        $where = " where 1=1 ";
        foreach($condition as $key=>$val){
            $where .= " and ".$key." = '".$val."'";
        }
        $orderstr = "";
        if($order!=null){
            $orderstr = " order by ".$order;
        }
        $sql = "select ".$field." from ".$tablename.$where.$orderstr;
        $sql = (is_null($limit_str))? $sql : $sql.$limit_str;

        return $db->selectOne($sql);
    }

    public function delete($condition = array(),$tablename = null){
        if($tablename===null){
            $tablename = $this->_tablename;
        }
        $db = $this->getDbConnect('master');
        $where = " where 1=1 ";
        foreach($condition as $key=>$val){
            $where .= " and ".$key." = '".$val."'";
        }
        $sql = "delete from ".$tablename.$where;
        return $db->executeSql($sql);
    }

    public function getTbname(){
        return $this->_tablename;
    }
    
    public function pgarray_to_phparray($postgresArray){
        $postgresStr = trim($postgresArray,"{}");
        $elmts = explode(",",$postgresStr);
        return $elmts;
    }

    public function phparray_to_pgarray_str($phparray){
        if(!$phparray) return "{}";
        return "{\"" . implode('","',$phparray) . "\"}";
    }

    public function phparray_to_pgarray_int($phparray){
        if(!$phparray) return "{}";
        return "{" . implode(',',$phparray) . "}";
    }
}
?>
