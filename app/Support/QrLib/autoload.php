<?php

/*
 * Copia incluida de las librerías de código QR, para no depender de composer ni de extensiones del servidor:
 *   - bacon/qr-code (BSD-2-Clause, Ben Scholzen) en ./BaconQrCode
 *   - dasprid/enum  (BSD-2-Clause, Ben Scholzen) en ./Enum
 * Las licencias están en esta carpeta. No se modificó ningún archivo de las librerías.
 * Solo se usa si la aplicación NO tiene ya bacon/qr-code instalada por composer (ver CertificateQr).
 */

namespace {
    spl_autoload_register(function (string $class): void {
        $map = [
            'BaconQrCode\\'  => __DIR__ . '/BaconQrCode/',
            'DASPRiD\\Enum\\' => __DIR__ . '/Enum/',
        ];

        foreach ($map as $prefix => $dir) {
            if (strncmp($class, $prefix, strlen($prefix)) === 0) {
                $file = $dir . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';

                if (is_file($file)) {
                    require_once $file;
                }

                return;
            }
        }
    });
}

namespace BaconQrCode\Encoder {
    // La librería llama a iconv() sin prefijo dentro de este namespace. Si el servidor no tiene la extensión
    // iconv, PHP usa esta función en su lugar (con mbstring, que Laravel ya exige).
    if (!\function_exists('iconv')) {
        function iconv(string $fromEncoding, string $toEncoding, string $string): string|false
        {
            $to = \preg_replace('#//.*$#', '', $toEncoding);

            try {
                return \mb_convert_encoding($string, $to, $fromEncoding);
            } catch (\Throwable $e) {
                return false;
            }
        }
    }
}
