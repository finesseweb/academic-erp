<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
                'permissions' => fn () => $request->user()?->allEffectivePermissionCodes() ?? [],
                'universityPermissions' => fn () => $request->user()?->permissionCodes() ?? [],
                'collegeScopeIds' => fn () => $request->user()?->activeCollegeScopeIds() ?? [],
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'flash' => [
                'toast' => function () use ($request) {
                    $toast = $request->session()->get('toast');

                    if (! is_array($toast) || blank($toast['message'] ?? null)) {
                        return $toast;
                    }

                    // Every mutation response gets a unique event id. This makes
                    // repeated identical messages (for example Save -> Save)
                    // observable by the shared client toast hook as separate events.
                    return [
                        ...$toast,
                        'event_id' => (string) Str::uuid(),
                    ];
                },
                'feedback_event_id' => function () use ($request) {
                    $toast = $request->session()->get('toast');
                    $hasToast = is_array($toast) && filled(data_get($toast, 'message'));
                    $hasErrors = $request->session()->has('errors');

                    return ($hasToast || $hasErrors) ? (string) Str::uuid() : null;
                },
            ],
        ];
    }
}
