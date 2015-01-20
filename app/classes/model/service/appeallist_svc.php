<?php

/**
 * appeallist_svc
 * 用于对外提供工单查询的数据服务
 * @author    Zheng Anquan
 * @copyright 2014
 */
class appeallist_svc
{
    const SUBMITTED_FROM_USER  = 0;
    const SUBMITTED_FROM_STAFF = 1;
    private $_tmp_store = null;

    /**
     * 计算当前指派给我的工单数量
     * @return int
     */
    public function countMyAppeals()
    {
        $appealModel = Loader::pgetModel("appeallist");

        $username = Loader::pgetService("user")->getUsername();;

        return $appealModel->countAssignedAppeals($username);
    }

    /**
     *
     * 获取指派给我的工单列表
     *
     * @param int $page     当前页数
     * @param int $pageSize 每页的记录数
     *
     * @return array
     */
    public function getMyAppeals($pageSize = -1, $page = 1)
    {
        $appealModel = Loader::pgetModel("appeallist");

        $username = Loader::pgetService("user")->getUsername();;

        return $appealModel->getAppealsByUser($username, $pageSize, $page);
    }

    private function checkAppeal($appeal)
    {
        $result = true;
        $msg    = array();
        if (!isset($appeal["fsecondarytype_id"]) || !$appeal["fsecondarytype_id"]) {
            $result &= false;
            $msg[] = "表单类型";
        }
        if (isset($appeal["uid"]) && (!is_int($appeal["uid"]) && $appeal["uid"] <= 0)) {
            $result &= false;
            $msg[] = "UID";
        }

        $formType   = $appeal["fsecondarytype_id"];
        $formFields = @json_decode(Loader::pgetModel("fsecondarytype")->getFormfieldById($formType, true));

        foreach ($formFields as $filed) {
            if (isset($filed->required) && $filed->required && empty($appeal[$filed->name])) {
                $result &= false;
                $msg[] = $filed->label;
                continue;
            }
            if (!empty($appeal[$filed->name]) && isset($filed->type)) {
                $type   = $filed->type;
                $regStr = Tools::getPregxStr($type);
                if ($regStr && !preg_match($regStr, $appeal[$filed->name])) {
                    $result &= false;
                    $msg[] = $filed->label;
                }
            }
        }

        $msg1 = "";
        if (!empty($appeal["player_account"]) && !empty($appeal["email_change_to"]) && $appeal["player_account"] == $appeal["email_change_to"]) {
            $result = false;
            $msg1   = "修改的邮箱与原账号不可一样";
        }

        if (!$result) {
            $message = "";
            $andStr  = "";
            if (count($msg) > 0) {
                $message = implode(",", $msg) . '格式不正确';
                $andStr  = ",";
            }

            if (!empty($msg1)) {
                $message .= $andStr . $msg1;
            }

            throw new SystemError("{$message}", SystemError::INVALID_INPUT);
        } else {
            return true;
        }
    }

    /**
     * 新建工单
     *
     * @param array $appeal
     */
    public function createAppeal($appeal)
    {
        $appealModel             = Loader::pgetModel("appeallist");
        $appeal["appealcode"]    = $appealModel->getUniqueCode();
        $appeal["if_gamer_view"] = "TRUE";

        $fsendaryTypeModel = Loader::pgetModel("fsecondarytype");
        $workflowModel     = Loader::pgetModel("workflow");
        $workflowId        = $fsendaryTypeModel->getWorkflowId($appeal["fsecondarytype_id"]);
        $initRole          = $workflowModel->getFirstnodeRoleinfo($workflowId);

        $appeal["workflow_id"]      = $workflowId;
        $appeal["user_belong_role"] = $initRole;

        $this->checkAppeal($appeal);
        $appealId = $appealModel->createAppeal($appeal);

        $userInfo = Loader::pgetService("user")->getUserinfo();
        $username = $userInfo["username"];

        $historyModel = Loader::pgetModel("historylog");
        //TODO change the description and action_type to correct content
        $historyModel->log($username, $appeal["appealcode"], $appealId, "创建");

        return $appealId;
    }

    /**
     * 统计当前待带处理单据队列的相关信息
     *
     * @return array
     */
    public function statisticsCurrentAppealsQueue()
    {
        $appealModel = Loader::pgetModel("appeallist");

        return $appealModel->statisticsCurrentAppealsQueue();

        //
    }

