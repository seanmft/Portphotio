<?php
namespace Portphotio;

use Upload\Uploads;
use Photo\Manifest;

class Manager
{
    protected
        $Uploads,
        $Manifest;
    public function __construct(Uploads$uploads, Manifest$manifest){
        $this->Uploads = $uploads;
        $this->Manifest = $manifest;
    }
}
