<?php
namespace Portphotio;

require_once 'CaseTemplate.php';

class RegisterTest extends CaseTemplate
{

    protected
        $fixturesPath,
        $fileData,
        $photos,
        $manifestPath;

    protected function setUp(){
        $this->beforeEach();
    }

    public function testRegister(){
        //should do nothing but hold entry
        Register::register($this->entry);
        $this->assertFileNotExists($this->manifestPath);

        //getting should be exactly equal to Entry::toArray() for any particular Entry object
        $uuid = $this->entry->getUuid();
        $this->assertEquals($this->entry->toArray(), Register::get()[$uuid]);

        //after write(), get() should be just as it was before write(), but the manfest file should now be written
        Register::write();
        $this->assertFileExists($this->manifestPath);
        $this->assertEquals($this->entry->toArray(), Register::get()[$uuid]);

        //removing the file should do nothing while Register is still in memory
        unlink($this->manifestPath);
        $this->assertEquals($this->entry->toArray(), Register::get()[$uuid]);

        //and if it's written again it should still be the same
        Register::write();
        $this->assertEquals($this->entry->toArray(), Register::get()[$uuid]);

        //removing the only entry should cause Register::get() to return empty array
        Register::unregister($uuid);
        $this->assertEquals([], Register::get());

        //and anfter wirte() it should still be empty
        Register::write();
        $this->assertEquals([], Register::get());

        $this->entry = null;
    }

    public function beforeEach(){
        self::cleanUpFiles();
        $this->fixturesPath = realpath('tests/fixtures');
        $this->fileData = json_decode(file_get_contents($this->fixturesPath.'/test-files/test_image_data.json'), true);
        foreach($this->fileData as $k => $obj){
            $this->photos[$k] = $this->fixturesPath . '/test-files/' . $k . '.jpg';
        }
        $this->manifestPath = $this->fixturesPath . '/img/manifest.json';
        $this->baseUrl = 'http://www.fakeurl.com';
        Register::$filePath = $this->manifestPath;
        $this->entry = new Entry($this->photos[0], $this->baseUrl);
    }

}
