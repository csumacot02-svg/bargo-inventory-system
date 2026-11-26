<?php

namespace App\Support;

class AmountInWords
{
    private const ONES = [
        '', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
        'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
        'Seventeen', 'Eighteen', 'Nineteen',
    ];

    private const TENS = [
        '', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety',
    ];

    public static function peso(float $amount): string
    {
        $amount = round($amount, 2);
        $pesos = (int) floor($amount);
        $centavos = (int) round(($amount - $pesos) * 100);

        $words = self::integerToWords($pesos).' Peso'.($pesos === 1 ? '' : 's');

        if ($centavos > 0) {
            $words .= ' and '.self::integerToWords($centavos).' Centavo'.($centavos === 1 ? '' : 's');
        }

        return $words.' Only';
    }

    private static function integerToWords(int $n): string
    {
        if ($n === 0) {
            return 'Zero';
        }

        $parts = [];

        if ($n >= 1_000_000) {
            $parts[] = self::integerToWords((int) floor($n / 1_000_000)).' Million';
            $n %= 1_000_000;
        }
        if ($n >= 1_000) {
            $parts[] = self::integerToWords((int) floor($n / 1_000)).' Thousand';
            $n %= 1_000;
        }
        if ($n >= 100) {
            $parts[] = self::integerToWords((int) floor($n / 100)).' Hundred';
            $n %= 100;
        }
        if ($n >= 20) {
            $parts[] = self::TENS[(int) floor($n / 10)];
            $n %= 10;
        }
        if ($n > 0) {
            $parts[] = self::ONES[$n];
        }

        return trim(implode(' ', array_filter($parts)));
    }
}
