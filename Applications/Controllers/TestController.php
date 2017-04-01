<?php
class TestController extends AppController
{
    public function test() {
        $a = 0;
        $b = 0;
        $data = true;
        for($i = 200; 1 < $i; $i--) {
            if($data) {
                ++$a;
                $data = get_curl('http://pub.alimama.com/items/search.json?q=https%3A%2F%2Fitem.taobao.com%2Fitem.htm%3Fspm%3Da219t.7900221%2F22.1998910419.d9a1dac8emuying.g2elIO%26id%3D527206059958&_t=1490948426333&auctionTag=&perPageSize=40&shopTag=yxjh&t=1490948426337&_tb_token_=test&pvid=10_116.231.154.205_472_1490948419770');
            } else {
                ++$b;
            }
            $data = json_decode($data, true);
        }
        echo $a.'<hr/>';
        echo $b;
    }
    public function a() {

    }

}
