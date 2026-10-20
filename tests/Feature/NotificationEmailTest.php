<?php

namespace Tests\Feature;

use App\Mail\AccountNotificationMail;
use App\Models\Department;
use App\Models\Event;
use App\Models\Notification;
use App\Models\Role;
use App\Models\User;
use App\Services\EventNotificationService;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NotificationEmailTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $admin;
    protected Role $studentRole;
    protected Role $facultyRole;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, PermissionSeeder::class, DepartmentSeeder::class]);

        $this->studentRole = Role::where('name', 'Student')->first();
        $this->facultyRole = Role::where('name', 'Faculty')->first();
        $adminRole = Role::where('name', 'Administrator')->first();
        $dept = Department::first();

        $this->user = User::create([
            'first_name'    => 'Juan',
            'last_name'     => 'Dela Cruz',
            'email'         => 'juan.delacruz@gmail.com',
            'password'      => bcrypt('password'),
            'role_id'       => $this->studentRole->id,
            'department_id' => $dept->id,
            'employee_id'   => 'STU-9901',
            'status'        => 'active',
        ]);

        $this->admin = User::create([
            'first_name'    => 'Admin',
            'last_name'     => 'User',
            'email'         => 'admin@slmcb.edu.ph',
            'password'      => bcrypt('password'),
            'role_id'       => $adminRole->id,
            'department_id' => $dept->id,
            'employee_id'   => 'ADM-9901',
            'status'        => 'active',
        ]);
    }

    public function test_notification_creation_dispatches_email_to_user_gmail(): void
    {
        Mail::fake();

        $notification = Notification::send(
            $this->user->id,
            'Equipment Request Approved',
            'Your borrow request for "Epson Projector" has been approved.',
            'success',
            '/equipment/5'
        );

        $this->assertDatabaseHas('notifications', [
            'id'      => $notification->id,
            'user_id' => $this->user->id,
            'title'   => 'Equipment Request Approved',
        ]);

        Mail::assertSent(AccountNotificationMail::class, function ($mail) {
            return $mail->hasTo('juan.delacruz@gmail.com')
                && $mail->notification->title === 'Equipment Request Approved'
                && str_contains($mail->notification->message, 'Epson Projector')
                && str_contains($mail->actionUrl, '/equipment/5');
        });
    }

    public function test_event_target_audience_notification_emails_all_recipients(): void
    {
        Mail::fake();

        $dept = Department::first();

        // Create faculty user
        $faculty = User::create([
            'first_name'    => 'Maria',
            'last_name'     => 'Santos',
            'email'         => 'maria.faculty@gmail.com',
            'password'      => bcrypt('password'),
            'role_id'       => $this->facultyRole->id,
            'department_id' => $dept->id,
            'employee_id'   => 'FAC-9901',
            'status'        => 'active',
        ]);

        $event = Event::create([
            'organizer_id'     => $this->admin->id,
            'title'            => 'Faculty Colloquium 2026',
            'description'      => 'Annual Faculty Colloquium',
            'objectives'       => 'Discuss curriculum updates',
            'event_type'       => 'academic',
            'target_audience'  => 'faculty',
            'event_date'       => now()->addDays(7)->toDateString(),
            'time_start'       => '09:00',
            'time_end'         => '12:00',
            'venue'            => 'Auditorium',
            'status'           => 'published',
            'max_participants' => 100,
        ]);

        $dispatched = EventNotificationService::notifyAudience($event, $this->admin->id);
        $this->assertGreaterThanOrEqual(1, $dispatched);

        // Faculty must receive email notification
        Mail::assertSent(AccountNotificationMail::class, function ($mail) {
            return $mail->hasTo('maria.faculty@gmail.com')
                && str_contains($mail->notification->title, 'Faculty Colloquium 2026');
        });

        // Student should NOT receive this faculty-only email
        Mail::assertNotSent(AccountNotificationMail::class, function ($mail) {
            return $mail->hasTo('juan.delacruz@gmail.com');
        });
    }

    public function test_notification_skips_email_when_user_has_invalid_or_empty_email(): void
    {
        Mail::fake();

        $dept = Department::first();
        $userWithoutEmail = User::create([
            'first_name'    => 'NoEmail',
            'last_name'     => 'User',
            'email'         => '',
            'password'      => bcrypt('password'),
            'role_id'       => $this->studentRole->id,
            'department_id' => $dept->id,
            'employee_id'   => 'STU-NOEMAIL',
            'status'        => 'active',
        ]);

        $notification = Notification::send(
            $userWithoutEmail->id,
            'Test Notification',
            'This user has no email.'
        );

        $this->assertDatabaseHas('notifications', ['id' => $notification->id]);
        Mail::assertNothingSent();
    }

    public function test_notification_send_with_send_email_false_bypasses_email(): void
    {
        Mail::fake();

        Notification::send(
            $this->user->id,
            'Internal Silent Alert',
            'This alert is marked sendEmail: false',
            'info',
            '/dashboard',
            sendEmail: false
        );

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'title'   => 'Internal Silent Alert',
        ]);

        Mail::assertNothingSent();
    }

    public function test_notification_email_renders_properly(): void
    {
        $notification = Notification::send(
            $this->user->id,
            'Important Reservation Notice',
            'Your reservation for Main Hall was confirmed.',
            'success',
            '/facilities/1',
            sendEmail: false // test rendering manually
        );

        $mailable = new AccountNotificationMail($notification, $this->user);
        $rendered = $mailable->render();

        $this->assertStringContainsString('Important Reservation Notice', $rendered);
        $this->assertStringContainsString('Your reservation for Main Hall was confirmed.', $rendered);
        $this->assertStringContainsString('Juan Dela Cruz', $rendered);
        $this->assertStringContainsString('juan.delacruz@gmail.com', $rendered);
        $this->assertStringContainsString('/facilities/1', $rendered);
        $this->assertStringContainsString('St. Louise de Marillac College of Bogo', $rendered);
    }

    public function test_notification_email_renders_all_types(): void
    {
        foreach (['info', 'success', 'warning', 'error'] as $type) {
            $notification = new Notification([
                'user_id' => $this->user->id,
                'title'   => "Test {$type} title",
                'message' => "Test {$type} message",
                'type'    => $type,
                'link'    => '/dashboard',
            ]);

            $mailable = new AccountNotificationMail($notification, $this->user);
            $rendered = $mailable->render();

            $this->assertStringContainsString("Test {$type} title", $rendered);
            $this->assertNotEmpty($mailable->getTypeTheme()['badge']);
        }
    }

    public function test_admin_can_access_mail_test_page_and_send_test(): void
    {
        $fakeUrl = 'https://script.google.com/macros/s/FAKE_TEST_RELAY/exec';
        \Illuminate\Support\Facades\Http::fake([
            $fakeUrl => \Illuminate\Support\Facades\Http::response([
                'success' => true,
            ], 200),
        ]);

        putenv("GMAIL_RELAY_URL={$fakeUrl}");
        $_ENV['GMAIL_RELAY_URL'] = $fakeUrl;

        $response = $this->actingAs($this->admin)->get('/admin/mail-test');
        $response->assertStatus(200);
        $response->assertSee('Email Diagnostics & Live Testing');

        $postResponse = $this->actingAs($this->admin)->post('/admin/mail-test', [
            'email'   => 'recipient@ciccebu.edu.ph',
            'title'   => 'Custom Web Test',
            'message' => 'Testing from web diagnostic form',
            'type'    => 'success',
        ]);

        $postResponse->assertSessionHas('mail_success');

        putenv('GMAIL_RELAY_URL');
        unset($_ENV['GMAIL_RELAY_URL']);
    }

    public function test_email_notification_service_dispatches_via_google_relay(): void
    {
        $fakeUrl = 'https://script.google.com/macros/s/FAKE_RELAY_KEY/exec';

        \Illuminate\Support\Facades\Http::fake([
            $fakeUrl => \Illuminate\Support\Facades\Http::response([
                'success' => true,
            ], 200),
        ]);

        putenv("GMAIL_RELAY_URL={$fakeUrl}");
        $_ENV['GMAIL_RELAY_URL'] = $fakeUrl;

        $notification = Notification::send(
            $this->user->id,
            'Relay Test Alert',
            'Testing Google Apps Script Relay'
        );

        \Illuminate\Support\Facades\Http::assertSent(function ($request) use ($fakeUrl) {
            return $request->url() === $fakeUrl
                && str_contains($request['subject'], 'Relay Test Alert')
                && $request['to'] === 'juan.delacruz@gmail.com';
        });

        putenv('GMAIL_RELAY_URL');
        unset($_ENV['GMAIL_RELAY_URL']);
    }
}
