<?php
require_once('htReport.php');
abstract class keepReport extends htReport{

    public $type = 'week';
    public $topsql = " select DATE_FORMAT(a.report_date,'%s') as week,a.report_date,sum(a.%s) as %s from ( %s ) a group by week ";

    public $sql = " select %s from %s %s %s ";


    public $field = " report_date,count(DISTINCT(uid)) as num ";
    public $table = " gw_uid_login_log ";
    public $where = " where uid in (%s) ";
    public $group = " GROUP BY report_date ";

    public function __construct($stime,$arr=[],$type='week'){
        parent::__construct();
        $this->type = $type;

        if(!empty($arr)){
            $this->reset($arr);
        }

        //创建时间条件
        $this->createTime($stime);

        //父语句组合
        $this->sql = sprintf($this->sql,$this->field,$this->table,$this->where,$this->group);
    }

    /**
     * [reset 分配属性]
     * @param  [type] $arr [需要重新分配的属性]
     */
    public function reset($arr){
        foreach ($arr as $k => $v) $this->$k = $v;
    }

    public function showsql(){
        echo $this->sql;die;
    }

    public function query(){
        return M()->query($this->sql,'all');
    }




    abstract function createSQL();

    /*
    生成时间段条件
     */
    public function createTime($date){

        switch ($this->type) {
            case 'week':
                $len = 7;
                break;

            case 'month':
                $len = 30;
                break;
        }

        $stime = $date;
        $etime = date('Y-m-d',strtotime("+ {$len} day",strtotime($date)));
        $this->where .= "  and report_date between '{$stime}' and '{$etime}' ";
    }
}
