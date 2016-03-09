<?php
namespace Portphotio;

require_once 'CaseTemplate.php';

use Intervention\Image\ImageManagerStatic;

class EntryTest extends CaseTemplate
{

    protected
        $fixturesPath,
        $photos,
        $photoStorageDir,
        $manifestPath,
        $baseUrl;

    protected function setUp(){
        $this->beforeEach();
    }

    public function testGoodConstruct($fileNum=0){
        $fileData = $this->fileData[$fileNum];
        $e = new Entry($this->photos[$fileNum], $this->baseUrl);
        $this->assertFileNotExists($this->manifestPath);
        $this->assertEquals($fileData['uuid'], $e->getUuid());
        $this->assertEquals($fileData['nativeWidth'], $e->getWidth());
        $this->assertEquals($fileData['nativeHeight'], $e->getHeight());
        $this->assertEquals($fileData['orientation'], $e->getOrientation());
        $this->assertEquals($this->baseUrl .'/'. $fileData['uuid'], $e->getHref());
        return $e;
    }

    public function testSetName($fileNum=1){
        $e = $this->testGoodConstruct($fileNum);
        $e->setName($this->fileData[$fileNum]['name']);
        $this->assertEquals($this->fileData[$fileNum]['name'], $e->getName());
        return $e;
    }

    public function testSetAttribute($fileNum=2, $attrName='foo', $attrVal='bar'){
        $fileData = $this->fileData[$fileNum];
        $e = $this->testGoodConstruct($fileNum);
        $e->setAttribute($attrName, $attrVal);
        $this->assertTrue($e->issetAttribute($attrName));
        $this->assertEquals($attrVal, $e->getAttribute($attrName));
        return $e;
    }

    public function testBootstrap($fileNum=3){
        $attr = [ 'some-attr-name' => 'Some Attribute Value'];
        $fileData = $this->fileData[$fileNum];
        $e = $this->testSetAttribute($fileNum, key($attr), $attr[key($attr)] );
        $array = $e->toArray();
        $e2 = Entry::bootstrap($array, $this->photos[$fileNum], $this->baseUrl);
        $this->assertEquals($e, $e2);
    }

    public function testPersistsOnDestruct($fileNum=4){
        $attr = [ 'some-attr-name' => 'Some Attribute Value'];
        $fileData = $this->fileData[$fileNum];

        $e = $this->testSetAttribute($fileNum, key($attr), $attr[key($attr)] );
        $uuid = $e->getUuid();
        $entryArray1 = $e->toArray();
        $e = null;

        //bootstrapping from file and bootstrapping from the $e->toArray() should be the same
        $entryArray2 = Register::get()[$uuid];
        $e1 = Entry::bootstrap($entryArray1, $this->photos[$fileNum], $this->baseUrl);
        $e2 = Entry::bootstrap($entryArray2, $this->photos[$fileNum], $this->baseUrl);
        $this->assertEquals($e1, $e2);
        return $e1;
    }

    public function testDelete($fileNum=5){
        $e = $this->testGoodConstruct($fileNum);
        $filePath = $e->getFilePath();
        $uuid = $e->getUuid();

        //entry hasn't been moved so it should only be unregistered, and shouldn't delete the original (hopefully!!! since we need these);
        $e->delete();
        $this->assertFileExists($filePath);
        $this->assertNull($e->getFilePath());
        $this->assertFalse(isset(Register::get()[$uuid]));

        $e = $this->testGoodConstruct($fileNum);
        $e->moveFile($this->photoStorageDir);
        $this->assertFileExists($this->photoStorageDir .'/'. $uuid);

        //should actually delete the new file
        $e->delete();
        $this->assertFileNotExists($this->photoStorageDir .'/'. $uuid);//no file
        $this->assertNull($e->getFilePath());//all properties should be null

        //should not be set before or after Register::write()
        $this->assertFalse(isset(Register::get()[$uuid]));
        Register::write();
        $this->assertFalse(isset(Register::get()[$uuid]));

    }

    public function beforeEach(){
        self::cleanUpFiles();
        $this->fixturesPath = realpath('tests/fixtures');
        $this->fileData = json_decode(file_get_contents('tests/fixtures/test-files/test_image_data.json'), true);
        foreach($this->fileData as $k => $obj){
            $this->photos[$k] = $this->fixturesPath . '/test-files/' . $k . '.jpg';
        }
        $this->photoStorageDir = $this->fixturesPath . '/img';
        $this->manifestPath = $this->photoStorageDir . '/manifest.json';
        Register::$filePath = $this->manifestPath;
        $this->baseUrl = 'http://www.foo.com';
    }
}
