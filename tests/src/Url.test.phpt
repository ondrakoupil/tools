<?php

require '../bootstrap.php';

use \Tester\Assert;
use \Tester\TestCase;

use OndraKoupil\Tools\Url;

class UrlTestCase extends TestCase {



	function testUrlBuilder() {

		$orig = "http://www.hranostaj.cz/cosi.html?a=10&b=20";

		$actual = Url::builder(array("c"=>12), $orig);
		Assert::equal("http://www.hranostaj.cz/cosi.html?a=10&b=20&c=12", $actual);

		$actual = Url::builder(array("c"=>12), $orig, true);
		Assert::equal("http://www.hranostaj.cz/cosi.html?c=12", $actual);

		$actual = Url::builder(array("c"=>array(1=>"aa", 4=>"bb")), $orig);
		Assert::equal("http://www.hranostaj.cz/cosi.html?a=10&b=20&c[1]=aa&c[4]=bb", $actual);

		$actual = Url::builder(array("c"=>array(1=>"aa", 4=>"bb")), $orig, true);
		Assert::equal("http://www.hranostaj.cz/cosi.html?c[1]=aa&c[4]=bb", $actual);

		$orig2 = "http://www.hranostaj.cz/cosi.html?bez[]=10&bez[]=20&s[4]=1&s[kk]=kkk&ord=5";

		$actual = Url::builder(array("next"=>100, "nextarr" => array(1=>"Z", 4=>"X")), $orig2);
		Assert::equal("http://www.hranostaj.cz/cosi.html?bez[0]=10&bez[1]=20&s[4]=1&s[kk]=kkk&ord=5&next=100&nextarr[1]=Z&nextarr[4]=X", $actual);

		$actual = Url::builder(array("next"=>100, "nextarr" => array(1=>"Z", 4=>"X")), $orig2, true);
		Assert::equal("http://www.hranostaj.cz/cosi.html?next=100&nextarr[1]=Z&nextarr[4]=X", $actual);

		$orig3 = "http://www.hranostaj.cz/cosi.html?a[3]=10&a[8]=11";

		$actual = Url::builder(array("a"=>array(11=>"24", 16=>"29")), $orig3, false, true);
		Assert::equal("http://www.hranostaj.cz/cosi.html?a[11]=24&a[16]=29", $actual);

		$actual = Url::builder(array("a"=>array(11=>"24", 16=>"29")), $orig3, false, false);
		Assert::equal("http://www.hranostaj.cz/cosi.html?a[3]=10&a[8]=11&a[11]=24&a[16]=29", $actual);

		$orig4 = "http://www.hranostaj.cz/cosi.html?a[slovo]=10&a[slovo2]=11&b[]=a&b[]=b&c=10";

		$actual = Url::builder(array("a"=>array("veta"=>"z"), "xx"=>"ppp"), $orig4, true);
		Assert::equal("http://www.hranostaj.cz/cosi.html?a[veta]=z&xx=ppp", $actual);

		$actual = Url::builder(array("a"=>array("veta"=>"z"), "xx"=>"ppp"), $orig4, false, false);
		Assert::equal("http://www.hranostaj.cz/cosi.html?a[slovo]=10&a[slovo2]=11&a[veta]=z&b[0]=a&b[1]=b&c=10&xx=ppp", $actual);

		$actual = Url::builder(array("a"=>array("veta"=>"z"), "xx"=>"ppp"), $orig4, false, true);
		Assert::equal("http://www.hranostaj.cz/cosi.html?a[veta]=z&b[0]=a&b[1]=b&c=10&xx=ppp", $actual);


		$actual = Url::builder(array("param"=>"%id%", "value"=>"%val%"), "http://www.hranostaj.cz");
		Assert::equal("http://www.hranostaj.cz?param=%25id%25&value=%25val%25", $actual);

		$actual = Url::builder(array("param"=>"%id%", "value"=>"%val%"), "http://www.hranostaj.cz", false, false, "%id%");
		Assert::equal("http://www.hranostaj.cz?param=%id%&value=%25val%25", $actual);

		$actual = Url::builder(array("param"=>"%id%", "value"=>"%val%"), "http://www.hranostaj.cz", false, false, array("%id%", "%val%"));
		Assert::equal("http://www.hranostaj.cz?param=%id%&value=%val%", $actual);

	}


