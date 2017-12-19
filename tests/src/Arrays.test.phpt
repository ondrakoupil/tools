<?php

require '../bootstrap.php';

use Tester\Assert;
use Tester\TestCase;

use OndraKoupil\Tools\Arrays;
use OndraKoupil\Testing\TraversableTestObject;
use OndraKoupil\Testing\ArrayAccessTestObject;

class ToolsTest extends TestCase {

	function testArrayize() {
		$a=1;
		$b=Arrays::arrayize($a);
		Assert::equal(array(1),$b);

		$a=array(3);
		$b=Arrays::arrayize($a);
		Assert::equal(array(3),$b);

		$a=0;
		$b=Arrays::arrayize($a);
		Assert::equal(array(0),$b);

		$a=false;
		$b=Arrays::arrayize($a);
		Assert::equal(array(),$b);

		$a=null;
		$b=Arrays::arrayize($a);
		Assert::equal(array(),$b);

		$a = new TraversableTestObject();
		$b=Arrays::arrayize($a);
		Assert::same($a,$b);

		$a = new TraversableTestObject(3);
		$b = Arrays::arrayize($a, true);
		Assert::equal(array(0=>0, 1=>1, 2=>2), $b);

		$a = new ArrayAccessTestObject(4);
		$b = Arrays::arrayize($a, false);
		Assert::same($a, $b);

	}

	function testDearrayize() {

		Assert::equal("hello,dolly,this,is,louis", Arrays::dearrayize(array("hello", "dolly", "this", "is", "louis")) );
		Assert::equal("hello", Arrays::dearrayize(array("hello")) );
		Assert::equal("hello", Arrays::dearrayize("hello") );
		Assert::same(2, Arrays::dearrayize(2) );
		Assert::same("hello+dolly", Arrays::dearrayize(array("hello", "dolly"), "+") );

	}


	function testValuesPicker() {
		$arr=array(
			"a"=>array("letter"=>"A","number"=>1),
			"b"=>array("letter"=>"A","number"=>"X"),
			"c"=>array("letter"=>"B","number"=>3),
			"d"=>array("letter"=>"B","number"=>3),
			"e"=>array("letter"=>"A","number"=>false)
		);

		Assert::equal(array("A","B"), Arrays::valuePicker($arr, "letter"));
		Assert::equal(array(1,"X",3,false), Arrays::valuePicker($arr, "number"));

		Assert::equal(
			array("A"),
			Arrays::valuePicker($arr, "letter", array("B"))
		);

		$added = new stdClass();
		$added->letter = "Z";
		$added->number = 10;
		$arr[] = $added;

		Assert::equal(array("A", "B", "Z"), Arrays::valuePicker($arr, "letter"));


	}

	function testFilterByKeys() {

		$source = array(
			"name" => "Ondra",
			"city" => "Hradec",
			"glasses" => true
		);

		Assert::same(
			array("name" => "Ondra", "glasses" => true),
			Arrays::filterByKeys($source, array("name", "glasses", "nonsense") )
		);

		$obj = new ArrayAccessTestObject();
		$obj["name"] = "Ondra";
		$obj["city"] = "Hradec";
		$obj["glasses"] = true;

		$target = array(
			"name" => "Ondra",
			"glasses" => true
		);

		Assert::same(
			array("name" => "Ondra", "glasses" => true),
			Arrays::filterByKeys($obj, array("name", "glasses", "nonsense") )
		);

		Assert::exception(function() {
			Arrays::filterByKeys(10, array(10, 20, 30));
		}, '\InvalidArgumentException');

	}


	function testToObject() {

		$original = array( "message" => "Hello", "type" => 10, "well" => array("a"=>"A", "b"=>"B") );

		$output = Arrays::toObject($original);
		$expected = new \stdClass();
		$expected->message = "Hello";
		$expected->type = 10;
		$expected->well = array("a"=>"A", "b"=>"B");
		Assert::equal(
			$output,
			$expected
		);

		$traversable = new TraversableTestObject(2);
		$output = Arrays::toObject($traversable);
		$expected = new \stdClass();
		$expected->{0} = 0;
		$expected->{1} = 1;

		Assert::equal(
			$output,
			$expected
		);

		Assert::exception(function() {
			Arrays::toObject(10);
		}, '\InvalidArgumentException');

		Assert::exception(function() {
			Arrays::toObject(new \stdClass());
		}, '\InvalidArgumentException');

	}