    /**
     * 检查是否有访问表单所属工作流的权限
     *
     * @param array $controller_action_info ,包含controller_name和action_name字段
     * @param int   $workflow_id            工作流的id
     *
     * @return boolean
     */
    public function checkWorkflowPrivilege($controller_action_info, $workflow_id)
    {
        $uri            = $controller_action_info['controller_name'] . '/' . $controller_action_info['action_name'];
        $data_uri_value = "'" . $uri . "'";
        if (!$workflow_info = Loader::pgetModel("workflow")->selectOne('*', array('id' => $workflow_id))) {
            return false;
        }
        if (strstr($workflow_info['uri'], $data_uri_value) === false)
            return false;
        if (is_null($role_info = Loader::pgetService("user_svc")->getUserRoleInfo()))
            return false;
        $flag = false;
        foreach ($role_info as $role_name) {
            $data_role_name = "'" . $role_name . "'";
            if (strstr($workflow_info['user_group'], $data_role_name) !== false) {
                $flag = true;
                break;
            }
        }

        return $flag;
    }

    //接取工单
    public function acceptAppeal()
    {
        $role      = current(Loader::pgetService("user")->getUserRoleInfo()); //获取人员所属的角色
        $user_name = Loader::pgetService("user")->getUsername();

        return Loader::pgetModel("appeallist")->getMyAppeal($user_name, $role);
    }

    public function getGameServerInfo($apptype){
        $interface_url = sprintf(Loader::getSelfConfigParams("GAME-SERVER-INFO-INTERFACE"),$apptype);
        $data_back = @json_decode(HttpRequest::_curl_get($interface_url),true);
        $game_server_config = isset($data_back['detail'])? $data_back['detail'] : array();
        return $game_server_config;
    }

    //游戏名称列表数组
    public function getGameList($apptype)
    {
        return array_keys($this->getGameServerInfo($apptype));
    }

    //获取游戏对应的渠道名称列表数组
    public function getChannelList($apptype,$game)
    {
        $game_server_config = $this->getGameServerInfo($apptype);
        return isset($game_server_config[$game]) ? array_keys($game_server_config[$game]) : null;
    }

    //获取游戏某渠道下的服务器列表
    public function getGameServerList($apptype,$game, $channel)
    {
        $game_server_config = $this->getGameServerInfo($apptype);
        return isset($game_server_config[$game][$channel]) ? $game_server_config[$game][$channel] : null;
    }


    public function getList($query, $start = 0, $limit = -1)
    {
        $model = Loader::pgetModel("appeallist");

        $rows  = $model->listAppeals($query, $start, $limit, "create_time desc");
        $count = $model->countAppeals($query);

        $formTypeService = Loader::pgetService("formtype");
        $scondaryTypes   = $formTypeService->getSecondaryTypesMapping();

        $statusModel   = Loader::pgetModel("appeallist");
        $statusMapping = $statusModel->getProcessStatusMapping();


        for ($i = 0; $i < count($rows); $i++) {
            $rows[$i]["create_time"]    = Tools::trimPgTimestamp($rows[$i]["create_time"]);
            $typeId                     = $rows[$i]["fsecondarytype_id"];
            $rows[$i]["fsecondarytype"] = isset($scondaryTypes[$typeId]) ? $scondaryTypes[$typeId] : "";
            $rows[$i]["process_status"] = isset($statusMapping[$rows[$i]["process_status"]]) ? $statusMapping[$rows[$i]["process_status"]] : "";
        }

        return array(
            //            "recordsTotal" => $count,
            "recordsFiltered" => $count,
            "data"            => $rows
        );
    }

    public function getAppeal($appealId)
    {
        $model  = Loader::pgetModel("appeallist");
        $appeal = $model->getAppeal($appealId);
        if (empty($appeal)) {
            throw new SystemError(SystemError::getErrorMsg(SystemError::APPEAL_NOT_FIND), SystemError::APPEAL_NOT_FIND);
        } else {
            return $appeal;
        }
    }

    public function getAppealByCode($appealCode)
    {
        $model  = Loader::pgetModel("appeallist");
        $appeal = $model->getAppealByCode($appealCode);
        if (empty($appeal)) {
            throw new SystemError(SystemError::getErrorMsg(SystemError::APPEAL_NOT_FIND), SystemError::APPEAL_NOT_FIND);
        } else {
            return $appeal;
        }
    }


