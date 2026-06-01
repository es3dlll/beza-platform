# 09 - UserManagementService

```php
<?php
// app/Services/Admin/UserManagementService.php

namespace App\Services\Admin;

use App\Events\Admin\UserActivated;
use App\Events\Admin\UserBlocked;
use App\Events\Admin\UserSuspended;
use App\Exceptions\Admin\CannotBlockAdminException;
use App\Exceptions\Admin\CannotDeleteSelfException;
use App\Exceptions\Admin\UserNotFoundException;
use App\Models\Admin\AdminActivityLog;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserManagementService
{
    public function list(array $filters): LengthAwarePaginator
    {
        $query = User::whereNull('deleted_at');

        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['kyc_status'])) {
            $query->where('kyc_status', $filters['kyc_status']);
        }
        if (!empty($filters['role'])) {
            match ($filters['role']) {
                'merchant' => $query->where('is_merchant', true),
                'agent'    => $query->where('is_agent', true),
                default    => null,
            };
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $perPage = $filters['per_page'] ?? 20;

        return $query->orderBy($sortBy, $sortOrder)->paginate($perPage);
    }

    public function findOrFail(int $id): User
    {
        $user = User::with(['wallets', 'merchant', 'agent'])
            ->whereNull('deleted_at')
            ->find($id);

        if (!$user) {
            throw new UserNotFoundException();
        }

        return $user;
    }

    public function update(int $id, array $data): User
    {
        $user = $this->findOrFail($id);
        $user->update($data);

        $this->logActivity(auth()->id(), 'update', 'user', $id);

        return $user->fresh();
    }

    public function suspend(int $id, int $adminId): User
    {
        $user = $this->findOrFail($id);

        if ($user->is_admin) {
            throw new CannotBlockAdminException('لا يمكن تعليق مشرف');
        }

        if ($user->isSuspended()) {
            return $user;
        }

        $user->suspend();

        UserSuspended::dispatch($user, $adminId);
        $this->logActivity($adminId, 'suspend', 'user', $id);

        Log::warning("User suspended", [
            'user_id' => $id, 'by_admin' => $adminId
        ]);

        return $user;
    }

    public function activate(int $id): User
    {
        $user = $this->findOrFail($id);

        if ($user->isActive()) {
            return $user;
        }

        $user->activate();

        UserActivated::dispatch($user);
        $this->logActivity(auth()->id(), 'activate', 'user', $id);

        return $user;
    }

    public function block(int $id, int $adminId): User
    {
        $user = $this->findOrFail($id);

        if ($user->is_admin) {
            throw new CannotBlockAdminException('لا يمكن حظر مشرف');
        }

        $user->block();

        UserBlocked::dispatch($user, $adminId);
        $this->logActivity($adminId, 'block', 'user', $id);

        return $user;
    }

    public function delete(int $id, int $adminId): void
    {
        if ($id === $adminId) {
            throw new CannotDeleteSelfException();
        }

        $user = $this->findOrFail($id);

        if ($user->is_admin) {
            throw new CannotBlockAdminException('لا يمكن حذف مشرف');
        }

        DB::transaction(function () use ($user, $adminId) {
            $user->delete(); // soft delete
            $this->logActivity($adminId, 'delete', 'user', $user->id);
        });

        Log::warning("User soft deleted", [
            'user_id' => $id, 'by_admin' => $adminId
        ]);
    }

    public function getTransactions(int $userId): array
    {
        $user = $this->findOrFail($userId);

        $walletIds = $user->wallets->pluck('id');

        return Transaction::where(function ($q) use ($walletIds) {
            $q->whereIn('from_wallet_id', $walletIds)
              ->orWhereIn('to_wallet_id', $walletIds);
        })
        ->with(['fromWallet.user', 'toWallet.user'])
        ->latest()
        ->limit(50)
        ->get()
        ->toArray();
    }

    private function logActivity(int $adminId, string $action, string $targetType, int $targetId): void
    {
        AdminActivityLog::create([
            'admin_id'    => $adminId,
            'action'      => $action,
            'target_type' => $targetType,
            'target_id'   => $targetId,
            'metadata'    => ['ip' => request()->ip()],
            'ip_address'  => request()->ip(),
        ]);
    }
}
```
