<?php


namespace Macaron\Base;


use Exception;

class ModelException extends Exception{
	static int $QUERY_ERROR = 1;
	static int $QUERY_PREPARED_ERROR = 2;
	static int $COLUMN_NOT_EXIST_ERROR = 2;
}