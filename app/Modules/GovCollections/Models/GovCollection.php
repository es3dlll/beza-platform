<?php

declare(strict_types=1);

namespace Modules\GovCollections\Models;

use Illuminate\Database\Eloquent\Model;

class GovCollection extends Model
{
    protected $table = 'gov_collections';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['id','user_id','provider_id','inquiry_id','service_code','account_number','amount','fee','currency','status','channel','receipt_number','failure_reason','paid_at'];
    protected function casts(): array { return ['amount'=>'integer','fee'=>'integer','paid_at'=>'datetime']; }
}
