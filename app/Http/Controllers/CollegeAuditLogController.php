<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\College;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CollegeAuditLogController extends Controller
{
    public function index(Request $request, College $college): Response
    {
        abort_unless($request->user()->hasCollegePermission('college_audit.view', $college->id), 403);

        foreach (['actor_id', 'event', 'resource_type'] as $filter) {
            if ($request->input($filter) === 'all') {
                $request->merge([$filter => null]);
            }
        }

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'actor_id' => ['nullable', 'integer', 'exists:users,id'],
            'event' => ['nullable', 'string', 'max:100'],
            'resource_type' => ['nullable', 'string', 'max:100'],
            'ip_address' => ['nullable', 'ip'],
            'from' => ['nullable', 'date'],
            'until' => ['nullable', 'date', 'after_or_equal:from'],
        ]);
        $scopeReference = "college:{$college->id}";
        $base = AuditLog::query()->where('scope_type', 'COLLEGE')->where('scope_reference', $scopeReference);
        $query = (clone $base)->with('actor:id,name,email');
        $query->when($filters['search'] ?? null, fn ($q, $value) => $q->where(fn ($inner) => $inner->where('event', 'like', "%{$value}%")->orWhere('resource_type', 'like', "%{$value}%")->orWhere('resource_id', $value)))
            ->when($filters['actor_id'] ?? null, fn ($q, $value) => $q->where('actor_user_id', $value))
            ->when($filters['event'] ?? null, fn ($q, $value) => $q->where('event', $value))
            ->when($filters['resource_type'] ?? null, fn ($q, $value) => $q->where('resource_type', $value))
            ->when($filters['ip_address'] ?? null, fn ($q, $value) => $q->where('ip_address', $value))
            ->when($filters['from'] ?? null, fn ($q, $value) => $q->where('created_at', '>=', $value.' 00:00:00'))
            ->when($filters['until'] ?? null, fn ($q, $value) => $q->where('created_at', '<=', $value.' 23:59:59'));

        return Inertia::render('audit-logs/index', [
            'logs' => $query->latest('created_at')->latest('id')->paginate(25)->withQueryString(),
            'filters' => $filters,
            'actors' => User::query()->whereIn('id', (clone $base)->whereNotNull('actor_user_id')->select('actor_user_id'))->orderBy('name')->get(['id', 'name']),
            'events' => (clone $base)->distinct()->orderBy('event')->pluck('event'),
            'resources' => (clone $base)->distinct()->orderBy('resource_type')->pluck('resource_type'),
            'context' => ['title' => $college->name.' Access Audit', 'eyebrow' => $college->code.' - Audit & Security', 'description' => 'Immutable access-management history restricted to this College.', 'action' => "/college/{$college->id}/audit-logs"],
        ]);
    }
}
