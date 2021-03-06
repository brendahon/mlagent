<?php 
include 'jwt.php';
include 'sendfb.php';
include 'sendwa.php';

$configs = include('config.php');
$GLOBALS["config"] = $configs;
$request= file_get_contents('php://input');
$decoded_request = json_decode($request, true);
$fp = fopen("logs/raw.txt", 'a');

echo "\nreceive message=$request\n";

fwrite($fp, $request."\n");
fclose($fp);

    ob_start();
    echo "OK";
    $size = ob_get_length();
    header("Content-Encoding: none");
    header("Content-Length: {$size}");
    header("Connection: close");
    ob_end_flush();
    ob_flush();
    flush();
 


$msg_type="";
$customer="";
$ournumber="";
if($decoded_request["to"]["type"]=="whatsapp"){
	$msg_type="whatsapp";
	$customer = $decoded_request["from"]["number"];
	$ournumber = $decoded_request["to"]["number"];

}
if($decoded_request["to"]["type"]=="messenger"){
	$msg_type="messenger";
	$customer = $decoded_request["from"]["id"];
	$ournumber = $decoded_request["to"]["id"];

}
if($decoded_request["to"]["type"]=="video"){
	$msg_type="video";
	$customer = $decoded_request["from"]["number"];
	$ournumber = $decoded_request["to"]["number"];
}

if($decoded_request["direction"]=="inbound"){
	$m = $decoded_request["message"]["content"];
	$message = "";
	if($m["type"]=="text"){
		$message = $m["text"];
	}
	else if($m["type"]=="location"){
		$message="__LOC__".$m["location"]["lat"].",".$m["location"]["long"];
	}
	$message_p = strtolower($m["text"]);

	/*check if we are already in an active conversation */
	$chatfile=findActiveAgent($customer);
	mylog("chatfile ".$chatfile);
	if($chatfile == "None"){
		/* before adding to queue, check if we have active agents, otherwise send a generic "come back later" response */
		$status = callCenterStatus($msg_type);
		mylog("call center status ".$status);
		if($status == "AGENTS_NOT_AVAILABLE"){
			sendallbusymessage($ournumber,$customer,$msg_type);
			return;
		}
		else if($status == "AGENTS_BUSY"){
			/* Add to queue and send a generic "we will get back" response */
			addToQueue($customer,$msg_type,$message);
			$queue_size = queueStatus($msg_type);
			sendinqueuemessage($ournumber,$customer,$msg_type,$queue_size);
			return;
		}
		addToQueue($customer,$msg_type,$message);		
	}
	else {
		$logfile=$chatfile;
		$fp = fopen($logfile, 'a');
    		fwrite($fp, $msg_type."__SP__ __SP__".$customer."__SP__".$message."\n");
		fclose($fp);
		/* continue the conversation with same agent */
	}
}

function mylog($msg){
	file_put_contents("debug.txt",$msg."\n",FILE_APPEND);
}

function sendallbusymessage($ournumber,$customer,$channel){
	if($channel=="video"){
		echo "AGENTS_NOT_AVAILABLE";
	}
	else{
		sendmessage($ournumber,$customer,"Sorry, agents not available. Please try later",$channel);
	}
}
function sendinqueuemessage($ournumber,$customer,$channel,$queue_size)
{
	if($channel == "video"){
		echo "AGENTS_BUSY,".$queue_size;
	}
	else {
		sendmessage($ournumber,$customer,"All agents are busy, we will attend to you shortly. Your position is ".$queue_size." in the queue",$channel);
	}
}
function sendmessage($ournumber,$customer, $msg,$channel){
	if($channel=="messenger"){
		sendfb($ournumber,$customer,$msg);
	}
	else{
		sendwa($ournumber,$customer,$msg,"","","","");
	}
}

function callCenterStatus($channel){
 	$endpoint=$GLOBALS["config"]["COMMS_ROUTER"]."/agents";
        $data = '{
        }';
        $res = sendCurlRequest("GET",$endpoint,$data);

	$json = json_decode($res, true);
	$available_agents=0;
	$busy_agents=0;
	for($i=0;$i<count($json);$i++){
		$skills = $json[$i]["capabilities"];
		if($json[$i]["state"]=="ready" && in_array($channel,$skills["channel"]) ){
			$available_agents++;
		}
		else if($json[$i]["state"]=="busy" && in_array($channel,$skills["channel"]) ){
			$busy_agents++;
		}
	}
	if(count($json)==0 || ($available_agents == 0 && $busy_agents == 0)){
		return "AGENTS_NOT_AVAILABLE";
	}
	else if($available_agents > 0){
		return "AGENTS_AVAILABLE";
	}
	else {
		return "AGENTS_BUSY";
	}
}

function queueStatus($channel){
 	$endpoint=$GLOBALS["config"]["COMMS_ROUTER"]."/tasks";
        $data = '{
        }';
        $res = sendCurlRequest("GET",$endpoint,$data);

	$json = json_decode($res, true);
	$queue_size=0;
	for($i=0;$i<count($json);$i++){
		if($json[$i]["state"]=="waiting"){
			$queue_size++;
		}
	}
	return $queue_size;
}

function addToQueue($from,$type,$message)
{
	/* create a comms-router task and assign to a queue */
	$endpoint=$GLOBALS["config"]["COMMS_ROUTER"]."/tasks";
	$data = '{
			"queueRef":"'.$type.'queue",
			"callbackUrl":"'.$GLOBALS["config"]["CALLBACK_URL"].'",
			"userContext":{
				"message":"'.$message.'",
				"from":"'.$from.'",
				"type":"'.$type.'"
			}
	}';
	sendCurlRequest("POST",$endpoint,$data);
}

function findActiveAgent($customer){
	/* if there is a file named *-customernumber.chat then there is active conversation */
	$list = glob('agent/*-'.$customer.'.chat');
	if(count($list) <=0 )
		return "None";
	return $list[0];
}

function sendCurlRequest($type,$endpoint,$data){
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_HTTPHEADER, array(
    		'Content-Type: application/json',
    		'Accept: application/json'
	));
	if($type=="POST"){
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
	}
	else if($type == "PUT"){
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
	}
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_URL,$endpoint);

	$result = curl_exec($curl);
	curl_close($curl);

	return $result;
}

?>
