<?php

namespace plathir\upload\actions;

use yii\base\Action;
use yii\base\DynamicModel;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\Response;
use yii\web\UploadedFile;
use plathir\cropper\Widget;
use yii\imagine\Image;
use Imagine\Image\Box;
use Yii;

class UploadAction extends Action {

    public $temp_path;
    public $uploadParam = 'file';
    public $maxSize = 2097152;
    public $extensions = 'jpeg, jpg, png, gif, zip, txt, doc';
    public $multiple = false;

    /**
     * @inheritdoc
     */
    public function init() {
        Widget::registerTranslations();
        if ($this->temp_path === null) {
            throw new InvalidConfigException(Yii::t('cropper', 'MISSING_ATTRIBUTE', ['attribute' => 'temp_path']));
        } else {
            $this->temp_path = rtrim(Yii::getAlias($this->temp_path), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        }
    }

    /**
     * @inheritdoc
     */
    public function run() {
        if (!$multiple) {
            $this->uploadSingleFile();
        } else {
            $this->uploadMultipleFiles();
        }
    }

    function uploadSingleFile() {
        if (Yii::$app->request->isPost) {


            $file = UploadedFile::getInstanceByName($this->uploadParam);
            $model = new DynamicModel(compact($this->uploadParam));

            $model->addRule($this->uploadParam, 'file', [
                'maxSize' => $this->maxSize,
                'tooBig' => Yii::t('cropper', 'TOO_BIG_ERROR', ['size' => $this->maxSize / (1024 * 1024)]),
                'extensions' => explode(', ', $this->extensions),
                'checkExtensionByMimeType' => false,
                'wrongExtension' => Yii::t('cropper', 'EXTENSION_ERROR', ['formats' => $this->extensions])
            ])->validate();

            if ($model->hasErrors()) {
                $result = [
                    'error' => $model->getFirstError($this->uploadParam)
                ];
            } else {
                $model->{$this->uploadParam}->name = uniqid() . '.' . $model->{$this->uploadParam}->extension;
                if ($image->save($this->temp_path . $model->{$this->uploadParam}->name)) {
                    $result = [
                        'filelink' => $model->{$this->uploadParam}->name,
                    ];
                } else {
                    $result = [
                        'error' => Yii::t('cropper', 'ERROR_CAN_NOT_UPLOAD_FILE')]
                    ;
                }
            }
            Yii::$app->response->format = Response::FORMAT_JSON;

            return $result;
        } else {
            throw new BadRequestHttpException(Yii::t('cropper', 'ONLY_POST_REQUEST'));
        }
    }

    function uploadMultipleFiles() {
        $files = UploadedFile::getInstancesByName($this->uploadParam);
    }

}
