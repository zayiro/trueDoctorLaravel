<?php

namespace App\Services\Certificates;

use Illuminate\Support\Facades\Log;

/**
 * Genera el código QR como imagen incrustable (data URI) con bacon/qr-code.
 * Usa la librería de composer si la aplicación ya la tiene; si no, la copia incluida en
 * app/Support/QrLib (no necesita composer). Si algo falla devuelve null y el PDF sale igual,
 * solo con el enlace escrito: nunca se rompe la emisión por el QR.
 */
class CertificateQr
{
    /** Deja la librería de QR cargada (la de composer o la incluida). */
    private static function boot(): bool
    {
        if (class_exists(\BaconQrCode\Writer::class)) {
            return true;
        }

        $loader = app_path('Support/QrLib/autoload.php');

        if (is_file($loader)) {
            require_once $loader;
        }

        return class_exists(\BaconQrCode\Writer::class);
    }

    /** ¿Se puede dibujar el QR en este servidor? (el SVG necesita la extensión xmlwriter) */
    public static function isAvailable(): bool
    {
        return self::boot() && extension_loaded('xmlwriter');
    }

    public static function dataUri(string $text, int $size = 240): ?string
    {
        if (!self::boot()) {
            return null;
        }

        // 1) SVG (necesita la extensión xmlwriter). dompdf lo dibuja con php-svg-lib.
        try {
            $renderer = new \BaconQrCode\Renderer\ImageRenderer(
                new \BaconQrCode\Renderer\RendererStyle\RendererStyle($size, 1),
                new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
            );
            $svg = (new \BaconQrCode\Writer($renderer))->writeString($text);

            if (is_string($svg) && str_contains($svg, '<svg')) {
                return 'data:image/svg+xml;base64,' . base64_encode($svg);
            }
        } catch (\Throwable $e) {
            Log::warning('certificate.qr.svg_failed', ['error' => substr($e->getMessage(), 0, 200)]);
        }

        // 2) PNG con Imagick, solo si el SVG no pudo.
        try {
            if (extension_loaded('imagick') && class_exists(\BaconQrCode\Renderer\Image\ImagickImageBackEnd::class)) {
                $renderer = new \BaconQrCode\Renderer\ImageRenderer(
                    new \BaconQrCode\Renderer\RendererStyle\RendererStyle($size, 1),
                    new \BaconQrCode\Renderer\Image\ImagickImageBackEnd('png')
                );
                $png = (new \BaconQrCode\Writer($renderer))->writeString($text);

                if (is_string($png) && strlen($png) > 100 && str_starts_with($png, "\x89PNG")) {
                    return 'data:image/png;base64,' . base64_encode($png);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('certificate.qr.png_failed', ['error' => substr($e->getMessage(), 0, 200)]);
        }

        return null;
    }
}
