<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SiteSmokeTest extends TestCase
{
    public function test_homepage_route_is_available(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('RohrFrisch', false);
        $response->assertSee('<link rel="canonical" href="/">', false);
    }

    public function test_representative_content_page_is_available(): void
    {
        $response = $this->get('/abflussreinigung-wien-1020');

        $response->assertOk();
        $response->assertSee('RohrFrisch', false);
    }

    public function test_representative_content_page_exposes_expected_canonical_and_robots_meta(): void
    {
        $response = $this->get('/abflussreinigung-wien-1020');

        $response->assertOk();
        $response->assertSee('<link rel="canonical" href="/abflussreinigung-wien-1020/">', false);
        $response->assertSee('<meta name="robots" content="index, follow">', false);
    }

    public function test_sitemap_is_available_and_lists_known_pages(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $response->assertSee(url('/'), false);
        $response->assertSee(url('/abflussreinigung-wien-1020'), false);
    }

    public function test_footer_form_submission_redirects_back_with_success_message(): void
    {
        Mail::fake();

        $response = $this->from('/kontakt')->post('/send-form', [
            'form_kind' => 'footer',
            'source_url' => url('/kontakt'),
            'form_fields' => [
                'email' => 'test@example.com',
            ],
        ]);

        $response->assertRedirect('/kontakt');
        $response->assertSessionHas('form_success');
        $response->assertSessionHas('form_success_kind', 'footer');
    }

    public function test_contact_form_submission_redirects_back_with_success_message(): void
    {
        Mail::fake();

        $response = $this->from('/kontakt')->post('/send-form', [
            'form_kind' => 'contact',
            'source_url' => url('/kontakt'),
            'text-368' => 'Test User',
            'email-248' => 'test@example.com',
            'tel-278' => '+43 1 4420059',
            'menu-552' => 'Abflussreinigung',
            'textarea-38' => 'Bitte um Rueckruf.',
        ]);

        $response->assertRedirect('/kontakt');
        $response->assertSessionHas('form_success');
        $response->assertSessionHas('form_success_kind', 'contact');
    }

    public function test_contact_form_requires_a_valid_prefixed_email_field(): void
    {
        Mail::fake();

        $response = $this->from('/kontakt')->post('/send-form', [
            'form_kind' => 'contact',
            'source_url' => url('/kontakt'),
            'text-368' => 'Test User',
            'email-248' => 'not-an-email',
        ]);

        $response->assertRedirect('/kontakt');
        $response->assertSessionHasErrors('form');
    }
}
