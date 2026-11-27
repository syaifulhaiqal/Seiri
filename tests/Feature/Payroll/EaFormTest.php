<?php

namespace Tests\Feature\Payroll;

use App\Models\Employee;
use App\Models\PayrollRun;
use App\Models\User;
use App\Services\EaStatement;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class EaFormTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $user;

    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-03-31 17:00:00');
        $this->seed(ChartOfAccountsSeeder::class);
        $this->admin = User::factory()->superAdmin()->create();
        $this->user = User::factory()->create();
        $this->employee = Employee::factory()->create([
            'user_id' => $this->user->id, 'join_date' => '2025-01-01', 'basic_salary' => '3000.00', 'monthly_pcb' => '100.00', 'tax_no' => 'IG 12345678090',
        ]);

        // January and February approved, March still a draft.
        foreach (['2026-01', '2026-02', '2026-03'] as $period) {
            $this->actingAs($this->admin, 'admin')->post(route('admin.payroll.store'), ['period' => $period]);
        }

        PayrollRun::query()->orderBy('period')->take(2)->get()
            ->each(fn (PayrollRun $run) => $this->actingAs($this->admin, 'admin')->post(route('admin.payroll.approve', $run)));
    }

    public function test_the_statement_adds_up_approved_payroll_only(): void
    {
        $statement = app(EaStatement::class)->for($this->employee, 2026);

        $this->assertSame(2, $statement['months']);
        $this->assertSame('6000.00', $statement['gross']);
        $this->assertSame('660.00', $statement['epf_employee']);
        $this->assertSame('200.00', $statement['pcb']);
        $this->assertNull(app(EaStatement::class)->for($this->employee, 2025));
    }

    public function test_employees_see_their_own_ea_form_and_admins_see_everyone(): void
    {
        $this->actingAs($this->user, 'web')->get(route('ea_forms.index'))->assertOk()->assertSee('EA form 2026');
        $this->actingAs($this->user, 'web')->get(route('ea_forms.show', 2026))
            ->assertOk()
            ->assertSee('IG 12345678090')
            ->assertSee('6,000.00')
            ->assertDontSee('March 2026');
        $this->actingAs($this->user, 'web')->get(route('ea_forms.show', 2025))->assertNotFound();

        $this->actingAs($this->admin, 'admin')->get(route('admin.ea_forms.index', ['year' => 2026]))->assertOk()->assertSee($this->employee->name);
        $this->actingAs($this->admin, 'admin')->get(route('admin.ea_forms.show', [2026, $this->employee]))->assertOk()->assertSee('February 2026');
    }
}
