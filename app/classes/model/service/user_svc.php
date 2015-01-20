<?php

/**
 * user_svc
 * 提供客服人员信息服务
 * @author    yichen
 * @copyright 2014
 */
class user_svc
{
    public function getUserinfo(){
        return isset($_SESSION['info'])? $_SESSION['info'] : null;
    }
    
    public function setUsername($username){
        $_SESSION['info']['username'] = $username;
    }
    
    public function getUsername(){
        return (isset($_SESSION['info']['username']) && ($_SESSION['info']['username']!=''))? $_SESSION['info']['username'] : null;
    }
    
    public function setUserUrlAllowaccess($uri_array){
        $_SESSION['info']['uri'] = $uri_array;
    }
    
    public function getUserUrlAllowaccess(){
        return (isset($_SESSION['info']['uri']) && is_array($_SESSION['info']['uri']))? $_SESSION['info']['uri'] : array();
    }
    
    public function setUserRoleInfo($role_info){
        if(is_array($role_info)){
            foreach($role_info as $key=>$value){
                if($value=="Admin") unset($role_info[$key]);
            }
            $_SESSION['info']['role'] = $role_info;
        }
    }
    
    //获取客服人员的角色信息
    public function getUserRoleInfo($user_name = null){
        if(is_null($user_name)){
            return (isset($_SESSION['info']['role']) && is_array($_SESSION['info']['role']))? $_SESSION['info']['role'] : null;
        }
        $full_role_info = $this->getFullRoleinfo();
        foreach($full_role_info as $role_name=>$role_member){
            foreach($role_member as $s_name){
                if($s_name===$user_name){
                    return $role_name;
                }
            }
        }
        return false;
    }
    
    public function destoryUserinfo(){
        unset($_SESSION['info']);
    }
    
    //获取用户中心的角色组列表
    public function getUserCenterRoleList(){
        if($role_list = HttpRequest::_curl_get(Loader::getSelfConfigParams("USER-CENTER-ROLELIST-INTERFACE"))){
            return json_decode($role_list,true);
        }
    }
    
    /**
     * 通过角色的名称获取此角色下所有的用户名称
     * @param string $rolename 角色名称
     * @return array
     */
    public function getRoleMemberList($rolename){
        $full_role_info = $this->getFullRoleinfo();
        return isset($full_role_info[$rolename])? $full_role_info[$rolename] : array();
    }
    
    public function getFullRoleinfo(){
        if(!is_array($role_info = json_decode(HttpRequest::_curl_get(Loader::getSelfConfigParams("USER-CENTER-ROLEINFO-INTERFACE")),true))){
            return array();
        }
        $role_list = $this->getUserCenterRoleList();
        $full_role_info = array();
        foreach($role_info as $key=>$memberlist){
            if(isset($role_list[$key])) $full_role_info[$role_list[$key]] = $memberlist;
        }
        return $full_role_info;
    }
}
?>