<?php
namespace Portphotio;

use RuntimeException;

/**
* Entries register themselves with Register::Entry() when they destruct
* Manfist gets updated on Entry changes via Entry::get()
*
* Register is responsible for error checking the manifest path, set via Register::$filePath
*  and for writing Entry array representations to that file upon Register destruct
*/

class Register
{
    public static $filePath = null;
    protected $entries = [];
    protected $Manifest = null;
    private static $instance = null;

    protected function __construct(){
        $filePath = self::$filePath = $this->_errorCheckFileDestination(self::$filePath);
        //first run; no file exists yet
        $contents = is_file($filePath)? file_get_contents($filePath) : '{}';
        //always return an array
        $jsonArray = json_decode($contents, true)?: [];
        $this->entries = $jsonArray;
    }

    private static function Instance(){
        if(!self::$instance){
            self::$instance = new self;
        }
        return self::$instance;
    }

    public static function subscribe(Manifest$Manifest){
        self::Instance()->Manifest = $Manifest;
    }

    public static function register(Entry$Entry){
        $uuid = $Entry->getUuid();
        $entry = $Entry->toArray();
        $Entry = null;
        self::Instance()->entries[$uuid] = $entry;
        if(self::Instance()->Manifest){
            self::Instance()->Manifest->updateEntryStatus($uuid, $entry);
        }
    }

    public static function unregister($uuid){
        unset(self::Instance()->entries[$uuid]);
    }

    public static function get(){
        return self::Instance()->entries;
    }

    public static function write(){
        self::Instance()->__destruct();
    }

    public function __destruct(){
        if(is_array(self::Instance()->entries)){
            $jsonString = json_encode($this->entries, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            file_put_contents(self::$filePath, $jsonString, LOCK_EX);
            chmod(self::$filePath, 0664);
        }
    }

    private function _errorCheckFileDestination($filePath){
        $filePath = trim($filePath);
        $filePath = $filePath?: '.';
        if( $filePath !== parse_url($filePath, PHP_URL_PATH) ){
            throw new RuntimeException('the string `'.$filePath.'` is not a valid filePath');
        }
        $pp = pathinfo($filePath);
        $dir = realpath($pp['dirname']);
        if(!$dir){
            throw new RuntimeException('the filepath `'.$filePath.'` cannot be resolved; its directory does not exist');
        }
        if(!is_writable($dir)){
            throw new RuntimeException('the directory of filePath `'.$filePath.'` is not writeable; check permissions');
        }
        $ext = $pp['extension'];
        if('json' !== $ext){
            echo $ext;
            throw new RuntimeException('filePath must end in .json extension; $filePath was '.$filePath);
        }
        $realPath = $dir.'/'.$pp['basename'];
        if( $realPath && is_file($realPath)  && !is_writable($realPath)){
            throw new RuntimeException('the filePath `'.$filePath.'` resolves to the file `'.$realPath.'`, which is not writeable; check permissions');
        }

        return $realPath;
    }

}////Register\\\\
