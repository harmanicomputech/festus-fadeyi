<?php

namespace App\Services;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\CSV\Writer as CsvWriter;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** CSV / Excel downloads for the admin dashboard. */
class Exporter
{
    /**
     * @param  array<string, array{0: list<string>, 1: iterable<array<int, mixed>>}>  $sheets  name => [headers, rows]
     */
    public function download(string $basename, string $format, array $sheets): BinaryFileResponse
    {
        $format = $format === 'xlsx' ? 'xlsx' : 'csv';
        $path = tempnam(sys_get_temp_dir(), 'ff85').'.'.$format;
        $writer = $format === 'xlsx' ? new XlsxWriter : new CsvWriter;
        $writer->openToFile($path);

        $header = (new Style)->withFontBold(true)->withFontColor('FFFFFF')->withBackgroundColor('0B1730');
        $first = true;

        foreach ($sheets as $name => [$headers, $rows]) {
            if ($format === 'csv' && ! $first) {
                break; // CSV holds a single table
            }
            if ($writer instanceof XlsxWriter) {
                $sheet = $first ? $writer->getCurrentSheet() : $writer->addNewSheetAndMakeItCurrent();
                $sheet->setName(mb_substr($name, 0, 31));
            }
            $writer->addRow(Row::fromValuesWithStyle($headers, $header));
            foreach ($rows as $row) {
                $writer->addRow(Row::fromValues(array_map([$this, 'cell'], array_values($row))));
            }
            $first = false;
        }

        $writer->close();

        $filename = $basename.'-'.now()->format('Y-m-d-Hi').'.'.$format;

        return response()->download($path, $filename, [
            'Content-Type' => $format === 'xlsx' ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' : 'text/csv; charset=utf-8',
            'Cache-Control' => 'no-store',
        ])->deleteFileAfterSend();
    }

    /** Neutralise spreadsheet formula injection in user-supplied text. */
    private function cell(mixed $value): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i');
        }
        if (is_string($value) && $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$value;
        }

        return $value;
    }
}
