<?php

declare(strict_types=1);

namespace Modules\Financing\Models;

use Illuminate\Database\Eloquent\Model;

final class LoanProduct extends Model
{
    protected $table = 'loan_products';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['id','name','name_ar','product_type','min_amount','max_amount','interest_rate','late_penalty_rate','min_term_days','max_term_days','required_documents','is_active','bnpl_installments'];
    protected function casts(): array
    {
        return [
            'min_amount'=>'integer','max_amount'=>'integer','interest_rate'=>'float',
            'late_penalty_rate'=>'float','min_term_days'=>'integer','max_term_days'=>'integer',
            'required_documents'=>'array','is_active'=>'boolean','bnpl_installments'=>'array',
        ];
    }

    public function loans(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Loan::class, 'loan_product_id');
    }
}
