<?php

declare(strict_types=1);

namespace Modules\Humanitarian\Models;

use Illuminate\Database\Eloquent\Model;

class HumanitarianOrganization extends Model
{
    protected $table = 'humanitarian_organizations';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['id','name','name_ar','code','type','description','description_ar','is_active'];
    protected function casts(): array { return ['is_active'=>'boolean']; }
}
