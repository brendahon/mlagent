<?php
include 'jwt.php';

if(isset($_POST["from"])){
	sendfb($_POST["from"],$_POST["to"],$_POST["four"]);
}
function sendfb($from,$to,$msg){
	$jwt = generate_jwt("7c107966-8f10-43f2-aa51-37d2ed505f86","private.key");

	echo "\njwt= $jwt\n";
	echo "from= $from\n";
	echo "to= $to\n";
	echo "msg= $msg\n";

	$data = "";
	$data = '{
		"from": { "type": "messenger", "id": "'.$from.'" },
		"to": { "type": "messenger", "id": "'.$to.'" },
		"message": {

			"content": {
				"type": "text",
				"text": "'.$msg.'"
			}
		}
	}'; 


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

	var_dump(curl_getinfo($curl));

	curl_close($curl);

	echo $result;
}
?>
