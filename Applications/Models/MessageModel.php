<?php
class MessageModel
{
    public function addMsg($data,$status=true)
    {
       if(empty($data))
            info('数据有误',-1);
        return M('message')->add($data,$status);
    }

    public function getMsg($where,$field='*',$status=true)
    {
        if(empty($where))
            info('where不能为空',-1);
        $msg_info = M('message')->field($field)->where($where)->order('createdAt DESC')->limit(100)->select($status);

        return $msg_info;
    }
}