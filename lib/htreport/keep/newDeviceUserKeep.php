<?php
require_once(dirname(__FILE__) . '/../keepReport.php');
class newDeviceUserKeep extends keepReport{
    public $pre_inner_sql = "  select DISTINCT(a.uid) from (
    select b.uid from ngw_did a join ngw_taobao_log b on a.id = b.did_id where a.report_date = '%s'
) a join ";

    public function __construct($time='',$media='',$type){
        if(empty($time) || !isset($media)) return;
        $this->pre_inner_sql = sprintf($this->pre_inner_sql,$time);
        parent::__construct($time,['field'=>' report_date,count(DISTINCT(uid)) as device_num '],$type);

        if($media != ''){
            $this->inner_sql =  $this->pre_inner_sql." ngw_tracking b on a.uid = b.uid where b.report_date = '{$time}' and b.source = '{$media}' ";
        }else{
            $this->inner_sql =  $this->pre_inner_sql." ngw_uid b on a.uid = b.objectId where b.report_date = '{$time}' ";
        }
    }

    public function createSQL(){
        $this->sql = sprintf($this->sql,$this->inner_sql);

        if($this->type == 'month')
            $this->sql = sprintf($this->topsql,'%Y-%u','device_num','device_num',$this->sql);
        return $this->sql;
    }

}