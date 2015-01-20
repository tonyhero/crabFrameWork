<?php
class RdfReader
{
    public static function parseContent($contents){
        if(strpos($contents,'CONNECTION_FAIL')===true){
            throw new SystemError("warpper fail",SystemError::WARPPER_FAIL);
        }
        preg_match_all("/<rdf:Description\srdf:nodeID=\"[A-Z\d]+\".*?<\/rdf:Description>/",$contents,$match);
        if(!$match[0]){
            //warpper抓取成功,但是无数据
            return array();
        }
        $return = array();
        foreach($match[0] as $line){
            $data = array();
            preg_match_all("/<j.0:([A-Za-z\d]+)>([^<]+)/",$line,$params);
            foreach($params[0] as $key=>$value){
                $data[$params[1][$key]] = $params[2][$key];
            }
            $return[] = $data;
        }
        return $return;
    }
}
?>