    public function countUserUnreadAppealsByUid($uid)
    {
        $model = Loader::pgetModel("appeallist");
        $count = $model->countUserUnreadAppealsByUid($uid);

        return $count;
    }

    public function getUserAppeals($username, $start, $limit)
    {
        $model   = Loader::pgetModel("appeallist");
        $appeals = $model->getUserAppeals($username, $start, $limit);

        return $appeals;
    }

    public function getUserAppealsByUid($uid, $start, $limit)
    {
        $model   = Loader::pgetModel("appeallist");
        $appeals = $model->getUserAppealsByUid($uid, $start, $limit);

        return $appeals;
    }

    /**
     * 指派申诉单给其他客服人员
     *
     * @param array   $params_list  ,包含(appeallist_id)和(user_belong:被指派的客服名)两字段
     * @param boolean $direct_refer ,是否可以直接指派,不需要先接取(指派检索页面用)
     *
     * @return void
     */
    public function referAppeal($params_list, $direct_refer = false)
    {
        $appeallist_id      = $params_list['appeallist_id'];
        $refer_to_user_name = $params_list['user_belong']; //被指派的客服的名称
        $expectfinish_time  = isset($params_list['expectfinish_time']) ? $params_list['expectfinish_time'] : null;
        $desc               = isset($params_list['desc']) ? $params_list['desc'] : null; //备注说明信息

        if (!$appeal_info = Loader::pgetModel("appeallist")->selectOne("*", array("id" => $appeallist_id))) {
            throw new SystemError("找不到对应的申诉单信息", SystemError::APPEAL_NOT_FIND);
        }

        if ($appeal_info['process_status'] == appeallist_dao::FINISH_ACCEPTED_PROCESS_STATUS) {
            throw new SystemError("申诉单已经处理完成,不能进行当前操作", SystemError::NO_ACTION_PERMIT_WITH_APPEAL_END);
        }
        $this->checkReferActionPrivilege($appeal_info, $refer_to_user_name, $direct_refer);
        $refer_info = $this->_tmp_store;
        if (!Loader::pgetModel("appeallist")->referAppeal($appeallist_id, $refer_info['refer_from_user_name'], $refer_info['refer_to_user_name'], $refer_info['refer_to_user_role'], $direct_refer, $expectfinish_time)) {
            throw new SystemError("指派失败,系统错误", SystemError::SYSTEM_ERROR);
        }
        $detail = array(
            'refer_from_user'  => $refer_info['refer_from_user_name'],
            'user_belong'      => $refer_info['refer_to_user_name'],
            'user_belong_role' => $refer_info['refer_to_user_role'],
            'desc'             => $desc,
        );
        Loader::pgetModel("historylog")->log($refer_info['refer_from_user_name'], $appeal_info['appealcode'], $appeal_info['id'], "指派", $detail);

        return;
    }

    //检查当前客服是否有指派此申诉单的权限
    private function checkReferActionPrivilege($appeal_info, $refer_to_user_name, $direct_refer)
    {
        //获取当前客服的名称
        if (!$refer_user_name = Loader::pgetService("user")->getUsername()) {
            throw new SystemError("用户未登陆", SystemError::USER_NOT_LOGIN);
        }

        if (!$role_info = Loader::pgetService("user")->getUserRoleInfo()) {
            throw new SystemError("当前人员未分配任何角色组", SystemError::NOT_ENOUGH_PRIVILEGE_TO_ACCEPT);
        }

        $workflow_id        = $appeal_info['workflow_id'];
        $workflow_role_info = Loader::pgetModel("workflow")->getWorkFlowRoleInfo($workflow_id); //获取有此工作流权限的角色
        if (!$direct_refer) {
            if ($appeal_info['user_belong'] !== $refer_user_name)
                throw new SystemError("当前用户不是申诉单的处理者,没有指派的权限", SystemError::NOT_ENOUGH_PRIVILEGE_TO_REFER);
            $current_role_name = current($role_info); //当前客服的角色名称

            if (!in_array($current_role_name, $workflow_role_info)) {
                throw new SystemError("当前人员所属角色组无操作此申诉单的权限", SystemError::NOT_ENOUGH_PRIVILEGE_TO_ACCEPT);
            }
        }


        $refer_to_user_role = Loader::pgetService("user")->getUserRoleInfo($refer_to_user_name); //获取被指派人员的角色组名称
        if (!in_array($refer_to_user_role, $workflow_role_info)) {
            throw new SystemError("被指派人员所属角色无处理此申诉单的权限", SystemError::NOT_ENOUGH_PRIVILEGE_TO_ACCEPT);
        }
        $refer_info['refer_from_user_name'] = $refer_user_name;
        $refer_info['refer_to_user_name']   = $refer_to_user_name;
        $refer_info['refer_to_user_role']   = $refer_to_user_role;
        $this->_tmp_store                   = $refer_info;
    }

