<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $payload['subject'] }}</title>
</head>
<body style="font-family: Arial, sans-serif; color: #222; line-height: 1.5;">
    <h2 style="margin-bottom: 16px;">{{ $payload['subject'] }}</h2>

    <p><strong>Source Page:</strong> {{ $payload['source_url'] ?: 'N/A' }}</p>
    <p><strong>Form Type:</strong> {{ ucfirst($payload['form_kind']) }}</p>

    @foreach($payload['fields'] as $label => $value)
        <p><strong>{{ $label }}:</strong> {{ $value }}</p>
    @endforeach
</body>
</html>
