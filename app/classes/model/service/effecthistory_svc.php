<?php
/**
 * demon_svc
 * 后台常驻的进程
 * @author yichen
 * @copyright 2014
 */
class effecthistory_svc
{
   public function saveHistory($data){
        $game_send_list = @json_decode($data['game_send_list']);
        if(!$game_send_list || !is_array($game_send_list) || (0==count($game_send_list))){
            Loader::pgetModel("pushinfo")->setPushFailById($data['id']);
            $errorMsg = 'action: runPushMessage | push_id:'.$data['id'].'| errorMsg:'.SystemError::getErrorMsg(SystemError::PUSHINFO_GAMELIST_NOT_EXIST);
            BaseLog::setDemonErrorLog($errorMsg);
        
        }

        foreach($game_send_list as $game){

            $tmp = explode('-',$game[0]);
            $history_insert['gamename'] = $tmp[0];//游戏名称
            $history_insert['cname'] = $tmp[1];//渠道名称
            $history_insert['platform'] = $game[1];//平台
            $history_insert['pushinfo_id'] = $data['id'];//推送信息的id
            $history_insert['send_true_time'] = date("Y-m-d H:i:s",$data['sendtimestamp']);//推送信息的id

             Loader::pgetModel("effecthistory")->insert($history_insert);
        }
   }
}
?>
