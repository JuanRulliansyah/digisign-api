<?php

namespace App;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $table = 'documents';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getTesting()
    {
        return "testing";
    }
}