<?php

namespace App\Services\Fiscal;

/**
 * Validación de FORMATO de RIF y cédula (no consulta al SENIAT ni comprueba el dígito
 * verificador). Acepta "J-12345678-9", "J123456789", "V-12345678", "E-81234567".
 */
class RifValidator
{
    /** @return array{type:string,number:string,verifier:?string,normalized:string}|null */
    public static function parse(?string $value): ?array
    {
        $v = strtoupper(preg_replace('/[\s.\-]/', '', (string) $value));

        if (preg_match('/^([VEJGPC])(\d{7,9})$/', $v, $m)) {
            $type = $m[1];
            $digits = $m[2];
        } else {
            return null;
        }

        // Persona jurídica / gubernamental: RIF con 9 dígitos (8 + verificador).
        if (in_array($type, ['J', 'G', 'C'], true)) {
            if (strlen($digits) !== 9) {
                return null;
            }
            $number = substr($digits, 0, 8);
            $verifier = substr($digits, 8);

            return ['type' => $type, 'number' => $number, 'verifier' => $verifier, 'normalized' => "$type-$number-$verifier"];
        }

        // V/E/P: cédula o pasaporte, con o sin dígito verificador de RIF.
        if (strlen($digits) === 9) {
            return ['type' => $type, 'number' => substr($digits, 0, 8), 'verifier' => substr($digits, 8), 'normalized' => "$type-" . substr($digits, 0, 8) . '-' . substr($digits, 8)];
        }

        return ['type' => $type, 'number' => $digits, 'verifier' => null, 'normalized' => "$type-$digits"];
    }

    public static function isValid(?string $value): bool
    {
        return self::parse($value) !== null;
    }
}
