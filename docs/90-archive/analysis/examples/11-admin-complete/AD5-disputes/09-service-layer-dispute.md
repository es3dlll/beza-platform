# 09 - DisputeService

```php
<?php
// app/Services/DisputeService.php

namespace App\Services;

use App\Events\Admin\DisputeOpened;
use App\Models\Dispute;
use App\Models\DisputeEvidence;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DisputeService
{
    public function create(
        User  $user,
        int   $transactionId,
        string $reason,
        string $description,
        array $evidenceFiles = [],
    ): Dispute {
        $transaction = Transaction::findOrFail($transactionId);

        // تحديد الطرف الآخر في النزاع
        $respondentId = $transaction->fromWallet->user_id === $user->id
            ? $transaction->toWallet->user_id
            : $transaction->fromWallet->user_id;

        return DB::transaction(function () use (
            $user, $transaction, $respondentId,
            $reason, $description, $evidenceFiles
        ) {
            $dispute = Dispute::create([
                'transaction_id'  => $transaction->id,
                'complainant_id'  => $user->id,
                'respondent_id'   => $respondentId,
                'reason'          => $reason,
                'description'     => $description,
                'status'          => 'open',
            ]);

            // رفع الأدلة
            foreach ($evidenceFiles as $file) {
                $path = $file->store('disputes/' . $dispute->id, 'public');

                DisputeEvidence::create([
                    'dispute_id'    => $dispute->id,
                    'file_path'     => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'type'          => $file->getMimeType() === 'application/pdf' ? 'document' : 'image',
                ]);
            }

            DisputeOpened::dispatch($dispute);

            return $dispute;
        });
    }

    public function getOpenDisputes(): Collection
    {
        return Dispute::with(['transaction', 'complainant', 'evidence'])
            ->open()
            ->latest()
            ->get();
    }

    public function getDetail(int $id): Dispute
    {
        return Dispute::with([
            'transaction.fromWallet.user',
            'transaction.toWallet.user',
            'complainant',
            'respondent',
            'evidence',
        ])->findOrFail($id);
    }

    public function getUserDisputes(int $userId): Collection
    {
        return Dispute::where('complainant_id', $userId)
            ->orWhere('respondent_id', $userId)
            ->with(['transaction', 'evidence'])
            ->latest()
            ->get();
    }

    public function autoCloseExpiredDisputes(): int
    {
        $expired = Dispute::open()
            ->where('created_at', '<', now()->subHours(48))
            ->get();

        $count = 0;
        foreach ($expired as $dispute) {
            $dispute->update([
                'status'         => 'resolved',
                'resolution'     => 'reject',
                'auto_closed_at' => now(),
            ]);
            $count++;
        }

        return $count;
    }
}
```
