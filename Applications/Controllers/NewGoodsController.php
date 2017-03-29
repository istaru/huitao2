<?php
/**
 * 测试类
 */
class NewGoodsController extends AppController
{
    public function __construct(){

       /*if(is_file(DIR_COMMON.'gwcore/NewGoods'.EXT)){

            require_once DIR_COMMON.'gwcore/GoodsModule'.EXT;

            require_once DIR_COMMON.'gwcore/NewGoods'.EXT;

            require_once DIR_COMMON.'gwcore/FilterScore'.EXT;

            require_once DIR_COMMON.'gwcore/GoodsPdo'.EXT;

            require_once DIR_COMMON.'gwcore/Score'.EXT;
        }*/
        //必须预先载入
        //include_all_php(DIR_COMMON.'gwcore');
    }
    /*
    function __autoload($class){
        $path = DIR_COMMON.'gwcore/';
        $file = $path.$class . '.php';
        if (is_file($file)) {echo $file;
            require_once($file);
        }
    }*/

    public function index()
    {
        $m = new Module("goods");
        //$m->load_module("goods");
          
        /*$s = new Score();
        $s->addPurchaseRate();exit;
       */
       // $t = new Tools();exit;
        //$t = new TransactionTools();exit;
    //算评分
        $f = new FavoriteGoods();
        $r = $f->getGoodsData(3);
        echo $r->msg;


        //$f->savaGoodsData();
     /*   $goods_info = array(
            "category_id"=>1,"rating"=>25.3,"price"=>7.0,
            "limited"=>5,"reduce"=>3,"store_type"=>1,
            "volume"=>9000,"post_fee"=>0,"top",
        );
        $f->index(2,$goods_info);
    */
        //$g = new NewGoods();
        //$g->inputGoods();


        //----------------测试Model层调用----------------
        //大A方法 可相当于 new XXModel
        //D(A('Test'));
        // 也可以直接利用大A函数执行自定义模型类的某个方法 第二个参数为一维数组 表示往这个方法里面传递的参数 不传递参数可以不写第二个参数
        // D(A('Test:index',['张三', '李四']));
        // 但是不建议这样使用 因为每次都会new一下这个对象之后才会调用这个方法  只能说声合理的去运用该方法吧！！！
        // D(A('Test:index'));
        // ----------------测试curd操作----------------
        //修改数据
        // D(M('user')->where('id=1')->save($arr,false));
        // select save add方法 第一个参数设为false可查看sql语句
        // 测试快速分页
        // D(M('one')->page(1,20)->select(false));
        // 测试where条件调用
        // D(M('user')->where(['id' => ['=',2],'name' => ['=','王亚辉']],['OR'])->limit(1,1)->select(false));
        // D(M('user')->where("id=%d and name=%s",[1,'王亚辉'])->limit(2,3)->select(false));
        // 测试order 排序
        // D(M('user')->order('id')->select(false));
        // 测试add方法添加数据
        // D(M('user')->add(['username' => 2, 'password' => 3, 'phone' => 'iphone'],false));
        // 测试分组 以及倒序正序 连贯操作
        // D(M('test')->field('max(id)')->group('id')->order('id')->select(false));
        // 测试删除操作
        // D(M('user')->where(['id' => ['=',1],'name' => ['=','王亚辉']],['OR'])->save(false));
        // 测试查询全部数据
        // D(M('user')->select(false));
        // 测试批量添加数据  数据格式(二维数组) $a = [['name' => '王亚辉', 'sex' => '男'],['name' => '张三', 'sex' => '男']]
        // D(M('user')->batchAdd($a))
        // 测试count用法 select count(id) from user
        // D(M('user')->field('id')->count());
        // 测试sql语句执行 大M方法可以不传递参数
        // D(M()->query('select * from user'));
        // 开启事务
        // M()->startTrans();
        // 提交事务
        // M()->commit();
        //回滚事务
        // M()->rollback();
        // ----------------测试视图类调用----------------
        //$this->view('index', ['name' => '张三']); //也可以不传递第二个参数
        // ----------------测试公共函数库调用----------------
        //获取所有配置
        // D(C());
        //获取某一个配置值
        // D(C('DB_TYPE'));
        // 测试大M方法来获取到模型类实例
        // D(M('user')->field('id',true)->select());
        // 通过大M方法来更改表前缀 只对本次操作有效
        // $a = M('test','jp_');
        // D($a->select(false));
        // 测试get_curl方法
        // $v = ['name' => 1];
        // D(get_curl('http://localhost:8080/HuiTao/test/a', $v)); //POST方式 不带$v GET方式
    }

}
