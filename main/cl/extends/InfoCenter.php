<?php
/**
 * InfoCenter 负责取机场,城市,国家的关系数据
 * @author yichen
 * @copyright 2013
 */
class InfoCenter
{
    const DB_TYPE = "INFOCENTER-DB-TYPE";//配置文件中的数据库类型名称
    const DB_CONNECT_CONFIG_NAME = "INFOCENTER-DB";//配置文件中的数据库连接配置名称
    const AIRPORT_DISTANCE_DB_CONNECT_CONFIG_NAME = "SKDB";
    const FLIGHT_ONTIME_DB_TYPE = "FLIGHT-ONTIME-CONNECT-TYPE";//航班准点率数据库类型名称
    const FLIGHT_ONTIME_DB_CONNECT_CONFIG_NAME = "FLIGHT-ONTIME-CONNECT-CONFIG";//航班准点率数据库连接信息
    
    
    const AIR_COMPANY_CONFIG_NAME = "AirComanyInfo.php";//航空公司配置文件
    const AIRPORT_CONFIG_NAME = "AirportInfo.php";//机场配置文件
    const CITY_CONFIG_NAME = "CityInfo.php";//城市信息配置文件
    const AIRPORT_TO_CITY_COUNTRY_CONFIG_NAME = "AirportToCityCountryInfo.php";//机场对应的城市国家信息配置文件
    
    //通过机场三字码获取对应所在的国家,城市
    public static function getCountryCityByAirport($airport){
        //从航空公司信息文件读取配置
        global $AirportToCityCountryInfoConfig;
        return isset($AirportToCityCountryInfoConfig[$airport])? $AirportToCityCountryInfoConfig[$airport] : false;

        //从db读取配置信息(暂时)
        /******************************
        $infocenter_db = DirectDbConnect::getStaticDbconnect(InfoCenter::DB_TYPE,InfoCenter::DB_CONNECT_CONFIG_NAME);
        return $infocenter_db->selectOne("select cou.countryname_zh,c.citycode from countrys cou, citys c, airportcode a where cou.validate = 1 and c.validate = 1 and a.validate = 1 and a.belongToCity = c.uri and c.belongToCountry = cou.uri and  a.Code = '$airport'");
        *******************************/
    }
    

    
    public static function getAirCompanyInfo($flightno){
        //从航空公司信息文件读取配置
        global $AirCompanyInfoConfig;
        $air_company_prefix = substr($flightno,0,2);
        if(isset($AirCompanyInfoConfig[$air_company_prefix])) return $AirCompanyInfoConfig[$air_company_prefix];
        
        //从db读取配置信息
        $infocenter_db = DirectDbConnect::getStaticDbconnect(InfoCenter::DB_TYPE,InfoCenter::DB_CONNECT_CONFIG_NAME);
        $ac_info['cname'] = null;
        $ac_info['cshortname'] = null;
        $ac_info['carrier'] = null;
        $sql = "select code,icon,name_zh,name_zh_short_2code from airline where code = '$air_company_prefix'";
        if($data = $infocenter_db->selectOne($sql)){
            $ac_info['cname'] = $data['name_zh'];
            $ac_info['cshortname'] = $data['name_zh_short_2code'];
            $ac_info['carrier'] = ($data['icon'])? 'http://source.qunar.com/site/images/'.str_replace('airlines','airlines/small',$data['icon']) : null;
            return $ac_info;
        }
        return $ac_info;
    }
    
    public static function getAirportName($airport_code){
        //从机场信息文件读取配置
        global $AirportInfoConfig;
        if(isset($AirportInfoConfig[$airport_code])) return $AirportInfoConfig[$airport_code]['airport_zh_short'];
        
        //从db读取配置信息
        $infocenter_db = DirectDbConnect::getStaticDbconnect(InfoCenter::DB_TYPE,InfoCenter::DB_CONNECT_CONFIG_NAME);
        $sql = "select airport_zh_short from airportcode where code = '$airport_code'";
        if($data = $infocenter_db->selectOne($sql)){
            return $data['airport_zh_short'];
        }
        return '';
    }
    
