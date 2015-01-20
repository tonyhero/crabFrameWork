<?php

/**
 * SendController
 * @author    Zhao shuang
 * @copyright 2014
 *
 * 首页
 */
class PushinfoController extends BaseCustomerController
{

    /**
     * 首页
     */
    function indexAction(){

         $this->checkUserCenterPrivilege();
         $gameconfig=Loader::pgetModel("gameconfig")->getGameList();
        
        
         $model=Loader::pgetModel("usergame");
         $data=$model->getUsergame();
         $usergame=array();
      
         if($data[0]=='all'){
            $usergame=$gameconfig;
            $type=1;
         }else{
             foreach ($gameconfig as $v) {
                if(in_array($v['name'], $data)){
                        $usergame[]=$v;
                   
                 }
             }
             $type=2;
        }
        $data= array('data'=>$usergame,'type'=>$type);
        Tools::view('pushinfo/index',$data);
    }

    function saveAction(){
        $this->checkUserCenterPrivilege();
    	 $gameconfig=Loader::pgetModel("gameconfig")->getGameList();
    	
    	$params                     = array();
		$params['userbelong']               =Loader::pgetService("user")->getUsername();
		$params['upload_filepath']             =$this->getRequest("file_url");;
		$params['sendcontent']              = $this->getRequest("sendcontent");
		$params['send_immediately']         = $this->getRequest("send_immediately");

        if($this->getRequest("send_immediately")){
            $params['sendtimestamp']=time();
        }else{
            $params['sendtimestamp']            = strtotime($this->getRequest("sendtimestamp"));
        }

		$params['offline_message_keeptime'] = $this->getRequest("offline_message_keeptime");
		$params['ios_sound']                = $this->getRequest("ios_sound");
		$params['android_notify_title']     = $this->getRequest("android_notify_title");
        $params['game_send_type']           =$this->getRequest("game_send_type");
        $game_list=$this->getRequest("game_send_list");
        if($params['game_send_type']==1){
            $str=implode(',',explode('_', $game_list));
            $params['game_send_list']           = '{{'.$str.'}}';//$this->getRequest("game_send_list");
            $tmp=array(explode(',', $str));
        }else{
            foreach ($gameconfig as $v) {
                if($v['platform']==$params['game_send_type']){
                    $arr[]='{'.implode(',', $v).'}';
                    $tmp[]=explode(',', implode(',', $v));
                }
            }
            $params['game_send_list']           = '{'.implode(',', $arr).'}';//$this->getRequest("game_send_list");
        }
		$params['tagid']                  = $this->getRequest("tagname");
        $params['create_time']              = date("Y-m-d H:i:s",time());
		$model = Loader::pgetDao("pushinfo");
        unset($params['game_send_type']);
		if($model->insert($params)){
            $params['id']=$model->getLastInsertId();
            $params['game_send_list']=json_encode($tmp);
            $server = Loader::pgetService("effecthistory")->saveHistory($params);
			$this->jsonOutput(array("success" => true));
		}else{
			$this->jsonOutput(array("success" => false));
		}
  
    }
    
        
    function uploadAction(){
    	$file='';
    	$act=$this->getRequest("act");
    	if($act=='ok'){
            try{
    		$upload=new Upload(array(0=>$_FILES['postfile']));
    		$file=$upload->upload('userlist','');
    		$file='/userlist/'.$file[0]['name'];
            }catch (SystemLogicException $e) {

            }
    	}
    	Tools::view('pushinfo/upload', array('file'=>$file));
    }
    
    function historyAction(){
        $this->checkUserCenterPrivilege();
        $game=Loader::pgetModel("usergame")->getUserCanDoGame();
        $starttime=$this->getRequest('starttime');
        $starttime=$starttime?$starttime:date('Y-m-d H:i:s',strtotime('-1 day'));
        $endtime=$this->getRequest('endtime');
        $endtime=$endtime?$endtime:date('Y-m-d H:i:s');
        $sel=$this->getRequest('sel');
        $sel=$sel?$sel:'';
        if($sel){
            $info=explode('-', $sel);
            $search['gamename']=$info[0];
            $search['cname']=$info[1];
            $search['platform']=$info[2];
        }
       
        if($this->getRequest('action')=='ok'){
          
            $currentPage=$this->getRequest('page');
            $model = Loader::pgetModel("effecthistory");
            $page=$model->gethistoryByPage($currentPage,20,$starttime,$endtime,$search);
           

        }else{
            $page['data']=array();
             $page['page_now']='';
              $page['page_num']='';
        }
         $url = '/pushinfo/history?action=ok&starttime='.$starttime.'&endtime='.$endtime.'&sel='.$sel;
         $page['url']=$url;
         Tools::view('pushinfo/history', array('game'=>(array)$game,'starttime'=>$starttime,'endtime'=>$endtime,'sel'=>$sel,'page'=>$page));
    }
    
    
    function cancelSendAction(){
        $id=$this->getRequest('id');
        $push=Loader::pgetModel("pushinfo")->cancelSend($id);
        if($push){
            $this->jsonOutput(array("success" => true));
        }else{
            $this->jsonOutput(array("success" => false));
        }
    }
       
}
