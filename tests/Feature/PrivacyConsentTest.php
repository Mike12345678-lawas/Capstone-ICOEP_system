<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\PrivacyConsent;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PrivacyConsentTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $student;
    protected Role $adminRole;
    protected Role $studentRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleSeeder::class,
            PermissionSeeder::class,
            DepartmentSeeder::class,
        ]);

        $this->adminRole = Role::where('name', 'Administrator')->first();
        $this->studentRole = Role::where('name', 'Student')->first();

        // Admin starts consented to manage users
        $this->admin = User::create([
            'id_type'              => 'employee',
            'employee_id'          => '001',
            'first_name'           => 'Admin',
            'last_name'            => 'User',
            'email'                => 'admin@slmcb.edu.ph',
            'password'             => Hash::make('Admin01a'),
            'role_id'              => $this->adminRole->id,
            'department_id'        => 1,
            'status'               => 'active',
            'privacy_consent'      => true,
            'privacy_consent_date' => now(),
        ]);

        // Student starts unconsented
        $this->student = User::create([
            'id_type'              => 'student',
            'employee_id'          => '2024-0001',
            'first_name'           => 'Jane',
            'last_name'            => 'Student',
            'email'                => 'jane.student@slmcb.edu.ph',
            'password'             => Hash::make('Admin01a'),
            'role_id'              => $this->studentRole->id,
            'department_id'        => 1,
            'status'               => 'active',
            'privacy_consent'      => false,
            'privacy_consent_date' => null,
        ]);
    }

    /**
     * 1. Stop UserController::store from setting the consent flag to true.
     */
    public function test_user_controller_store_does_not_set_privacy_consent_to_true(): void
    {
        $payload = [
            'id_type'       => 'student',
            'student_id'    => '2024-9999',
            'first_name'    => 'Mark',
            'last_name'     => 'Created',
            'email'         => 'mark.created@slmcb.edu.ph',
            'password'      => 'Admin01a',
            'password_confirmation' => 'Admin01a',
            'role_id'       => $this->studentRole->id,
            'department_id' => 1,
            'status'        => 'active',
        ];

        $response = $this->actingAs($this->admin)->post('/users', $payload);

        $response->assertRedirect('/users');
        $response->assertSessionHas('success', 'User created successfully.');

        $newUser = User::where('email', 'mark.created@slmcb.edu.ph')->first();
        $this->assertNotNull($newUser);
        $this->assertFalse($newUser->privacy_consent, 'New user privacy_consent must be false upon creation.');
        $this->assertNull($newUser->privacy_consent_date, 'New user privacy_consent_date must be null upon creation.');
    }

    /**
     * 2. Show a consent screen at first login.
     */
    public function test_unconsented_user_is_redirected_to_consent_screen_on_login(): void
    {
        $response = $this->post('/login', [
            'employee_id' => '2024-0001',
            'password'    => 'Admin01a',
        ]);

        $response->assertRedirect(route('privacy.consent'));
        $this->assertAuthenticatedAs($this->student);
    }

    /**
     * 3. Block the rest of the system until the user accepts.
     */
    public function test_unconsented_user_is_blocked_from_the_rest_of_the_system(): void
    {
        // Unconsented student tries to navigate to protected routes
        $routesToTest = [
            '/dashboard',
            '/facilities',
            '/equipment',
            '/events',
            '/announcements',
            '/users',
            '/user-guide',
            '/about-us',
        ];

        foreach ($routesToTest as $url) {
            $response = $this->actingAs($this->student)->get($url);
            $response->assertRedirect(route('privacy.consent'));
        }
    }

    /**
     * 4. Unconsented user can still read the privacy policy and can sign out.
     */
    public function test_unconsented_user_can_view_policy_and_logout(): void
    {
        // Allowed to view policy
        $policyResponse = $this->actingAs($this->student)->get(route('privacy.policy'));
        $policyResponse->assertOk();
        $policyResponse->assertSee('Data Privacy Policy');

        // Allowed to logout
        $logoutResponse = $this->actingAs($this->student)->post(route('logout'));
        $logoutResponse->assertRedirect(route('login'));
        $this->assertGuest();
    }

    /**
     * 5. Write a row to privacy_consents on acceptance, update user, and grant system access.
     */
    public function test_consenting_writes_row_to_privacy_consents_and_unblocks_system(): void
    {
        $this->assertDatabaseMissing('privacy_consents', [
            'user_id' => $this->student->id,
        ]);

        // Submit consent acceptance
        $response = $this->actingAs($this->student)->post(route('privacy.consent.accept'), [
            'agree' => '1',
        ]);

        $response->assertRedirect('/dashboard');
        $response->assertSessionHas('success');

        // Assert user record updated
        $this->student->refresh();
        $this->assertTrue($this->student->privacy_consent);
        $this->assertNotNull($this->student->privacy_consent_date);

        // Assert row written to privacy_consents table
        $this->assertDatabaseHas('privacy_consents', [
            'user_id'      => $this->student->id,
            'consent_type' => 'terms_and_privacy',
            'consented'    => true,
        ]);

        // Assert system is now accessible
        $dashboardResponse = $this->actingAs($this->student)->get('/dashboard');
        $dashboardResponse->assertOk();
    }

    /**
     * 6. Prompt seeded users on their next login.
     */
    public function test_seeded_users_are_unconsented_and_prompted_on_next_login(): void
    {
        $this->seed(UserSeeder::class);

        // Check staff1 seeded account
        $staff1 = User::where('email', 'staff1@slmcb.edu.ph')->first();
        $this->assertNotNull($staff1);
        $this->assertFalse($staff1->privacy_consent, 'Seeded staff user must have privacy_consent set to false.');
        $this->assertNull($staff1->privacy_consent_date, 'Seeded staff user must have privacy_consent_date set to null.');

        // Attempt login with staff1
        $response = $this->post('/login', [
            'employee_id' => '021',
            'password'    => 'Admin01a',
        ]);

        $response->assertRedirect(route('privacy.consent'));
        $this->assertAuthenticatedAs($staff1);
    }

    /**
     * 7. Show consent status in User Management.
     */
    public function test_user_management_shows_consent_status(): void
    {
        // Admin has consented, student has not
        $responseDashboard = $this->actingAs($this->admin)->get('/dashboard?section=users');
        $responseDashboard->assertOk();
        $responseDashboard->assertSee('Consent');
        $responseDashboard->assertSee('Consented');
        $responseDashboard->assertSee('Pending');

        // Standalone user index via legacy route redirect
        $responseIndex = $this->actingAs($this->admin)->followingRedirects()->get('/users');
        $responseIndex->assertOk();
        $responseIndex->assertSee('Consent');
        $responseIndex->assertSee('Consented');
        $responseIndex->assertSee('Pending');
    }
}
