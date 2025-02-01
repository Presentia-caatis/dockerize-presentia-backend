<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdmsCredential extends Model
{
    protected $fillable = [
        'username',
        'password'
    ];
}
