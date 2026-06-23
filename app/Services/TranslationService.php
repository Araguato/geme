<?php

namespace App\Services;

class TranslationService
{
    /**
     * Traduce un texto al chino simplificado (zh-CN) usando el endpoint
     * libre de Google Translate. Si falla, retorna null y el usuario puede
     * editar el campo manualmente.
     */
    public function translateToChinese(?string $text): ?string
    {
        if (empty($text)) {
            return null;
        }

        try {
            $url = 'https://translate.googleapis.com/translate_a/single?client=gtx&sl=es&tl=zh-CN&dt=t&q=' . urlencode($text);
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $response = curl_exec($ch);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error !== '' || $response === false) {
                return null;
            }

            $json = json_decode($response, true);
            if (!is_array($json) || !isset($json[0])) {
                return null;
            }

            $translated = '';
            foreach ($json[0] as $segment) {
                if (isset($segment[0]) && is_string($segment[0])) {
                    $translated .= $segment[0];
                }
            }

            return $translated !== '' ? $translated : null;
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }
}
