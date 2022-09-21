<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    use HasFactory;

    protected $guarded = [];


    public function getLogDateAttribute($value)
    {
        return date("m/d/Y",strtotime($value));
    }
}
