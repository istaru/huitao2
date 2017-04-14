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
        $sql = 'select id , name from ngw_category';
        $info = M()->query($sql,'all');
        // $sql = 'select id,category  from ngw_goods_online';
        // $goods = M()->query($sql,'all');
        // D($goods);

        foreach ($info as $k => $v) {
            $sql = "update ngw_goods_online set category_id = {$v['id']} where category like '{$v['name']}'";
            M()->query($sql);
            sleep(1);
            echo $sql.'<br>';
        }
    }

}
