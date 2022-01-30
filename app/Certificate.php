<?php

namespace App;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    protected $table = 'certificates';

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}