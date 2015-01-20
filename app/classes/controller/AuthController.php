<?php
/**
 * AuthController
 * 
 * @package customer_service_center
 * @copyright 2014
 * @version $Id$
 * @access public
 */
class AuthController extends BaseController
{
    
    function __construct(){
        Session::start();
        $this->allowAccessUri();
    }
    /**
     * 登陆页面
     */
    function indexAction(){
       Tools::view('auth/index', array());
    }
    
    
    /**
     * 登陆操作
     */
    function doLoginAction(){
        $username = $this->getRequest("username");
        $password = $this->getRequest("password");
        try{
            $uc = UserCenter::getInstance();
            $uc->init(Loader::getSelfConfigParams("USER-CENTER-PROGRAM-ID"),'','');
            $user_info = $uc->checkPassword($username,$password); //登录验证
        }catch(\UserCenterException $e){
            echo("<script language='javascript'>alert('登陆失败,请重新登陆');history.back();</script>");exit();
        }
        Loader::pgetService("user")->setUsername($username);
        $pmInfos = $uc->getPmRolesByUser(); //获取PM信息
        Loader::pgetService("user")->setUserUrlAllowaccess($pmInfos['funcOfRole']['*']['*']['dataForFunc']);//设置能访问的uri
        Loader::pgetService("user")->setUserRoleInfo($pmInfos['roleOfUser']['*']['*']);//设置当前人物所属的用户组
        $this->pageRedirect('/pushinfo/index');
    }


    /**
     * 登出操作
     */
    function logOutAction(){
        Loader::pgetService("user")->destoryUserinfo();
        $this->pageRedirect('/Auth');
    }
}

?>
