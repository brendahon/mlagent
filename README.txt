1. Link your business page with Nexmo account - https://developer.nexmo.com/messages/concepts/facebook
2. Navigate to dashboard.nexmo.com and create a new application under "Messages and Dipatch" section.
3. You will get an application id and private key from step 2
4. modify sendfb.php and insert the application id and path to private key
5. modify config.php
5.1 COMMS_ROUTER - If you do not want to setup your own comms-router for routing you can use our hosted instance. Modify this if you setup your own instance
5.2 CALLBACK_URL - This path should point to agent-webhook.php included in this package
6. modify js/index.js and look for sendfb() method and insert your page id for the "from" parameter
7. make sure logs and agent folders are writable by the user running webserver. Recommended to transfer ownership of all files and folders to the user running webserver.


If you want to setup your own comms-router
1. follow instructions here to install comms-router
2. create a router
curl -X PUT http://localhost:8080/comms-router-web/api/routers/mlrouter
3. create queues
curl -X PUT http://localhost:8080/comms-router-web/api/routers/mlrouter/queues/messengerqueue \
  -H 'Content-Type:application/json' \
  -d$'{"predicate":"HAS(#{channel},\'messenger\')"}}'

 
curl -X PUT http://localhost:8080/comms-router-web/api/routers/mlrouter/queues/whatsappqueue \
  -H 'Content-Type:application/json' \
  -d$'{"predicate":"HAS(#{channel},\'whatsapp\')"}}' 

4. modify the config.php and set COMMS_ROUTER to the correct path
