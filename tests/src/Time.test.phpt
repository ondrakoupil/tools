<?php

require '../bootstrap.php';

use Tester\Assert;
use OndraKoupil\Tools\Time;

class TimeTest extends Tester\TestCase {

	public function testConvert() {
		$int=mktime(12,50,20,5,13,1987);
		$string="1987-05-13 12:50:20";
		$object=new \DateTime($string);

		Assert::same(Time::convert(false, Time::INT), time());

		$out=Time::convert($int, Time::INT);
		Assert::equal($int, $out);
		$out=Time::convert($int, Time::PHP);
		Assert::equal($object, $out);
		$out=Time::convert($int, "j-n:H");
		Assert::equal("13-5:12", $out);

		$out=Time::convert($object, Time::INT);
		Assert::equal($int, $out);
		$out=Time::convert($object, Time::PHP);
		Assert::equal($object, $out);
		$out=Time::convert($object, "j-n:s");
		Assert::equal("13-5:20", $out);

		$out=Time::convert($string, Time::INT);
		Assert::equal($int, $out);
		$out=Time::convert($string, Time::PHP);
		Assert::equal($object, $out);
		$out=Time::convert($string, "Y-n:s");
		Assert::equal("1987-5:20", $out);

		$t1=Time::convert(false, Time::PHP);
		$t2=Time::convert(false, Time::MYSQL);
		Assert::equal(Time::convert($t1,  Time::INT), Time::convert($t2, Time::INT));

		$czechString = "11. 5. 2015";
		$out = Time::convert($czechString, Time::PHP);
		Assert::equal($czechString, $out->format("j. n. Y"));

		$americanString = "5/12/2015";
		$out = Time::convert($americanString, Time::PHP);
		Assert::equal($americanString, $out->format("n/d/Y"));

		Assert::exception(function() {
			Time::convert("nonsense");
		}, '\InvalidArgumentException');

		Assert::exception(function() {
			Time::convert(null, Time::PHP, false);
		}, '\InvalidArgumentException');
		
		Assert::same(null, Time::convert(null, Time::PHP, true));
		
	}

	public function testConvertInterval() {
		$int = new DateInterval("P1D");
		Assert::equal(86400, Time::convertInterval($int));

		$int = new DateInterval("P1DT3H1M10S");
		Assert::equal(86400 + 3600*3 + 60 + 10, Time::convertInterval($int));

		$int = new DateInterval("P3M");
		Assert::true(  abs(92 * 86400 - Time::convertInterval($int)) < 86400 );


	}

	public function testAge() {

		$kdy = time() - 86400;
		Assert::equal("1 den", Time::age($kdy));

		$kdy = time() - 86400 - 3600*2;
		Assert::equal("1 den", Time::age($kdy));

		$kdy = time();
		Assert::equal("úplně nové", Time::age($kdy));

		$kdy = time() - 3600*3 - 65;
		Assert::equal("3 hodiny", Time::age($kdy));

		$kdy = time() - 185;
		Assert::equal("3 minuty", Time::age($kdy));

		$datetime = new \DateTime("2 days ago");
		Assert::equal("2 dny", Time::age($datetime));

		Assert::equal("6 dnů", Time::age("6 days ago"));

		Assert::exception(function() {
			Time::convert("nonsense");
		}, '\InvalidArgumentException');

	}

	public function testHoliday() {

		$kdy = "25. 12. 2019";
		Assert::equal(1, Time::holiday($kdy));

		$kdy = "21. 12. 2019";
		Assert::equal(0, Time::holiday($kdy, false));

		$kdy = "24. 5. 2015"; // neděle
		Assert::equal(0, Time::holiday($kdy, false));
		Assert::equal(2, Time::holiday($kdy, true));


		Assert::equal(2, Time::holiday("next Sunday"));

		Assert::equal(1, Time::holiday("2011-04-25"));
		Assert::equal(1, Time::holiday("2011-04-25", true));

	}

