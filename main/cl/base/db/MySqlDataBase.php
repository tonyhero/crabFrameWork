<?php
class MySqlDataBase
{
    
    private $_host = null;
    private $_port = null;
    private $_username = null;
    private $_password = null;
    private $_dbname = null;
    private $_charset = null;
    
    private $_conn = null;
    
    function __construct($dbconfig){
        $this->_host = $dbconfig['host'];
        $this->_port = $dbconfig['port'];
        $this->_username = $dbconfig['username'];
        $this->_password = $dbconfig['password'];
        $this->_dbname = $dbconfig['dbname'];
        $this->_charset = isset($dbconfig['charset'])? $dbconfig['charset'] : 'utf8';
        
        $this->createDbConnection();
    }
    
    private function createDbConnection(){
        $conn = mysql_connect($this->_host.":".$this->_port,$this->_username,$this->_password);
        try{
            if(!$conn){
                throw new SystemError("DbConnection:".$this->_host."-".mysql_error(),SystemError::DB_CONNECT_FAIL);
            }else{
                if(false===mysql_select_db($this->_dbname,$conn)){
                    throw new SystemError("DbConnection:".$single_config['host']."-SelectDB@".$this->_dbname.mysql_error(),SystemError::DB_CONNECT_FAIL);
                }
            }
        }catch(SystemError $e){
            Cacti::sendWarningMsg('db connect error',$e->getMessage());
            $msg = "err_num:".$e->getCode()."|err_msg:".$e->getMessage();
            BaseLog::setDbLog($msg);
        }
        mysql_query("set names '$this->_charset'");
        $this->_conn = $conn;
    }
    
    private static function escapeString($params){
        if(is_array($params)){
            foreach($params as $key=>$val){
                $params[$key] = mysql_escape_string($val);
            }
        }else{
            $params = mysql_escape_string($params);
        }
        return $params;
    }
    
    public function query($sql){
        try{
            $query_result = mysql_query($sql,$this->_conn);
            if(false===$query_result){
                throw new SystemError("SqlError:".mysql_error(),SystemError::DB_SQL_FAIL);
            }
        }catch(SystemError $e){
            $msg = "err_num:".$e->getCode()."|err_msg:".$e->getMessage();
            Cacti::sendWarningMsg('db query error',$e->getMessage());
            BaseLog::setDbLog($msg);
            return $query_result;
        }
        return $query_result;
    }
    
    public function select($sql){
        $result = $this->query($sql);
        $arr = array();
        if($result!==false){
            while ($row = mysql_fetch_array($result,MYSQL_ASSOC)) {
            //while ($row = mysql_fetch_row($result)) {
                $arr[] = $row;
            }
        }
        mysql_free_result($result);
        return $arr;
    }
    
    public function selectOne($sql){
        $arr = $this->select($sql);
        return empty($arr)? false : $arr[0];
    }
    
    public function executeSql($sql){
        return $this->query($sql);
    }
    
    public function getInsertId($primaryKeyname = 'id'){
        return mysql_insert_id($this->_conn);
    }
    
    public function closeDb(){
        mysql_close($this->_conn);
    }
    
}
?>