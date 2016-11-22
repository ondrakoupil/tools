<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require '../bootstrap.php';

use \Tester\Assert;
use \Tester\TestCase;

use \OndraKoupil\Tools\Files;
use \OndraKoupil\Testing\FilesTestCase;

class FilesTest extends FilesTestCase {

	function testBasename() {
		$file=__FILE__;
		Assert::equal("Files.test.phpt", Files::filename($file));
	}

	function testDir() {
		$file="/var/www/vhosts/something/somewhere";
		Assert::equal("/var/www/vhosts/something", Files::dir($file));

		$file="/var/www/vhosts/something/somewhere";
		Assert::equal("/var/www/vhosts/something", Files::dir($file));

		$file=__DIR__."//.././";
		$correct=substr(__DIR__,0,strrpos(__DIR__,"/"));
		Assert::equal($correct, Files::dir($file,true));
	}

	function testChangeFilename() {
		$from="/var/www/aaa.txt";
		Assert::equal("/var/www/bbb.php", Files::changedFilename($from, "bbb.php"));

		$from="/var/www/aaa";
		Assert::equal("/var/www/bbb", Files::changedFilename($from, "bbb"));
	}

	function testFilenameWithoutExtension() {
		Assert::equal("soubor", Files::filenameWithoutExtension("/var/www/soubor.txt"));
		Assert::equal("jiny-soubor", Files::filenameWithoutExtension("/var/www/nekde/jiny-soubor"));
	}

	function testExtension() {
		$filename="/var/www/vhosts/something/myFile.php";
		Assert::equal("php", Files::extension($filename));
		Assert::equal("PHP", Files::extension($filename, Files::UPPERCASE));

		$filename="THISTHAT.Z";
		Assert::equal("Z", Files::extension($filename));
		Assert::equal("z", Files::extension($filename, Files::LOWERCASE));

		$filename="/var/www/vhosts/zip.z/abc";
		Assert::equal("", Files::extension($filename));

		$filename="unext";
		Assert::equal("", Files::extension($filename));
	}

	function testSafename() {
		Assert::equal("zlutoucky-kun.bar", Files::safeName("žluťoučký  kůň.BAr"));
		Assert::equal("dscn-090-1.jpg", Files::safeName("DSCN_090 (1).jpg"));
		Assert::equal("no-extension", Files::safeName("No Extension"));
		Assert::equal("this-is-obviously-it.jpeg", Files::safeName("this.is.obviously.it.jpeg"));
		Assert::equal("evilfile.txt", Files::safeName("EvilFile.php"));
		Assert::equal("evilfile.txt", Files::safeName("EvilFile.JPg",array("png","jpg")));
		Assert::equal("evilfile.doc", Files::safeName("EvilFile.jpg",array("png","jpg"),"doc"));
		Assert::equal("htaccess", Files::safeName(".htaccess"));
		Assert::equal("htaccess.txt", Files::safeName(".htaccess.php"));
		Assert::equal("go-somewhere-else.png", Files::safeName("../../go/somewhere/else.png"));
	}

	function testAddBeforeExtension() {
		Assert::equal("abcdEf.tXt", Files::addBeforeExtension("abc.tXt", "dEf"));
		Assert::equal("abc defgh", Files::addBeforeExtension("abc de", "fgh"));
		Assert::equal("/var/www/vhosts/something.cz/img-small.png", Files::addBeforeExtension("/var/www/vhosts/something.cz/img.png", "-small"));
		Assert::equal("img-small.png", Files::addBeforeExtension("/var/www/vhosts/something.cz/img.png", "-small", false));
	}

