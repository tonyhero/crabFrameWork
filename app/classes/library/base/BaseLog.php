<?php
class BaseLog
{
    public static function getLogPath($load_instance = null){
        return is_null($load_instance)? Loader::getConfigParams(Loader::getLoader(),"LOGPATH") : Loader::getConfigParams($load_instance,"LOGPATH");
    }
    
    /**
     * @param logName string 日志名称
     * @param logMsg string 需要写入的日志信息
     * @param load_instance object 加载器的句柄
    */
    public static function setLog($logName,$logMsg,$load_instance = null){
        $logName = self::getLogPath($load_instance)."/".$logName.date("Y-m-d").".log";
        $logMsg = "[".date("H:i:s")."]".$logMsg."\r\n";
        if(file_exists($logName)){
            file_put_contents($logName,$logMsg,FILE_APPEND);
        }else{
            file_put_contents($logName,$logMsg,FILE_APPEND);
            chmod($logName, 0777);
        }
    }
    
    public static function __callStatic($name, $arguments){
        $logName = preg_replace("/^set/",'',$name);
        self::setLog($logName,implode(',',$arguments));
    }
    
    //设置数据库错误日志
    public static function setDbLog($logMsg,$load_instance = null){
        $logName = "SqlError";
        self::setLog($logName,$logMsg,$load_instance);
    }
    
    //设置curl错误日志
    public static function setCurlErrorLog($logMsg,$load_instance = null){
        $logName = "Curl-Error";
        self::setLog($logName,$logMsg,$load_instance);
    }
    
    //demon 错误日志
    public static function setDemonErrorLog($logMsg,$load_instance = null){
        $logName = "Demon-Error";
        self::setLog($logName,$logMsg,$load_instance);
    }
    
    //demon 错误日志
    public static function setClientActionLog($logMsg,$load_instance = null){
        $logName = "Client-Action";
        self::setLog($logName,$logMsg,$load_instance);
    }
    
}
?>