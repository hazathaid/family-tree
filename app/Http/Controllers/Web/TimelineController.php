<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notification\IndexNotificationRequest;
use App\Http\Requests\Timeline\TimelineIndexRequest;
use App\Http\Requests\Web\WebActionRequest;
use App\Models\Family;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use App\Services\NotificationService;
use App\Services\WebEngagementService;
use App\Services\WebOnboardingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TimelineController extends Controller
{
    public function __construct(private readonly WebOnboardingService $onboarding, private readonly WebEngagementService $presentation, private readonly NotificationRepositoryInterface $notifications, private readonly NotificationService $notificationService) {}

    public function index(TimelineIndexRequest $request): View
    {
        $family = $this->family($request);

        return view('timeline.index', ['family' => $family, ...$this->presentation->timeline($family, $request->user(), $request->safe()->only(['type']))]);
    }

    public function notifications(IndexNotificationRequest $request): View
    {
        $this->family($request);

        return view('notifications.index', $this->presentation->notifications($request->user(), $request->validated('status')));
    }

    public function read(WebActionRequest $request, string $notification): RedirectResponse
    {
        $item = $this->notifications->findForUser($request->user(), $notification);
        $this->notificationService->markRead($item);

        return back();
    }

    public function readAll(WebActionRequest $request): RedirectResponse
    {
        $this->notificationService->markAllRead($request->user());

        return back()->with('status', 'Semua notifikasi ditandai sudah dibaca.');
    }

    private function family(Request $request): Family
    {
        return $this->onboarding->activeFamilyFor($request->user()) ?? abort(403);
    }
}
