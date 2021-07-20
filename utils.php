<?php

use Hyperion\API\ClientModel;
use Hyperion\API\LogsModel;
use Hyperion\API\UserModel;
use JetBrains\PhpStorm\NoReturn;
use Hyperion\API\TokenModel;
use JetBrains\PhpStorm\Pure;

$_PUT = [];
/**
 * Used to parse put request parameters
 */
function parse_put(){
	global $_PUT;
	$_PUT = [];
	$body = file_get_contents("php://input");
	$tmp = explode("&", $body);
	foreach($tmp as $field){
		$tmp2 = explode("=", $field);
		$_PUT[$tmp2[0]] = $tmp2[1];
	}
}

function getUser(TokenModel $tm, string $token, UserModel $um): array | false{
	$token = $tm->selectByToken($token);
	if($token === false){
		response(404, "Not Found");
	}
	$user = $um->select($token['user']);
	if(!$user){
		response(404,"Not Found");
	}
	if($user['addr'] !== null){
		$user['addr'] = (new \Hyperion\API\AddressModel())->select($user['addr']);
		if($user['addr'] === false){
			response(500, "Internal Server Error");
		}
	}
	return $user;
}

function parse_body(): array| false{
	$body = file_get_contents("php://input");
	try{
		return json_decode($body, true, flags: JSON_THROW_ON_ERROR);
	}catch(JsonException){
		return false;
	}
}

/**
 * Create a JSON format response with HTTP Code, message and eventually content
 * @param int $status HTTP Code to return
 * @param string $message Message to join with status code
 * @param array|null $return Content to return
 */
#[NoReturn] function response(int $status, string $message, array|null $return = null){
	header("Content-Type: application/json");
	$response = [
		'status' => [
			'code' => $status,
			'message' => $message
		]
	];
	if($return !== null){
		$response['content'] = $return;
	}
	header("HTTP/1.1 $status $message");
	echo json_encode($response);
	exit();
}

function checkValidity(string $datetime): bool{
	$test = DateTime::createFromFormat("Y-m-d H:i:s", $datetime);
	$now = new DateTime();
	return $test->diff($now)->invert === 1;
}

function checkToken(string $token, int $level): bool{
	$tm = new TokenModel();
	$res = $tm->selectByToken($token);
	if($res !== false && checkValidity($res['end']) && $res['scope'] < $level){
		$end = new DateTime();
		$end->add(new DateInterval("PT2H"));
		$tm->update($res['id'], ['end' => $end->format("Y-m-d H:i:s")]);
		return true;
	}
	return false;
}

function API_log(string $token, string $table, string $message): bool{
	$lm = new LogsModel();
	$tm = new TokenModel();
	$um = new UserModel();
	$cm = new ClientModel();
	$info = $tm->selectByToken($token);
	if($info === false){
		return false;
	}
	$user = $um->select($info['user']);
	if($user === false){
		return false;
	}
	$client = $cm->select($info['client']);
	if($client === false){
		return false;
	}
	$username = $user['name'] . " " .$user['fname'] . ":" . $user['id'];
	$res = $lm->insert(['action' => "Operation on $table by user $username on client ${client['name']}\n\t$message", "user" => $user['id'], "client" => $client['id']]);
	return $res === false;
}

function replace_file_ext(string $org, string $new_ext): string{
	$exp = explode('.', $org);
	array_pop($exp);
	$exp[] = $new_ext;
	return join(".", $exp);
}

#[Pure] function is_png($pict): bool{
	return (bin2hex($pict[0]) == '89' && $pict[1] == 'P' && $pict[2] == 'N' && $pict[3] == 'G');
}