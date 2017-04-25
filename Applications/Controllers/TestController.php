<?php
class TestController extends AppController {
    public function test() {
    }
    public function a() {
        R()->delLike('lm');
        R()->delLike('ex');
    }
    public function b(){
        (SuccShopIncomeController::getObj())->incomeHandle(['7145541093113224']);
    }
    public function c(){
        (new GoodsShowController())->delRedisCateGoods(1);
    }
    public function d(){
        (FailShopIncomeController::getObj())->incomeHandle(['7145541093113222','7145541093113223','7145541093113224']);
    }
    public function del(){
        R()->delFeild('detailLists');
        R()->delFeild('soldLists');
        R()->delLike('ex');
        R()->delLike('lm');

    }

}
