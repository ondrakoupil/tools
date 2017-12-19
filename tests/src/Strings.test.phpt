<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require '../bootstrap.php';

use OndraKoupil\Testing\ArrayAccessTestObject;
use \Tester\Assert;
use \Tester\TestCase;

use OndraKoupil\Tools\Strings;


class StringsTestCase extends TestCase {

	function getPluralArgs() {
		return array(
			array(2, "2 dny"),
			array(1, "1 den"),
			array(6, "6 dnů"),
			array(0, "0 dnů")
		);
	}

	/**
	 * @dataProvider getPluralArgs
	 */
	function testPlural($arg, $expected) {

		Assert::equal( $expected, Strings::plural($arg, "%% den", "%% dny", "%% dnů") );

	}

	function testPluralSpecialZero() {

		Assert::equal( "nic", Strings::plural(0, "%% den", "%% dny", "%% dnů", "nic") );

		Assert::equal( "4 stavení", Strings::plural(4, "%% stavení") );
		Assert::equal( "6 staveních", Strings::plural(6, "%% stavení", "%% staveních") ); // ano, jsem buran

	}



	function testStrlen() {
		Assert::equal(16, Strings::strlen("Příliš žluťoučký"));
		Assert::equal(16, Strings::length("Příliš žluťoučký"));
	}

	function testLowerUpper() {
		Assert::equal("příliš", Strings::lower("Příliš"));
		Assert::equal("příliš", Strings::strToLower("Příliš"));
		Assert::equal("PŘÍLIŠ", Strings::upper("Příliš"));
		Assert::equal("PŘÍLIŠ", Strings::strToUpper("Příliš"));
	}


	function getSubstrArgs() {
		return array(
			array("čřž", 2, 3),
			array("ěš", 0, 2),
			array("řžý", 3),
			array("žý", -2),
			array("ěščř", 0, -2)
		);
	}

	/**
	 * @dataProvider getSubstrArgs
	 */
	function testSubstr($expected, $arg1, $arg2 = null) {
		$str = "ěščřžý";

		Assert::equal($expected, Strings::substr($str, $arg1, $arg2));
		Assert::equal($expected, Strings::substring($str, $arg1, $arg2));
	}


	function testContains() {
		Assert::true( Strings::contains("Psisko", "Psi") );
		Assert::true( Strings::contains("Psisko", "isk") );
		Assert::false( Strings::contains("Psisko", "SIs") );
		Assert::true( Strings::icontains("Psisko", "SIs") );
	}


	function testBr2nl() {
		Assert::equal("jeden\ndruhý", Strings::br2nl("jeden<br />druhý"));
		Assert::equal("jeden\ndruhý\ntřetí", Strings::br2nl("jeden<br />druhý<br>třetí"));
		Assert::equal("jeden\ndruhý\ntřetí", Strings::br2nl("jeden<BR  />druhý<BR>třetí"));
	}

	function testNl2br() {
		Assert::equal("jeden<br />druhý", Strings::nl2br("jeden\ndruhý"));
		Assert::equal("jeden<br />druhý<br />třetí", Strings::nl2br("jeden\ndruhý\r\ntřetí"));
	}

	function testShorten() {
		$str = "Jeďňó ďřúhé třetí čtvrté slovo";
		Assert::equal("Jeďňó ďřúhé", Strings::shorten($str, 13, ""));

		$str2 = "One two three four five six seven eight";
		Assert::equal("One two", Strings::shorten($str2, 7, ""));
		Assert::equal("One two", Strings::shorten($str2, 8, ""));
		Assert::equal("One two", Strings::shorten($str2, 9, ""));
		Assert::equal("One two", Strings::shorten($str2, 10, ""));
		Assert::equal("One two", Strings::shorten($str2, 11, ""));
		Assert::equal("One two", Strings::shorten($str2, 12, ""));
		Assert::equal("One two three", Strings::shorten($str2, 13, ""));
		Assert::equal("One two three", Strings::shorten($str2, 14, ""));
		Assert::equal("One two three", Strings::shorten($str2, 15, ""));
		Assert::equal("One two three", Strings::shorten($str2, 16, ""));
		Assert::equal("One two three", Strings::shorten($str2, 17, ""));
		Assert::equal("One two three four", Strings::shorten($str2, 19, ""));
		Assert::equal("One two three four", Strings::shorten($str2, 20, ""));

		Assert::equal($str2, Strings::shorten($str2, 1000, "..."));

		Assert::equal("One twoxxx", Strings::shorten($str2, 7, "xxx"));

		$str3 = "<b>One two</b> three four";;
		Assert::equal("One two...", Strings::shorten($str3, 8, "..."));

	}

