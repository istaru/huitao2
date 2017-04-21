<?php
ini_set('memory_limit', '-1');
class HtGoodsController
{
    /**
     * 解析post参数--仅限查询使用
     */
    public static function explain($name=''){
       return isset($_REQUEST[$name])&$_REQUEST[$name]!=''? "AND a.$name='".$_REQUEST[$name]."'":"";
    }
    /**
     * [queryGoods 查询全部商品以及某个商品的详情]
     */
    public function querygoods()
    {
        /**
         * kind必传项，kind传1表示查询商品编辑，传2表示查询商品详情
         */
        if($_REQUEST['kind']==1){
            //如果传入了keywords，那么就是按关键字搜索，否则按条件搜索
            if(isset($_REQUEST['keyword'])&$_REQUEST['keyword']!=''&$_REQUEST['keyword']!=null){
                //排序规则
                if(I('o_para')&&I('order')){
                    $addtion=" ORDER BY a.".$_REQUEST['o_para']." ".$_REQUEST['order'];
                }else{
                    $addtion="";
                }
                $sql="select  a.num_iid,c.name,b.title,a.source,c.name cname,a.`status`,a.click,a.purchase,a.score,a.top,a.is_front,b.created_date date
          from ngw_goods_info a
         left join ngw_goods_online b on a.num_iid=b.num_iid
         left join (SELECT id,name from ngw_category group by id,name)c on c.id=a.category_id
         WHERE b.title like '%".$_REQUEST['keyword']."%'  or b.num_iid like '%".$_REQUEST['keyword']."%'".$addtion;
                $res=M()->query($sql,'all');
                $data['data']=$res;
                $data['sum']='1';
                $data['data']?info("success",1,$data):info('failed',-4,[]);
            }
            else{
                //排序规则,因为是联表查询，所以来自不同表的排序字段也要做区分
                if(I('o_para')&&I('order')){
                    $addtion=" ORDER BY a.".$_REQUEST['o_para']." ".$_REQUEST['order'];
                }else{
                    $addtion="";
                }
                $c_goods_type=self::explain('source');
                $c_is_new=self::explain('is_new');
                $c_goods_status=I('status')? "AND a.status='".$_REQUEST["status"]."'":"";
                $c_is_sold=self::explain('is_sold');
                $c_is_board=self::explain('is_board');
                $c_category_id=self::explain('category_id');
                $c_is_front=self::explain('is_front');
                $c_sdate=I('c_sdate')? "AND a.created_date>='".$_REQUEST["c_sdate"]."'":"";
                $c_edate=I('c_edate')? "AND a.created_date<='".$_REQUEST["c_edate"]."'":"";
                $c_limited=" limit ".(($_REQUEST['page']-1)*50).",50";
                $sql='select  a.num_iid,c.name,b.title,a.source,c.name cname,a.`status`,a.click,a.purchase,a.score,a.top,a.is_front,a.created_date date
          from ngw_goods_info a
         left join ngw_goods_online b on a.num_iid=b.num_iid
         left join (SELECT id,name from ngw_category group by id,name)c on c.id=a.category_id WHERE 1=1 '
                    .$c_goods_type.$c_is_new.$c_goods_status.$c_is_sold.$c_is_board.$c_category_id.$c_is_front.$c_sdate.$c_edate.$addtion;
                $sql_info=$sql.$c_limited;                                   //返回给前端的数据，这里每次写固定了，每次50条
//        D($sql_info);
                $res=M()->query($sql_info,'all');
                $data_sum=M()->query($sql,'all');
                $data['data']=$res;
                $data['sum']=count($data_sum);                               //返回总条数
                $data['data']?info("success",1,$data):info('failed',-4,[]);
            }

        }else{
            $c_addtion=I('order')?" ORDER BY ".$_REQUEST['o_para']." ".$_REQUEST['order']:"";
            //如果传入了keywords，那么就是按关键字搜索，否则按条件搜索
            if(isset($_REQUEST['keyword'])&$_REQUEST['keyword']!=''&$_REQUEST['keyword']!=null){
                $sql="select num_iid,source,title,status,price,deal_price,category,item_url,rating,created_date from ngw_goods_online
         WHERE title like '%".$_REQUEST['keyword']."%'  or num_iid like '%".$_REQUEST['keyword']."%'".$c_addtion;
                $res=M()->query($sql,'all');
                $data['data']=$res;
                $data['sum']='1';   //关键字查询不分页
                $data['data']?info("success",1,$data):info('failed',-4,[]);
            }
            else{
                //排序规则,因为是联表查询，所以来自不同表的排序字段也要做区分
                $c_goods_type=I('source')!=''? "AND source='".$_REQUEST["source"]."'":"";
                $c_goods_status=I('status')? "AND status='".$_REQUEST["status"]."'":"";
                $c_category_id=I('category_id')? "AND category_id='".$_REQUEST["category_id"]."'":"";
                $c_sdate=I('c_sdate')? "AND created_date>='".$_REQUEST["c_sdate"]."'":"";
                $c_edate=I('c_edate')? "AND created_date<='".$_REQUEST["c_edate"]."'":"";
                $c_limited=" limit ".(($_REQUEST['page']-1)*50).",50";
                $sql='select num_iid,source,title,status,price,deal_price,category,item_url,rating,created_date from ngw_goods_online WHERE 1=1 '
                    .$c_goods_type.$c_goods_status.$c_category_id.$c_sdate.$c_edate;
                $sql_info=$sql.$c_addtion.$c_limited;
                //返回给前端的数据，这里每次写固定了，每次50条
//                D($sql_info);
                $res=M()->query($sql_info,'all');
                $data_sum=M()->query($sql,'all');
                $data['data']=$res;
                $data['sum']=count($data_sum);                               //返回总条数
                $data['data']?info("success",1,$data):info('failed',-4,[]);
            }


        }

    }
    /**
     * [deleteGoods 删除修改商品] 修改商品是传type=editor
     */
    public function deletegoods()
    {
         if(I('POST:type')){
             $sql="update ngw_goods_info a,ngw_goods_online b set b.title='".$_REQUEST['title']."',a.top='".$_REQUEST['top']
                   ."',a.score='".$_REQUEST['score']."',a.is_front='".$_REQUEST['is_front']."' where a.num_iid='".$_REQUEST['num_iid']."' and b.num_iid='".$_REQUEST['num_iid']."'";
//             D($sql);
             $res=M()->exec($sql);
             $res ?info("修改成功",1,[]) :info("修改失败",-4,[]);
         }else{
             I('POST:num_iid') or info('缺少唯一标示无法删除',-1);
             $sql="delete from ngw_goods_online where num_iid=".$_REQUEST['num_iid'];
             $res=M()->exec($sql);
             $sql2="delete from ngw_goods_info where num_iid=".$_REQUEST['num_iid'];
             $res2=M()->exec($sql2);
             $res|$res2 ? info('删除成功',1,[]) : info('删除失败',-1);
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

    public function updategooodsstatus(){
        if(isset($_REQUEST['arr'])){
            //上架
           if($_REQUEST['type']==1){
               $colum=implode(',',$_REQUEST['arr']);
               $sql="update ngw_goods_online a,ngw_goods_info b set a.status=1,b.status=1 where a.num_iid IN (".$colum.") and b.num_iid IN (".$colum.")";
               $res=M()->exec($sql);
               $res?info("操作成功","1",[]):info("操作失败","1",[]);

           }
           //手工下架
           else if($_REQUEST['type']==2){
               $colum=implode(',',$_REQUEST['arr']);
               $sql="update ngw_goods_online a,ngw_goods_info b set a.status=3,b.status=5 where a.num_iid IN (".$colum.") and b.num_iid IN (".$colum.")";
               $res=M()->exec($sql);
               $res?info("操作成功","1",[]):info("操作失败","1",[]);
           }
           //上架不显示
           else{
               $colum=implode(',',$_REQUEST['arr']);
               $sql="update ngw_goods_online a,ngw_goods_info b set a.status=3,b.status=3 where a.num_iid IN (".$colum.") and b.num_iid IN (".$colum.")";
               $res=M()->exec($sql);
               $res?info("操作成功","1",[]):info("操作失败","1",[]);
           }
        }
}




}