@component('mail::message')
# Fully paid

Hello {{ $inventoryRequest->clientDisplayName() }},

Your account with **SLSU BARGO (Hinunangan Campus)** is now **fully paid**.

| Detail | |
|:--|:--|
| Request | {{ $inventoryRequest->request_no }} |
| Purpose | {{ $inventoryRequest->purpose }} |
| Total amount | ₱{{ number_format($inventoryRequest->totalAmount(), 2) }} |
| Amount paid | ₱{{ number_format($inventoryRequest->amount_paid, 2) }} |
| OR / Serial | {{ $inventoryRequest->or_number ?: '—' }} |
| Paid on | {{ optional($inventoryRequest->paid_at)->format('F d, Y h:i A') }} |

Thank you for completing your payment.
@endcomponent
