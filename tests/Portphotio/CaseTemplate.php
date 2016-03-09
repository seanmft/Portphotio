<?php
namespace Portphotio;


class CaseTemplate extends \PHPUnit_Framework_TestCase
{
    public static function tearDownAfterClass(){
        self::unregisterAllAndDeleteFiles();
    }

    public static function unregisterAllAndDeleteFiles(){
        $register = Register::get();
        foreach($register as $uuid => $e){
            $e = null;
            Register::unregister($uuid);
        }
        self::cleanUpFiles();
    }

    public static function cleanUpFiles(){
        $realpath = realpath('tests/fixtures/img');
        if($realpath){
            $fi = new \FilesystemIterator(realpath('tests/fixtures/img'));
            foreach($fi as $finfo){
                if($finfo->isFile() && '.gitkeep' !== $finfo->getFilename()){
                    unlink($finfo->getPathName());
                }
            }
        }
        else{
            throw \RuntimeException('could not effectively clean up: check cleanUpFiles path');
        }
    }
}////CleanUp\\\\
