<?php
/**
 * flightstatus_svc
 * 用于对外提供航班动态的数据服务
 * @author yichen
 * @copyright 2014
 */
class flightstatus_svc
{
    private $domestic_flightstatus_warpper_list = null;//航班飞行状态抓取数据源
    private $_limit_flightschedule_disappeartime = 50;
    
    function __construct(){
        $this->domestic_flightstatus_warpper_list = json_decode(Loader::getSelfConfigParams("DMOESTIC-STATUS-DATA-WARPPER-SOURCE-LIST"),true);
    }
    
    //获取用户所在的机场信息(机场名称,机场三字码)
    public function airportTrafficInfo(){
        return InfoCenter::getClientCurrentAirportInfo();
    }
    
    //国内通过起飞城市+降落城市+日期获取航班信息
    public function dRoute($params){
        global $CityNameToCodeConfig;
        //获取不到对应城市的信息,抛处异常
        if(!isset($CityNameToCodeConfig[$params['departure']]) || !isset($CityNameToCodeConfig[$params['arrival']])){
            throw new SystemError("请输入正确的城市名称",SystemError::PARAMS_WORONG);
        }
        $search_params['dptcity'] = $CityNameToCodeConfig[$params['departure']]['citycode'];
        $search_params['arrcity'] = $CityNameToCodeConfig[$params['arrival']]['citycode'];
        $search_params['date'] = $params['date'];
        $back = $this->getFlightstatusByDepArrDate($search_params);
        if($back['sectionnumber']>0){
            $order_keyname = "plan_local_dep_time";
            $back['data'] = Tools::array_sort($back['data'],$order_keyname);
            /***********
            $data = Tools::array_sort($back['data'],$order_keyname);
            $sort_data = array();
            foreach($data as $flight){
                $sort_data[] = $flight;
            }
            $back['data'] = $sort_data;
            ************/
        }
        return $back;
    }
    
    //加载航班动态按航班号查询(国内),需要调用
    public function getDflightInfo($params){
        $params['flightno'] = strtoupper($params['flightcode']);
        $flight_schedule_back = $this->getFlightstatusByFnoDate($params);
        $flight_schedule_back['flightInfo']['detail'] = $flight_schedule_back['data'];
        $flight_schedule_back['flightInfo']['schedule'] = array();
        unset($flight_schedule_back['data']);
        $schedule_info = $flight_schedule_back['flightInfo']['detail'];
        //生成班期数据
        if($flight_schedule_back['sectionnumber']>0){
            reset($schedule_info);
            $first_schedule = current($schedule_info);
            $end_schedule = end($schedule_info);
            $flight_schedule_back['flightInfo']['schedule'] = Loader::pgetService("skdatatmp")->getFlightDomesticScheduleInfoByFnoDepArrCity($params['flightno'],$first_schedule['dptcity'],$end_schedule['arrcity']);
            //print_r($flight_schedule_back['flightInfo']['schedule']);die();
            if($flight_schedule_back['flightInfo']['schedule']){
                global $AirportInfoConfig;
                foreach($flight_schedule_back['flightInfo']['schedule'] as $key=>$val){
                    $flight_schedule_back['flightInfo']['schedule'][$key]['depname'] = isset($AirportInfoConfig[$val['dep']])? $AirportInfoConfig[$val['dep']]['airport_zh_short'] : $flight_schedule_back['flightInfo']['schedule'][$key]['depname'];
                    $flight_schedule_back['flightInfo']['schedule'][$key]['arrname'] = isset($AirportInfoConfig[$val['arr']])? $AirportInfoConfig[$val['arr']]['airport_zh_short'] : $flight_schedule_back['flightInfo']['schedule'][$key]['arrname'];
                    $flight_schedule_back['flightInfo']['schedule'][$key]['schedule'] = explode(',',$val['schedule']);
                }
            }
            
            $transform_params = array(
                                    "cname"=>"name",
                                    "carrier"=>"icon",
                                    "planetype"=>"type",
                                    "meal"=>"food",
                                    "dptairport_name"=>"departure",
                                    "dpttower"=>"dt",
                                    "plan_local_dep_time"=>"ptd",
                                    "expect_local_dep_time"=>"etd",
                                    "actual_local_dep_time"=>"atd",
                                    "plan_local_arr_time"=>"pta",
                                    "expect_local_arr_time"=>"eta",
                                    "actual_local_arr_time"=>"ata",
                                    "plan_flighttime"=>"flight_time",
                                    "distance"=>"flight_distance",
                                    "arrairport_name"=>"arrival",
                                    "arrtower"=>"at",
                                    "status"=>"state",
                                    );
            $server_time = date("Y-m-d");
            $s_list = array(
                            "计划"=>0,
                            "起飞"=>1,
                            "到达"=>2,
                            "延误"=>3,
                            "取消"=>4,
                            "备降"=>5,
                            "今日无航班"=>6);
            foreach($flight_schedule_back['flightInfo']['detail'] as $key=>$single_info){
                $flight_schedule_back['flightInfo']['detail'][$key]['serverTime'] = $server_time;
                $flight_schedule_back['flightInfo']['detail'][$key]['accross'] = (substr($flight_schedule_back['flightInfo']['detail'][$key]['plan_local_arr_time'],0,10)>$params['date'])? 1 : 0;
                $flight_schedule_back['flightInfo']['detail'][$key]['alternateAirport'] = null;
                
                foreach($transform_params as $tkey=>$ckey){
                    $flight_schedule_back['flightInfo']['detail'][$key][$ckey] = $flight_schedule_back['flightInfo']['detail'][$key][$tkey];
                    unset($flight_schedule_back['flightInfo']['detail'][$key][$tkey]);
                }
                $flight_schedule_back['flightInfo']['detail'][$key]['state'] = $s_list[$flight_schedule_back['flightInfo']['detail'][$key]['state']];
                if($flight_schedule_back['flightInfo']['detail'][$key]['state']==$s_list['到达'] && !is_null($flight_schedule_back['flightInfo']['detail'][$key]['actual_flighttime'])) $flight_schedule_back['flightInfo']['detail'][$key]['flight_time'] = $flight_schedule_back['flightInfo']['detail'][$key]['actual_flighttime'];
                $flight_schedule_back['flightInfo']['detail'][$key]['ptd'] = is_null($flight_schedule_back['flightInfo']['detail'][$key]['ptd'])? null : substr($flight_schedule_back['flightInfo']['detail'][$key]['ptd'],-8,5);
                $flight_schedule_back['flightInfo']['detail'][$key]['etd'] = is_null($flight_schedule_back['flightInfo']['detail'][$key]['etd'])? null : substr($flight_schedule_back['flightInfo']['detail'][$key]['etd'],-8,5);
                $flight_schedule_back['flightInfo']['detail'][$key]['atd'] = is_null($flight_schedule_back['flightInfo']['detail'][$key]['atd'])? null : substr($flight_schedule_back['flightInfo']['detail'][$key]['atd'],-8,5);
                $flight_schedule_back['flightInfo']['detail'][$key]['pta'] = is_null($flight_schedule_back['flightInfo']['detail'][$key]['pta'])? null : substr($flight_schedule_back['flightInfo']['detail'][$key]['pta'],-8,5);
                $flight_schedule_back['flightInfo']['detail'][$key]['eta'] = is_null($flight_schedule_back['flightInfo']['detail'][$key]['eta'])? null : substr($flight_schedule_back['flightInfo']['detail'][$key]['eta'],-8,5);
                $flight_schedule_back['flightInfo']['detail'][$key]['ata'] = is_null($flight_schedule_back['flightInfo']['detail'][$key]['ata'])? null : substr($flight_schedule_back['flightInfo']['detail'][$key]['ata'],-8,5);
                $flight_schedule_back['flightInfo']['detail'][$key]['type'] = (is_null($flight_schedule_back['flightInfo']['detail'][$key]['type']) || $flight_schedule_back['flightInfo']['detail'][$key]['type']=='')? 'JET' : $flight_schedule_back['flightInfo']['detail'][$key]['type'];
                $flight_schedule_back['flightInfo']['detail'][$key]['food'] = ($flight_schedule_back['flightInfo']['detail'][$key]['food']=='t')? true : false;
            }
            $flight_schedule_back['flightInfo']['dptcity_start_name'] = $first_schedule['dptcity_name'];
            $flight_schedule_back['flightInfo']['arrcity_end_name'] = $end_schedule['arrcity_name'];
            $flight_schedule_back['flightInfo']['dptairport_name'] = $first_schedule['dptairport_name'];
            $flight_schedule_back['flightInfo']['arrairport_name'] = $end_schedule['arrairport_name'];
            $flight_schedule_back['flightInfo']['dptairport_description'] = InfoCenter::getAirportDescription($first_schedule['dptairport']);
            $flight_schedule_back['flightInfo']['arrairport_description'] = InfoCenter::getAirportDescription($end_schedule['arrairport']);
            
        }
        Loader::closeDbCluster();//关闭框架的数据库连接
        DirectDbConnect::closeDirectDbConnect();//关闭所有直连
        RedisCache::closeRedisConnection();//关闭redis连接,现在用的是短连接
        //Loader::pgetModel('direct_domestic')->closeDirectDbconnect();//关闭直连数据库连接
        return $flight_schedule_back;
    }
    
    
    //获取机大屏数据的接口
    public function getAirportTrafficInfo($params){
        $traffic_info_back['ret'] = true;
        $traffic_info_back['sectionnumber'] = 0;
        $traffic_info_back['list'] = array();
        
        global $CityNameToCodeConfig;
        if(!isset($CityNameToCodeConfig[$params['city_name']]) || $CityNameToCodeConfig[$params['city_name']]['citycode']==''){
            return $traffic_info_back;
        }
        
        $airport_code = $CityNameToCodeConfig[$params['city_name']]['citycode'];
        //$airport_code = $params['airport_code']; 
        $traffic = $params['traffic'];
        $date = date("Y-m-d");
        $traffic_info = array();
        $airport_line = $this->getAirportLine($airport_code,$traffic);
        foreach($airport_line as $line){
            //获取单条航线(机场->机场)的所有航班信息
            if(!is_array($single_line_info = Loader::pgetService("demon")->getAirportDepFlightinfoCacheInfo($line[0],$line[1],$date))) continue;
            foreach($single_line_info as $flight_info){
                if($flight_info['stops']==0) $traffic_info[] = $flight_info;
            }
        }
        $transform_params_name = array(
                                    "carrier"=>"icon",
                                    "cname"=>"name",
                                    "flightno"=>"flight",
                                    "dptairport_name"=>"departure",
                                    "arrairport_name"=>"arrival",
                                    "plan_local_dep_time"=>"ptd",
                                    "actual_local_dep_time"=>"atd",
                                    "plan_local_arr_time"=>"pta",
                                    "actual_local_arr_time"=>"ata",                         
                                    "dpttower"=>"dt",
                                    "arrtower"=>"at",
                                    "status"=>"state",
                                    );
        foreach($traffic_info as $key=>$info){
            foreach($transform_params_name as $okey_name=>$nkey_name){
                $traffic_info[$key][$nkey_name] = $traffic_info[$key][$okey_name];
                unset($traffic_info[$key][$okey_name]);
            }
        }
        reset($traffic_info);
        switch($traffic){
            case 'in':
                $order_keyname = 'pta';
                break;
            case 'out':
                $order_keyname = 'ptd';
                break;
        }
        
        $traffic_info = Tools::array_sort($traffic_info,$order_keyname);
        $new_traffic_info = array();
        foreach($traffic_info as $info){
            //$info['ptd'] = substr($info['ptd'],-8);
            //$info['atd'] = substr($info['atd'],-8);
            //$info['pta'] = substr($info['pta'],-8);
            //$info['ata'] = substr($info['ata'],-8);
            if("今日无航班" == $info['state']) continue;
            $new_traffic_info[] = $info;
        }
        $traffic_info_back['sectionnumber'] = count($new_traffic_info);
        $traffic_info_back['list'] = $new_traffic_info;
        return $traffic_info_back;
    }
    
