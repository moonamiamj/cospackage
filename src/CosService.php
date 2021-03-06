<?php
/**
 * Created by PhpStorm.
 * User: lwj <381244953@qq.com>
 * Date: 2018/9/10 9:58
 */

namespace Gkcosapi\Cospackage;

use Carbon\Carbon;
use Gkcosapi\Cospackage\statusException\CurlCommon;
use Qcloud\Cos\Client;
use Vod\VodApi;

class CosService
{
    /**
     * @var SessionManager
     */
    protected $session;

    /**
     * @var 配置项
     */
    protected $options;

    /**
     * Packagetest constructor.
     * @param SessionManager $session
     * @param Repository $config
     */
    public function __construct()
    {
        $this->options= config('cosapi.options');
    }

    /**
     * 获取API签名
     * @param $method
     * @param $pathname
     * @return string
     * @author lwj <381244953@qq.com>
     * @since huangjinbing <373768442@qq.com>
     */

    public function createImageSign($method, $pathname = '')
    {
        // 获取个人 API 密钥 https://console.qcloud.com/capi
        $secretKey = $this->options['secret_key'];
        $secretId = $this->options['secret_id'];

        // 整理参数
        $queryParams = array();
        $headers = array();
        $method = strtolower($method ? $method : 'get');
        $pathname = $pathname ? $pathname : '/';
        substr($pathname, 0, 1) != '/' && ($pathname = '/' . $pathname);

        // 签名有效起止时间
        $now = time() - 1;
        $expired = $now + 600; // 签名过期时刻，600 秒后

        // 要用到的 Authorization 参数列表
        $qSignAlgorithm = 'sha1';
        $qAk = $secretId;
        $qSignTime = $now . ';' . $expired;
        $qKeyTime = $now . ';' . $expired;

        if (!function_exists('getObjectKeys')) {
            // 工具方法
            function getObjectKeys($obj)
            {
                $list = array_keys($obj);
                sort($list);
                return $list;
            }
        }
        if (!function_exists('obj2str')) {
            function obj2str($obj)
            {
                $list = array();
                $keyList = getObjectKeys($obj);
                $len = count($keyList);
                for ($i = 0; $i < $len; $i++) {
                    $key = $keyList[$i];
                    $val = isset($obj[$key]) ? $obj[$key] : '';
                    $key = strtolower($key);
                    $list[] = rawurlencode($key) . '=' . rawurlencode($val);
                }
                return implode('&', $list);
            }
        }


        $qHeaderList = strtolower(implode(';', getObjectKeys($headers)));
        $qUrlParamList = strtolower(implode(';', getObjectKeys($queryParams)));

        // 签名算法说明文档：https://www.qcloud.com/document/product/436/7778
        // 步骤一：计算 SignKey
        $signKey = hash_hmac("sha1", $qKeyTime, $secretKey);

        // 步骤二：构成 FormatString
        $formatString = implode("\n", array(strtolower($method), $pathname, obj2str($queryParams), obj2str($headers), ''));

        // 步骤三：计算 StringToSign
        $stringToSign = implode("\n", array('sha1', $qSignTime, sha1($formatString), ''));

        // 步骤四：计算 Signature
        $qSignature = hash_hmac('sha1', $stringToSign, $signKey);

        // 步骤五：构造 Authorization
        $authorization = implode('&', array(
            'q-sign-algorithm=' . $qSignAlgorithm,
            'q-ak=' . $qAk,
            'q-sign-time=' . $qSignTime,
            'q-key-time=' . $qKeyTime,
            'q-header-list=' . $qHeaderList,
            'q-url-param-list=' . $qUrlParamList,
            'q-signature=' . $qSignature
        ));

        return $authorization;
    }

