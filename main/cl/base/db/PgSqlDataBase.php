<?php
class PgSqlDataBase
{
    
    private $_host = null;
    private $_port = null;
    private $_username = null;
    private $_password = null;
    private $_dbname = null;
    private $_charset = null;
    private $_query_result = null;
    private $_last_insertsql_tablename = null;
    
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
        $conn_str = "host=$this->_host dbname=$this->_dbname user=$this->_username password=$this->_password port=$this->_port";
        //echo($conn_str);die();
        $conn = pg_connect($conn_str);
        try{
            if(!$conn){
                throw new SystemError("DbConnection:".$this->_host."-".pg_last_error(),SystemError::DB_CONNECT_FAIL);
            }
        }catch(SystemError $e){
            //Cacti::sendWarningMsg('db connect error',$e->getMessage());
            $msg = "err_num:".$e->getCode()."|err_msg:".$e->getMessage();
            BaseLog::setDbLog($msg);
        }
        //mysql_query("set names '$this->charset'");
        $this->_conn = $conn;
    }
    
    
    //过滤参数
    private static function escapeString($params){

    }
    
    
    public function query($sql){
        $sql = trim($sql);
        try{
            $query_result = pg_query($this->_conn,$sql);
            if(false===$query_result){
                throw new SystemError("SqlError:".pg_last_error(),SystemError::DB_SQL_FAIL);
            }
        }catch(SystemError $e){
            //Cacti::sendWarningMsg('db query error',$e->getMessage());
            $msg = "err_num:".$e->getCode()."|err_msg:".$e->getMessage()."|sql:$sql";
            BaseLog::setDbLog($msg);
            return $query_result;
        }
        $this->_query_result = $query_result;
        if(preg_match("/^insert/i",$sql)){
            //$this->_last_insertsql_tablename = preg_replace("/(^insertinto)|([(][^)]+[)])|(values)/",'',str_replace(' ','',$sql));
            preg_match("/^insertinto([^(]+?)\(/",str_replace(' ','',$sql),$match);
            $this->_last_insertsql_tablename = isset($match[1])? $match[1] : null;
        }
        return $query_result;
    }
    
    public function select($sql){
        $result = $this->query($sql);
        $arr = array();
        if($result!==false){
            while ($row = pg_fetch_array($result,null,PGSQL_ASSOC)) {
                $arr[] = $row;
            }
        }
        pg_free_result($result);
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
        if(is_null($this->_last_insertsql_tablename)){
            return false;
        }
        $sequence_name = $this->_last_insertsql_tablename.'_'.$primaryKeyname.'_seq';
        $sql = "select currval('$sequence_name')";
        $primaryKeyname_info = $this->selectOne($sql);
        return $primaryKeyname_info? $primaryKeyname_info['currval'] : false;
    }
    
    public function closeDb(){
        pg_close($this->_conn);
    }
    
}
?>