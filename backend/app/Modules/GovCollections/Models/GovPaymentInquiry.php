<?php

declare(strict_types=1);

namespace Modules\GovCollections\Models;

use Illuminate\Database\Eloquent\Model;

final class GovPaymentInquiry extends Model
{
    protected $table = 'gov_payment_inquiries';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['id','user_id','provider_id','service_code','account_number','account_name','amount_due','fee','currency','status','reference_id','expires_at'];
    protected function casts(): array { return ['amount_due'=>'integer','fee'=>'integer','expires_at'=>'datetime']; }
}