    private function getAirportLine($airport_code,$traffic){
        //从demon生成的
        if(is_null($domestic_airportline_array = Loader::pgetService("demon")->getAllAirportLineCacheInfo())){
            $domestic_airportline_array = Loader::pgetService("cron")->getDomesticAirPortline();//机场航线的全量
        }
        $line_list = array();
        switch($traffic){
            case 'in':
                foreach($domestic_airportline_array as $line){
                    if($line[1]==$airport_code){
                        $line_list[] = $line;
                    }
                }
                break;
            case 'out':
                foreach($domestic_airportline_array as $line){
                    if($line[0]==$airport_code){
                        $line_list[] = $line;
                    }
                }
                break;
        }
        return $line_list;
    }
    
    
    public function getFlightstatusByDepArrDate($params){
        $flight_info_back['ret'] = true;
        $flight_info_back['sectionnumber'] = 0;
        $flight_info_back['data'] = array();
        $schedule = $this->getDepArrSearchSchedule($params);
        $dep_arr_flight_list_status = array();
        $transfer_params = array(
                            "dpttime"=>"plan_local_dep_time",
                            "arrtime"=>"plan_local_arr_time",
                            "flighttime"=>"plan_flighttime");
        foreach($schedule as $flightno_schedule_info){
            $info = current($flightno_schedule_info);
            $air_company_info = InfoCenter::getAirCompanyInfo($info['flightno']);//获取航空公司信息
            foreach($flightno_schedule_info as $key=>$each_part){
                foreach($transfer_params as $t_okey=>$t_nkey){
                    $flightno_schedule_info[$key][$t_nkey] = $flightno_schedule_info[$key][$t_okey];
                    unset($flightno_schedule_info[$key][$t_okey]);
                }
                $flightno_schedule_info[$key]['arrairport_name'] = InfoCenter::getAirportName($flightno_schedule_info[$key]['arrairport']);//获取机场中文名
                $flightno_schedule_info[$key]['dptairport_name'] = InfoCenter::getAirportName($flightno_schedule_info[$key]['dptairport']);
                $flightno_schedule_info[$key]['arrcity_name'] = InfoCenter::getCityName($flightno_schedule_info[$key]['arrcity']);//城市中文名称
                $flightno_schedule_info[$key]['dptcity_name'] = InfoCenter::getCityName($flightno_schedule_info[$key]['dptcity']);
            }
            
            $status = $this->getSingleFlightStatusByDepArr($flightno_schedule_info,$params['date']);//获取到某航班从起飞地到目的地的整段的状态
            $each_flight_info = $this->mergeMultipleSingleFlightSchedulePart($flightno_schedule_info);//获取到合并多段之后的飞行计划

            
            foreach($status as $data_key=>$val){
                $each_flight_info[$data_key] = $val;
            }
            
            //补充航空公司信息
            foreach($air_company_info as $akey=>$aval){
                $each_flight_info[$akey] = $aval;
            }
            

            
            $dep_arr_flight_list_status[] = $each_flight_info;
        }
        $flight_info_back['sectionnumber'] = count($dep_arr_flight_list_status);
        $flight_info_back['data'] = $dep_arr_flight_list_status;
        return $flight_info_back;
    }
    
