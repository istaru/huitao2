<?php
class UidLogModel
{
    public function addUidLog($data,$status=true)
    {
        if(empty($data))
            info('数据有误',-1);

        return M('uid_log')->add($data,$status);
    }

    public function getUidLog($where,$field='*',$status=true)
    {
        if(empty($where))
            info('where不能为空',-1);
        $uid_log = M('uid_log')->field($field)->where($where)->select($status);

        return $uid_log;
    }
}