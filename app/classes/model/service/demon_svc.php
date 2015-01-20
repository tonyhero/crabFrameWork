<?php
/**
 * demon_svc
 * 后台常驻的进程
 * @author yichen
 * @copyright 2014
 */
class demon_svc
{
    public function run($action){
        while(1){
            try{
                $this->$action();
            }catch(SystemError $e){
                $errorMsg = 'action:'.$action.'| errorMsg:'.$e->getMessage();
                BaseLog::setDemonErrorLog($errorMsg);
            }
        }
        sleep(5);
    }
    
    private function runPushMessage(){
        Loader::pgetModel("pushinfo")->updateWaitingToSending();
        if($sending_list = Loader::pgetModel("pushinfo")->findNeedSendPushinfo()){

            foreach($sending_list as $data){
                $game_send_list = @json_decode($data['game_send_list']);
                if(!$game_send_list || !is_array($game_send_list) || (0==count($game_send_list))){
                    Loader::pgetModel("pushinfo")->setPushFailById($data['id']);
                    $errorMsg = 'action: runPushMessage | push_id:'.$data['id'].'| errorMsg:'.SystemError::getErrorMsg(SystemError::PUSHINFO_GAMELIST_NOT_EXIST);
                    BaseLog::setDemonErrorLog($errorMsg);
                    continue;
                }
                foreach($game_send_list as $game){

                    $tmp = explode('-',$game[0]);
                    $history_insert['gamename'] = $tmp[0];//游戏名称
                    $history_insert['cname'] = substr($game[0],strlen($tmp[0])+1);//渠道名称
                    $history_insert['platform'] = $game[1];//平台
                    $history_insert['pushinfo_id'] = $data['id'];//推送信息的id
                    $history_insert['send_true_time'] = date("Y-m-d H:i:s");//推送信息的id
                    //Loader::pgetModel("effecthistory")->insert($history_insert);
                    
                    $sendinfo = $history_insert;
                    $sendinfo['sendcontent'] = $data['sendcontent'];
                    $sendinfo['upload_filepath'] = $data['upload_filepath'];
                    $sendinfo['offline_message_keeptime'] = $data['offline_message_keeptime'];
                    $sendinfo['ios_sound'] = $data['ios_sound'];
                    $sendinfo['android_notify_title'] = $data['android_notify_title'];
                    $sendinfo['tagid'] = $data['tagid'];
                    
                    $condition = array(
                                    'gamename'=>$sendinfo['gamename'],
                                    'cname'=>$sendinfo['cname'],
                                    'platform'=>$sendinfo['platform'],
                                    );
                    if(!$game_config = Loader::pgetModel("gameconfig")->selectOne("*",$condition)){
                        throw new SystemError("游戏配置不存在", SystemError::PUSHINFO_GAMECONFIG_NOT_EXIST);
                    }
                    
                    $sendinfo['push_key'] = $game_config['push_appkey'];
                    $sendinfo['secret'] = $game_config['push_secret'];
                    
                    Loader::pgetService("push")->pushMessage($sendinfo);
                }
            }
        }
    }

}
?>
