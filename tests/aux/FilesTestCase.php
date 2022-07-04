<?php

namespace OndraKoupil\Testing;

use \Tester\TestCase;
use \OndraKoupil\Tools\Files;

/**
 * Test case with a temp dir. It is created and purged automatically.
 */
class FilesTestCase extends TestCase {

	private $tempDir=false;

	function tearDown() {
		parent::tearDown();
		if ($this->tempDir) {
			try {
				Files::removeDir($this->tempDir);
				$this->tempDir=false;
			} catch (\Exception $e) {
				throw new \Tester\TestCaseException("Could not remove temp directory $this->tempDir, there might be some files left inside.", 1, $e);
			}
		}
	}


	/**
	 * Vytvoří dočasný adresář. Nevolej tuto metodu, ale getTempDir().
	 * @return string
	 * @throws \Tester\TestCaseException
	 */
	function createTempDir() {
		if ($this->tempDir) {
			throw new \Tester\TestCaseException("Temp directory already exists, can not create another one.");
		}
		$dir=uniqid("test");
		if (!defined("TMP_TEST_DIR")) throw new \Tester\TestCaseException("Constant TMP_TEST_DIR was not set in test bootstrap! Please define it with path to some base temp dir in which FilesTestCase can create its own temp directories.");
		Files::mkdir(TMP_TEST_DIR."/".$dir);
		$this->tempDir=TMP_TEST_DIR."/".$dir;
		return TMP_TEST_DIR."/".$dir;
	}

	/**
	 * Vrátí cestu k dočasnému adresáři, do kterého si test může ukládat, cokoliv chce.
	 * Pokud zatm žádný testovací adresář neexistuje, vytvoří ho.
	 * @return string
	 */
	function getTempDir() {
		if ($this->tempDir) {
			return $this->tempDir;
		}
		return $this->createTempDir();
	}

	function setUp() {
		parent::setUp();
		$this->tempDir=false;
	}
}