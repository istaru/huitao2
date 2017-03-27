<?php
/**
 * 收入日志
 */
class IncomeLogModel
{
    public function addIncomeLog($data)
    {
        if(empty($data))
            info('数据有误',-1);

        return M('income_log')->add($data);
    }
}