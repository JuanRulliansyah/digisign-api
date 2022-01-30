<?php

namespace App;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Position_letter extends Model
{
    protected $table = 'position_letters';

    protected $fillable = [
        'is_usable',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

}