    /**
     * 驳回申诉单
     *
     * @param array $params_list ,包含(appeallist_id)和(desc:玩家看到的驳回描述信息)两字段
     *
     * @return void
     */
    public function rejectAppeal($params_list)
    {
        $appeallist_id     = $params_list['appeallist_id'];
        $result            = $params_list['desc'];
        $expectfinish_time = isset($params_list['expectfinish_time']) ? $params_list['expectfinish_time'] : null;
        if (!$appeal_info = Loader::pgetModel("appeallist")->selectOne("*", array("id" => $appeallist_id))) {
            throw new SystemError("找不到对应的申诉单信息", SystemError::APPEAL_NOT_FIND);
        }

        if ($appeal_info['process_status'] == appeallist_dao::FINISH_ACCEPTED_PROCESS_STATUS) {
            throw new SystemError("申诉单已经处理完成,不能进行当前操作", SystemError::NO_ACTION_PERMIT_WITH_APPEAL_END);
        }
        $this->checkRejectActionPrivilege($appeal_info);
        $reject_info = $this->_tmp_store;
        if (!Loader::pgetModel("appeallist")->rejectAppeal($appeallist_id, $result, $expectfinish_time)) {
            throw new SystemError("驳回失败,系统错误", SystemError::SYSTEM_ERROR);
        }
        $detail = array(
            'user_belong'      => $reject_info['current_user_name'],
            'user_belong_role' => $reject_info['current_user_role'],
            'result'           => $result,
        );
        Loader::pgetModel("historylog")->log($reject_info['current_user_name'], $appeal_info['appealcode'], $appeal_info['id'], "驳回", $detail);

        return;
    }

    //检查当前客服是否有驳回申诉单的权限
    private function checkRejectActionPrivilege($appeal_info)
    {
        //获取当前客服的名称
        if (!$current_user_name = Loader::pgetService("user")->getUsername()) {
            throw new SystemError("用户未登陆", SystemError::USER_NOT_LOGIN);
        }

        if (!$role_info = Loader::pgetService("user")->getUserRoleInfo()) {
            throw new SystemError("当前人员未分配任何角色组", SystemError::NOT_ENOUGH_PRIVILEGE_TO_REJECT);
        }

        $workflow_id        = $appeal_info['workflow_id'];
        $workflow_role_info = Loader::pgetModel("workflow")->getWorkFlowRoleInfo($workflow_id); //获取有此工作流权限的角色
        if ($appeal_info['user_belong'] !== $current_user_name)
            throw new SystemError("当前用户不是申诉单的处理者,没有驳回的权限", SystemError::NOT_ENOUGH_PRIVILEGE_TO_REJECT);
        $current_role_name = current($role_info); //当前客服的角色名称

        if (!in_array($current_role_name, $workflow_role_info)) {
            throw new SystemError("当前人员所属角色组无操作此申诉单的权限", SystemError::NOT_ENOUGH_PRIVILEGE_TO_REJECT);
        }
        $reject_info['current_user_name'] = $current_user_name;
        $reject_info['current_user_role'] = $current_role_name;
        $this->_tmp_store                 = $reject_info;
    }

