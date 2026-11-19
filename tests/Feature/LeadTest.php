<?php

namespace Tests\Feature;

use App\Events\LeadSubmitted;
use App\Mail\LeadConfirmationMail;
use App\Mail\NewLeadAdminMail;
use App\Models\Lead;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LeadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Test Visitor',
            'phone' => '+91 98765 43210',
            'email' => 'visitor@example.com',
            'service' => 'SEO Services',
            'message' => 'Please call me about SEO.',
            '_ts' => Crypt::encryptString((string) (time() - 10)),
            'source_page' => url('/seo-company-in-noida/'),
        ], $overrides);
    }

    public function test_valid_enquiry_is_stored_and_notifications_are_sent(): void
    {
        Mail::fake();

        $this->postJson('/api/v1/leads', $this->payload())->assertCreated()->assertJson(['ok' => true]);

        $lead = Lead::firstOrFail();
        $this->assertSame('Test Visitor', $lead->name);
        $this->assertSame('new', $lead->status->value);
        $this->assertNotNull($lead->ip);
        Mail::assertSent(NewLeadAdminMail::class, fn ($m) => $m->hasTo('info@techalphonic.com'));
        Mail::assertSent(LeadConfirmationMail::class, fn ($m) => $m->hasTo('visitor@example.com'));
    }

    public function test_validation_errors_are_returned(): void
    {
        $this->postJson('/api/v1/leads', $this->payload(['name' => '', 'phone' => 'abc']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'phone']);
    }

    public function test_honeypot_blocks_bots(): void
    {
        Event::fake([LeadSubmitted::class]);
        $this->postJson('/api/v1/leads', $this->payload(['fax_number' => 'spam']))->assertUnprocessable();
        $this->assertDatabaseCount('leads', 0);
    }

    public function test_html_is_stripped_from_message(): void
    {
        Mail::fake();
        $this->postJson('/api/v1/leads', $this->payload(['message' => '<script>alert(1)</script>Hello']))->assertCreated();
        $this->assertSame('alert(1)Hello', Lead::first()->message);
    }

    public function test_lead_form_is_rate_limited(): void
    {
        Mail::fake();
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/v1/leads', $this->payload(['phone' => '+91 9876543'.str_pad($i, 3, '0', STR_PAD_LEFT)]))->assertCreated();
        }
        $this->postJson('/api/v1/leads', $this->payload())->assertStatus(429);
    }

    public function test_lead_route_is_throttled(): void
    {
        $this->assertContains('throttle:leads', app('router')->getRoutes()->getByName('api.leads.store')->gatherMiddleware());
    }

    public function test_api_lead_endpoint_works_without_session(): void
    {
        Mail::fake();
        $this->postJson('/api/v1/leads', ['name' => 'Api User', 'phone' => '+91 9999999999'])->assertCreated();
        $this->assertDatabaseHas('leads', ['name' => 'Api User']);
    }
}
