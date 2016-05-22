<?php

namespace plathir\upload\actions;

use yii\base\Action;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\Response;
use Yii;

class FileDeleteAction extends Action {

    /**
     * @inheritdoc
     */
    public $uploadDir;

    public function init() {

        if ($this->uploadDir === null) {
            throw new InvalidConfigException(Yii::t('upload', 'MISSING_ATTRIBUTE', ['attribute' => 'uploadDir']));
        } else {
            $this->uploadDir = rtrim(Yii::getAlias($this->uploadDir), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        }
    }

    public function run() {
        if (Yii::$app->request->isPost) {
            if (isset($_POST['filename'])) {
                if (unlink($this->uploadDir .$_POST['filename'])) {
                   $upload_result = json_encode(array('success' => false, 'msg' => 'deleted'));
                } else {
                 $upload_result = json_encode(array('success' => false, 'msg' => 'cannot delete'));
                }
                
              Yii::$app->response->format = Response::FORMAT_JSON;
              return $upload_result;
            }
        } else {
            throw new BadRequestHttpException(Yii::t('upload', 'ONLY_POST_REQUEST'));
        }
    }

}
