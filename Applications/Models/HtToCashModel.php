<?php
class HtToCashModel{
    /**
     * [getApplyCash 查询所有用户的提现列表，包括申请中，已成功提现，已提现失败]
     * [duiba_stime 没值 表示正在申请提现的用户  duiba_stime有值 表示正在申请中  duiba_success 有值 表示提现成功 duiba_end_errmsg 有值 表示提现失败]
     */
    public  function   getApplyCash($data = []){
        //如果参数中传了ID,就按照id查询，不论什么状态(申请中，已提现，已拒绝。。。)
        if(isset($data['id'])){
            return M('pnow')->where(['id' => ['=',$data['id']]])->select();

        }
        //查询处理中的提现列表，即duiba_stime不为null，且duiba_success和duiba_end_errmsg为空，传参为duiba_stime=true
        else if(isset($data['duiba_stime'])){
            $page = !empty($data['page']) ? $data['page'] : 1;
            $data['data']=M("pnow")->where(' duiba_stime is not null and duiba_success is null and duiba_end_errmsg is null ')->page($page,50)->select();
            $data['sum']=M("pnow")->where(' duiba_stime is not null and duiba_success is null and duiba_end_errmsg is null ')->field('id')->count();
            return $data;
        }
        //查询提现成功的列表，即duiba_success 有值，传参为duiba_success=true
        else if(isset($data['duiba_success'])){
            $page = !empty($data['page']) ? $data['page'] : 1;
            $data['data']=M("pnow")->where(' duiba_success is not null')->page($page,50)->select();
            $data['sum']=M("pnow")->where(' duiba_success is not null ')->field('id')->count();
            return $data;

        }
        //查询提现失败的列表，duiba_end_errmsg有值，传参为duiba_end_errmsg=true
        else if(isset($data['duiba_end_errmsg'])){
            $page = !empty($data['page']) ? $data['page'] : 1;
            $data['data']= M("pnow")->where(' duiba_end_errmsg is not null ')->page($page,50)->select();
            $data['sum']=M("pnow")->where(' duiba_end_errmsg is not null ')->field('id')->count();
            return $data;

        }
        //查询正在申请的提现列表，即duiba_stime为null，传参为apply=true
        else if(isset($data['apply'])){
            $page = !empty($data['page']) ? $data['page'] : 1;
            $data['data']= M("pnow")->where(' duiba_stime is  null')->page($page,50)->select();
            $data['sum']=M("pnow")->where(' duiba_stime is  null ')->field('id')->count();
            return $data;

        }
        else{
            $page = !empty($data['page']) ? $data['page'] : 1;
            //如果没有传参数，那么就查询所有状态的
            $data['data']= M("pnow")->page($page,50)->select();
            $data['sum']=M("pnow")->field('id')->count();
            return $data;
        }
    }
    //$data=[
    //'objectId'=>"xxxx",
    //'erromsg'=>'后台拒绝提现',
    //]
    public  function refuseToCash($data = []){
        if(isset($data['id'])) {
            $arr['duiba_end_errmsg']=$data['duiba_end_errmsg'];
            $arr['oper_id']=$data['oper_id'];
            $arr['duiba_stime']=$data['duiba_stime'];
            return M('pnow')->where(['id' => ['=',$data['id']]])->save($arr);
        }
    }



}