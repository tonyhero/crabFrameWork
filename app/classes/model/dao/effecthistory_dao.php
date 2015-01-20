<?php

class effecthistory_dao extends BaseDao
{
    //表名
    protected $_tablename = "effecthistory";
    
     public function getGameconfigByTime($starttime,$endtime){
        $sql = 'select gamename,platform,cname from '.$this->_tablename.' ';
        $sql .= "where  send_true_time>='".$starttime."' and send_true_time<'".$endtime."' group by gamename,platform,cname";
       return $this->getDbConnect("slaver")->select($sql);
    }
     public function getMsgidBytime($search,$starttime,$endtime){
        $sql = 'select msg_id from '.$this->_tablename.' ';
        $sql .= "where  send_true_time>='".$starttime."' and send_true_time<'".$endtime."' and gamename='".$search['gamename']."' and platform='".$search['platform']."' and cname='".$search['cname']."'";
       return $this->getDbConnect("slaver")->select($sql);
    }

    public function gethistoryByPage($currentPage,$page_size,$starttime,$endtime,$search){
        $page = array();
        $page['page_size'] = $page_size; //每页数量
        $sql = 'select count(id) as count from '.$this->_tablename.' ';
        $sql .= "where  send_true_time>='".$starttime."' and send_true_time<'".$endtime."' and gamename='".$search['gamename']."' and platform='".$search['platform']."' and cname='".$search['cname']."'";
        $count=$this->getDbConnect("slaver")->selectOne($sql);

        $page['count']=$count['count'];
        $page['page_num'] = ceil($page['count']/$page['page_size']); //总页数
        $page['page_now'] = empty($currentPage)?1:$currentPage; //当前页数
        $start = $page['page_size']*($page['page_now']-1);//skip 
        $sql = 'select * from '.$this->_tablename.' ';
        $sql .= "where  send_true_time>='".$starttime."' and send_true_time<'".$endtime."' and gamename='".$search['gamename']."' and platform='".$search['platform']."' and cname='".$search['cname']."' order by send_true_time  desc limit ".$page['page_size']." offset ".$start;
        $page['data']=$this->getDbConnect("slaver")->select($sql);
        return $page;
    }

}