    public function mergeMultipleSingleFlightSchedulePart($flightno_schedule_info){
        reset($flightno_schedule_info);
        $first_part_schedule = current($flightno_schedule_info);
        $stops = count($flightno_schedule_info)-1;
        if($stops===0) {
            $first_part_schedule['stops'] = $stops;
            return $first_part_schedule;//判断两地之间此航班有几段飞行
        }
        $end_part_schedule = end($flightno_schedule_info);
        $merge_schedule_back['dptairport'] = $first_part_schedule['dptairport'];
        $merge_schedule_back['plan_local_dep_time'] = $first_part_schedule['plan_local_dep_time'];
        $merge_schedule_back['arrairport'] = $end_part_schedule['arrairport'];
        $merge_schedule_back['plan_local_arr_time'] = $end_part_schedule['plan_local_arr_time'];
        $merge_schedule_back['planetype'] = $first_part_schedule['planetype'];
        $merge_schedule_back['dptcity'] = $first_part_schedule['dptcity'];
        $merge_schedule_back['arrcity'] = $end_part_schedule['arrcity'];
        $merge_schedule_back['flightno'] = $first_part_schedule['flightno'];
        $merge_schedule_back['stops'] = $stops;
        $merge_schedule_back['dpttower'] = isset($first_part_schedule['dpttower'])? $first_part_schedule['dpttower'] : '';
        $merge_schedule_back['arrtower'] = isset($end_part_schedule['arrtower'])? $end_part_schedule['arrtower'] : '';
        $merge_schedule_back['dptairport_name'] = $first_part_schedule['dptairport_name'];
        $merge_schedule_back['arrairport_name'] = $end_part_schedule['arrairport_name'];
        $merge_schedule_back['dptcity_name'] = $first_part_schedule['dptcity_name'];
        $merge_schedule_back['arrcity_name'] = $end_part_schedule['arrcity_name'];
        $merge_schedule_back['plan_flighttime'] = Tools::flightTimeFormat(strtotime($merge_schedule_back['plan_local_arr_time'])-strtotime($merge_schedule_back['plan_local_dep_time']));
        return $merge_schedule_back;
    }
    
    public function getSingleFlightStatusByDepArr($single_flight_schedule,$date){
        $status_list = array();
        foreach($single_flight_schedule as $schedule){
            $status_part_list_info[] = $this->getFlightPartStatus($schedule['flightno'],$schedule['dptairport'],$schedule['arrairport'],$date,$schedule['plan_local_dep_time'],$schedule);
            //print_r($status_part_list_info);
        }
        $merge_result = $this->mergeMultipleSingleFlightStatusPart($status_part_list_info);
        return $merge_result;//合并一航班的多段飞行状态
    }
    
    //合并一航班的多段飞行状态
    public function mergeMultipleSingleFlightStatusPart($status_part_list_info){
        $first_part_status = current($status_part_list_info);
        $end_part_status = end($status_part_list_info);
        reset($status_part_list_info);
        if(count($status_part_list_info)===1) return $first_part_status;//两地之间此航班只有一段飞行,直接返回第一段状态值
        $arr_flag = true;//是否是到达状态的标志,每一段状态都是到达,则整段航程才是到达
        //$status_precedence_list = array("今日无航班","取消","备降","起飞");
        $status_precedence_list = array("今日无航班","取消","备降");
        foreach($status_precedence_list as $status_name){
            foreach($status_part_list_info as $part_status){
                $arr_flag = ("到达"==$part_status['status'])? $arr_flag : false;
                if($status_name == $part_status['status']){
                    switch($part_status['status']){
                        case "今日无航班" :
                            $status_back['expect_local_dep_time'] = null;
                            $status_back['expect_local_arr_time'] = null;
                            $status_back['actual_local_dep_time'] = null;
                            $status_back['actual_local_arr_time'] = null;
                            $status_back['status'] = $status_name;
                            $status_back['actual_flighttime'] = null;
                            break;
                        case "取消" :
                            $status_back['expect_local_dep_time'] = null;
                            $status_back['expect_local_arr_time'] = null;
                            $status_back['actual_local_dep_time'] = null;
                            $status_back['actual_local_arr_time'] = null;
                            $status_back['status'] = $status_name;
                            $status_back['actual_flighttime'] = null;
                            break;
                        case "备降" :
                            $status_back['expect_local_dep_time'] = null;
                            $status_back['expect_local_arr_time'] = null;
                            $status_back['actual_local_dep_time'] = null;
                            $status_back['actual_local_arr_time'] = null;
                            $status_back['status'] = $status_name;
                            $status_back['actual_flighttime'] = null;
                            break;
                        /***************
                        case "起飞" :
                            $status_back['expect_local_dep_time'] = $first_part_status['expect_local_dep_time'];
                            $status_back['expect_local_arr_time'] = $end_part_status['expect_local_arr_time'];
                            $status_back['actual_local_dep_time'] = $first_part_status['actual_local_dep_time'];
                            $status_back['actual_local_arr_time'] = null;
                            $status_back['status'] = $status_name;
                            $status_back['actual_flighttime'] = null;
                            break;
                        *****************/
                    }
                    return $status_back;
                }

                
            }
        }
        
        //到达状态
        if($arr_flag){
            $status_back['expect_local_dep_time'] = $first_part_status['expect_local_dep_time'];
            $status_back['expect_local_arr_time'] = $end_part_status['expect_local_arr_time'];
            $status_back['actual_local_dep_time'] = $first_part_status['actual_local_dep_time'];
            $status_back['actual_local_arr_time'] = $end_part_status['actual_local_arr_time'];;
            $status_back['status'] = "到达";
            $status_back['actual_flighttime'] = Tools::flightTimeFormat(strtotime($status_back['actual_local_arr_time'])-strtotime($status_back['actual_local_dep_time']));
            return $status_back;
        }
        
        //第一段飞行是延误或者计划,则整段都是此状态
        if($first_part_status['status']=="延误" || $first_part_status['status']=="计划"){
            $status_back['expect_local_dep_time'] = $first_part_status['expect_local_dep_time'];
            $status_back['expect_local_arr_time'] = $end_part_status['expect_local_arr_time'];
            $status_back['actual_local_dep_time'] = null;
            $status_back['actual_local_arr_time'] = null;;
            $status_back['status'] = $first_part_status['status'];
            $status_back['actual_flighttime'] = null;
            return $status_back;
        }
        
        foreach($status_part_list_info as $part_status){
            if(("延误" == $part_status['status']) || ("计划" == $part_status['status']) || ("起飞" == $part_status['status'])){
                $status_back['expect_local_dep_time'] = $first_part_status['expect_local_dep_time'];
                $status_back['expect_local_arr_time'] = $end_part_status['expect_local_arr_time'];
                $status_back['actual_local_dep_time'] = null;
                $status_back['actual_local_arr_time'] = null;;
                $status_back['status'] = "起飞";
                $status_back['actual_flighttime'] = null;
                return $status_back;
            }
        }

    }

