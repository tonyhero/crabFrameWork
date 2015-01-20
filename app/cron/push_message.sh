#!/bin/bash

fpid=`ps ax|grep push_message|grep -v grep|awk '{print $1}'`
if [ "$fpid" != "" ]; then
    kill $fpid
fi

php /home/push_message.php >> /home/log/mobile_message_send/push_message.log &

