<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class FormMailHelper
{
    public const CONTACT_FORM_KIND = 'contact';
    public const FOOTER_FORM_KIND = 'footer';

    protected const CONTACT_FIELD_PATTERNS = [
        'name' => '/^text-/',
        'email' => '/^email-/',
        'phone' => '/^tel-/',
        'service' => '/^menu-/',
        'message' => '/^textarea-/',
    ];

    public static function buildPayload(Request $request): array
    {
        $formKind = $request->input('form_kind', self::CONTACT_FORM_KIND);
        $sourceUrl = $request->input('source_url', url()->previous());

        if ($formKind === self::FOOTER_FORM_KIND) {
            return [
                'form_kind' => self::FOOTER_FORM_KIND,
                'source_url' => $sourceUrl,
                'subject' => 'RohrFrisch Website - Neue Newsletter-Anfrage',
                'name' => null,
                'email' => $request->input('form_fields.email'),
                'phone' => null,
                'service' => null,
                'message' => null,
                'fields' => self::filterFields([
                    'E-Mail' => $request->input('form_fields.email'),
                ]),
            ];
        }

        $contactFields = self::extractContactFields($request);

        return [
            'form_kind' => self::CONTACT_FORM_KIND,
            'source_url' => $sourceUrl,
            'subject' => 'RohrFrisch Website - Neue Kontaktanfrage',
            'name' => $contactFields['name'],
            'email' => $contactFields['email'],
            'phone' => $contactFields['phone'],
            'service' => $contactFields['service'],
            'message' => $contactFields['message'],
            'fields' => self::filterFields([
                'Name' => $contactFields['name'],
                'E-Mail' => $contactFields['email'],
                'Telefon' => $contactFields['phone'],
                'Leistung' => $contactFields['service'],
                'Nachricht' => $contactFields['message'],
            ]),
        ];
    }

    public static function extractContactFields(Request $request): array
    {
        return collect(self::CONTACT_FIELD_PATTERNS)
            ->mapWithKeys(fn (string $pattern, string $field): array => [
                $field => self::firstMatchingInput($request, $pattern),
            ])
            ->all();
    }

    public static function rules(string $formKind): array
    {
        if ($formKind === self::FOOTER_FORM_KIND) {
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
