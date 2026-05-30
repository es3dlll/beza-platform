<?php

declare(strict_types=1);

namespace Modules\Cards\Models;

use Illuminate\Database\Eloquent\Model;

final class CardMerchantBlock extends Model
{
    protected $table = 'card_merchant_blocks';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'card_id', 'merchant_category', 'reason',
    ];
}
