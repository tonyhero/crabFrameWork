<?php

/**
 * SendController
 * @author    Zhao shuang
 * @copyright 2014
 *
 * 首页
 */
class UsergameController extends BaseCustomerController
{

    /**
     * 首页
     */
    function indexAction()
    {

    
    	$this->checkUserCenterPrivilege();
        $data=Loader::pgetModel("gameconfig")->getGameList();
         Tools::view('usergame/index',array('data'=>$data));
    }
     function saveAction()
    {
    	
    	$this->checkUserCenterPrivilege();
    	$params                     = array();
		$params['username']       =$this->getRequest("username");
		$params['game_list']        = $this->getRequest("gamelist");
        
		$model = Loader::pgetModel("usergame");
		$data=$model->selectOne('game_list',array('username'=>$params['username']));

		if($data){
			if($data['game_list']==$params['game_list']){
				$this->jsonOutput(array("success" => true));
			}else{
				if($model->update(array('username'=>$params['username']),array('game_list'=>$params['game_list']))){
					$this->jsonOutput(array("success" => true));
				}else{
					$this->jsonOutput(array("success" => false));
				}
			}
		}else{
			if($model->insert($params)){
				$this->jsonOutput(array("success" => true));
			}else{
				$this->jsonOutput(array("success" => false));
			}
		}
	
    }
    function getGamelistAction(){
    	$params['username']       =$this->getRequest("username");
		$model = Loader::pgetModel("usergame");
		$data=$model->selectOne('game_list',array('username'=>$params['username']));
		$this->jsonOutput(array("success" => true,'data'=>$data['game_list']));
    }
    function listAction(){
    	$this->checkUserCenterPrivilege();
    	$model = Loader::pgetModel("usergame");
		$data=$model->select('username,game_list',array());
		 Tools::view('usergame/list',array('data'=>$data));
    }


}
