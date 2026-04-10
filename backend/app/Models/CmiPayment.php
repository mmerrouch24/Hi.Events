<?php

namespace HiEvents\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CmiPayment extends BaseModel
{
    protected function getCastMap(): array
    {
        return [
            'amount' => 'float',
            'hash_verified' => 'boolean',
            'request_payload' => 'array',
            'raw_callback_payload' => 'array',
            'raw_return_payload' => 'array',
            'callback_received_at' => 'datetime',
            'returned_at' => 'datetime',
            'completed_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function accountCmiConfig(): BelongsTo
    {
        return $this->belongsTo(AccountCmiConfig::class, 'account_cmi_config_id');
    }
}
