<?php
class DeviceverController extends AppController{
    public function __construct() {
        $this->status = 2;
        parent::__construct();
    }
    public function deviceVer() {
        $params = $this->dparam;
        $type = !empty($params['type']) ? : 0;
        if(empty($params['device']) || empty($params['status']))
            info('缺少参数', -1);
        switch ($params['status']) {
            //查库
            case 1:
                $data = $this->queryDevice($params['device']);
                $data = isset($data['type']) ? $data['type'] : info('库里可能还没存在',-1);
                break;
            //修改
            case 2:
                $data = M('device')->where(['deviceVer' => ['=',$params['device']]])->save(['type' => $type]);
                break;
            //添加
            case 3:
                if(!$this->queryDevice($params['device'])) {
                    $data = M('device')->add(['deviceVer' => $params['device'], 'type' => $type]);
                } else {
                    info('库里已经存在', false);
                }
                break;
        }
        info('ok',(int)$data);
    }
    public function queryDevice($device = '') {
        return $device ? M('device')->where(['deviceVer' => ['=', $device]])->field('type')->select('single') : M('device')->select();
    }
}