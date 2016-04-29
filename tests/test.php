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

namespace Test;

chdir(__DIR__);
include('../Dbug.php');

trait TraitA {
	public function traitMethodA() {}

	public static function publicStaticMethod() {
		return 'TraitA';
	}
}

trait TraitB {
	public function traitMethodA() {}
	public function traitMethodB() {}
	private function privateMethod() {}
}

trait TraitC {
	public static function publicStaticMethod() {
		return 'TraitA';
	}
}

class TestA extends \stdClass {
	use TraitC;

	const BLAH = 'dsfgdfg';

	private $privateProp = 124;
	protected $protectedProp = array(
		'a'	=> 1,
		'b'	=> false
	);
	public static $publicStaticProp = true;

	private function privateMethod() {
		return true;
	}
}

class TestB extends TestA {
	use TraitA;

	const BLAH = 'dsfg&dfg';

	private $privateProp = 124;
	protected $protectedProp = array(
		'a'	=> 1,
		'b'	=> false
	);
	public static $publicStaticProp = true;
	public $test = 'asdgb<';

	private function privateMethod(&$a, $b = 1, $c = '1 + 1 = 2', $d = "test'd") {
		return true;
	}

	public static function publicStaticMethod() {
		return 'TestB';
	}
}

class TestC extends TestB implements \Iterator {
	use TraitC;
	use TraitB;

	public function current() {}
	public function key() {}
	public function next() {}
	public function rewind() {}
	public function valid() {}

	public static function publicStaticMethod() {
		\D::backtrace(false);
	}
}

$fileTest = fopen(__FILE__, 'r');
$test = array(
	'a'	=> 'test',
	'b'	=> 1,
	'c'	=> 1.2,
	'd'	=> true,
	'e'	=> $fileTest,
	'f'	=> null,
	'g'	=> new \D(),
	'h'	=> TestA::publicStaticMethod(),
	'i'	=> TestB::publicStaticMethod()
);
\D::bug($test, false);
\D::bug($test['a'], false);
\D::bug(new TestA(), false, -1);
\D::bug(new TestC(), false, -1);
fclose($fileTest);
TestC::publicStaticMethod();
\D::bugString(array(1, 2, 3), false);
\D::bugString('', false);
\D::bugString('control' . chr(8) . ' character', false);