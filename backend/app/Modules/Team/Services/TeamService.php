<?php

namespace App\Modules\Team\Services;

use App\Models\User;
use App\Modules\Team\Models\DelegationLog;
use App\Modules\Team\Models\Team;
use App\Modules\Team\Models\TeamMember;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TeamService
{
    public function createTeam(User $owner, array $data): Team
    {
        if ($owner->kyc_level < 3) {
            throw new \RuntimeException('مستوى التحقق من الهوية يجب أن يكون 3 على الأقل لإنشاء فريق.');
        }

        return DB::transaction(function () use ($owner, $data) {
            $team = Team::create([
                'name' => $data['name'],
                'owner_id' => $owner->id,
                'description' => $data['description'] ?? null,
                'max_depth' => min($data['max_depth'] ?? 3, 3),
                'status' => 'active',
            ]);

            TeamMember::create([
                'team_id' => $team->id,
                'user_id' => $owner->id,
                'parent_id' => null,
                'role' => 'master',
                'level' => 0,
                'commission_rate' => $data['master_commission_rate'] ?? 60.00,
                'daily_deposit_limit' => $data['daily_deposit_limit'] ?? null,
                'daily_withdrawal_limit' => $data['daily_withdrawal_limit'] ?? null,
                'status' => 'active',
                'activated_at' => now(),
            ]);

            DelegationLog::create([
                'team_id' => $team->id,
                'granter_id' => $owner->id,
                'grantee_id' => $owner->id,
                'permissions' => ['team:owner'],
                'action' => 'granted',
                'reason' => 'إنشاء الفريق',
            ]);

            return $team;
        });
    }

    public function listTeams(User $user): Collection
    {
        return Team::where('owner_id', $user->id)
            ->orWhereHas('members', fn ($q) => $q->where('user_id', $user->id))
            ->with('members.user')
            ->get();
    }

    public function getTeam(int $id): Team
    {
        return Team::with(['members.user', 'owner'])->findOrFail($id);
    }

    public function addMember(Team $team, array $data): TeamMember
    {
        if ($team->status !== 'active') {
            throw new \RuntimeException('الفريق غير نشط.');
        }

        $user = User::findOrFail($data['user_id']);

        if ($user->kyc_level < 2) {
            throw new \RuntimeException('مستوى التحقق من الهوية يجب أن يكون 2 على الأقل للانضمام إلى فريق.');
        }

        $existingMember = TeamMember::where('team_id', $team->id)
            ->where('user_id', $data['user_id'])
            ->first();

        if ($existingMember) {
            throw new \RuntimeException('المستخدم موجود بالفعل في هذا الفريق.');
        }

        $memberInOtherTeam = TeamMember::where('user_id', $data['user_id'])
            ->where('team_id', '!=', $team->id)
            ->first();

        if ($memberInOtherTeam) {
            throw new \RuntimeException('المستخدم عضو بالفعل في فريق آخر.');
        }

        $parentId = $data['parent_id'] ?? null;
        $parentLevel = -1;

        if ($parentId) {
            $parent = TeamMember::where('team_id', $team->id)
                ->where('id', $parentId)
                ->firstOrFail();

            $parentLevel = $parent->level;
        } else {
            $master = TeamMember::where('team_id', $team->id)
                ->where('role', 'master')
                ->first();

            if ($master) {
                $parentId = $master->id;
                $parentLevel = $master->level;
            }
        }

        $newLevel = $parentLevel + 1;

        if ($newLevel > $team->max_depth) {
            throw new \RuntimeException('تم تجاوز أقصى عمق للتسلسل الهرمي للفريق.');
        }

        $role = match ($newLevel) {
            0 => 'master',
            1 => 'sub_agent',
            2 => 'junior_sub_agent',
            default => 'sub_agent',
        };

        $parentDepositLimit = null;
        $parentWithdrawalLimit = null;
        if ($parentId) {
            $parent = TeamMember::findOrFail($parentId);
            $parentDepositLimit = $parent->daily_deposit_limit;
            $parentWithdrawalLimit = $parent->daily_withdrawal_limit;
        }

        $dailyDepositLimit = $data['daily_deposit_limit']
            ?? ($parentDepositLimit ? (int) round($parentDepositLimit * 0.7) : null);

        $dailyWithdrawalLimit = $data['daily_withdrawal_limit']
            ?? ($parentWithdrawalLimit ? (int) round($parentWithdrawalLimit * 0.7) : null);

        $commissionRate = $data['commission_rate'] ?? 0;
        if ($commissionRate < 0 || $commissionRate > 50) {
            throw new \RuntimeException('نسبة العمولة يجب أن تكون بين 0 و 50.');
        }

        $member = TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $data['user_id'],
            'parent_id' => $parentId,
            'role' => $role,
            'level' => $newLevel,
            'commission_rate' => $commissionRate,
            'daily_deposit_limit' => $dailyDepositLimit,
            'daily_withdrawal_limit' => $dailyWithdrawalLimit,
            'status' => $user->kyc_level >= 2 ? 'active' : 'pending',
            'activated_at' => $user->kyc_level >= 2 ? now() : null,
        ]);

        DelegationLog::create([
            'team_id' => $team->id,
            'granter_id' => $team->owner_id,
            'grantee_id' => $data['user_id'],
            'permissions' => ['agent:team_member'],
            'action' => 'granted',
            'reason' => 'إضافة عضو إلى الفريق',
        ]);

        return $member;
    }

    public function removeMember(Team $team, int $memberId): void
    {
        $member = TeamMember::where('team_id', $team->id)
            ->where('id', $memberId)
            ->firstOrFail();

        if ($member->role === 'master') {
            throw new \RuntimeException('لا يمكن إزالة مالك الفريق.');
        }

        $hasChildren = TeamMember::where('team_id', $team->id)
            ->where('parent_id', $memberId)
            ->exists();

        if ($hasChildren) {
            throw new \RuntimeException('لا يمكن إزالة عضو لديه أعضاء تابعين. قم بإزالة التابعين أولاً.');
        }

        $member->delete();

        DelegationLog::create([
            'team_id' => $team->id,
            'granter_id' => $team->owner_id,
            'grantee_id' => $member->user_id,
            'permissions' => [],
            'action' => 'revoked',
            'reason' => 'إزالة عضو من الفريق',
        ]);
    }

    public function updateCommission(Team $team, int $memberId, float $rate): TeamMember
    {
        if ($rate < 0 || $rate > 50) {
            throw new \RuntimeException('نسبة العمولة يجب أن تكون بين 0 و 50.');
        }

        $member = TeamMember::where('team_id', $team->id)
            ->where('id', $memberId)
            ->firstOrFail();

        $oldRate = $member->commission_rate;
        $member->update(['commission_rate' => $rate]);

        DelegationLog::create([
            'team_id' => $team->id,
            'granter_id' => $team->owner_id,
            'grantee_id' => $member->user_id,
            'permissions' => ['commission_rate' => $rate],
            'action' => 'modified',
            'reason' => "تغيير نسبة العمولة من {$oldRate}% إلى {$rate}%",
        ]);

        return $member->fresh();
    }

    public function getDelegationLogs(Team $team): Collection
    {
        return DelegationLog::where('team_id', $team->id)
            ->with(['granter', 'grantee'])
            ->latest('created_at')
            ->get();
    }
}
