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


}


$case = new ObjectsTest();
$case->run();
