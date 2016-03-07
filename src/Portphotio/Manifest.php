<?php
namespace Portphotio;

use ArrayAccess;
use Iterator;
use JsonSerializable;
use ErrorException;
use RuntimeException;

class Manifest implements JsonSerializable,Iterator
{
    const MANIFEST_FILENAME = 'manifest.json';
    protected
        $fileStorageDir,
        $baseUrl;
    protected
        $entries = [];

    public function __construct($fileStorageDir, $baseUrl){
        $this->fileStorageDir = $this->_errorCheckDir($fileStorageDir);
        $this->baseUrl = $this->_errorCheckUrl($baseUrl);
        $this->entries = $this->_getPersistentEntries($fileStorageDir);
    }

    public function __destruct(){
        $manifestPath = $this->fileStorageDir .'/'. self::MANIFEST_FILENAME;
        $json = json_encode($this->entries);
        file_put_contents($manifestPath, $json);
        chmod($manifestPath, 0664);
    }

    public function count(){
        return count($this->entries);
    }

    protected function _getPersistentEntries($fileStorageDir){
        $manifestFile = $fileStorageDir .'/'. self::MANIFEST_FILENAME;
        $entries = [];
        if( is_file($manifestFile) && is_readable($manifestFile) ){
            $entriesArray = json_decode(file_get_contents($manifestFile), true);
            $entries = $entriesArray;
        }
        return $entries;
    }

    protected function _mapEntryArrayToEntryClass($entryArray){
        if($entryArray instanceof Entry)return $entryArray;
        if(false===$entryArray)return false;
        $entryFilePath = $this->fileStorageDir .'/'. $entryArray['uuid'];
        $entry = new Entry($entryFilePath, $this->baseUrl);
        $entry->setName($entryArray['name']);
        $entry->replaceAttributes($entryArray['attrs']);
        return $entry;
    }

    public function getEntry($uuid){
        if(isset($this->entries[$uuid])){
            $entry = $this->_mapEntryArrayToEntryClass($this->entries[$uuid]);
            return $entry;
        }
        return null;
    }

//--new->
    public function getEntries($propOrAttrName, $value = null){
        $entries = [];
        $lc_propOrAttrName = strtolower($propOrAttrName);
        $value = null===$value? $value : strtolower($value);
        foreach ($this->entries as $uuid => $entry) {
            $entry = $this->_mapEntryArrayToEntryClass($entry);
            //match properties case sensitive
            if( null !== $entry->getProperty($propOrAttrName) ){
                if($entry->getProperty($propOrAttrName) == $value){
                    $entries[] = $entry;
                }
                elseif($value === null){
                    $entries[] = $entry;
                }
            }
            //match attributes case insensitive
            elseif($entry->issetAttribute($lc_propOrAttrName)){
                if(strtolower($entry->getAttribute($propOrAttrName)) == $value){
                    $entries[] = $entry;
                }
                elseif($value === null) {
                    $entries[] = $entry;
                }
            }
        }
        return $entries;
    }

    public function saveFile($filePath, $fileName = null){
        $e = new Entry($filePath, $this->baseUrl);
        if( null !== $fileName){
            $e->setName($fileName);
        }
        $uuid = $e->getUUID();
        $e->moveFile($this->fileStorageDir);
        $this->entries[$uuid] = $e;
        return $uuid;
    }

//--new->
    public function delete($uuid){
        $entry = $this->getEntry($uuid);
        if( null !== $entry){
            $this->removeEntry($uuid);
            return $this->deleteFile($entry->getFilePath());
        }
    }

//--new->
    public function removeEntry($uuid){
        if( null !== $this->getEntry($uuid) ){
            unset($this->entries[$uuid]);
        }
    }

//--new->
    public function deleteFile($filePath){
        if( is_file($filePath) && $filePath !== $this->fileStorageDir . self::MANIFEST_FILENAME)
        $deleted = unlink($filePath);
        return $deleted;
    }

    public function jsonSerialize(){
        return $this->toArray();
    }

    public function toArray(){
        $entries = [];
        foreach($this->entries as $uuid => $entry){
            if($entry instanceof Entry){
                $entry = $entry->toArray();
            }
            $entries[$uuid] = $entry;
        }
        return array_values($entries);
    }

    public function key(){
        return key($this->entries);
    }
    public function current(){
        return $this->_mapEntryArrayToEntryClass(current($this->entries));
    }
    public function valid(){
        return isset($this->entries[key($this->entries)]);
    }
    public function next(){
        return $this->_mapEntryArrayToEntryClass(next($this->entries));
    }
    public function rewind(){
        return reset($this->entries);
    }

    protected function _errorCheckUrl($url){
        $url = trim($url);
        if( !filter_var($url, FILTER_VALIDATE_URL) ){
            throw new RuntimeException($url.' is not a valid url');
        }
        return $url;
    }

    protected function _errorCheckDir($dir){
        $dir = rtrim(trim($dir), '/');
        if( !is_dir($dir) ){
            throw new RuntimeException($dir.' is not a directory');
        }
        if( !is_writable($dir) ){
            throw new RuntimeException($dir.' is not writeable');
        }
        return $dir;
    }

}////
