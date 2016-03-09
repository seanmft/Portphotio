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
    }

    public function beforeEach(){
        self::cleanUpFiles();
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
