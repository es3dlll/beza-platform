<?php

declare(strict_types=1);

namespace Modules\OpenFinance\Models;

use Illuminate\Database\Eloquent\Model;

class OpenFinanceConsent extends Model
{
    protected $table = 'open_finance_consents';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['id','user_id','app_id','granted_scopes','status','expires_at','revoked_at'];
    protected function casts(): array { return ['granted_scopes'=>'array','expires_at'=>'datetime','revoked_at'=>'datetime']; }
}
