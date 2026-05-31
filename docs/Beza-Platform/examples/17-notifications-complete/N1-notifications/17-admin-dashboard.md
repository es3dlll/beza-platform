# 17 - لوحة المشرف (Admin Dashboard)

## إرسال إشعار يدوي

```blade
{{-- resources/views/admin/notifications/send.blade.php --}}
@extends('admin.layouts.app')

@section('content')
<div class="container">
    <h1>إرسال إشعار</h1>
    <form action="{{ route('admin.notifications.send') }}" method="POST">
        @csrf
        <div class="form-group">
            <label>المستخدم</label>
            <select name="user_id" class="form-control select2" required>
                <option value="">اختر مستخدم...</option>
                @foreach($users as $user)
                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->phone }})</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label>نوع الإشعار</label>
            <select name="type" class="form-control" required>
                @foreach($templates as $template)
                <option value="{{ $template->type }}">
                    {{ app()->getLocale() === 'ar' ? $template->title_ar : $template->title_en }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label>القنوات</label>
            <div class="form-check">
                <input type="checkbox" name="channels[]" value="fcm" checked>
                <label>Push Notification</label>
            </div>
            <div class="form-check">
                <input type="checkbox" name="channels[]" value="sms">
                <label>SMS</label>
            </div>
            <div class="form-check">
                <input type="checkbox" name="channels[]" value="email">
                <label>Email</label>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">إرسال</button>
    </form>
</div>
@endsection
```

## Controller

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\NotificationTemplate;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class AdminNotificationController extends Controller
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function create()
    {
        return view('admin.notifications.send', [
            'users' => User::orderBy('name')->get(),
            'templates' => NotificationTemplate::orderBy('type')->get(),
        ]);
    }

    public function send(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'type' => 'required|exists:notification_templates,type',
            'channels' => 'required|array|min:1',
            'channels.*' => 'in:fcm,sms,email',
        ]);

        $user = User::findOrFail($request->user_id);
        $this->notificationService->send(
            $user,
            $request->type,
            [],
            $request->channels,
        );

        return redirect()->back()->with('success', 'تم إرسال الإشعار');
    }

    public function logs()
    {
        $logs = \App\Models\Notification::with('notifiable')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('admin.notifications.logs', compact('logs'));
    }

    public function stats()
    {
        $stats = [
            'total_sent' => \App\Models\Notification::count(),
            'total_failed' => \App\Models\Notification::where('status', 'failed')->count(),
            'by_channel' => \App\Models\Notification::selectRaw('channel, COUNT(*) as count')
                ->groupBy('channel')->get(),
            'by_type' => \App\Models\Notification::selectRaw('type, COUNT(*) as count')
                ->groupBy('type')->get(),
            'today' => \App\Models\Notification::whereDate('created_at', today())->count(),
        ];

        return view('admin.notifications.stats', compact('stats'));
    }
}
```
