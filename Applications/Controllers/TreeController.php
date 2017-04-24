<?php
class TreeController
{
    public $param = NULL;
    public function __construct()
    {
        $this->param = $_REQUEST;
    }

    /**
     * [addnode 增加节点]
     */
    public function addnode()
    {
        $type = !empty($this->param['type']) ? $this->param['type'] : 0;
        if(empty($this->param['pid'])) info('参数不全',-1);
        //检查父节点
        if(!empty($this->param['pid'])){
            $sql = "SELECT * FROM ngw_category WHERE id = '{$this->param['pid']}'";
            $p_info = M()->query($sql,'single');
        }else{//模拟需要用到的父节点值
            $p_info = ['id'=>0,'depth'=>0,'right'=>1,'name'=>null,'type'=>$type];
        }

        M()->startTrans();
        try {
            if(!empty($this->param['pid'])){
                $sql = "UPDATE ngw_category SET `right` = `right`+2 WHERE `right` >= {$p_info['right']} AND 'type' = {$p_info['type']}";
                M()->query($sql);

                $sql = "UPDATE ngw_category SET `left` = `left`+2 WHERE `left` >= {$p_info['right']} AND 'type' = {$p_info['type']}";
                M()->query($sql);
            }
            $sql = "INSERT INTO ngw_category (`pid`,`pname`,`name`,`depth`,`left`,`right`,type) VALUES ({$p_info['id']},'{$p_info['name']}','{$this->param['node']}',{$p_info['depth']}+1,{$p_info['right']},{$p_info['right']}+1,{$p_info['type']})";
            M()->query($sql);

        } catch (Exception $e) {
            M()->rollback();
            echo 'fail';die;
        }
        M()->commit();
        echo 'ok';
    }

    /**
     * [delnode 删除节点]
     */
    public function delnode()
    {
        if(empty($this->param['id'])) info('参数不全',-1);
        $sql = "SELECT * FROM ngw_category WHERE `id` = '{$this->param['id']}'";
        $node = M()->query($sql,'single');

        $sql = "SELECT count(id) count FROM ngw_category WHERE `left` >= {$node['left']} AND `right` <= {$node['right']}  AND 'type' = {$p_info['type']}";
        $count = M()->query($sql);

        $count = $count['count']*2;

        M()->startTrans();
        try {
            $sql = "DELETE FROM ngw_category WHERE `left` >= {$node['left']} AND `right` <= {$node['right']}  AND 'type' = {$p_info['type']}";
            M()->query($sql);

            $sql = "UPDATE ngw_category SET `left` = `left`-{$count}, `right` = `right`-{$count} WHERE `left` > {$node['left']}  AND 'type' = {$p_info['type']}";
            M()->query($sql);

            $sql = "UPDATE ngw_category SET `right` = `right`-{$count} WHERE `right` > {$node['right']} AND `left` < {$node['left']}  AND 'type' = {$p_info['type']}";
            M()->query($sql);
        } catch (Exception $e) {
            M()->rollback();
            echo 'fail';die;
        }
        M()->commit();
        echo 'ok';
    }

    /**
     * [getpnode 查询上层节点]
     */
    public function getpnode()
    {
        if(empty($this->param['pid'])) info('参数不全',-1);
        $sql = "SELECT * FROM ngw_category WHERE `id` = {$this->param['id']}";
        $node = M()->query($sql);
        $sql = "SELECT `id`,`pid`,`name` node,`depth` dep FROM ngw_category WHERE `left` < {$node['left']} AND `right` > {$node['right']} ORDER BY depth ASC";
        $pnodes = M()->query($sql,'all');
        info($pnodes);
    }

    /**
     * [getsnode 查询下层节点]
     */
    public function getsnode()
    {
        if(empty($this->param['id'])) info('参数不全',-1);
        $sql = "SELECT * FROM ngw_category WHERE `id` = {$this->param['id']}";
        $node = M()->query($sql);
        $sql = "SELECT `id`,`pid`,`name` node,`depth` dep FROM ngw_category WHERE `left` > {$node['left']} AND `right` < {$node['right']}  AND 'type' = {$p_info['type']} ORDER BY depth ASC";
        $snodes = M()->query($sql,'all');
        info($snodes);
    }

    /**
     * [shownode 展示节点树]
     */
    public function shownode()
    {
        // $sql = "SELECT `id` AS `key`,`pid` AS `parent`,`node` AS `name`,`lft`,`rgt`,dep FROM ngw_tree";
        $sql = "SELECT `id`,`pid`,`name` node FROM ngw_category WHERE 'type' = {$p_info['type']}";

        $list = M()->query($sql,'all');
        $list = $this->showtree($list);
        info($list);
    }


    private function showtree($list,$root=0)
    {
        foreach ($list as  $v){
            $v['son'] = [];
            $data[$v['id']] = $v;
        }
        foreach ($data as $k => $v){
            if($v['pid'] == $root)
                $tree[] = &$data[$k];
            else
                $data[$v['pid']]['son'][] = &$data[$k];
        }
        return $tree;
    }





}









