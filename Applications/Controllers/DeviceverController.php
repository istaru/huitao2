<?php
class DeviceverController extends AppController {
    public $deviceVer = null;
    public $type      = 0;
    public $stat      = 0;
    public $url       = null;
    public function __construct() {
        $this->status = 2;
        parent::__construct();
        $this->dparam = $this->dparam ? $this->dparam : $_REQUEST;
        //新版本
        if(!empty($this->dparam['app_ver'])) {
            $this->stat      = 1;
            $this->deviceVer = $this->dparam['app_ver'];
            if(isset($this->dparam['isUser']))
                $this->type = $this->dparam['isUser'];
            if(isset($this->dparam['webUrl']))
                $this->url = $this->dparam['webUrl'];
        }else info('ok', -1);
        //旧版本
        // else if(!empty($_REQUEST['device'])) {
            // info('ok', 1);
            // $this->stat = 0;
            // $this->deviceVer = $_REQUEST['device'];
            // if(!empty($_REQUEST['type']))
            //     $this->type      = $_REQUEST['type'];
        // }  else info('缺少参数', -1);
    }
    public function query() {$this->deviceVer(1);}
    public function up() {$this->deviceVer(2);}
    public function add() {$this->deviceVer(3);}
    public function deviceVer($status = 0) {
        $status = $status == 0 ? empty($this->dparam['status']) ? $_REQUEST['status'] : $this->dparam['status'] : $status;
        switch ($status) {
            //查库
            case 1:
                $data = DeviceverModel::query($this->stat, $this->deviceVer);
                if($this->stat == 0)
                    isset($data['type']) ? info('ok', $data['type']) : info('库里可能还没存在',-1);
                else
                    !empty($data) ? info('ok', 1, $data) : info('库里可能还没存在', -1);
                break;
            //修改
            case 2:
                DeviceverModel::up($this->deviceVer, $this->stat, $this->type, $this->url) ? info('修改成功', 1) : info('修改失败', -1);
                break;
            //添加
            case 3:
                DeviceverModel::query($this->stat, $this->deviceVer) ? info('库里已经存在', -1) : DeviceverModel::add($this->deviceVer, $this->type, $this->url, $this->stat);
                info('添加成功', 1);
                break;
        }
    }
}