	function testAbsolutize() {

		$str = "Click to <a href='mailto:koupil@optimato.cz'>Mail</a> or <a href='contacts.html'>link</a> or <a href='https://www.cz'>abs link</a> on image: <img src='imgs/img.png'>. Enjoy!";
		$absStr = "Click to <a href='mailto:koupil@optimato.cz'>Mail</a> or <a href='http://thisweb.cz/contacts.html'>link</a> or <a href='https://www.cz'>abs link</a> on image: <img src='http://thisweb.cz/imgs/img.png'>. Enjoy!";

		Assert::equal($absStr, Url::absolutize($str, "http://thisweb.cz/"));

	}

	function testHumanize() {

		$url = 'https://www.hranostaj.cz/hra1223';
		Assert::same('www.hranostaj.cz', Url::humanize($url));

		$url = 'https://www.hranostaj.cz/hra1223/some/other/stuff';
		Assert::same('www.hranostaj.cz', Url::humanize($url));

		$url = 'http://hranostaj.cz/hra1223/some?params=that&are=very%12compli%20%39cated';
		Assert::same('hranostaj.cz', Url::humanize($url));

		$url = 'ftp://123.11.13.23/folder/file';
		Assert::same('123.11.13.23', Url::humanize($url));

		$url = 'https://seznam.cz';
		Assert::same('seznam.cz', Url::humanize($url));

		$url = 'https://praha.prodejna.cz';
		Assert::same('praha.prodejna.cz', Url::humanize($url));

		$url = 'praha.prodejna.cz/kontakty';
		Assert::same('praha.prodejna.cz', Url::humanize($url));

		$url = 'https://seznam.cz?utm=campaign';
		Assert::same('seznam.cz', Url::humanize($url));

		$url = 'idnes.cz/novinky/blaboly/pakarny';
		Assert::same('idnes.cz', Url::humanize($url));

		$url = 'idnes.cz?kecy&skvar&reklamy';
		Assert::same('idnes.cz', Url::humanize($url));

		$url = 'www.novinky.cz';
		Assert::same('www.novinky.cz', Url::humanize($url));

		$url = 'hranostaj.cz';
		Assert::same('hranostaj.cz', Url::humanize($url));

		$url = 'asdfasfasdf';
		Assert::same('asdfasfasdf', Url::humanize($url));

		$url = 'https://t.co/abcde11234';
		Assert::same('t.co/abcde11234', Url::humanize($url));

		$url = 'https://bit.ly/xx918273';
		Assert::same('bit.ly/xx918273', Url::humanize($url));

		$url = 'https://bit.ly/xx918273';
		Assert::same('bit.ly', Url::humanize($url, 2));

		$url = 'https://www.hranostaj.cz/hra1234';
		Assert::same('www.hranostaj.cz/hra1234', Url::humanize($url, 20));

	}

	function testTechnicalize() {

		$url = 'hranostaj.cz';
		Assert::same('https://hranostaj.cz', Url::technicalize($url));

		$url = 'hranostaj.cz';
		Assert::same('ftp://hranostaj.cz', Url::technicalize($url, 'ftp'));

		$url = 'http://www.hranostaj.cz/xxssyy';
		Assert::same($url, Url::technicalize($url, 'ftp'));

		$url = 'http://www.hranostaj.cz/xxssyy?dfasdf';
		Assert::same($url, Url::technicalize($url));

		$url = 'hranostaj.cz?utm=campaign&medium=andrej';
		Assert::same('https://hranostaj.cz?utm=campaign&medium=andrej', Url::technicalize($url));

	}

}

$case = new UrlTestCase();
$case->run();
