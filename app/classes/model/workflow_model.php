<?php
class workflow_model extends BaseModel
{
    protected $_daoname = "workflow";
    
    public function getWorkFlowInfoById($workflow_id){
        $dao = Loader::pgetDao($this->_daoname);
        $condition = array('id'=>$workflow_id);
        return $dao->selectOne('*',$condition);
    }
    
    //��ȡ��������һ���ڵ����ĸ���ɫ����
    public function getFirstnodeRoleinfo($workflow_id){
        $role = $this->getWorkFlowInfoById($workflow_id);
        $node_role_info = explode(',',str_replace(array("'","{","}"),'',$role['user_group']));
        return $node_role_info[0];
    }
    
    //��ȡ��������һ���ڵ����ĸ���ɫ����
    public function getNextnodeRoleinfo($workflow_id,$current_role){
        $role = $this->getWorkFlowInfoById($workflow_id);
        $node_role_info = explode(',',str_replace(array("'","{","}"),'',$role['user_group']));
        if(count($node_role_info)==1) return false;
        $find_current_role = false;
        foreach($node_role_info as $role){
            if($find_current_role==true) return $role;
            if($role==$current_role){
                $find_current_role = true;
            }
        }
        return false;
    }
    
    //�жϵ�ǰ��¼�Ŀͷ��Ƿ��Ǵ˹����������һ���ڵ�
    public function ifCurrentUserLastNode($workflow_id){
        $current_role_name = current(Loader::pgetService("user")->getUserRoleInfo());
        $role = $this->getWorkFlowInfoById($workflow_id);
        $array = explode(',',str_replace(array("'","{","}"),'',$role['user_group']));
        $last_node_role = end($array);
        return ($current_role_name==$last_node_role)? true : false;
    }
    
    /**
     * ��ȡ�ܹ������������Ľ�ɫ��Ϣ
     * @param int $workflow_id ������id
     * @return array
     */
    public function getWorkFlowRoleInfo($workflow_id){
        if(!$workflow_info = $this->selectOne("user_group",array('id'=>$workflow_id))){
            return false;
        }
        return explode(",",str_replace(array('{','}',"'",),'',$workflow_info['user_group']));
    }
}

?>