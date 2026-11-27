<?php

namespace App\Support;

use App\Models\User;

/**
 * The how-to guide shown on the Tutorial page. Staff see the self-service guide; admins see a section for each
 * module their role can use, so nobody reads about screens they cannot open.
 */
class Tutorial
{
    /**
     * @return list<array{key: string, title: string, icon: string, intro: string, steps: list<string>, tip?: string}>
     */
    public static function sectionsFor(User $user): array
    {
        $sections = $user->isAdmin() ? self::adminSections($user) : self::staffSections($user);

        return array_values(array_filter([self::basics(), ...$sections]));
    }

    /**
     * @return array{key: string, title: string, icon: string, intro: string, steps: list<string>, tip?: string}
     */
    protected static function basics(): array
    {
        return [
            'key' => 'basics',
            'title' => __('Getting around'),
            'icon' => 'bi-compass',
            'intro' => __('The menu on the left holds every page you can use. Sections fold away: click a section name to open or close it.'),
            'steps' => [
                __('The bell at the top shows what needs your attention. Click a notification to open the record.'),
                __('Switch between English and Bahasa Melayu with EN / BM, and between light and dark mode with the sun or moon icon.'),
                __('Change your name, password and email settings on your Profile (click your name at the top right).'),
            ],
            'tip' => __('Your choices of language, theme and open menu sections are remembered on this device.'),
        ];
    }

    /**
     * @return list<null|array{key: string, title: string, icon: string, intro: string, steps: list<string>, tip?: string}>
     */
    protected static function staffSections(User $user): array
    {
        return [
            [
                'key' => 'clock-in',
                'title' => __('Clocking in and out'),
                'icon' => 'bi-clock-history',
                'intro' => __('Record your attendance once a day: clock in when you start and clock out when you finish.'),
                'steps' => [
                    __('At the office, scan the QR code on the office screen with your phone camera and press Clock in now.'),
                    __('Working from home or outside? Open the clock-in link HR sent you, sign in and press Clock in now.'),
                    __('Allow your browser to share your location when asked. HR sees where you clocked in.'),
                    __('Clock out the same way at the end of the day. My attendance shows your last 30 days.'),
                ],
                'tip' => __('Photos of the office QR code stop working after a few minutes, so scan it at the office.'),
            ],
            [
                'key' => 'leave',
                'title' => __('Applying for leave'),
                'icon' => 'bi-calendar2-check',
                'intro' => __('My leave shows how many days you have left of each leave type.'),
                'steps' => [
                    __('Choose the leave type and the first and last day, and add a reason if you like.'),
                    __('Only working days are counted. Weekends and public holidays are skipped.'),
                    __('Your request goes to your approvers, then HR. You get a notification when it is decided.'),
                    __('Changed your plans? Cancel a request while it is still pending.'),
                ],
            ],
            [
                'key' => 'overtime',
                'title' => __('Overtime and movements'),
                'icon' => 'bi-hourglass-split',
                'intro' => __('Claim overtime you worked, and log when you leave the office during work hours.'),
                'steps' => [
                    __('My overtime: enter the date, start and end time and the reason. Claims can go back 30 days.'),
                    __('Approved overtime is paid with your next salary, at the rate for a working day, rest day or public holiday.'),
                    __('My movements: log where you are going and why before you leave, and press Back when you return.'),
                ],
            ],
            [
                'key' => 'claims',
                'title' => __('Expense claims'),
                'icon' => 'bi-receipt-cutoff',
                'intro' => __('Claim back money you spent for work, such as travel or supplies.'),
                'steps' => [
                    __('Choose what it was for, the date and amount, and attach a photo or PDF of the receipt.'),
                    __('Your claim is reviewed, then paid back. Follow its status on My expense claims.'),
                ],
            ],
            [
                'key' => 'pay',
                'title' => __('Payslips and EA forms'),
                'icon' => 'bi-wallet2',
                'intro' => __('Your payslips appear on My payslips once the month\'s payroll is approved.'),
                'steps' => [
                    __('Open a month to see your pay, allowances, overtime and deductions, and print it.'),
                    __('EA forms give your yearly totals for your income tax return. Open them from My payslips.'),
                    __('My training lists the courses HR has recorded for you.'),
                ],
            ],
            $user->isApprover() ? [
                'key' => 'approvals',
                'title' => __('Approving for your team'),
                'icon' => 'bi-check2-square',
                'intro' => __('You are an approver for some colleagues, so their requests come to you first.'),
                'steps' => [
                    __('Open Approvals to see leave, claims and overtime waiting for you.'),
                    __('Approve, or reject with a short note saying why. The request then moves to the next approver or HR.'),
                ],
            ] : null,
        ];
    }

