<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/auxiliary/ArrayAccessTestObject.php';
require __DIR__ . '/auxiliary/FilesTestCase.php';
require __DIR__ . '/auxiliary/TraversableTestObject.php';

define('TMP_TEST_DIR', __DIR__ . '/temp');

\Tester\Environment::setup();
