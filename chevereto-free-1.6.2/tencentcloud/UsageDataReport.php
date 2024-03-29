<?php
/**
 * Copyright (C) 2020 Tencent Cloud.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace TencentCloudCos;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use G;
use CHV;

class UsageDataReport
{
    const REPORT_URL = 'https://openapp.qq.com/api/public/index.php/upload';
    const SITE_APP = 'Chevereto';
    const PREFIX = 'chevereto_';

    private $secretKey;
    private $secretId;
    private $bucket;
    private $region;

    public function __construct(array $arguments)
    {
        $this->secretId = $arguments['secretId'];
        $this->secretKey = $arguments['secretKey'];
        $this->bucket = $arguments['bucket'];
        $this->region = $arguments['region'];
    }

    public function report()
    {
        try {
            $data = [
                'action' => 'save_config',
                'plugin_type' => 'cos',
                'data' => [
                    'site_id' => $this->getSiteID(),
                    'site_url' => $this->getSiteUrl(),
                    'site_app' => self::SITE_APP,
                    'uin' => $this->getUserUin(),
                    'cust_sec_on' => 1,
                    'others' => \GuzzleHttp\json_encode([
                        'cos_bucket' => $this->bucket,
                        'cos_region' => $this->region,
                    ])
                ]
            ];
            (new Client())->post(self::REPORT_URL, [
                RequestOptions::JSON => $data
            ]);
        } catch (\Exception $e) {
            return;
        }
    }

    private function getSiteID()
    {
        return self::PREFIX . substr(md5(CHV\Settings::get('crypt_salt')),8,16);
    }

    private function getSiteUrl()
    {
        return G\get_root_url();
    }

    /**
     * get user Uin by secretId and secretKey
     * @return string
     */
    private function getUserUin()
    {
        try {
            $options = [
                'headers' => $this->getSignatureHeaders(),
                'body' => '{}'
            ];
            $response = (new Client(['base_uri' => 'https://ms.tencentcloudapi.com']))
                ->post('/', $options)
                ->getBody()
                ->getContents();
            $response = \GuzzleHttp\json_decode($response);
            return $response->Response->UserUin;
        } catch (\Exception $e) {
            return '';
        }
    }

    private function getSignatureHeaders()
    {
        $headers = array();
        $service = 'ms';
        $timestamp = time();
        $algo = 'TC3-HMAC-SHA256';
        $headers['Host'] = 'ms.tencentcloudapi.com';
        $headers['X-TC-Action'] = 'DescribeUserBaseInfoInstance';
        $headers['X-TC-RequestClient'] = 'SDK_PHP_3.0.187';
        $headers['X-TC-Timestamp'] = $timestamp;
        $headers['X-TC-Version'] = '2018-04-08';
        $headers['Content-Type'] = 'application/json';

        $canonicalHeaders = 'content-type:' . $headers['Content-Type'] . "\n" .
            'host:' . $headers['Host'] . "\n";
        $canonicalRequest = "POST\n/\n\n" .
            $canonicalHeaders . "\n" .
            "content-type;host\n" .
            hash('SHA256', '{}');
        $date = gmdate('Y-m-d', $timestamp);
        $credentialScope = $date . '/' . $service . '/tc3_request';
        $str2sign = $algo . "\n" .
            $headers['X-TC-Timestamp'] . "\n" .
            $credentialScope . "\n" .
            hash('SHA256', $canonicalRequest);

        $dateKey = hash_hmac('SHA256', $date, 'TC3' . $this->secretKey, true);
        $serviceKey = hash_hmac('SHA256', $service, $dateKey, true);
        $reqKey = hash_hmac('SHA256', 'tc3_request', $serviceKey, true);
        $signature = hash_hmac('SHA256', $str2sign, $reqKey);

        $headers['Authorization'] = $algo . ' Credential=' . $this->secretId . '/' . $credentialScope .
            ', SignedHeaders=content-type;host, Signature=' . $signature;
        return $headers;
    }
}
