<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\FormMailHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class FormSubmissionController extends Controller
{
    public function submit(Request $request): RedirectResponse
    {
        $formKind = (string) $request->input('form_kind', FormMailHelper::CONTACT_FORM_KIND);

        $request->validate(FormMailHelper::rules($formKind));

        if ($formKind !== FormMailHelper::FOOTER_FORM_KIND) {
            $this->validateContactForm($request);
        }

        $payload = FormMailHelper::buildPayload($request);

        Mail::send('emails.form-submission', ['payload' => $payload], function ($message) use ($payload) {
            $message->to((string) config('contact.recipient_email'))
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
        $contactFields = FormMailHelper::extractContactFields($request);
        $name = $contactFields['name'];
        $email = $contactFields['email'];

        if (blank($name) || blank($email)) {
            throw ValidationException::withMessages([
                'form' => 'Name and email are required.',
            ]);
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                'form' => 'A valid email address is required.',
            ]);
        }
    }
}