    /**
     * 反馈复查申诉单
     *
     * @param array $params_list ,包含(appeallist_id)和(desc:接取申诉单的客服人员看到的驳回描述信息)两字段
     *
     * @return void
     */
    public function reviewAppeal($params_list)
    {
        $appeallist_id     = $params_list['appeallist_id'];
        $desc              = $params_list['desc'];
        $expectfinish_time = isset($params_list['expectfinish_time']) ? $params_list['expectfinish_time'] : null;
        if (!$appeal_info = Loader::pgetModel("appeallist")->selectOne("*", array("id" => $appeallist_id))) {
            throw new SystemError("找不到对应的申诉单信息", SystemError::APPEAL_NOT_FIND);
        }

        if ($appeal_info['process_status'] == appeallist_dao::FINISH_ACCEPTED_PROCESS_STATUS) {
            throw new SystemError("申诉单已经处理完成,不能进行当前操作", SystemError::NO_ACTION_PERMIT_WITH_APPEAL_END);
        }

        $this->checkReviewActionPrivilege($appeal_info);
        $review_info = $this->_tmp_store;
        if (!Loader::pgetModel("appeallist")->reviewAppeal($appeallist_id, $review_info['first_node_role_name'], $expectfinish_time)) {
            throw new SystemError("反馈复查失败,系统错误", SystemError::SYSTEM_ERROR);
        }
        $detail = array(
            'user_belong_role' => $review_info['first_node_role_name'],
            'desc'             => $desc,
        );
        Loader::pgetModel("historylog")->log($review_info['current_user_name'], $appeal_info['appealcode'], $appeal_info['id'], "反馈复查", $detail);

        return;
    }

    //检查当前客服是否有反馈复查申诉单的权限
    private function checkReviewActionPrivilege($appeal_info)
    {
        //获取当前客服的名称
        if (!$current_user_name = Loader::pgetService("user")->getUsername()) {
            throw new SystemError("用户未登陆", SystemError::USER_NOT_LOGIN);
        }

        if (!$role_info = Loader::pgetService("user")->getUserRoleInfo()) {
            throw new SystemError("当前人员未分配任何角色组", SystemError::NOT_ENOUGH_PRIVILEGE_TO_REVIEW);
        }

        $workflow_id        = $appeal_info['workflow_id'];
        $workflow_role_info = Loader::pgetModel("workflow")->getWorkFlowRoleInfo($workflow_id); //获取有此工作流权限的角色
        if ($appeal_info['user_belong'] !== $current_user_name)
            throw new SystemError("当前用户不是申诉单的处理者,没有反馈复查的权限", SystemError::NOT_ENOUGH_PRIVILEGE_TO_REVIEW);
        $current_role_name = current($role_info); //当前客服的角色名称

        if (!in_array($current_role_name, $workflow_role_info)) {
            throw new SystemError("当前人员所属角色组无操作此申诉单的权限", SystemError::NOT_ENOUGH_PRIVILEGE_TO_REVIEW);
        }

        //不是最后一个节点,则无反馈复查的权限
        if (Loader::pgetModel("workflow")->getNextnodeRoleinfo($appeal_info['workflow_id'], $current_role_name)) {
            throw new SystemError("当前角色组无反馈复查的权限", SystemError::NOT_ENOUGH_PRIVILEGE_TO_REVIEW);
        }


        $first_node_role_name                = Loader::pgetModel("workflow")->getFirstnodeRoleinfo($appeal_info['workflow_id']);
        $review_info['first_node_role_name'] = $first_node_role_name;
        $review_info['current_user_name']    = $current_user_name;
        $this->_tmp_store                    = $review_info;
    }


    /**
     * 待补充申诉单
     *
     * @param array $params_list ,包含(appeallist_id)和(desc:玩家看到的待补充的描述信息)两字段
     *
     * @return void
     */
    public function resupplyAppeal($params_list)
    {
        $appeallist_id     = $params_list['appeallist_id'];
        $result            = $params_list['desc'];
        $expectfinish_time = isset($params_list['expectfinish_time']) ? $params_list['expectfinish_time'] : null;
        if (!$appeal_info = Loader::pgetModel("appeallist")->selectOne("*", array("id" => $appeallist_id))) {
            throw new SystemError("找不到对应的申诉单信息", SystemError::APPEAL_NOT_FIND);
        }

        if ($appeal_info['process_status'] == appeallist_dao::FINISH_ACCEPTED_PROCESS_STATUS) {
            throw new SystemError("申诉单已经处理完成,不能进行当前操作", SystemError::NO_ACTION_PERMIT_WITH_APPEAL_END);
        }
        $this->checkResupplyActionPrivilege($appeal_info);
        $supply_info = $this->_tmp_store;
        if (!Loader::pgetModel("appeallist")->resupplyAppeal($appeallist_id, $result, $expectfinish_time)) {
            throw new SystemError("驳回失败,系统错误", SystemError::SYSTEM_ERROR);
        }
        $detail = array(
            'user_belong'      => $supply_info['current_user_name'],
            'user_belong_role' => $supply_info['current_user_role'],
            'result'           => $result,
        );
        Loader::pgetModel("historylog")->log($supply_info['current_user_name'], $appeal_info['appealcode'], $appeal_info['id'], "待补充", $detail);

        return;
    }

