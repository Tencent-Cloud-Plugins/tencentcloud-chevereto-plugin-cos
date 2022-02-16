# 腾讯云对象存储

## 1.介绍

> 将Chevereto用户上传图片存储到的腾讯云对象存储（COS）中。

| 标题       | 名称                                                                                                |
| -------- | ------------------------------------------------------------------------------------------------- |
| 中文名称     | 腾讯云对象存储（COS）插件                                                                                  |
| 英文名称     | tencentcloud-chevereto-cos                                                                             |
| 最新版本     | 1.0.1 (2022.02.15)                                                                                |
| 适用平台     | [Chevereto](https://chevereto.com/)                                                               |
| 适用产品     | [腾讯云对象存储（COS）](https://cloud.tencent.com/product/cos)                                             |
| 主创团队     | 腾讯云中小企业产品中心（SMB Product Center of Tencent Cloud）                                                  |

## 2.功能特性

- 将Chevereto用户上传图片存储到的腾讯云对象存储（COS）中

## 3.安装指引

### 3.1.部署方式

### 3.1.部署方式一：通过GitHub部署安装

> 1. git clone [git@github.com:Tencent-Cloud-Plugins/tencentcloud-chevereto-plugin-cos.git](https://github.com/Tencent-Cloud-Plugins/tencentcloud-chevereto-cos.git)
> 2. 选择和自己站点对应的版本代码，复制tencentcloud-chevereto-plugin-cos目录中对应版本的chevereto-hook.php文件和tencentcloud文件夹到Chevereto安装目录/app文件夹里面

## 4.使用指引

### 4.1.界面功能介绍

![](./images/cos1.png)

> 进入Cheveteto 仪表盘, 对腾讯云COS进行配置。配置介绍请参考下方的[名词解释](#_4-2-名词解释)

### 4.2.名词解释

- **SecretId**：在腾讯云云平台API密钥上申请的标识身份的 SecretId。详情参考[腾讯云文档](https://cloud.tencent.com/document/product)
- **SecretKey**：在腾讯云云平台API密钥上申请的标识身份的SecretId对应的SecretKey。详情参考[腾讯云文档](https://cloud.tencent.com/document/product)
- **存储桶名称**：COS服务中存储桶的名称。详情参考[腾讯云文档](https://cloud.tencent.com/document/product/436/41153)
- **所属区域**：存储桶基本信息中的所属地域（以ap-开头）。详情参考[腾讯云文档](https://cloud.tencent.com/document/product/436/6224)


## 5.插件自定义Chevereto-Free版本适配
> 腾讯云Chevereto插件代码库中只发布了支持1.2.3版本和1.6.2版本的插件代码，但是Chevereto的官方版本更新比较频繁，
> 插件持续更新的成本比较大，所以下面简单介绍下通过简单修改代码也能支持其他Chevereto版本。

- **Step1**：获取任意一个 Chevereto 版本的 cos 插件代码。
- **Step2**：拷贝chevereto-hook.php文件和tencentcloud文件夹到Chevereto安装目录/app文件夹中。
- **Step3**：参考/app/web.php文件中150行左右的 $hook_before 匿名函数，
将函数内容覆盖 chevereto-hook.php 文件中 $hook_before 匿名函数，但是要保留函数中cos处理相关代码，如下：
```php
 try {
         (new Actions())->hookDispatcher($handler,'before');
     }catch (\Exception $exception){
         header('Cache-Control: no-cache, must-revalidate');
         header('Pragma: no-cache');
         header('Content-type: application/json; charset=UTF-8');
         echo json_encode([
             'code'=>500,
             'msg'=>$exception->getMessage(),
             'data'=>[],
         ]);
         exit;
     }
```
- **Step4**：参考/app/web.php文件中470行左右的 $hook_after 匿名函数，
将函数内容覆盖 chevereto-hook.php 文件中 $hook_after 匿名函数，但是要保留函数中cos处理相关代码，如下：
```php
 try {
         (new Actions())->hookDispatcher($handler,'after');
     }catch (\Exception $exception) {
         header('Cache-Control: no-cache, must-revalidate');
         header('Pragma: no-cache');
         header('Content-type: application/json; charset=UTF-8');
         echo json_encode([
             'code' => 500,
             'msg' => $exception->getMessage(),
             'data' => [],
         ]);
         exit;
     }
```

## 6.FAQ

> 1. Q: 支持上传的头像图片吗？
>    
>    A：目前仅支持用户上传的图片。不支持设置的头像和设置的背景图片
>    
> 2. Q: 为什么COS上的图片没有加上水印？
>       
>    A：目前COS上仅保留原始图片。不包含水印图片和.md,.th等Chevereto生成的缩略图


## 7.版本迭代记录

### 2022.2.15 tencentcloud-chevereto-plugin-cos v1.0.1
- 新增插件支持Chevereto-free1.6.2版本。

### 2020.9.18 tencentcloud-chevereto-plugin-cos v1.0.0
- 将Chevereto用户上传图片存储到的腾讯云对象存储（COS）中
---

本项目由腾讯云中小企业产品中心建设和维护，了解与该插件使用相关的更多信息，请访问[春雨文档中心](https://openapp.qq.com/docs/Chevereto/cos.html) 

请通过[咨询建议](https://da.do/y0rp) 向我们提交宝贵意见。