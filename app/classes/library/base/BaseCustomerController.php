<?php
if (!function_exists('getallheaders'))
{
    function getallheaders()
    {
        $headers = '';
        foreach ($_SERVER as $name => $value)
        {
            if (substr($name, 0, 5) == 'HTTP_')
            {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}

class BaseCustomerController extends BaseController
{
    protected $_check_user_login = true;
    protected $_default_login_uri = "/auth";


    function __construct()
    {
        Session::start();
        $this->allowAccessUri();
        $this->checkUserLogin();
    }

    function checkUserLogin()
    {
        $user_info = Loader::pgetService("user")->getUserinfo();

        if (is_null($user_info)) {
            $headers = getallheaders();
            if (isset($headers["X-Requested-With"])) {
                $result = array(
                    "result" => false,
                    "errMsg" => "长时间未活动，已经登出，请重新<a href='/Auth' target='_blank'>登录</a>"
                );
                $this->jsonOutput($result);
            } else {
                header("location:$this->_default_login_uri");
            }
            exit();
        }
    }


    /**
     * 检查工作流权限
     *
     * @param int $workflow_id workflower的id
     */
    function checkWorkflowPrivilege($workflow_id)
    {
        $controller_action_info = $this->getControllerActionName();
        $appeallistSvc          = Loader::pgetService("appeallist");
        if (!$appeallistSvc->checkWorkflowPrivilege($controller_action_info, $workflow_id)) {
            header("Content-type: text/html;charset=utf-8");
            echo("<script language='javascript'>alert('sorry,当前用户组无操作此工单的权限');history.back();</script>");
            exit();
        }
    }

    /**
     * 判断当前用户所属的用户组在用户中心是否配置了对应uri的权限
     */
    function checkUserCenterPrivilege()
    {
        $controller_action_info = $this->getControllerActionName();
       
        $user_uri_list          = Loader::pgetService("user")->getUserUrlAllowaccess();
        $uri                    = $controller_action_info['controller_name'] . '/' . $controller_action_info['action_name'];
        if (!in_array($uri, $user_uri_list)) {
            header("Content-type: text/html;charset=utf-8");
            echo("<script language='javascript'>alert('sorry,当前用户组无访问此地址的权限');history.back();</script>");
            exit();
        }
    }



    /**
     * 加载摸板
     *
     * @param string $view_name 模板名
     * @param array  $data      数据
     *
     * @return mixed
     */
    public static function view($view_name, $data = array())
    {
        //获取来源信息
        $sourceSvc = Loader::pgetService("sourcetype");
        $sources   = $sourceSvc->getAllSources();

        $data["appeal_sources"] = $sources;

        $formTypeSvc       = Loader::pgetService("formtype");
        $formTypes         = $formTypeSvc->getFirstTypes();
        $data["formtypes"] = $formTypes;


        //用于转换appeallist中得fsecondarytype到文字
        $scondaryTypes = $formTypeSvc->getSecondaryTypes();
        $typeMapping   = array();
        foreach ($scondaryTypes as $type) {
            $typeMapping[$type["id"]] = $type["name"];
        }
        $data["secondaryFormTypes"] = $typeMapping;


        Tools::view($view_name, $data);
    }

}

?>
