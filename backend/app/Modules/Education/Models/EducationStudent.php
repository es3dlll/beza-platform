<?php

declare(strict_types=1);

namespace Modules\Education\Models;

use Illuminate\Database\Eloquent\Model;

class EducationStudent extends Model
{
    protected $table = 'education_students';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['id','user_id','institution_id','student_id','full_name','full_name_ar','phone','national_id','grade','status'];

    public function institution(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(EducationInstitution::class, 'institution_id'); }

    public function fees(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(EducationFee::class, 'student_id'); }
}