	function testReplaceEntities() {
		$str = "Lorem %a% %b% %c% %b %c ipsum !a!";

		$repl = Strings::replaceEntities($str, array("a" => "AAA", "c" => "CCC", "d" => "DDD"));
		Assert::equal("Lorem AAA %b% CCC %b %c ipsum !a!", $repl);

		$repl = Strings::replaceEntities($str, array("a" => "AAA", "c" => "CCC", "d" => "DDD"), null, "!");
		Assert::equal("Lorem %a% %b% %c% %b %c ipsum AAA", $repl);

		$str = "Lorem %a% %b% %c% %b %c ipsum !a!";
		$repl = Strings::replaceEntities($str, array("a" => null, "b "=>"BBB", "b" => "BXB", "d" => "DDD"), null, "%", '^%');
		Assert::equal("Lorem  BXB %c% BBBc ipsum !a!", $repl);

		$str = "Lorem %a% %b% %c%";

		$repl = Strings::replaceEntities($str, array("a" => "<a>", "c" => "</a>"), null);
		Assert::equal("Lorem <a> %b% </a>", $repl);

		$repl = Strings::replaceEntities($str, array("a" => "<a>", "c" => "</a>"));
		Assert::equal("Lorem &lt;a&gt; %b% &lt;/a&gt;", $repl);

		$repl = Strings::replaceEntities($str, array("a" => 2.8, "c" => 10.4), "round");
		Assert::equal("Lorem 3 %b% 10", $repl);

	}

	function testReplaceEntitesWithObject() {

		$str = "Lořém %a% %b% %c% %b %c ipsum !a!";

		$arrayObject = new ArrayAccessTestObject(array('a' => 'bař', 'b' => 10));
		$repl = Strings::replaceEntities($str, $arrayObject);
		Assert::equal("Lořém bař 10 %c% %b %c ipsum !a!", $repl);

		$obj = new stdClass();
		$obj->a = 'fÁČo';
		$obj->c = 100;

		$repl = Strings::replaceEntities($str, $obj);
		Assert::equal("Lořém fÁČo %b% 100 %b %c ipsum !a!", $repl);



	}

	function testParsePhpNumber() {

		Assert::equal(100, Strings::parsePhpNumber(100));
		Assert::equal(100, Strings::parsePhpNumber("100"));
		Assert::equal(100.0, Strings::parsePhpNumber("100,0 "));
		Assert::equal(100.0, Strings::parsePhpNumber(" 100.0"));
		Assert::equal(1024, Strings::parsePhpNumber("1k"));
		Assert::equal(1024 * (-1.23), Strings::parsePhpNumber("-1,23k "));
		Assert::equal(1024, Strings::parsePhpNumber("1K"));
		Assert::equal(1024*1024*2.5, Strings::parsePhpNumber("2.5M"));
		Assert::equal(1024*1024*2.5, Strings::parsePhpNumber("2,5m "));
		Assert::equal(1024*1024*1024*5, Strings::parsePhpNumber("5G"));
		Assert::equal(1024*1024*1024*1024*8, Strings::parsePhpNumber(" 8T"));

		Assert::same(false, Strings::parsePhpNumber(" 8D"));
		Assert::same(false, Strings::parsePhpNumber(" FIJDFOA"));
		Assert::same(false, Strings::parsePhpNumber("1M4K"));

	}

