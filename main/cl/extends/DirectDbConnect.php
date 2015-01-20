<?php
/**
 * DirectDbConnect 获取数据库直连类-不通过框架本身的数据库连接机制
 * @author yichen
 * @copyright 2014
 */
class DirectDbConnect
{
    public static $_instance = null;
    protected $_direct_db_conn = null;
    public $flag = null;
    
    function __construct($db_type,$direct_db_connect_config){
        try{
            $direct_db_connect_type = strtoupper(Loader::getSelfConfigParams($db_type));
            $direct_db_connect_config = json_decode(Loader::getSelfConfigParams($direct_db_connect_config),true);
            $db_config = isset($direct_db_connect_config['host'])? $direct_db_connect_config : $direct_db_connect_config[mt_rand(0,count($direct_db_connect_config)-1)];
            switch($direct_db_connect_type){
                case "MYSQL":
                    $this->_direct_db_conn = new MySqlDataBase($db_config);
                    break;
                case "PGSQL":
                    $this->_direct_db_conn = new PgSqlDataBase($db_config);
                    break;
                default:
                    $this->_direct_db_conn = new MySqlDataBase($db_config);
                    break;
            }
            $this->flag = time();
        }catch(Exception $e){
            exit($e->getCode());
        }
    }
    
    //获取对象实例句柄
    public static function getInstance($db_type,$direct_db_connect_config){
        if(is_null(self::$_instance) || !isset(self::$_instance[$direct_db_connect_config])){
            self::$_instance[$direct_db_connect_config] = new DirectDbConnect($db_type,$direct_db_connect_config);
        }
        return self::$_instance[$direct_db_connect_config];
    }
    
    public function getDbconnect(){
        return $this->_direct_db_conn;
    }

    public static function getStaticDbconnect($db_type,$direct_db_connect_config){
        return self::getInstance($db_type,$direct_db_connect_config)->getDbconnect();
    }

    public static function closeDirectDbConnect($direct_db_connect_config = null){
        //默认关闭所有直连
        if(is_null($direct_db_connect_config)){
            if(is_array(self::$_instance)){
                foreach(self::$_instance as $key=>$conn_instance){
                    self::$_instance[$key]->getDbconnect()->closeDb();
                    unset(self::$_instance[$key]);
                }
            }
        }else{
            if(!is_null(self::$_instance) && isset(self::$_instance[$direct_db_connect_config])){
                self::$_instance[$direct_db_connect_config]->getDbconnect()->closeDb();
                unset(self::$_instance[$direct_db_connect_config]);
            }
        }

    }
}

?>