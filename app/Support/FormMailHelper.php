<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;

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

        $contactFields = self::extractContactFields($request);

        return [
            'form_kind' => 'contact',
            'source_url' => $sourceUrl,
            'subject' => 'New Website Contact Form Submission',
            'name' => $contactFields['name'],
            'email' => $contactFields['email'],
            'phone' => $contactFields['phone'],
            'service' => $contactFields['service'],
            'message' => $contactFields['message'],
            'fields' => self::filterFields([
                'Name' => $contactFields['name'],
                'Email' => $contactFields['email'],
                'Phone' => $contactFields['phone'],
                'Service' => $contactFields['service'],
                'Message' => $contactFields['message'],
            ]),
        ];
    }

    public static function extractContactFields(Request $request): array
    {
        return [
            'name' => self::firstMatchingInput($request, '/^text-/'),
            'email' => self::firstMatchingInput($request, '/^email-/'),
            'phone' => self::firstMatchingInput($request, '/^tel-/'),
            'service' => self::firstMatchingInput($request, '/^menu-/'),
            'message' => self::firstMatchingInput($request, '/^textarea-/'),
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
