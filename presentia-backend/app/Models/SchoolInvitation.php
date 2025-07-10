<?php

namespace App\Models;

use App\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class SchoolInvitation extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        "sender_id",
        "receiver_id",
        "school_id",
        "role_to_assign_id",
        "status"
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function roleToAssign()
    {
        return $this->belongsTo(Role::class, 'role_to_assign_id');
    }
}

