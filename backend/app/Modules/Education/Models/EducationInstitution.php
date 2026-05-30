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
    protected $appends = ['fee_collection_rate'];
    protected function casts(): array { return ['is_active'=>'boolean']; }

    public function students(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(EducationStudent::class, 'institution_id'); }

    public function fees(): \Illuminate\Database\Eloquent\Relations\HasManyThrough { return $this->hasManyThrough(EducationFee::class, EducationStudent::class, 'institution_id', 'student_id'); }

    public function getFeeCollectionRateAttribute(): ?float
    {
        $total = (float) $this->fees()->sum('amount');
        if ($total <= 0) return null;
        $collected = (float) $this->fees()->where('status', 'paid')->sum('paid_amount');
        return round(($collected / $total) * 100, 2);
    }
}
