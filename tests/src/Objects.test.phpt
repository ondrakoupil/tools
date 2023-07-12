<?php

include "../bootstrap.php";

use OndraKoupil\Tools\Objects;
use \Tester\TestCase;
use \Tester\Assert;

class Foo {

	public $a, $b, $c;

	function __construct($a = null, $b = null, $c = null) {
		$this->a = $a;
		$this->b = $b;
		$this->c = $c;
	}

}

class Bar extends Foo {

}

interface ITrum {

}

interface IBlam extends ITrum {

}

class FooTrum extends Foo implements ITrum {

}

class FooBlam extends Foo implements IBlam {

}

class Something {

}



class ObjectsTest extends TestCase {

	function testCreate() {

		// Input as string

		$o = Objects::createFrom('Foo');
		Assert::type('Foo', $o);

		$o = Objects::createFrom('Bar');
		Assert::type('Bar', $o);

		$o = Objects::createFrom('Bar', null, array(1, 2, 3));
		Assert::type('Bar', $o);
		Assert::same(1, $o->a);
		Assert::same(2, $o->b);
		Assert::same(3, $o->c);

		// Input as object

		$foo = new Foo(1, 5);
		$o = Objects::createFrom($foo);
		Assert::type('Foo', $o);
		Assert::same(5, $o->b);

		// Input as callable

		$bar = new Bar(10);
		$o = Objects::createFrom(
			function ($a) use ($bar) {
				$bar->b = $a;
				return $bar;
			},
			null,
			array(20)
		);

		Assert::type('Bar', $o);
		Assert::same(10, $o->a);
		Assert::same(20, $o->b);

		// Type checking
		$o = Objects::createFrom('Bar', 'Foo');
		$o = Objects::createFrom('FooTrum', 'ITrum');

		Assert::exception(function() {
			Objects::createFrom('Something', 'Bar');
		}, 'Exception');

		Assert::exception(function() {
			$a = new Foo();
			Objects::createFrom($a, 'Something');
		}, 'Exception');

		Assert::exception(function() {
			$a = new Foo();
			Objects::createFrom(function() use ($a) {
				return $a;
			}, 'Something');
		}, 'Exception');

	}

	function testKeyPathSplit() {

		$object = new stdClass();
		$object->objA = 'hello';
		$object->objB = 'dolly';
		$object->objC = array(
			'x' => 'this',
			'y' => 'is',
			'z' => 'louis',
		);

		$testSource1 = array(
			'a' => array(
				'foo' => 'a',
				'bar' => 192,
				'obj' => $object,
			),
			'b' => 'www',
			'10' => 'gooo'
		);

		$testSource2 = array(1, 2, 3, array('a' => 'A', 'b' => array('x' => 'X', 'y' => 'Y')), 4, 5);

		Assert::same('www', Objects::extractWithKeyPath($testSource1, 'b'));
		Assert::same('gooo', Objects::extractWithKeyPath($testSource1, 10));
		Assert::same(192, Objects::extractWithKeyPath($testSource1, 'a.bar'));
		Assert::same('hello', Objects::extractWithKeyPath($testSource1, 'a.obj.objA'));
		Assert::same('this', Objects::extractWithKeyPath($testSource1, 'a.obj.objC.x'));
		Assert::same($object, Objects::extractWithKeyPath($testSource1, 'a.obj'));
		Assert::same($object->objC, Objects::extractWithKeyPath($testSource1, 'a.obj.objC'));
		Assert::same('dolly', Objects::extractWithKeyPath($object, 'objB'));
		Assert::same('louis', Objects::extractWithKeyPath($object, 'objC.z'));

		Assert::same(3, Objects::extractWithKeyPath($testSource2, 2));
		Assert::same('A', Objects::extractWithKeyPath($testSource2, '3.a'));
		Assert::same('Y', Objects::extractWithKeyPath($testSource2, '3.b.y'));
		Assert::same($testSource2[3]['b'], Objects::extractWithKeyPath($testSource2, '3.b'));

		Assert::exception(function() use ($testSource1) {
			Objects::extractWithKeyPath($testSource1, 'neni');
		}, Exception::class);

		Assert::exception(function() use ($testSource1) {
			Objects::extractWithKeyPath($testSource1, 'a.neni');
		}, Exception::class);

		Assert::exception(function() use ($object) {
			Objects::extractWithKeyPath($object, 'neni');
		}, Exception::class);

		Assert::exception(function() use ($object) {
			Objects::extractWithKeyPath($object, 'objC.neni');
		}, Exception::class);

		Assert::exception(function() use ($object) {
			Objects::extractWithKeyPath($object, 'objB.neniObjekt');
		}, Exception::class);

		Assert::exception(function() use ($testSource1) {
			Objects::extractWithKeyPath($testSource1, 'b.neniPole');
		}, Exception::class);

	}


}


$case = new ObjectsTest();
$case->run();
