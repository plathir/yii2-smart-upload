<?php

namespace plathir\upload\actions;

use yii\base\Action;
use yii\base\DynamicModel;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\Response;
use yii\web\UploadedFile;
use plathir\upload\Widget;
use yii\imagine\Image;
use Imagine\Image\Box;
use Yii;

class ImageCropUploadAction extends Action {

    public $temp_path;
    public $uploadParam = 'file';
    public $maxSize = 3145728;  //3MB
    public $extensions = 'jpeg, JPEG, jpg, JPG, png, PNG, gif, GIF';
    public $width = 200;
    public $height = 200;

    /**
     * @inheritdoc
     */
    public function init() {
        Widget::registerTranslations();
        if ($this->temp_path === null) {
            throw new InvalidConfigException(Yii::t('upload', 'MISSING_ATTRIBUTE', ['attribute' => 'temp_path']));
        } else {
            $this->temp_path = rtrim(Yii::getAlias($this->temp_path), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        }
    }

    /**
     * @inheritdoc
     */
    public function run() {
        if (Yii::$app->request->isPost) {
            $file = UploadedFile::getInstanceByName($this->uploadParam);
            $model = new DynamicModel(compact($this->uploadParam));

            $model->addRule($this->uploadParam, 'file', [
                'maxSize' => $this->maxSize,
                'tooBig' => Yii::t('upload', 'TOO_BIG_ERROR', ['size' => $this->maxSize / (1024 * 1024)]),
                'extensions' => explode(', ', $this->extensions),
                'checkExtensionByMimeType' => false,
                'wrongExtension' => Yii::t('upload', 'EXTENSION_ERROR', ['formats' => $this->extensions])
            ])->validate();

            if ($model->hasErrors()) {
                $result = [
                    'error' => $model->getFirstError($this->uploadParam)
                ];
            } else {
                $model->{$this->uploadParam}->name = uniqid() . '.' . $model->{$this->uploadParam}->extension;
                $request = Yii::$app->request;

                $image = Image::crop(
                                $file->tempName . $request->post('filename'), intval($request->post('w')), intval($request->post('h')), [$request->post('x'), $request->post('y')]
                        )->resize(
                        new Box($this->width, $this->height)
                );

                if ($image->save($this->temp_path . $model->{$this->uploadParam}->name)) {
                    $result = [
                        'filelink' => $model->{$this->uploadParam}->name,
                    ];
                } else {
                    $result = [
                        'error' => Yii::t('upload', 'ERROR_CAN_NOT_UPLOAD_FILE')]
                    ;
                }
            }
            Yii::$app->response->format = Response::FORMAT_JSON;

            return $result;
        } else {
            throw new BadRequestHttpException(Yii::t('upload', 'ONLY_POST_REQUEST'));
        }
    }

}
