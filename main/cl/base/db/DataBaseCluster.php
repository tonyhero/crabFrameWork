<?php
class DataBaseCluster
{
    private $_dbinstance = array();
    private $_dbtype = null;
    private $_db_config = null;
    private $_dbslaver_seed = null;
    
    public function __construct($db_config,$db = "mysql"){
        $this->_dbtype = $db;
        $this->_db_config = $db_config;
    }
    
    
    private function initCluster($db_config,$db){
        $db = ($db=="pgsql")? "PgSqlDataBase" : "MySqlDataBase";
        $this->_dbinstance['master'] = new $db($db_config['master']);
        foreach($db_config['slaver'] as $key=>$single_config){
            $this->_dbinstance['slaver'][$key] = new $db($single_config);
        }
    }
    
    //初始化主库
    private function initClusterMaster(){
        $db = ($this->_dbtype=="pgsql")? "PgSqlDataBase" : "MySqlDataBase";
        if(!isset($this->_dbinstance['master'])){
            $this->_dbinstance['master'] = new $db($this->_db_config['master']);
        }
        return $this->_dbinstance['master'];
    }
    
    //初始化从库
    private function initClusterSlaver(){
        $db = ($this->_dbtype=="pgsql")? "PgSqlDataBase" : "MySqlDataBase";
        $seed = $this->getSlaverSeed();
        if(!isset($this->_dbinstance['slaver'][$seed])){
            $this->_dbinstance['slaver'][$seed] = new $db($this->_db_config['slaver'][$seed]);
        }
        return $this->_dbinstance['slaver'][$seed];
    }
    
    private function getSlaverSeed(){
        if(is_null($this->_dbslaver_seed)){
            $this->_dbslaver_seed = mt_rand(0,count($this->_db_config['slaver'])-1);
        }
        return $this->_dbslaver_seed;
    }
    
    //获取主库的连接实例
    public function getDbMasterInstance(){
        return $this->initClusterMaster();
    }
    
    
    //随即算法获取从库对象,可根据不同架构更改对应算法
    public function getDbSlaverInstance(){
        return $this->initClusterSlaver();
    }
    
    public function closeCluster(){
        if(isset($this->_dbinstance['master'])) $this->_dbinstance['master']->closeDb();
        if(isset($this->_dbinstance['slaver'])){
            foreach($this->_dbinstance['slaver'] as $db){
                $db->closeDb();
            }
        }
    }
}
?>