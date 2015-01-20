<?php

/**
 * appeallist_svc
 * 用于对外提供工单查询的数据服务
 * @author    Zheng Anquan
 * @copyright 2014
 */
class historylog_svc
{


    /**
     *
     * 获取当前用户的操作日志的列表
     *
     * @param int $page     当前页数
     * @param int $pageSize 每页的记录数
     *
     * @return array
     */
    public function getMyHistoryLogs($pageSize = -1, $page = 1)
    {
        $model = Loader::pgetModel("historylog");

        $username = Loader::pgetService("user")->getUsername();

        return $model->getHistoryLogByUser($username, $pageSize, $page);
    }

    public function getHistoryLog($start, $limit, $sort = "create_time", $dir = "desc")
    {
        $model = Loader::pgetModel("historylog");

        $username = Loader::pgetService("user")->getUsername();;

        $rows  = $model->getHistoryLogDetailsByUser($username, $start, $limit, $sort, $dir);
        $count = $model->countHistoryLogByUser($username);

        $result = array();


        $formTypeService = Loader::pgetService("formtype");
        $scondaryTypes   = $formTypeService->getSecondaryTypesMapping();
        $actionMapping   = $model->getActionMapping();

        $sourceModel  = Loader::pgetModel("sourcetype");
        $soureMapping = $sourceModel->getSourceMapping();

        $appealModel   = Loader::pgetModel("appeallist");
        $statusMapping = $appealModel->getProcessStatusMapping();

        for ($i = 0; $i < count($rows); $i++) {
            $rows[$i]["create_time"]        = Tools::trimPgTimestamp($rows[$i]["create_time"]);
            $rows[$i]["appeal_create_time"] = Tools::trimPgTimestamp($rows[$i]["appeal_create_time"]);
            $rows[$i]["finish_time"]        = Tools::trimPgTimestamp($rows[$i]["finish_time"]);
            $rows[$i]["expectfinish_time"]  = Tools::trimPgTimestamp($rows[$i]["expectfinish_time"]);

            $typeId                     = $rows[$i]["fsecondarytype_id"];
            $rows[$i]["fsecondarytype"] = isset($scondaryTypes[$typeId]) ? $scondaryTypes[$typeId]: "";
            $rows[$i]["action_type"]    = isset($actionMapping[$rows[$i]["action_type"]]) ? $actionMapping[$rows[$i]["action_type"]] : "";
            $rows[$i]["sourcetype_id"]  = isset($soureMapping[$rows[$i]["sourcetype_id"]]) ? $soureMapping[$rows[$i]["sourcetype_id"]] : "";

            $rows[$i]["process_status"] = isset($statusMapping[$rows[$i]["process_status"]]) ? $statusMapping[$rows[$i]["process_status"]] : "";
        }

        $result["recordsFiltered"] = $count;
        $result["data"]            = $rows;

        return $result;

    }


    public function  getLogByAppealCode($appealCode, $pageSize = -1, $page = 1)
    {
        $model = Loader::pgetModel("historylog");

        return $model->getHistoryLogByAppealCode($appealCode, $pageSize, $page);
    }

}
