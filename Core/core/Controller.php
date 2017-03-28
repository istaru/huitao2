<?php
/**
 * 抽象顶层控制器类
 */
abstract class Controller
{
    /**
     * [view 加载视图]
     */
    public function view($tpl = 'index', $value = [])
    {
        /**
         * [$suffix 读取文件后缀 如果没设置就默认后缀.php]
         */
        $suffix = !empty(C('view:VIEW_SUFFIX')) ? explode(',',C('view:VIEW_SUFFIX')) : '.php';
        extract($value);
        foreach($suffix as $k => $v){
            if(is_file(DIR_VIEW.'top'.$v))
                include DIR_VIEW.'top'.$v;
            if(is_file(DIR_VIEW.$tpl.$v))
                include DIR_VIEW.$tpl.$v;
            if(is_file(DIR_VIEW.'button'.$v))
                include DIR_VIEW.'button'.$v;
        }
    }
}