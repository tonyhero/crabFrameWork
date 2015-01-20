<?php

class upload_model extends BaseModel
{
    protected $_daoname = "upload";


    public function createUpload($file_data)
    {
        $dao = Loader::pgetDao($this->_daoname);

        $result = $dao->insert($file_data);

        return $dao->getLastInsertId();
    }

}
