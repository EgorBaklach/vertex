<?php namespace App\Models\Dev\WB;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FBOAmounts extends AmountsAbstract
{
    protected $table = 'wb_fbo_amounts';

    public function stock(): BelongsTo
    {
        return $this->belongsTo(FBOStocks::class, 'sid', 'id');
    }
}
