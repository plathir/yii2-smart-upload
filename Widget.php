<?php

namespace plathir\upload;

use yii\base\InvalidConfigException;
use yii\helpers\Json;
use yii\widgets\InputWidget;
use Yii;

class Widget extends InputWidget {

    public $uploadParameter = 'uploadfile';
    public $label = '';
    public $uploadUrl;
    public $previewUrl;
    public $KeyFolder;
    public $tempPreviewUrl;
    public $noPhotoImage;
    public $extensions = 'jpeg, jpg, png, gif, pdf, mov, txt, pdf';
    public $maxUploads = 10;
    public $autoSubmit = false;
    public $maxSize = 3145728; // 3MB
    public $galleryType = false;

    /**
     * @inheritdoc
     */
    public function init() {
        parent::init();
        self::registerTranslations();

        if ($this->uploadUrl === null) {
            throw new InvalidConfigException(Yii::t('upload', 'MISSING_ATTRIBUTE', ['attribute' => 'uploadUrl']));
        } else {
            $this->uploadUrl = rtrim(Yii::getAlias($this->uploadUrl), '/') . '/';
        }

        if ($this->KeyFolder) {
            $this->previewUrl = $this->previewUrl . '/' . $this->KeyFolder;
        }

        if ($this->label == '') {
            $this->label = Yii::t('upload', 'DEFAULT_LABEL');
        }
        $this->noPhotoImage = '';
        
        if ($this->galleryType == true ) {
            $this->extensions = 'jpeg, jpg, png, gif';
        }
    }

    /**
     * @inheritdoc
     */
    public function run() {
        $this->registerClientAssets();

        return $this->render('widget', [
                    'model' => $this->model,
                    'widget' => $this
        ]);
    }

    /**
     * Register widget asset.
     */
    public function registerClientAssets() {
        $view = $this->getView();
        $assets = Asset::register($view);

        if ($this->noPhotoImage == '') {
            $this->noPhotoImage = $assets->baseUrl . '/img/file.png';
        }

        $settings = [
            'url' => $this->uploadUrl,
            'name' => $this->uploadParameter,
            'previewUrl' => $this->previewUrl,
            'tempPreviewUrl' => $this->tempPreviewUrl,
            'allowedExtensions' => explode(', ', $this->extensions),
            'autoSubmit' => $this->autoSubmit,
            'maxUploads' => $this->maxUploads,
            'noPhotoImage' => $this->noPhotoImage,
            'maxSize' => $this->maxSize / 1024,
            'allowedExtensions' => explode(', ', $this->extensions),
            'size_error_text' => Yii::t('upload', 'TOO_BIG_ERROR', ['size' => $this->maxSize / (1024 * 1024)]),
            'ext_error_text' => Yii::t('upload', 'EXTENSION_ERROR', ['formats' => $this->extensions]),
            'galleryType' => $this->galleryType,
            
        ];
 
        $view->registerJs(
                'jQuery("#' . $this->options['id'] . '").siblings(".upload_box").uploader(' . Json::encode($settings) . ');', $view::POS_READY
        );
    }

    /**
     * Register widget translations.
     */
    public static function registerTranslations() {
        if (!isset(Yii::$app->i18n->translations['upload']) && !isset(Yii::$app->i18n->translations['upload/*'])) {
            Yii::$app->i18n->translations['upload'] = [
                'class' => 'yii\i18n\PhpMessageSource',
                'basePath' => '@plathir/upload/messages',
                'forceTranslation' => true,
                'fileMap' => [
                    'upload' => 'upload.php'
                ]
            ];
        }
    }

}
