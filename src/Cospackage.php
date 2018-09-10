<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/10 9:47
 */

namespace Gkcosapi\Cospackage;

use App\Helpers\Tools;
use App\Models\File;
use Gkcosapi\Cospackage\statusException\statusException;
//use App\Models\Image;
//use App\Services\CloudObjectStorageService;


class Cospackage extends statusException
{
    /**
     * API 获取上传参数
     * @return \Illuminate\Http\JsonResponse
     * @author lwj <381244953@qq.com>
     * @since huangjinbing <373768442@qq.com>
     * @param1 _application string
     * @param2 _type string
     */
    public function getUploadParam($application='jike-wap',$type=1)
    {
        // 检查必填参数
        $pathname=time() . rand(10000, 99999);
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
     * API 获取上传签名
     * @return \Illuminate\Http\JsonResponse
     * @author lwj <381244953@qq.com>
     * @since huangjinbing <373768442@qq.com>
     * @param1 _fileType string
     * @param2 _method string
     */
    public function getUploadSign($fileType="image",$method="post")
    {
        $cosService = new CosService();

        // 生成API签名
        if ($fileType == 'image') {
            $data['sign'] = $cosService->createImageSign($method);
        } else {
            $data['sign'] = $cosService->createVideoSign();
        }

        return $this->response(Tools::setData($data));
    }

    /**
     * API 回写数据
     * @return \Illuminate\Http\JsonResponse
     * @author lwj <381244953@qq.com>
     * @since huangjinbing <373768442@qq.com>
     * @param1 _Type string
     * @param2 _path string
     */
    public function postSaveFile($path,$type="image")
    {
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
