<?php

namespace App;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    protected $table = 'profiles';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}