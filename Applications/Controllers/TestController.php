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

    }

}
