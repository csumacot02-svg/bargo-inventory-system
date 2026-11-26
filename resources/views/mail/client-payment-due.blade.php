@component('mail::message')
# Payment reminder

Hello {{ $inventoryRequest->clientDisplayName() }},

@if ($daysRemaining === 0)
Your installment with **SLSU BARGO (Hinunangan Campus)** is **due today**. Please pay the corresponding amount below.
@elseif ($daysRemaining === 1)
You have **1 day** before your installment is due. Please pay the corresponding amount below.
@else
You have **{{ $daysRemaining }} days** before your installment is due. Please pay the corresponding amount below.
@endif

| Detail | |
|:--|:--|
| Request | {{ $inventoryRequest->request_no }} |
| Purpose | {{ $inventoryRequest->purpose }} |
| Installment | #{{ $installment->sequence }} |
| Due date | {{ optional($installment->due_date)->format('F d, Y') }} |
| Days remaining | **{{ $daysRemaining === 0 ? 'Due today' : $daysRemaining.' day'.($daysRemaining === 1 ? '' : 's') }}** |
| Amount to pay | **₱{{ number_format($installment->balanceDue(), 2) }}** |
| Remaining balance | ₱{{ number_format($inventoryRequest->balanceDue(), 2) }} |

Please settle this amount at the Cash/Treasury Unit using your Payment Order / OR reference.

You will receive a reminder each day until the due date.

Thank you.
@endcomponent
