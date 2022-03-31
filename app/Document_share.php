<?php

namespace App;
use App\Models\User;
use App\Document;
use Illuminate\Database\Eloquent\Model;

class Document_share extends Model
{
    protected $table = 'document_shares';

    public function from_user()
    {
        return $this->belongsTo(User::class);
    }

    public function to_user()
    {
        return $this->belongsTo(User::class);
    }

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

}