	function testTransform() {
		$original=array(
			"a"=>array("letter"=>"A","number"=>1,"otherletter"=>"z"),
			"b"=>array("letter"=>"A","number"=>2,"otherletter"=>"x"),
			"c"=>array("letter"=>"B","number"=>3,"otherletter"=>"c"),
			"d"=>array("letter"=>"B","number"=>4,"otherletter"=>"v"),
			"e"=>array("letter"=>"A","number"=>5,"otherletter"=>"b")
		);

		Assert::same(
			array(
				"a"=>"A",
				"b"=>"A",
				"c"=>"B",
				"d"=>"B",
				"e"=>"A"
			),
			Arrays::transform($original, true, "letter")
		);

		Assert::same(
			array(
				0=>"A",
				1=>"A",
				2=>"B",
				3=>"B",
				4=>"A"
			),
			Arrays::transform($original, false, "letter")
		);

		Assert::same(
			array(
				0=>null,
				1=>null,
				2=>null,
				3=>null,
				4=>null
			),
			Arrays::transform($original, false, "nonsense")
		);

		Assert::same(
			array(
				0=>array("letter" => "A", "nonsense" => null),
				1=>array("letter" => "A", "nonsense" => null),
				2=>array("letter" => "B", "nonsense" => null),
				3=>array("letter" => "B", "nonsense" => null),
				4=>array("letter" => "A", "nonsense" => null)
			),
			Arrays::transform($original, false, array("letter", "nonsense"))
		);

		Assert::same(
			array(
				1=>"z",
				2=>"x",
				3=>"c",
				4=>"v",
				5=>"b",
			),
			Arrays::transform($original, "number", "otherletter")
		);

		Assert::same(
			array(
				1=>"a",
				2=>"b",
				3=>"c",
				4=>"d",
				5=>"e",
			),
			Arrays::transform($original, "number", false)
		);

		Assert::same(
			array(
				1=>$original["a"],
				2=>$original["b"],
				3=>$original["c"],
				4=>$original["d"],
				5=>$original["e"],
			),
			Arrays::transform($original, "number", true)
		);

		Assert::same(
			array(
				"A"=>5,
				"B"=>4
			),
			Arrays::transform($original, "letter", "number")
		);

		Assert::same(
			array(
				1=>array("letter"=>"A","otherletter"=>"z","key"=>"a"),
				2=>array("letter"=>"A","otherletter"=>"x","key"=>"b"),
				3=>array("letter"=>"B","otherletter"=>"c","key"=>"c"),
				4=>array("letter"=>"B","otherletter"=>"v","key"=>"d"),
				5=>array("letter"=>"A","otherletter"=>"b","key"=>"e"),
			),
			Arrays::transform($original, "number", array("letter","otherletter",false))
		);

	}


	public function testDeleteValue() {

		// Keys insignificant
		$dataArray = array("a", "b", "C", "a", "D");
		Assert::same(
			array(0=>"b", 1=>"C", 2=>"D"),
			Arrays::deleteValue($dataArray, "a")
		);

		// Keys significant
		$dataArray = array("a", "b", "C", "a", "D");
		Assert::same(
			array(1=>"b", 2=>"C", 4=>"D"),
			Arrays::deleteValue($dataArray, "a", false)
		);

		// Non-strict
		$dataArray = array(10, "2", 2, "40");
		Assert::same(
			array(0=>10, 1=>"40"),
			Arrays::deleteValue($dataArray, 2, true, false)
		);

		// Strict
		$dataArray = array(10, "2", 2, "40");
		Assert::same(
			array(0=>10, 1=>"2", 2=>"40"),
			Arrays::deleteValue($dataArray, 2, true, true)
		);

		// Not found
		$dataArray = array(1, 2, "3", 4);
		Assert::same(
			$dataArray,
			Arrays::deleteValue($dataArray, 100)
		);

		// Clear all
		$dataArray = array(0, "", false);
		Assert::same(
			array(),
			Arrays::deleteValue($dataArray, 0)
		);

		// Null is invalid
		Assert::exception(function() {
			Arrays::deleteValue(array(1, 2, 3), null);
		}, '\InvalidArgumentException');

	}

