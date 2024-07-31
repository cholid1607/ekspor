<?php
namespace App\Services;

use Exception;

class Curl
{
    /**
     * Melakukan permintaan cURL ke URL tertentu dengan opsi tambahan.
     *
     * @param string $url URL yang akan diminta.
     * @param array $data Data yang akan dikirimkan (opsional).
     * @param string $method Metode HTTP yang digunakan (default: 'GET').
     * @param array $headers Header tambahan untuk permintaan (opsional).
     * @return mixed Respon dari permintaan cURL.
     * @throws Exception Jika terjadi kesalahan saat melakukan permintaan cURL.
     */
    public function makeRequest(string $url, array $data = [], string $method = 'GET', array $headers = [])
    {
        $ch = curl_init();
        $this->setCurlOptions($ch, $url, $data, $method, $headers);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception('Curl error: ' . curl_error($ch), 400);
        }

        curl_close($ch);

        return $response;
    }

    /**
     * Mengatur opsi-opsi cURL.
     *
     * @param $ch Resource cURL.
     * @param string $url URL yang akan diminta.
     * @param array $data Data yang akan dikirimkan (opsional).
     * @param string $method Metode HTTP yang digunakan (default: 'GET').
     * @param array $headers Header tambahan untuk permintaan (opsional).
     * @return void
     */
    private function setCurlOptions($ch, string $url, array $data, string $method, array $headers): void
    {
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => false,
            CURLOPT_HTTPHEADER     => $headers,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }
    }
}