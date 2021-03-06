<?php
require_once(dirname(__DIR__, 3) . "/vendor/autoload.php");
require_once(dirname(__DIR__, 3) . "/Classes/autoload.php");
require_once (dirname(__DIR__, 3) . "/lib/xsrf.php");
require_once("/etc/apache2/capstone-mysql/encrypted-config.php");

use Edu\Cnm\CreepyOctoMeow\Profile;

/**
 * API for profile activation, Profile class
 *
 * GET requests are supported.
 *
 * @author Rochelle Lewis <rlewis37@cnm.edu>
 **/

//check the session status. If it is not active, start the session.
if(session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}

/**
 * Prepare an empty reply.
 *
 * Here we create a new stdClass named $reply. A stdClass is basically an empty bucket that we can use to store things in.
 *
 * We will use this object named $reply to store the results of the call to our API. The status 200 line adds a state variable to $reply called status and initializes it with the integer 200 (success code). The proceeding line adds a state variable to $reply called data. This is where the result of the API call will be stored. We will also update $reply->message as we proceed through the API.
 **/
$reply = new stdClass();
$reply->status = 200;
$reply->data = null;

try {

	//grab the database connection
	$pdo = connectToEncryptedMySQL("/etc/apache2/capstone-mysql/rlewis37.ini");

	//determine which HTTP method, store the result in $method
	$method = array_key_exists("HTTP_X_HTTP_METHOD", $_SERVER) ? $_SERVER["HTTP_X_HTTP_METHOD"] : $_SERVER["REQUEST_METHOD"];

	//sanitize and store activation token - never trust the end user.
	//In this case... check to make sure "id" is changed to "token" on line 5 in the .htaccess
	$token = filter_input(INPUT_GET, "token", FILTER_SANITIZE_STRING);

	//check that activation token is a valid string of a hex
	if(ctype_xdigit($token) === false) {
		throw (new \InvalidArgumentException("Activation token is invalid.", 405));
	}

	//check that activation token is correct length
	if(strlen($token) !== 32) {
		throw (new \InvalidArgumentException("Activation Token is invalid length.", 405));
	}

	if($method === "GET") {

		setXsrfCookie();

		//grab the profile by activation token
		$profile = Profile::getProfileByProfileActivationToken($pdo, $token);

		if($profile !== null) {

			//make doubly sure the activation token matches
			if($token === $profile->getProfileActivationToken()) {

				//set the activation token to null
				$profile->setProfileActivationToken(null);

				//update profile
				$profile->update($pdo);

				//update reply
				$reply->message = "Profile activated!";
			}
		} else {
			throw (new \RuntimeException("No profile found for this activation token. Have you already activated your account?", 404));
		}
	} else {
		throw (new \RuntimeException("Invalid HTTP request!", 405));
	}

} catch(Exception | \TypeError $exception) {
	$reply->status = $exception->getCode();
	$reply->message = $exception->getMessage();
}

if($reply->data === null) {
	unset($reply->data);
}
?>

<!-- BEGIN HTML FOR ACCOUNT ACTIVATION PAGE -->
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

		<!-- Bootstrap CSS -->
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.2/css/bootstrap.min.css"
				integrity="sha384-PsH8R72JQ3SOdhVi3uxftmaW6Vc51MKb0q5P2rRUpPvrszuE4W1povHYgTpBfshb" crossorigin="anonymous">

		<!-- Font Awesome -->
		<link type="text/css" href="//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css"
				rel="stylesheet"/>

		<!-- Optional: JSON encode the $reply object and console.log it -->
		<script>
			console.log(<?php echo json_encode($reply);?>);
		</script>

		<title>Account Activation | Creepy Octo Meow</title>
	</head>
	<body>
		<div class="container">
			<div class="jumbotron my-5">
				<h1>Creepy Octo Meow | Account Activation</h1>
				<hr>
				<p class="lead d-flex">

					<!-- echo the $reply message in a creative way to the front end :D -->
					<?php
						echo $reply->message . "&nbsp;";
						if($reply->status === 200) {
							echo "<span class=\"align-self-center badge badge-success\">Code:&nbsp;" . $reply->status . "&nbsp;OK!</span>";
						} else {
							echo "<span class=\"align-self-center badge badge-danger\">Code:&nbsp;" . $reply->status . "</span>";
						}
					?>

				</p>
				<div class="mt-4">
					<a class="btn btn-lg btn-primary" href="https://bootcamp-coders.cnm.edu/~rlewis37/creepy-octo-meow/public_html/"><i class="fa fa-sign-in"></i>&nbsp;Sign In</a>
				</div>
			</div>
		</div>
	</body>
</html>