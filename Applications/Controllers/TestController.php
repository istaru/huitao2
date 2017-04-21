<?php
class TestController extends AppController {
    public function test() {
    }
    public function a() {
        R()->delLike('lm');
        R()->delLike('ex');
    }
    public function b(){
        (SuccShopIncomeController::getObj())->incomeHandle(['7142340340113222']);
    }

}
