#!/bin/bash
#自启动redis
for((i = 0; i < 6; i++))
do
    ps=`ps -ax|grep redis|grep -v $0|grep -v grep|wc -l`
    if [ $ps -eq 0 ]
        then
            /usr/local/redis-3.0.2/bin/redis-server
    fi
    sleep 10
done
