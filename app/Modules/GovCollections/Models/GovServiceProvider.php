<?php

declare(strict_types=1);

namespace Modules\GovCollections\Models;

use Illuminate\Database\Eloquent\Model;

class GovServiceProvider extends Model
{
    protected $table = 'gov_service_providers';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['id','name','name_ar','code','type','description','description_ar','supported_services','fee_rate','min_fee','max_fee','is_active'];
    protected function casts(): array { return ['supported_services'=>'array','fee_rate'=>'float','min_fee'=>'integer','max_fee'=>'integer','is_active'=>'boolean']; }
}
