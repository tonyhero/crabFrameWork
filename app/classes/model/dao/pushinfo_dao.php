<?php

class pushinfo_dao extends BaseDao
{
    //表名
    protected $_tablename = "pushinfo";
    
    const WAITING_SEND = '0';//等待发送
    const SENDING = '1';//发送中
    const SEND_SUCCESS = '2';//发送成功
    const SEND_FAIL = '3';//发送失败
    const SEND_CANCEL = '4';//发送失败
    
    
    static $_send_status_chinese_map = array(
                                        self::WAITING_SEND=>'等待发送',
                                        self::SENDING=>'发送中',
                                        self::SEND_SUCCESS=>'发送完成',
                                        self::SEND_FAIL=>'发送失败',
                                        self::SEND_CANCEL=>'取消发送'
                                        );
    
    public function updateWaitingToSending(){
        $sql = 'update '.$this->_tablename." set status = '".self::SENDING."' ";
        $sql .= "where status = '".self::WAITING_SEND."' and sendtimestamp <= ".time();
       return $this->getDbConnect("master")->executeSql($sql);
    }

    // public function updateSuccessToSending($id){
    //     $sql = 'update '.$this->_tablename." set status = '".self::SEND_SUCCESS."' ";
    //     $sql .= "where id=".$id;
    //     $this->getDbConnect("master")->executeSql($sql);
    //     return $dao->update($condition,$data_array);
    // }
    //   public function updateFailToSending($id){
    //     $sql = 'update '.$this->_tablename." set status = '".self::SEND_FAIL."' ";
    //     $sql .= "where id=".$id;
    //     $this->getDbConnect("master")->executeSql($sql);
    //     return $dao->update($condition,$data_array);
    // }
    public function findNeedSendPushinfo(){
        $sql = 'select id,sendcontent,upload_filepath,offline_message_keeptime,ios_sound,android_notify_title,array_to_json(game_send_list) as game_send_list,tagid ';
        $sql .= 'from '.$this->_tablename;
        $sql .= " where status = ".self::SENDING;
        return $this->getDbConnect("slaver")->select($sql);
    }

    
    public function getPushinfoById($id){
        $sql = 'select id,sendcontent,upload_filepath,offline_message_keeptime,ios_sound,android_notify_title,array_to_json(game_send_list) as game_send_list,tagid ';
        $sql .= 'from '.$this->_tablename;
        $sql .= " where id = '".$id."'";
        return $this->getDbConnect("slaver")->select($sql);
    }
    public function getStatusDesc($k){
        return self::$_send_status_chinese_map[$k];
    }
}
