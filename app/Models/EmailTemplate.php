<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'from', 'created_by'
    ];

    public function template()
    {
        return $this->hasOne(UserEmailTemplate::class, 'template_id', 'id')->where('user_id', auth()->user()->id);
    }
}
