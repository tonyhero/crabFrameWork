<?php

class usergame_model extends BaseModel
{
    protected $_daoname = "usergame";
    public function getUsergame(){
    	$username=Loader::pgetService("user")->getUsername();
   		 $dao   = Loader::pgetDao($this->_daoname);
         $data=$dao->selectOne('game_list', array('username' => $username ));
         $usergame=$dao->pgarray_to_phparray($data['game_list']);
         return $usergame;
    }
    
    public function getUserCanDoGame (){
    	$gameconfig=Loader::pgetModel("gameconfig")->getGameList();
        
         $data=$this->getUsergame();
         $usergame=array();
      
         if($data[0]=='all'){
            $usergame=$gameconfig;
            
         }else{
             foreach ($gameconfig as $v) {
                if(in_array($v['name'], $data)){
                        $usergame[]=$v;
                   
                 }
             }
        }
        return $usergame;
    }
}