    public static function getCityName($city_code){
        //从城市信息文件读取配置
        global $CityInfoConfig;
        if(isset($CityInfoConfig[$city_code])) return $CityInfoConfig[$city_code]['cityname_zh'];
        
        //从db读取配置信息
        $infocenter_db = DirectDbConnect::getStaticDbconnect(InfoCenter::DB_TYPE,InfoCenter::DB_CONNECT_CONFIG_NAME);
        $sql = "select * from citys where citycode = '$city_code'";
        if($data = $infocenter_db->selectOne($sql)){
            return $data['cityname_zh'];
        }
        return '';
    }
    
    //获取所有机场对应的城市以及国家信息
    public static function getAllCountryCityAirportInfo(){
        $infocenter_db = DirectDbConnect::getStaticDbconnect(InfoCenter::DB_TYPE,InfoCenter::DB_CONNECT_CONFIG_NAME);
        $sql = "select a.code,cou.countryname_zh,c.citycode from countrys cou, citys c, airportcode a where cou.validate = 1 and c.validate = 1 and a.validate = 1 and a.belongToCity = c.uri and c.belongToCountry = cou.uri";
        if($data = $infocenter_db->select($sql)){
            foreach($data as $per_info){
                $tmp = array();
                if($per_info['code']=='') continue;
                $tmp['countryname_zh'] = $per_info['countryname_zh'];
                $tmp['citycode'] = $per_info['citycode'];
                $info_back[$per_info['code']] = $tmp;
            }
        }
        return $info_back;
    }
    
    public static function getAllAirCompanyInfo(){
        $infocenter_db = DirectDbConnect::getStaticDbconnect(InfoCenter::DB_TYPE,InfoCenter::DB_CONNECT_CONFIG_NAME);
        $info_back = array();
        $sql = "select code,icon,name_zh,name_zh_short_2code, name_zh_short from airline";
        if($data = $infocenter_db->select($sql)){
            foreach($data as $per_info){
                $tmp = array();
                if($per_info['code']=='') continue;
                $tmp['cname'] = $per_info['name_zh'];
                $tmp['name_zh_short'] = $per_info['name_zh_short'];
                $tmp['cshortname'] = $per_info['name_zh_short_2code'];
                $tmp['carrier'] = 'http://source.qunar.com/site/images/airlines/small/'.$per_info['code'].'.gif';
                $info_back[$per_info['code']] = $tmp;
            }
        }
        return $info_back;
    }
    

    
    public static function getAllAirportName(){
        $infocenter_db = DirectDbConnect::getStaticDbconnect(InfoCenter::DB_TYPE,InfoCenter::DB_CONNECT_CONFIG_NAME);
        $info_back = array();
        $sql = "select code,airport_zh_short from airportcode";
        if($data = $infocenter_db->select($sql)){
            foreach($data as $per_info){
                $tmp = array();
                if($per_info['code']=='') continue;
                $tmp['airport_zh_short'] = $per_info['airport_zh_short'];
                $info_back[$per_info['code']] = $tmp;
            }
        }
        return $info_back;
    }
    
    
    public static function getAllCityName(){
        $infocenter_db = DirectDbConnect::getStaticDbconnect(InfoCenter::DB_TYPE,InfoCenter::DB_CONNECT_CONFIG_NAME);
        $info_back = array();
        $sql = "select * from citys";
        if($data = $infocenter_db->select($sql)){
            foreach($data as $per_info){
                $tmp = array();
                if($per_info['citycode']=='') continue;
                $tmp['cityname_zh'] = $per_info['cityname_zh'];
                $info_back[$per_info['citycode']] = $tmp;
            }
        }
        return $info_back;
    }
    
