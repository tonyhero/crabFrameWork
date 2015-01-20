<?php
class BaseModel
{
    protected $_daoname = null;
    
    public function getDao(){
        if(is_null($this->_daoname)){
            echo(get_class($this).'short of daoname');exit();
        }
        return Loader::pgetDao($this->_daoname);
    }
    
    public function insert($data_array,$tablename = null){
        return $this->getDao()->insert($data_array,$tablename);
    }

    public function update($condition,$data_array,$tablename = null){
        return $this->getDao()->update($condition,$data_array,$tablename);
    }
    
    public function select($field,$condition,$order = null,$limit_str = null,$tablename = null){
        return $this->getDao()->select($field,$condition,$order,$limit_str,$tablename);
    }
    
    public function selectOne($field,$condition,$order = null,$limit_str = null,$tablename = null){
        return $this->getDao()->selectOne($field,$condition,$order,$limit_str,$tablename);
    }
    
    public function delete($condition,$tablename = null){
        return $this->getDao()->delete($condition,$tablename);
    }
    
    public function getUniqueCode()
    {
        //return md5($_SERVER['REMOTE_ADDR'] . uniqid(rand(), true));
        $digit_to_letter_map = array(
                                0=>array('u','Z','A','R','5','N'),
                                1=>array('2','s','Q','k','c','I'),
                                2=>array('S','n','q','W','g','H'),
                                3=>array('X','M','T','j','9','a'),
                                4=>array('r','P','J','7','1','w'),
                                5=>array('C','8','x','z','m','E'),
                                6=>array('O','o','6','L','F','e'),
                                7=>array('G','D','V','f','l','3'),
                                8=>array('p','Y','B','v','d','U'),
                                9=>array('t','h','i','b','K','0'),
                                );
        $current_str = substr(microtime(),2,6).substr(date('YmdHis'),2);
        $str_len = strlen($current_str);
        $code_str = '';
        for($i=0;$i<$str_len;$i++){
            $code_str .= $digit_to_letter_map[intval(substr($current_str,$i,1))][mt_rand(0,5)];
        }
        return $code_str;
    }
}

?>
