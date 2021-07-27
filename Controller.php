<?php


namespace Macaron\Base;


use JetBrains\PhpStorm\NoReturn;

/**
 * Class Controller
 * @package Controller
 * @author Macaron
 */
abstract class Controller{

    protected string $title;

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

    /**
     * @param string $head String to add to the default head
     * @param string $header String to add to the default header
     * @param string $main String to add to the default main
     * @param string $footer String to add to the default footer
     */
	protected function generatePage(string $head = "", string $header = "", string $main = "", string $footer = ""){
	    $head = $this->generateHead($head);
	    $body = $this->generateBody($header, $main, $footer);
	    include "root.php";
    }

    /**
     * @param string $head line to add to the default head of the page
     * @return string
     */
    protected function generateHead(string $head = ""): string{
	    ob_start();
	    //make all work to have a proper head
        return ob_get_clean() . $head;
    }

    protected function generateBody(string $header = "", string $main = "", string $footer = ""): string{
        $header = $this->generateHeader() . $header;
        $main = $this->generateMain() . $main;
        $footer = $this->generateFooter() . $footer;
        ob_start();
        include "body.php";
        return ob_get_clean();
    }

    protected function generateHeader(): string{
        ob_start();
        //make all work to have a proper page header
        return ob_get_clean();
    }

    protected abstract function generateMain(): string;

	protected function generateFooter(): string{
	    ob_start();
	    //make all work to get a proper page footer
        return ob_get_clean();
    }
}