	public function testGroup() {
		$input=array(
			array("master"=>"A","value"=>1),
			array("master"=>"C","value"=>6),
			array("master"=>"B","value"=>2),
			array("master"=>"B","value"=>3),
			array("master"=>"B","value"=>4),
			array("master"=>"A","value"=>5),
			array("master"=>"B","value"=>7),
			array("master"=>"A","value"=>8),
			array("master"=>"B","value"=>9)
		);

		$out=Arrays::group($input, "master");
		$valuesInA=Arrays::valuePicker($out["A"], "value");
		$valuesInB=Arrays::valuePicker($out["B"], "value");
		$valuesInC=Arrays::valuePicker($out["C"], "value");
		Assert::same(array(1,5,8), $valuesInA);
		Assert::same(array(2,3,4,7,9), $valuesInB);
		Assert::same(array(6), $valuesInC);

		Assert::same(array("A","C","B"), array_keys($out) );

		$out2=Arrays::group($input, "master", true);
		Assert::same(array("A","B","C"), array_keys($out2) );

		$out3=Arrays::group($input, "master", "desc");
		Assert::same(array("C","B","A"), array_keys($out3) );

		$out4 = Arrays::group($input, "nonsense");
		Assert::equal(count($out4[0]), count($input));
	}

	public function testFlatten() {
		$arr=array(
			1=>array(5, 10, 11),
			2=>array(9,14),
			6=>array(20)
		);

//		Testing\TestTools::assertArrayEqual(array(5,10,11,9,14,20), Tools::arrayFlatten($arr));
		Assert::equal(array(5,10,11,9,14,20), Arrays::flatten($arr));

		$arr=array(
			1=>array(1,3,4),
			10=>array(1,2,4,8),
			19=>array(5,8,3)
		);

//		Testing\TestTools::assertArrayEqual(array(1,2,3,4,5,8), Tools::arrayFlatten($arr));
		Assert::equal(array(1,3,4,2,8,5), Arrays::flatten($arr));
	}

	public function testEnrich() {

		$mainArray=array(
			1=>array("name"=>"Jednička"),
			2=>array("name"=>"Dvojka"),
			3=>array("name"=>"Trojka"),
			5=>array("name"=>"Pětka")
		);

		$mixinArray=array(
			1=>array("even"=>0,"english"=>"One"),
			2=>array("even"=>1,"english"=>"Two"),
			3=>array("even"=>0,"english"=>"Three"),
			4=>array("even"=>1,"english"=>"Four")
		);

		$result=Arrays::enrich($mainArray, $mixinArray, "even");

		Assert::equal(4, count($result));
		Assert::equal(2, count($result[1]));
		Assert::equal(1, count($result[5]));
		Assert::false(isset($result[4]));
		Assert::equal(1, $result[2]["even"]);


		$result=Arrays::enrich($mainArray, $mixinArray, array("even", "nonsense"));

		Assert::equal(array("name" => "Dvojka", "even" => 1, "nonsense" => null), $result[2]);


		$result=Arrays::enrich($mainArray, $mixinArray);

		Assert::equal(4, count($result));
		Assert::equal(3, count($result[1]));
		Assert::equal(1, count($result[5]));
		Assert::false(isset($result[4]));
		Assert::equal(1, $result[2]["even"]);
		Assert::equal("Two", $result[2]["english"]);

		// Překlady klíčů

		$result = Arrays::enrich($mainArray, $mixinArray, true, array("english"=>"alt"));

		Assert::equal("Two", $result[2]["alt"]);
		Assert::false(isset($result[2]["english"]));

		$result = Arrays::enrich($mainArray, $mixinArray, true, array("english"=>"alt", "nonsense" => "othernonsense"));

		Assert::equal(array("name" => "Dvojka", "even" => 1, "alt" => "Two", "othernonsense" => null), $result[2]);

	}