    /**
     * @return list<null|array{key: string, title: string, icon: string, intro: string, steps: list<string>, tip?: string}>
     */
    protected static function adminSections(User $user): array
    {
        return [
            $user->can('employees.manage') ? [
                'key' => 'employees',
                'title' => __('Setting up employees'),
                'icon' => 'bi-person-badge',
                'intro' => __('Every staff member needs an employee record. Link it to their sign-in account so they can use the self-service pages.'),
                'steps' => [
                    __('Add departments and leave types first, then add employees with their join date and job details.'),
                    __('Staff sign up for an account at the sign-in page. Then pick their account under Login account on their employee record.'),
                    __('On an employee, use Approvers to set who approves their leave, claims and overtime (up to three levels).'),
                    __('Tick Can clock in remotely for people who always work outside the office.'),
                ],
            ] : null,
            $user->can('attendance.view') ? [
                'key' => 'attendance',
                'title' => __('Attendance'),
                'icon' => 'bi-clock-history',
                'intro' => __('The attendance board shows who is in, late, on leave or absent, and how and where they clocked in.'),
                'steps' => [
                    __('Open Office QR screen on a tablet or monitor at the entrance and press Full screen. Staff scan it to clock in.'),
                    __('For work from home, create a clock-in link under Attendance → Clock-in links and send it to your staff.'),
                    __('Today\'s board refreshes every minute. Filter by department or by how people clocked in.'),
                    __('Movements shows who is out of the office during work hours.'),
                ],
                'tip' => __('If a clock-in link reaches the wrong people, use Get a new link. The old one stops working at once.'),
            ] : null,
            $user->can('leave.review') ? [
                'key' => 'leave',
                'title' => __('Leave, overtime and holidays'),
                'icon' => 'bi-calendar2-check',
                'intro' => __('Requests reach you after the employee\'s approvers, or straight away if they have none.'),
                'steps' => [
                    __('Open Leave requests or Overtime, check the details and approve, or reject with a note.'),
                    __('Add each year\'s public holidays, including Hari Raya and state holidays, under Public holidays.'),
                ],
            ] : null,
            $user->can('payroll.view') ? [
                'key' => 'payroll',
                'title' => __('Running payroll'),
                'icon' => 'bi-wallet2',
                'intro' => __('Payroll is prepared, approved and paid in three separate steps, often by different people.'),
                'steps' => [
                    __('Prepare: choose the month. Payslips are calculated with EPF, SOCSO, EIS, PCB, unpaid leave and overtime.'),
                    __('Check the draft. Fix pay settings on employees if needed and press Recalculate.'),
                    __('Approve: payslips become visible to staff. Then record the payment once salaries are paid.'),
                    __('EA forms are ready after the year\'s payroll is approved.'),
                ],
            ] : null,
            $user->can('claims.view') ? [
                'key' => 'claims',
                'title' => __('Expense claims'),
                'icon' => 'bi-receipt-cutoff',
                'intro' => __('Claims are reviewed first and paid back after, so each step can belong to a different person.'),
                'steps' => [
                    __('Open a claim, look at the receipt, then approve or reject it with a note.'),
                    __('Once the money is paid to the employee, mark it as paid back. It is posted to the books.'),
                ],
            ] : null,
            $user->can('invoices.manage') || $user->can('ledger.manage') ? [
                'key' => 'finance',
                'title' => __('Finance'),
                'icon' => 'bi-file-earmark-text',
                'intro' => __('Invoices, bills and payments post to the books by themselves; you rarely need a journal entry.'),
                'steps' => [
                    __('Add customers and suppliers under Customers & suppliers.'),
                    __('Create an invoice or bill as a draft, check it, then issue it. Record payments as they come in or go out.'),
                    __('Reports show profit and loss, the balance sheet and more for any period.'),
                ],
            ] : null,
            $user->can('procurement.view') || $user->can('inventory.manage') ? [
                'key' => 'purchasing',
                'title' => __('Purchasing and stock'),
                'icon' => 'bi-cart-plus',
                'intro' => __('Buying follows request, approval, order, receipt and bill.'),
                'steps' => [
                    __('Raise a purchase request for what you need. An approver approves or rejects it.'),
                    __('Turn an approved request into a purchase order, print it and mark it as sent to the supplier.'),
                    __('Mark it as received when the goods arrive. Products on the order are added to stock.'),
                    __('Create bill turns the order into a supplier bill. Adjust stock for items used, sold or damaged.'),
                ],
            ] : null,
            $user->can('crm.manage') ? [
                'key' => 'sales',
                'title' => __('Sales and CRM'),
                'icon' => 'bi-funnel',
                'intro' => __('Track each opportunity from first contact to invoice.'),
                'steps' => [
                    __('Add a lead and log every call, meeting, email or note on it.'),
                    __('Make customer when it gets serious, then send a quotation.'),
                    __('When the customer accepts, Create invoice turns the quotation into a draft invoice and marks the lead as won.'),
                ],
            ] : null,
            $user->can('roles.manage') || $user->can('users.manage') ? [
                'key' => 'access',
                'title' => __('Users and roles'),
                'icon' => 'bi-key',
                'intro' => __('Each admin has one role, and the role decides which modules they see.'),
                'steps' => [
                    __('Roles & permissions (super admin only): create roles such as HR manager or Auditor and tick their permissions.'),
                    __('Users: make someone an admin and give them a role, or delete an account that is no longer needed.'),
                ],
                'tip' => __('Keep preparing and approving separate: give them to different roles.'),
            ] : null,
            $user->can('audit.view') ? [
                'key' => 'audit',
                'title' => __('Audit trail'),
                'icon' => 'bi-shield-check',
                'intro' => __('Every change, sign-in and failed sign-in is recorded with who, when and from where.'),
                'steps' => [
                    __('Filter by person, record type, event or date, open an entry to see old and new values, or export to CSV.'),
                ],
            ] : null,
        ];
    }
}
