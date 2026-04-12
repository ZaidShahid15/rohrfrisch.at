<?php

namespace App\Http\Controllers;

use App\Support\FormMailHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class FormSubmissionController extends Controller
{
    private const RECIPIENT_EMAIL = '1zaidshaikh234@gmail.com';

    public function submit(Request $request): RedirectResponse
    {
        $formKind = (string) $request->input('form_kind', 'contact');

        $request->validate(FormMailHelper::rules($formKind));

        if ($formKind !== 'footer') {
            $this->validateContactForm($request);
        }

        $payload = FormMailHelper::buildPayload($request);

        Mail::send('emails.form-submission', ['payload' => $payload], function ($message) use ($payload) {
            $message->to(self::RECIPIENT_EMAIL)
                ->subject($payload['subject']);

            if (! empty($payload['email'])) {
                $message->replyTo($payload['email'], $payload['name'] ?: $payload['email']);
            }
        });

        return back()->with([
            'form_success' => 'Your message has been sent successfully.',
            'form_success_kind' => $payload['form_kind'],
        ]);
    }

    protected function validateContactForm(Request $request): void
    {
        $name = null;
        $email = null;

        foreach ($request->all() as $key => $value) {
            if (! is_scalar($value)) {
                continue;
            }

            if ($name === null && preg_match('/^text-/', (string) $key) && filled($value)) {
                $name = trim((string) $value);
            }

            if ($email === null && preg_match('/^email-/', (string) $key) && filled($value)) {
                $email = trim((string) $value);
            }
        }

        if (blank($name) || blank($email)) {
            throw ValidationException::withMessages([
                'form' => 'Name and email are required.',
            ]);
        }
    }
}
