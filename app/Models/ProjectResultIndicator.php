<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectResultIndicator extends Model
{
    use HasFactory;

    public function documents()
    {
        return $this->morphMany(ProjectDocumentLink::class, 'element');
    }

    public function title_narratives()
    {
        return $this->morphMany(ProjectNarrative::class, 'element');
    }
}