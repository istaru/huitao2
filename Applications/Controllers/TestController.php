<?php
class TestController extends AppController {
    public function test() {
    }
    public function a() {
        R()->delLike('lm');
        R()->delLike('ex');
    }

}
