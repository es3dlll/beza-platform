<?php

declare(strict_types=1);

namespace Modules\OpenFinance\Models;

use Illuminate\Database\Eloquent\Model;

class OpenFinanceWebhookDelivery extends Model
{
    protected $table = 'open_finance_webhook_deliveries';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['id','webhook_id','event','payload','status','attempts','last_attempt_at','succeeded_at','response_body'];
    protected function casts(): array { return ['payload'=>'array','attempts'=>'integer','last_attempt_at'=>'datetime','succeeded_at'=>'datetime']; }
}
