<?php namespace App\Models\Dev\WB;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FBSAmounts extends AmountsAbstract
{
    protected $table = 'wb_fbs_amounts';

    public function stock(): BelongsTo
    {
        return $this->belongsTo(FBSStocks::class, 'sid', 'id');
    }
}
