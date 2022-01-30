<?php

namespace App;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Access_group extends Model
{
    protected $table = 'access_groups';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    function modules(){
		return $this->hasMany('App\Access_group_module','group_id');
	}

}