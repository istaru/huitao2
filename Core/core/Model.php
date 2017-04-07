<?php
/**
 * Model  curd操作
 */
class Model
{
    /**
     * [$conn 数据库实例]
     */
    public static $conn = null;
    /**
     * [$obj 类实例]
     */
    public static $obj = null;
    /**
     * [$where where语句]
     */
    protected $where = '';
    /**
     * [$methods 链式操作  比如 group by -- order by-- limit]
     * 遵从sql语句使用顺序
     */
    protected $methods = [];
    /**
     * [$table 表名称]
     */
    protected static $table = '';
    /**
     * [$sqlCache sql查询记录]
     */
    private static $sqlCache = [];
    /**
     * [$filed 字段名称]
     */
    protected static $prefix = '';
    public $field = '*';
    public function __construct($table = null)
    {
        if(!self::$conn) {
            $data = $this->ckparamster();
            self::$conn = Database::pdo($data['DB_DSN'],$data['DB_USER'],$data['DB_PWD']);
            empty($data['DB_CHARSET']) OR self::$conn->exec('SET NAMES '.$data['DB_CHARSET']);
            self::$prefix = $data['DB_PREFIX'];
        }
        self::$table = self::$prefix ? self::$prefix.$table : $table;
    }
    public function ckparamster() {
        $data = C('Database');
        if(!isset($data['DB_DSN'], $data['DB_USER'], $data['DB_PWD'], $data['DB_CHARSET'], $data['DB_PREFIX']))
            throw new Exception("缺少数据库连接参数", 999);
        else
            return $data;
    }
    /**
     * @param  [type] $where [条件表达式]
     * @param  [type] $parse [预处理参数]
     * @return [type]        [description]
     */
    public function where($where = '', $parse = null)
    {
        if(is_array($where)) {
            $a = '';
            /**
             * 兼容where数组最后一个元素是逻辑与或
             */
            if(count($where) !== 1 && is_null($parse)) {
                if(isset($where[0])) {
                    $parse = end($where);
                    unset($where[0]);
                }
            }
            if(count($where)-1 === count($parse) && !is_null($parse)) {
                $i = 0;
                array_push($parse,' ');
                foreach($where as $k => $v) {
                    $v[1] = $this->formattedData($v[1]);
                    $a .= ' '.$k.$v[0].$v[1].' '.$parse[$i++];
                }
            } else {
                foreach($where as $k => $v) {
                    $v[1] = $this->formattedData($v[1]);
                    $a .= ' '.$k.$v[0].$v[1].' AND';
                }
            }
            /**
             *  此处判断如果$parse是数组就去除掉最后一个元素 否则就去除默认 AND
             */
            $parse = is_array($parse) ? rtrim($a,end($parse)) : rtrim($a,' AND ');
        } else if(is_string($where) && is_array($parse)) {
            //预处理参数不是数组 则移除func_get_args 获取到的第一个条件表达式
            if(!is_array($parse)) {
                $parse = func_get_args();
                array_shift($parse);
            }
            foreach($parse as $v) {
                $pa[] = $this->formattedData($v).' ';
            }
            empty($pa) or $parse = vsprintf($where,$pa);
        /**
         * 执行原生where语句
         */
        } else if(is_null($parse) && is_string($where)) {
            $parse = $where;
        }
        empty($parse) or $this->where = $parse ? ' WHERE '.$parse : '';
        return $this;
    }
    /**
     * [save 删除 修改数据]
     * @data  array   $data   [有值为修改]
     * @param  boolean $status [description]
     * @return [type]          [description]
     */
    public function save($data = [],$status = true)
    {
        /**
         * 如果缺少where条件 禁止修改删除数据 同时抛出一个错误
         */
        !empty($this->where) or E('缺少where条件 禁止修改删除数据');
        $sqls = '';
        /**
         * 表达式
         */
        $sql = [
            'UPDATE '.self::$table.' SET %s',
            'DELETE FROM '.self::$table,
        ];
        $field = $this->getTableFields(self::$table);
        /**
         * 修改语句
         */
        if(!empty($data) && is_array($data)) {
            foreach($data as $k => $v) {
                if(in_array($k,$field) && $v !== '' && $v !== null) {
                    $v = $this->formattedData($v);
                    $sqls .= $k.'='.$v.', ';
                }
            }
            $sqls = rtrim(sprintf($sql[0],$sqls),', ');
        /**
         * 删除语句
         */
        } else {
            $sqls = $sql[1];
        }
        $sqls = $sqls.$this->where;
        /**
         * 清空表达式
         */
        $this->unsetSql();
        /**
         * 查看sql语句 不执行库操作
         */
        if($data === false || $status === false)
            return $sqls;
        /**
         * 如果没有受影响的行 exec会返回0  在这里全等判断 只要sql语句能执行成功 都算是成功操作
         */
        $res = self::$conn->exec($sqls) === false ? false : true;
        /**
         * 如果是执行修改或删除数据操作 则清空sql查询记录
         */
        if($res)
            self::$sqlCache = [];
        return $res;
    }
    public function select($status = true) {
        /**
         * 拼接sql语句
         */
        $sql = 'SELECT '.$this->field.' FROM '.self::$table.' %s';
        /**
         * 拼接链式操作
         */
        if(!empty($this->methods)) {
            foreach($this->methods as $k => $v) {
                $this->where .= ' '.$k.' '.$v.' ';
            }
        }
        $sql = sprintf($sql,$this->where);
        /**
         * 查看本次进程中该sql记录是否存在 存在则不再需要再去查库
         */
        if(isset(self::$sqlCache[$sql])) {
            $data = self::$sqlCache[$sql];
        } else {
            if($status === false)
                return $sql;
            $data = $this->query($sql,$status);
            self::$sqlCache[$sql] = $data;
        }
        $this->unsetSql();
        return $data;
    }
    /**
     * [batchAdd 批量添加数据]
     * @param  array  $data [二维数组]
     * @return [type]       [description]
     */
    public function batchAdd($data = [])
    {
        $key = array_keys($data[0]);
        /**
         * [$key 拼接字段]
         */
        $keys = '('.implode(',', array_keys($data[0])).')';
        /**
         * [拼接预处理 :value 值]
         */
        $keyV = '';
        foreach($key as $k => $v) {
            $keyV .= ':'.$v.',';
        }

        $keyV = rtrim($keyV,',');
        $status = '';
        $rs = self::$conn->prepare("INSERT INTO ".self::$table.$keys.'VALUES('.$keyV.')');
        try {
            //$this->startTrans();
            foreach($data as $k => $v) {
                foreach($v as $key => $value) {
                    $rs->bindParam(':'.$key, $v[$key]);
                }
                $status = $rs->execute();
            }
          //  trigger_error(2);
           // $this->commit();
        } catch (Exception $e) {
            // $this->rollback();
            echo 'ss';
        }
        return $status;
    }
    /**
     * [add 添加数据]
     */
    public function add($data, $status = true)
    {
        $field = $this->getTableFields(self::$table);
        /**
         * 添加数据时 过滤掉表中不存在的字段 第一种写法不会去过滤
         */
        $key = '';
        $value = '';
        if($status === 'ignore')
            $sql = 'INSERT IGNORE INTO '.self::$table.'(%s)Values(%s)';
        else
            $sql = 'INSERT INTO '.self::$table.'(%s)Values(%s)';

        if(is_array($data)) {
            foreach($data as $k => &$v) {
                if(!in_array($k,$field) || !isset($v)){
                    unset($data[$k]);
                }else{
                    $key .= $k.',';
                    $value .= $this->formattedData($v).',';
                }
            }
            $key = rtrim($key,',');
            $value = rtrim($value,',');
            $sqls = sprintf($sql,$key,$value);
        } else {
            $sqls = sprintf($sql,$this->field,$data);
        }
        if($status)
            self::$sqlCache = [];
        return $status ? (self::$conn->exec($sqls) ? $this->getLastInsertId() : false) : $sqls;
    }
    /**
     * [field 指定查询字段 支持字段排除]
     * @field  [String,Array]  $field  [指定查询的字段]
     * @except  [false,true]    [false = 指定查询字段 true = 排除字段]
     * @return [type]
     */
    public function field($field = true, $except=false) {
        //先获取到全部字段
        $fields = $this->getTableFields(self::$table);
        if($field === true) {
          $field = $fields ? : '*';
        /**
         * $except 为true == 字段排除
         */
        } else if($except) {
          if(is_string($field))
              $field  =  explode(',',$field);
          $field = $fields ? array_diff($fields,$field) : $field;
        }
        $this->field = is_array($field) ? implode(',',$field) : $field;
        return $this;
    }
    /**
     * [getTableFields 获取表全部字段]
     * @table  [String] $table [表名称]
     * @return [array]        [表字段]
     */
    public function getTableFields() {
        if(isset(self::$table)) {
            $table_fields = [];
            $field_name = self::$conn->query('DESC '.self::$table)->fetchAll(PDO::FETCH_ASSOC);
            foreach($field_name as $k => $v)
                $table_fields[] = $v['Field'];
            return $table_fields;
        }
    }
    /**
     * [order 倒序 正序]
     * @order  [String] $order [可选 默认 id desc]
     */
    public function order($order = null)
    {
        if(is_null($order))
            $order = 'id';
        $n = array_filter(explode(',',$order));
        foreach($n as $v) {
            $a[] = explode(' ',trim($v));
        }
        $b = '';
        foreach($a as $v) {
            if(count($v) == 1)
                $v[1] = 'desc';
            foreach($v as $value) {
                $b .= ' '.$value;
            }
            $b .= ',';
        }
        $b = rtrim($b,',');
        $this->methods['ORDER BY'] = $b;
        return $this;
    }
    /**
     * [count 获取数据数]
     */
    public function count()
    {
        $sql = sprintf('SELECT count(%s) FROM %s %s',$this->field,self::$table,$this->where);
        $sum = self::$conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        if($sum !== false) {
            foreach($sum as $k => $v)
                $sum = $v["count({$this->field})"];
        }
        return $sum;
    }
    /**
     * [group 分组]
     */
    public function group($data)
    {
        $this->methods['GROUP BY'] = is_array($data) ? implode(',',$data) : $data;
        return $this;
    }
    /**
     * [limit 限制查询结果数量]
     */
    public function limit($offset, $length = null)
    {
        if(strpos($offset,',') && is_null($length))
            list($offset,$length) = explode(',',$offset);
        $this->methods['limit'] = intval($offset).($length ? ','.intval($length) : '');
        return $this;
    }
    /**
     * [page 快速分页]
     */
    public function page($start, $end = null)
    {
        if(strpos($start,',') && is_null($end))
            list($start,$end) = explode(',',$start);
        $this->methods['limit'] = (intval($start) - 1) * intval($end).','.intval($end);
        return $this;
    }
    /**
     * 给字符串转义 并且加上单引号
     */
    public function formattedData($str)
    {
        if(is_string($str)) {
            $str = addslashes(htmlspecialchars($str));
            return "'{$str}'";
        }
        return $str;
    }
    /**
     * [query 执行原始sql语句]
     */
    public function query($sql, $type='single')
    {
        $result = self::$conn->query($sql);
        $this->ErrorMsg($sql);
        if($type === 'single') {
            return $result->fetch(PDO::FETCH_ASSOC);
        }else{
            return $result->fetchAll(PDO::FETCH_ASSOC);
        }
    }


