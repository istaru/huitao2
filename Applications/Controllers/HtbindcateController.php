<?php
class HtbindcateController extends Controller
{
    public $res_arr=[];
    public  function getcategory(){
        /**
         * type值为fav表示选品库分类，值为huitao表示惠淘分类，不传则表示查询所有，传type的时候，一定传keyword
         */
        if(isset($_REQUEST["type"])&&!empty($_REQUEST["type"])){
            if($_REQUEST["type"]=='fav'){
                $sql="select favorite_id,favorite_name,category_id,category_name from ngw_category_favorite_ref  where favorite_name like '%".addslashes($_REQUEST['keyword'])."%'";
                $res_arr['fav']=M()->query($sql,'all');
                $res_arr['fav']?info('ok',10,$res_arr):info("选品库暂无数据",-3);
            }else{
                $sql="select pid,name from ngw_category where name like '%".addslashes($_REQUEST['keyword'])."%' and name is not null GROUP BY name,pid ORDER BY pid ASC";
                $res_arr['huitao']=M()->query($sql,'all');
                $res_arr['huitao']?info('ok',20,$res_arr):info("分类库暂无数据",-4);
            }
        }
        /**
         * 查询所有的选品库分类，包括已绑定，未绑定的，以及惠淘自定义分类
         */
        else{
            $sql1='select pid,name from ngw_category where name is not null GROUP BY name,pid ORDER BY pid ASC';
            $sql2='select favorite_id,favorite_name,category_id,category_name from ngw_category_favorite_ref';
            $res_arr['huitao']=M()->query($sql1,'all');
            $res_arr['fav']=M()->query($sql2,'all');
            info('ok',1,$res_arr);
        }
        info("暂无数据",-5);
    }
    //将选品库分类绑定到惠淘自定义分类上
    public function bindcategory(){
        if(isset($_REQUEST['fid'])&&isset($_REQUEST['pid'])){
            $sql="update ngw_category_favorite_ref set category_id='".$_REQUEST['pid']."',category_name=(select DISTINCT(name) from ngw_category where pid='".$_REQUEST['pid']."') where favorite_id=".$_REQUEST['fid'];
            $res=M()->exec($sql);
            $res?info("映射成功",'1'):info("绑定失败或已建立映射关系",-1);
        }
        info("参数不全",-2);
    }
    //解决绑定
    public function unbinded(){
        if(isset($_REQUEST['fid'])){
            $sql="update ngw_category_favorite_ref set category_id=null,category_name=null where favorite_id=".$_REQUEST['fid'];
            $res=M()->exec($sql);
            $res?info("解绑成功",'1'):info("未绑定或解绑失败",-1);
        }
        info("参数不全",-2);
    }

    /**
     * 加载分类列表
     */
    public function getcatitem(){
        $sql1='select pid,name from ngw_category where name is not null GROUP BY name,pid ORDER BY pid ASC';
        $data=M()->query($sql1,'all');
        info('success',1,$data);
    }

}