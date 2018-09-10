<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/10 9:47
 */

namespace Gkcosapi\Cospackage;

use Gkcosapi\Cospackage\statusException\StatusException;
use Gkcosapi\Cospackage\statusException\CurlCommon;

class Cospackage extends StatusException
{
    /**
     * API 获取上传参数
     * @return \Illuminate\Http\JsonResponse
     * @author lwj <381244953@qq.com>
     * @since huangjinbing <373768442@qq.com>
     * @param1 _application string
     * @param2 _type string
     */
    public function getUploadParam($key)
    {
        // 检查必填参数
        $pathname = time() . rand(10000, 99999);

        // 获取上传文件夹
        $data['path'] = $key . date('/Y/m/d') . '/' . $pathname;

        // 返回存储桶，地区
        $data['bucket'] = env('COS_BUCKET') . '-' . env('COS_APP_ID');

        // 存储地区
        $regionmap = [
            'cn-east' => 'ap-shanghai',
            'cn-sorth' => 'ap-guangzhou',
            'cn-north' => 'ap-beijing-1',
            'cn-south-2' => 'ap-guangzhou-2',
            'cn-southwest' => 'ap-chengdu',
            'sg' => 'ap-singapore'
        ];
        $region = env('COS_REGION', 'cn-sorth');
        $data['region'] = $regionmap[$region];

        return $data;
    }

    /**
     * API 获取上传签名
     * @return \Illuminate\Http\JsonResponse
     * @author lwj <381244953@qq.com>
     * @since huangjinbing <373768442@qq.com>
     * @param1 _fileType string
     * @param2 _method string
     */
    public function getUploadSign($fileType = "image", $method = "post")
    {
        $cosService = new CosService();

        // 生成API签名
        if ($fileType == 'image') {
            $data['sign'] = $cosService->createImageSign($method);
        } else {
            $data['sign'] = $cosService->createVideoSign();
        }

        return $data;
    }

    /**
     * API 获取资源路径
     * @return array
     * @author lwj <381244953@qq.com>
     * @since huangjinbing <373768442@qq.com>
     * @param1 _url string
     */
    public function getResource($url)
    {
        $fileUrl= $url;
        // 获取文件的详细信息
        $imageInfo = CurlCommon::requestWithHeader($fileUrl . '?imageInfo', 'GET');
        dd($imageInfo);
        if (!isset($imageInfo['size'])) return false;

        // 整合入库数据
        $pathInfo = pathinfo($fileUrl);
        $data = [
            'name' => $pathInfo['basename'] ?? '',
            'url' => $url,
            'file_type' => $imageInfo['format'] ?? '',
            'file_size' => $imageInfo['size'] ?? 0,
            'width' => $imageInfo['width'] ?? 0,
            'height' => $imageInfo['height'] ?? 0
        ];
        return $data;
    }

}
