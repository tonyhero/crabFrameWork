<?php
class flightstatus_model extends BaseModel
{
    private $_daoname = "flightstatus";
    private $_Acrossday_arr_dep_maxdiff = 10800;//跨天航班,前一天的最后一条数据的降落时间与第二天第一条数据的最大时间差(三小时)
    
    public function insertFlightstatus($data_array){
        $dao = Loader::pgetDao($this->_daoname);
        $dao->insert($data_array);
    }
    
    public function setFlightCancel($condition,$date){
        $dao = Loader::pgetDao($this->_daoname);
        $condition['flightbelong'] = $date.' 00:00:00';
        $data_array['status'] = flightstatus_dao::CNL_STATUS;
        $data_array['ifend'] = flightstatus_dao::FLIGHT_END;
        return $dao->updateSubmeter($data_array,$condition);
    }
    
    public function getNeedCheckFlightlist($date){
        $dao = Loader::pgetDao($this->_daoname);
        $condition['flightbelong'] = $date.' 00:00:00';
        $condition['status'] = flightstatus_dao::NEED_CHECK_STATUS;
        $field = "*";
        return $dao->selectFromSubmeter($field,$condition);
    }
    
    //生成preid,nextid,preid_belong,nextid_belong字段
    public function createPreidNextid($date){
        $dao = Loader::pgetDao($this->_daoname);
        $field = "distinct flightno";
        $condition['flightbelong'] = $date." 00:00:00";
        $flightno_list = $dao->selectFromSubmeter($field,$condition);
        
        foreach($flightno_list as $flightno){
            $info_condition['flightno'] = $flightno['flightno'];
            $info_condition['flightbelong'] = $date." 00:00:00";
            $info_field = "*";
            $info_order = "id asc";
            $info_list=$dao->selectFromSubmeter($info_field,$info_condition,$info_order);
            $tmp = array();
            foreach($info_list as $flightinfo){
                $tmp[$flightinfo['id']] = $flightinfo;
            }
            ksort($tmp);
            $flightno_first_data = current($tmp);//每张表此航班号的第一条数据
            
            //对前一天的同航班号数据计算,是否是跨天航班,并且需要做preid,nextid的数据填充
            $this->setAcrossdayPreidNextid($flightno_first_data);
            
            
            $first_id = $flightno_first_data['id'];
            $init_key = 0;
            $s_array = array();
            foreach($tmp as $key=>$fl){
                if($key==$first_id){
                    $s_array[$init_key][] = $fl;
                    $pre_id = $key;
                    $pre_arr = $fl['arr'];
                }else{
                    $pre_id = (intval($pre_id)+1)."";
                    if(($key==$pre_id)&&($pre_arr==$fl['dep'])){
                        $s_array[$init_key][] = $fl;
                        $pre_arr = $fl['arr'];
                    }else{
                        $init_key++;
                        $s_array[$init_key][] = $fl;
                        $pre_id = $key;
                        $pre_arr = $fl['arr'];
                    }
                }
            }
            
            //print_r($s_array);
            foreach($s_array as $c_array){
                $update_field = array();
                $update_condition = array();
                foreach($c_array as $key=>$fl){
                    if($key==0){
                        $pid = $fl['id'];
                        $update_condition[$key]['flightbelong'] = $date." 00:00:00";
                        $update_condition[$key]['id'] = $fl['id'];
                        continue;
                    }else{
                        $update_field[$key-1]['nextid'] = $fl['id'];//设置上一条记录的nextid为当前记录的id
                        $update_field[$key-1]['nextid_belong'] = $date;//设置上一条记录的nextid数据归属日期
                        $update_field[$key]['preid'] = $pid;
                        $update_field[$key]['preid_belong'] = $date;//设置当前记录的preid数据对应的归属日期
                        $update_condition[$key]['flightbelong'] = $date." 00:00:00";
                        $update_condition[$key]['id'] = $fl['id'];
                        $pid = $fl['id'];
                    }
                }
                //开始更新preid,nextid,preid_belong,nextid_belong
                foreach($update_condition as $key=>$val){
                    if(!isset($update_field[$key])) continue;
                    $dao->updateSubmeter($update_field[$key],$update_condition[$key]);
                }
            }
        }
    }
    
    
    private function combineFlightinfo($sfl,$efl){
        if($sfl['dptcity']==$efl['arrcity']){
            return false;
        }
        $tmp = array();
        $tmp['dptcity'] = $sfl['dptcity'];
        $tmp['arrcity'] = $efl['arrcity'];
        $tmp['dptairport'] = $sfl['dptairport'];
        $tmp['arrairport'] = $efl['arrairport'];
        $tmp['dptdate'] = $sfl['dptdate'];
        $tmp['planetype'] = $sfl['planetype'];
        $tmp['plan_local_dep_time'] = $sfl['plan_local_dep_time'];
        $tmp['local_dep_timezone'] = $sfl['local_dep_timezone'];
        $tmp['plan_bj_dep_time'] = $sfl['plan_bj_dep_time'];
        $tmp['bj_dep_timezone'] = $sfl['bj_dep_timezone'];
        
        $tmp['plan_local_arr_time'] = $efl['plan_local_arr_time'];
        $tmp['local_arr_timezone'] = $efl['local_arr_timezone'];
        $tmp['plan_bj_arr_time'] = $efl['plan_bj_arr_time'];
        $tmp['bj_arr_timezone'] = $efl['bj_arr_timezone'];
        $tmp['flighttime'] = strtotime($tmp['plan_bj_arr_time']) - strtotime($tmp['plan_bj_dep_time']);
        return $tmp;
    }
    