	function testFreeFilename() {
		$dirname=$this->createTempDir();
		Files::mkdir($dirname);
		Files::create($dirname."/aaa.txt");
		Files::create($dirname."/bbb.txt");
		Files::create($dirname."/bbb-2.txt");
		Files::create($dirname."/bbb-3.txt");

		Assert::equal("abc.txt",Files::freeFilename($dirname, "abc.txt"));
		Assert::equal("aaa-2.txt",Files::freeFilename($dirname, "aaa.txt"));
		Assert::equal("bbb-4.txt",Files::freeFilename($dirname, "bbb.txt"));

		Files::removeDir($dirname);
	}

	function testCreateDirectories() {
		$dir=$this->createTempDir();

		Files::createDirectories($dir."/abc/def.ghi/jkl");

		Files::createDirectories($dir."/abc/def.ghi/xyz");
		Files::createDirectories($dir."/abc/fghj/axsdf");

		Assert::true(file_exists($dir."/abc"));
		Assert::true(is_dir($dir."/abc"));
		Assert::true(file_exists($dir."/abc/def.ghi/jkl"));
		Assert::true(is_dir($dir."/abc/def.ghi/jkl"));
		Assert::true(file_exists($dir."/abc/def.ghi/xyz"));
		Assert::true(is_dir($dir."/abc/def.ghi/xyz"));
		Assert::true(file_exists($dir."/abc/fghj/axsdf"));
		Assert::true(is_dir($dir."/abc/fghj/axsdf"));

		Files::create($dir."/abc/file");
		Assert::exception(function() use ($dir) {
			Files::createDirectories($dir."/abc/file/abcde.txt");
		}, '\OndraKoupil\Tools\Exceptions\FileException');
	}

	function assertPermsIsCorrect($file, $fileMode = true) {
		if ($fileMode) {
			$required = array("666", "644");
		} else {
			$required = array("777", "755");
		}
		Assert::true(in_array(Files::getPerms($file), $required));
	}

	function testCreateAndPerms() {
		$dir=$this->createTempDir();

		mkdir($dir."/aa",0750);
		Assert::equal("750",  Files::getPerms($dir."/aa"));

		touch($dir."/bb.txt");
		chmod($dir."/bb.txt",0632);
		Assert::equal("632",  Files::getPerms($dir."/bb.txt"));

		$filename=$dir."/test".rand(10000,99999).".txt";
		$out=Files::create($filename);
		Assert::equal(time(),filemtime($filename));
		$this->assertPermsIsCorrect($filename);
		Assert::equal($filename, $out);

		$ok=chmod($filename,0600);
		clearstatcache();
		Assert::equal("600", Files::getPerms($filename) );
		Files::perms($filename);
		clearstatcache();
		$this->assertPermsIsCorrect($filename, true);

		$dirname=$dir."/testdir".rand(10000,99999);
		$out=Files::mkdir($dirname);
		$this->assertPermsIsCorrect($dirname, false);
		Assert::equal($dirname	, $out);

		$dirname=$dir."/testdir".rand(10000,99999);
		mkdir($dirname,0700);
		clearstatcache();
		Assert::equal("700", Files::getPerms($dirname) );
		Files::perms($dirname);
		clearstatcache();
		$this->assertPermsIsCorrect($dirname, false);

		Files::create($dir."/test/in/some/deep/directory.txt");
		Assert::true(file_exists($dir."/test/in/some") and is_dir($dir."/test/in/some"));
		Assert::true(file_exists($dir."/test/in/some/deep/directory.txt") and !is_dir($dir."/test/in/some/deep/directory.txt"));
		$this->assertPermsIsCorrect($dir."/test/in/some/deep/directory.txt");

		Files::mkdir($dir."/test/in/some/another/deep/directory");
		Assert::true(file_exists($dir."/test/in/some/another/deep/directory") and is_dir($dir."/test/in/some/another/deep/directory"));
		$this->assertPermsIsCorrect($dir."/test/in/some/another/deep/directory", false);

		Files::create($dir."/test/file.txt",true,"Hello World");
		Assert::equal("Hello World", file_get_contents($dir."/test/file.txt"));
	}