    private function getDepArrSearchSchedule($params){
        $schedule_keyname = $this->getDepArrSearchScheduleCacheKey($params['dptcity'],$params['arrcity'],$params['date']);
        $cache_schedule_data = RedisCache::getCacheInfo($schedule_keyname);
        if(!is_null($cache_schedule_data)){
            //缓存有数据直接从缓存取
            $schedule_data = @json_decode($cache_schedule_data,true);
            return is_array($schedule_data)?  $schedule_data : array();
        }
        //获取avdb的两地查询结果
        $avdb_schedule = Loader::pgetModel('direct_domestic')->getflightpathbycity($params);
        $output_params_array = array("arrcity","dptcity","arrairport","arrtime","dptairport","dpttime","planetype","flighttime","flightno","arrtower","dpttower","disappear_times");
        foreach($avdb_schedule['data'] as $fk=>$v){
            foreach($v as $key=>$vaule){
                $avdb_schedule['data'][$fk][$key]["arrcity"] = $avdb_schedule['data'][$fk][$key]["arr"];
                $avdb_schedule['data'][$fk][$key]["dptcity"] = $avdb_schedule['data'][$fk][$key]["dpt"];
                $avdb_schedule['data'][$fk][$key]["flightno"] = $avdb_schedule['data'][$fk][$key]["unifiedcode"];
                $avdb_schedule['data'][$fk][$key]["flighttime"] = ($avdb_schedule['data'][$fk][$key]["flighttime"]=="")? direct_domestic_model::flightTimeFormat(strtotime($avdb_schedule['data'][$fk][$key]["arrtime"])-strtotime($avdb_schedule['data'][$fk][$key]["dpttime"])) : $avdb_schedule['data'][$fk][$key]["flighttime"];
                foreach($vaule as $k=>$val){
                    if(!in_array($k,$output_params_array)){
                        unset($avdb_schedule['data'][$fk][$key][$k]);
                    }
                }
            }
        }
        $schedule_source_list[] = $avdb_schedule;
        
        
        //获取sk的两地查询结果
        $sk_schedule = Loader::pgetModel('skdatatmp')->getflightpathbycity($params);
        $output_params_array = array("arrcity","dptcity","arrairport","arrtime","dptairport","dpttime","planetype","flighttime","flightno","arrtower","dpttower","disappear_times");
        foreach($sk_schedule['data'] as $fk=>$v){
            foreach($v as $key=>$vaule){
                $sk_schedule['data'][$fk][$key]["arrcity"] = $sk_schedule['data'][$fk][$key]["arrcity"];
                $sk_schedule['data'][$fk][$key]["dptcity"] = $sk_schedule['data'][$fk][$key]["depcity"];
                $sk_schedule['data'][$fk][$key]["arrtower"] = '';
                $sk_schedule['data'][$fk][$key]["dpttower"] = '';
                $sk_schedule['data'][$fk][$key]["arrairport"] = $sk_schedule['data'][$fk][$key]["arr"];
                $sk_schedule['data'][$fk][$key]["arrtime"] = $sk_schedule['data'][$fk][$key]["plan_local_arr_time"];
                $sk_schedule['data'][$fk][$key]["dptairport"] = $sk_schedule['data'][$fk][$key]["dep"];
                $sk_schedule['data'][$fk][$key]["dpttime"] = $sk_schedule['data'][$fk][$key]["plan_local_dep_time"];
                $sk_schedule['data'][$fk][$key]["flighttime"] = skdatatmp_model::flightTimeFormat(strtotime($sk_schedule['data'][$fk][$key]["plan_bj_arr_time"])-strtotime($sk_schedule['data'][$fk][$key]["plan_bj_dep_time"]));
                $sk_schedule['data'][$fk][$key]["disappear_times"] = $this->_limit_flightschedule_disappeartime;
                foreach($vaule as $k=>$val){
                    if(!in_array($k,$output_params_array)){
                        unset($sk_schedule['data'][$fk][$key][$k]);
                    }
                }
            }
        }
        $schedule_source_list[] = $sk_schedule;
        //得两地查询的飞行计划数据合并结果
        $schedule = $this->MergeDepArrSearchSchedule($schedule_source_list);
        global $SCHEDULE_CACHE_TIME_SECONDS;
        RedisCache::setCacheInfo($schedule_keyname,json_encode($schedule),$SCHEDULE_CACHE_TIME_SECONDS);
        return $schedule;
    }
    
    //合并多个两地查询条件获取到的航班飞行数据
    private function MergeDepArrSearchSchedule($schedule_source_list){
        $merge_schedule = array();
        foreach($schedule_source_list as $schedule){
            if(!isset($schedule['sectionnumber']) || $schedule['sectionnumber']==0) continue;//没获取到两地航班的飞行数据
            foreach($schedule['data'] as $flight){
                $first_part_schedule = current($flight);
                if(!isset($merge_schedule[$first_part_schedule['flightno']])){
                    $merge_schedule[$first_part_schedule['flightno']] = $flight;
                }
            }
        }
        return $merge_schedule;
    }
    
