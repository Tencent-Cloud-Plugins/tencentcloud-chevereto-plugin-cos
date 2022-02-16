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
use CHV;

class Options
{
    //上传本地存量文件
    const UPLOAD_LOCAL_FILE = 1;
    //不上传
    const DONT_UPLOAD_LOCAL_FILE = 0;
    //上传COS后删除本地文件
    const DEL_LOCAL_FILE = 0;
    //保留本地文件
    const SAVE_LOCAL_FILE = 1;

    private $secretId;
    private $secretKey;
    private $region;
    private $bucket;
    private $uploadLocalFile;
    private $delLocalFile;
    private $hasBeenSaved = false;
    private static $Options;

    public function __construct()
    {
        $options = json_decode(CHV\getSetting('tencentcloud_cos'));
        if (!empty($options)) {
            $this->hasBeenSaved = true;
            $this->setSecretId($options->secretId);
            $this->setSecretKey($options->secretKey);
            $this->setBucket($options->bucket);
            $this->setRegion($options->region);
            $this->setUploadLocalFile($options->uploadLocalFile);
            $this->setDelLocalFile($options->delLocalFile);
        }
    }

    public static function getObject()
    {
        if (!empty(self::$Options)) {
            return self::$Options;
        }
        self::$Options = new static();
        return self::$Options;
    }

    public function setSecretId($secretId)
    {
        if (empty($secretId)) {
            throw new \Exception('secretId不能为空');
        }
        $this->secretId = $secretId;
    }

    public function setSecretKey($secretKey)
    {
        if (empty($secretKey)) {
            throw new \Exception('secretKey不能为空');
        }
        $this->secretKey = $secretKey;
    }

    public function setRegion($region)
    {
        if (empty($region)) {
            throw new \Exception('所属区域不能为空');
        }
        $this->region = $region;
    }

    public function setBucket($bucket)
    {
        if (empty($bucket)) {
            throw new \Exception('存储桶名称不能为空');
        }
        $this->bucket = $bucket;
    }

    public function setUploadLocalFile($uploadLocalFile)
    {
        $this->uploadLocalFile = intval($uploadLocalFile);
    }

    public function setDelLocalFile($delLocalFile)
    {
        $this->delLocalFile = intval($delLocalFile);
    }


    /**
     * @return mixed
     */
    public function getBucket()
    {
        return $this->bucket;
    }

    /**
     * @return mixed
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * @return mixed
     */
    public function getSecretId()
    {
        return $this->secretId;
    }

    /**
     * @return mixed
     */
    public function getUploadLocalFile()
    {
        return $this->uploadLocalFile;
    }
    /**
     * @return mixed
     */
    public function getDelLocalFile()
    {
        return $this->delLocalFile;
    }

    /**
     * @return mixed
     */
    public function getSecretKey()
    {
        return $this->secretKey;
    }

    public function save()
    {
        if ($this->hasBeenSaved) {
            CHV\Settings::update(['tencentcloud_cos'=>json_encode($this->toArray())]);
        } else {
            CHV\DB::insert('settings', ['name'=>'tencentcloud_cos','value'=>json_encode($this->toArray()),'default'=>'[]','typeset'=>'string']);
        }
    }

    public function toArray()
    {
        return [
            'secretId' =>$this->secretId,
            'secretKey' =>$this->secretKey,
            'region' =>$this->region,
            'bucket' =>$this->bucket,
            'uploadLocalFile' =>$this->uploadLocalFile,
            'delLocalFile' =>$this->delLocalFile,
        ];
    }

}
