<?php
class BehaviourTempController extends AppController
{
    public $status  = true;
    public $count   = 10;   //记录商品数量


    //{"user_id":"","num_iid"}
    /**
     * [click 用户点击]
     */
    public function click()
    {
        if(empty($this->dparam['user_id']) || empty($this->dparam['num_iid'])) info('数据不完整',-1);

        if(!R()->exisit($this->dparam['user_id'])){

            if(R()->getTtl($this->dparam['user_id']) < 200){

            }
        }
    }
}