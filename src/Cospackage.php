<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/10 9:47
 */

namespace Gkcosapi\Cospackage;

//use App\Helpers\Tools;
//use App\Models\File;
//use App\Models\Image;
//use App\Services\CloudObjectStorageService;


class Cospackage
{
    /**
     * 外部获取上传参数
     * @return \Illuminate\Http\JsonResponse
     * @author huangjinbing <373768442@qq.com>
     */
    public function getUploadParam()
    {
        return '123';
        // 检查必填参数

        $type = $data['type'];
        $pathname =  $data['pathname'];
        $application =  $data['application'];

        $cosService = new CosService();

        // 获取上传文件夹
        $data['path'] = $cosService->getUploadFolder($application, $type) . $pathname;

        // 返回存储桶，地区
        $data['bucket'] = env('COS_BUCKET', 'share-static') . '-' . env('COS_APP_ID', '1255605079');

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

        return $this->response(Tools::setData($data));
    }

    /**
     * 外部获取上传签名
     * @return \Illuminate\Http\JsonResponse
     * @author huangjinbing <373768442@qq.com>
     */
    public function getUploadSign()
    {
        $fileType = request('file_type', 'image');
        $method = request('method', 'post');

        $cosService = new CloudObjectStorageService();

        // 生成API签名
        if ($fileType == 'image') {
            $data['sign'] = $cosService->createImageSign($method);
        } else {
            $data['sign'] = $cosService->createVideoSign();
        }

        return $this->response(Tools::setData($data));
    }

    /**
     * 回写数据
     * @return \Illuminate\Http\JsonResponse
     * @author huangjinbing <373768442@qq.com>
     */
    public function postSaveFile()
    {
        // 检查必填参数
        Tools::checkRequest(['path']);

        $type = request('file_type', 'image');
        $path = request('path');

        // 判断上传文件的类型
        if ($type == 'image') {
            $result = Image::insertData($path, APPLICATION);
        } else {
            $result = File::insertData($path, APPLICATION);
        }
        dd($result);
        if ($result) {
            return $this->response(Tools::setData($result));
        }
        return $this->response(Tools::error());
    }
}