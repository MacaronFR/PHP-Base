<?php


namespace Hyperion\API;


use JetBrains\PhpStorm\NoReturn;

/**
 * Class Controller
 * @package Controller
 * @author Macaron
 */
abstract class Controller{

	public function __construct(
		/** @var array|null $uri_args*/
		protected array|null $uri_args,
		/** @var array|null $post_args*/
		protected array|null $post_args = null,
		/** @var array|null $put_args*/
		protected array|null $put_args = null,
		/** @var array|null $additional*/
		protected array|null $additional = null
	){

	}

	/**
	 * Must be instanced for using the get() method and control GET request
	 * @return no-return
	 */
	#[NoReturn]public abstract function get();
	/**
	 * Must be instanced for using the post() method and control POST request
	 * @return no-return
	 */
	#[NoReturn]public abstract function post();
	/**
	 * Must be instanced for using the put() method and control PUT request
	 * @return no-return
	 */
	#[NoReturn]public abstract function put();
	/**
	 * Must be instanced for using the delete() method and control DELETE request
	 * @return no-return
	 */
	#[NoReturn]public abstract function delete();
}