    public function getCombineFlightDetailByDateFlno($flightno,$date){
        $flight_info = $this->getFlightDetailByDateFlno($flightno,$date);
        $key_array = array(
                        'dptcity'=>'','arrcity'=>'','dptairport'=>'',
                        'arrairport'=>'','dptdate'=>'','planetype'=>'',
                        'plan_local_dep_time'=>'','local_dep_timezone'=>'',
                        'plan_bj_dep_time'=>'','bj_dep_timezone'=>'',
                        "plan_local_arr_time"=>'',"local_arr_timezone"=>'',
                        "plan_bj_arr_time"=>'',"bj_arr_timezone"=>'','flighttime'=>'',
                        );
        if(isset($flight_info['detail']) && is_array($flight_info['detail'])){
            foreach($flight_info['detail'] as $index=>$part){
                foreach($part as $key=>$fl){
                    foreach($fl as $kw=>$val){
                        if(!isset($key_array[$kw])){
                            unset($flight_info['detail'][$index][$key][$kw]);
                        }
                    }
                }
            }
            foreach($flight_info['detail'] as $index=>$part){
                $count = count($part);//存在几条数据
                if($count>1){
                    foreach($part as $key=>$fl){
                        $i = $key+1;
                        while(isset($part[$i])){
                            if($combineinfo = $this->combineFlightinfo($part[$key],$part[$i])){
                                $flight_info['detail'][$index][] = $combineinfo;
                                $flight_info['sectionnumber']++;
                            }
                            $i++;
                        }
                    }
                }

            }
        }
        return $flight_info;
    }
    