    public static function getAllCityNameToCityCode(){
        $infocenter_db = DirectDbConnect::getStaticDbconnect(InfoCenter::DB_TYPE,InfoCenter::DB_CONNECT_CONFIG_NAME);
        $info_back = array();
        $sql = "select * from citys";
        if($data = $infocenter_db->select($sql)){
            foreach($data as $per_info){
                $tmp = array();
                if($per_info['cityname_zh']=='') continue;
                $tmp['citycode'] = $per_info['citycode'];
                $info_back[$per_info['cityname_zh']] = $tmp;
            }
        }
        return $info_back;
    }
    
    public static function getAllAirportDistance(){
        $infocenter_db = DirectDbConnect::getStaticDbconnect(InfoCenter::DB_TYPE,InfoCenter::AIRPORT_DISTANCE_DB_CONNECT_CONFIG_NAME);
        $info_back = array();
        $sql = "select depCode,arrCode,distance from FlightDistance where publishtarget = 'A'";
        if($data = $infocenter_db->select($sql)){
            foreach($data as $per_info){
                $info_back[self::getAirportDistanceKeyname($per_info['depCode'],$per_info['arrCode'])] = $per_info['distance'];
            }
        }
        return $info_back;
    }
    
    //获取航班准点率
    public static function getAllFlightOntimePercent(){
        $flight_ontime_db = DirectDbConnect::getStaticDbconnect(InfoCenter::FLIGHT_ONTIME_DB_TYPE,InfoCenter::FLIGHT_ONTIME_DB_CONNECT_CONFIG_NAME);
        $info_back = array();
        $sql = "select * from dynamic_flight";
        global $CityNameToCodeConfig;
        if($data = $flight_ontime_db->select($sql)){
            foreach($data as $per_info){
                if(!isset($CityNameToCodeConfig[$per_info['departurecity']]) || !isset($CityNameToCodeConfig[$per_info['arrivalcity']])) continue;
                $departCityCode = $CityNameToCodeConfig[$per_info['departurecity']]['citycode'];
                $arrivalCityCode = $CityNameToCodeConfig[$per_info['arrivalcity']]['citycode'];
                $keyname = self::getFlightOntimePercentKeyname($per_info['code'],$departCityCode,$arrivalCityCode);
                $info_back[$keyname] = floatval($per_info['rate_ontime'])*100;
            }
        }
        return $info_back;
    }
    
    //获取航班平均延误时间
    public static function getAllFlightDealyTime(){
        $flight_ontime_db = DirectDbConnect::getStaticDbconnect(InfoCenter::FLIGHT_ONTIME_DB_TYPE,InfoCenter::FLIGHT_ONTIME_DB_CONNECT_CONFIG_NAME);
        $info_back = array();
        $sql = "select * from dynamic_flight";
        global $CityNameToCodeConfig;
        if($data = $flight_ontime_db->select($sql)){
            foreach($data as $per_info){
                if(!isset($CityNameToCodeConfig[$per_info['departurecity']]) || !isset($CityNameToCodeConfig[$per_info['arrivalcity']])) continue;
                $departCityCode = $CityNameToCodeConfig[$per_info['departurecity']]['citycode'];
                $arrivalCityCode = $CityNameToCodeConfig[$per_info['arrivalcity']]['citycode'];
                $keyname = self::getFlightDelayTimeKeyname($per_info['code'],$departCityCode,$arrivalCityCode);
                $info_back[$keyname] = $per_info['avg_latetime'];
            }
        }
        return $info_back;
    }
    
    //获取起飞机场到降落机场之间的距离
    public static function getDepArrairportDistance($dep_airport,$arr_airport){
        $distance = RedisCache::getCacheInfo(self::getAirportDistanceKeyname($dep_airport,$arr_airport));
        return is_null($distance)? null : $distance;
    }
    
    //通过机场三字码获取机场的中文介绍
    public static function getAirportDescription($airport_code){
        $description = RedisCache::getCacheInfo(self::getAirportDescriptionKeyname($airport_code));
        return is_null($description)? null : $description;
    }
    
