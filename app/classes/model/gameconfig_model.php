<?php
class gameconfig_model extends BaseModel
{
    protected $_daoname = "gameconfig";

    public function getInfoByCondition($condition)
    {
        $dao   = Loader::pgetDao($this->_daoname);
        $field = "*";
        return $dao->select($field, $condition);
    }


    public function getGameList(){
        $condition = array();
        $order = 'platform desc';
        $data_back = array();
        if($select_back = $this->select("*",$condition,$order)){
            foreach($select_back as $row){
                $data_back[] = array(
                                'name'=>$row['gamename'].'-'.$row['cname'],
                                'platform'=>$row['platform']);
            }
        }
        return $data_back;
    }
    
    public function getGameTagBygame($game){
        $tmp = explode('-',$game);
        $gamename = $tmp[0];//游戏名称
        $cname = $tmp[1];//渠道名称
        
        return array('12'=>'vip','31'=>'male');
    }
    public function getKey($condition)
    {
        $dao   = Loader::pgetDao($this->_daoname);
        $field = "*";
        return $dao->selectOne($field, $condition);
    }
}