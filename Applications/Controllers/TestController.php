<?php
class TestController extends AppController {
    public function test() {
        $arr = json_decode(file_get_contents('http://127.0.0.1/test/2.json'), true);
        ++$arr['test'];
        file_put_contents('../test/2.json', json_encode($arr));
    }
    public function a() {
        R()->delLike('lm');
        R()->delLike('ex');
    }

}
