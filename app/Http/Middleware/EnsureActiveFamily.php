<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\WebOnboardingService;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveFamily
{
    public function __construct(private readonly WebOnboardingService $onboarding) {}

    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user instanceof User || $this->onboarding->activeFamilyFor($user) === null) {
            $request->session()->forget(WebOnboardingService::ACTIVE_FAMILY_KEY);

            return redirect()->route('onboarding.index');
        }

        return $next($request);
    }
}
