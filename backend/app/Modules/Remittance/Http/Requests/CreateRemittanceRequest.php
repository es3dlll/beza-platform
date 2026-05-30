<?php

declare(strict_types=1);

namespace Modules\Remittance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateRemittanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'corridor_id' => 'required|string|size:26',
            'beneficiary_id' => 'required|string|size:26',
            'source_amount' => 'required|integer|min:1',
            'source_currency' => 'required|string|size:3',
            'payout_method' => 'required|string|in:wallet,agent,bank',
            'payout_wallet_id' => 'required_if:payout_method,wallet|nullable|string',
            'payout_agent_id' => 'required_if:payout_method,agent|nullable|string',
            'payout_bank_account' => 'required_if:payout_method,bank|nullable|string',
            'purpose_code' => 'required|string|in:FAMILY_SUPPORT,SALARY,EDUCATION,MEDICAL,SAVINGS,INVESTMENT,BUSINESS,CHARITY,OTHER',
            'source_of_funds_declaration' => 'required|string|max:500',
            'sender_full_name' => 'required|string|max:100',
            'sender_phone' => 'required|string|max:20',
            'sender_id_document' => 'sometimes|nullable|string|max:100',
        ];
    }
}
