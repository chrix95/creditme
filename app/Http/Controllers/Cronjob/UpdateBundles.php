<?php

namespace App\Http\Controllers\Cronjob;

use App\TVBundle;
use App\DataBundle;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\UtilityController;

class UpdateBundles extends Controller
{
    public function __construct(UtilityController $utility) {
        $this->utility = $utility;
    }

    public function loadBundle () {
        $disco = array(
            'Etisalat',
            'MTN',
            'Glo',
            'Airtel',
            'Smile',
            'Spectranet'
        );
        $count = 0;
        $service_id = '';
        $network = '';
        foreach ($disco as $key => $value) {
            $network = $value;
            if ($value == 'MTN') {
                $service_id = 5;
            } elseif ($value == 'Etisalat') {
                $service_id = 6;
            } else if ($value == 'Glo') {
                $service_id = 28;
            } elseif ($value == 'Airtel') {
                $service_id = 7;
            } elseif ($value == 'Smile') {
                $service_id = 8;
            } elseif ($value == 'Spectranet') {
                $service_id = 9;
            }
            $data = array(
                'network'  => $network
            );
            // $data['passcode'] = $this->utility->accessHash($data['network']);
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://irecharge.com.ng/pwr_api_sandbox/v2/get_data_bundles.php?response_format=json&data_network=". $data['network'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => array(
                "cache-control: no-cache",
                "content-type: application/json",
                "postman-token: a588010c-6d02-d4e1-8ea4-5b207c16f201"
                ),
            ));
            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);

            if ($err) {
                echo "cURL Error #:" . $err;
            } else {
                $response = json_decode($this->utility->remove_utf8_bom($response), true);
                if ($count == 0) {
                    \DB::table('data_bundles')->truncate();
                    $count++;
                }
                foreach ($response['bundles'] as $key => $value) {
                    $data = array(
                        'name'  => $value['title'],
                        'amount'  => $value['price'],
                        'code'  => $value['code'],
                        'service_id'  => $service_id,
                        'date_created'  => now(),
                        'date_modified'  => now(),
                    );
                    \App\DataBundle::create($data);
                }
            }
        }
        echo "Wow! Bundles updated successfully.";
    }

    public function loadTVBundle () {
        $disco = array(
            'DSTV',
            'GOTV'
        );
        $count = 0;
        foreach ($disco as $key => $value) {
            $network = $value;
            $service_id = '';
            if ($value == 'DSTV') {
                $service_id = 21;
            } elseif ($value == 'GOTV') {
                $service_id = 22;
            } else if ($value == 'StarTimes') {
                $service_id = 23;
            }
            $data = array(
                'network'  => $network
            );
            $url = env('VENDOR_TEST_URL');
            if (env('MODE') == 2) {
                $url = env('VENDOR_LIVE_URL');
            }
            $responseFormat = "json";
            $url .= "/get_tv_bouquet.php?tv_network=" . urlencode($network) . "&response_format=" . urlencode($responseFormat);
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => array(
                    "cache-control: no-cache",
                    "content-type: application/json",
                    "postman-token: a588010c-6d02-d4e1-8ea4-5b207c16f201"
                ),
            ));
            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);

            if ($err) {
                echo "cURL Error #:" . $err;
            } else {
                $response = json_decode($this->utility->remove_utf8_bom($response), true);
                if ($response['status'] == "00") {
                    if ($count == 0) {
                        \DB::table('tv_bundles')->truncate();
                        $count++;
                    }
                    foreach ($response['bundles'] as $key => $value) {
                        $data = array(
                            'name'  => $value['title'],
                            'amount'  => $value['price'],
                            'code'  => $value['code'],
                            'allowance'  => $value['allowance'],
                            'available'  => "YES",
                            'service_id'  => $service_id,
                            'date_created'  => Now(),
                            'date_modified'  => Now()
                        );
                        \App\TVBundle::create($data);
                    }
                } else {
                    \Log::info("Could not update the latest". $network ." bundles. " . now());
                }
            }
        }
        $data = array(
            'name'  => "StarTimes",
            'amount'  => 0.00,
            'code'  => "StarTimes",
            'allowance'  => "Yes",
            'available'  => "YES",
            'service_id'  => 23,
            'date_created'  => now(),
            'date_modified'  => now()
        );
        \App\TVBundle::create($data);
        echo "Wow! TV Bundles updated successfully.";
    }

}