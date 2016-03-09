<?php
namespace Portphotio;

class Manifest extends ManifestSystem
{
    const MANIFEST_FILENAME = 'manifest.json';
    protected
        $fileStorageDir,
        $manifestPath,
        $baseUrl;

    public function __construct($fileStorageDir, $baseUrl){
        //register manifest; let Register check the path
        $this->fileStorageDir = $fileStorageDir;
        Register::$filePath = $this->manifestPath = $this->fileStorageDir . '/' . self::MANIFEST_FILENAME;
        $this->baseUrl = $this->_errorCheckUrl($baseUrl);
    }

    public function count(){
        return count(Register::get());
    }

    public function getEntry($uuid){
        $entries = Register::get();
        if(isset($entries[$uuid])){
            $entry = $this->_makeBootstrapEntry($entries[$uuid]);
            return $entry;
        }
        return null;
    }

    public function saveFile($filePath, $fileName = null){
        $entries = Register::get();
        $uuid = Entry::makeUUID($filePath);
        if( isset($entries[$uuid]) ){
            return $uuid;
        }
        $e = $this->_makeNewEntry($filePath);
        if( null !== $fileName){
            $e->setName($fileName);
        }
        $e->moveFile($this->fileStorageDir);
        return $uuid;
    }

    public function toArray(){
        return array_values(Register::get());
    }

    public function query($propOrAttrName = null, $value = null){
        $result = [];
        $entries = Register::get();
        if($propOrAttrName == null){
            $result = $entries;
        }
        else{
            $lc_propOrAttrName = strtolower($propOrAttrName);
            $value = null===$value? $value : strtolower($value);
            foreach ($entries as $uuid => $entry) {
                //match properties case sensitive
                if( isset($entry['attrs'][$propOrAttrName]) ){
                    if($entry['attrs'][$propOrAttrName] == $value){
                        $result[] = $entry;
                    }
                    elseif($value === null){
                        $result[] = $entry;
                    }
                }
                //match attributes case insensitive
                elseif( isset($entry['attrs'][$lc_propOrAttrName]) ){
                    if(strtolower($entry['attrs'][$propOrAttrName]) == $value){
                        $result[] = $entry;
                    }
                    elseif($value === null) {
                        $result[] = $entry;
                    }
                }
            }//foreach
        }//else

        return $result;
    }

//---------------------------------------------------------implemented-->
    public function jsonSerialize(){
        return $this->toArray();
    }


//------------------------------------------------------------protected->
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

    protected function _makeNewEntry($filePath){
        return new Entry($filePath, $this->baseUrl);
    }

    protected function _makeBootstrapEntry($entryArray){
        if($entryArray instanceof Entry)return $entryArray;
        if(false===$entryArray)return false;
        $entryFilePath = $this->fileStorageDir .'/'. $entryArray['uuid'];
        return Entry::bootstrap($entryArray, $entryFilePath, $this->baseUrl);
    }

}////Manifest\\\\