    //通过航班号,日期获取航班动态信息
    public function getFlightstatusByFnoDate($params){
        $start_time = microtime();
        Cacti::setStatusSearchByFlnoCountPerMin();//记录访问一次
        $schedule_info = $this->getDomesicSchedule($params);//获取整理过的飞行计划
        /********* 从这里开始将获取到的飞行计划查找各warpper对应的航班状态信息 **********/
        $flight_schedule_back['ret'] = false;
        $flight_schedule_back['sectionnumber'] = 0;
        $flight_schedule_back['data'] = "无此航班数据";
        if($schedule_info){
            $air_company_info = InfoCenter::getAirCompanyInfo($params['flightno']);//获取航空公司信息
            $flight_schedule_back['ret'] = true;
            $transfer_params = array(
                                "dpttime"=>"plan_local_dep_time",
                                "arrtime"=>"plan_local_arr_time",
                                "flighttime"=>"plan_flighttime");
            foreach($schedule_info as $key=>$flight_path){
                $schedule_info[$key]['arrairport_name'] = InfoCenter::getAirportName($flight_path['arrairport']);//获取机场中文名
                $schedule_info[$key]['dptairport_name'] = InfoCenter::getAirportName($flight_path['dptairport']);
                $schedule_info[$key]['arrcity_name'] = InfoCenter::getCityName($flight_path['arrcity']);//城市中文名称
                $schedule_info[$key]['dptcity_name'] = InfoCenter::getCityName($flight_path['dptcity']);
                $ontime_percent = InfoCenter::getFlightOntimePercent($params['flightno'],$flight_path['dptcity'],$flight_path['arrcity']);
                $schedule_info[$key]['distance'] = InfoCenter::getDepArrairportDistance($flight_path['dptairport'],$flight_path['arrairport']);//机场之间的距离
                $schedule_info[$key]['ontime_percent'] = is_null($ontime_percent)? null : $ontime_percent.'%';//机场之间的距离
                if(!isset($flight_path['arrtower'])) $schedule_info[$key]['arrtower'] = '';//航站楼信息
                if(!isset($flight_path['dpttower'])) $schedule_info[$key]['dpttower'] = '';
                if(!isset($flight_path['meal'])) $schedule_info[$key]['meal'] = null;
                //将模块的结果字段转换成接口字段名称
                foreach($transfer_params as $t_okey=>$t_nkey){
                    $schedule_info[$key][$t_nkey] = $schedule_info[$key][$t_okey];
                    unset($schedule_info[$key][$t_okey]);
                }
                
                //获取每段的飞行状态
                $status = $this->getFlightPartStatus($params['flightno'],$flight_path['dptairport'],$flight_path['arrairport'],$params['date'],$flight_path['dpttime'],$schedule_info[$key]);
                foreach($status as $data_key=>$val){
                    $schedule_info[$key][$data_key] = $val;
                }
                
                //补充航空公司信息
                foreach($air_company_info as $akey=>$aval){
                    $schedule_info[$key][$akey] = $aval;
                }
            }
            $flight_schedule_back['sectionnumber'] = count($schedule_info);
            $flight_schedule_back['data'] = $schedule_info;
        }
        $end_time = microtime();
        Cacti::setStatusSearchByFlnoRuntime($start_time,$end_time);
        Loader::closeDbCluster();//关闭框架的数据库连接
        DirectDbConnect::closeDirectDbConnect();//关闭所有直连
        RedisCache::closeRedisConnection();//关闭redis连接,现在用的是短连接
        //Loader::pgetModel('direct_domestic')->closeDirectDbconnect();//关闭直连数据库连接
        return $flight_schedule_back;
    }
    
    
    public function getActcode($flightno,$dep,$arr,$date){
        $cache_keyname = $this->getActInfoCacheKey($flightno,$dep,$arr,$date);
        $cache_shareinfo_data = RedisCache::getCacheInfo($cache_keyname);
        if(!is_null($cache_shareinfo_data)){
            return $cache_shareinfo_data;
        }
        $params['flightno'] = $flightno;
        $params['dep'] = $dep;
        $params['arr'] = $arr;
        $params['date'] = $date;
        $share_info = Loader::pgetModel('direct_domestic')->getFcodeShareinfoByFno($params);
        $actcode = is_null($share_info['detail'])? $flightno : $share_info['detail']['actcode'];
        global $FLIGHTNO_ACTINFO_CACHE_TIME_SECONDS;
        RedisCache::setCacheInfo($cache_keyname,$actcode,$FLIGHTNO_ACTINFO_CACHE_TIME_SECONDS);
        return $actcode;
    }
    
    //通过航班号,起飞,降落机场,日期获取某段实际飞行轨迹的航班状态
    private function getFlightPartStatus($flightno,$dptairport,$arrairport,$date,$plan_local_dep_time,$schedule){
        $cache_keyname = $this->getSingleFlightStatusCacheKey($flightno,$dptairport,$arrairport,$date);
        $cache_data = RedisCache::getCacheInfo($cache_keyname);
        if(!is_null($cache_data)){
            //缓存有数据直接从缓存取
            return @json_decode($cache_data,true);
        }

        $params['flightno'] = $flightno;
        $params['dep'] = $dptairport;
        $params['arr'] = $arrairport;
        $params['date'] = $date;
        $status_params = array(
                        'expect_local_dep_time',
                        'expect_local_arr_time',
                        'actual_local_dep_time',
                        'actual_local_arr_time',
                        'alternate_airport',
                        'status',
                        );
        $final_status_info = array();
        
        //判断是否是国内的航班(能从合作warpper获取到部分国际的航班飞行数据,所以存在国际类型的数据,但比较少)
        $if_domestic = (!InfoCenter::ifInterAirport($dptairport) && !InfoCenter::ifInterAirport($arrairport));
        /*********** 从最终状态表获取 ************/
        $final_status = Loader::pgetModel('finalflightstatus')->getFinalFlightinfoByNoPtoP($params);
        if(($final_status['ret']==true) && is_array($final_status['detail'])){
            foreach($status_params as $keyname){
                $final_status_info[$keyname] = $final_status['detail'][$keyname];
            }
            //降落状态则计算
            $final_status_info['actual_flighttime'] = ($final_status['detail']['status']==finalflightstatus_dao::ARR_STATUS)? Tools::flightTimeFormat(strtotime($final_status_info['actual_local_arr_time'])-strtotime($final_status_info['actual_local_dep_time'])) : null;
            $final_status_info['status'] = finalflightstatus_dao::$status_val_to_chinese_map[$final_status_info['status']];
            $cache_seconds = $this->getStatusCacheTime($schedule,$final_status_info);//获取需要设置的数据过期时间
            RedisCache::setCacheInfo($cache_keyname,json_encode($final_status_info),$cache_seconds);
            return $final_status_info;
        }
        
        //包含未来日期的飞行计划则返回
        if(substr($plan_local_dep_time,0,10)>date("Y-m-d")){
            $final_status_info['expect_local_dep_time'] = null;
            $final_status_info['expect_local_arr_time'] = null;
            $final_status_info['actual_local_dep_time'] = null;
            $final_status_info['actual_local_arr_time'] = null;
            $final_status_info['alternate_airport'] = null;
            $final_status_info['actual_flighttime'] = null;
            $final_status_info['status'] = finalflightstatus_dao::$status_val_to_chinese_map[finalflightstatus_dao::SCHEDULE_STATUS];
            return $final_status_info;
        }
        
        //获取主飞航班号
        $act_code = $this->getActcode($flightno,$dptairport,$arrairport,$date);
        //$params['flightno'] = $act_code;
        
        /*********** 从warpper获取航班飞行状态 *************/
        $flightstatus_warpper_list = $this->domestic_flightstatus_warpper_list;
        $warpper_status_list = array();//获取所有提供航班状态的warpper返回数据
        foreach($flightstatus_warpper_list as $warppername){
            $warpper_status_list[$warppername] = Loader::pgetModel($warppername)->getFlightStatusByNoAirport($params);
            $warning_msg = array();
            //如果抓取的航班状态预计起飞时间或者实际起飞时间早于计划起飞之前半小时,并且是国内航班,则报警数据异常
            if(isset($warpper_status_list[$warppername]['actual_local_dep_time'])
                && !is_null($warpper_status_list[$warppername]['actual_local_dep_time'])
                && ((strtotime($schedule['plan_local_dep_time'])-1800)>strtotime($warpper_status_list[$warppername]['actual_local_dep_time']))
                && $if_domestic)
            {
                $warning_msg['schedule'] = $schedule;
                $warning_msg['warpper_status'] = $warpper_status_list[$warppername];
                $warning_msg['source'] = $warppername;
                $warning_msg['actcode'] = $act_code;
                Cacti::setAbnormalWarpperBack($warning_msg);                
                unset($warpper_status_list[$warppername]);//不删除抓取到的数据,使用运维工具清理脏数据(清除终态表以及缓存)
            }
        }
        reset($warpper_status_list);        
        if($warpper_status_list){
            $status_info = $this->mergeWarpperStatus($warpper_status_list,$params,$schedule['disappear_times']);//获取合并之后的航班状态
        }else{
            //如果没有获取到正确的数据源则返回计划状态
            $status_info['expect_local_dep_time'] = null;
            $status_info['expect_local_arr_time'] = null;
            $status_info['actual_local_dep_time'] = null;
            $status_info['actual_local_arr_time'] = null;
            $status_info['alternate_airport'] = null;
            $status_info['actual_flighttime'] = null;
            $status_info['status'] = finalflightstatus_dao::$status_val_to_chinese_map[finalflightstatus_dao::SCHEDULE_STATUS];
            return $status_info;
        }
        
        //需要写入db的最终状态列表
        $final_status_list = array(
                                finalflightstatus_dao::ARR_STATUS,//到达
                                finalflightstatus_dao::CNL_STATUS,//取消
                                finalflightstatus_dao::ALTERNATE_STATUS,//备降
                                finalflightstatus_dao::NOSCHEDULE_STATUS//今日无航班
                                );

        
        //是共享航班号
        if($flightno != $act_code){
            $act_cache_keyname = $this->getSingleFlightStatusCacheKey($act_code,$dptairport,$arrairport,$date);
            $act_cache_data = RedisCache::getCacheInfo($act_cache_keyname);
            //主飞航班号数据有缓存
            if(!is_null($act_cache_data)){
                $act_cache_data = json_decode($act_cache_data,true);
                //主飞航班号的状态是最终状态,共享航班号不是最终状态,更新共享航班号的状态与主飞航班相同
                if(in_array(finalflightstatus_dao::$status_map[$act_cache_data['status']],$final_status_list) 
                && !in_array(finalflightstatus_dao::$status_map[$status_info['status']],$final_status_list)){
                    $status_info = $act_cache_data;
                }
            }
        }
        
        //如果获取的状态为计划，并且当前时间已经超过计划起飞时间,则更改状态为延误
        if((finalflightstatus_dao::$status_map[$status_info['status']]===finalflightstatus_dao::SCHEDULE_STATUS) &&(date("Y-m-d H:i").':00'>$schedule['plan_local_dep_time'])){
            $status_info['status'] = finalflightstatus_dao::$status_val_to_chinese_map[finalflightstatus_dao::DELAY_STATUS];//延误
        }
        
        @Loader::pgetModel("readyflightstatus")->insertReadyFlightinfo($flightno,$schedule,$status_info);
        
        //设置缓存
        $cache_seconds = $this->getStatusCacheTime($schedule,$status_info);//获取需要设置的数据过期时间
        RedisCache::setCacheInfo($cache_keyname,json_encode($status_info),$cache_seconds);
        
        if(in_array(finalflightstatus_dao::$status_map[$status_info['status']],$final_status_list)){
            //是最终状态并且是共享航班的情况下,更新主飞航班的数据
            if($flightno != $act_code){
                @Loader::pgetModel("finalflightstatus")->insertFinalFlightinfo($act_code,$schedule,$status_info);
            }
            @Loader::pgetModel("finalflightstatus")->insertFinalFlightinfo($flightno,$schedule,$status_info);
        }
        return $status_info;
    }
    
