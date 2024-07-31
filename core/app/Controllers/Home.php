<?php

namespace App\Controllers;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Services\Curl;
use App\Services\Siasn;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

class Home extends BaseController
{
    protected $sipgan;
    public function __construct()
    {
        $this->sipgan = db_connect('sipgan');
    }

    public function index(): string
    {
        return view('welcome_message');
    }

    public function dataJabatan()
    {
        $siasnService   = new Siasn();
        $accessTokenSso = $siasnService->getTokenSso();
        $accessTokenWso = $siasnService->getTokenWso();

        $client = new Client();
        $headers = [
            'accept'        => 'application/json',
            'Auth'          => 'bearer ' . $accessTokenSso,
            'Authorization' => 'Bearer ' . $accessTokenWso,
            'Content-Type'  => 'application/json',
            'read_timeout'  => 100000,
        ];

        $loop  = $this->sipgan->table('testingekin')->where('status is NULL', null, true)->get()->getResultArray();
        
        dd($loop);

        foreach ($loop as $row) :
            $nip     = $row['NIP'];
            $request = new Request('GET', 'https://apimws.bkn.go.id:8243/apisiasn/1.0/jabatan/pns/' . $nip, $headers);
            $res     = $client->sendAsync($request)->wait();

            if ($res->getStatusCode() == '200') {
                $res    = json_decode($res->getBody()->getContents(), TRUE);
                $dump   = $res['data'];
                //dd($dump);
                if (!empty($dump)) {
                    if (count($dump) >= 1) {
                        // foreach ($dump as $key) {
                        //     if ($key['tmtJabatan'] != '31-12-2016') {
                        $builder = $this->sipgan->table('testingekin');
                        $builder->set('tanggalSk', $dump[0]['tanggalSk']);
                        $builder->set('tmtJabatan', $dump[0]['tmtJabatan']);
                        $builder->set('tmtPelantikan', $dump[0]['tmtPelantikan']);
                        $builder->set('eselonId', $dump[0]['eselonId']);
                        $builder->set('jabatanFungsionalId', $dump[0]['jabatanFungsionalId']);
                        $builder->set('jabatanFungsionalUmumId', $dump[0]['jabatanFungsionalUmumId']);
                        $builder->set('jenisJabatan', $dump[0]['jenisJabatan']);
                        $builder->set('idRwyt', $dump[0]['id']);
                        $builder->set('nomorSk', $dump[0]['nomorSk']);
                        $builder->set('status', 'ok');
                        $builder->where('NIP', $nip);
                        $builder->update();
                        //     }
                        // }

                        echo "Sukses = " . $nip . "<br>";
                    }
                }
            } else {
                echo "Gagal = " . $nip . "<br>";
            }
        endforeach;

        echo "selesai";
    }

    public function dataUtama()
    {
        $siasnService   = new Siasn();
        $accessTokenSso = $siasnService->getTokenSso();
        $accessTokenWso = $siasnService->getTokenWso();

        $client = new Client();
        $headers = [
            'accept'        => 'application/json',
            'Auth'          => 'bearer ' . $accessTokenSso,
            'Authorization' => 'Bearer ' . $accessTokenWso,
            'Content-Type'  => 'application/json',
            'read_timeout'  => 100000,
        ];
            $nip     = '199507162024211013';
            $request = new Request('GET', 'https://apimws.bkn.go.id:8243/apisiasn/1.0/pns/data-utama/' . $nip, $headers);
            $res     = $client->sendAsync($request)->wait();
            $res    = json_decode($res->getBody()->getContents(), TRUE);
            $dump   = $res['data'];
            dd($dump);
    }

    public function unorJabatan()
    {
        $siasnService   = new Siasn();
        $accessTokenSso = $siasnService->getTokenSso();
        $accessTokenWso = $siasnService->getTokenWso();
        $client = new Client();

        $headers = [
            'Auth'          => 'bearer ' . $accessTokenSso,
            'Authorization' => 'Bearer ' . $accessTokenWso,
            'accept'        => 'application/json',
            'Content-Type'  => 'application/json',
            'read_timeout'  => 100000,
        ];

        $loop = $this->sipgan->table('testingekinerja')->get()->getResultArray();
        //dd($loop);

        foreach ($loop as $row) :
            $eselon = $row["eselonId"] == null ? "null" : '"' . $row["eselonId"] . '"';
            $jft    = $row["jabatanFungsionalId"] == null ? "null" : '"' . $row["jabatanFungsionalId"] . '"';
            $jfu    = $row["jabatanFungsionalUmumId"] == null ? "null" : '"' . $row["jabatanFungsionalUmumId"] . '"';

            $body = '{
                "eselonId":' . $eselon . ',
                "id":"' . $row["idRwyt"] . '",
                "instansiId":"' . $row["instansiId"] . '",
                "satuanKerjaId":"' . $row["satuanKerjaId"] . '",
                "jabatanFungsionalId":' . $jft . ',
                "jabatanFungsionalUmumId":' . $jfu . ',
                "jenisJabatan":"' . $row["jenisJabatan"] . '",
                "subJabatanId":"' . $row["subJabatanId"] . '",
                "tanggalSk":"' . $row["tanggalSk"] . '",
                "tmtJabatan":"' . $row["tmtJabatan"] . '",
                "tmtMutasi":"' . $row["tmtMutasi"] . '",
                "tmtPelantikan":"' . $row["tmtPelantikan"] . '",
                "nomorSk":"' . $row["nomorSk"] . '",
                "jenisMutasiId":"' . $row["jenisMutasiId"] . '",
                "jenisPenugasanId":"' . $row["jenisPenugasanId"] . '",
                "path":[],
                "pnsId":"' . $row["pnsId"] . '",
                "unorId":"' . $row["unorId"] . '",
            }';

            $request = new Request('POST', 'https://apimws.bkn.go.id:8243/apisiasn/1.0/jabatan/unorjabatan/save', $headers, $body);
            $res     = $client->sendAsync($request)->wait();
            $statuscode = $res->getStatusCode();
            $response   = $res->getReasonPhrase();

            //simpan hasil ke database
            $update  = "UPDATE disparitas_unorjabatan SET response='" . $response . "', statuscode='" . $statuscode . "' WHERE nip='" . $row['nip'] . "'";
            $this->sipgan->query($update);
        endforeach;
    }
}