<?php
/**
 * Cacti 负责监控数据存储
 * @author yichen
 * @copyright 2014
 */
class Cacti
{
    
    const STATUS_SEARCH_BY_FLNO_QPS = "search-status-by-flno-count";//航班号查询航班状态的接口查询量监控->键名
    const STATUS_SEARCH_BY_FLNO_PERMINUTE_RUNTIME_TOTLE_COUNT = "search-status-by-flno-perminute-runtime-totle_count";
    const STATUS_SEARCH_BY_FLNO_PERMINUTE_ACCESS_TOTLE_COUNT = "search-status-by-flno-perminute-access-totle-count";
    const WARPPER_ABNORMAL_DATA_BACK = "warpper-abnormal-data-back";
    const WARPPER_SEARCH_PERMINUTE_ACCESS_COUNT = "warpper-search-perminute-count";//warpper每分钟访问次数
    
    //过期时间配置,单位:秒
    static $TIME_OUT_CONFIG = array(
                        self::STATUS_SEARCH_BY_FLNO_QPS=>180,
                        self::STATUS_SEARCH_BY_FLNO_PERMINUTE_RUNTIME_TOTLE_COUNT=>180,
                        self::STATUS_SEARCH_BY_FLNO_PERMINUTE_ACCESS_TOTLE_COUNT=>180,
                        self::WARPPER_ABNORMAL_DATA_BACK=>180,
                        self::WARPPER_SEARCH_PERMINUTE_ACCESS_COUNT=>180,
                        );
    
    //设置不同warpper的抓取次数监控
    public static function setWarpperSearchCountPerMin($codebase){
        $keyname = $codebase.'-'.self::WARPPER_SEARCH_PERMINUTE_ACCESS_COUNT.date("Y-m-d-H-i");
        //设置初始值0,如果key已经存在则不做处理
        RedisCache::setCacheInfo($keyname,0,self::$TIME_OUT_CONFIG[self::WARPPER_SEARCH_PERMINUTE_ACCESS_COUNT]);
        RedisCache::valIncrease($keyname);//自增,默认1
    }
    
    
    //设置通过航班号查询航班状态的接口访问量
    public static function setStatusSearchByFlnoCountPerMin(){
        $keyname = self::STATUS_SEARCH_BY_FLNO_QPS.date("Y-m-d-H-i");
        //设置初始值0,如果key已经存在则不做处理
        RedisCache::setCacheInfo($keyname,0,self::$TIME_OUT_CONFIG[self::STATUS_SEARCH_BY_FLNO_QPS]);
        RedisCache::valIncrease($keyname);//自增,默认1
    }
    
    //获取通过航班号查询航班状态的接口前一分钟的访问量
    public static function getStatusSearchByFlnoCountPerMin(){
        $keyname = self::STATUS_SEARCH_BY_FLNO_QPS.date("Y-m-d-H-i",(time()-60));
        $count = RedisCache::getCacheInfo($keyname);
        return is_null($count)? 0 : $count;
    }
    
    //设置接口执行时间
    public static function setStatusSearchByFlnoRuntime($start,$end){
        $run_time_diff = Tools::getMicrotimeDiff($start,$end);
        //echo($run_time_diff);die();
        $ymdhi = date("Y-m-d-H-i");
        $runtime_keyname = self::STATUS_SEARCH_BY_FLNO_PERMINUTE_RUNTIME_TOTLE_COUNT.$ymdhi;
        $access_count_keyname = self::STATUS_SEARCH_BY_FLNO_PERMINUTE_ACCESS_TOTLE_COUNT.$ymdhi;
        RedisCache::setCacheInfo($runtime_keyname,0,self::$TIME_OUT_CONFIG[self::STATUS_SEARCH_BY_FLNO_PERMINUTE_RUNTIME_TOTLE_COUNT]);
        RedisCache::valIncrease($runtime_keyname,$run_time_diff);
        RedisCache::setCacheInfo($access_count_keyname,0,self::$TIME_OUT_CONFIG[self::STATUS_SEARCH_BY_FLNO_PERMINUTE_ACCESS_TOTLE_COUNT]);
        RedisCache::valIncrease($access_count_keyname);
    }
    
    //获取接口前一分钟的平均执行时间
    public static function getStatusSearchByFlnoAverageRuntimePerMin(){
        $pre_time = date("Y-m-d-H-i",(time()-60));
        $runtime_keyname = self::STATUS_SEARCH_BY_FLNO_PERMINUTE_RUNTIME_TOTLE_COUNT.$pre_time;
        $run_time_totle = RedisCache::getCacheInfo($runtime_keyname);
        $access_count_keyname = self::STATUS_SEARCH_BY_FLNO_PERMINUTE_ACCESS_TOTLE_COUNT.$pre_time;
        $access_count = intval(RedisCache::getCacheInfo($access_count_keyname));
        return (0===$access_count)? 0 : round($run_time_totle/$access_count);
    }
    
    //设置warpper返回的数据异常    
    public static function setAbnormalWarpperBack($data){
        $ymdhi = date("Y-m-d-H-i");
        $keyname = self::WARPPER_ABNORMAL_DATA_BACK.$ymdhi;
        RedisCache::setCacheInfo($keyname,0,self::$TIME_OUT_CONFIG[self::WARPPER_ABNORMAL_DATA_BACK]);
        RedisCache::valIncrease($keyname);
        BaseLog::setAbnormalWarpperBack(json_encode($data));
    }        
    
    //获取warpper前一分钟的异常数据个数
    public static function getAbnormalWarpperBack(){
        $pre_time = date("Y-m-d-H-i",(time()-60));
        $keyname = self::WARPPER_ABNORMAL_DATA_BACK.$pre_time;
        $abnormal_count = RedisCache::getCacheInfo($keyname);
        return (is_null($abnormal_count))? 0 : $abnormal_count;
    }
    
    //获取不同的warpper每分钟的warpper抓取次数
    public static function getWarpperSearchCountPerMin($codebase){
        $pre_time = date("Y-m-d-H-i",(time()-60));
        $keyname = $codebase.'-'.self::WARPPER_SEARCH_PERMINUTE_ACCESS_COUNT.$pre_time;
        $warpper_search_count = RedisCache::getCacheInfo($keyname);
        return (is_null($warpper_search_count))? 0 : $warpper_search_count;
    }
    
    public static function sendWarningMsg($subject,$errMsg){
        $message_receive = Loader::getSelfConfigParams("WARNING-MESSAGE-RECEIVE");
        //shell_exec("print '' | mail -s '$errMsg' $message_receive");
        //PlaycrabMail::send($subject,$errMsg);
    }
}

?>