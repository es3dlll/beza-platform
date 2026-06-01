<?php

namespace App\Modules\Team\Services;

use App\Modules\Team\Models\Team;
use App\Modules\Team\Models\TeamMember;
use Illuminate\Support\Collection;

class CommissionSplitService
{
    public function calculateSplit(Team $team, int $totalCommission): Collection
    {
        $members = TeamMember::where('team_id', $team->id)
            ->where('status', 'active')
            ->orderBy('level')
            ->get();

        $splits = collect();

        foreach ($members as $member) {
            $rate = $member->commission_rate / 100;
            $amount = (int) round($totalCommission * $rate);

            $splits->push([
                'member_id' => $member->id,
                'user_id' => $member->user_id,
                'role' => $member->role,
                'level' => $member->level,
                'commission_rate' => $member->commission_rate,
                'amount' => $amount,
            ]);
        }

        return $splits;
    }

    public function getDefaultSplit(): array
    {
        return [
            ['level' => 0, 'role' => 'master', 'rate' => 60.00],
            ['level' => 1, 'role' => 'sub_agent', 'rate' => 30.00],
            ['level' => 2, 'role' => 'junior_sub_agent', 'rate' => 10.00],
        ];
    }
}
