<?php

class effecthistory_model extends BaseModel
{
    protected $_daoname = "effecthistory";
    
  

  public function getGameconfigByTime($starttime,$endtime){
        $dao = Loader::pgetDao($this->_daoname);
        return  $dao->getGameconfigByTime($starttime,$endtime);
    }
    public function getMsgidBytime($search,$starttime,$endtime){
        $dao = Loader::pgetDao($this->_daoname);
        return  $dao->getMsgidBytime($search,$starttime,$endtime);
    }
    public function gethistoryByPage($currentPage,$page_size,$starttime,$endtime,$search){
    	$dao = Loader::pgetDao($this->_daoname);
    	$data=$dao->gethistoryByPage($currentPage,$page_size,$starttime,$endtime,$search);

    	foreach ($data['data'] as $k => $v) {
    		
    		$dao=Loader::pgetDao('pushinfo');
    		$pushinfo=$dao->selectOne('*',array('id'=>$v['pushinfo_id']));
    		
    		$data['data'][$k]['content']=$pushinfo['sendcontent'];
    		$data['data'][$k]['send_immediately']=$pushinfo['send_immediately'];
    		$data['data'][$k]['sendtimestamp']=$pushinfo['sendtimestamp'];
    		$data['data'][$k]['status']=$pushinfo['status'];
            $data['data'][$k]['userbelong']=$pushinfo['userbelong'];
    		$data['data'][$k]['statusdesc']=$dao->getStatusdesc($pushinfo['status']);

    	}
    
    	return $data;
    }
}