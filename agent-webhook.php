<?php
$request= file_get_contents('php://input');
$decoded_request = json_decode($request, true);
$task = $decoded_request["task"];

$taskid = $task["ref"];
$agent = $task["agentRef"];
$caller = $task["userContext"]["from"];
$message = $task["userContext"]["message"];
$channel=$task["userContext"]["type"];

file_put_contents("agent/".$agent."-".$caller.".chat",$channel."__SP__".$taskid."__SP__".$caller."__SP__".$message."\n");

?>
