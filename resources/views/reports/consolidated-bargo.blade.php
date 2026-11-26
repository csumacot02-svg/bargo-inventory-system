<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Consolidated BARGO Products Report — {{ $report['year'] }}</title>
    <style>
        :root {
            --navy: #0a2a5c;
            --maroon: #7a1f2b;
            --line: #222;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #e8e2d4;
            color: #111;
            font-family: "Times New Roman", Times, serif;
        }
        .toolbar {
            max-width: 1200px;
            margin: 12px auto;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: end;
            padding: 0 12px;
        }
        .toolbar label { font-size: 13px; font-family: Arial, sans-serif; display: block; margin-bottom: 4px; }
        .toolbar input, .toolbar button, .toolbar a {
            font-family: Arial, sans-serif;
            font-size: 13px;
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }
        .toolbar button, .toolbar a.btn {
            background: var(--navy);
            color: #fff;
            border: none;
            text-decoration: none;
            cursor: pointer;
        }
        .toolbar a.btn-secondary { background: #fff; color: var(--navy); border: 1px solid var(--navy); }
        .sheet {
            max-width: 1200px;
            margin: 0 auto 24px;
            background: #fff;
            padding: 18px 20px 28px;
            border: 1px solid #ccc;
            box-shadow: 0 8px 24px rgba(0,0,0,.08);
        }
        .banner-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 18px;
            margin: 0 auto 10px;
        }
        .banner-row img {
            display: block;
            height: 72px;
            width: auto;
            max-width: 46%;
            object-fit: contain;
        }
        .banner-row img.slsu-header {
            height: 70px;
            max-width: 58%;
        }
        .core-values {
            margin: 6px 0 0;
            padding-bottom: 8px;
            border-bottom: 3px solid #111;
            text-align: center;
            font-size: 11px;
            letter-spacing: 0.01em;
            line-height: 1.35;
        }
        .title {
            text-align: center;
            font-size: 18px;
            font-weight: 700;
            letter-spacing: .02em;
            margin: 10px 0 14px;
            text-transform: uppercase;
        }
        table.report {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }
        table.report th, table.report td {
            border: 1px solid var(--line);
            padding: 4px 5px;
            vertical-align: middle;
        }
        table.report thead th {
            background: #f3f3f3;
            font-weight: 700;
            text-align: center;
        }
        table.report td.num { text-align: right; font-variant-numeric: tabular-nums; }
        table.report td.center { text-align: center; }
        table.report td.product { text-align: left; font-weight: 600; }
        table.report tfoot td {
            font-weight: 700;
            background: #fafafa;
        }
        .meta {
            margin-top: 10px;
            font-size: 12px;
        }
        .signs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 36px;
            font-size: 13px;
        }
        .signs .label { margin-bottom: 28px; }
        .signs .name { font-weight: 700; text-transform: uppercase; }
        .signs .role { font-size: 12px; }
        .footnote {
            margin-top: 18px;
            font-size: 10px;
            color: #555;
            font-family: Arial, sans-serif;
        }
        @media print {
            body { background: #fff; }
            .toolbar { display: none !important; }
            .sheet {
                max-width: none;
                margin: 0;
                border: none;
                box-shadow: none;
                padding: 0;
            }
            @page { size: landscape; margin: 10mm; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <form method="GET" action="{{ route('reports.consolidated-bargo') }}">
            <label for="year">Report Year</label>
            <input id="year" type="number" name="year" min="2020" max="2100" value="{{ $year }}">
            <button type="submit">Generate</button>
        </form>
        <button type="button" onclick="window.print()">Print / Save PDF</button>
        <a class="btn btn-secondary" href="{{ route('reports.index') }}">Back to Reports</a>
    </div>

    <article class="sheet">
        <div class="banner-row">
            <img src="{{ asset('images/slsu-header.png') }}" alt="Southern Leyte State University" class="slsu-header">
            <img src="{{ asset('images/bagong-pilipinas.svg') }}" alt="Bagong Pilipinas">
        </div>
        <p class="core-values">Excellence | Service | Leadership and Good Governance | Innovation | Social Responsibility | Integrity | Professionalism | Spirituality</p>

        <h1 class="title">Consolidated BARGO Products Report — {{ $report['year'] }}</h1>

        <table class="report">
            <thead>
                <tr>
                    <th rowspan="2" style="width:28px">#</th>
                    <th rowspan="2">PRODUCTS</th>
                    <th colspan="12">MONTH</th>
                    <th rowspan="2">YEARLY TOTAL</th>
                    <th rowspan="2">Highest-Selling<br>Product Rank</th>
                </tr>
                <tr>
                    @foreach ($report['month_labels'] as $label)
                        <th>{{ $label }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($report['rows'] as $row)
                    <tr>
                        <td class="center">{{ $row['no'] }}</td>
                        <td class="product">{{ $row['product'] }}</td>
                        @foreach ($row['months'] as $amount)
                            <td class="num">
                                @if ($amount > 0)
                                    {{ number_format($amount, 2) }}
                                @else
                                    -
                                @endif
                            </td>
                        @endforeach
                        <td class="num">{{ $row['yearly_total'] > 0 ? number_format($row['yearly_total'], 2) : '0' }}</td>
                        <td class="center">{{ $row['rank'] ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2">TOTAL ALL PRODUCT MONTHLY</td>
                    @foreach ($report['monthly_totals'] as $total)
                        <td class="num">{{ number_format($total, 2) }}</td>
                    @endforeach
                    <td class="num">{{ number_format($report['overall_total'], 2) }}</td>
                    <td></td>
                </tr>
                <tr>
                    <td colspan="2">OVERALL TOTAL</td>
                    <td class="num" colspan="12">{{ number_format($report['overall_total'], 2) }}</td>
                    <td class="num">{{ number_format($report['overall_total'], 2) }}</td>
                    <td class="center">{{ $report['highest_selling'] }}</td>
                </tr>
            </tfoot>
        </table>

        <p class="meta">
            Total inventory value: <strong>₱{{ number_format($report['inventory_value'], 2) }}</strong>
            · Product sales ({{ $report['year'] }}): <strong>₱{{ number_format($report['overall_total'], 2) }}</strong>
            · Highest-selling: <strong>{{ $report['highest_selling'] }}</strong>
            · Generated {{ $report['generated_at']->format('F d, Y h:i A') }}
        </p>

        <div class="signs">
            <div>
                <div class="label">Prepared by:</div>
                <div class="name">{{ $report['signatories']['prepared_by_name'] }}</div>
                <div class="role">{{ $report['signatories']['prepared_by_title'] }}</div>
            </div>
            <div>
                <div class="label">Approved by:</div>
                <div class="name">{{ $report['signatories']['approved_by_name'] }}</div>
                <div class="role">{{ $report['signatories']['approved_by_title'] }}</div>
            </div>
        </div>

        <p class="footnote">
            Based on inventory items in BARGO · SLSU–Hinunangan Campus. Monthly figures are released requests
            (quantity × unit price). For inventory monitoring; not an official accounting voucher.
        </p>
    </article>
</body>
</html>
