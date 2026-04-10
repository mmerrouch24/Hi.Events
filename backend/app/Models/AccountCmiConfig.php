<?php

namespace HiEvents\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountCmiConfig extends BaseModel
{
    protected function getCastMap(): array
    {
        return [
            'store_key' => 'encrypted',
            'auto_redirect' => 'boolean',
            'is_enabled' => 'boolean',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