    //对warpper获取的航班状态数据做合并
    private function mergeWarpperStatus($warpper_status_list,$params,$disappear_times = 50){
        //按状态做数据合并
        $domestic_data_warpper_status_precedence_button =  (strtoupper(Loader::getSelfConfigParams("DMOESTIC-DATA-WARPPER-STATUS-PRECEDENCE-BUTTON"))=="ON")? true : false;
        if($domestic_data_warpper_status_precedence_button){
            $domestic_data_warpper_status_precedence_map = json_decode(Loader::getSelfConfigParams("DMOESTIC-DATA-WARPPER-STATUS-PRECEDENCE-MAP"),TRUE);
            $status_list = array();
            //所有warpper的状态集合,同状态做了去重
            foreach($warpper_status_list as $info){
                if(!in_array($info['status'],$status_list)){
                    $status_list[] = $info['status'];
                }
            }
            
            foreach($warpper_status_list as $warpper_name=>$status_info){
                foreach($domestic_data_warpper_status_precedence_map as $map_array){
                    if(isset($map_array['s'.$status_info['status']]) && in_array($map_array['s'.$status_info['status']],$status_list)){
                        unset($warpper_status_list[$warpper_name]);
                        break;
                    }
                }
            }
            reset($warpper_status_list);
        }
        //按数据源优先级做合并
        $warpper_status = null;
        $domestic_data_warpper_source_precedence_list = json_decode(Loader::getSelfConfigParams("DMOESTIC-STATUS-DATA-WARPPER-SOURCE-PRECEDENCE-LIST"),TRUE);
        foreach($domestic_data_warpper_source_precedence_list as $source_name){
            if(isset($warpper_status_list[$source_name])){
                $warpper_status['warpper_source'] = $source_name;
                foreach($warpper_status_list[$source_name] as $key=>$val){
                    $warpper_status[$key] = $val;
                }
                break;
            }
        }
        
        //如果从warpper获取到的状态是今日无航班,则再判断消失次数(小于此次数则确认为航班状态为计划,=此次数则为今日无航班,>此次数则为航班取消)
        if($warpper_status['status']==finalflightstatus_dao::NOSCHEDULE_STATUS){
            if(intval($disappear_times)<$this->_limit_flightschedule_disappeartime){
                $warpper_status['status']=finalflightstatus_dao::SCHEDULE_STATUS;
            }elseif(intval($disappear_times)>$this->_limit_flightschedule_disappeartime){
                $warpper_status['status']=finalflightstatus_dao::CNL_STATUS;
            }
        }
        
        //是否加入华北数据合并的逻辑
        if('ON'==strtoupper(Loader::getSelfConfigParams("DMOESTIC-STATUS-MERGE-HBWARPPER-BUTTON"))){
            //最后取华北取数据做合并逻辑
            $hb_status = Loader::pgetModel('hbwarpper')->getHbWarpperDomesticFlightstatus($params);
            if($hb_status['status']==$warpper_status['status']){
                foreach($hb_status as $key=>$val){
                    $warpper_status[$key] = $val;
                }
                $warpper_status['warpper_source'] = $warpper_status['warpper_source'].'+hbwarpper';
            }else{
                foreach($domestic_data_warpper_status_precedence_map as $map_array){
                    //华北的状态优先级别高于合并之后的状态,并且用于合并的数据源是正常状态(warpper抛出异常之后生成的状态中,expect_local_arr_time值为null)
                    if(isset($map_array['s'.$warpper_status['status']]) && ($map_array['s'.$warpper_status['status']]==$hb_status['status']) && !is_null($warpper_status['expect_local_arr_time'])){
                        foreach($hb_status as $key=>$val){
                            $warpper_status[$key] = $val;
                        }
                        if($hb_status['status']==finalflightstatus_dao::ARR_STATUS){
                            $warpper_status['actual_flighttime'] = is_null($warpper_status['actual_local_dep_time'])? null : Tools::flightTimeFormat(strtotime($hb_status['actual_local_arr_time'])-strtotime($warpper_status['actual_local_dep_time']));
                        }
                        $warpper_status['warpper_source'] = $warpper_status['warpper_source'].'->hbwarpper';
                        break;
                    }
                }
                
            }

        }
        
        $status_val_to_chinese_map = finalflightstatus_dao::$status_val_to_chinese_map;//状态值到状态中文名的映射
        $warpper_status['status'] = $status_val_to_chinese_map[$warpper_status['status']];
        return $warpper_status;
    }
    
