<?php
namespace Portphotio;

use Intervention\Image\ImageManagerStatic;

class EntryTest extends \PHPUnit_Framework_TestCase
{

    protected
        $fixturesPath,
        $files,
        $baseDir,
        $baseUrl;

    protected function setUp(){
        $this->fixturesPath = realpath('tests/fixtures');
        foreach(new \FilesystemIterator($this->fixturesPath . '/test-files') as $finfo){
            if($finfo->isFile()){
                $this->files[] = $finfo->getPathName();
            }
        }
        $this->baseDir = $this->fixturesPath . '/img';
        $this->baseUrl = 'http://www.foo.com';
    }

    public function testConstruct(){
        $e = new Entry($this->files[0], $this->baseUrl);
        $this->assertEquals(hash_file('fnv164', $this->files[0]), $e->getUUID());
        $this->assertEquals($this->files[0], $e->getFilePath());
    }

    public function testGetImage(){
        $e = new Entry($this->files[0], $this->baseUrl);
        $this->assertInstanceOf('Intervention\Image\Image', $e->getImage() );
    }

    public function testMoveFile(){
        $e = new Entry($this->files[0], $this->baseUrl);
        $e->moveFile($this->baseDir);
        $this->assertFileExists($e->getFilePath());
    }

    public function testSettingNameAndAttributes(){
        $e = new Entry($this->files[0], $this->baseUrl);
        $name = 'fooey foo foo';
        $photographer = 'sammy jackson';
        $e->setName($name);
        $e->setAttribute('photographer', $photographer);

        $array = $e->toArray();
        $this->assertEquals($array['name'], $name);
        $this->assertEquals($array['attrs']['photographer'], $photographer, 'attr values arent set as expected');
        $this->assertJsonStringEqualsJsonString(json_encode($array), json_encode($e), 'jsonSerialize is broken');
    }

    public static function tearDownAfterClass(){
        $fi = new \FilesystemIterator(realpath('tests/fixtures/img'));
        foreach($fi as $finfo){
            if($finfo->isFile()){
                unlink($finfo->getPathName());
            }
        }
    }
}
