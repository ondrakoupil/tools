<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/aux/ArrayAccessTestObject.php';
require __DIR__ . '/aux/FilesTestCase.php';
require __DIR__ . '/aux/TraversableTestObject.php';

define('TMP_TEST_DIR', __DIR__ . '/temp');

\Tester\Environment::setup();
