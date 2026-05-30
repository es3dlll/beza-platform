<?php

declare(strict_types=1);

namespace Modules\Humanitarian\Models;

use Illuminate\Database\Eloquent\Model;

final class HumanitarianProgram extends Model
{
    protected $table = 'humanitarian_programs';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['id','organization_id','name','name_ar','type','total_budget','remaining_budget','currency','status','starts_at','ends_at'];
    protected function casts(): array { return ['total_budget'=>'integer','remaining_budget'=>'integer','starts_at'=>'datetime','ends_at'=>'datetime']; }
}