    //检查当前客服是否有驳回申诉单的权限
    private function checkResupplyActionPrivilege($appeal_info)
    {
        //获取当前客服的名称
        if (!$current_user_name = Loader::pgetService("user")->getUsername()) {
            throw new SystemError("用户未登陆", SystemError::USER_NOT_LOGIN);
        }

        if (!$role_info = Loader::pgetService("user")->getUserRoleInfo()) {
            throw new SystemError("当前人员未分配任何角色组", SystemError::NOT_ENOUGH_PRIVILEGE_TO_RESUPPLY);
        }

        $workflow_id        = $appeal_info['workflow_id'];
        $workflow_role_info = Loader::pgetModel("workflow")->getWorkFlowRoleInfo($workflow_id); //获取有此工作流权限的角色
        if ($appeal_info['user_belong'] !== $current_user_name)
            throw new SystemError("当前用户不是申诉单的处理者,没有驳回的权限", SystemError::NOT_ENOUGH_PRIVILEGE_TO_RESUPPLY);
        $current_role_name = current($role_info); //当前客服的角色名称

        if (!in_array($current_role_name, $workflow_role_info)) {
            throw new SystemError("当前人员所属角色组无操作此申诉单的权限", SystemError::NOT_ENOUGH_PRIVILEGE_TO_RESUPPLY);
        }
        $supply_info['current_user_name'] = $current_user_name;
        $supply_info['current_user_role'] = $current_role_name;
        $this->_tmp_store                 = $supply_info;
    }