    //获取国内航班飞行计划
    private function getDomesicSchedule($params){
        $schedule_keyname = $this->getSingleFlightScheduleCacheKey($params['flightno'],$params['date']);
        $cache_schedule_data = RedisCache::getCacheInfo($schedule_keyname);
        if(!is_null($cache_schedule_data)){
            //缓存有数据直接从缓存取
            $schedule_data = @json_decode($cache_schedule_data,true);
            return is_array($schedule_data)?  $schedule_data : array();
        }
        //是否开启国内航班计划数据优先级开关
        $if_spring = (ltrim($params['flightno'],'9C')==$params['flightno'])? false : true;//是否是春秋航空
        //国内航班动态查询所使用的数据源,格式: model=>function
        $domestic_source_list = array('direct_domestic'=>'getflightpathbyno','skdatatmp'=>'getflightpathbyno');
        if($if_spring) $domestic_source_list['sk9ctmp'] = 'getflightpathbyno';//春秋的航班增加sk9ctmp做为飞行计划数据源
        //如果是查当天的航班动态,则使用jz数据源
        if(date("Y-m-d")==$params['date']){
            $domestic_source_list['jzwarpper'] = 'getflightpathbyno';
        }
        //从多个数据源获取飞行计划
        foreach($domestic_source_list as $model=>$function){
            $schedule_list[$model] = Loader::pgetModel($model)->$function($params);
        }
        
        $schedule = $schedule_list;
        $domestic_schedule_precedence_button =  (strtoupper(Loader::getSelfConfigParams("DMOESTIC-DATA-SINGLE-SELECT-SCHEDULE-BUTTON"))=="ON")? true : false;
        if($domestic_schedule_precedence_button){
            //开启只从一个数据源获取飞行计划数据
            $domestic_schedule_precedence_list = json_decode(Loader::getSelfConfigParams("DMOESTIC-DATA-SINGLE-SELECT-SCHEDULE-PRECEDENCE-LIST"),true);
            foreach($domestic_schedule_precedence_list as $source){
                if(isset($schedule_list[$source])){
                    $schedule = array($source=>$schedule_list[$source]);break;//只取优先级最高的数据源
                }
            }
        }
        $merge_back = $this->mergeDomesticSchedule($schedule);
        global $SCHEDULE_CACHE_TIME_SECONDS;
        $need_cache_data = array();
        //没有飞行数据则空数组写入缓存
        if($merge_back){
            $need_cache_data = $merge_back;
        }
        RedisCache::setCacheInfo($schedule_keyname,json_encode($need_cache_data),$SCHEDULE_CACHE_TIME_SECONDS);
        return $merge_back;
    }
    
    
    //合并多个航班飞行计划
    private function mergeDomesticSchedule($schedule){
        $format_schedule = array();//格式化的航班飞行计划数据
        $log_source_msg = '';
        $source_info_msg = '';
        foreach($schedule as $source=>$schedule_info){
            $log_source_msg .= $source.'|';
            if($schedule_info['sectionnumber']>0){
                $format_schedule[$source] = $this->formatScheduleData($source,$schedule_info);
                $single_source_name = $source;
                $source_info_msg .= $source.':'.json_encode($format_schedule[$source]).'|';
            }else{
                $source_info_msg .= $source.':'.json_encode($schedule_info).'|';
            }
        }
        //var_dump(current($format_schedule));
        
        if(1===count($format_schedule)) return $format_schedule[$single_source_name]['data'];//只有一个数据源
        /********* 组合多个数据源的航班飞行轨迹逻辑 **********/
        $merge_precedence = json_decode(Loader::getSelfConfigParams("DMOESTIC-DATA-SCHEDULE-MERGE-PRECEDENCE-LIST"),true);//merge优先级
        $schedule_precedence_list = array();//按优先级排列的航班计划数据
        $i = 0;
        foreach($merge_precedence as $sourename=>$level){
            if(isset($format_schedule[$sourename]) && ($format_schedule[$sourename]['sectionnumber']>0)){
                $schedule_precedence_list[$i] = $format_schedule[$sourename];
                $i++;
            }
        }
        
        $main_schedule_info = false;
        if($main_schedule_back = current($schedule_precedence_list)){
            $main_schedule_info = $main_schedule_back['data'];
            foreach($schedule_precedence_list as $point_flag=>$format_info){
                //每个数据源
                $source_schedule_info = $format_info['data'];
                foreach($source_schedule_info as $single_flight_schedule_info){
                    //某个数据源下的每条飞行计划
                    $jump_flag = false;
                    foreach($main_schedule_info as $k=>$main_single_info){
                        //判断每条飞行计划的起飞和降落机场是否在之前的主数据中已经存在
                        if(($single_flight_schedule_info['dptairport']==$main_single_info['dptairport']) || ($single_flight_schedule_info['arrairport']==$main_single_info['arrairport'])){
                            if($main_single_info['planetype']=='' && $single_flight_schedule_info['planetype']!='') $main_schedule_info[$k]['planetype'] = $single_flight_schedule_info['planetype'];
                            $jump_flag = true;
                        }
                    }
                    //不存在则将此飞行计划添加到主飞行数据中
                    if(!$jump_flag) $main_schedule_info[] = $single_flight_schedule_info;
                }
            }
            $main_schedule_info = $this->getPointToPoint($main_schedule_info);
        }
        
        $logmsg = 'fetch:{'.$log_source_msg.'}-'.$source_info_msg.'-mergResult:'.json_encode($main_schedule_info);
        BaseLog::setFetchScheduleFromSourceLog($logmsg);
        return $main_schedule_info;
    }
    
