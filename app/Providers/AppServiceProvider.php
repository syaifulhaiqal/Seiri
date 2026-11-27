<?php

namespace App\Providers;

use App\Models;
use App\Models\User;
use App\Support\Money;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        Password::defaults(fn () => Password::min(8)->letters()->numbers());

        $this->configureVerificationEmail();
        $this->configureAreas();
        $this->configureAudit();

        // @money($amount) prints "RM 1,234.50".
        Blade::directive('money', fn (string $expression) => '<?php echo e(\\' . Money::class . "::format({$expression})); ?>");
    }

    /**
     * Short, stable names for audited and journal-source models.
     * (Sign-in logging is App\\Listeners\\RecordAuthenticationEvents, found by event discovery.)
     */
    protected function configureAudit(): void
    {
        // {claim} in routes means an expense claim.
        Route::model('claim', Models\ExpenseClaim::class);

        Relation::morphMap([
            'user' => User::class,
            'employee' => Models\Employee::class,
            'department' => Models\Department::class,
            'leave_type' => Models\LeaveType::class,
            'leave_request' => Models\LeaveRequest::class,
            'attendance' => Models\Attendance::class,
            'pay_item' => Models\PayItem::class,
            'payroll_run' => Models\PayrollRun::class,
            'account' => Models\Account::class,
            'journal_entry' => Models\JournalEntry::class,
            'contact' => Models\Contact::class,
            'invoice' => Models\Invoice::class,
            'payment' => Models\Payment::class,
            'expense_claim' => Models\ExpenseClaim::class,
        ]);
    }

    /**
     * The app has two sign-in areas: users (`web` guard, no prefix) and admins (`admin` guard,
     * `admin.` route names). These macros let shared controllers and views stay in the right one.
     */
    protected function configureAreas(): void
    {
        // Route-name prefix of the current area: '' or 'admin.'.
        Request::macro('area', fn (): string => $this->routeIs('admin.*') ? 'admin.' : '');

        // Sign out of the current area only. The other area may still be signed in on this
        // browser, so the session is wiped only when nobody is left in it; otherwise it gets a
        // new id but keeps its CSRF token, so forms open in the other area's tabs still submit.
        Request::macro('signOut', function (): void {
            $admin = $this->area() === 'admin.';

            Auth::guard($admin ? 'admin' : 'web')->logout();

            if (Auth::guard($admin ? 'web' : 'admin')->check()) {
                $this->session()->migrate(true);
            } else {
                $this->session()->invalidate();
                $this->session()->regenerateToken();
            }
        });
    }

    /**
     * The activation link points to our signed, guest-friendly verify route
     * (`email/verify/{user}/{hash}`) so it can use route model binding.
     */
    protected function configureVerificationEmail(): void
    {
        VerifyEmail::createUrlUsing(fn (User $user) => URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'user' => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
            ],
        ));

        VerifyEmail::toMailUsing(fn (User $user, string $url) => (new MailMessage)
            ->subject(__('Activate your :app account', ['app' => config('app.name')]))
            ->greeting(__('Hi :name,', ['name' => $user->name]))
            ->line(__('Thanks for signing up. Please confirm your email address to activate your account.'))
            ->action(__('Activate Account'), $url)
            ->line(__('This link expires in :minutes minutes. Your account stays inactive until you confirm it.', [
                'minutes' => Config::get('auth.verification.expire', 60),
            ]))
            ->line(__('If you did not create an account, no further action is required.')));
    }
}
