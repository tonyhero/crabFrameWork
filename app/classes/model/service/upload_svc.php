<?php

/**
 * 上传服务
 *
 * @author  yeyongfa<zhangjianwai813@gmail.com>
 * @copyright 2014
 */
class upload_svc
{
    private $upload_instance;

    public function __construct()
    {
        $this->upload_instance = new Upload(); 
    }

    /**
     * 执行上传功能
     *
     */
    public function up($appid , $store_type)
    {
        $upload_result = $this->upload_instance->upload($appid, $store_type);

        $insert_data = $upload_result;
        if($upload_result['result'] === true) 
        {
            unset($insert_data['result']); 
            foreach($insert_data as $v => $val)
            {
//                foreach($v as $val)
//                {
                    $data['hash']       = $val['hash'];
                    $data['type']       = $store_type;
                    $data['appid']      = $appid;
                    $data['filename']   = $val['name'];
                    $data['filesize']   = $val['size'];

                    $upload_model = Loader::pgetModel("upload");
                    $upload_model->createUpload($data);
//                }
            }
        }
        return $upload_result;
    }

    /**
     * 通过附件hash值获取相关URI
     *
     * @param string hash 文件hash值
     * @param int appid 应用id
     * @param string store_type 存储类型
     *
     * @return string 返回URI
     */
    public function getUrlByHash($hash, $appid = 1000, $store_type = 'localhost')
    {
        $condition = array(
            'hash'  => $hash,
            'appid' => $appid,     
        );
        $upload_model = Loader::pgetModel("upload");
        $filename = $upload_model->select('filename', $condition);

        $base_url = ($store_type == 'localhost') ? $_SERVER['SERVER_NAME'] : Loader::getSelfConfigParams("CDN_URL");

        $uri = trim($base_url, '\\').'/upload/'.$appid.'/'.$filename[0]['filename'];
        return $uri;
    }
}
?>
