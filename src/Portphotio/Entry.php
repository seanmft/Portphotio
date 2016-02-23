<?php
namespace Portphotio;

use RuntimeException;
use JsonSerializable;

use Intervention\Image\ImageManagerStatic as Image;
Image::configure(array('driver' => 'gd'));

class Entry implements JsonSerializable
{
    protected
        $filePath,
        $baseUrl,
        $uuid;

    protected $values = [];

    function __construct($filePath, $baseUrl){
        $this->filePath = $this->_errorCheckFilePath($filePath);
        $this->baseUrl = $this->_errorCheckBaseUrl($baseUrl);
        $this->uuid = $this->_makeUUID($filePath);

        $this->values = [
            'uuid' => $this->uuid,
            'name' => $this->uuid,
            'href' => $this->baseUrl .'/'. $this->uuid,
            'attrs' => []
        ];
    }

    public function jsonSerialize(){
        return $this->values;
    }

    public function toArray(){
        return $this->values;
    }

    public function setName($name){
        $this->values['name'] = $name;
    }

    public function setAttribute($name, $value){
        $name = $this->_errorCheckAndSanitizeAttributeNames($name);
        //when attribute values are set to null we just unset them
        if( isset($this->values['attrs'][$name]) && null === $value ){
            unset($this->values['attrs'][$name]);
        }
        else{
            $this->values['attrs'][$name] = $value;
        }
    }

    protected function _errorCheckAndSanitizeAttributeNames($name){
        $name = strtolower(trim($name));
        $name = trim(str_replace([' ','_'], '-', $name), '-');
        if( !preg_match('/^[-a-z0-9\.]*[a-z0-9]$/', $name) ){
            throw new RuntimeException(
                'The attribute name '.$name.' is not valid. Use a-z, 0-9, period "." and/or hyphen "-" (between words only), with a minimum length of one characetr'
            );
        }
        return $name;
    }

    public function replaceAttributes(array $attributes){
        $this->values['attrs'] = $attributes;
    }

    public function issetAttribute($name){
        return isset($this->values['attrs'][$name]);
    }

    public function getName(){
        return $this->values['name'];
    }

    public function getHref(){
        return $this->values['href'];
    }

    public function getAttribute($name){
        if( isset($this->values['attrs'][$name]) ){
            return $this->values['attrs'][$name];
        }
        return null;
    }

    public function getProperty($name){
        if( isset($this->values[$name]) ){
            $this->values[$name];
        }
    }

    public function getUUID(){
        return $this->uuid;
    }

    public function getFilePath(){
        return $this->filePath;
    }

    public function getImage(){
        return Image::make($this->filePath);
    }

    public function moveFile($dir){
        $newFilePath = $dir .'/'. $this->uuid;
        if(copy($this->filePath, $newFilePath)){
            $this->filePath = $newFilePath;
            return true;
        }
        return false;
    }

    protected function _makeUUID($filePath){
        $this->_ensureHashingAlgorithmIsAvailable('fnv164');
        return hash_file('fnv164', $filePath);
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
}
