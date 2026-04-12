<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class FormMailHelper
{
    public static function buildPayload(Request $request): array
    {
        $formKind = $request->input('form_kind', 'contact');
        $sourceUrl = $request->input('source_url', url()->previous());

        if ($formKind === 'footer') {
            return [
                'form_kind' => 'footer',
                'source_url' => $sourceUrl,
                'subject' => 'New Footer Email Submission',
                'name' => null,
                'email' => $request->input('form_fields.email'),
                'phone' => null,
                'service' => null,
                'message' => null,
                'fields' => self::filterFields([
                    'Email' => $request->input('form_fields.email'),
                ]),
            ];
        }

        $name = self::firstMatchingInput($request, '/^text-/');
        $email = self::firstMatchingInput($request, '/^email-/');
        $phone = self::firstMatchingInput($request, '/^tel-/');
        $service = self::firstMatchingInput($request, '/^menu-/');
        $message = self::firstMatchingInput($request, '/^textarea-/');

        return [
            'form_kind' => 'contact',
            'source_url' => $sourceUrl,
            'subject' => 'New Website Contact Form Submission',
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'service' => $service,
            'message' => $message,
            'fields' => self::filterFields([
                'Name' => $name,
                'Email' => $email,
                'Phone' => $phone,
                'Service' => $service,
                'Message' => $message,
            ]),
        ];
    }

    public static function rules(string $formKind): array
    {
        if ($formKind === 'footer') {
            return [
                'form_fields.email' => ['required', 'email'],
                'source_url' => ['nullable', 'string'],
            ];
        }

        return [
            'source_url' => ['nullable', 'string'],
            'form_kind' => ['nullable', 'string'],
            '*' => ['nullable', 'string'],
        ];
    }

    public static function validateRequiredContactFields(Request $request): void
    {
        $name = self::firstMatchingInput($request, '/^text-/');
        $email = self::firstMatchingInput($request, '/^email-/');

        abort_if(blank($name) || blank($email), 422, 'Name and email are required.');
    }

    protected static function firstMatchingInput(Request $request, string $pattern): ?string
    {
        foreach ($request->all() as $key => $value) {
            if (! is_string($key) || ! preg_match($pattern, $key)) {
                continue;
            }

            if (is_scalar($value) && filled($value)) {
                return trim((string) $value);
            }
        }

        return null;
    }

    protected static function filterFields(array $fields): array
    {
        return Arr::where($fields, fn ($value) => filled($value));
    }
}
