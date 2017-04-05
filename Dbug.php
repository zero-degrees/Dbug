<?php

/**
 * @package Dbug
 *
 * @author Craig Russell
 * @link https://github.com/zero-degrees/Dbug
 * @license MIT License
 *
 * @copyright
 * Copyright (c) 2015 Craig Russell
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

class D {
	const STYLE = 'background-color: white; color: black; font-size: initial; font-weight: initial; font-style: initial; text-decoration: none; text-align: left; font-family: monospace; text-transform: none; padding: 14px; border: solid 1px #888;';

	protected static $_controlChars = array(
		0	=> 'null',
		1	=> 'start of header',
		2	=> 'start of text',
		3	=> 'end of text',
		4	=> 'end of transmission',
		5	=> 'enquiry',
		6	=> 'acknowledge',
		7	=> 'bell',
		8	=> 'backspace',
		9	=> 'horizontal tab',
		10	=> 'line feed',
		11	=> 'vertical tab',
		12	=> 'form feed',
		13	=> 'carriage return',
		14	=> 'shift out',
		15	=> 'shift in',
		16	=> 'data link escape',
		17	=> 'device control 1',
		18	=> 'device control 2',
		19	=> 'device control 3',
		20	=> 'device control 4',
		21	=> 'negative acknowledge',
		22	=> 'synchronous idle',
		23	=> 'end of transmission block',
		24	=> 'cancel',
		25	=> 'end of medium',
		26	=> 'control-z',
		27	=> 'escape',
		28	=> 'file separator',
		29	=> 'group separator',
		30	=> 'record separator',
		31	=> 'unit separator',
		127	=> 'delete'
	);

	protected static $_styles = array(
		'default'		=> array("\033[0;39m", ''),
		'implement'		=> array("\033[1;36m", 'color: darkcyan; font-weight: bold;'),
		'className'		=> array("\033[1;36m", 'color: darkcyan; font-weight: bold;'),
		'methodName'	=> array("\033[1;36m", 'color: darkcyan; font-weight: bold;'),
		'constantName'	=> array("\033[1;36m", 'color: darkcyan; font-weight: bold;'),
		'propertyName'	=> array("\033[1;36m", 'color: darkcyan; font-weight: bold;'),
		'parameterName'	=> array("\033[0;32m", 'color: darkgreen; font-weight: bold;'),
		'static'		=> array("\033[1;31m", 'color: firebrick; font-weight: bold;'),
		'controlChar'	=> array("\033[1;31m", 'color: red;'),
		'warning'		=> array("\033[1;31m", 'color: red;'),
		'visibility'	=> array("\033[1;35m", 'color: darkmagenta; font-weight: bold;'),
		'function'		=> array("\033[1;35m", 'color: darkmagenta; font-weight: bold;'),
		'type'			=> array("\033[1;32m", 'color: green; font-weight: bold;'),
		'namespace'		=> array("\033[0;36m", 'color: darkcyan;'),
		'caller'		=> array("\033[1;39m", 'font-weight: bold;'),
		'trait'			=> array("\033[1;34m", 'color: blue; font-weight: bold;')
	);

	protected static $_registry = array();

	/**
	 * Display debugging info about any kind of variable.
	 *
	 * @param mixed $var The variable to debug
	 * @param bool $exit
	 * @param int $maxDepth Limits recursion. Set to -1 to disable resursion protection.
	 * @param int $depth The current depth
	 */
	public static function bug($var, $exit = true, $maxDepth = 1, $depth = 0) {
		if(!self::isAuthorized()) {
			return;
		}

		if($depth == 0) {
			self::_header();
		}

		echo str_repeat("\t", $depth);
		$type = gettype($var);
		echo '(', self::_style($type, 'type');
		if($type == 'string') {
			$formattedVar = self::_mode() == 'web' ? htmlentities($var) : $var;
			echo ' ', strlen($var), ') ', $formattedVar;
		}
		else if($type == 'boolean') {
			echo ') ', $var ? 'true' : 'false';
		}
		else if($type == 'resource') {
			echo ') ', get_resource_type($var);
		}
		else if(in_array($type, array('unknown', 'NULL'))) {
			echo ')';
		}
		else if($type == 'array') {
			echo ' ', count($var), ')';
			if($depth != $maxDepth) {
				foreach($var as $key => $child) {
					ob_start();
					self::bug($child, false, $maxDepth, $depth + 1);
					$info = ltrim(ob_get_clean());
					echo "\n", str_repeat("\t", $depth + 1), self::_style($key, 'propertyName'), ' => ', $info;
				}
			}
		}
		else if($type == 'object') {
			echo ' ', self::_styleClassName(get_class($var)), ')';
			if($depth != $maxDepth) {
				self::_bugObject($var, $maxDepth, $depth + 1);
			}
		}
		else {
			echo ') ', $var;
		}

		if($depth == 0) {
			self::_footer($exit);
		}
	}

	/**
	 * Output a string's ASCII values, and highlight control characters.
	 *
	 * @param string $var
	 * @param bool $exit
	 */
	public static function bugString($var, $exit = true) {
		if(!self::isAuthorized()) {
			return;
		}

		self::_header();

		if(is_string($var)) {
			$length = strlen($var);
			echo '(', self::_style('string', 'type'), ' ', strlen($var), ")\n";
			if($var) {
				echo "\n";
				for($i = 0; $i != $length; ++$i) {
					$code = ord($var[$i]);
					$isControlChar = isset(self::$_controlChars[$code]);

					if($isControlChar) {
						$code .= '(' . self::$_controlChars[$code] . ')';
						echo self::_style($code, 'controlChar');
					}
					else {
						echo $code;
					}
					echo ' ';
				}
			}
		}
		else {
			echo self::_style("Not a string.\n", 'warning');
		}

		self::_footer($exit);
	}

	/**
	 * Generate a backtrace.
	 *
	 * @param bool $showArgs Show function/method arguments
	 * @param bool $exit
	 */
	public static function backtrace($exit = true, $showArgs = false) {
		if(!self::isAuthorized()) {
			return;
		}

		self::_header();
		
		$reflection = new ReflectionFunction('debug_backtrace');
		$parameterCount = sizeof($reflection->getParameters());
		if($parameterCount != 0) {
			$options = $showArgs ? DEBUG_BACKTRACE_IGNORE_ARGS : 0;
			debug_print_backtrace($options);
		}
		else {
			debug_print_backtrace();
		}
		
		self::_footer($exit);
	}

	/**
	 * Display a list of currently included files.
	 *
	 * @param bool $exit
	 */
	public static function includes($exit = true) {
		if(!self::isAuthorized()) {
			return;
		}

		self::_header();
		echo implode("\n", get_included_files());
		self::_footer($exit);
	}

	/**
	 * Display the ini settings.
	 *
	 * @param bool $exit
	 */
	public static function ini($exit = true) {
		if(!self::isAuthorized()) {
			return;
		}

		self::bug(ini_get_all(), $exit, -1);
	}

	/**
	 * Check if the client is eligible for debugging.
	 */
	public static function isAuthorized() {
		$authorized = self::_get('authorized');
		if($authorized !== null) {
			return $authorized;
		}

		if(self::_mode() == 'cli') {
			return true;
		}

		$headers = function_exists('apache_request_headers') ? apache_request_headers() : array();
		$ip = isset($headers['X-Forwarded-For']) ? $headers['X-Forwarded-For'] : $_SERVER['REMOTE_ADDR'];
		return preg_match('/^(192\.168\.|172\.16\.|10\.|127\.0\.0\.1$|0:0:0:0:0:0:0:1$|::1$)/S', $ip);
	}

	/**
	 * Grant debugging privelege to the client.
	 */
	public static function authorize() {
		self::_set('authorized', true);
	}

	/**
	 * Revoke the client's debugging privelege.
	 */
	public static function deauthorize() {
		self::_set('authorized', false);
	}

	/**
	 * Write detailed debugging info about an object.
	 *
	 * @param object $var The variable to debug
	 * @param int $maxDepth Limits recursion. Set to -1 to disable resursion protection.
	 * @param int $depth The current depth
	 */
	protected static function _bugObject($var, $maxDepth, $depth) {
		$mode = self::_mode();
		$class = get_class($var);
		$object = new ReflectionObject($var);

		$ancestors = self::_getAncestors($object);
		foreach($ancestors as $ancestor) {
			echo "\n", str_repeat("\t", $depth), self::_bugDeclaration($ancestor['reflection']);
		}
		echo "\n";

		$implements = class_implements($class, false);
		if($implements) {
			echo "\nImplements:\n";
			foreach($implements as $implement) {
				echo str_repeat("\t", $depth), self::_style($implement, 'implement'), "\n";
			}
		}

		$constants = $object->getConstants();
		if($constants) {
			echo "\nConstants:\n";
			foreach($constants as $name => $value) {
				ob_start();
				self::bug($value, false, $maxDepth, $depth + 1);
				$info = ltrim(ob_get_clean());
				echo str_repeat("\t", $depth), self::_style($name, 'constantName'), ' = ', $info, "\n";
			}
		}

		$properties = $object->getProperties();
		if($properties) {
			echo "\nProperties:\n";
			foreach($properties as $k => $property) {
				$visibility = self::_getVisibility($property);
				$accessible = $visibility == 'public';
				$static = $property->isStatic() ? 'static ' : '';
				$name = '$' . $property->getName();

				if(!$accessible) {
					$property->setAccessible(true);
				}
				ob_start();
				self::bug($property->getValue($var), false, $maxDepth, $depth);
				$info = ltrim(ob_get_clean());
				if(!$accessible) {
					$property->setAccessible(false);
				}

				echo str_repeat("\t", $depth),
					self::_style($visibility, 'visibility'), ' ', self::_style($static, 'static'), self::_style($name, 'propertyName'), ' = ', $info,
					"\n";
			}
		}

		$methods = $object->getMethods();
		if($methods) {
			echo "\nMethods:\n";
			foreach($methods as $k => $method) {
				$visibility = self::_getVisibility($method);
				$accessible = $visibility == 'public';
				$static = $method->isStatic() ? 'static ' : '';
				$name = $method->getName();
				if(!$accessible) {
					$method->setAccessible(true);
				}
				$formattedParams = array();
				$params = $method->getParameters();
				foreach($params as $param) {
					$variadic = method_exists($param, 'isVariadic') && $param->isVariadic();
					if(!$variadic && $param->isOptional() && $param->isDefaultValueAvailable()) {
						$value = $param->getDefaultValue();
						$type = gettype($value);

						if($type == 'string') {
							$value = self::_mode() == 'web' ? htmlentities($value) : $value;
							$value = "'" . str_replace("'", "\'", $value) . "'";
						}
						else if($type == 'boolean') {
							$value = $value ? 'true' : 'false';
						}
						else if(in_array($type, array('unknown', 'NULL'))) {
							$value = $type;
						}
						else if($type == 'array') {
							$value = $value ? 'array(...)' : 'array()';
						}
						$value = ' = ' . $value;
					}
					else {
						$value = '';
					}
					$paramName = '$' . $param->getName();
					$reference = $param->isPassedByReference() ? '&' : '';
					$reference = $mode == 'web' ? htmlentities($reference) : $reference;
					$formattedParam = ($variadic ? '...' : '') . $reference . self::_style($paramName, 'parameterName') . $value;
					$formattedParams[] = $formattedParam;
				}
				echo str_repeat("\t", $depth),
					self::_style($visibility, 'visibility'), ' ', self::_style($static, 'static'), self::_style('function', 'function'), ' ', self::_style($name, 'methodName'), '(', implode(', ', $formattedParams), ')',
					"\n";

				foreach($ancestors as $ancestor) {
					$reflection = $ancestor['reflection'];
					if($reflection->hasMethod($name)) {
						$ancestorMethod = $reflection->getMethod($name);
						if($ancestorMethod->class == $reflection->getName()) {
							echo str_repeat("\t", $depth + 1), self::_bugDeclaration($ancestorMethod), "\n";
						}
					}
				}
				if(!$accessible) {
					$method->setAccessible(false);
				}
			}
		}
	}

	/**
	 * Build a formatted declaration string for the supplied class or method.
	 *
	 * @param ReflectionClass|ReflectionMethod $reflection
	 *
	 * @return string
	 */
	protected static function _bugDeclaration($reflection) {
		$isMethod = get_class($reflection) == 'ReflectionMethod';
		$name = $isMethod ? $reflection->class : $reflection->getName();
		$file = $reflection->getFileName();
		$line = $reflection->getStartLine();
		if($file !== false) {
			ob_start();
			$isTrait = (method_exists($reflection, 'isTrait') && $reflection->isTrait()) || 
					($isMethod && method_exists($reflection->getDeclaringClass(), 'isTrait') && $reflection->getDeclaringClass()->isTrait());
			if($isTrait) {
				echo "\t";
			}
			echo $file, ':', $line, ' (';
			if($isTrait) {
				echo self::_style('Trait', 'trait'), ' ';
			}
			echo self::_styleClassName($name) . ')';

			return ob_get_clean();
		}
		else {
			return 'Built-in (' . self::_style($name, 'className') . ')';
		}
	}

	/**
	 * Get a list of ancestors for a given class or object.
	 *
	 * @param RefectionClass|ReflectionObject $reflection
	 *
	 * @return array
	 */
	protected static function _getAncestors($reflection) {
		if(get_class($reflection) == 'ReflectionObject') {
			$reflection = new ReflectionClass($reflection->getName());
		}
		$class = $reflection->name;
		$ancestors[] = array(
				'isTrait'		=> false,
				'reflection'	=> $reflection
			);
		$ancestor = $reflection;
		$traits = self::_getTraits($ancestor);
		foreach($traits as $trait) {
			$ancestors[] = array(
					'isTrait'		=> true,
					'reflection'	=> $trait
				);
		}
		while($ancestor) {
			$ancestor = $ancestor->getParentClass();
			if($ancestor) {
				$ancestors[] = array(
						'isTrait'		=> false,
						'reflection'	=> $ancestor
					);
				
				$traits = self::_getTraits($ancestor);
				foreach($traits as $trait) {
					$ancestors[] = array(
							'isTrait'		=> true,
							'reflection'	=> $trait
						);
				}
			}
		}

		return $ancestors;
	}


	/**
	 * Get the traits (if supported) the supplied class or method.
	 *
	 * @param ReflectionClass|ReflectionMethod $reflection
	 *
	 * @return array
	 */
	protected static function _getTraits($reflection) {
		return method_exists($reflection, 'getTraits') ? $reflection->getTraits() : array();
	}

	/**
	 * Get a property or method's visibility.
	 *
	 * @param ReflectionProperty|ReflectionMethod $var
	 *
	 * @return string
	 */
	protected static function _getVisibility($var) {
		if($var->isPrivate()) {
			return 'private';
		}
		if($var->isProtected()) {
			return 'protected';
		}
		if($var->isPublic()) {
			return 'public';
		}
	}

	/**
	 * Split a class name from its namespace.
	 *
	 * @param string $className
	 *
	 * @return array
	 */
	protected static function _splitClassName($className) {
		$pieces = explode('\\', $className);

		return array(
			'name'		=> array_pop($pieces),
			'namespace'	=> sizeof($pieces) ? implode('\\', $pieces) . '\\' : ''
		);
	}

	protected static function _mode() {
		return php_sapi_name() == 'cli' ? 'cli' : 'web';
	}

	/**
	 * Start the output buffer, and write the header.
	 */
	protected static function _header() {
		ob_start();
		echo "\n";
		if(self::_mode() == 'web') {
			echo '<pre style="', self::STYLE, '">';
		}
		else {
			echo self::_getStyle('default');
		}

		echo 'Dbug called from ' . self::_style(self::_getCaller(), 'caller'), "\n\n";
	}

	/**
	 * Write the footer, flush the output buffer, and exit if requested.
	 *
	 * @param bool $exit
	 */
	protected static function _footer($exit) {
		echo "\n";
		if(self::_mode() == 'web') {
			echo "</pre>";
		}
		else {
			echo self::_getStyle('default');
		}
		echo "\n";
		ob_end_flush();

		if($exit) {
			exit;
		}
	}

	/**
	 * Style the supplied string.
	 *
	 * @param string $string The string to style
	 * @param string $name The name of the style
	 *
	 * @return string
	 */
	protected static function _style($string, $name) {
		$style = self::_getStyle($name);
		if(self::_mode() == 'web') {
			return '<span style="' . $style . '">' . $string . '</span>';
		}
		else {
			return $style . $string . self::_getStyle('default');
		}
	}

	/**
	 * Style the supplied class name.
	 *
	 * @param string $className
	 *
	 * @return string
	 */
	protected static function _styleClassName($className) {
		$pieces = self::_splitClassName($className);

		$output = '';
		if(sizeof($pieces) > 1) {
			$output = self::_style($pieces['namespace'], 'namespace');
		}
		$output .= self::_style($pieces['name'], 'className');
		
		return $output;
	}

	/**
	 * Look up a style.
	 *
	 * @param string $name The name of the style
	 *
	 * @return string
	 */
	protected static function _getStyle($name) {
		$style = isset(self::$_styles[$name]) ? self::$_styles[$name] : self::$_styles['default'];

		return self::_mode() == 'web' ? $style[1] : $style[0];
	}

	/**
	 * Determine the file and line of Dbug's caller.
	 *
	 * @return string
	 */
	protected static function _getCaller() {
		$trace = debug_backtrace();
		foreach($trace as $step) {
			if($step['function'][0] != '_') {
				break;
			}
		}

		return $step['file'] . ':' . $step['line'];
	}

	/**
	 * Get a value from the registry.
	 *
	 * @param string $key
	 *
	 * @return mixed
	 */
	protected static function _get($key) {
		return isset(self::$_registry[$key]) ? self::$_registry[$key] : null;
	}

	/**
	 * Store a value in the registry.
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	protected static function _set($key, $value) {
		if(!isset(self::$_registry)) {
			self::$_registry = array();
		}
		self::$_registry[$key] = $value;
	}
}