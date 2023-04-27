<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectCountryBudgetItem extends Model
{
    use HasFactory;

    protected $table = 'project_budget_items';

    protected $guarded = [];

    protected $with = ['description_narratives'];


    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function description_narratives()
    {
        return $this->morphMany(ProjectNarrative::class, 'element');
    }

    public function iati_code()
    {
       return iati_get_code_value('BudgetIdentifier', $this->code);
    }
}