    /**
     * 完成申诉单
     *
     * @param array $params_list ,包含(appeallist_id)和(desc:给当前节点的操作说明或者返回给玩家的信息)两字段
     *
     * @return void
     */
    public function finishAppeal($params_list)
    {
        $appeallist_id     = $params_list['appeallist_id'];
        $desc              = $params_list['desc'];
        $expectfinish_time = isset($params_list['expectfinish_time']) ? $params_list['expectfinish_time'] : null;
        if (!$appeal_info = Loader::pgetModel("appeallist")->selectOne("*", array("id" => $appeallist_id))) {
            throw new SystemError("找不到对应的申诉单信息", SystemError::APPEAL_NOT_FIND);
        }

        if ($appeal_info['process_status'] == appeallist_dao::FINISH_ACCEPTED_PROCESS_STATUS) {
            throw new SystemError("申诉单已经处理完成,不能进行当前操作", SystemError::NO_ACTION_PERMIT_WITH_APPEAL_END);
        }

        $this->checkFinishActionPrivilege($appeal_info);
        $finish_info = $this->_tmp_store;

        if ($next_role_name = Loader::pgetModel("workflow")->getNextnodeRoleinfo($appeal_info['workflow_id'], $finish_info['current_role_name'])) {
            //不是最后一个节点,则设置当前申诉单信息到下一个节点
            $skip_key_array = array('appeallist_id', 'desc', 'expectfinish_time');
            $params         = array();
            foreach ($params_list as $key => $val) {
                if (in_array($key, $skip_key_array))
                    continue;
                $params[$key] = $val;
            }
            if (!Loader::pgetModel("appeallist")->turnToNextnode($appeallist_id, $next_role_name, $params, $expectfinish_time)) {
                throw new SystemError("提交完成失败,系统错误", SystemError::SYSTEM_ERROR);
            }
            $detail = array(
                'user_belong_role' => $next_role_name,
                'desc'             => $desc,
            );
            Loader::pgetModel("historylog")->log($finish_info['current_user_name'], $appeal_info['appealcode'], $appeal_info['id'], "完成", $detail);
        } else {
            //当前为最后一个节点,则处理玩家信息，并且发送邮件，短信等

            Loader::pgetDao('fsecondarytype');
            switch ($appeal_info['fsecondarytype_id']) {
                case fsecondarytype_dao::FIND_ACCOUNT_ID :
                    $contact_email = Tools::isEmail(trim($appeal_info['contact_way'])) ? trim($appeal_info['contact_way']) : null; //联系邮箱
                    $email         = isset($params_list['email']) ? trim($params_list['email']) : '';
                    //if (!Tools::isEmail($contact_email) || !Tools::isEmail($email)) {
                    if (!Tools::isEmail($email)) {
                        throw new SystemError("填写的玩家邮箱必须是邮箱格式", SystemError::PARAMS_WORONG);
                    }

                    //获取实际发送给玩家的邮件内容(模板内容替换)
                    $send_contents  = Loader::pgetModel("msgtemplate")->getSendMsgTemplateContents($appeal_info['fsecondarytype_id'], $desc);
                    $contents_split = explode('==================================', $send_contents);
                    //$desc = $contents_split[1];
                    //print_r($contents_split);die();
                    if (!Loader::pgetModel("appeallist")->endAppeal($appeal_info, $send_contents, $expectfinish_time)) {
                        throw new SystemError("提交完成失败,系统错误", SystemError::SYSTEM_ERROR);
                    }

                    if (!is_null($contact_email)) {
                        PlaycrabMail::sendMail($contact_email, '纵乐账号找回账号通知', $contents_split[0]);
                    }
                    PlaycrabMail::sendMail($email, '纵乐账号找回账号通知', $contents_split[1]);
                    break;
                case fsecondarytype_dao::MODIFY_EMAIL_ID :
                    $email_change_to = trim($appeal_info['email_change_to']); //邮箱变更为
                    $email           = trim($appeal_info['player_account']); //玩家邮箱
                    $gamer_uid       = trim($appeal_info['gamer_uid']);
                    if (!Tools::isEmail($email_change_to) || !Tools::isEmail($email)) {
                        throw new SystemError("变更成的邮箱以及账号不是邮箱格式", SystemError::PARAMS_WORONG);
                    }
                    if (!Tools::isInt($gamer_uid)) {
                        throw new SystemError("客户端提交的uid不是数字", SystemError::PARAMS_WORONG);
                    }

                    if (!Loader::pgetModel("msgtemplate")->getMsgParse($desc)) {
                        throw new SystemError("填写的发送内容不符合格式,请检查", SystemError::PARAMS_WORONG);
                    }
                    $user_center_params['uid']      = $gamer_uid;
                    $user_center_params['username'] = $email; //玩家用户名
                    $user_center_params['type']     = 'email'; //类型为更改邮箱
                    $user_center_params['value']    = $email_change_to; //邮箱更改为

                    if ($email == $email_change_to) {
                        throw new SystemError("玩家原账号与变更成的账号不能相同", SystemError::PARAMS_WORONG);
                    }

                    $action_result = Loader::pgetService("account")->changeSafeInfo($user_center_params);
                    if ($action_result['result'] != 0) {
                        throw new SystemError("提交纵乐,处理失败", SystemError::SUMIT_ZONGLE_FAIL);
                    }

                    $send_contents = Loader::pgetModel("msgtemplate")->getSendMsgTemplateContents($appeal_info['fsecondarytype_id'], $desc);
                    if (!Loader::pgetModel("appeallist")->endAppeal($appeal_info, $send_contents, $expectfinish_time)) {
                        throw new SystemError("提交完成失败,系统错误", SystemError::SYSTEM_ERROR);
                    }
                    PlaycrabMail::sendMail($email_change_to, '纵乐账号变更通知', $send_contents);
                    PlaycrabMail::sendMail($email, '纵乐账号变更通知', $send_contents);
                    break;
                case fsecondarytype_dao::MODIFY_MOBILE_ID :
                    $mobile_change_to = trim($appeal_info['mobile_change_to']); //手机变更为
                    $mobile           = isset($params_list['mobile']) ? trim($params_list['mobile']) : ''; //玩家原来的手机号
                    $email            = trim($appeal_info['player_account']); //玩家邮箱
                    $gamer_uid        = trim($appeal_info['gamer_uid']); //玩家的uid
                    if (!Tools::isEmail($email)) {
                        throw new SystemError("玩家账号必须是邮箱格式", SystemError::PARAMS_WORONG);
                    }
                    if (!Tools::isInt($gamer_uid)) {
                        throw new SystemError("客户端提交的uid不是数字", SystemError::PARAMS_WORONG);
                    }
                    if (!Tools::isMobile($mobile_change_to) || !Tools::isMobile($mobile)) {
                        throw new SystemError("玩家原手机以及变更成的手机信息必须是手机号格式", SystemError::PARAMS_WORONG);
                    }
                    $user_center_params['uid']      = $gamer_uid;
                    $user_center_params['username'] = $email; //玩家用户名
                    $user_center_params['type']     = 'mobile'; //类型为更改手机
                    $user_center_params['value']    = $mobile_change_to; //手机更改为

                    $send_contents = Loader::pgetModel("msgtemplate")->getSendMsgTemplateContents($appeal_info['fsecondarytype_id'], $desc);


                    if (!$message = Loader::pgetModel("msgtemplate")->getMsgParse($desc)) {
                        throw new SystemError("填写的发送内容不符合格式,请检查", SystemError::SYSTEM_ERROR);
                    }
                    $action_result = Loader::pgetService("account")->changeSafeInfo($user_center_params);
                    if ($action_result['result'] != 0) {
                        throw new SystemError("提交纵乐,处理失败", SystemError::SUMIT_ZONGLE_FAIL);
                    }
                    if (!Loader::pgetModel("appeallist")->endAppeal($appeal_info, $send_contents, $expectfinish_time)) {
                        throw new SystemError("提交完成失败,系统错误", SystemError::SYSTEM_ERROR);
                    }
                    $appid            = Loader::getSelfConfigParams("MOBILE-MESSAGE-APPID"); //分配的发送短信所需的appid
                    $message_model_id = Loader::getSelfConfigParams("CHANGE-MOBILE-MESSAGE-MODEL-ID"); //修改手机情况下发送的模板id
                    $key              = Loader::getSelfConfigParams("MOBILE-MESSAGE-SEND-KEY"); //获取到发送所需的私钥
                    PlaycrabMessage::send($mobile_change_to, $appid, $message_model_id, $key, $message);
                    PlaycrabMessage::send($mobile, $appid, $message_model_id, $key, $message);
                    PlaycrabMail::sendMail($email, '纵乐账号变更手机信息通知', $send_contents);
                    break;
            }
            $detail = array(
                'result' => $send_contents,
            );
            Loader::pgetModel("historylog")->log($finish_info['current_user_name'], $appeal_info['appealcode'], $appeal_info['id'], "完成", $detail);

        }

        return;
    }

