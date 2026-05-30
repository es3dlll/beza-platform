<?php

declare(strict_types=1);

namespace Modules\OpenFinance\Models;

use Illuminate\Database\Eloquent\Model;

class OpenFinanceApp extends Model
{
    protected $table = 'open_finance_apps';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['id','user_id','name','redirect_uris','client_id','client_secret','scopes','is_active'];
    protected $hidden = ['client_secret'];
    protected function casts(): array { return ['scopes'=>'array','is_active'=>'boolean']; }
}
