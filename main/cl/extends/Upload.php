<?php

class Upload
{
    /**
     * file instance
     *
     * @var array
     */
    private $file;

    /**
     * file counts
     *
     * @var int
     */
    private $count;

    /**
     * 允许上传的文件类型
     *
     * @var array
     */
    private $type_list;

    /**
     * 允许上传的文件大小
     *
     * @var int
     */
    private $file_size;

    /**
     * 上传保存路径
     *
     * @var string
     */
    private $path;


    public function __construct($file = null, $path = null )
    {
        $this->file = (null !== $file) ? $file : $_FILES;

        $this->path = (null !== $path) ? $path : Loader::getSelfConfigParams("UPLOAD_PATH");

        $this->count = count($this->file);

        $type_list = Loader::getSelfConfigParams('TYPE_LIST');
        $this->type_list = json_decode($type_list);

        $this->file_size = Loader::getSelfConfigParams('FILE_SIZE');
    }

    /**
	 * 移动文件
	 *
	 * @param  string  $path
	 * @param  string  $target
	 * @return bool
	 */
	public function move($path, $target)
	{
		return rename($path, $target);
	}

    /**
    * 单文件上传函数
    *
    * 成功返回包括文件名的路径。
    */
    public function upload($appid, $store_type)
    {
        $i = 0;
        foreach($this->file as $k => $v)
        {
            //1.判断文件上传是否错误
            if($this->file[$k]['error'] > 0)
            {
                switch($this->file[$k]['d'])
                {
                    case 1:
                        $err_info = "上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值";
                        $err_code = SystemError::OVER_CONFIG_MAX_FILESIZE;
                        break;

                    case 2:
                        $err_info = "上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值";
                        $err_code = SystemError::MAX_HTML_FILE_SIZE;
                        break;

                    case 3:
                        $err_info = "文件只有部分被上传";
                        $err_code = SystemError::ONLY_PARTIALLY_UPLOADED;
                        break;

                    case 4:
                        $err_info = "没有文件被上传";
                        $err_code = SystemError::NO_FILE_UPLOADED;
                        break;

                    case 6:
                        $err_info = "找不到临时文件夹";
                        $err_code = SystemError::NO_FIND_TMP;
                        break;

                    case 7:
                        $err_info = "文件写入失败";
                        $err_code = SystemError::IO_FAIL;
                        break;

                    default:
                        $err_info = "未知的上传错误";
                        $err_code = SystemError::FILE_UNKNOWN_ERROR;
                        break;
                }

                throw new SystemError($err_info.'该文件为：'.$this->files[$k]['name'], $err_code);
            }

            //2.判断上传文件类型是否合法
            if(count($this->type_list) > 0)
            {
                //error_log($this->file[$k]['type']);
                if(!in_array($this->file[$k]['type'], $this->type_list))
                {
                    throw new SystemError(SystemError::getErrorMsg(SystemError::FILE_TYPE_ILLEGAL).'该文件为：'.$this->file[$k]['name'], SystemError::FILE_TYPE_ILLEGAL);
                }
            }

            //4.判断上传文件大小是否超出允许值
            if($this->file[$k]['size'] > $this->file_size)
            {
                throw new SystemError(SystemError::getErrorMsg(SystemError::OVER_USER_MAX_FILESIZE).'该文件为：'.$this->file[$k]['name'], SystemError::OVER_USER_MAX_FILESIZE);
            }

            //5.上传文件重命名
            $exten_name = pathinfo($this->file[$k]['name'], PATHINFO_EXTENSION);

            do
            {
                $main_name  = date('YmdHis'.'--'.rand(100,999));
                $new_name   = $main_name.'.'.$exten_name;
                $hash       = md5($new_name.'powerbyzongle');
            }while(file_exists($this->path.'/'.$new_name));

            //6.判断是否是上传的文件，并移动文件
            if(!is_uploaded_file($this->file[$k]['tmp_name']))
            {
                throw new SystemError('这个文件不是上传文件'.'该文件为：'.$this->file[$k]['name'], SystemError::NOT_UPLOAD_FILE);
            }

            if(!is_dir($this->path.'/'.$appid))
            {
                mkdir($this->path.'/'.$appid);
            }

            if(!move_uploaded_file($this->file[$k]['tmp_name'], $this->path.'/'.$appid.'/'.$new_name))
            {
                throw new SystemError('上传文件移动失败'.'该文件为：'.$this->file[$k]['name'], SystemError::FILE_MOVE_ERROR);
            }

            $file[$k]           = array();
            $file['result']     = true;
            $file[$k]['name']   = $new_name;
            $file[$k]['hash']   = $hash;
            $file[$k]['size']   = $this->file[$k]['size'];
            $i++;
        }//for结束

        return $file;
    }

}
?>