    public function formatScheduleData($source,$schedule_info){
        //格式化之后需要输出的字段
        $output_params_array = array("arrcity","dptcity","arrairport","arrtime","dptairport","dpttime","planetype","meal","flighttime","dpttower","arrtower","disappear_times");
        if($schedule_info['sectionnumber']==0){
            //无数据则直接返回
            return $schedule_info;
        }
        foreach($schedule_info['data'] as $key=>$vaule){
            switch($source){
                case 'sk9ctmp':
                    /************ sk9ctmp:spring **************/
                    $schedule_info['data'][$key]["arrairport"] = $schedule_info['data'][$key]["arr"];
                    $schedule_info['data'][$key]["arrtime"] = $schedule_info['data'][$key]["plan_local_arr_time"];
                    $schedule_info['data'][$key]["dptairport"] = $schedule_info['data'][$key]["dep"];
                    $schedule_info['data'][$key]["dpttime"] = $schedule_info['data'][$key]["plan_local_dep_time"];
                    $schedule_info['data'][$key]["dptcity"] = $schedule_info['data'][$key]["depcity"];
                    $schedule_info['data'][$key]["flighttime"] = Tools::flightTimeFormat(strtotime($schedule_info['data'][$key]["plan_bj_arr_time"])-strtotime($schedule_info['data'][$key]["plan_bj_dep_time"]));
                    $schedule_info['data'][$key]["disappear_times"] = $this->_limit_flightschedule_disappeartime;
                    break;
                case 'direct_domestic':
                    /************** direct_domestic:avdb  *****************/
                    $schedule_info['data'][$key]["arrcity"] = $schedule_info['data'][$key]["arr"];
                    $schedule_info['data'][$key]["dptcity"] = $schedule_info['data'][$key]["dpt"];
                    $schedule_info['data'][$key]["flighttime"] = ($schedule_info['data'][$key]["flighttime"]=="")? Tools::flightTimeFormat(strtotime($schedule_info['data'][$key]["arrtime"])-strtotime($schedule_info['data'][$key]["dpttime"])) : $schedule_info['data'][$key]["flighttime"];
                    $schedule_info['data'][$key]["disappear_times"] = in_array($schedule_info['data'][$key]["flightstatus"],array(direct_domestic_dao::USERFUL_YESTERDAY,direct_domestic_dao::USERFUL_YESTERDAY))?  0 : $schedule_info['data'][$key]["disappear_times"];
                    break;
                case 'skdatatmp':
                    /************** skdatatmp:sk *****************/
                    $schedule_info['data'][$key]["arrcity"] = $schedule_info['data'][$key]["arrcity"];
                    $schedule_info['data'][$key]["dptcity"] = $schedule_info['data'][$key]["depcity"];
                    $schedule_info['data'][$key]["arrairport"] = $schedule_info['data'][$key]["arr"];
                    $schedule_info['data'][$key]["arrtime"] = $schedule_info['data'][$key]["plan_local_arr_time"];
                    $schedule_info['data'][$key]["dptairport"] = $schedule_info['data'][$key]["dep"];
                    $schedule_info['data'][$key]["dpttime"] = $schedule_info['data'][$key]["plan_local_dep_time"];
                    $schedule_info['data'][$key]["flighttime"] = Tools::flightTimeFormat(strtotime($schedule_info['data'][$key]["plan_bj_arr_time"])-strtotime($schedule_info['data'][$key]["plan_bj_dep_time"]));
                    $schedule_info['data'][$key]["disappear_times"] = $this->_limit_flightschedule_disappeartime;
                    break;
                case 'jzwarpper':
                    $schedule_info['data'][$key]["disappear_times"] = $this->_limit_flightschedule_disappeartime;
                    //航班飞行计划warpper已经做了数据的格式化,无需要再处理
                    break;
            }

            foreach($vaule as $k=>$val){
                if(!in_array($k,$output_params_array)){
                    unset($schedule_info['data'][$key][$k]);
                }
            }
        }
        return $schedule_info;
    }
    
    //获取航班实际的飞行轨迹(核心算法)
    private function getPointToPoint($flight_info){
        $flag = false;
        $timestamp_array = array();
        foreach($flight_info as $flight){
            $timestamp_sum = strtotime($flight['dpttime'])+strtotime($flight['arrtime']);
            $timestamp_array[$timestamp_sum] = $flight;
        }
        unset($flight_info);
        ksort($timestamp_array);
        reset($timestamp_array);
        $min_data = current($timestamp_array);
        
        if($min_data){
            $min_data['arrairport'] = $min_data['dptairport'];
            $min_data['arrtime'] = $min_data['dpttime'];
        }
        $flightinfo_list_bysort = array();//此趟航班的实际飞行轨迹(机场->机场)
        while($min_data){
            $tmp = array();
            $next_dep = $min_data['arrairport'];//获取此趟航班下一次的起飞机场
            $pre_arr_timestamp = strtotime($min_data['arrtime']);
            reset($timestamp_array);
            foreach($timestamp_array as $key=>$data){
                if(($data['dptairport']==$next_dep) && (substr($min_data['dpttime'],0,10)==substr($data['dpttime'],0,10))){
                    $tmp[$key] = $data;
                }
            }
            ksort($tmp);
            reset($tmp);
            $min_data = current($tmp);
            if($min_data){
                foreach($tmp as $key=>$val){
                    unset($timestamp_array[$key]);
                }
                $flightinfo_list_bysort[] = $min_data;
            }else{
                ksort($timestamp_array);
                reset($timestamp_array);
                //var_dump($ct);
                if($ct = current($timestamp_array)){
                    //if((strtotime($ct['dpttime'])-$pre_arr_timestamp)>$this->_flight_max_diff){
                        $min_data = $ct;
                        $flightinfo_list_bysort[] = $min_data;
                        foreach($timestamp_array as $key=>$line){
                            if($line['dptairport']==$min_data['dptairport'] && (substr($min_data['dpttime'],0,10)==substr($line['dpttime'],0,10))) unset($timestamp_array[$key]);
                        }
                    //}
                }
            }
        }
        return $flightinfo_list_bysort;
    }
    
    //通过航班状态获取缓存时间
    private function getStatusCacheTime($schedule,$statusinfo){
        global $STATUS_CACHE_CONFIG;
        $missing_status_cache_time_seconds = 10;
        
        //状态在配置中不存在则默认缓存时间
        if(!isset($STATUS_CACHE_CONFIG[$statusinfo['status']])){
            return $missing_status_cache_time_seconds;
        }
        
        if(isset($STATUS_CACHE_CONFIG[$statusinfo['status']]) && !is_array($STATUS_CACHE_CONFIG[$statusinfo['status']])){
            return $STATUS_CACHE_CONFIG[$statusinfo['status']];
        }
        
        switch($statusinfo['status']){
            case "计划":
                $flag_time = (isset($statusinfo['expect_local_dep_time']) && !is_null($statusinfo['expect_local_dep_time']))? $statusinfo['"expect_local_dep_time'] : $schedule['plan_local_dep_time'];
                break;
            case "起飞":
                $flag_time = (isset($statusinfo['expect_local_arr_time']) && !is_null($statusinfo['expect_local_arr_time']))? $statusinfo['"expect_local_arr_time'] : $schedule['plan_local_arr_time'];
                break;
            case "延误":
                $flag_time = (isset($statusinfo['expect_local_dep_time']) && !is_null($statusinfo['expect_local_dep_time']))? $statusinfo['"expect_local_dep_time'] : $schedule['plan_local_dep_time'];
                break;
            default:
                $flag_time = null;
                break;
        }
        
        $diff_minute = strtotime($flag_time) - time();
        $no_diff_area_match = 3600;
        foreach($STATUS_CACHE_CONFIG[$statusinfo['status']] as $diff_area=>$cache_time){
            if(!is_numeric($diff_area)) return $cache_time;
            $diff_area = intval($diff_area);
            if($diff_minute<$diff_area){
                return $cache_time;
            }
        }
        return $no_diff_area_match;
    }
    
    private function getSingleFlightScheduleCacheKey($flightno,$date){
        return 'Schedule-'.$flightno.$date;
    }
    
    private function getActInfoCacheKey($flightno,$dep,$arr,$date){
        return 'Actcode-'.$flightno.'-'.$dep.'-'.$arr.'-'.$date;
    }
    
    private function getSingleFlightStatusCacheKey($flightno,$dep,$arr,$date){
        return 'Status-'.$flightno.'-'.$dep.'-'.$arr.'-'.$date;
    }
    
    private function getDepArrSearchScheduleCacheKey($depcity,$arrcity,$date){
        return 'Schedule-Dep('.$depcity.')-Arr('.$arrcity.')-'.$date;
    }
}
?>
