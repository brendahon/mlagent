<?php
require __DIR__ . '/vendor/autoload.php';
error_reporting(E_ALL);
ini_set('display_errors', 'On');
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Rsa\Sha256;
if (!function_exists('generate_jwt')) {
	function generate_jwt( $application_id, $keyfile) {

	    $jwt = false;
	    date_default_timezone_set('UTC');    //Set the time for UTC + 0
	    $key = file_get_contents($keyfile);  //Retrieve your private key
	    $signer = new Sha256();
	    $privateKey = new Key($key);

	    $jwt = (new Builder())->setIssuedAt(time() - date('Z')) // Time token was generated in UTC+0
		->set('application_id', $application_id) // ID for the application you are working with
		->setId( base64_encode( mt_rand (  )), true)
		->sign($signer,  $privateKey) // Create a signature using your private key
		->getToken(); // Retrieves the JWT

	    return $jwt;
	}
}

?>
