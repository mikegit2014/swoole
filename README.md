# swoole
`swoole异步任务`
使用swoole和极光推送，向app推送消息
client:
AdminMsgTaskClient.php
server:
AdminMsgTaskServer.php
方法：
1.使用redis存储app端用户信息。
2.swoole从redis读取用户，向用户推送消息,并使用redis存储极光推送的消息id
3.swoole server执行完成后，通知服务器，把极光消息id,入库

