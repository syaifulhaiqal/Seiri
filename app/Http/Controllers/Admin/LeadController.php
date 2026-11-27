<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ActivityType;
use App\Enums\LeadStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LeadRequest;
use App\Models\Lead;
use App\Models\User;
use App\Services\Sales;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The sales pipeline: leads, what was said to them, and their quotations.
 */
class LeadController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(LeadStatus::class)],
            'owner' => ['nullable', 'integer'],
        ]);

        $pipeline = Lead::query()
            ->selectRaw('status, count(*) as count, sum(value) as value')
            ->groupBy('status')
            ->get()
            ->keyBy(fn (Lead $row) => $row->status->value);

        return view('admin.leads.index', [
            'leads' => Lead::query()
                ->with('owner')
                ->withMax('activities', 'happened_at')
                ->search($filters['search'] ?? null)
                ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
                ->when($filters['owner'] ?? null, fn ($query, int $owner) => $query->where('owner_id', $owner))
                ->orderByRaw('case when status in (?, ?) then 1 else 0 end', [LeadStatus::Won->value, LeadStatus::Lost->value])
                ->latest('updated_at')
                ->paginate(20)
                ->withQueryString(),
            'filters' => $filters,
            'pipeline' => $pipeline,
            'owners' => $this->owners(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.leads.form', [
            'lead' => new Lead(['status' => LeadStatus::New, 'owner_id' => $request->user()->id]),
            'owners' => $this->owners(),
        ]);
    }

    public function store(LeadRequest $request): RedirectResponse
    {
        $lead = Lead::query()->create($request->validated());

        return to_route('admin.leads.show', $lead)->with('status', __(':name has been added.', ['name' => $lead->title]));
    }

    public function show(Lead $lead): View
    {
        return view('admin.leads.show', [
            'lead' => $lead->load(['owner', 'contact', 'activities.user', 'quotations']),
        ]);
    }

    public function edit(Lead $lead): View
    {
        return view('admin.leads.form', [
            'lead' => $lead,
            'owners' => $this->owners(),
        ]);
    }

    public function update(LeadRequest $request, Lead $lead): RedirectResponse
    {
        $lead->update($request->validated());

        return to_route('admin.leads.show', $lead)->with('status', __('Lead updated.'));
    }

    public function destroy(Lead $lead): RedirectResponse
    {
        if ($lead->quotations()->exists()) {
            return back()->withErrors(['delete' => __('This lead has quotations. Mark it as lost instead.')]);
        }

        $title = $lead->title;
        $lead->delete();

        return to_route('admin.leads.index')->with('status', __(':name has been deleted.', ['name' => $title]));
    }

    /**
     * Make the lead a customer, so it can be quoted and invoiced.
     */
    public function convert(Lead $lead, Sales $sales): RedirectResponse
    {
        $contact = $sales->convert($lead);

        return to_route('admin.leads.show', $lead)->with('status', __(':name is now a customer.', ['name' => $contact->name]));
    }

    public function activity(Request $request, Lead $lead): RedirectResponse
    {
        $data = $request->validateWithBag('activity', [
            'type' => ['required', Rule::enum(ActivityType::class)],
            'happened_at' => ['required', 'date'],
            'summary' => ['required', 'string', 'max:1000'],
        ]);

        $activity = $lead->activities()->make($data);
        $activity->user()->associate($request->user());
        $activity->save();
        $lead->touch();

        return to_route('admin.leads.show', $lead)->with('status', __(':type logged.', ['type' => $activity->type->label()]));
    }

    /**
     * @return Collection<int, string>
     */
    protected function owners(): Collection
    {
        return User::query()->whereIn('role', [UserRole::Admin, UserRole::SuperAdmin])->orderBy('name')->pluck('name', 'id');
    }
}