	public function testNormaliseValues() {
		$values=array(2,1,5,0,4,1,1);
		$out=Arrays::normaliseValues($values);
		Assert::equal(array(0.4,0.2,1,0,0.8,0.2,0.2), $out);

		$values=array(2,1);
		$out=Arrays::normaliseValues($values);
		Assert::equal(array(1,0), $out);

		$values=array(-10,2,8,-4,0,10);
		$out=Arrays::normaliseValues($values);
		Assert::equal(array(0,0.6,0.9,0.3,0.5,1), $out);

		$values=array(2,2,2);
		$out=Arrays::normaliseValues($values);
		Assert::equal(array(1,1,1), $out);

		$values=array(2);
		$out=Arrays::normaliseValues($values);
		Assert::equal(array(1), $out);

		$values=array();
		$out=Arrays::normaliseValues($values);
		Assert::equal(array(), $out);

		$values=100;
		$out=Arrays::normaliseValues($values);
		Assert::equal(array(1), $out);

	}

	public function testArraySortByExternalKeys() {
		$dataValues = array("d"=>"Déčko","a"=>"Áčko", "c"=>"Céčko", "e"=>"Éčko", "b"=>"Béčko");
		$correct1DataValues = array("a"=>"Áčko", "b"=>"Béčko", "c"=>"Céčko", "d"=>"Déčko", "e"=>"Éčko");
		$correct2DataValues = array("a"=>"Áčko", "b"=>"Béčko", "e"=>"Éčko", "c"=>"Céčko", "d"=>"Déčko");

		Assert::same($correct1DataValues, Arrays::sortByExternalKeys($dataValues, array("a", "b", "c", "d", "e")));
		Assert::same($correct2DataValues, Arrays::sortByExternalKeys($dataValues, array("a", "b", "e", "c", "d")));

		Assert::same(
			array("x"=>null, "a"=>"Áčko", "t"=>null, "y"=>null),
			Arrays::sortByExternalKeys($dataValues, array("x", "a", "t", "y"))
		);
	}

	public function testDeleteValues() {

		// Keys insignificant
		$source = array("a", "b", "a", "c", "a", "d");
		$out = Arrays::deleteValues($source, array("a", "b"), true);
		Assert::equal(
			array(0 => "c", 1 => "d"),
			$out
		);

		// Keys significant
		$source = array("a", "b", "a", "c", "a", "d");
		$out = Arrays::deleteValues($source, array("a", "b"), false);
		Assert::equal(
			array(3 => "c", 5 => "d"),
			$out
		);

		// Needs arrayize
		$source = array("a", "b", "a", "c", "a", "d");
		$out = Arrays::deleteValues($source, "a");
		Assert::equal(
			array("b", "c", "d"),
			$out
		);

		// Error because of non-scalar argument
		Assert::error(function() use ($source) {
			Arrays::deleteValues($source, array("0", "2", array("a")));
		}, E_NOTICE);
	}


	function testEnumItem() {
		$data=array(
			1=>"Nízká",
			2=>"Nižší",
			3=>"Střední",
			4=>"Vyšší",
			5=>"Vysoká"
		);

		$out=Arrays::enumItem($data, 2);
		Assert::equal("Nižší", $out);

		$out=Arrays::enumItem($data, false);
		Assert::equal(5, count($out));
		Assert::equal("Vyšší", $out[4]);

		$out=Arrays::enumItem($data, 3, "%index% je %value%, i je %i%");
		Assert::equal("3 je Střední, i je ", $out);

		$out=Arrays::enumItem($data, false, "%index% je %value%, i je %i%");
		Assert::equal(5, count($out));
		Assert::equal("5 je Vysoká, i je 4", $out[5]);
		Assert::equal("2 je Nižší, i je 1", $out[2]);

		$out=Arrays::enumItem($data, 5, false, 2);
		Assert::equal("Vysoká", $out);

		$out=Arrays::enumItem($data, 6, false, 2);
		Assert::equal("Nižší", $out);

		$out=Arrays::enumItem($data, 200, false, 100);
		Assert::equal(100, $out);

		$out=Arrays::enumItem($data, false, "%index%", 0, true);
		Assert::same( array(5=>"5",4=>"4",3=>"3",2=>"2",1=>"1"), $out );


	}

