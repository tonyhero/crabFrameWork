<?php
/**
 * demon_svc
 * 后台常驻的进程
 * @author yichen
 * @copyright 2014
 */
class gethistory_svc
{
    public function run($action,$stime='',$etime=''){
        
            try{
                $this->$action($stime,$etime);
            }catch(SystemError $e){
                $errorMsg = 'action:'.$action.'| errorMsg:'.$e->getMessage();
                BaseLog::setDemonErrorLog($errorMsg);
            }
    
    }
    
    private function getHistory($stime='',$etime=''){
        if($stime&&$etime){
            $starttime=$stime;
            $endtime=$etime;
        }else{
            $starttime=date('Y-m-d 00:00:00',strtotime('-1 day'));
            $endtime=date('Y-m-d 00:00:00');
        }
        $model= Loader::pgetModel("effecthistory");
        $app_list=$model->getGameconfigByTime($starttime,$endtime);
        foreach ($app_list as $v) {
            $condition = array(
                            'gamename'=>$v['gamename'],
                            'cname'=>$v['cname'],
                            'platform'=>$v['platform'],
                            );
            if(!$game_config = Loader::pgetModel("gameconfig")->selectOne("*",$condition)){
                throw new SystemError("游戏配置不存在", SystemError::PUSHINFO_GAMECONFIG_NOT_EXIST);
            }
                    
            $sendinfo['push_key'] = $game_config['push_appkey'];
            $sendinfo['secret'] = $game_config['push_secret'];
            $msgs_id=$model->getMsgidBytime($v,$starttime,$endtime);
            foreach ($msgs_id as $v) {
               $sendinfo['msg_id'][]=$v['msg_id'];
            }
            Loader::pgetService("push")->getMessage($sendinfo);

        }

       
    }

}
?>
