<?php

require '../bootstrap.php';

use \Tester\Assert;
use \Tester\TestCase;

use \OndraKoupil\Tools\Html;

class HtmlTestCase extends TestCase {

	public function testProcessLinksInText() {

		// Strip tags test
		$input="Something <b>with tags</b> that <br />Should be removed. ";
		Assert::equal("Something with tags that Should be removed.",  Html::processLinksInText($input, true));
		Assert::equal("Something <b>with tags</b> that <br />Should be removed.",  Html::processLinksInText($input, false));

		// Basic urls test
		$input="Go to http://google.com to see.";
		Assert::equal("Go to <a href=\"http://google.com\">http://google.com</a> to see.",  Html::processLinksInText($input, false, false));
		Assert::equal("Go to <a href=\"http://google.com\" target=\"_blank\">http://google.com</a> to see.",  Html::processLinksInText($input, false, true));

		//Beginning/end of text
		$input="Go to http://google.com";
		Assert::equal("Go to <a href=\"http://google.com\">http://google.com</a>",  Html::processLinksInText($input, false, false));
		$input="http://www.google.com is not Seznam";
		Assert::equal("<a href=\"http://www.google.com\">http://www.google.com</a> is not Seznam",  Html::processLinksInText($input, false, false));
		$input="http://www.google.com";
		Assert::equal("<a href=\"http://www.google.com\">http://www.google.com</a>",  Html::processLinksInText($input, false, false));

		// Adding protocol
		$input="Go to www.google.com to see.";
		Assert::equal("Go to <a href=\"http://www.google.com\">www.google.com</a> to see.",  Html::processLinksInText($input, false, false));

		// Other protocols
		$input="Go to ftp://co.uni.part.gov to see.";
		Assert::equal("Go to <a href=\"ftp://co.uni.part.gov\" target=\"_blank\">ftp://co.uni.part.gov</a> to see.",  Html::processLinksInText($input, false, true));

		// Mail links
		$input="Write me to koupil@animato.cz and i might answer";
		Assert::equal("Write me to <a href=\"mailto:koupil@animato.cz\">koupil@animato.cz</a> and i might answer",  Html::processLinksInText($input, false, false));

		// Multiple links
		$input="Go to www.google.com or www.seznam.cz or http://www.hranostaj.cz to see.";
		Assert::equal("Go to <a href=\"http://www.google.com\">www.google.com</a> or <a href=\"http://www.seznam.cz\">www.seznam.cz</a>"
			." or <a href=\"http://www.hranostaj.cz\">http://www.hranostaj.cz</a> to see.",  Html::processLinksInText($input, false, false));

		// Links with paths
		$input="Go to www.hranostaj.cz/hra123 because it is good";
		Assert::equal("Go to <a href=\"http://www.hranostaj.cz/hra123\">www.hranostaj.cz/hra123</a> because it is good",  Html::processLinksInText($input, false, false));
		$input="Go to www.hranostaj.cz/hra123, but no"; //commonly written
		Assert::equal("Go to <a href=\"http://www.hranostaj.cz/hra123\">www.hranostaj.cz/hra123</a>, but no",  Html::processLinksInText($input, false, false));
		$input="My web (http://www.hranostaj.cz) is cool"; //commonly written
		Assert::equal("My web (<a href=\"http://www.hranostaj.cz\">http://www.hranostaj.cz</a>) is cool",  Html::processLinksInText($input, false, false));
		$input="My web (www.hranostaj.cz) is cool"; //commonly written
		Assert::equal("My web (<a href=\"http://www.hranostaj.cz\">www.hranostaj.cz</a>) is cool",  Html::processLinksInText($input, false, false));
		$input="Go to www.hranostaj.cz/hra123-dome_thing.html?abc=def&ghi=jkl and watch";
		Assert::equal("Go to <a href=\"http://www.hranostaj.cz/hra123-dome_thing.html?abc=def&ghi=jkl\">www.hranostaj.cz/hra123-dome_thing.html?abc=def&ghi=jkl</a> and watch",
			Html::processLinksInText($input, false, false));
		$input="Go to www.hranostaj.cz/hra123-dome_thing.html?abc=def&ghi=jkl%20mezera and watch";
		Assert::equal("Go to <a href=\"http://www.hranostaj.cz/hra123-dome_thing.html?abc=def&ghi=jkl%20mezera\">www.hranostaj.cz/hra123-dome_thing.html?abc=def&ghi=jkl%20mezera</a> and watch",
			Html::processLinksInText($input, false, false));
	}


	function testShortenHtml() {
		$string = "Ředkvička, která <b>měla</b> čupřík žakéř?";

		// Without HTML

		$shortened = Html::shortenHtml($string, 22, "", true, false);
		Assert::same( "Ředkvička, která <b>mě", $shortened );

		$shortened = Html::shortenHtml($string, 22, ",ř,,", true, false);
		Assert::same( "Ředkvička, která <,ř,,", $shortened );

		$shortened = Html::shortenHtml($string, 22, "...", false, false);
		Assert::same( "Ředkvička, která...", $shortened );

		$shortened = Html::shortenHtml($string, 32, "...", false, false);
		Assert::same( "Ředkvička, která <b>měla</b>...", $shortened );

		$shortened = Html::shortenHtml($string, 6, "...", false, false);
		Assert::same( "...", $shortened );

		// With HTML
		$shortened = Html::shortenHtml($string, 27, "...", false, true);
		Assert::same( "Ředkvička, která <b>měla</b>...", $shortened );

		$shortened = Html::shortenHtml($string, 22, "...", true, true);
		Assert::same( "Ředkvička, která <b>mě...</b>", $shortened );

	}

	function testEscape() {

		$string = "Tag ŘŘ <b class='some\"> S&W &amp; &gt; hello";

		$escaped = "Tag ŘŘ &lt;b class=&#039;some&quot;&gt; S&amp;W &amp; &gt; hello";
		$doubleEscaped = "Tag ŘŘ &lt;b class=&#039;some&quot;&gt; S&amp;W &amp;amp; &amp;gt; hello";

		Assert::equal($escaped, Html::escape($string));
		Assert::equal($doubleEscaped, Html::escape($string, true));

	}

	function testDiff() {

		$string1 = "Hello world, the Earth says good morning!";
		$string2 = "Hello starshine, the Earth says hello!";

		$diff = trim(Html::diff($string1, $string2));
		$expected = "Hello <del>world,</del> <ins>starshine,</ins> the Earth says <del>good morning!</del> <ins>hello!</ins>";
		Assert::equal($expected, $diff);

		$diff = trim(Html::diff($string1, $string2, "++", "++", "--", "--"));
		$expected = "Hello --world,-- ++starshine,++ the Earth says --good morning!-- ++hello!++";
		Assert::equal($expected, $diff);

	}

}

$case = new HtmlTestCase();
$case->run();
