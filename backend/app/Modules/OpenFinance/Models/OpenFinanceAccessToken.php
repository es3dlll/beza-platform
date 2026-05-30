<?php

declare(strict_types=1);

namespace Modules\OpenFinance\Models;

use Illuminate\Database\Eloquent\Model;

final class OpenFinanceAccessToken extends Model
{
    protected $table = 'open_finance_access_tokens';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['id','consent_id','token','scopes','expires_at','revoked_at'];
    protected $hidden = ['token'];
    protected function casts(): array { return ['scopes'=>'array','expires_at'=>'datetime','revoked_at'=>'datetime']; }
}
