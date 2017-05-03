#!/bin/bash
#获取订单
for((i = 0; i < 2; i++))
do
    curl http://es3.laizhuan.com/shopping_new/TaoBaoKe/run
	sleep 30
done