	function testPhoneNumberFormatter() {

		$input = "721374431";
		Assert::equal( "+420721374431", Strings::phoneNumberFormatter($input) );
		Assert::equal( "+420 721 374 431", Strings::phoneNumberFormatter($input, true, true) );
		Assert::equal( "721 374 431", Strings::phoneNumberFormatter($input, false, true) );
		Assert::equal( "721374431", Strings::phoneNumberFormatter($input, false, false) );
		Assert::equal( "721-374-431", Strings::phoneNumberFormatter($input, false, "-") );
		Assert::equal( "+420/721/374/431", Strings::phoneNumberFormatter($input, true, "/") );
		Assert::equal( "420721374431", Strings::phoneNumberFormatter($input, true, false, "") );
		Assert::equal( "00420 721 374 431", Strings::phoneNumberFormatter($input, true, true, "00") );
		Assert::equal( "721 374 431", Strings::phoneNumberFormatter($input, false, true, "00") );
		Assert::equal( "+123 721 374 431", Strings::phoneNumberFormatter($input, true, true, "+", "123") );

		$input = "721/37 44 31";
		Assert::equal( "+420 721 374 431", Strings::phoneNumberFormatter($input, true, true) );
		Assert::equal( "721374431", Strings::phoneNumberFormatter($input, false, false) );
		Assert::equal( "721 374 431", Strings::phoneNumberFormatter($input, false, true) );

		$input = "27916";
		Assert::equal( "27 916", Strings::phoneNumberFormatter($input, false, true) );
		Assert::equal( "+42027916", Strings::phoneNumberFormatter($input, true, false) );

		$input = "+420721374431";
		Assert::equal( "721374431", Strings::phoneNumberFormatter($input, false, false) );
		Assert::equal( "721 374 431", Strings::phoneNumberFormatter($input, false, true) );
		Assert::equal( "+420 721 374 431", Strings::phoneNumberFormatter($input, true, true) );

		$input = "00420721374431";
		Assert::equal( "721374431", Strings::phoneNumberFormatter($input, false, false) );
		Assert::equal( "721 374 431", Strings::phoneNumberFormatter($input, false, true) );
		Assert::equal( "+00420 721 374 431", Strings::phoneNumberFormatter($input, true, true) );

		$input = "";
		Assert::equal( "", Strings::phoneNumberFormatter($input, false, false) );
		Assert::equal( "", Strings::phoneNumberFormatter($input, true, false) );
		Assert::equal( "", Strings::phoneNumberFormatter($input, true, true) );
		Assert::equal( "", Strings::phoneNumberFormatter($input, false, true) );

	}


	function testNumber() {

		Assert::same(110.3, Strings::number(" 110,3"));
		Assert::same(10, Strings::number(" nonsense", 10));
		Assert::same(10, Strings::number(" -10", 10, true));

	}

	function testNumberOnly() {
		Assert::same("10;20", Strings::numberOnly(" a10,20", ",", ";"));
	}


	function testFormatSize() {
		Assert::equal("1 B", Strings::formatSize(1));
		Assert::equal("1 kB", Strings::formatSize(1024));
		Assert::equal("1.5 kB", Strings::formatSize(1024 * 1.5));
		Assert::equal("1.9 kB", Strings::formatSize(1024 * 1.9));
		Assert::equal("2 kB", Strings::formatSize(1024 * 1.9, 0));
		Assert::equal("2 MB", Strings::formatSize(1024 * 1024 * 1.942, 0));
		Assert::equal("1.94 MB", Strings::formatSize(1024 * 1024 * 1.942, 2));
		Assert::equal("1.942 MB", Strings::formatSize(1024 * 1024 * 1.942, 3));
		Assert::equal("1.95 MB", Strings::formatSize(1024 * 1024 * 1.948, 2));
		Assert::equal("1.26 GB", Strings::formatSize(1024 * 1024 * 1024 * 1.256, 2));
		Assert::equal("100 TB", Strings::formatSize(1024 * 1024 * 1024 * 1024 * 100, 2));
	}

	function testToAscii() {
		Assert::equal("ZlutoUCKy kun!", Strings::toAscii("ŽluťoUČKý kůň!"));
	}

	function testWebalize() {
		Assert::equal("zlutoucky-kun", Strings::webalize("ŽluťoUČKý kůň!"));
		Assert::equal("ZlutoUCKy-kun", Strings::webalize("ŽluťoUČKý kůň!", null, false));

		Assert::equal("zlutoucky-kun", Strings::webalize("ŽluťoUČKý, !-? kůň!"));

		Assert::equal("zlutoucky-kun", Strings::webalize("ŽluťoUČKý. kůň!"));
		Assert::equal("zlutoucky{-kun!", Strings::webalize("ŽluťoUČKý{,* kůň!,", "!{"));
	}

	function testSafe() {
		Assert::equal("zlutoucky-kun.jpg", Strings::safe("žluťoučký kůň.jpg", true));
		Assert::equal("zlutoucky-kun-jpg", Strings::safe("žluťoučký kůň.jpg", false));
	}

	function testSpecChars() {
		$input = "Toto <b style='url:(\"some\")'>je tučně & hezky";
		Assert::equal("Toto &lt;b style=&#039;url:(&quot;some&quot;)&#039;&gt;je tučně &amp; hezky", Strings::specChars($input));
	}

}


$case = new StringsTestCase();
$case->run();
