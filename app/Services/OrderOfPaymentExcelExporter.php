<?php

namespace App\Services;

use App\Models\InventoryRequest;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderOfPaymentExcelExporter
{
    public function __construct(
        private readonly OrderOfPaymentDocumentBuilder $documentBuilder,
        private readonly BargoDocumentTemplateService $templates,
    ) {}

    public function download(InventoryRequest $inventoryRequest): StreamedResponse
    {
        $payload = $this->documentBuilder->build($inventoryRequest);
        $document = $payload['document'];
        $path = $this->templates->excelTemplatePath();

        $spreadsheet = IOFactory::load($path);
        $this->fillWorkbook($spreadsheet, $document);

        $filename = 'Payment-Order-'.$inventoryRequest->request_no.'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @param  array<string, mixed>  $document
     */
    private function fillWorkbook(Spreadsheet $spreadsheet, array $document): void
    {
        $filled = false;

        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            $marker = trim((string) $sheet->getCell('A12')->getValue());
            $title = strtoupper(trim((string) $sheet->getCell('A9')->getValue()));

            if (! str_contains($marker, 'Serial No') && ! str_contains($title, 'PAYMENT ORDER') && ! str_contains($title, 'ORDER OF PAYMENT')) {
                continue;
            }

            $this->fillFormCopy($sheet, $document, 'left');
            $this->fillFormCopy($sheet, $document, 'right');
            $filled = true;
        }

        // If the workbook has no recognizable form sheet, leave it as uploaded.
        if (! $filled) {
            return;
        }
    }

    /**
     * @param  array<string, mixed>  $document
     */
    private function fillFormCopy(Worksheet $sheet, array $document, string $side): void
    {
        $map = $side === 'right'
            ? [
                'serial' => 'K12',
                'date' => 'K13',
                'payee' => 'N20',
                'amount_label' => 'P20',
                'amount_words' => 'N21',
                'purpose' => 'J22',
                'coordinator' => 'N25',
                'coordinator_title' => 'N27',
                'released_date' => 'L42',
                'or_number' => 'P42',
                'released_by' => 'L45',
                'amount_paid' => 'P45',
                'received_by' => 'L50',
                'detail_start' => 32,
                'cols' => ['type' => 'J', 'code' => 'L', 'qty' => 'M', 'particular' => 'N', 'price' => 'P', 'total' => 'Q'],
            ]
            : [
                'serial' => 'B12',
                'date' => 'B13',
                'payee' => 'E20',
                'amount_label' => 'G20',
                'amount_words' => 'E21',
                'purpose' => 'A22',
                'coordinator' => 'E25',
                'coordinator_title' => 'E27',
                'released_date' => 'C42',
                'or_number' => 'G42',
                'released_by' => 'C45',
                'amount_paid' => 'G45',
                'received_by' => 'C50',
                'detail_start' => 32,
                'cols' => ['type' => 'A', 'code' => 'C', 'qty' => 'D', 'particular' => 'E', 'price' => 'G', 'total' => 'H'],
            ];

        $amount = (float) ($document['amount_figures'] ?? 0);
        $amountPaid = (float) ($document['release']['amount_paid'] ?? 0);
        $formDate = $document['form_date'] ?? null;
        $releasedAt = $document['release']['date_released'] ?? null;

        $sheet->setCellValue($map['serial'], (string) ($document['serial_no'] ?? ''));
        $sheet->setCellValue($map['date'], $formDate ? $formDate->format('F d, Y') : '');
        $sheet->setCellValue($map['payee'], (string) ($document['payee_name'] ?? ''));
        $sheet->setCellValue($map['amount_label'], 'in the amount of  ₱'.number_format($amount, 2));
        $sheet->setCellValue($map['amount_words'], '( '.(string) ($document['amount_words'] ?? '').' )');
        $sheet->setCellValue($map['purpose'], (string) ($document['payment_for'] ?? ''));
        $sheet->setCellValue($map['coordinator'], strtoupper((string) ($document['coordinator']['name'] ?? '')));
        $sheet->setCellValue($map['coordinator_title'], (string) ($document['coordinator']['title'] ?? 'Coordinator, BARGO'));
        $sheet->setCellValue($map['released_date'], $releasedAt ? $releasedAt->format('F d, Y') : '');
        $sheet->setCellValue($map['or_number'], (string) ($document['release']['or_number'] ?? ''));
        $sheet->setCellValue($map['released_by'], (string) ($document['release']['released_by'] ?? ''));
        $sheet->setCellValue($map['amount_paid'], $amountPaid > 0 ? '₱'.number_format($amountPaid, 2) : '');
        $sheet->setCellValue($map['received_by'], (string) ($document['release']['received_by'] ?? ''));

        $lines = array_values($document['detail_lines'] ?? []);
        for ($i = 0; $i < 6; $i++) {
            $row = $map['detail_start'] + $i;
            $line = $lines[$i] ?? null;
            $cols = $map['cols'];

            if (! $line || ($line['particular'] ?? '') === '') {
                $sheet->setCellValue($cols['type'].$row, '');
                $sheet->setCellValue($cols['code'].$row, '');
                $sheet->setCellValue($cols['qty'].$row, '');
                $sheet->setCellValue($cols['particular'].$row, '');
                $sheet->setCellValue($cols['price'].$row, '');
                $sheet->setCellValue($cols['total'].$row, '');

                continue;
            }

            $qty = $line['quantity'];
            $price = (float) $line['unit_price'];
            $total = (float) $line['total'];

            $sheet->setCellValue($cols['type'].$row, (string) ($line['collection_type'] ?? ''));
            $sheet->setCellValue($cols['code'].$row, (string) ($line['code'] ?? ''));
            $sheet->setCellValue(
                $cols['qty'].$row,
                is_numeric($qty) ? rtrim(rtrim(number_format((float) $qty, 2), '0'), '.') : ''
            );
            $sheet->setCellValue($cols['particular'].$row, (string) ($line['particular'] ?? ''));
            $sheet->setCellValue($cols['price'].$row, number_format($price, 2));
            $sheet->setCellValue($cols['total'].$row, number_format($total, 2));
        }
    }
}
