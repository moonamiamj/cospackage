#cos扩张包使用说明0.0

##项目根目录配置
    "require": {
        "gkcosapi/cospackage": "~3.0",
        }


##通过COMPOSER获取项目
        composer update


##配置项目bootstrap下的app.php

$app->register(Gkcosapi\Cospackage\CospackageServiceProvider::class);


##初始化扩展包
        composer dump-autoload


#使用说明
##1、在业务层引用扩展包 use Gkcosapi\Cospackage\Facades\Cospackage;
##2、Cospackage::uploadFile($file->getPathname(), 'jpg');//调取扩展函数 进行cos文件上传工作
##3、函数介绍：
###Cospackage::uploadFile($file->getPathname(), 'jpg');//cos文件上传工作

###Cospackage::getResource($url)//获取文件资源

###Cospackage::getUploadSign($fileType = "image", $method = "post")//获取cos签名

###Cospackage::getUploadParam($key)//获取文件上传参数 key:定义的文件目录名称或mercharID(生成目录)
