<?php
/**
 * RedisCache 提供redis操作
 * 
 * @package framework
 * @author yichen
 * @copyright 2014
 */
class RedisCache implements CacheInterface
{
    static $instance = null;
    private $_redis_connecion = null;
    private $_redis_config_php_file = null;//redis 连接串配置文件
    private $_connection_persistent_str = '&connection_persistent=false';//false:短连接  true长连接
    
    function __construct(){
        $this->_redis_config_php_file = Loader::getSelfConfigParams("STATIC-FILE-STORE-DIR").'RedisConfig.php';
        $this->checkRedisConnection();
    }
    
    public function getRedisConnect(){
        return $this->_redis_connecion;
    }
    
    //删除
    public static function del($key){
        $instance = self::getInstance();
        $cache_log_msg = 'action:del |key:'.$key;
        if(!is_null($redis = $instance->getRedisConnect())){
            $cache_log_msg .= '|result: success';
            BaseLog::redisOperateLog($cache_log_msg);
            return $redis->del($key);
        }
        $cache_log_msg .= '|result: connect fail';
        BaseLog::redisOperateLog($cache_log_msg);
        return false;
    }
    
    //设置自增
    public static function valIncrease($key,$step = 1){
        $instance = self::getInstance();
        $cache_log_msg = 'action:increase |key:'.$key.'|step:'.$step;
        if(!is_null($redis = $instance->getRedisConnect())){
            BaseLog::redisOperateLog($cache_log_msg);
            return (1 === $step)? $redis->incr($key) : $redis->incrby($key,$step);
        }
        return false;
    }
    
    
    /**
     * 设置缓存内容并且不过期,设置overwrite之后如果值存在,则覆盖
     * @param boolean    $if_overwrite 值存在是否覆盖,默认覆盖
     */
    public static function setCacheInfoWithoutTimeout($key,$val,$if_overwrite=true){
        $instance = self::getInstance();
        $cache_log_msg = 'action:set |key-without-timeout:'.$key.'|data:'.$val;
        if(!is_null($redis = $instance->getRedisConnect())){
            //能连接上redis
            if($redis->exists($key) && $if_overwrite){
                $redis->getset($key,$val);//覆盖
                $cache_log_msg .= '|result: over-write success';
            }else{
                $redis->set($key,$val);//如果已经过期或者没有设置
                $cache_log_msg .= '|result: success';
            }
            BaseLog::redisOperateLog($cache_log_msg);
            return true;
        }
        $cache_log_msg .= '|result: connect fail';
        BaseLog::redisOperateLog($cache_log_msg);
        return false;
    }
    
    /**
     * 设置缓存内容和过期时间,设置overwrite之后如果值存在,则覆盖然后重置过期时间
     * @param boolean    $if_overwrite 值存在是否覆盖,默认不重写
     */
    public static function setCacheInfo($key,$val,$cache_timeout_seconds,$if_overwrite=false){
        $instance = self::getInstance();
        $cache_log_msg = 'action:set |key:'.$key.'|cachetime:'.$cache_timeout_seconds.'|data:'.$val;
        if(!is_null($redis = $instance->getRedisConnect())){
            //能连接上redis
            if(!$redis->exists($key)) {
                $redis->setex($key,$cache_timeout_seconds,$val);//如果已经过期或者没有设置
                $cache_log_msg .= '|result: already exist';
                BaseLog::redisOperateLog($cache_log_msg);
            }else if($if_overwrite===true){
                $redis->getset($key,$val);
                $redis->expire($key,$cache_timeout_seconds);
                $cache_log_msg .= '|result: over-write success';
                BaseLog::redisOperateLog($cache_log_msg);
            }
            return true;
        }
        $cache_log_msg .= '|result: connect fail';
        BaseLog::redisOperateLog($cache_log_msg);
        return false;
    }
    
    public static function setKeyExpiretime($key,$cache_timeout_seconds){
        $instance = self::getInstance();
        $cache_log_msg = 'action:set-key-expiretime |key:'.$key.'|cachetime:'.$cache_timeout_seconds;
        if(!is_null($redis = $instance->getRedisConnect())){
            if(!$redis->exists($key)) {
                $cache_log_msg .= '|result: key not exist';
                BaseLog::redisOperateLog($cache_log_msg);
                return false;
            }
            $redis->expire($key,$cache_timeout_seconds);
            $cache_log_msg .= '|result: success';
            BaseLog::redisOperateLog($cache_log_msg);
            return true;
        }
        $cache_log_msg .= '|result: connect fail';
        BaseLog::redisOperateLog($cache_log_msg);
        return false;
    }
    
    public static function getCacheInfo($key){
        $instance = self::getInstance();
        $cache = null;
        if(!is_null($redis = $instance->getRedisConnect())){
            $cache = $redis->get($key);
        }
        BaseLog::redisOperateLog('action:get|key:'.$key.' |data:'.$cache);
        return $cache;
    }
    
    public static function getInstance(){
        if(is_null(self::$instance)){
            self::$instance = new RedisCache();
        }
        return self::$instance;
    }
    
    public static function closeRedisConnection(){
        $instance = self::getInstance();
        if(!is_null($redis = $instance->getRedisConnect())){
            $redis->quit();
        }
    }
    
    private function checkRedisConnection(){
        global $RedisConnectString;//redis连接串
        try{
            if(is_null($this->_redis_connecion)){
                $this->_redis_connecion = new Predis\Client($RedisConnectString.$this->_connection_persistent_str);
            }
            $this->_redis_connecion->del('test');
        }catch(Predis\PredisException $e){
            $errorMsg = $e->getMessage();
            $this->setErrorLog($RedisConnectString,$errorMsg);
            $connect_flag = false;
            $redis_config = json_decode(Loader::getSelfConfigParams("REDIS-CACHE-CONNECT-CONFIG"),true);
            foreach($redis_config as $c_str){
                if($c_str==$RedisConnectString){
                    continue;
                }
                try{
                    $this->_redis_connecion = new Predis\Client($c_str.$this->_connection_persistent_str);
                    $this->_redis_connecion->del('test');
                    $this->createRedisConnectString($c_str);
                    $connect_flag = true;
                    break;
                }catch(Predis\PredisException $e){
                    $errorMsg = $e->getMessage();
                    $this->setErrorLog($c_str,$errorMsg);
                }
            }
            
            //所有redis连接都不可用,则写报警日志
            if(false===$connect_flag){
                $this->_redis_connecion = null;
                BaseLog::warningLog('redis-connect-error: no redis-connect can be used');
            }
        }
    }
    
    
    private function createRedisConnectString($connect_str){
        $str = "<?php\r\n";
        $str .= "\$RedisConnectString = '$connect_str';\r\n";
        $str .= "?>";
        file_put_contents($this->_redis_config_php_file,$str);
    }
    
    private function setErrorLog($connect_string,$errorMsg){
        BaseLog::redisConnectErrorLog("connect-string:".$connect_string.'|errorMsg:'.$errorMsg);
    }
    
    
}
?>