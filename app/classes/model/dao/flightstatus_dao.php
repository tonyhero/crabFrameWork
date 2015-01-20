<?php
class flightstatus_dao extends BaseDao
{
    //表名
    protected $_tablename = "flightstatus";
    const SCHEDULE_STATUS = 0;//计划
    const DEP_STATUS = 1;//起飞
    const ARR_STATUS = 2;//降落
    const DELAY_STATUS = 3;//延误
    const CNL_STATUS = 4;//取消
    const ALTERNATE_STATUS = 5;//备降
    const NOSCHEDULE_STATUS = 6;//今日无航班
    const NEED_CHECK_STATUS = 7;//待确定
    
    const FLIGHT_END = "TRUE";//航班是最终状态
    const FLIGHT_NEVER_END = "FALSE";//不是最终状态
    
    public function getTbname($data_array){
        if(isset($data_array['flightbelong'])){
            //$tmp = explode(" ",$data_array['flightbelong']);
            preg_match("/^([\d]{4}-?[\d]{2}-?[\d]{2})/",$data_array['flightbelong'],$match);
            $suffix = $match? "_".str_replace("-","",$match[0]) : "";
            return $this->_tablename.$suffix;
        }else{
            return $this->_tablename;
        }
    }
    
    //更新分表记录
    public function updateSubmeter($data_array,$condition){
        $tablename = $this->getTbname($condition);
        return $this->update($condition,$data_array,$tablename);
    }
    
    public function selectFromSubmeter($field,$condition,$order = null){
        $tablename = $this->getTbname($condition);
        return $this->select($field,$condition,$order,$tablename);
    }
    
    public function deleteFromSubmeter($condition){
        $tablename = $this->getTbname($condition);
        return $this->delete($condition,$tablename);
    }
    
    public function getFinfoByFlno($field,$condition,$order=null){
        $sql = "select * from ".$this->getTbname($condition);
        $sql .= " where 1=1 ";
        if(isset($condition['flightno'])){
            $sql .= " and (flightno = '".$condition['flightno']."' or shareno @>ARRAY['".$condition['flightno']."'])";
            unset($condition['flightno']);
        }
        foreach($condition as $key=>$val){
            $sql .= " and $key = '".$val."'";
        }
        if(!is_null($order)){
            $sql .= " order by $order";
        }
        $flighinfo_list = $this->getDbConnect("slaver")->select($sql);
        /***************
        foreach($flighinfo_list as $key=>$info){
            $flighinfo_list[$key]['flightno'] = $condition['flightno'];
        }
        ***************/
        return $flighinfo_list;
    }

    //获取所有的航信息
    public function getFlightAll($tablename, $currpage, $limit = 10){
        $sql = "select flightno from {$tablename} offset $currpage limit {$limit}";
        return $this->getDbConnect('slaver')->select($sql); 
    }

    public function getFlightCount($tablename){
        $sql = "select count(*) from {$tablename}";
        return $this->getDbConnect('slaver')->selectOne($sql); 
    }

}
