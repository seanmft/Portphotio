<?php
namespace Portphotio;

require_once 'CaseTemplate.php';

class ManifestTest extends CaseTemplate
{
    protected
        $fixturesPath,
        $ManifestPath,
        $files,
        $fileNames,
        $baseUrl,
        $fileStorageDir;

    protected function setUp(){
        $this->beforeEach();
    }

    public function testGoodConstruct(){
        $m = new Manifest($this->fileStorageDir, $this->baseUrl);
        $this->assertFileNotExists($this->manifestPath);
        return $m;
    }

    public function testSaveFile(){
        $m = $this->testGoodConstruct();
        $uuid = Entry::makeUUID($this->files[0]);
        $m->saveFile($this->files[0], $this->fileData[0]['name']);
        $this->assertFileExists($this->fileStorageDir . '/' . $uuid);
        $entry = $m->getEntry($uuid);
        $this->assertInstanceOf('Portphotio\Entry', $entry);
        $this->assertEquals($this->fileStorageDir.'/'.$uuid, $entry->getFilePath());
    }

    public function testPersistsOnDestruct(){
        $m = $this->testGoodConstruct();
        $m->saveFile($this->files[1], $this->fileData[1]['name']);
        $uuid = Entry::makeUUID($this->files[1]);
        $m->getEntry($uuid)->setAttribute('foo-bar', 'baz');
        $this->assertFileNotExists($this->manifestPath);
        $this->assertFileExists($this->fileStorageDir .'/'. $uuid);
        //destroy Manfiest instance; should be same number of entries and same entries with same props and attrs
        $m = null;
        $m = $this->testGoodConstruct();
        $this->assertEquals(1, $m->count());
        $this->assertInstanceOf("Portphotio\Entry", $m->getEntry($uuid));
        $this->assertEquals('baz', $m->getEntry($uuid)->getAttribute('foo-bar'));
    }

    public function testDanglingEntryReportsChanges(){
        $m = $this->testGoodConstruct();
        $m->saveFile($this->files[2], $this->fileData[2]['name']);
        $uuid = Entry::makeUUID($this->files[2]);
        $entry = $m->getEntry($uuid);
        $entry->setAttribute('photographer', 'that one guy');
        $entry->setName('anotherFileName');//dangling entry
        $entry2 = $m->getEntry($uuid);//new entry object
        $this->assertEquals('that one guy', $entry2->getAttribute('photographer'));
        $this->assertEquals('anotherFileName', $entry2->getName());
        $this->assertEquals($entry, $entry2);
        $this->assertNotSame($entry,$entry2);
    }

    public function beforeEach(){
        self::unregisterAllAndDeleteFiles();
        $this->fixturesPath = realpath('tests/fixtures');
        $this->fileData = json_decode(file_get_contents('tests/fixtures/test-files/test_image_data.json'), true);
        foreach($this->fileData as $k => $obj){
            $this->files[$k] = $this->fixturesPath . '/test-files/' . $k . '.jpg';
        }
        $this->baseUrl = 'http://www.fakeurl.com';
        $this->fileStorageDir = $this->fixturesPath . '/img';
        $this->manifestPath = $this->fileStorageDir . '/manifest.json';
    }

}
