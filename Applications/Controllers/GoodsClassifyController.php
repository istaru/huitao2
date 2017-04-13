<?php

class GoodsClassifyController {
    //存储百川 appkey,secretKey参数
    public static $params = null;
    public $name = [];
    public function __construct() {

    }
    //获取淘宝分类 入库
    public function addTaoBaoClassify() {
        $obj = new TaoBaoApiController('23630277', 'a13d3d6a8cf33d063f630f3d2b571727');
        //获取顶级分类
        // $classify = $obj->itemcatsGetRequest('', '0');
        // $classify = array ( );
        // $this->getTaoBaoClassify($classify, $obj);
    }
    //递归获取淘宝所有分类入库
    public function getTaoBaoClassify($data, $obj) {
        foreach($data as $v) {
            if(!$v['is_parent'])
                $v['is_parent'] = 0;
            $sql = "INSERT INTO ngw_classify(name , cid , parent_cid , is_parent) VALUES('{$v['name']}',{$v['cid']},{$v['parent_cid']},{$v['is_parent']})";
            M()->query($sql);
            if($v['is_parent'] == 1) {
                $classify = $obj->itemcatsGetRequest('', (string)$v['cid']);
                $this->getTaoBaoClassify($classify, $obj);
            }
        }
    }
    //返回该分类下的所有二级分类
    public function querySubClass($parent_cid = '') {
        $data = M('classify')->where(['parent_cid' => ['=', $parent_cid ? $parent_cid : $_GET['parent_cid']]])->select();
        return $parent_cid ? $data : info('ok', 1, $data);
    }
    public function queryAll() {
        $this->queryClassify(120886001);
        D($this->name);
    }
    //递归查询某一类下的所有子类
    public function queryAllSubClass($parent_cid = '120886001') {
        foreach($this->querySon($parent_cid) as $v) {
            $this->name[] = $v;
            if($v['is_parent'])
                $this->queryClassify($v['cid']);
        }
    }
    //递归删除某一分类
    public function deleteClassify($parent_cid) {
        $data = $this->querySon($parent_cid);
        foreach($data as $v) {
            M()->query("DELETE FROM ngw_classify WHERE cid={$v['cid']}");
            if($v['is_parent'])
                $this->deleteClassify($v['cid']);
        }
    }
    //删除节点分类
    public function deleteNode() {
        $params = $_REQUEST;
        //获取该节点下面所有节点类目
        $parent = M('sort')->where(['id' => ['=', $params['id']]])->select('single') or info('暂无该分类');
        $data   = M()->query("SELECT * FROM ngw_sort WHERE lft >= {$parent['lft']} AND rht <= {$parent['rht']}", 'all');
        M('sort')->where("lft >= {$parent['lft']} and rht <= {$parent['rht']}")->save();
        $sum = count($data) * 2;
        //重新整理已存在库里的左值右值
        $sql = "UPDATE ngw_sort SET `lft` = `lft` - {$sum} WHERE `lft` > ".$parent['rht'];
        M()->query($sql);
        $sql = "UPDATE ngw_sort SET `rht` = `rht` - {$sum} WHERE `rht` > ".$parent['rht'];
        M()->query($sql);
        info('删除成功', 1);
    }
    //添加顶级分类
    public function addParentNode($params) {
        $data = M('sort')->where('pid = 0')->order('id desc')->limit(1)->select('single');
        $params['lft'] = $data['rht'] + 1;
        $params['rht'] = $data['rht'] + 2;
        M('sort')->add($params);
        info('添加成功', 1);
    }
    //添加子分类
    public function addChildNode() {
        $params = $_REQUEST;
        !empty($params['name']) or info('缺少参数');
        //当为空的时候表示添加顶级节点
        !empty($params['pid']) or $this->addParentNode($params);
        try {
            M()->startTrans();
            $data =  M('sort')->where(['id' => ['=', $params['pid']]])->select('single') or E('没有该父级分类');
            // 把左值和右值大于父节点左值的节点的左右值加上2
            $sql = 'UPDATE ngw_sort SET `lft` = `lft` + 2 WHERE `lft` >= '.$data['rht'];
            M()->query($sql);
            $sql = 'UPDATE ngw_sort SET `rht` = `rht` + 2 WHERE `rht` >= '.$data['rht'];
            M()->query($sql);
            //当前节点的左值是父节点的右值 而右值则是+1
            $params['lft'] = $data['rht'];
            $params['rht'] = $data['rht'] + 1;
            M('sort')->add($params);
        } catch(Exception $e) {
            M()->rollback();
            info($e->getMessage(),-1);
        }
        M()->commit();
        info('添加成功',1);
    }
}