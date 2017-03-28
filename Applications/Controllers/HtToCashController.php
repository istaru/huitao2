<?php
date_default_timezone_set('Asia/Shanghai');
class  HtToCashController extends HtController {
    //查询提现申请
     function querycashapply(){
         $data = A('HtToCash:getApplyCash',[$_POST]);
         $data ? info('ok',1,$data) : info('暂无记录',-4);
     }
     //拒绝提现
     function refusetocash() {
         if(!empty($_POST)) {
             $res = A('HtToCash:refuseToCash',[$_POST]);
             $res ? info('拒绝提现成功',1) : info('拒绝提现失败',-1);
         }
         info('暂时无法处理，请稍后重试','-1');

     }
}