    /**
     * @return string
     * @author lwj <381244953@qq.com>
     * @since huangjinbing <373768442@qq.com>
     */
    public function createVideoSign()
    {
        $secretKey = $this->options['secret_key'];
        $secretId = $this->options['secret_id'];

        $current = time();
        $expired = $current + 600;

        // 向参数列表填入参数
        $arg_list = [
            'secretId' => $secretId,
            'currentTimeStamp' => $current,
            'expireTime' => $expired,
            'random' => rand()
        ];

        // 计算签名
        $orignal = http_build_query($arg_list);
        $signature = base64_encode(hash_hmac('SHA1', $orignal, $secretKey, true) . $orignal);

        return $signature;
    }

    /**
     * 获取上传文件夹
     * @param $type
     * @return string
     * @author lwj <381244953@qq.com>
     * @since huangjinbing <373768442@qq.com>
     */
    public function getUploadFolder($application, $type)
    {
        $type -= 1;
        $data = [
            'jike-backend' => [
                '/merchant/shop/',
                '/merchant/logo/',
                '/merchant/certificate/',
                '/merchant/goods/',
                '/merchant/goods_detail/',
                '/merchant/giftbag/',
                '/merchant/content_cover/',
                '/merchant/content/',
                '/merchant/content_video/',
                '/merchant/prize/',
                '/merchant/qrcord/',
            ],
            'jike-wap' => [
                '/customer/portrait/',
                '/customer/qrcord/',
                '/customer/coupon/',
            ],
            'share-wap' => [
                '/customer/portrait/',
                '/customer/log/',
                '/customer/log_video/',
            ],
        ];

        // 不存在的type,存放再时间文件夹下
        $dir = $data[$application][$type] ?? '/';
        $dir = '/' . $application . $dir;
        $folder = $dir . Carbon::now()->format('Y/m/d/');

        return $folder;
    }

    /**
     * 内部上传文件
     * @param string $merchar_id 类型，商家ID
     * @param string $filePath 文件路径
     * @param string $postfix 文件后缀
     * @return bool
     * @author lwj <381244953@qq.com>
     * @since huangjinbing <373768442@qq.com>
     */
    public function uploadImage($merchar_id,$filePath, $postfix = '.png')
    {
        // 配置
        $configs = [
            'region' => $this->options['region'],
            'credentials' => [
                'appId' => $this->options['app_id'],
                'secretId' => $this->options['secret_id'],
                'secretKey' => $this->options['secret_key']
            ]
        ];

        // 实例化
        $cosClient = new Client($configs);

        // 打开图片资源
        $file = fopen($filePath, 'r');

        $folder = '/'.$merchar_id.'/'.date('Y/m/d');
        $fileName = time() . rand(10000, 99999) .'.' .$postfix;

        $fileData=[
            'Bucket' => $this->options['bucket'],
            'Key' => $folder . $fileName,
            'Body' => fopen($file, 'rb')
        ];
        // 上传
        try {
            $result = $cosClient->putObject($fileData);
            // \Log::info(print_r($result,true));
            if (isset($result['ObjectURL'])) {
                $parseUrl = parse_url(urldecode($result['ObjectURL']));
                return $parseUrl['path'];
            }

        } catch (\Exception $e) {
            //   CurlCommon::logUnusualError($e);
        }

        return false;
    }

    public function uploadVideo($application, $type)
    {
        // 初始化
        VodApi::initConf($this->options['secret_id'], $this->options['secret_key']);

        // 获取文件夹与文件名
        $folder = $this->getUploadFolder($application, $type);

        // 内部使用删除前面斜杆
        $folder = str_replace_once('/', '', $folder);

        // 文件路径
        $upload = [
            'videoPath' => public_path() . '/20180425.mp4',
            'coverPath' => public_path() . '/20180425.jpg',
        ];
        // 上传参数（不必填）
        $param = [
            'videoName' => $folder . time() . rand(10000, 99999) . '.mp4'
        ];

        $result = VodApi::upload($upload, $param);
        echo "upload to vod result: " . json_encode($result) . "\n";
    }
}