    public function exec($sql) {
        return self::$conn->exec($sql);
    }
    /**
     * [getSqlCache 查看本次进程sql查询日志]
     */
    public function getSqlCache()
    {
        return self::$sqlCache;
    }
    /**
     * [startTrans 开启事务]
     */
    public function startTrans()
    {
        self::$conn->beginTransaction();
    }
    /**
     * [commit 提交事务]
     */
    public function commit()
    {
        self::$conn->commit();
    }
    /**
     * [rollback 回滚事务]
     */
    public function rollback()
    {
       self::$conn->rollBack();
    }
    /**
     * [getLastInsID 返回最后插入的行id]
     */
    public function getLastInsertId()
    {
        return self::$conn->lastInsertId();
    }
    /**
     * [unsetSql 清空表达式和表名称]
     */
    protected function unsetSql()
    {
        $this->where = '';
        $this->methods = [];
        $this->field = '*';
    }
    /**
     * [ErrorMsg sql语句错误信息]
     */
    private function ErrorMsg($sql)
    {
        if(self::$conn->errorCode() != '00000'){
            $info = self::$conn->errorInfo ();
            echo "ErrorInfo:   </br>{$info[2]}<hr>";
            echo "ErrorSql:    </br>{$sql}</br>";
            exit;
        }
    }
}