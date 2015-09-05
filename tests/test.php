<?php

chdir(__DIR__);
include('../Dbug.php');

class TestA extends stdClass {
    const BLAH = 'dsfgdfg';

    private $privateProp = 124;
    protected $protectedProp = array(
        'a' => 1,
        'b' => false
    );
    public static $publicStaticProp = true;

    private function privateFunction() {
        return true;
    }

    public static function publicStaticFunction() {
        return true;
    }
}

class TestB extends TestA {
    const BLAH = 'dsfg&dfg';

    private $privateProp = 124;
    protected $protectedProp = array(
        'a' => 1,
        'b' => false
    );
    public static $publicStaticProp = true;
    public $test = 'asdgb<';

    private function privateFunction(&$a, $b = 1, $c = '1 + 1 = 2', $d = "test'd") {
        return true;
    }
}

class TestC extends TestB {
    public static function publicStaticFunction() {
        D::backtrace(false);
    }
}

$fileTest = fopen(__FILE__, 'r');
$test = array(
    'a' => 'test',
    'b' => 1,
    'c' => 1.2,
    'd' => true,
    'e' => $fileTest,
    'f' => null,
    'g' => new D()
);
D::bug($test, false);
D::bug($test['a'], false);
D::bug(new TestC(), false, -1);
fclose($fileTest);
TestC::publicStaticFunction();
D::bugString('control' . chr(8) . ' character');
