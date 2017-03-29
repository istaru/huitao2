<?php
class TestController extends AppController
{
    public function test() {
        TaoBaoApiController::__setas('23630111', 'd2a2eded0c22d6f69f8aae033f42cdce')->tbkItemGetRequest($this->dparam);
    }

}
