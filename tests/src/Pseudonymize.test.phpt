<?php

require '../bootstrap.php';

use OndraKoupil\Tools\Pseudonymize;
use \Tester\Assert;
use \Tester\TestCase;

use OndraKoupil\Tools\Url;

class PseudonymizeTestCase extends TestCase {



	function testString() {

		Assert::same('', Pseudonymize::string(''));

		Assert::same('*', Pseudonymize::string('X'));
		Assert::same(',', Pseudonymize::string('X', ','));

		Assert::same('a**', Pseudonymize::string('aaa'));
		Assert::same('a##', Pseudonymize::string('abc', '#'));

		Assert::same('k******a', Pseudonymize::string('karkulka'));
		Assert::same('k......a', Pseudonymize::string('karkulka', '.'));

		Assert::same('hr*****ve', Pseudonymize::string('hradeckralove'));
		Assert::same('hr-----ve', Pseudonymize::string('hradeckralove', '-'));

		Assert::same('ne*****mu', Pseudonymize::string('nejneobhospodarovavatelnejsimu'));

		Assert::same('Č**', Pseudonymize::string('Čaj'));
		Assert::same('ř*****a', Pseudonymize::string('řepička'));
		Assert::same('žl*****vý', Pseudonymize::string('žluťáčkový'));

	}

	function testEmail() {
		Assert::same('', Pseudonymize::email(''));
		Assert::same('h****c', Pseudonymize::email('hradec'));

		Assert::same('i***@hr*****aj.c*', Pseudonymize::email('info@hranostaj.cz'));

		Assert::same('o******k@on*****il.c*', Pseudonymize::email('ondrasek@ondrakoupil.cz'));
		Assert::same('o***a@on*****il.c*', Pseudonymize::email('ondra@ondrakoupil.cz'));

		Assert::same('on*****il@g***l.c**', Pseudonymize::email('ondrakoupil@gmail.com'));
		Assert::same('i***@so*****in.c*', Pseudonymize::email('info@some.subdomain.cz'));

		Assert::same('jo*****ke@s****m.c*', Pseudonymize::email('johnny.snowflake@seznam.cz'));
		Assert::same('jo*****ke@re*****in.n***', Pseudonymize::email('johnny.snowflake@reallylongdomain.name'));
		Assert::same('jo*****ke@re*****in.g***o', Pseudonymize::email('johnny.snowflake@reallylongdomain.gizmo'));

		Assert::same('če*****ek@ry*****vý.c*', Pseudonymize::email('červenáček@rychlošípový.cz'));
		Assert::same('i***@p****k.c*', Pseudonymize::email('info@pošťák.cz'));

	}

	function testIp() {
		Assert::same('', Pseudonymize::ip(''));
		Assert::same('h****c', Pseudonymize::ip('hradec'));
		Assert::same('192.168.120.***', Pseudonymize::ip('192.168.120.123'));
		Assert::same('192.168.5.***', Pseudonymize::ip('192.168.5.1'));
		Assert::same('19*****.5', Pseudonymize::ip('192.168.5'));
	}

	function testPhone() {
		Assert::same('', Pseudonymize::phone(''));
		Assert::same('a**', Pseudonymize::phone('aaa'));
		Assert::same('72*****31', Pseudonymize::phone('721374431'));
		Assert::same('12*****89', Pseudonymize::phone('123456789'));
		Assert::same('2***6', Pseudonymize::phone('27916'));

		Assert::same('72*****31', Pseudonymize::phone('721 374 431'));
		Assert::same('72*****31', Pseudonymize::phone('721 37 44 31'));

		Assert::same('+4207213****1', Pseudonymize::phone('+420 721 37 44 31'));
		Assert::same('+4207213****1', Pseudonymize::phone('+420 721374431'));
	}

}

$case = new PseudonymizeTestCase();
$case->run();
