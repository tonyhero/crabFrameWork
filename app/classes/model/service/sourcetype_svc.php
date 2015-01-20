<?php

/**
 * sourcetype_svc
 * 用于对source type进行操作的服务
 * @author    Zheng Anquan
 * @copyright 2014
 */
class sourcetype_svc
{

    /**
     * 获取所有来源类型的列表
     * @return array
     */
    public function getAllSources()
    {
        $sources = Loader::pgetModel('sourcetype')->getAllSources();

        return $sources;
    }

}
