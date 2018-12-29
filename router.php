<?php
$configs = include('config.php');
$GLOBALS["config"] = $configs;
$cmd = $_GET["cmd"];
$agent = $_GET["agent"];
$taskid = $_GET["taskid"];
$customer = $_GET["customer"];
$skills = $_GET["skills"];
$GLOBALS["chat_file_path"]="agent/";

if($cmd == "offline"){
	setAgentStatus($agent,"offline");
	deleteAgent($agent);
}
else if($cmd == "online"){
	createAgent($agent,"");
	setAgentStatus($agent,"ready");
}
else if($cmd == "agentreset"){
	setTaskCompleted($taskid,$agent,$customer);
}
else if($cmd == "systemreset"){
	systemReset();
}
else if($cmd == "changeskills"){
	createAgent($agent,$skills);
}

function setAgentStatus($agent,$status){

	/* get the agent information so that we can get the E-Tag of the agent*/
	$endpoint=$GLOBALS["config"]["COMMS_ROUTER"]."/agents/".$agent;
	$data = '{
	}';
	$request_headers=array(
    		'Content-Type: application/json',
    		'Accept: application/json',
		'If-None-Match: '
	);
	$res = sendCurlRequest("GET",$endpoint,$data,$request_headers);
	$headers =$res[1];
	$etag = $headers["etag"][0];

	/* set agent status to offline now */

	$data = '{
		"state":"'.$status.'"
	}';
	$request_headers=array(
    		'Content-Type: application/json',
    		'Accept: application/json',
		'If-Match: '.$etag
	);
	
	$endpoint=$GLOBALS["config"]["COMMS_ROUTER"]."/agents/".$agent;
	$res = sendCurlRequest("POST",$endpoint,$data,$request_headers);
	echo $res[0];
}

function createAgent($agent,$skills){
	$endpoint=$GLOBALS["config"]["COMMS_ROUTER"]."/agents/".$agent;
	$skillset='';
	if(substr($skills,-1)==","){
		$skills = rtrim($skills,',');
	}
	if($skills != ""){
		$parts = explode(",",$skills);
		for($i=0;$i<count($parts);$i++){
			$skillset = $skillset.'"'.$parts[$i].'"';
			if($i!=count($parts)-1){
				$skillset = $skillset.',';
			}
		}
	}
	if($skillset == '')
		$skillset ='""';
	$data = '{
			"address":"sip:alex@vonage.com",
			"capabilities":{
					"channel":['.$skillset.']
			}
		}';
        $request_headers=array(
                'Content-Type: application/json',
                'Accept: application/json'
        );
	echo $data;
        $res = sendCurlRequest("PUT",$endpoint,$data,$request_headers);
        echo $res[0];
	setAgentStatus($agent,"ready");
}

function deleteAgent($agent){
	$endpoint=$GLOBALS["config"]["COMMS_ROUTER"]."/agents/".$agent;

	$data = '{}';
        $request_headers=array(
                'Content-Type: application/json',
                'Accept: application/json'
        );

        $res = sendCurlRequest("DELETE",$endpoint,$data,$request_headers);
        echo $res[0];

}

function deleteTask($task){
	$endpoint=$GLOBALS["config"]["COMMS_ROUTER"]."/tasks/".$task;

	$data = '{}';
        $request_headers=array(
                'Content-Type: application/json',
                'Accept: application/json'
        );

        $res = sendCurlRequest("DELETE",$endpoint,$data,$request_headers);
        echo $res[0];

}

function systemReset(){
	/* set assigned tasks to completed and remove pending tasks */
	$endpoint=$GLOBALS["config"]["COMMS_ROUTER"]."/tasks";
        $data = '{
        }';
	
	$request_headers=array(
    		'Content-Type: application/json',
    		'Accept: application/json'
	);
        $res = sendCurlRequest("GET",$endpoint,$data,$request_headers);

        $json = json_decode($res[0], true);
        $queue_size=0;
        for($i=0;$i<count($json);$i++){
                if($json[$i]["state"]=="assigned"){
			setTaskCompleted($json[$i]["ref"],"","");
                }
		deleteTask($json[$i]["ref"]);
        }
	
	$endpoint=$GLOBALS["config"]["COMMS_ROUTER"]."/agents";
	/* set all agents to offline */
        $res = sendCurlRequest("GET",$endpoint,$data,$request_headers);

        $json = json_decode($res[0], true);
        for($i=0;$i<count($json);$i++){
		deleteAgent($json[$i]["ref"]);        	
	}
	$list = glob('agent/*.chat');
	for($i=0;$i<count($list);$i++){
        	unlink($list[$i]);
	}
	 echo "All agents set to offline. Please close all demo windows and login again";	
}

function setTaskCompleted($taskid,$agent,$customer){
	$endpoint=$GLOBALS["config"]["COMMS_ROUTER"]."/tasks/".$taskid;
	$data = '{
		"state":"completed"
	}';
	$request_headers=array(
    		'Content-Type: application/json',
    		'Accept: application/json'
	);
	$res = sendCurlRequest("POST",$endpoint,$data,$request_headers);
	echo $res[0];
	/* delete the chat file */
	if($agent != ""){
		echo $GLOBALS["chat_file_path"].$agent."-".$customer.".chat";
		unlink($GLOBALS["chat_file_path"].$agent."-".$customer.".chat");
		deleteTask($taskid);
	}
}

function sendCurlRequest($type,$endpoint,$data,$request_headers){
	$curl = curl_init();
	$headers = [];
	curl_setopt($curl, CURLOPT_HTTPHEADER, $request_headers);
	if($type=="POST"){
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	}
	else if($type == "PUT"){
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	}
	else if($type == "GET"){
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	}
	else if($type == "DELETE"){
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
	}
	curl_setopt($curl, CURLOPT_HEADERFUNCTION,
  		function($curl, $header) use (&$headers)
  		{
    			$len = strlen($header);
    			$header = explode(':', $header, 2);
    			if (count($header) < 2) // ignore invalid headers
      				return $len;

    			$name = strtolower(trim($header[0]));
    			if (!array_key_exists($name, $headers))
      				$headers[$name] = [trim($header[1])];
    			else
      				$headers[$name][] = trim($header[1]);

    			return $len;
  		}
	);
	//curl_setopt($curl, CURLOPT_VERBOSE, true);
	curl_setopt($curl, CURLOPT_URL,$endpoint);

	$result = curl_exec($curl);
	curl_close($curl);

	return array($result,$headers);
}

?>
