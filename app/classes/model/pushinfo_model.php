<?php

class pushinfo_model extends BaseModel
{
    protected $_daoname = "pushinfo";
    
    //取出已经设置为发送中状态的任务
    public function findNeedSendPushinfo(){
        $dao = Loader::pgetDao($this->_daoname);
        return $dao->findNeedSendPushinfo();
    }
    
    //将等待发送状态的任务设置为发送中
    public function updateWaitingToSending(){
        $dao = Loader::pgetDao($this->_daoname);
        return $dao->updateWaitingToSending();
    }

    //将id为$id的任务设置为发送失败
    public function setPushFailById($id){
        $dao = Loader::pgetDao($this->_daoname);
        $condition['id'] = $id;
        $data_array = array(
                        'status'=>pushinfo_dao::SEND_FAIL,
                        'try_times'=>1,
                        );
        return $dao->update($condition,$data_array);
    }

     public function updateSuccessToSending($id){
        $dao = Loader::pgetDao($this->_daoname);
        $dao->update(array('id'=>$id),array('status'=>$dao::SEND_SUCCESS));
    }
     public function updateFailToSending($id){
        $dao = Loader::pgetDao($this->_daoname);
        return $dao->update(array('id'=>$id),array('status'=>$dao::SEND_FAIL));
  
    }
     public function cancelSend($id){
        $dao = Loader::pgetDao($this->_daoname);
        return $dao->update(array('id'=>$id),array('status'=>$dao::SEND_CANCEL));
  
    }

}