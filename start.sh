#!/bin/bash
ps -aux | grep "php server.php" | cut -c 9-15 | xargs kill -9 php server.php