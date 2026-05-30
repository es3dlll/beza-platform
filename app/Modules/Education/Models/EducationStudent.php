<?php

declare(strict_types=1);

namespace Modules\Education\Models;

use Illuminate\Database\Eloquent\Model;

class EducationStudent extends Model
{
    protected $table = 'education_students';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['id','user_id','institution_id','student_id','full_name','full_name_ar','grade','status'];
}
