<?php namespace App\Filament\Tables\Actions;

use App\Models\User;
use Filament\Actions\Concerns\CanCustomizeProcess;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class TokenBulkAction extends BulkAction
{
    use CanCustomizeProcess;

    protected function setUp(): void
    {
        parent::setUp();

        $this->action(function (): void
        {
            $this->process(static fn (Collection $records) => $records->each(fn (Model|User $record) => $record->update(['token' => $record->createToken($record->getEmailForVerification())->plainTextToken])));

            $this->success();
        });
    }
}
