<?php

namespace plathir\upload;

use yii\base\InvalidConfigException;
use yii\helpers\Json;
use yii\base\Widget;
use Yii;

class ListFilesWidget extends Widget {

    public $KeyFolder;
    public $previewUrl;
    public $attribute;
    public $model;
    public $previewImages = true;
    public $id;

    /**
     * @inheritdoc
     */
    public function init() {
        parent::init();
        self::registerTranslations();

        if ($this->model === null) {
            throw new InvalidConfigException('Please specify the "model" property.');
        }

        if ($this->previewUrl === null) {
            throw new InvalidConfigException(Yii::t('upload', 'MISSING_ATTRIBUTE', ['attribute' => 'previewUrl']));
        } else {
            $this->previewUrl = rtrim(Yii::getAlias($this->previewUrl), '/') . '/';
        }

        if ($this->KeyFolder) {
            $this->previewUrl = rtrim(Yii::getAlias($this->previewUrl), '/') . '/';
            $this->previewUrl = $this->previewUrl . $this->KeyFolder . '/' ;
        }
    }

    /**
     * @inheritdoc
     */
    public function run() {
        $this->registerClientAssets();

        return $this->render('list_widget', [
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

        $settings = [
            'preview' => $this->previewImages,
        ];

        $view->registerJs(
                'jQuery(".flist").listfiles(' . Json::encode($settings) . ');', $view::POS_READY
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
