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
        // (new TimerTaskController())->orderInfo(['12401281776113222']);
        (SuccShopIncomeController::getObj())->incomeHandle(['12384660416113222']);
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
        $source = json_encode($this->dparam);
        $data['secret'] = randstr(10);
        $key = substr(MD5($data['secret']-2),8,16);
        echo $key;
        $data['content'] = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $source, MCRYPT_MODE_CBC, $key));
        D($data);
    }
    public function jiemi()
    {
        D($this->dparam);
    }

}