	public function testCreateInterval() {

		$input = 'P10D';
		$interval = Time::createInterval($input);
		Assert::same(DateInterval::class, get_class($interval));
		Assert::same(10, $interval->d);
		Assert::same(0, $interval->m);

		$input = 'PT1M';
		$interval = Time::createInterval($input);
		Assert::same(DateInterval::class, get_class($interval));
		Assert::same(1, $interval->i);
		Assert::same(0, $interval->s);
		Assert::same(0, $interval->d);
		Assert::same(0, $interval->m);
		Assert::same('0', $interval->format('%s'));
		Assert::same('1', $interval->format('%i'));

		$interval2 = Time::createInterval($interval);
		Assert::same($interval, $interval2);

		$input = 120;
		$interval = Time::createInterval($input);
		Assert::same(DateInterval::class, get_class($interval));
		Assert::same(120, $interval->s);
		Assert::same('120', $interval->format('%s'));

		Assert::null(Time::createInterval(null, true));

		Assert::exception(function () {
			Time::createInterval(null, false);
		}, InvalidArgumentException::class);

		Assert::exception(function () {
			Time::createInterval(true);
		}, InvalidArgumentException::class);

		Assert::exception(function () {
			Time::createInterval('XYZ');
		}, Exception::class);

	}

	public function testCompareInterval() {

		$i1 = new DateInterval('PT10M');
		$i2 = new DateInterval('PT20M');
		Assert::same(-1, Time::compareIntervals($i1, $i2));

		$i1 = new DateInterval('P10D');
		$i2 = new DateInterval('P3M');
		Assert::same(-1, Time::compareIntervals($i1, $i2));

		$i1 = new DateInterval('P1D');
		$i2 = new DateInterval('PT10M');
		Assert::same(1, Time::compareIntervals($i1, $i2));

		$i1 = new DateInterval('PT1M');
		$i2 = new DateInterval('PT1M');
		Assert::same(0, Time::compareIntervals($i1, $i2));

		$i1 = new DateInterval('PT60S');
		$i2 = new DateInterval('PT1M');
		Assert::same(0, Time::compareIntervals($i1, $i2));

		$date1 = new DateTime('2024-01-10 10:00:00');
		$date2 = new DateTime('2024-01-10 10:05:00');
		$date3 = new DateTime('2024-01-10 10:08:00');

		$i1 = $date1->diff($date2);
		$i2 = $date2->diff($date1);
		Assert::same(0, Time::compareIntervals($i1, $i2));
		Assert::same(0, Time::compareIntervals($i2, $i1));

		$i1 = $date1->diff($date3);
		$i2 = $date1->diff($date2);
		Assert::same(1, Time::compareIntervals($i1, $i2));
		Assert::same(-1, Time::compareIntervals($i2, $i1));

		$i1 = $date3->diff($date1);
		$i2 = $date2->diff($date1);
		Assert::same(1, Time::compareIntervals($i1, $i2));
		Assert::same(-1, Time::compareIntervals($i2, $i1));


	}

	public function testDiffInterval() {

		$i1 = new DateInterval('PT1M');
		$i2 = new DateInterval('PT2M');
		Assert::same(60, Time::diffIntervals($i1, $i2));

		$i1 = new DateInterval('PT1H');
		$i2 = new DateInterval('PT30M');
		Assert::same(1800, Time::diffIntervals($i1, $i2));
		Assert::same(1800, Time::diffIntervals($i2, $i1));

		$i1 = new DateInterval('PT5M');
		$i2 = new DateInterval('PT300S');
		Assert::same(0, Time::diffIntervals($i1, $i2));
		Assert::same(0, Time::diffIntervals($i2, $i1));

		$i1 = new DateInterval('PT10M');
		$i2 = new DateInterval('PT9M');
		Assert::same(60, Time::diffIntervals($i1, $i2, false));
		Assert::same(-60, Time::diffIntervals($i2, $i1, false));

		$i1 = new DateInterval('PT9M');
		$i2 = new DateInterval('PT10M');
		Assert::same(-60, Time::diffIntervals($i1, $i2, false));
		Assert::same(60, Time::diffIntervals($i2, $i1, false));

		$date1 = new DateTime('2024-01-10 10:00:00');
		$date2 = new DateTime('2024-01-10 10:05:00');
		$date3 = new DateTime('2024-01-10 10:08:00');

		$i1 = $date1->diff($date2);
		$i2 = $date1->diff($date3);
		Assert::same(180, Time::diffIntervals($i2, $i1));
		Assert::same(180, Time::diffIntervals($i1, $i2));
		Assert::same(-180, Time::diffIntervals($i1, $i2, false));

		$i1 = $date2->diff($date1);
		$i2 = $date3->diff($date1);
		Assert::same(180, Time::diffIntervals($i2, $i1));
		Assert::same(180, Time::diffIntervals($i1, $i2));
		Assert::same(-180, Time::diffIntervals($i1, $i2, false));



	}

}

$test = new TimeTest();
$test->run();