    //通过航班+起飞城市三字码+降落城市三字码查询航班的准点率
    public static function getFlightOntimePercent($flightno,$depcityCode,$arrcityCode){
        $ontime_percent = RedisCache::getCacheInfo(self::getFlightOntimePercentKeyname($flightno,$depcityCode,$arrcityCode));
        return is_null($ontime_percent)? null : $ontime_percent;
    }
    
    //通过航班+起飞城市三字码+降落城市三字码查询航班的平均延误时间
    public static function getFlightDelayTime($flightno,$depcityCode,$arrcityCode){
        $delay_time = RedisCache::getCacheInfo(self::getFlightDelayTimeKeyname($flightno,$depcityCode,$arrcityCode));
        return is_null($delay_time)? null : $delay_time;
    }
    
    //是否是国外机场
    public static function ifInterAirport($airport){
        $airport_info = self::getCountryCityByAirport($airport);
        if($airport_info && $airport_info['countryname_zh']=="中国"){
            return false;
        }
        return true;
    }
    
    //获取客户端所在城市的机场信息
    public static function getClientCurrentAirportInfo(){
        global $CityNameToCodeConfig;//城市中文名对应三字码
        global $AirportInfoConfig;//机场三字码对应机场简称
        $city_name = self::getClientCityname();//获取城市中文名
        $city_code = (isset($CityNameToCodeConfig[$city_name]))? $CityNameToCodeConfig[$city_name]['citycode'] : 'PEK';
        $airport_code = $city_code;
        if((!isset($AirportInfoConfig[$airport_code])) || ($AirportInfoConfig[$airport_code]['airport_zh_short']=='')){
            $airport_info['airport_code'] = 'PEK';
            $airport_info['airport_name'] = '首都机场';
            $airport_info['city_name'] = '北京';
        }else{
            $airport_info['airport_code'] = $airport_code;
            $airport_info['airport_name'] = $AirportInfoConfig[$airport_code]['airport_zh_short'];
            $airport_info['city_name'] = $city_name;
        }
        return $airport_info;
    }
    
    public static function getIP(){
        if(getenv("HTTP_CLIENT_IP")){
            $ip = getenv("HTTP_CLIENT_IP");
        }else if(getenv("HTTP_X_FORWARDED_FOR")){
            $ip = getenv("HTTP_X_FORWARDED_FOR");
        }else if(getenv("REMOTE_ADDR")){
            $ip = getenv("REMOTE_ADDR");
        }else{
            $ip = "Unknow";
        }
        return $ip;
    }
    
    public static function getClientCityname(){
        $ip = self::getIP();
        if(!Tools::is_ip($ip)) return "北京";
        $search_city_url = Loader::getSelfConfigParams("CLIENT-CURRENT-CITY-INTERFACE");
        if($back = HttpRequest::_curl_get(sprintf($search_city_url,$ip),20)){
            $parse_data = @json_decode($back,true);
            if(isset($parse_data['ret']) && $parse_data['ret']==true && isset($parse_data['data']['city']) && !is_null($parse_data['data']['city'])){
                return str_replace('市','',$parse_data['data']['city']);
            }
        }
        return "北京";
    }
    
    //通过机场三字码获取机场中文描述的缓存key名称
    public static function getAirportDescriptionKeyname($airport_code){
        return $airport_code.'-description';
    }
    
    //获取航班准点率的换存keyname
    public static function getFlightOntimePercentKeyname($flightno,$depcityCode,$arrcityCode){
        return $flightno.'-'.$depcityCode.'-'.$arrcityCode.'-percent';
    }
    
    //获取航班准点率的换存keyname
    public static function getFlightDelayTimeKeyname($flightno,$depcityCode,$arrcityCode){
        return $flightno.'-'.$depcityCode.'-'.$arrcityCode.'-average-delay';
    }
    
    //获取机场批->机场之间距离的环存keyname
    public static function getAirportDistanceKeyname($depCode,$arrCode){
        return $depCode.'-'.$arrCode.'-distance';
    }
}

?>
