<?php

/**
 * AssignController
 * @author    Zheng Anquan
 * @copyright 2014
 *
 * 指派检索
 */
class AssignController extends BaseCustomerController
{
    function indexAction()
    {
       // $this->checkUserCenterPrivilege();
        $role_list_info = Loader::pgetService("user")->getUserCenterRoleList();
        //        $this->view('workflow/accept', array("appeal_info" => $appeal_info, "formfield" => $formfield, "role_list_info" => $role_list_info));
        $appealService = Loader::pgetService("appeallist");
        $games         = $appealService->getGameList("kfbackend");
        $returnGames   = array();

        foreach ($games as $game) {
            $returnGames[$game] = array(
                "name" => $game
            );

            $channels = $appealService->getChannelList("kfbackend", $game);
            $subs     = array();

            foreach ($channels as $channel) {
                $servers     = $appealService->getGameServerList("kfbackend",$game, $channel);
                $channelSubs = array();
                foreach ($servers as $server) {
                    $channelSubs[$server] = array("name" => $server);
                }
                $subs[$channel] = array(
                    "name" => $channel,
                    "subs" => $channelSubs
                );
            }
            $returnGames[$game]["subs"] = $subs;
        }

        $model         = Loader::pgetModel("appeallist");
        $statusMapping = $model->getProcessStatusMapping();


        $this->view('assign/index', array(
            "role_list_info" => $role_list_info,
            "games"          => $returnGames,
            "status"         => $statusMapping
        ));
    }

    function ajaxlistAction()
    {
        $result = array();

        //        $result["draw"]            = $_POST["draw"];
        $result["recordsTotal"]    = 100;
        $result["recordsFiltered"] = 50;
        $result["data"]            = array(
            array("hello" => "1", "hello2" => 2)
        );

        $query = array();
        //账号
        $impressAccount = $this->getRequest("impress_account");
        if (!empty($impressAccount)) {
            $query["impress_account"] = $impressAccount;
        }

        //UID
        $uid = $this->getRequest("uid");
        if (!empty($uid)) {
            //TODO How to use UID ?
            $query["gamer_uid"] = $uid;
        }

        //单据ID
        $appealCode = $this->getRequest("appealcode");
        if (!empty($appealCode)) {
            $query["appealcode"] = $appealCode;
        }
        //状态
        $status = $this->getRequest("process_status");
        if ($status >= 0) {
            $query["process_status"] = $status;
        }
        //提交时间
        $createTimeStart = $this->getRequest("create_time");
        if (!empty($createTimeStart) && is_array($createTimeStart)) {
            if (isset($createTimeStart["start"]) && !empty($createTimeStart["start"])) {
                $query[] = array("create_time", ">=", $createTimeStart["start"]);
            }

            if (isset($createTimeStart["end"]) && !empty($createTimeStart["end"])) {
                $query[] = array("create_time", "<=", $createTimeStart["end"]);
            }
        }

        //结束时间
        $finishTime = $this->getRequest("finish_time");
        if (!empty($finishTime) && is_array($finishTime)) {
            if (isset($finishTime["start"]) && !empty($finishTime["start"])) {
                $query[] = array("finish_time", ">=", $finishTime["start"]);
            }

            if (isset($finishTime["end"]) && !empty($finishTime["end"])) {
                $query[] = array("finish_time", "<=", $finishTime["end"]);
            }
        }

        //预期结束时间
        $expectedFinishTime = $this->getRequest("expectfinish_time");
        if (!empty($expectedFinishTime) && is_array($expectedFinishTime)) {
            if (isset($expectedFinishTime["start"]) && !empty($expectedFinishTime["start"])) {
                $query[] = array("expectfinish_time", ">=", $expectedFinishTime["start"]);
            }

            if (isset($expectedFinishTime["end"]) && !empty($expectedFinishTime["end"])) {
                $query[] = array("expectfinish_time", "<=", $expectedFinishTime["end"]);
            }
        }

        //处理人
        $userBelong = $this->getRequest("user_belong");
        if (!empty($userBelong) && $userBelong != -1) {
            $query["user_belong"] = $userBelong;
        }

        //游戏
        $gameName = $this->getRequest("gamename");
        if (!empty($gameName) && $gameName != -1) {
            $query["gamename"] = $gameName;
        }

        //渠道
        $channel = $this->getRequest("channel");
        if (!empty($channel) && $channel != -1) {
            $query["channel"] = $channel;
        }

        //服务器
        $serverName = $this->getRequest("servername");
        if (!empty($serverName) && $serverName != -1) {
            $query["servername"] = $serverName;
        }

        $appealService = Loader::pgetService("appeallist");


        $start = $this->getRequest("start");
        $limit = $this->getRequest("length");

        $result         = $appealService->getList($query, $start, $limit);
        $result["draw"] = $this->getRequest("draw");

        $this->jsonOutput($result);
    }


    public function assignAction()
    {
        $params                     = array();
        $params['appeallist_id']    = $this->getRequest("appeal_id");
        $params['user_belong_role'] = $this->getRequest("user_belong_role");
        $params['user_belong']      = $this->getRequest("user_belong");


        try {
            Loader::pgetService("appeallist")->referAppeal($params, true);
            $this->jsonOutput(array("success" => true));
        } catch (Exception $e) {
            $this->jsonOutput(array("success" => false,
                                    "msg"     => $e->getMessage()
            ));
        }
    }
}

?>
