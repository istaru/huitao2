<?php
class HtGoodsModel
{
    public function queryGoods($data = [])
    {
        /**
         * 查询单个商品详情
         */
        if(isset($data['id'])) {
            return M('goods_online')->field('pict_url,small_images',true)->where(['num_iid' => ['=',$data['id']]])->select();
        /**
         * 查询全部商品
         */
        } else {
            /**
             * [$page 如果没分页参数 则默认第一页]
             */
            $page = !empty($data['page']) ? $data['page'] : 1;
            /**
             * 查询每页的商品数量
             */
            $data['data'] = M('goods_online')->field('pict_url,small_images',true)->page($page,50)->select();
            /**
             * 查询商品数量
             */
            $data['sum']  = M('goods_online')->field('id')->count();
            return $data;
        }
    }
    public function addGoods($data)
    {
        if(is_array($data))
            return M('goods_online')->add($data);
    }
}