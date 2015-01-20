<?php
class ApiController extends BaseController
{
    public function getTagByGameAction(){
        try{
            $pregx_list = array("game"=>"/^[a-z\-\d]/i");
            $params['game'] = $action = $this->getRequest('game');
            $this->checkParmas($params, $pregx_list);
            $data_back['result'] = true;
            $data_back['detail'] = Loader::pgetModel("gameconfig")->getGameTagBygame($params['game']);
            $this->jsonOutput($data_back);
        }catch(SystemError $e){
            $this->displayJsonError($e);
        }
    }

    /**
     * 上传文件接口
     *
     */
    public function uploadFileAction()
    {
        $appid      = $this->getRequest('appid', 1000);
        $store_type = $this->getRequest('store_type', 'localhost');
        $upload_svc = Loader::pgetService("upload");
        //直接调用上传即可，支持一个或多个文件上传，上传成功会返回
        //每个文件相对应的文件名和hash值
        try {
            $result = $upload_svc->up($appid, $store_type);
        } catch (SystemError $e) {
            $this->displayJsonError($e);
        }
        $this->jsonOutput($result);
    }

}

