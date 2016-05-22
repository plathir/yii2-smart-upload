<?php

namespace plathir\upload;

use yii\web\AssetBundle;

/**
 * Widget asset bundle
 */
class Asset extends AssetBundle {

    /**
     * @inheritdoc
     */
    public $sourcePath = '@plathir/upload/assets';

    /**
     * @inheritdoc
     */
    public $css = [
        'css/uploader.css'
    ];

    /**
     * @inheritdoc
     */
    public $js = [
        'js/SimpleAjaxUploader.js',
        'js/uploader.js',
        'js/listfiles.js',
    ];

    /**
     * @inheritdoc
     */
    public $depends = [
        'yii\web\JqueryAsset'
    ];

}
