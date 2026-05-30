<?php

declare(strict_types=1);

namespace Modules\Education\Models;

use Illuminate\Database\Eloquent\Model;

class EducationInstitution extends Model
{
    protected $table = 'education_institutions';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['id','name','name_ar','code','type','phone','email','is_active'];
    protected function casts(): array { return ['is_active'=>'boolean']; }
}
