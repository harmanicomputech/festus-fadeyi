<?php

namespace App\Services;

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

/**
 * QR images. A guest QR encodes only an opaque check-in token prefixed
 * with "FF85:" — never a name, email or phone number.
 */
class QrCodes
{
    public const PREFIX = 'FF85:';

    public function png(string $token, int $scale = 10): string
    {
        return $this->render(self::PREFIX.$token, $scale);
    }

    /** QR pointing at a URL (e.g. the donation page for venue table cards). */
    public function urlPng(string $url, int $scale = 12): string
    {
        return $this->render($url, $scale);
    }

    private function render(string $data, int $scale): string
    {
        $options = new QROptions([
            'outputInterface' => QRGdImagePNG::class,
            'eccLevel' => EccLevel::M,
            'scale' => $scale,
            'quietzoneSize' => 3,
            'outputBase64' => false,
            'bgColor' => [255, 255, 255],
        ]);

        return (new QRCode($options))->render($data);
    }

    public static function tokenFromScan(string $scanned): ?string
    {
        $scanned = trim($scanned);
        if (str_starts_with($scanned, self::PREFIX)) {
            $scanned = substr($scanned, strlen(self::PREFIX));
        }

        return preg_match('/^[A-Za-z0-9]{40}$/', $scanned) ? $scanned : null;
    }
}
