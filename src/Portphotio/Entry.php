<?php
namespace Portphotio;

use RuntimeException;
use Intervention\Image\ImageManagerStatic as Image;
Image::configure(array('driver' => 'gd'));


/**
* Entry class is designed to be instantiated by a Manfiest instance.
* each Entry object created has three manifestations:
*  - as an object of Entry class
*  - as an array returned by Entry::toArray()
*  - as a JSON object with key Entry::uuid and value Entry::values
* within a JSON object, written to a JSON file the path to which is
* stored in Entry::manifestPath
*/
class Entry extends ManifestSystem
{
    const HASH_ALGORITHM = 'fnv164';
    const ATTRIBUTE = 'attrs';
    const ORIENTATION = 'orientation';
    const NATIVE_WIDTH = 'nativeWidth';
    const NATIVE_HEIGHT = 'nativeHeight';
    const HREF = 'href';
    const UUID = 'uuid';
    const NAME = 'name';

    protected
        $filePath,
        $baseUrl,
        $uuid,
        $fileHasBeenMoved = false;

    protected $values = [];

    function __construct($filePath, $baseUrl){
        $this->filePath = $this->_errorCheckFilePath($filePath);
        $this->baseUrl = $this->_errorCheckBaseUrl($baseUrl);
        $this->_ensureHashingAlgorithmIsAvailable(self::HASH_ALGORITHM);
        $this->uuid = self::makeUUID($filePath);
        list($width, $height) = $this->_getWidthAndHeight($filePath);
        $orientation = $this->_getOrientation($width, $height);

        $this->values = [
            self::UUID => $this->uuid,
            self::NAME => $this->uuid,
            self::ORIENTATION => $orientation,
            self::NATIVE_WIDTH => $width,
            self::NATIVE_HEIGHT => $height,
            self::HREF => $this->baseUrl .'/'. $this->uuid,
            self::ATTRIBUTE => []
        ];
    }

    /**
    * Entry objects are responsible for registering with Register upon destruction
    */
    public function __destruct(){
        Register::register($this);
    }

    public static function makeUUID($filePath){
        return hash_file(self::HASH_ALGORITHM, $filePath);
    }

    public function setName($name){
        $this->values['name'] = $name;
        Register::register($this);
    }

    public function setAttribute($name, $value){
        $name = $this->_errorCheckAndSanitizeAttributeNames($name);
        //when attribute values are set to null we just unset them
        if( isset($this->values[self::ATTRIBUTE][$name]) && null === $value ){
            unset($this->values[self::ATTRIBUTE][$name]);
        }
        else{
            $this->values[self::ATTRIBUTE][$name] = $value;
        }
        Register::register($this);
    }

    public function isValidAttribute($attribute){
        $attribute = strtolower(trim($attribute));
        $attribute = trim(str_replace([' ','_'], '-', $attribute), '-');
        if( !preg_match('/^[-a-z0-9\.]*[a-z0-9]$/', $attribute) ){
            return false;
        }
        return true;
    }

    public function issetAttribute($name){
        return isset($this->values[self::ATTRIBUTE][$name]);
    }

    public function issetProperty($name){
        return isset($this->values[$name]);
    }

    public function getUUID(){
        return $this->uuid;
    }

    public function getName(){
        return $this->values[self::NAME];
    }

    public function getOrientation(){
        return $this->values[self::ORIENTATION];
    }

    public function getWidth(){
        return $this->values[self::NATIVE_WIDTH];
    }

    public function getHeight(){
        return $this->values[self::NATIVE_HEIGHT];
    }

    public function getHref(){
        return $this->values[self::HREF];
    }

    public function getAttribute($name){
        if( isset($this->values[self::ATTRIBUTE][$name]) ){
            return $this->values[self::ATTRIBUTE][$name];
        }
        return null;
    }

    public function getProperty($name){
        if( isset($this->values[$name]) ){
            return $this->values[$name];
        }
        return null;
    }

    public function getAttributeKeys(){
        return array_keys($this->values[self::ATTRIBUTE]);
    }

    public function getPropertyKeys(){
        return array_keys($this->values);
    }

    public function getImage(){
        return Image::make($this->filePath);
    }

    public function getFilePath(){
        return $this->filePath;
    }

    public function moveFile($dir){
        $newFilePath = $dir .'/'. $this->uuid;
        if(copy($this->filePath, $newFilePath)){
            chmod($newFilePath, 0664);
            $this->filePath = $newFilePath;
            $this->fileHasBeenMoved = true;
            return true;
        }
        return false;
        Register::register($this);
    }

    public function delete(){
        Register::unregister($this->uuid);
        $return = $this->fileHasBeenMoved? unlink($this->filePath) : true;
        foreach($this as $k=>$v){
            $this->$k = null;
        }
        return $return;
    }

    public function toArray(){
        return $this->values;
    }
//----------------------------------------implemented-->
    public function jsonSerialize(){
        return $this->toArray();
    }

    /**
    * static constructor reinits Enrtry object with and entry array;
    *   normally form a json_decode'd string in the manifest
    */
    public static function bootstrap(array$entry, $filePath, $baseUrl){
        $reflect = new \ReflectionClass(__CLASS__);
        $consts = $reflect->getConstants();
        unset($consts['HASH_ALGORITHM']);
        if( 0 !== count(array_diff($consts, array_keys($entry))) ){
            throw new RuntimeException('faulty Entry array, cannot bootstrap.');
        }
        if( $entry['uuid'] !== self::makeUUID($filePath) ){
            throw new RuntimeException(
            'uuid of file does not match uuid from entry array; either the file changed, or the wrong file was passed'
            );
        }
        $Entry = new self($filePath, $baseUrl);
        $Entry->values = $entry;
        return $Entry;
    }

//--------------------------------------------------------------------------protected->
    protected function _errorCheckAndSanitizeAttributeNames($name){
        if( !$this->isValidAttribute($name) ){
            throw new RuntimeException(
                'The attribute name '.$name.' is not valid. Use a-z, 0-9, period "." and/or hyphen "-" (between words only), with a minimum length of one characetr'
            );
        }
        return $name;
    }


    protected function _ensureHashingAlgorithmIsAvailable($algo){
        if( !in_array($algo, hash_algos()) ){
            throw new RuntimeException('The prefered hashing algorithm is not available');
        }
    }

    protected function _errorCheckBaseUrl($baseUrl){
        $baseUrl = trim($baseUrl,'/');
        if(!filter_var($baseUrl, FILTER_VALIDATE_URL)){
            throw new RuntimeException('The url '.$baseUrl.' is not valid');
        }
        return $baseUrl;
    }

    protected function _errorCheckFilePath($filePath){
        $filePath = trim($filePath);
        if(!file_exists($filePath) && is_file($filePath)){
            throw new RuntimeException('file '.$filePath.' does not exist');
        }
        if(!is_readable($filePath)){
            throw new RuntimeException('file '.$filePath.' is not readable');
        }
        return $filePath;
    }

    protected function _getWidthAndHeight($filePath){
        $img = Image::make($filePath);
        $height = $img->height();
        $width = $img->width();
        return [$width, $height];
    }

    protected function _getOrientation($width, $height){
        $proportion = $width/$height;
        $orientation = '';
        if($proportion < 1){
            $orientation = 'portrait';
        }
        elseif($proportion > 1){
            $orientation = 'landscape';
        }
        else{
            $orientation = 'square';
        }
        return $orientation;
    }
}////Entry\\\\
