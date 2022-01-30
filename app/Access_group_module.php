<?php

namespace App;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Access_group_module extends Model
{
    protected $table = 'access_group_modules';
    public $timestamps = false;

    function access_group(){
		return $this->belongsTo('App\Access_group','group_id');
	}
}