	function testTraversableToArray() {

		$objSub = new TraversableTestObject(3);
		$objMain = new TraversableTestObject(2, $objSub);

		$out = Arrays::traversableToArray($objMain);

		Assert::equal(array(
			0 => array(0=>0, 1=>1, 2=>2),
			1 => array(0=>0, 1=>1, 2=>2)
		), $out);


		Assert::exception(function() {
			Arrays::traversableToArray("ahoj");
		}, '\InvalidArgumentException');

		Assert::exception(function() use ($objSub) {
			Arrays::traversableToArray($objSub, 11);
		}, '\RuntimeException');

	}

	function testCompareValues() {

		Assert::false(Arrays::compareValues(
			array("A"),
			array("A", "B", "C")
		));

		Assert::true(Arrays::compareValues(
			array("A", "B", "C") ,
			array("A", "B", "C")
		));

		Assert::true(Arrays::compareValues(
			array("A", "B", "C") ,
			array("C", "A", "B")
		));

		Assert::true(Arrays::compareValues(
			array("A", "3", "C") ,
			array("C", 3, "A")
		));

		Assert::true(Arrays::compareValues(
			array("A", "", "C") ,
			array("C", false, "A")
		));


		Assert::false(Arrays::compareValues(
			array("A", "3", "C") ,
			array("C", 3, "B"),
			true
		));

	}

	function testIconv() {

		$win1250 = iconv("utf-8", "windows-1250", "Příliš žluťoučký kůň");
		$utf8 = "Příliš žluťoučký kůň";

		$winArray = explode(" ", $win1250);
		$utfArray = explode(" ", $utf8);

		// Basic conversion
		Assert::equal($winArray, Arrays::iconv("utf-8", "windows-1250", $utfArray));
		Assert::equal($utfArray, Arrays::iconv("windows-1250", "utf-8", $winArray));
		Assert::equal($win1250, implode(" ", Arrays::iconv("utf-8", "windows-1250", Arrays::iconv("windows-1250", "utf-8", $winArray))));

		// Recursive
		$bigArrayWin = array($winArray, $winArray);
		$bigArrayUtf = Arrays::iconv("windows-1250", "utf-8", $bigArrayWin);

		Assert::equal("žluťoučký", $bigArrayUtf[0][1]);
		Assert::equal("kůň", $bigArrayUtf[1][2]);

		// Convert keys
		$combinedWin = array_combine($winArray, $winArray);
		$converted = Arrays::iconv("windows-1250", "utf-8", $combinedWin, true);
		Assert::equal(array_keys($converted), $utfArray);
		Assert::equal(array_values($converted), $utfArray);

		// Non-array conversions
		Assert::equal($win1250, Arrays::iconv("utf-8", "windows-1250", $utf8));
		Assert::equal(array($win1250, 10), Arrays::iconv("utf-8", "windows-1250", array($utf8, 10)));

		$obj = new stdClass();
		$obj->a = "Žluťoučký kůň";
		$obj->b = 10;
		Assert::equal("Žluťoučký kůň", Arrays::iconv("utf-8", "windows-1250", $obj)->a );

	}



	function testCartesian() {

		// Exact test
		$numbers = array(100, 256, "245.4");
		$letters = array("O", "K");

		$array = array(
			"number" => $numbers,
			"letter" => $letters
		);

		$output = Arrays::cartesian($array);
		$expected = array(
			array("number" => 100, "letter" => "O"),
			array("number" => 100, "letter" => "K"),
			array("number" => 256, "letter" => "O"),
			array("number" => 256, "letter" => "K"),
			array("number" => "245.4", "letter" => "O"),
			array("number" => "245.4", "letter" => "K")
		);

		Assert::equal($expected, $output);

		// Counts test

		$arr1 = range(1, 5);
		$arr2 = range(10, 13);
		$arr3 = range(3, 5);

		$input = array( $arr1, $arr2, $arr3 );
		$output = Arrays::cartesian($input);

		Assert::equal(count($output), count($arr1) * count($arr2) * count($arr3));

	}


