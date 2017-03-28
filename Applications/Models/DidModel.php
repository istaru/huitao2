<?php

class DidModel
{

    public function getDidInfo($where,$field='*',$status='single')
    {
        if(empty($where))
            info('where不能为空',-1);
        $d_info = M('did')->field($field)->where($where)->limit(1)->select($status);

        return $d_info;
    }

    public function addDid($data,$status=true)
    {
        if(empty($data))
            info('数据有误',-1);

        return M('did')->add($data,$status);
    }

}

