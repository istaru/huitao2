<?php
class TestController extends AppController
{
    public function test() {
        $data = M('order_status')->getTableFields();
        foreach($data as $k => $v) {
            D($v);
        }
    }
    public function a() {
        (SuccShopIncomeController::getObj())->incomeHandle(['2960718718611576','2946998613943222']);
    }

}
