<?php
/*
 * Copyright (c) 2013 David Negrier
 * 
 * See the file LICENSE.txt for copying permission.
 */

namespace Mouf\Utils\Log\Psr;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\AbstractLogger;

/**
 * A logger class that writes messages into the php error_log.
 */
class ErrorLogLogger extends AbstractLogger {
	
	const EMERGENCY = 'emergency';
	const ALERT = 'alert';
	const CRITICAL = 'critical';
	const ERROR = 'error';
	const WARNING = 'warning';
	const NOTICE = 'notice';
	const INFO = 'info';
	const DEBUG = 'debug';
	
	private static $levels = array(
		'none'=>0,
		'emergency'=>1,
		'alert'=>2,
		'critical'=>3,
		'error'=>4,
		'warning'=>5,
		'notice'=>6,
		'info'=>7,
		'debug'=>8,
	);
	
	
	/**
	 * The minimum level that will be tracked by this logger.
	 * 1=emergency
	 * 8=debug
	 *
	 * @var int
	 */
	private $level;
	
	
	/**
	 * 
	 * @param string $level The minimum level that will be tracked by this logger, as defined in the Psr\Log\LogLevel class. Must be one of: 'none', 'emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'.
	 */
	public function __construct($level = 'debug') {
		if (!isset(self::$levels[$level])) {
			throw new \Exception("Error, level '".$level."' is not a valid log level. The \$level property must be one of 'none', 'emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'");
		}
		
		$this->level = self::$levels[$level];
	}

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return null
     */
    public function log($level, $message, array $context = array()) {
    	if (!isset(self::$levels[$level]) || $level == 'none') {
    		throw new \Psr\Log\InvalidArgumentException("Error, level '".$level."' is not a valid log level. The \$level property must be one of 'emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'");
    	}
    	
    	// If the level is greater than the max level we log, let's not log anything.
    	if (self::$levels[$level] > $this->level) {
    		return;
    	}
    	
    	$exceptionText = '';
    	if (isset($context['exception'])) {
    		$e = $context['exception'];
			if ($e instanceof \Exception || $e instanceof \Throwable) {
    			$exceptionText = "\n".self::getTextForException($e);
			}
    	}
		$trace = debug_backtrace();
		$string = self::interpolate((string) $message, $context);
    	error_log(strtoupper($level).': '.$trace[1]['file']."(".$trace[1]['line'].") ".(isset($trace[2])?($trace[2]['class'].$trace[2]['type'].$trace[2]['function']):"")." -> ".$string.$exceptionText);
    }
    
    /**
     * Interpolates context values into the message placeholders.
     */
    private function interpolate($message, array $context = array())
	{
		// build a replacement array with braces around the context keys
		$replace = array();
	  	foreach ($context as $key => $val) {
	      	$replace['{' . $key . '}'] = $val;
	 	}
	
	  	// interpolate replacement values into the message and return
	  	return strtr($message, $replace);
	}
	
	/*private static function logMessage($level, $string, $e=null) {
		if ($e == null) {
			if (!$string instanceof \Exception) {
				$trace = debug_backtrace();
				error_log($level.': '.$trace[1]['file']."(".$trace[1]['line'].") ".(isset($trace[2])?($trace[2]['class'].$trace[2]['type'].$trace[2]['function']):"")." -> ".$string);
			} else {
				error_log($level.': '.self::getTextForException($string));
			}
		} else {
			$trace = debug_backtrace();
			error_log($level.': '.$trace[1]['file']."(".$trace[1]['line'].") ".(isset($trace[2])?($trace[2]['class'].$trace[2]['type'].$trace[2]['function']):"")." -> ".$string."\n".self::getTextForException($e));
		}

	}*/
	
	
	/**
	 * Function called to display an exception if it occurs.
	 * It will make sure to purge anything in the buffer before calling the exception displayer.
	 *
	 * @param \Throwable $exception
	 */
	private static function getTextForException($exception) {
		// Now, let's compute the same message, but without the HTML markup for the error log.
		$textTrace = "Message: ".$exception->getMessage()."\n";
		$textTrace .= "File: ".$exception->getFile()."\n";
		$textTrace .= "Line: ".$exception->getLine()."\n";
		$textTrace .= "Stacktrace:\n";
		$textTrace .= self::getTextBackTrace($exception->getTrace());
		return $textTrace;
	}
	
	/**
	 * Returns the Exception Backtrace as a text string.
	 *
	 * @param unknown_type $backtrace
	 * @return unknown
	 */
	private static function getTextBackTrace($backtrace) {
		$str = '';
	
		foreach ($backtrace as $step) {
			if ($step['function']!='getTextBackTrace' && $step['function']!='handle_error')
			{
				if (isset($step['file']) && isset($step['line'])) {
					$str .= "In ".$step['file'] . " at line ".$step['line'].": ";
				}
				if (isset($step['class']) && isset($step['type']) && isset($step['function'])) {
					$str .= $step['class'].$step['type'].$step['function'].'(';
				}
	
				if (isset($step['args']) && is_array($step['args'])) {
					$drawn = false;
					$params = '';
					foreach ( $step['args'] as $param)
					{
						$params .= self::getPhpVariableAsText($param);
						//$params .= var_export($param, true);
						$params .= ', ';
						$drawn = true;
					}
					$str .= $params;
					if ($drawn == true)
					$str = substr($str, 0, strlen($str)-2);
				}
				$str .= ')';
				$str .= "\n";
			}
		}
	
		return $str;
	}
	
	/**
	 * Used by the debug function to display a nice view of the parameters.
	 *
	 * @param unknown_type $var
	 * @return unknown
	 */
	private static function getPhpVariableAsText($var) {
		if( is_string( $var ) )
		return( '"'.str_replace( array("\x00", "\x0a", "\x0d", "\x1a", "\x09"), array('\0', '\n', '\r', '\Z', '\t'), $var ).'"' );
		else if( is_int( $var ) || is_float( $var ) )
		{
			return( $var );
		}
		else if( is_bool( $var ) )
		{
			if( $var )
			return( 'true' );
			else
			return( 'false' );
		}
		else if( is_array( $var ) )
		{
			$result = 'array( ';
			$comma = '';
			foreach( $var as $key => $val )
			{
				$result .= $comma.self::getPhpVariableAsText( $key ).' => '.self::getPhpVariableAsText( $val );
				$comma = ', ';
			}
			$result .= ' )';
			return( $result );
		}
	
		elseif (is_object($var)) return "Object ".get_class($var);
		elseif(is_resource($var)) return "Resource ".get_resource_type($var);
		return "Unknown type variable";
	}
}

?>
