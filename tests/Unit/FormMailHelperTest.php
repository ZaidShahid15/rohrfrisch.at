<?php

namespace Tests\Unit;

use App\Support\FormMailHelper;
use Illuminate\Http\Request;
use Tests\TestCase;

class FormMailHelperTest extends TestCase
{
    public function test_extract_contact_fields_reads_prefixed_inputs(): void
    {
        $request = Request::create('/send-form', 'POST', [
            'text-368' => 'Test User',
            'email-248' => 'test@example.com',
            'tel-278' => '+43 1 4420059',
            'menu-552' => 'Abflussreinigung',
            'textarea-38' => 'Bitte um Rueckruf.',
        ]);

        $this->assertSame([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '+43 1 4420059',
            'service' => 'Abflussreinigung',
            'message' => 'Bitte um Rueckruf.',
        ], FormMailHelper::extractContactFields($request));
    }

    public function test_build_payload_for_contact_form_uses_extracted_fields(): void
    {
        $request = Request::create('/send-form', 'POST', [
            'form_kind' => 'contact',
            'source_url' => 'https://example.com/kontakt',
            'text-368' => 'Test User',
            'email-248' => 'test@example.com',
            'tel-278' => '+43 1 4420059',
            'menu-552' => 'Abflussreinigung',
            'textarea-38' => 'Bitte um Rueckruf.',
        ]);

        $payload = FormMailHelper::buildPayload($request);

        $this->assertSame('contact', $payload['form_kind']);
        $this->assertSame('https://example.com/kontakt', $payload['source_url']);
        $this->assertSame('Test User', $payload['name']);
        $this->assertSame('test@example.com', $payload['email']);
        $this->assertSame('+43 1 4420059', $payload['phone']);
        $this->assertSame('Abflussreinigung', $payload['service']);
        $this->assertSame('Bitte um Rueckruf.', $payload['message']);
    }

    public function test_build_payload_for_footer_form_keeps_only_email_field(): void
    {
        $request = Request::create('/send-form', 'POST', [
            'form_kind' => 'footer',
            'source_url' => 'https://example.com/kontakt',
            'form_fields' => [
                'email' => 'footer@example.com',
            ],
        ]);

        $payload = FormMailHelper::buildPayload($request);

        $this->assertSame('footer', $payload['form_kind']);
        $this->assertSame('footer@example.com', $payload['email']);
        $this->assertSame([
            'Email' => 'footer@example.com',
        ], $payload['fields']);
    }
}
