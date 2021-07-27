<?php

namespace Macaron\Base;
require_once "autoload.php";

/**
 * Class Router used to route all request on project hyperion website
 * @package Router
 * @author Macaron
 */
class Router{
	/** @var string $uri URI of the request */
	private string $uri;
	/** @var string $method HTTP method of the request; */
	private string $method;
	/** @var bool $routed Flag if request has been routed */
	private bool $routed;
	/** @var Controller|null $default_controller Used if no route match */
	private Controller|null $default_controller;
	/** @var string */
	public const GET = "GET";
	/** @var string */
	public const POST = "POST";
	/** @var string */
	public const PUT = "PUT";
	/** @var string */
	public const DELETE = "DELETE";
	/** @var array|null $uri_args */
	private array|null $uri_args = null;
	/** @var array|null $post_args */
	private array|null $post_args = null;
	/** @var array|null $put_args */
	private array|null $put_args = null;
	/** @var array|null $additional */
	private array|null $additional = null;

	/**
	 * Router constructor.
	 * @param Controller|null $default_controller Controller used if no route match and default() method is called
	 */
	public function __construct(Controller|null $default_controller = null){
		$this->uri = rawurldecode($_SERVER["REQUEST_URI"]);
		$this->method = $_SERVER["REQUEST_METHOD"];
		$this->routed = false;
		$headers = getallheaders();
		$this->default_controller = $default_controller;
		unset($headers);
	}

	/**
	 * Take a pattern and return the correspondent regex;
	 * @param string $pattern pattern to convert
	 * @return string
	 */
	private static function patternToRegex(string $pattern): string{
		$regex = preg_quote($pattern, "/");
		$regex = preg_replace("/\\\{[a-zA-Z0-9_-]+\\\}/", "([^\/]+)", $regex);
		$regex = str_replace("\*", "([^\/]+)", $regex);
		$regex = "/^" . $regex . "$/";
		$regex = str_replace("\[", "(?:", $regex);
		$regex = str_replace("\]", ")?", $regex);
		while(($pos = strpos($regex, "_")) !== false){
			if($regex[$pos - 1] !== '\\'){
				$regex = substr_replace($regex, "(.+)", $pos, 1);
			}
		}
		return $regex;
	}

	private function getUriParamName(string $pattern): array{
		$paramName = [];
		$count = 0;
		$in_param = false;
		for($i = 0; $i < strlen($pattern); ++$i){
			if($pattern[$i] === '{'){
				$paramName[$count] = "";
				$in_param = true;
				continue;
			}
			if($pattern[$i] === '}'){
				$count++;
				$in_param = false;
				continue;
			}
			if($pattern[$i] === '*' || $pattern[$i] === '_'){
				$paramName[$count] = $count;
				$count++;
			}
			if($in_param){
				$paramName[$count] .= $pattern[$i];
			}
		}
		return $paramName;
	}

	/**
	 * take router pattern and test it returning the matches if it match and false in the other case
	 * @param string $pattern
	 * @return array|bool
	 */
	private function match(string $pattern): array|bool{
		$regex = Router::patternToRegex($pattern);
		if(preg_match($regex, $this->uri, $matches) === 1){
			array_shift($matches);
			$key = $this->getUriParamName($pattern);
			$uri_args = array_combine($key, $matches);
			return $uri_args;
		}
		return false;
	}

	/**
	 * Prepare an args array with the uri args and if it exist, POST and FILES value
	 * @param array $uri_arg argument of uri
	 * @param mixed $additional_param additional param
	 */
	private function prepareArgs(array $uri_arg, mixed $additional_param = null){
		$this->uri_args = $uri_arg;
		if($this->method === Router::POST){
			$post = parse_body();
			if($post !== false){
				$this->post_args = $post;
			}else{
				response(400, "Bad Request");
			}
		}
		if($this->method === Router::PUT){
			$put = parse_body();
			if($put !== false){
				$this->put_args = $put;
			}else{
				response(400, "Bad Request");
			}
		}
		if($additional_param !== null){
			$this->additional = $additional_param;
		}
	}

	/**
	 * Route GET request with matching pattern (use * as wildcard character)
	 * @param string $pattern Pattern to match with
	 * @param string|null $controller_name Controller to use if pattern match
	 * @param mixed|null $additional_param
	 */
	public function get(string $pattern = "/", string|null $controller_name = null, mixed $additional_param = null){
		if($this->method == Router::GET){
			if(($matches = $this->match($pattern)) !== false){
				$this->prepareArgs($matches, $additional_param);
				$controller = new $controller_name($this->uri_args, additional: $this->additional);
				$this->routed = true;
				if($controller !== null)
					$controller->get();
				else
					$this->default_controller->get();
			}
		}
	}

	/**
	 * Route POST request with matching pattern (use * as wildcard character)
	 * @param string $pattern Pattern to match with
	 * @param string|null $controller_name Controller used if pattern match
	 * @param mixed|null $additional_param
	 */
	public function post(string $pattern = "/", string|null $controller_name = null, mixed $additional_param = null){
		if($this->method == Router::POST){
			if(($matches = $this->match($pattern)) !== false){
				$this->prepareArgs($matches, $additional_param);
				$controller = new $controller_name($this->uri_args, $this->post_args, additional: $this->additional);
				$this->routed = true;
				if($controller !== null)
					$controller->post();
				else
					$this->default_controller->post();
			}
		}
	}

	/**
	 * Route PUT request with matching pattern (use * as wildcard character)
	 * @param string $pattern Pattern to match with
	 * @param string|null $controller_name Controller used if pattern match
	 * @param mixed|null $additional_param
	 */
	public function put(string $pattern = "/", string|null $controller_name = null, mixed $additional_param = null){
		if($this->method == Router::PUT){
			if(($matches = $this->match($pattern)) !== false){
				$this->prepareArgs($matches, $additional_param);
				$controller = new $controller_name($this->uri_args, put_args: $this->put_args, additional: $this->additional);
				$this->routed = true;
				if($controller !== null)
					$controller->put();
				else
					$this->default_controller->put();
			}
		}
	}

	/**
	 * Route GET request with matching pattern (use * as wildcard character)
	 * @param string $pattern Pattern to match with
	 * @param string|null $controller_name Controller to use if pattern match
	 * @param mixed|null $additional_param
	 */
	public function delete(string $pattern = "/", string|null $controller_name = null, mixed $additional_param = null){
		if($this->method == Router::DELETE){
			if(($matches = $this->match($pattern)) !== false){
				$this->prepareArgs($matches, $additional_param);
				$controller = new $controller_name($this->uri_args, additional: $this->additional);
				$this->routed = true;
				if($controller !== null)
					$controller->delete();
				else
					$this->default_controller->delete();
			}
		}
	}

	/**
	 * Execute a controller on a method when no previous was executed
	 * @param string $method Method to execute default
	 */
	public function default(string $method = ""){
		if(!($this->routed) && $this->default_controller !== null){
			if(($method !== "" && $method === $this->method) || $method === ""){
				$this->prepareArgs([]);
				if($method === Router::GET){
					$this->default_controller->get();
				}elseif($method === Router::POST){
					$this->default_controller->post();
				}elseif($method === Router::PUT){
					$this->default_controller->put();
				}elseif($method === Router::DELETE){
					$this->default_controller->delete();
				}
			}
		}
	}

	public function route(string $pattern, callable $callback){
		if($this->match($pattern) !== false){
			$callback($this);
		}
	}

	public function getRouted(): bool{
		return $this->routed;
	}
}