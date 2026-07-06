<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Publication review update</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.5;">
    <p>Hello {{ $recipientName !== '' ? $recipientName : 'there' }},</p>

    <p>
        Your publication request for project <strong>{{ $reviewRequest->project?->code ?? 'N/A' }}</strong>
        has been marked as <strong>{{ $statusLabel }}</strong>.
    </p>

    <p>
        <strong>Data type:</strong> {{ \App\Support\PublicationReviewRegistry::label($reviewRequest->data_type, $reviewRequest->literature_type) }}<br>
        <strong>Submitted:</strong> {{ optional($reviewRequest->submitted_at)->format('Y-m-d H:i') ?? 'N/A' }}<br>
        <strong>Reviewed:</strong> {{ optional($reviewRequest->reviewed_at)->format('Y-m-d H:i') ?? 'N/A' }}
    </p>

    @if (!empty($reviewRequest->reviewer_message))
        <p><strong>Admin message:</strong></p>
        <p style="white-space: pre-line;">{{ $reviewRequest->reviewer_message }}</p>
    @endif

    <p>You can open the publication page in Aleph∞One to review the current status of your submission.</p>

    <p>Best regards,<br>Aleph∞One</p>
</body>
</html>
