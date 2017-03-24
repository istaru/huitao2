<?php
class HtGoodsController
{
    /**
     * [queryGoods 查询全部商品以及某个商品的详情]
     */
    public function querygoods()
    {
        $c_goods_type=I($_REQUEST['goods_type'])? "AND a.source='".$_REQUEST["goods_type"]."'":"";
        $c_is_new=I($_REQUEST['is_new'])? "AND a.is_new='".$_REQUEST["is_new"]."'":"";
        $c_goods_status=I($_REQUEST['goods_status'])&$_REQUEST['goods_status']!=null ? "AND a.status='".$_REQUEST["goods_status"]."'":"";
        $c_is_sold=I($_REQUEST['is_hot']) ? "AND a.is_sold='".$_REQUEST["is_hot"]."'":"";
        $c_is_board=I($_REQUEST['is_board'])? "AND a.is_board='".$_REQUEST["is_board"]."'":"";
        $c_category_id=I($_REQUEST['catelog_id']) ? "AND a.category_id='".$_REQUEST["catelog_id"]."'":"";
        $c_is_front=I($_REQUEST['c_front'])? "AND a.is_front='".$_REQUEST["c_front"]."'":"";
        $c_sdate=isset($_REQUEST['c_sdate']) &$_REQUEST['c_sdate']!=null? "AND a.created_date>='".$_REQUEST["c_sdate"]."'":"";
        $c_edate=isset($_REQUEST['c_edate'])&$_REQUEST['c_edate']!=null ? "AND a.created_date<='".$_REQUEST["c_edate"]."'":"";
        $c_limited=" limit ".(($_REQUEST['page']-1)*50).",".(($_REQUEST['page']-1)*50+50);
        $sql='select  a.num_iid,c.name,b.title,c.name cname,a.`status`,a.click,a.purchase,a.score,a.top,a.is_front,a.created_date date
         from gw_goods_info a
         left join gw_goods_online b on a.num_iid=b.num_iid 
         left join (SELECT pid,name from gw_category group by pid,name)c on c.pid=a.category_id WHERE 1=1 '
            .$c_goods_type.$c_is_new.$c_goods_status.$c_is_sold.$c_is_board.$c_category_id.$c_is_front.$c_sdate.$c_edate;
        $sql_info=$sql.$c_limited;                                   //返回给前端的数据，这里每次写固定了，每次50条
        $data=M()->query($sql_info,'all');
        $data_sum=M()->query($sql,'all');
        $data['data']=$data;
        $data['sum']=count($data_sum);                               //返回总条数
        $data['data']?info("success",1,$data):info('failed',-4,[]);
    }
    /**
     * [deleteGoods 删除商品] 修改商品是传type=editor
     */
    public function deletegoods()
    {
         if(I('POST:type')){
             $res=M('goods_online')->where(['num_iid' => ['=',$_REQUEST['num_iid']]])->save(['title' =>$_REQUEST['title']]);
             $res ?info("修改成功",1,[]) :info("修改失败",-4,[]);
         }else{
             I('POST:id') or info('缺少唯一标示无法删除',-1);
             $data = A('HtGoods:deleteGoods',[I('id')]);
             $data ? info('删除成功',1,$data) : info('删除失败',-1);
         }


    }
    /**
     * [addGoods 添加商品]
     */
    public function addgoods()
    {
        if(!empty($_POST)) {
            $res = A('HtGoods:addGoods',[$_POST]);
            $res ? info('添加成功',1) : info('添加失败',-1);
        }
    }
}