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
        //检查父节点
        if(!empty($this->param['pid'])){
            $sql = "SELECT * FROM gw_tree WHERE id = '{$this->param['pid']}'";
            $p_info = M()->query($sql,'single');
        }else{//模拟需要用到的父节点值
            $p_info = ['id'=>0,'dep'=>0,'rgt'=>1,'node'=>''];
        }

        M()->startTrans();
        try {
            if(!empty($this->param['pid'])){
                $sql = "UPDATE gw_tree SET rgt = rgt+2 WHERE rgt >= {$p_info['rgt']}";
                M()->query($sql);

                $sql = "UPDATE gw_tree SET lft = lft+2 WHERE lft >= {$p_info['rgt']}";
                M()->query($sql);
            }
            $sql = "INSERT INTO gw_tree (pid,pnode,node,dep,lft,rgt) VALUES ({$p_info['id']},'{$p_info['node']}','{$this->param['node']}',{$p_info['dep']}+1,{$p_info['rgt']},{$p_info['rgt']}+1)";
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
        $sql = "SELECT * FROM gw_tree WHERE id = '{$this->param['id']}'";
        $node = M()->query($sql,'single');

        $sql = "SELECT count(id) count FROM gw_tree WHERE lft >= {$node['lft']} AND rgt <= {$node['rgt']}";
        $count = M()->query($sql);

        $count = $count['count']*2;

        M()->startTrans();
        try {
            $sql = "DELETE FROM gw_tree WHERE lft >= {$node['lft']} AND rgt <= {$node['rgt']}";
            M()->query($sql);

            $sql = "UPDATE gw_tree SET lft = lft-{$count}, rgt = rgt-{$count} WHERE lft > {$node['lft']}";
            M()->query($sql);

            $sql = "UPDATE gw_tree SET rgt = rgt-{$count} WHERE rgt > {$node['rgt']} and lft < {$node['lft']}";
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
        $sql = "SELECT * FROM gw_tree WHERE id = {$this->param['id']}";
        $node = M()->query($sql);
        $sql = "SELECT id,pid,node,dep FROM gw_tree WHERE lft < {$node['lft']} AND rgt > {$node['rgt']} ORDER BY dep ASC";
        $pnodes = M()->query($sql,'all');
        info($pnodes);
    }

    /**
     * [getsnode 查询下层节点]
     */
    public function getsnode()
    {
        $sql = "SELECT * FROM gw_tree WHERE id = {$this->param['id']}";
        $node = M()->query($sql);
        $sql = "SELECT id,pid,node,dep FROM gw_tree WHERE lft > {$node['lft']} AND rgt < {$node['rgt']} ORDER BY dep ASC";
        $snodes = M()->query($sql,'all');
        info($snodes);
    }

    /**
     * [shownode 展示节点树]
     */
    public function shownode()
    {
        // $sql = "SELECT `id` AS `key`,`pid` AS `parent`,`node` AS `name`,`lft`,`rgt`,dep FROM gw_tree";
        $sql = "SELECT id,pid,node FROM gw_tree";

        $list = M()->query($sql,'all');
        $list = $this->showtree($list);
        // D($list);die;
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



    // public function movenode($this->param)
    // {
    //  //查询父节点
    //  $sql = "SELECT * FROM gw_tree WHERE id = {$this->param['pid']}";
    //  $pnode = M()->query($sql);

    //  //查自己
    //  $sql = "SELECT * FROM gw_tree WHERE id = {$this->param['id']}";
    //  $node = M()->query($sql);

    //  //查自己及子节点id
    //  $sql = "SELECT id  FROM gw_tree WHERE lft >= {$node['lft']} AND rgt <= {$node['rgt']}";
    //  $ids = M()->query($sql,'all');

    //  //计算位移数量
    //  $count = count($ids)*2;

    //  //生成需要位移的所有节点id
    //  $ids = implode(',',array_column($ids,'id'));

    //  M()->startTrans();
    //  try {

    //      //目标子节点深度值更新(移动节点的所有 子节点深度+ 目标节点移动的深度-目标节点原深度)
    //      $sql = "UPDATE gw_tree SET dep = dep+{$pnode['dep']}+1-{$node['dep']} WHERE lft > {$node['lft']} and rgt < {$node['rgt']}";
    //      M()->query($sql);

    //      //节点右移
    //      if($node['rgt'] < $pnode['rgt']){
    //          echo 1;

    //          $arr2 = range($node['rgt']+1,$pnode['rgt']-1,1);
    //          $arr = implode(',',range($node['rgt']+1,$pnode['rgt']-1,1));
    //          $arr = M()->query("select id,lft,rgt from gw_tree where lft in ({$arr}) or rgt in ({$arr})",'all');
    //          $sql = $this->createsql($arr,$arr2,$count,1);
    //          M()->query($sql);
    //          //目标节点及所有子节点偏移数 = 落点父右值-目标点原右值
    //          $count2 = $pnode['rgt']-$count-$node['lft'];
    //          // echo $count2;die;
    //          $sql = "UPDATE gw_tree SET lft = lft+{$count2},rgt = rgt+{$count2} WHERE (id IN ({$ids}))";
    //          M()->query($sql);


    //      }//节点左移
    //      else{

    //          echo '2';

    //          $arr2 = range($pnode['lft']+1,$node['rgt'],1);
    //          $arr = implode(',',range($node['rgt']+1,$pnode['rgt']-1,1));
    //          $arr = M()->query("select id,lft,rgt from gw_tree where lft in ({$arr}) or rgt in ({$arr})",'all');
    //          $sql = $this->createsql($arr,$arr2,$count,2);
    //          M()->query($sql);
    //          //目标节点及所有子节点偏移数
    //          $count2 = $node['lft']-$pnode['lft']-1;
    //          $sql = "UPDATE gw_tree SET lft = lft-{$count}-{$count2},rgt = rgt-{$count}-{$count2} WHERE (id IN ({$ids})) ";
    //          M()->query($sql);

    //      }
    //      //更新目标点深度
    //      $sql = "UPDATE gw_tree SET pid = {$pnode['id']},pnode = '{$pnode['node']}',dep = {$pnode['dep']}+1 WHERE id = {$node['id']}";
    //      M()->query($sql);

    //  } catch (Exception $e) {
    //      M()->rollback();
    //      echo 'fail';die;
    //  }
    //  M()->commit();
    //  echo 'ok';
    // }




    // public function createsql($arr,$arr2,$num,$type)
    // {
    //  // D($arr);
    //  // D($arr2);
    //  foreach ($arr as $k => &$v) {
    //      foreach ($v as $kk => &$vv) {
    //          if(in_array($vv,$arr2)&&$kk!='id') $vv = $vv+$num*($type==1?-1:1);
    //      }
    //  }

    //  $sql = "UPDATE gw_tree SET lft = CASE %s ELSE lft END,rgt = CASE %s  ELSE rgt END";
    //  $lsql = '';
    //  $rsql = '';
    //  D($arr);
    //  foreach ($arr as $key => $val) {
    //      $lsql .= " WHEN id = {$val['id']} THEN {$val['lft']} ";
    //      $rsql .= " WHEN id = {$val['id']} THEN {$val['rgt']} ";
    //  }

    //  $sql = sprintf($sql,$lsql,$rsql);
    //  // echo $sql;die;
    //  return $sql;
    // }


}









