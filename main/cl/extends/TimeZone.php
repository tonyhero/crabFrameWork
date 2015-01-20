<?php
class TimeZone
{
    private static $_instance = null;
    private $_dbconnect = null;
    private $_timezone_config = null;
    const BJ_TIMEZONE = "+0800";//东八区
    
    //获取时区配置
    public static function getTimezoneConfig(){
        $instance = self::getInstance();
        if(is_null($instance->_timezone_config)){
            $timezone_filepath = Loader::getSelfConfigParams("TIMEZONE-FILE-PATH");
            $file_content = file_get_contents($timezone_filepath);
            preg_match_all("/([A-Z\d]+)\s([\+\-]\d+)/",$file_content,$match);
            $config = array();
            if($match){
                foreach($match[1] as $key=>$airport){
                    $config[$airport] = $match[2][$key];
                }
            }
            $instance->_timezone_config = $config;
        }
        return $instance->_timezone_config;
    }
    
    private static function getInstance(){
        if(is_null(self::$_instance)){
            self::$_instance = new TimeZone();
        }
        return self::$_instance;
    }
    
    private function getDbconnect(){
        if(is_null($this->_dbconnect)){
            $this->_dbconnect = new MySqlDataBase(json_decode(Loader::getSelfConfigParams("SUMMER-TIMEZONE"),true));
        }
        return $this->_dbconnect;
    }
    
    private function closeDbconnect(){
        if(!is_null($this->_dbconnect)){
            $this->_dbconnect->closeDb();
            $this->_dbconnect = null;
        }
    }
    
    //获取夏令时的时间差(小时)
    public static function getSummerTimezoneLag($airport,$date){
        $airport_info = InfoCenter::getCountryCityByAirport($airport);//获取城市
        $city_code = $airport_info? $airport_info['citycode'] : $airport;
        $instance = self::getInstance();
        $summer_config = array();
        $year = date("Y");
        $date = str_replace("-","",$date);
        $sql = "select cityCode,dstStart,dstEnd,offset from DaylightSavingTime where publishtarget = 'A'";
        $sql .= " GROUP BY cityCode,dstStart,dstEnd,offset HAVING cityCode = '$city_code'";
        $summer_list = $instance->getDbconnect()->select($sql);
        $instance->closeDbconnect();
        if($summer_list){
            foreach($summer_list as $pdata){
                $date_start = $year.$pdata['dstStart'];
                $date_end = $year.$pdata['dstEnd'];
                if(($date>=$date_start) && ($date<=$date_end)){
                    return intval($pdata['offset']);
                }
            }
        }
        return 0;
    }
    
    
    //获取机场时区
    public static function getAirportTimezone($airport){
        $timezone_config = self::getTimezoneConfig();//获取时区配置
        if(!isset($timezone_config[$airport])){
            $logmsg = "cann't find timezone config by airport:$airport";
            BaseLog::getAirportTimezoneErrorLog($logmsg);
            return false;
        }
        return $timezone_config[$airport];
    }
    
    //将机场本地时间转换成北京时间
    public static function getAirportBjTime($airport,$time){
        $airport_timezone = self::getAirportTimezone($airport);
        if($airport_timezone!==false){
            $airport_timezone = intval($airport_timezone)/100;
            $date = date("Ymd",strtotime($time));
            $airport_timezone = $airport_timezone - TimeZone::getSummerTimezoneLag($airport,$date);//获取转换成夏令时的时区
            $time_diff = intval(TimeZone::BJ_TIMEZONE)/100 - $airport_timezone;//计算机场与北京的时差(单位:小时)
            $time = date("Y-m-d H:i:s",strtotime($time) + $time_diff*3600);//转换成北京时间
        }
        return $time;
    }
}
?>