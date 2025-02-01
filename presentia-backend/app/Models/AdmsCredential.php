<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Str;

class AdmsCredential extends Model
{
    protected $fillable = [
        'school_id',
        'username',
        'password'
    ];

    public $incrementing = false; 
    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = Str::uuid();
            }
        });
    }
}
