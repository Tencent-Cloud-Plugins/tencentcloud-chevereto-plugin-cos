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

use Qcloud\Cos\Client;
use CHV;
use G;

class Actions
{
    /**
     * @var Client
     */
    private static $cosClient;

    public function hookDispatcher($handler,$hook = 'before')
    {
        $route = $handler::$base_request;
        $requestArr = $handler->request;
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $action = $this->filterPostParam('action');

        $route_menu = $handler::getVar('tabs');
        $route_menu['tencent'] = [
            'label' => '腾讯云设置',
            'id' => 'tencentcloud-setting'
        ];
        $handler::setVar('tabs', $route_menu);

        if ( $requestMethod == 'POST' ) {
            //保存配置
            if ( $route == 'dashboard' && $requestArr[0] == 'settings' && $requestArr[1] == 'cos' && $hook == 'before' ) {
                $this->saveCosConfig();
                return;
            }
            //测试联通
            if ( $route == 'dashboard' && $requestArr[0] == 'settings' && $requestArr[1] == 'test' && $hook == 'before' ) {
                $this->testCos();
                return;
            }

            //上传图片到COS
            if ( $route == 'json' && $action == 'upload' && $hook == 'before') {
                $source = $_REQUEST['type'] == 'file' ? $_FILES['source'] : $_REQUEST['source'];
                $CosName = $filename = $this->getUploadPath() . $source['name'];
                $this->uploadToCos($source['tmp_name'],$CosName,false);
                return;
            }
            //删除COS上的图片
            if ( $route == 'json' && $action == 'delete' && $hook == 'before') {
                $this->deleteFromCos();
                return;
            }
        }

        if ( $requestMethod == 'GET' ) {
            //设置COS的前端页面
            if ( $route == 'dashboard'  && $hook == 'before' ) {
                $this->configHtml($handler);
                return;
            }
            //图片展示前的操作
            if ( $route == 'image' && $hook == 'after' ) {
                $this->beforeImageDisplay($handler);
                return;
            }
        }

    }

    public function filterPostParam($key, $default = '')
    {
        return isset($_POST[$key]) ? G\sanitize_string($_POST[$key], false) : $default;
    }

    private function getCosClient()
    {
        if ( self::$cosClient instanceof Client ) {
            return self::$cosClient;
        }

        $options = Options::getObject();
        self::$cosClient = new Client(
            [
                'region' => $options->getRegion(),
                'schema' => 'https',
                'credentials' => [
                    'secretId' => $options->getSecretId(),
                    'secretKey' => $options->getSecretKey()
                ]
            ]
        );
        return self::$cosClient;
    }

    /**
     * 生成图片的上传路径
     * @return false|string
     */
    private function getUploadPath()
    {
        $uploadPath = '';
        $storageMode = CHV\getSetting('upload_storage_mode');
        switch ($storageMode) {
            case 'direct':
                $uploadPath = CHV_PATH_IMAGES;
                break;
            case 'datefolder':
                $dateFolder = [
                    'date' => G\datetime(),
                    'date_gmt' => G\datetimegmt(),
                ];
                $dateFolder = date('Y/m/d/', strtotime($dateFolder['date']));
                $uploadPath = $dateFolder;
                break;
        }
        return $uploadPath;
    }

    /**
     * 上传到COS
     * @param $fileFullPath
     * @param $CosName string 在Cos里的名称，可能包含相对路径
     * @param bool $stockImg
     */
    private function uploadToCos($fileFullPath,$CosName,$stockImg = false)
    {
        try {
            $options = Options::getObject();
            $handle = file_get_contents($fileFullPath);
            $result = $this->getCosClient()->upload($options->getBucket(), $CosName, $handle);
            //存量的图片才能在这一步删除
            if ($stockImg && !empty($result) && $options->getDelLocalFile() !== $options::SAVE_LOCAL_FILE) {
                @unlink($fileFullPath);
            }
        }catch (\Exception $exception){
            return;
        }
    }


    /**
     * 从COS中删除图片
     */
    private function deleteFromCos()
    {
        try {
            if ($this->filterPostParam('delete') != 'image') {
                return;
            }
            $deleting = $_POST['deleting'];
            if (empty($deleting['id'])) {
                return;
            }

            $id = CHV\decodeID($deleting['id']);
            $image = CHV\Image::getSingle($id, false, false);
            if (empty($image['file_resource']['chain']['image'])){
                return;
            }
            $relativePath = $this->parseImgRelativePath($image['file_resource']['chain']['image']);

            $this->getCosClient()->deleteObject(
                [
                    'Bucket' => Options::getObject()->getBucket(),
                    'Key' => $relativePath,
                ]
            );
        } catch (\Exception $exception) {
            return;
        }

    }

