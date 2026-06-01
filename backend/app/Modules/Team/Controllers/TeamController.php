<?php

namespace App\Modules\Team\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Team\Services\TeamService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function __construct(
        private readonly TeamService $teamService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $teams = $this->teamService->listTeams($request->user());

        return response()->json([
            'success' => true,
            'data' => $teams,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:1000',
            'max_depth' => 'nullable|integer|min:1|max:3',
            'master_commission_rate' => 'nullable|numeric|min:0|max:100',
            'daily_deposit_limit' => 'nullable|integer|min:0',
            'daily_withdrawal_limit' => 'nullable|integer|min:0',
        ]);

        try {
            $team = $this->teamService->createTeam($request->user(), $validated);

            return response()->json([
                'success' => true,
                'data' => $team->load('members.user'),
            ], 201);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'TEAM_CREATION_FAILED', 'message' => $e->getMessage()],
            ], 422);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $team = $this->teamService->getTeam($id);

            return response()->json([
                'success' => true,
                'data' => $team,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'NOT_FOUND', 'message' => 'الفريق غير موجود.'],
            ], 404);
        }
    }

    public function addMember(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'parent_id' => 'nullable|integer|exists:team_members,id',
            'commission_rate' => 'nullable|numeric|min:0|max:50',
            'daily_deposit_limit' => 'nullable|integer|min:0',
            'daily_withdrawal_limit' => 'nullable|integer|min:0',
        ]);

        try {
            $team = $this->teamService->getTeam($id);

            if ($team->owner_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'error' => ['code' => 'FORBIDDEN', 'message' => 'لست مالك هذا الفريق.'],
                ], 403);
            }

            $member = $this->teamService->addMember($team, $validated);

            return response()->json([
                'success' => true,
                'data' => $member->load('user'),
            ], 201);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'NOT_FOUND', 'message' => 'الفريق أو المستخدم غير موجود.'],
            ], 404);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'MEMBER_ADD_FAILED', 'message' => $e->getMessage()],
            ], 422);
        }
    }

    public function removeMember(Request $request, int $id, int $memberId): JsonResponse
    {
        try {
            $team = $this->teamService->getTeam($id);

            if ($team->owner_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'error' => ['code' => 'FORBIDDEN', 'message' => 'لست مالك هذا الفريق.'],
                ], 403);
            }

            $this->teamService->removeMember($team, $memberId);

            return response()->json([
                'success' => true,
                'message' => 'تم إزالة العضو بنجاح.',
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'NOT_FOUND', 'message' => 'الفريق أو العضو غير موجود.'],
            ], 404);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'MEMBER_REMOVE_FAILED', 'message' => $e->getMessage()],
            ], 422);
        }
    }

    public function updateCommission(Request $request, int $id, int $memberId): JsonResponse
    {
        $validated = $request->validate([
            'commission_rate' => 'required|numeric|min:0|max:50',
        ]);

        try {
            $team = $this->teamService->getTeam($id);

            if ($team->owner_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'error' => ['code' => 'FORBIDDEN', 'message' => 'لست مالك هذا الفريق.'],
                ], 403);
            }

            $member = $this->teamService->updateCommission(
                $team,
                $memberId,
                (float) $validated['commission_rate'],
            );

            return response()->json([
                'success' => true,
                'data' => $member,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'NOT_FOUND', 'message' => 'الفريق أو العضو غير موجود.'],
            ], 404);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'COMMISSION_UPDATE_FAILED', 'message' => $e->getMessage()],
            ], 422);
        }
    }

    public function delegationLogs(int $id): JsonResponse
    {
        try {
            $team = $this->teamService->getTeam($id);

            $logs = $this->teamService->getDelegationLogs($team);

            return response()->json([
                'success' => true,
                'data' => $logs,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'NOT_FOUND', 'message' => 'الفريق غير موجود.'],
            ], 404);
        }
    }
}