    //通过航班号,日期获取航班某日的实际飞行轨迹
    public function getFlightDetailByDateFlno($flightno,$date){
        $flight_info['ret'] = true;
        $flight_info['sectionnumber'] = 0;
        $flight_info['detail'] = null;
        $dao = Loader::pgetDao($this->_daoname);
        $field = "*";
        $condition['flightbelong'] = $date." 00:00:00";
        $condition['flightno'] = $flightno;
        $order = "id asc";
        $info_list = $dao->getFinfoByFlno($field,$condition,$order);
        $scheudle_data_array = array("dep","arr","flightschedule","schedule_start","schedule_end","plan_local_dep_time","plan_local_arr_time");
        if($info_list){
            $index = 0;
            foreach($info_list as $p2p){
                if(isset($tmp)) unset($tmp);
                
                /********** 过滤掉除9c之外的所有状态不确定的航班基础信息 **************
                if((!preg_match("/^9C/",$flightno)) && ($p2p['status']==flightstatus_dao::NEED_CHECK_STATUS)) continue;//未确定状态的数据做丢弃处理
                ********** 过滤掉除9c之外的所有状态不确定的航班基础信息 **************/
                
                $schedule = array();
                if($schedule_data = Loader::pgetModel('schedule')->getScheduleDataByFnoDepArr($flightno,$p2p['dep'],$p2p['arr'])){
                    foreach($schedule_data as $key=>$singledata){
                        foreach($scheudle_data_array as $keyname){
                            $schedule[$key][$keyname] = $singledata[$keyname];
                        }
                    }
                }
                //print_r($p2p);die();
                $detail[$index][] = array(
                                    "dptcity"=>$p2p['depcity'],
                                    "arrcity"=>$p2p['arrcity'],
                                    "dptairport"=>$p2p['dep'],
                                    "arrairport"=>$p2p['arr'],
                                    "dptdate"=>str_replace(" 00:00:00","",$p2p['flightbelong']),
                                    "actcode"=>$p2p['flightno'],
                                    "shareno"=>str_replace(array("{","}"),"",$p2p['shareno']),
                                    "planetype"=>$p2p['planetype'], 
                                    "plan_local_dep_time"=>$p2p['plan_local_dep_time'],
                                    "local_dep_timezone"=>$p2p['local_dep_timezone'],
                                    "plan_bj_dep_time"=>$p2p['plan_bj_dep_time'],
                                    "bj_dep_timezone"=>$p2p['bj_dep_timezone'],
                                    "plan_local_arr_time"=>$p2p['plan_local_arr_time'],
                                    "local_arr_timezone"=>$p2p['local_arr_timezone'],
                                    "plan_bj_arr_time"=>$p2p['plan_bj_arr_time'],
                                    "bj_arr_timezone"=>$p2p['bj_arr_timezone'],
                                    "flighttime"=>intval($p2p['flighttime']),
                                    "expect_local_dep_time"=>$p2p['expect_local_dep_time'],
                                    "expect_bj_dep_time"=>$p2p['expect_bj_dep_time'],
                                    "expect_local_arr_time"=>$p2p['expect_local_arr_time'],
                                    "expect_bj_arr_time"=>$p2p['expect_bj_arr_time'],
                                    "actual_local_dep_time"=>$p2p['actual_local_dep_time'],
                                    "actual_bj_dep_time"=>$p2p['actual_bj_dep_time'],
                                    "actual_local_arr_time"=>$p2p['actual_local_arr_time'],
                                    "actual_bj_arr_time"=>$p2p['actual_bj_arr_time'],
                                    "alternate_airport"=>$p2p['alternate_airport'],
                                    "alternate_airportname"=>$p2p['alternate_airportname'],
                                    "status"=>intval($p2p['status']),
                                    "last_update_time"=>$p2p['last_update_time'],
                                    "schedule"=>$schedule,
                                    );
                if($p2p['nextid']==0) $index++;
                $tmp = $p2p;
            }
            if(isset($tmp) && $tmp['nextid']!=0){
                $ncondition['flightbelong'] = $tmp['nextid_belong']." 00:00:00";
                $ncondition['id'] = $tmp['nextid'];
                $ncondition['flightno'] = $flightno;
                $info_list = $dao->getFinfoByFlno($field,$ncondition,$order);
                
                /*********** 过滤掉除9c之外的所有状态不确定的航班基础信息 ************
                if($info_list && (($info_list[0]['status']!=flightstatus_dao::NEED_CHECK_STATUS) || (preg_match("/^9C/",$flightno)))){
                ********** 过滤掉除9c之外的所有状态不确定的航班基础信息 ***********/
                if($info_list){
                    $schedule = array();
                    if($schedule_data = Loader::pgetModel('schedule')->getScheduleDataByFnoDepArr($flightno,$info_list[0]['dep'],$info_list[0]['arr'])){
                        foreach($schedule_data as $key=>$singledata){
                            foreach($scheudle_data_array as $keyname){
                                $schedule[$key][$keyname] = $singledata[$keyname];
                            }
                        }
                    }
                    $detail[$index][] = array(
                                        "dptcity"=>$info_list[0]['depcity'],
                                        "arrcity"=>$info_list[0]['arrcity'],
                                        "dptairport"=>$info_list[0]['dep'],
                                        "arrairport"=>$info_list[0]['arr'],
                                        "dptdate"=>str_replace(" 00:00:00","",$info_list[0]['flightbelong']),
                                        "actcode"=>$info_list[0]['flightno'],
                                        "shareno"=>str_replace(array("{","}"),"",$info_list[0]['shareno']),
                                        "planetype"=>$info_list[0]['planetype'],                                        
                                        "plan_local_dep_time"=>$info_list[0]['plan_local_dep_time'],
                                        "local_dep_timezone"=>$info_list[0]['local_dep_timezone'],
                                        "plan_bj_dep_time"=>$info_list[0]['plan_bj_dep_time'],
                                        "bj_dep_timezone"=>$info_list[0]['bj_dep_timezone'],
                                        "plan_local_arr_time"=>$info_list[0]['plan_local_arr_time'],
                                        "local_arr_timezone"=>$info_list[0]['local_arr_timezone'],
                                        "plan_bj_arr_time"=>$info_list[0]['plan_bj_arr_time'],
                                        "bj_arr_timezone"=>$info_list[0]['bj_arr_timezone'],
                                        "flighttime"=>intval($info_list[0]['flighttime']),
                                        "expect_local_dep_time"=>$info_list[0]['expect_local_dep_time'],
                                        "expect_bj_dep_time"=>$info_list[0]['expect_bj_dep_time'],
                                        "expect_local_arr_time"=>$info_list[0]['expect_local_arr_time'],
                                        "expect_bj_arr_time"=>$info_list[0]['expect_bj_arr_time'],
                                        "actual_local_dep_time"=>$info_list[0]['actual_local_dep_time'],
                                        "actual_bj_dep_time"=>$info_list[0]['actual_bj_dep_time'],
                                        "actual_local_arr_time"=>$info_list[0]['actual_local_arr_time'],
                                        "actual_bj_arr_time"=>$info_list[0]['actual_bj_arr_time'],
                                        "alternate_airport"=>$info_list[0]['alternate_airport'],
                                        "alternate_airportname"=>$info_list[0]['alternate_airportname'],
                                        "status"=>intval($info_list[0]['status']),
                                        "last_update_time"=>$info_list[0]['last_update_time'],
                                        "schedule"=>$schedule,
                                        );
                    $nextid = $info_list[0]['nextid'];
                    while($nextid!=0){
                        //echo("1<br/>");
                        $ncondition['flightbelong'] = $info_list[0]['nextid_belong']." 00:00:00";
                        $ncondition['id'] = $nextid;
                        $ncondition['flightno'] = $flightno;
                        $nextid = 0;
                        $info_list = $dao->getFinfoByFlno($field,$ncondition,$order);
                        if($info_list) $nextid = $info_list[0]['nextid'];
                        /*********** 过滤掉除9c之外的所有状态不确定的航班基础信息 ************
                        if($info_list && (($info_list[0]['status']!=flightstatus_dao::NEED_CHECK_STATUS) || (preg_match("/^9C/",$flightno)))){
                        *********** 过滤掉除9c之外的所有状态不确定的航班基础信息 ************/
                        if($info_list){
                            $schedule = array();
                            if($schedule_data = Loader::pgetModel('schedule')->getScheduleDataByFnoDepArr($flightno,$info_list[0]['dep'],$info_list[0]['arr'])){
                                foreach($schedule_data as $key=>$singledata){
                                    foreach($scheudle_data_array as $keyname){
                                        $schedule[$key][$keyname] = $singledata[$keyname];
                                    }
                                }
                            }
                            $detail[$index][] = array(
                                                "dptcity"=>$info_list[0]['depcity'],
                                                "arrcity"=>$info_list[0]['arrcity'],
                                                "dptairport"=>$info_list[0]['dep'],
                                                "arrairport"=>$info_list[0]['arr'],
                                                "dptdate"=>str_replace(" 00:00:00","",$info_list[0]['flightbelong']),
                                                "actcode"=>$info_list[0]['flightno'],
                                                "shareno"=>str_replace(array("{","}"),"",$info_list[0]['shareno']),
                                                "planetype"=>$info_list[0]['planetype'],
                                                "plan_local_dep_time"=>$info_list[0]['plan_local_dep_time'],
                                                "local_dep_timezone"=>$info_list[0]['local_dep_timezone'],
                                                "plan_bj_dep_time"=>$info_list[0]['plan_bj_dep_time'],
                                                "bj_dep_timezone"=>$info_list[0]['bj_dep_timezone'],
                                                "plan_local_arr_time"=>$info_list[0]['plan_local_arr_time'],
                                                "local_arr_timezone"=>$info_list[0]['local_arr_timezone'],
                                                "plan_bj_arr_time"=>$info_list[0]['plan_bj_arr_time'],
                                                "bj_arr_timezone"=>$info_list[0]['bj_arr_timezone'],
                                                "flighttime"=>intval($info_list[0]['flighttime']),
                                                "expect_local_dep_time"=>$info_list[0]['expect_local_dep_time'],
                                                "expect_bj_dep_time"=>$info_list[0]['expect_bj_dep_time'],
                                                "expect_local_arr_time"=>$info_list[0]['expect_local_arr_time'],
                                                "expect_bj_arr_time"=>$info_list[0]['expect_bj_arr_time'],
                                                "actual_local_dep_time"=>$info_list[0]['actual_local_dep_time'],
                                                "actual_bj_dep_time"=>$info_list[0]['actual_bj_dep_time'],
                                                "actual_local_arr_time"=>$info_list[0]['actual_local_arr_time'],
                                                "actual_bj_arr_time"=>$info_list[0]['actual_bj_arr_time'],
                                                "alternate_airport"=>$info_list[0]['alternate_airport'],
                                                "alternate_airportname"=>$info_list[0]['alternate_airportname'],
                                                "status"=>intval($info_list[0]['status']),
                                                "last_update_time"=>$info_list[0]['last_update_time'],
                                                "schedule"=>$schedule,
                                                );
                        }
                    }
                }
            }
            
            $count = 0;
            if(isset($detail) && is_array($detail)){
                foreach($detail as $i=>$ar){
                    $count += count($detail[$i]);
                }
            }else{
                $detail = null;
            }

            $flight_info['sectionnumber'] = $count;
            $flight_info['detail'] = $detail;
        }
        Loader::closeDbCluster();
        return $flight_info;
    }
    
    
    //设置跨天航班的nextid,preid
    private function setAcrossdayPreidNextid($secondday_data){
        $second_day = date("Y-m-d",strtotime($secondday_data['flightbelong']));
        $first_day = date("Y-m-d",strtotime("-1 day",strtotime($secondday_data['flightbelong'])));
        $first_flightbelong_date = $first_day." 00:00:00";//前一天
        $first_flightinfo_condition['flightbelong'] = $first_flightbelong_date;
        $first_flightinfo_condition['flightno'] = $secondday_data['flightno'];
        
        $dao = Loader::pgetDao($this->_daoname);
        $field = "*";
        $order = "id desc";
        if($first_flightinfo_list = $dao->selectFromSubmeter($field,$first_flightinfo_condition,$order)){
            $last_data = $first_flightinfo_list[0];//前一天此航班号最后一条数据(按id降序)
            
            if(($last_data['arr']==$secondday_data['dep'])){
                $last_data_arr_timestamp = strtotime($last_data['plan_bj_arr_time']);
                $secondday_data_dep_timestamp = strtotime($secondday_data['plan_bj_dep_time']);
                if(($secondday_data_dep_timestamp-$this->_Acrossday_arr_dep_maxdiff)<=$last_data_arr_timestamp){
                    //更新跨天航班前一天的nextid,nextid_belong字段
                    $first_day_update_condition = array(
                                                    "id"=>$last_data['id'],
                                                    "flightbelong"=>$first_flightbelong_date,
                                                    );
                    $first_day_update_field = array(
                                                "nextid"=>$secondday_data['id'],
                                                "nextid_belong"=>$second_day,
                                                );
                    $dao->updateSubmeter($first_day_update_field,$first_day_update_condition);
                    //更新跨天航班第二天的preid,preid_belong字段
                    $second_day_update_condition = array(
                                                    "id"=>$secondday_data['id'],
                                                    "flightbelong"=>$secondday_data['flightbelong'],
                                                    );
                    $second_day_update_field = array(
                                                "preid"=>$last_data['id'],
                                                "preid_belong"=>$first_day,
                                                );
                    $dao->updateSubmeter($second_day_update_field,$second_day_update_condition);
                    //echo("first_data_id:".$last_data['id']."|second_data_id:".$secondday_data['id']."\n");
                }
            }
        }
    }
    
    
    //删除自动化测试数据
    public function deleteAutoTestData($date = array()){
        $dao = Loader::pgetDao($this->_daoname);
        if(!$date) $date = array("2013-11-17","2013-11-18","2013-11-19","2013-11-20");
        foreach($date as $d){
            $condition['flightbelong'] = $d." 00:00:00";
            if(!$dao->deleteFromSubmeter($condition)) return false;
        }
        $avtmp_dao = Loader::pgetDao("avtmp");
        $avtmp_dao->delete();
        $skdatatmp_dao = Loader::pgetDao("skdatatmp");
        $skdatatmp_dao->delete();
        return true;
        
    }

    //获取数据
    public function getFlightCurInfo($date, $currpage)
    {
        $dao = Loader::pgetDao($this->_daoname);
        $res = $dao->getFlightAll($date, $currpage);
        $res['c'] = $dao->getFlightCount($date);
        return $res;
    }
    
}

?>
