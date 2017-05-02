<?php
date_default_timezone_set('Asia/Shanghai');
class  HtToCashController {
    //查询提现申请
     function querycashapply(){
         $data = A('HtToCash:getApplyCash',[$_POST]);
         $data ? info('ok',1,$data) : info('暂无记录',-4);
     }
     //拒绝提现
     function refusetocash() {
         if(!empty($_POST)) {
             $res = A('HtToCash:refuseToCash',[$_POST]);
             $info=M()->query("select price,uid from ngw_pnow where id='".$_POST['id']."'");
             $res2=M()->exec("update ngw_uid set price=price+".$info['price'].",pnow=pnow-".$info['price']."where objectId='".$info['uid']."'");
             $res&$res2? info('拒绝提现成功',1) : info('拒绝提现失败',-1);
         }
         info('暂时无法处理，请稍后重试','-1');

     }
}