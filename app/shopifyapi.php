<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class shopifyapi extends Model {
    //public $incrementing = false;
    protected $table = 'api';
    //public $timestamps = false;

    /**
     * @var array
     */
    protected $fillable = ['savetext', 'tracking_info'];

    /**
     * @var array
     */
    protected $casts = [
        //'savetext'      => 'array',
        'tracking_info' => 'array',
    ];
}