	function testRemove() {
		$dirMain=$this->createTempDir();
		$dir1=Files::mkdir($dirMain."/test".rand(10000,99999));
		$dir2=Files::mkdir($dir1."/test".rand(1000,9999));
		$file=Files::create($dir2."/test".rand(1000,9999));

		Files::removeDir($dir1);

		Assert::false(file_exists($file));
		Assert::false(file_exists($dir1));

		$dir1=Files::mkdir($dirMain."/test".rand(10000,99999));
		$dir2=Files::mkdir($dir1."/test".rand(1000,9999));
		$file=Files::create($dir2."/test".rand(1000,9999));
		$file2=Files::create($dir2."/test".rand(1000,9999));
		$file3=Files::create($dir2."/test".rand(1000,9999));
		clearstatcache();
		Assert::true(file_exists($file3));
		Files::purgeDir($dir2);
		clearstatcache();
		Assert::false(file_exists($file2));
		Assert::false(file_exists($file3));
		Assert::true(file_exists($dir2));
	}

	function testRebasedFilename() {

		Assert::equal("/var/www/vhosts/siteb/files/file.txt",
			Files::rebasedFilename("/var/www/vhosts/sitea/files/file.txt","/var/www/vhosts/sitea","/var/www/vhosts/siteb")
		);

		Assert::equal("/var/www/vhosts/siteabc/file.txt",
			Files::rebasedFilename("/var/www/vhosts/sitea/file.txt","/var/www/vhosts/sitea","/var/www/vhosts/siteabc")
		);

		Assert::exception(function() {
			Files::rebasedFilename("/var/www/anotherdir/file.txt","/var/www/vhosts/sitea","/var/www/vhosts/siteb");
		}, '\InvalidArgumentException');
	}

	function testIsFileInDir() {
		Assert::true(Files::isFileInDir("/var/www/vhosts/some/file/some/where.txt","/var/www/vhosts"));
		Assert::false(Files::isFileInDir("/var/www/vhosts-another/file.txt","/var/www/vhosts"));
		Assert::false(Files::isFileInDir("/var/totally/another/path/file.txt","/var/www/vhosts"));
	}

	function testRebase() {
		$dirMain=$this->createTempDir();
		$cof="contents of file";
		Files::create($dirMain."/a/b/c/file.txt",true,$cof);
		Files::createDirectories($dirMain."/out");
		Files::createDirectories($dirMain."/x/y");
		Files::createDirectories($dirMain."/a/b/k");

		Assert::exception(function() use ($dirMain) {
			Files::rebaseFile($dirMain."/a/b/c/unknown-file.txt", $dirMain."/x/y", $dirMain."/x/z");
		}, '\OndraKoupil\Tools\Exceptions\FileException');

		Assert::exception(function() use ($dirMain)  {
			Files::rebaseFile($dirMain."/a/b/c/file.txt", $dirMain."/x/y", $dirMain."/x/z");
		}, '\InvalidArgumentException');

		// Test Copying
		Files::rebaseFile($dirMain."/a/b/c/file.txt", $dirMain, $dirMain."/out", true);
		Assert::true(file_exists($dirMain."/out/a/b/c/file.txt"));
		Assert::true(file_exists($dirMain."/a/b/c/file.txt"));
		Assert::equal($cof,file_get_contents($dirMain."/out/a/b/c/file.txt"));

		// Test Rename
		Files::rebaseFile($dirMain."/a/b/c/file.txt", $dirMain."/a/b", $dirMain."/a/b/k");
		Assert::false(file_exists($dirMain."/a/b/c/file.txt"));
		Assert::true(file_exists($dirMain."/a/b/k/c/file.txt"));
		Assert::equal($cof,file_get_contents($dirMain."/a/b/k/c/file.txt"));
	}

	function testMaxFileUpload() {
		Assert::type("int", Files::maxUploadFileSize());
	}

}

$case = new FilesTest();
$case->run();
