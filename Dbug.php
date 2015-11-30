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
    const STYLE = 'background-color: white; color: black; font-size: initial; font-weight: initial; font-style: initial; text-decoration: none; text-align: left; font-family: monospace; text-transform: none; padding: 14px;';

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
            echo ' ', self::_style(get_class($var), 'className'), ')';
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

        $var = (string)$var;
        if($var) {
            $length = strlen($var);
            for($i = 0; $i != $length; ++$i) {
                $code = ord($var[$i]);
                $isControlChar = $code < 32 || $code == 127;
                $code = str_pad($code, 3, '0', STR_PAD_LEFT);
                echo $isControlChar ? self::_style($code, 'controlChar') : $code, ' ';
            }
        }
        else {
            echo 'Empty string';
        }

        self::_footer($exit);
    }

    /**
     * Generate a backtrace.
     *
     * @param bool $exit
     */
    public function backtrace($exit = true) {
        if(!self::isAuthorized()) {
            return;
        }

        self::_header();
        debug_print_backtrace();
        self::_footer($exit);
    }

    /**
     * Display a list of currently included files.
     *
     * @param bool $exit
     */
    public function includes($exit = true) {
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
    public function ini($exit = true) {
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
            echo "\n", str_repeat("\t", $depth), self::_bugDeclaration($ancestor);
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
                    if(!$variadic && $param->isOptional()) {
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
                    if($ancestor->hasMethod($name)) {
                        $ancestorMethod = $ancestor->getMethod($name);
                        if($ancestorMethod->class == $ancestor->getName()) {
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
        $name = get_class($reflection) == 'ReflectionMethod' ? $reflection->class : $reflection->getName();
        $file = $reflection->getFileName();
        $line = $reflection->getStartLine();
        if($file !== false) {
            return $file . ':' . $line . ' (' . self::_style($name, 'className') . ')';
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
        $ancestors = array($reflection);
        $ancestor = $reflection;
        while($ancestor) {
            $ancestor = $ancestor->getParentClass();
            if($ancestor) {
                $ancestors[] = $ancestor;
            }
        }

        return $ancestors;
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
     * Look up a style.
     *
     * @param string $name The name of the style
     *
     * @return string
     */
    protected static function _getStyle($name) {
		$styles = array(
			'default'		=> array("\033[0;39m", ''),
			'implement'  	=> array("\033[1;36m", 'color: darkcyan; font-weight: bold;'),
			'className'		=> array("\033[0;36m", 'color: darkcyan; font-weight: bold;'),
			'methodName'	=> array("\033[1;36m", 'color: darkcyan; font-weight: bold;'),
			'constantName'	=> array("\033[1;36m", 'color: darkcyan; font-weight: bold;'),
			'propertyName'	=> array("\033[1;36m", 'color: darkcyan; font-weight: bold;'),
			'parameterName'	=> array("\033[0;32m", 'color: darkgreen; font-weight: bold;'),
			'static'		=> array("\033[1;31m", 'color: firebrick; font-weight: bold;'),
			'controlChar'	=> array("\033[1;31m", 'color: red;'),
			'visibility'	=> array("\033[1;35m", 'color: darkmagenta; font-weight: bold;'),
			'function'   	=> array("\033[1;35m", 'color: darkmagenta; font-weight: bold;'),
			'type'   		=> array("\033[1;32m", 'color: green; font-weight: bold;')
		);
        $style = isset($styles[$name]) ? $styles[$name] : $styles['default'];

        return self::_mode() == 'web' ? $style[1] : $style[0];
    }

    /**
     * Get a value from the registry.
     *
     * @param string $key
     *
     * @return mixed
     */
    protected static function _get($key) {
        global $_Dbug;

        return isset($_Dbug[$key]) ? $_Dbug[$key] : null;
    }

    /**
     * Store a value in the registry.
     *
     * @param string $key
     * @param mixed $value
     */
    protected static function _set($key, $value) {
        global $_Dbug;

        if(!isset($_Dbug)) {
            $_Dbug = array();
        }
        $_Dbug[$key] = $value;
    }
}