    //检查当前客服是否有完成申诉单的权限
    private function checkFinishActionPrivilege($appeal_info)
    {
        //获取当前客服的名称
        if (!$current_user_name = Loader::pgetService("user")->getUsername()) {
            throw new SystemError("用户未登陆", SystemError::USER_NOT_LOGIN);
        }

        if (!$role_info = Loader::pgetService("user")->getUserRoleInfo()) {
            throw new SystemError("当前人员未分配任何角色组", SystemError::NOT_ENOUGH_PRIVILEGE_TO_FINISH);
        }

        $workflow_id        = $appeal_info['workflow_id'];
        $workflow_role_info = Loader::pgetModel("workflow")->getWorkFlowRoleInfo($workflow_id); //获取有此工作流权限的角色
        //var_dump($appeal_info['user_belong']);var_dump($current_user_name);
        if ($appeal_info['user_belong'] !== $current_user_name)
            throw new SystemError("当前用户不是申诉单的处理者,没有完成操作的权限", SystemError::NOT_ENOUGH_PRIVILEGE_TO_FINISH);
        $current_role_name = current($role_info); //当前客服的角色名称

        if (!in_array($current_role_name, $workflow_role_info)) {
            throw new SystemError("当前人员所属角色组无操作此申诉单的权限", SystemError::NOT_ENOUGH_PRIVILEGE_TO_FINISH);
        }

        $finish_info['current_role_name'] = $current_role_name;
        $finish_info['current_user_name'] = $current_user_name;
        $this->_tmp_store                 = $finish_info;
    }

    public function markReadById($appealId)
    {
        $appealModel = Loader::pgetModel("appeallist");

        return $appealModel->markReadById($appealId);
    }

    public function markReadByCode($appealCode)
    {
        $appealModel = Loader::pgetModel("appeallist");

        return $appealModel->markReadByCode($appealCode);
    }
}
