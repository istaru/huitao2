<?php
class TestController extends AppController {
    public function test() {
        file_put_contents('test.txt', date('H:i:s'), FILE_APPEND);
    }
    public function a() {
        R()->delLike('lm');
        R()->delLike('ex');
    }
    public function b(){
        (SuccShopIncomeController::getObj())->incomeHandle(['14227148149795698']);
    }
    public function c(){
        (new GoodsShowController())->delRedisCateGoods(1);
    }
    public function d(){
        (FailShopIncomeController::getObj())->incomeHandle(['12384660416113222']);
    }
    public function del(){
        R()->delLike('ex_');
        R()->delFeild('detailLists');
        R()->delFeild('soldLists');
        R()->delLike('lm_');
        R()->delLike('board_');
        echo 'ok';
    }
    public function jiami()
    {
        $arr = ['user_id'=>'0wG5FIQQMi'];
        $arr = json_encode($arr);
        $a = aes_encode($arr);
        D($a);
    }
    public function jiemi()
    {
        $a = aes_decode($this->dparam);
        D($a);
    }

}
