<x-layouts.app :title="__('EA forms')">
    <div class="page-header">
        <div>
            <h1>{{ __('EA forms :year', ['year' => $year]) }}</h1>
            <p class="text-secondary mb-0">{{ __('Give every employee their EA form by the end of February. Totals come from approved payroll only.') }}</p>
        </div>
        <div class="btn-group">
            <a href="{{ route('admin.ea_forms.index', ['year' => $year - 1]) }}" class="btn btn-outline-secondary" title="{{ __('Previous year') }}"><i class="bi bi-chevron-left"></i><span class="visually-hidden">{{ __('Previous year') }}</span></a>
            <a href="{{ route('admin.ea_forms.index', ['year' => $year + 1]) }}" class="btn btn-outline-secondary" title="{{ __('Next year') }}"><i class="bi bi-chevron-right"></i><span class="visually-hidden">{{ __('Next year') }}</span></a>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">{{ __('Employee') }}</th>
                        <th>{{ __('Months') }}</th>
                        <th class="text-end">{{ __('Gross') }}</th>
                        <th class="text-end">{{ __('EPF') }}</th>
                        <th class="text-end">{{ __('PCB') }}</th>
                        <th class="text-end pe-3">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td class="ps-3">
                                <div class="fw-medium">{{ $row['employee']->name }}</div>
                                <div class="small text-secondary">{{ $row['employee']->employee_no }}</div>
                            </td>
                            <td>{{ $row['months'] }}</td>
                            <td class="text-end text-nowrap">@money($row['gross'])</td>
                            <td class="text-end text-nowrap">@money($row['epf_employee'])</td>
                            <td class="text-end text-nowrap">@money($row['pcb'])</td>
                            <td class="text-end pe-3">
                                <a href="{{ route('admin.ea_forms.show', [$year, $row['employee']]) }}" class="btn btn-sm btn-outline-secondary">{{ __('View') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-secondary py-5">
                                <i class="bi bi-file-earmark-text fs-3 d-block mb-2"></i>
                                {{ __('No approved payroll in :year.', ['year' => $year]) }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
