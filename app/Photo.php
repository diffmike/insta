<?php

namespace App;

use Jenssegers\Mongodb\Model as Eloquent;

class Photo extends Eloquent
{
    protected $collection = 'photos';
    protected $guarded = [];
}
