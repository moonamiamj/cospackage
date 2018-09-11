#cos扩张包使用说明0.0
##项目根目录配置
    "require": {
        "gkcosapi/cospackage": "~1.0",
        }
        
##通过COMPOSER获取项目
        composer update
        
##配置项目bootstrap下的app.php
    $app->register(Gkcosapi\Cospackage\CospackageServiceProvider::class);

##初始化扩展包
        composer dump-autoload
