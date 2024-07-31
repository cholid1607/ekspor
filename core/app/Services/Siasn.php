<?php
namespace App\Services;

use App\Services\Curl;
use SiASN\Sdk\SiasnClient;

/**
 * Class Siasn
 * 
 * Kelas layanan untuk berinteraksi dengan API SIASN.
 */
class Siasn extends Curl
{
    private $ssoBaseUrl     = 'https://sso-siasn.bkn.go.id/auth/realms/public-siasn/protocol/openid-connect/token';
    private $wsoBaseUrl     = 'https://apimws.bkn.go.id/oauth2/token';
    private $serviceBaseUrl = 'https://apimws.bkn.go.id:8243/apisiasn/1.0/';

    /**
     * Mendapatkan token SSO, menggunakan cache sesi jika tersedia.
     *
     * @return string|array Data token yang ter-decode atau deskripsi error.
     */
    public function getTokenSso()
    {
        $data = [
            "client_id"  => getenv("siasn.client_id"),
            "grant_type" => "password",
            "username"   => getenv("siasn.username"),
            "password"   => getenv("siasn.password")
        ];
        return $this->getToken('sso_access_token', $this->ssoBaseUrl, $data);
    }

    /**
     * Mendapatkan token WSO, menggunakan cache sesi jika tersedia.
     *
     * @return string|array Data token yang ter-decode atau deskripsi error.
     */
    public function getTokenWso()
    {
        $data    = ["grant_type" => "client_credentials"];
        $headers = ['Authorization: Basic ' . getenv('siasn.basic')];
        return $this->getToken('wso_access_token', $this->wsoBaseUrl, $data, $headers);
    }

    /**
     * Mendapatkan token dari API, menggunakan cache sesi jika tersedia.
     *
     * @param string $sessionKey Kunci sesi untuk menyimpan token
     * @param string $url URL untuk mendapatkan token
     * @param array $data Data yang dikirimkan untuk permintaan token
     * @param array $additionalHeaders Header tambahan untuk permintaan token
     * @return string|array Data token yang ter-decode atau deskripsi error.
     */
    private function getToken($sessionKey, $url, $data, $additionalHeaders = [])
    {
        if (session()->has($sessionKey)) {
            return session($sessionKey);
        }

        $headers         = array_merge(['Content-Type: application/x-www-form-urlencoded'], $additionalHeaders);
        $decodedResponse = $this->makeRequestAndDecode($url, $data, 'POST', $headers);

        if (isset($decodedResponse['error'])) {
            return $decodedResponse['error_description'];
        }

        session()->setTempdata($sessionKey, $decodedResponse['access_token'], $decodedResponse['expires_in'] - 10);

        return $decodedResponse['access_token'];
    }

    /**
     * Mendapatkan riwayat pemberhentian instansi.
     *
     * @param string $tanggalAwal Tanggal awal dalam format d-m-Y
     * @param string $tanggalAkhir Tanggal akhir dalam format d-m-Y
     * @return array Data riwayat pemberhentian instansi
     */
    public function getRiwayatPemberhentianInstansi(string $tanggalAwal = '', string $tanggalAkhir = '')
    {
        $endPoint = 'pns/list-pensiun-instansi?' . http_build_query([
            "tglAwal"  => date('d-m-Y', strtotime($tanggalAwal)),
            "tglAkhir" => date('d-m-Y', strtotime($tanggalAkhir))
        ]);

        $decodedResponse = $this->makeRequestAndDecode($this->serviceBaseUrl . $endPoint, [], 'GET', [
            'Auth: bearer ' . $this->getTokenSso(),
            'Authorization: Bearer ' . $this->getTokenWso()
        ]);

        return $decodedResponse['data'] ?? [];
    }

    /**
     * Mengunduh file dari API dan menyimpannya secara lokal.
     *
     * @param string $pathSk Path file di server API
     * @param string $fileName Nama file lokal untuk menyimpan file
     * @param string $path Direktori lokal untuk menyimpan file
     * @return string Pesan sukses atau error
     */
    public function downloadFile(string $pathSk = '', string $fileName = '', string $path = '')
    {
        if (empty($pathSk)) {
            return 'Path SK tidak boleh kosong';
        }

        $localFolder = WRITEPATH . $path;

        if (!is_dir($localFolder)) {
            mkdir($localFolder, 0755, true);
        }

        $url  = $this->serviceBaseUrl . 'download-dok?filePath=' . $pathSk;
        $file = $this->makeRequest($url, [], 'GET', [
            'Auth: bearer ' . $this->getTokenSso(),
            'Authorization: Bearer ' . $this->getTokenWso()
        ]);

        if ($this->isJson($file)) {
            $decode = json_decode($file, true);
            return $decode['message'] ?? 'Unknown error';
        }

        file_put_contents("$localFolder/$fileName", $file);
        return $fileName;
    }

    /**
     * Memeriksa apakah sebuah string adalah JSON.
     *
     * @param string $string String yang akan diperiksa
     * @return bool True jika string adalah JSON valid, false jika tidak
     */
    private function isJson($string)
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    /**
     * Membuat permintaan HTTP dan mendecode respons JSON.
     *
     * @param string $url URL yang dituju
     * @param array $data Data yang dikirimkan
     * @param string $method Metode HTTP (GET, POST, dll)
     * @param array $headers Header tambahan untuk permintaan
     * @return array Respons yang sudah ter-decode
     */
    private function makeRequestAndDecode($url, $data, $method, $headers)
    {
        $response = $this->makeRequest($url, $data, $method, $headers);
        return json_decode($response, true);
    }

    public function call()
    {
        $config = [
            "consumerKey"    => "9NB75yEfBlFhkSuPojsjO9yhmUYa",
            "consumerSecret" => "3d45kbbsstx9dxztLNWwW9aRP1ca",
            "clientId"       => "kabmglclient",
            "username"       => "199202062020121004",
            "password"       => "Trinifornia13"
        ];
   
        return new SiasnClient($config);
    }
}
