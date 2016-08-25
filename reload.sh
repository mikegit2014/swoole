#! /bin/sh

### BEGIN INIT INFO

# Provides:          mail_server

# Required-Start:    $remote_fs $network

# Required-Stop:     $remote_fs $network

# Default-Start:     2 3 4 5

# Default-Stop:      0 1 6

# Short-Description: starts mail_server

# Description:       starts the PHP FastCGI Process Manager daemon

### END INIT INFO

#php路径，如不知道在哪，可以用whereis php尝试

PHP_BIN=/usr/local/php/bin/php

#代码根目录

SERVER_PATH=/home/shenruiqing/php-shell/one

#task flag
T_FLAG=msgTask

getMasterPid()

{

    PID=`ps axu|grep $T_FLAG|grep master|awk '{print $2}'`

    echo $PID

}

getManagerPid()

{

    MID=`ps axu|grep $T_FLAG|grep manager|awk '{print $2}'`

    echo $MID

}

case "$1" in

    start)

            PID=`getMasterPid`

            if [ -n "$PID" ]; then

                echo -n "mail server is running"

                exit 1

            fi

            echo -n "Starting mail server "

            ####下面一行修改为你的php运行脚本#########

            $PHP_BIN $SERVER_PATH/AdminMsgTaskServer.php 

            echo " done"

    ;;

    stop)

            PID=`getMasterPid`

            if [ -z "$PID" ]; then

                echo -n "mail server is not running"

                exit 1

            fi

            echo -n "Gracefully shutting down mail server "

            kill $PID

            echo " done"

    ;;

    status)

            PID=`getMasterPid`

            if [ -n "$PID" ]; then

                echo -n "mail server is running"

            else

                echo -n "mail server is not running"

            fi

    ;;

    force-quit)

            $0 stop

    ;;

    restart)

            $0 stop

            $0 start

    ;;

    reload)

            MID=`getManagerPid`

            if [ -z "$MID" ]; then

                echo -n "mail server is not running"

                exit 1

            fi

            echo -n "Reload service mail_server "

            kill -USR1 $MID

            echo " done"

    ;;

    reloadtask)

            MID=`getManagerPid`

            if [ -z "$MID" ]; then

                echo -n "mail server is not running"

                exit 1

            fi

            echo -n "Reload service mail_server"

            kill -USR2 $MID

            echo " done"

    ;;

    *)

           echo "Usage: $0 {start|stop|force-quit|restart|reload|reloadtask|status}"

            exit 1

    ;;

esac

