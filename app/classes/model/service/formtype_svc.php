<?php

/**
 * sourcetype_svc
 * 用于对source type进行操作的服务
 * @author    Zheng Anquan
 * @copyright 2014
 */
class formtype_svc
{

    /**
     * 获取所有来源类型的列表
     * @return array
     */
    public function getFirstTypes()
    {
        $types = Loader::pgetModel('formtype')->getFirstTypes();

        return $types;
    }

    /**
     * 获取所有来源类型的列表
     *
     * @param int $formTypeId 一级分类的id
     *
     * @return array
     */
    public function getSecondaryTypes($formTypeId = false)
    {
        $types = Loader::pgetModel('fsecondarytype')->getSecondaryTypes($formTypeId);

        return $types;
    }

    /**
     * 获取所有来源类型的列表
     *
     * @param int $formTypeId 一级分类的id
     *
     * @return array
     */
    public function getSecondaryTypesMapping($formTypeId = false)
    {
        $types  = Loader::pgetModel('fsecondarytype')->getSecondaryTypes($formTypeId);
        $result = array();
        foreach ($types as $type) {
            $result[$type["id"]] = $type["name"];
        }

        return $result;
    }

    /**
     * 获取所有分类信息
     */
    public function  getAllFormTypes()
    {

        $formTypes = $this->getFirstTypes();
        for ($i = 0; $i < count($formTypes); $i++) {
            $secondaryTypes            = $this->getSecondaryTypes($formTypes[$i]["id"]);
            $formTypes[$i]["subtypes"] = $secondaryTypes;
        }

        return $formTypes;
    }

}
