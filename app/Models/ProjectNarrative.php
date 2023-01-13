<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectNarrative extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function element()
    {
        return $this->morphTo();
    }
}