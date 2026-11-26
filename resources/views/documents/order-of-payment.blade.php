<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment Order — {{ $document['serial_no'] }}</title>
    <style>
        :root { --line: #000; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #ddd;
            color: #000;
            font-family: "Times New Roman", Times, serif;
            font-size: 12px;
        }
        .toolbar {
            max-width: 820px;
            margin: 10px auto;
            display: flex;
            gap: 8px;
            padding: 0 10px;
        }
        .toolbar button, .toolbar a {
            font-family: Arial, sans-serif;
            font-size: 13px;
            padding: 8px 12px;
            cursor: pointer;
            text-decoration: none;
            border: 1px solid #333;
            background: #0a2a5c;
            color: #fff;
        }
        .toolbar a { background: #fff; color: #0a2a5c; }
        .form-sheet {
            width: 7.6in;
            max-width: 100%;
            margin: 0 auto 20px;
            background: #fff;
            padding: 0.35in 0.4in 0.45in;
            border: 1px solid #999;
            box-shadow: 0 6px 20px rgba(0,0,0,.12);
        }
        .letterhead {
            text-align: center;
            margin-bottom: 4px;
        }
        .letterhead img {
            display: inline-block;
            vertical-align: middle;
            height: 58px;
            width: auto;
            max-width: 48%;
            object-fit: contain;
        }
        .letterhead img.header-wide { max-width: 55%; height: 56px; }
        .quality-bar {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 12px;
            margin-top: 14px;
            padding-top: 8px;
            border-top: 1px solid #bbb;
        }
        .quality-bar img.doc-code {
            height: 42px;
            width: auto;
            object-fit: contain;
        }
        .quality-bar img.qs {
            height: 72px;
            width: auto;
            object-fit: contain;
        }
        .quality-bar img.socotec {
            height: 52px;
            width: auto;
            object-fit: contain;
        }
        .core-values {
            font-family: Poppins, Arial, sans-serif;
            font-size: 8.5px;
            text-align: center;
            margin: 6px 0 8px;
            line-height: 1.35;
        }
        .office-name {
            text-align: center;
            font-size: 12px;
            font-weight: 700;
            margin: 4px 0;
        }
        .doc-title {
            text-align: center;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.04em;
            margin: 8px 0 10px;
            padding: 4px 0;
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
        }
        .field-row {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            margin: 6px 0;
        }
        .field-row .fill {
            flex: 1;
            border-bottom: 1px solid #000;
            min-height: 18px;
            padding: 0 4px 2px;
            font-weight: 600;
        }
        .block { margin: 10px 0; line-height: 1.45; }
        .block.tight { margin: 6px 0; }
        .collector { margin: 12px 0 8px; font-weight: 700; }
        .or-line { margin: 10px 0; text-align: justify; }
        .or-line .name {
            display: inline-block;
            min-width: 180px;
            border-bottom: 1px solid #000;
            font-weight: 700;
            text-align: center;
        }
        .or-line .amount {
            display: inline-block;
            min-width: 100px;
            border-bottom: 1px solid #000;
            font-weight: 700;
            text-align: center;
        }
        .amount-parens {
            text-align: center;
            margin: 8px 0 12px;
            font-style: italic;
        }
        .amount-parens span {
            display: inline-block;
            min-width: 85%;
            border-bottom: 1px solid #000;
            padding: 2px 8px 4px;
        }
        .sign-block {
            margin: 16px 0 8px;
            text-align: center;
        }
        .sign-block .name {
            font-weight: 700;
            text-transform: uppercase;
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 260px;
            padding-bottom: 2px;
        }
        .sign-block .caption { font-size: 11px; margin-top: 2px; }
        .sign-block .role { font-size: 11px; }
        .section-label {
            font-weight: 700;
            margin: 14px 0 6px;
        }
        table.payment-details {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }
        table.payment-details th,
        table.payment-details td {
            border: 1px solid var(--line);
            padding: 4px 5px;
            vertical-align: middle;
        }
        table.payment-details thead th {
            text-align: center;
            font-weight: 700;
            vertical-align: middle;
        }
        table.payment-details td.num { text-align: right; }
        table.payment-details td.center { text-align: center; }
        table.payment-details tfoot td {
            font-weight: 700;
        }
        .release-grid {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            font-size: 11px;
        }
        .release-grid td {
            padding: 5px 4px;
            vertical-align: bottom;
        }
        .release-grid .lbl { white-space: nowrap; width: 1%; font-weight: 700; }
        .release-grid .val {
            border-bottom: 1px solid #000;
            min-width: 120px;
            padding-bottom: 2px;
        }
        .release-grid .sig-caption {
            font-size: 10px;
            text-align: center;
            padding-top: 2px;
        }
        .verify {
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
            font-size: 9px;
            font-family: Arial, sans-serif;
            color: #444;
        }
        #verify-qr { width: 64px; height: 64px; }
        @media print {
            body { background: #fff; }
            .toolbar { display: none !important; }
            .form-sheet {
                width: auto;
                margin: 0;
                border: none;
                box-shadow: none;
                padding: 0.25in 0.35in;
            }
            @page { size: letter portrait; margin: 12mm; }
        }
    </style>
</head>
<body>
    <div class="toolbar no-print">
        <button type="button" onclick="window.print()">Print / Save PDF</button>
        <a href="{{ route('requests.payment-order-excel', $inventoryRequest) }}">Download Excel</a>
        <a href="{{ route('requests.show', $inventoryRequest) }}">Back to request</a>
    </div>

    <article class="form-sheet">
        <div class="letterhead">
            <img src="{{ asset('images/slsu-header.png') }}" alt="SLSU" class="header-wide">
            <img src="{{ asset('images/bagong-pilipinas.svg') }}" alt="Bagong Pilipinas">
        </div>
        <p class="core-values">Excellence | Service | Leadership and Good Governance | Innovation | Social Responsibility | Integrity | Professionalism | Spirituality</p>

        <p class="office-name">Business Affairs and Resource Generation Office (BARGO)</p>
        <h1 class="doc-title">PAYMENT ORDER</h1>

        <div class="field-row">
            <div>Serial No.: <span class="fill" style="max-width:200px;display:inline-block">{{ $document['serial_no'] }}</span></div>
            <div>Date: <span class="fill" style="max-width:160px;display:inline-block">{{ optional($document['form_date'])->format('F d, Y') }}</span></div>
        </div>

        <p class="collector">The Collector Officer<br>Cash/Treasury Unit</p>

        <p class="or-line block">
            Please issue Official Receipt in the favor of
            <span class="name">{{ $document['payee_name'] }}</span>
            in the amount of
            <span class="amount">₱{{ number_format($document['amount_figures'], 2) }}</span>
            as payment for
            <span class="name" style="min-width:140px">{{ $document['payment_for'] }}</span>
        </p>
        @if(! empty($document['client_id_number']) || ! empty($document['client_office']))
            <p class="block tight" style="font-size:11px;margin-top:4px">
                @if(! empty($document['client_id_number']))
                    ID No.: <strong>{{ $document['client_id_number'] }}</strong>
                @endif
                @if(! empty($document['client_id_number']) && ! empty($document['client_office']))
                    ·
                @endif
                @if(! empty($document['client_office']))
                    Office/Dept: <strong>{{ $document['client_office'] }}</strong>
                @endif
            </p>
        @endif
        <p class="amount-parens">(<span>{{ $document['amount_words'] }}</span>)</p>

        <div class="sign-block">
            <div class="name">{{ $document['coordinator']['name'] }}</div>
            <div class="caption">Signature over Printed Name</div>
            <div class="role">{{ $document['coordinator']['title'] }}</div>
        </div>

        <p class="section-label">Details of Payment:</p>

        <table class="payment-details">
            <thead>
                <tr>
                    <th style="width:22%">Type of Collection<br><span style="font-weight:400;font-size:10px">(Institutional / Non-Institutional)</span></th>
                    <th style="width:10%">Code</th>
                    <th style="width:10%">Quantity</th>
                    <th style="width:28%">Item Particular</th>
                    <th style="width:15%">Unit Price</th>
                    <th style="width:15%">TOTAL</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($document['detail_lines'] as $row)
                    <tr>
                        <td>{{ $row['collection_type'] }}</td>
                        <td class="center">{{ $row['code'] }}</td>
                        <td class="center">
                            @if($row['quantity'] !== '')
                                {{ rtrim(rtrim(number_format((float) $row['quantity'], 2), '0'), '.') }}
                            @endif
                        </td>
                        <td>{{ $row['particular'] }}</td>
                        <td class="num">
                            @if($row['unit_price'] !== '')
                                ₱{{ number_format((float) $row['unit_price'], 2) }}
                            @endif
                        </td>
                        <td class="num">
                            @if($row['total'] !== '')
                                ₱{{ number_format((float) $row['total'], 2) }}
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" style="text-align:right">TOTAL</td>
                    <td class="num">₱{{ number_format($document['detail_total'], 2) }}</td>
                </tr>
            </tfoot>
        </table>

        <p class="section-label" style="margin-top:12px">Release form:</p>

        <table class="release-grid">
            <tr>
                <td class="lbl">Date Released:</td>
                <td class="val">{{ optional($document['release']['date_released'])->format('F d, Y') }}</td>
                <td class="lbl">OR No.</td>
                <td class="val">{{ $document['release']['or_number'] ?? '—' }}</td>
            </tr>
            <tr>
                <td class="lbl">Released by:</td>
                <td class="val" style="font-weight:700;text-transform:uppercase">{{ $document['release']['released_by'] }}</td>
                <td class="lbl">Amount Paid:</td>
                <td class="val">₱{{ number_format($document['release']['amount_paid'], 2) }}</td>
            </tr>
            <tr>
                <td></td>
                <td class="sig-caption">Signature over Printed Name</td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td></td>
                <td class="sig-caption" style="font-weight:700">{{ $document['release']['released_by_title'] }}</td>
                <td></td>
                <td></td>
            </tr>
            <tr><td colspan="4" style="height:8px"></td></tr>
            <tr>
                <td class="lbl">Received by:</td>
                <td class="val" style="font-weight:600">{{ $document['release']['received_by'] }}</td>
                <td colspan="2"></td>
            </tr>
            <tr>
                <td></td>
                <td class="sig-caption">Customer's Signature</td>
                <td colspan="2"></td>
            </tr>
        </table>

        <div class="quality-bar" aria-label="Document control and certifications">
            <img src="{{ asset('images/oop-doc-code.png') }}" alt="Doc. Code SLSU-QF-IG01" class="doc-code">
            <img src="{{ asset('images/oop-qs-badge.png') }}" alt="QS Rated Good" class="qs">
            <img src="{{ asset('images/oop-socotec.png') }}" alt="SOCOTEC ISO 9001" class="socotec">
        </div>

        <div class="verify">
            <span>Verify: {{ $document['verified_url'] }}</span>
            <canvas id="verify-qr" width="64" height="64" aria-hidden="true"></canvas>
        </div>
    </article>

    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js" defer></script>
    <script defer>
        document.addEventListener('DOMContentLoaded', function () {
            const url = @json($document['verified_url']);
            const canvas = document.getElementById('verify-qr');
            if (canvas && typeof QRCode !== 'undefined') {
                QRCode.toCanvas(canvas, url, { width: 64, margin: 0 });
            }
        });
    </script>
</body>
</html>