    /**
     * 保存COS配置
     * @throws \Exception
     */
    private function testCos()
    {
        $valid = (new Client(
            [
                'region' => $this->filterPostParam('region'),
                'schema' => 'https',
                'credentials' => [
                    'secretId' => $this->filterPostParam('secretId'),
                    'secretKey' => $this->filterPostParam('secretKey')
                ]
            ]
        ))->doesBucketExist($this->filterPostParam('bucket'));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Content-type: application/json; charset=UTF-8');
        echo json_encode([
            'valid' => $valid,
        ]);
        exit;
    }

    /**
     * 保存COS配置
     * @throws \Exception
     */
    private function saveCosConfig()
    {
        $options = Options::getObject();
        $options->setSecretId($this->filterPostParam('secretId'));
        $options->setSecretKey($this->filterPostParam('secretKey'));
        $options->setRegion($this->filterPostParam('region'));
        $options->setBucket($this->filterPostParam('bucket'));
        $options->setDelLocalFile($this->filterPostParam('delLocalFile'));
        $options->setUploadLocalFile($this->filterPostParam('uploadLocalFile'));
        $options->save();
        (new UsageDataReport($options->toArray()))->report();
    }

    /**
     * 加载配置页面
     * @param $handler
     */
    private function configHtml($handler)
    {
        $options = Options::getObject();
        $html = file_get_contents(TENCENTCLOUD_DIR . 'template' . DIRECTORY_SEPARATOR . 'config_modal.html');
        $search = ['{{secretId}}', '{{secretKey}}', '{{bucket}}', '{{region}}'];
        $replace = [
            $options->getSecretId(),
            $options->getSecretKey(),
            $options->getBucket(),
            $options->getRegion()
        ];
        $html = str_replace($search, $replace, $html);
        $search = ['{{uploadFile}}', '{{dontUpload}}', '{{saveFile}}', '{{delFile}}'];
        $uploadFile = 'selected=""';
        $dontUpload = '';
        if ($options->getUploadLocalFile() === $options::DONT_UPLOAD_LOCAL_FILE) {
            $uploadFile = '';
            $dontUpload = 'selected=""';
        }
        $saveFile = 'selected=""';
        $delFile = '';
        if ($options->getDelLocalFile() === $options::DEL_LOCAL_FILE) {
            $saveFile = '';
            $delFile = 'selected=""';
        }
        $replace = array($uploadFile, $dontUpload, $saveFile, $delFile);
        $html = str_replace($search, $replace, $html);
        $handler->hookTemplate(array('code' => $html, 'where' => 'after'));
    }

    /**
     * 同步存量图片到COS
     * @param $relativePath
     */
    private function uploadStockImg($relativePath)
    {
        //图片保存在磁盘的路径
        $fullPath = CHV_PATH_IMAGES.$relativePath;
        if (is_file($fullPath)) {
            $this->uploadToCos($fullPath,$relativePath,true);
        }
    }

    /**
     * 解析相对路径
     * @param $path
     * @return string
     */
    private function parseImgRelativePath($path)
    {
        $pattern = '/(?<=\/'.CHV_FOLDER_IMAGES.').*?(?=\.(gif|png|jpg|jpeg)$)/';
        $matches = [];
        preg_match_all($pattern, $path, $matches, PREG_SET_ORDER, 0);
        if (empty($matches[0])) {
            return '';
        }
        $matches = $matches[0];
        return $matches[0].'.'.$matches[1];
    }

    private function beforeImageDisplay($handler)
    {
        $image = $handler::getVar('image');
        //删除本地的主文件会导致url为空
        if (empty($image['url'])) {
            $image['url'] = str_replace('.md.','.',$image['display_url']);
        }
        //图片的相对路径
        $relativePath = $this->parseImgRelativePath($image['url']);
        if (empty($relativePath)) {
            return;
        }
        $options = Options::getObject();
        $existCos = $this->getCosClient()->doesObjectExist($options->getBucket(), $relativePath);
        //图片不存在于COS且也不上传到COS直接返回
        if (!$existCos && $options->getUploadLocalFile() !== $options::UPLOAD_LOCAL_FILE) {
            return;
        }
        //上传存量图片到COS
        $this->uploadStockImg($relativePath);
        $fileResource = $image['file_resource']['chain'];
        //新上传图片的本地文件在这一步删除
        if ($options->getDelLocalFile() === $options::DEL_LOCAL_FILE && is_file($fileResource['image'])) {
            @unlink($fileResource['image']);
        }
        $image['url'] = $this->getCosUrl($relativePath);
        $handler::setVar('image', $image);
    }

    /**
     * 获取图片
     * @param $relativePath
     * @return mixed|string
     */
    private function getCosUrl($relativePath)
    {
        $cosPrefix = 'https://{{bucket}}.cos.{{region}}.myqcloud.com/';
        $options = Options::getObject();
        $search = ['{{bucket}}', '{{region}}'];
        $replace = [$options->getBucket(), $options->getRegion()];
        $cosPrefix = str_replace($search, $replace, $cosPrefix);
        return $cosPrefix.$relativePath;
    }

}