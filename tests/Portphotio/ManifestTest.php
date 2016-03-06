<?php
namespace Portphotio;


class ManifestTest extends \PHPUnit_Framework_TestCase
{
    protected
        $fixturesPath,
        $files,
        $fileNames,
        $baseUrl,
        $fileStorageDir;

    protected function setUp(){
        self::cleanUpFiles();
        $this->fixturesPath = realpath('tests/fixtures');
        foreach(new \FilesystemIterator($this->fixturesPath .'/test-files') as $finfo){
            if($finfo->isFile()){
                $this->files[] = $finfo->getPathName();
                $this->fileNames[] = $finfo->getFileName();
            }
        }

        $this->baseUrl = 'http://www.fakeurl.com';
        $this->fileStorageDir = $this->fixturesPath . '/img';
    }

    public function testGoodConstruct(){
        $m = new Manifest($this->fileStorageDir, $this->baseUrl);
        return $m;
    }
    /**
     * @expectedException \RuntimeException
     */
    public function testBadConstructDir(){
        $misspelledDir = 'fixtuers/img';
        $this->setExpectedException(
            '\RuntimeException',
            $misspelledDir.' is not a directory'
        );
        $m = new Manifest($misspelledDir, $this->baseUrl);
    }
    /**
     * @expectedException \RuntimeException
     */
    public function testBadConstructUrl(){
        $badUrl = 'http:/fakeurl..com';
        $this->setExpectedException(
            '\RuntimeException',
            $badUrl.' is not a valid url'
        );
         $m = new Manifest($this->fileStorageDir, $badUrl);
    }

    public function testSaveFile(){
         $m = $this->testGoodConstruct();
         $uuid = $m->saveFile($this->files[0]);

         $this->assertFileExists($this->fileStorageDir .'/'. $uuid, 'file wasn\'t saved corectly');
         $this->assertFileEquals($this->files[0], $this->fileStorageDir .'/'. $uuid, 'saved file doesn\'t match original');
    }

    public function testGetEntry(){
        $m = $this->testGoodConstruct();
        $uuid = $m->saveFile($this->files[1]);

        $this->assertInstanceOf('\Portphotio\Entry', $m->getEntry($uuid));
        $this->assertFileExists($m->getEntry($uuid)->getFilePath());
        $this->assertFileEquals($this->files[1], $m->getEntry($uuid)->getFilePath() );
    }

    public function testIterable(){
        $m = $this->testGoodConstruct();
        $m->saveFile($this->files[2]);
        $m->saveFile($this->files[3]);
        $m->saveFile($this->files[4]);

        $i = 2;//$this->files offset start from above
        foreach($m as $uuid => $entry){
            $this->assertInstanceOf('\Portphotio\Entry', $entry);
            $this->assertEquals($uuid, $entry->getUUID());
            $this->assertFileEquals($this->files[$i], $entry->getFilePath());
            $this->assertInstanceOf('\Intervention\Image\Image', $entry->getImage() );
            $i++;
        }
    }

    public function testJsonSerialize(){
        $m = $this->testGoodConstruct();
        $expectedUuid = hash_file('fnv164', $this->files[5]);
        $uuid = $m->saveFile($this->files[5], $this->fileNames[5]);

        $expectedArray = [
            [
                'uuid' => $expectedUuid,
                'name' => $this->fileNames[5],
                'href' => $this->baseUrl .'/'. $expectedUuid,
                'attrs' => []
            ]
        ];
        $this->assertJsonStringEqualsJsonString(json_encode($expectedArray), json_encode($m));
    }

    public function testPersistence(){
        $m = $this->testGoodConstruct();
        $_uuid[6] = $m->saveFile($this->files[6]);
        $_uuid[7] = $m->saveFile($this->files[7]);
        $_uuid[8] = $m->saveFile($this->files[8]);

        //state before Manifest instance is destroyed
        $beforePersistenceJson = json_encode($m);
        $this->assertFileNotExists($this->fileStorageDir .'/'. Manifest::MANIFEST_FILENAME);
        //destroy manifest: should create manifest file
        $m = null;
        $this->assertFileExists($this->fileStorageDir .'/'. Manifest::MANIFEST_FILENAME);

        //new Manifest instance should be functionally identical to the one that was just destroyed
        $m = new Manifest($this->fileStorageDir, $this->baseUrl);

        $this->assertEquals(count($_uuid), $m->count());
        foreach($m as $uuid => $entry){
            $this->assertContains($uuid, $_uuid);
            $this->assertInstanceOf('\Portphotio\Entry', $entry);
        }
        $this->assertJsonStringEqualsJsonString($beforePersistenceJson, json_encode($m));
    }

    protected function tearDown(){
        self::cleanUpFiles();
    }

    protected static function cleanUpFiles(){
        $fi = new \FilesystemIterator(realpath('tests/fixtures') .'/img');
        foreach($fi as $finfo){
            if($finfo->isFile()){
                unlink($finfo->getPathName());
            }
        }
    }
}
