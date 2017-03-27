<?php
class mRedis{

    private static $_redis;
    public $_redisHandle;
    public  $pass;

    private function __construct(){
        $this->_redisHandle = new Redis();
        $this->pass = $this->_redisHandle->connect('127.0.0.1',6379);
    }

    public static function getRedis(){
        if(!(self::$_redis instanceof self)){
            self::$_redis = new self();
        }
        return self::$_redis;
    }

    public function setHash($key,$field,$value,$type=null){
        // $this->del($key);
        $value = $type == 1 ? json_encode($value) : $value;
        // D($value);die;
        $this->_redisHandle->hset($key,$field,$value);
    }

    public function setHashList($key,$arr=[]){
        // $this->del($key);
        // D($arr);die;
        foreach ($arr as $k => $v) {
            $v = json_encode($v);
            $this->setHash($key,$k,$v);
        }
    }

    public function getHash($key,$field){
        $value = $this->_redisHandle->hget($key,$field);
        // D($value);die;
        return json_decode($value,true);
    }

    public function getHashList($key){
        $list = $this->_redisHandle->hgetall($key);
        // D($list);die;
        foreach ($list as $k => $v) {
            $_list[] = json_decode($v,true);
        }
        return $_list;
    }

    public function getHashPage($key,$lo,$len){
        for($i=0;$i<$len;$i++){
            $arr[] = (int)$lo + (int)$i;
        }
        $list = $this->_redisHandle->hmget($key,$arr);
        foreach ($list as $k => $v) {
            $_list[] = json_decode($v,true);
        }
        return $_list;
    }

    public function del($key){
        $this->_redisHandle->delete($key);
    }

    public function exisit($key){
        return $this->_redisHandle->exists($key);
    }


}