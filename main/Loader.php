<?php
/**
 * 加载器
 * 
 * @package hbproject
 * @author chen.yi
 * @copyright 2013.08.28
 * @version $1.0$
 */
class Loader
{
    private $_config = null;
    private static $_handle = null;//自身的句柄
    private $_services = null;
    private $_models = null;
    private $_htmls = null;
    public $_dbcluster = null;
    private $_daos = null;


    function __construct(){
        self::loadconfig($this);
        //print_r($this->_config);die();
    }
    
    
    private static function _init(){
        if(is_null(self::$_handle)){
           self::$_handle = new Loader();
        }
    }
    
    //获取加载器实例
    public static function getLoader(){
        self::_init();
        return self::$_handle;
    }
    
    private static function loadconfig(&$instance){
        $config_path = WEBROOT.'/config/web.conf';
        $config_content = file_get_contents($config_path);
        $tmp = explode("\n",$config_content);
        foreach($tmp as $line){
            if($line=="") continue;
            $match = array();
            preg_match("/^\[([-@_A-Za-z\d]+)\]:\[([^\r\n]{0,})\]$/",$line,$match);
            if(!$match || count($match)!=3) continue;
            if($match[1]=="MASTER-DBCONFIG"){
                $instance->_config['dbconfig']['master'] = json_decode($match[2],true);
            }elseif($match[1]=="SLAVER-DBCONFIG"){
                $instance->_config['dbconfig']['slaver'] = json_decode($match[2],true);
            }else{
                $instance->_config[$match[1]] = $match[2];
            }
        }
    }
    
    public static function getSelfConfigParams($param){
        $instance = self::getLoader();
        if(isset($instance->_config[$param])){
            return $instance->_config[$param];
        }
        return false;
    }
    
    //获取配置文件相关参数值
    public static function getConfigParams($instance,$param){
        if(isset($instance->_config[$param])){
            return $instance->_config[$param];
        }
        return false;
    }
    
    //创建数据库连接集群
    public function getDbCluster($db = "mysql"){
        if(is_null($this->_dbcluster)){
            $this->_dbcluster = new DataBaseCluster($this->_config['dbconfig'],$db);
        }
        return $this->_dbcluster;
    }
    
    public static function closeDbCluster(){
        $instance = self::getLoader();
        if(!is_null($instance->_dbcluster)){
            $instance->_dbcluster->closeCluster();
            $instance->_dbcluster = null;
        }
    }
    
    //获取service服务
    public function getService($class_name){
        $class_perfect_name = $class_name.'_svc';
        if(!isset($this->_services[$class_name])){
            $model_path = WEBROOT.'/classes/model/service';
            $service_path = $model_path."/".$class_name."_svc.php";
            require_once($service_path);
            $this->_services[$class_name] = new $class_perfect_name();
        }
        return $this->_services[$class_name];
    }
    
    public static function pgetService($class_name){
        $handle = self::getLoader();
        return $handle->getService($class_name);
    }


    //获取model服务
    public function getModel($class_name){
        $class_perfect_name = $class_name.'_model';
        if(!isset($this->_models[$class_name])){
            $model_path = WEBROOT.'/classes/model';
            $model_path = $model_path."/".$class_name."_model.php";
            require_once($model_path);
            $this->_models[$class_name] = new $class_perfect_name();
        }
        return $this->_models[$class_name];
    }

    public static function pgetModel($class_name){
        $handle = self::getLoader();
        return $handle->getModel($class_name);
    }
    //加载摸板
    public static function loadView($html){
        return new BaseView($html);
    }
    
    public function getDao($class_name){
        $class_perfect_name = $class_name.'_dao';
        if(!isset($this->_daos[$class_name])){
            $dao_path = WEBROOT.'/classes/model/dao';
            $dao_path = $dao_path."/".$class_name."_dao.php";
            require_once($dao_path);
            $this->_daos[$class_name] = new $class_perfect_name();
        }
        return $this->_daos[$class_name];
    }
    
    public static function pgetDao($class_name){
        $handle = self::getLoader();
        return $handle->getDao($class_name);
    }
}