@component('mail::message')
# BARGO daily digest — {{ $payload['date'] }}

| Check | Count |
|:--|--:|
| Pending user approvals | {{ $payload['pending_users'] }} |
| Low-stock items | {{ $payload['low_stock'] }} |
| Expiring within 7 days | {{ $payload['expiring_7'] }} |
| Overdue credit installments | {{ $payload['overdue_credit'] }} |

@component('mail::button', ['url' => config('app.url')])
Open BARGO
@endcomponent

Keep the scheduler running so this mail is sent each morning.
@endcomponent
