<?php

declare(strict_types=1);

namespace Modules\OpenFinance\Models;

use Illuminate\Database\Eloquent\Model;

class OpenFinanceWebhook extends Model
{
    protected $table = 'open_finance_webhooks';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['id','app_id','url','secret','events','is_active'];
    protected function casts(): array { return ['events'=>'array','is_active'=>'boolean']; }
}
