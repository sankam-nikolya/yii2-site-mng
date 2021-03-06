<?php
$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'),
    require(__DIR__ . '/../../common/config/params-local.php'),
    require(__DIR__ . '/params.php'),
    require(__DIR__ . '/params-local.php')
);

return [
    'id' => 'app-backend',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'backend\controllers',
    'bootstrap' => ['log'],
    'modules' => [
        'map' => [
            'class' => 'app\modules\map\Module',
        ],
        'news' => [
            'class' => 'app\modules\news\Module',
        ],
        'gallery' => [
            'class' => 'app\modules\gallery\Module',
        ],
        'items' => [
            'class' => 'app\modules\items\Module',
        ],
        'forms' => [
            'class' => 'app\modules\forms\Module',
        ],
        'shop' => [
            'class' => 'app\modules\shop\Module',
        ],
        'realty' => [
            'class' => 'app\modules\realty\Module',
        ],
        'broadcast' => [
            'class' => 'app\modules\broadcast\Module',
        ],
        'sms' => [
            'class' => 'app\modules\sms\Module',
        ],
        'certificates' => [
            'class' => 'app\modules\certificates\Module',
        ],
        'auctionmb' => [
            'class' => 'app\modules\auctionmb\Module',
        ],
        'user' => [
            'class' => 'app\modules\user\Module',
        ],
        'settings' => [
            'class' => 'app\modules\settings\Module',
        ],
    ],
    'components' => [
        'request' => [
            'baseUrl' => '/mng',
        ],
        'assetManager' => [
            'class' => 'yii\web\AssetManager',
            'forceCopy' => true,
            'bundles' => [
                'dmstr\web\AdminLteAsset' => [
                    'skin' => 'skin-blue',
                ],
            ],
        ],
        'urlManager' => [
            'class' => 'yii\web\UrlManager',
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                '/' => '/site/index',
                '/<action>' => '/site/<action>',
                '/<module>/<action>' => '/<module>/default/<action>',
                '/<controller>/<action>' => '/<controller>/<action>',
            ],
        ],
        'user' => [
            'identityClass' => 'common\models\User',
            'enableAutoLogin' => true,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
    ],
    'params' => $params,
];
