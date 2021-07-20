<?php

use JetBrains\PhpStorm\NoReturn;
use JetBrains\PhpStorm\Pure;

function parse_body(): array|false{
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