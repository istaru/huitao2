<?php
/**
 * 后台公共控制器
 */
class HtController extends Controller
{

    public static $pageSize = 10;
    /**
     * [__construct 验证用户权限 验证用户是否登录]
     */
    public function __construct()
    {
        /**
         * 获取当前节点
         */
       preg_match('/^(\/\w*){2}/', $_SERVER['PATH_INFO'], $matches);
//      preg_match('/^(\/\w*){2}/', substr($_SERVER['REQUEST_URI'],9), $matches);
//        preg_match('/^(\/\w*){2}/', substr($_SERVER['SCRIPT_NAME'],9), $matches);
        $cf = !empty($matches) ? $matches[0] : '';
//        D($cf);
        /**
         * 验证用户是否拥有当前节点的操作权限
         */
        if($cf != '/HtUser/dologin'&&$cf != '/HtPowerController/inipower') {
            if(!empty($_SESSION['user']))
                $id = $_SESSION['user']['id'];
            else
                info('您还未登录',-3);
            $this->checkPower($id, $cf) or info('您没有该权限', -2);
        }
    }
    /**
     * [checkPower 用户权限验证 获取]
     */
    public function checkPower($role,$cf)
    {
        $nodeId = implode(',',array_column((M('htrole')->field('htNode_id')->where('htUser_id = %d',[intval($role)])->select()),"htNode_id"));
        /**
         *
         * 获取用户所拥有的节点名称
         */
        $node = M('htnode')->field('node')->where("id in({$nodeId})")->select();
        /**
         * 返回用户是否有权限操作当前节点
         */
        foreach ($node as $k => $v){
            foreach ($v as $k1 => $v2){
               if($v2==$cf){
                   return $v2;
               }
            }
        }
        return false;

    }
}