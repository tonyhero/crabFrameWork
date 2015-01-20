<?php

class pushinfo_model extends BaseModel
{
    protected $_daoname = "pushinfo";
    
    //ȡ���Ѿ�����Ϊ������״̬������
    public function findNeedSendPushinfo(){
        $dao = Loader::pgetDao($this->_daoname);
        return $dao->findNeedSendPushinfo();
    }
    
    //���ȴ�����״̬����������Ϊ������
    public function updateWaitingToSending(){
        $dao = Loader::pgetDao($this->_daoname);
        return $dao->updateWaitingToSending();
    }

    //��idΪ$id����������Ϊ����ʧ��
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