	function testIsNumeric() {

		Assert::true( Arrays::isNumeric( array(1, 2, 3) ) );
		Assert::true( Arrays::isNumeric( array("hello", "Dolly", 2, 3, 10, "this", "is", "Louis") ) );
		Assert::true( Arrays::isNumeric( array() ) );
		Assert::false( Arrays::isNumeric( array( "name" => "One", "number" => 1) ) );
		Assert::false( Arrays::isNumeric( array( "name" => "One", "number" => 1, 3, 10, "something other") ) );

	}

	function testIsAssoc() {
		Assert::false( Arrays::isAssoc( array(1, 2, 3) ) );
		Assert::false( Arrays::isAssoc( array("hello", "Dolly", 2, 3, 10, "this", "is", "Louis") ) );
		Assert::true( Arrays::isAssoc( array() ) );
		Assert::true( Arrays::isAssoc( array( "name" => "One", "number" => 1) ) );
		Assert::true( Arrays::isAssoc( array( "name" => "One", "number" => 1, 3, 10, "something other") ) );
	}


	function testDiff() {

		$array1 = array("x", "a", "b", "c");
		$array2 = array("a", "d", "b", "c", "y");

		$diff = Arrays::diff($array1, $array2);

		Assert::equal(6, count($diff));

		Assert::equal("a", $diff[1]);
		Assert::equal("d", $diff[2]["i"][0]);
		Assert::equal("b", $diff[3]);
		Assert::equal("c", $diff[4]);

	}

	function testIndexByKey() {

		// Standard run

		$input = array(
			array("id" => 1, "name" => "One"),
			array("id" => 10, "name" => "Ten"),
			array("id" => 100, "name" => "Hundred"),
		);

		$output = Arrays::indexByKey($input, "id");

		Assert::same(array(
			1 => array("id" => 1, "name" => "One"),
			10 => array("id" => 10, "name" => "Ten"),
			100 => array("id" => 100, "name" => "Hundred")
		), $output);



		// Invalid input
		Assert::exception(function () {
			Arrays::indexByKey("Normal string", "id");
		}, 'InvalidArgumentException');


		// Some input objects does not have required property

		$input = array(
			array("id" => 1, "name" => "One"),
			array("id" => 10, "name" => "Ten"),
			array("name" => "Hundred"),
		);

		$output = Arrays::indexByKey($input, "id");

		Assert::same(array(
			1 => array("id" => 1, "name" => "One"),
			10 => array("id" => 10, "name" => "Ten"),
			2 => array("name" => "Hundred")
		), $output);


		$input = array(
			array("id" => 1, "name" => "One"),
			array("id" => 10, "name" => "Ten"),
			16 => array("name" => "Hundred"),
		);

		$output = Arrays::indexByKey($input, "id");

		Assert::same(array(
			1 => array("id" => 1, "name" => "One"),
			10 => array("id" => 10, "name" => "Ten"),
			16 => array("name" => "Hundred")
		), $output);
		

		// Some input objects are not arrays/objects - owerwrite must be done well
		$input = array(
			2 => "xxx",
			array("id" => 2, "name" => "Two"),
			array("id" => 10, "name" => "Ten")
		);

		$output = Arrays::indexByKey($input, "id");

		Assert::same(array(
			2 => array("id" => 2, "name" => "Two"),
			10 => array("id" => 10, "name" => "Ten")
		), $output);


		$input = array(
			array("id" => 2, "name" => "Two"),
			array("id" => 10, "name" => "Ten"),
			2 => "xxx"
		);

		$output = Arrays::indexByKey($input, "id");

		Assert::same(array(
			2 => array("id" => 2, "name" => "Two"),
			10 => array("id" => 10, "name" => "Ten")
		), $output);

	}

}


$case = new ToolsTest();
$case->run();
