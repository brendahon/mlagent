<?php
include 'jwt.php';

if(isset($_POST["from"])){
	sendwa($_POST["from"],$_POST["to"],$_POST["four"],$_POST["one"],$_POST["two"],$_POST["three"]);
}
function sendwa($from,$to,$msg,$type,$one,$two,$three){
	$jwt = generate_jwt("application-id","path-to-privatekey");

	$data = "";

	if($type == "hsm"){
		$data ='{
	   "from":{
	      "type":"whatsapp",
	      "number":"'.$from.'"
	   },
	   "to":{
	      "type":"whatsapp",
	      "number":"'.$to.'"
	   },
	   "message":{
	      "content":{
		 "type":"template",
		 "template":{
		    "name":"whatsapp:hsm:technology:nexmo:verify",
		    "parameters":[
		       {
			  "default":"'.$one.'"
		       },
		       {
			  "default":"'.$two.'"
		       },
		       {
			  "default":"'.$three.'"
		       }
		    ]
		 }
	      }
	   }
	 }
	 ';
	}
	else{
		$data='{
	    "from": { "type": "whatsapp", "number": "'.$from.'" },
	    "to": { "type": "whatsapp", "number": "'.$to.'" },
	    "message": {
	      "content": {
		"type": "text",
		"text": "'.$_POST["four"].'"
	      }
	    }
	  }'; 

	}

	$curl = curl_init();
	curl_setopt($curl, CURLOPT_HTTPHEADER, array(
	    'Content-Type: application/json',
	    'Accept: application/json',
	    'Authorization: Bearer '.$jwt
	));
	curl_setopt($curl, CURLOPT_POST, 1);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
	curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	curl_setopt($curl, CURLOPT_URL, "https://api.nexmo.com/v0.1/messages");
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

	$result = curl_exec($curl);
	curl_close($curl);

	echo $result;
}
?>
