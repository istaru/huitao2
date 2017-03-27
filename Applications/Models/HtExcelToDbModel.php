<?php

class HtExcelToDbModel
{
    //添加商品信息
    public function addGoodsbyexcle($data)
    {
        if (is_array($data)) {
            return M('goods')->batchAdd($data);
        }
    }

    //添加优惠券
    public function addcouponbyexcle($data)
    {
        if (is_array($data)) {
            return M('goods_coupon')->batchAdd($data);
        }
    }
}