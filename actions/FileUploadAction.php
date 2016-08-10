<?php

namespace plathir\upload\actions;

use yii\base\Action;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\Response;
use plathir\upload\Widget;
use Yii;
use plathir\upload\lib\FileUpload;

class FileUploadAction extends Action {

    public $uploadDir;
    public $uploadParam = 'file';
    public $maxSize = 3145728;  //3MB
    public $extensions = 'jpeg, JPEG, jpg, JPG, png, PNG, gif, GIF';
    private $fileName;                    // Filename of the uploaded file
    private $fileSizeLimit = 10485760;    // Size of uploaded file in bytes
    private $fileExtension;               // File extension of uploaded file
    private $fileNameWithoutExt;
    private $savedFile;                   // Path to newly uploaded file (after upload completed)
    private $errorMsg;                    // Error message if handleUpload() returns false (use getErrorMsg() to retrieve)
    private $isXhr;

    /**
     * @inheritdoc
     */
    public function init() {

        Widget::registerTranslations();
        if ($this->uploadDir === null) {
            throw new InvalidConfigException(Yii::t('upload', 'MISSING_ATTRIBUTE', ['attribute' => 'uploadDir']));
        } else {
            $this->uploadDir = rtrim(Yii::getAlias($this->uploadDir), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        }
    }

    /**
     * @inheritdoc
     */
    public function run() {
        if (Yii::$app->request->isPost) {
            if (isset($_POST['filename'])) {
                //  $this->fileName = uniqid() . '_' . $_POST['filename'];
                $this->fileName = pathinfo($_POST['filename'], PATHINFO_FILENAME) . '_' . uniqid() . '.' . pathinfo($_POST['filename'], PATHINFO_EXTENSION);

                $upload_dir = $this->uploadDir;
                $upload_file = $this->fileName;

                $uploader = new FileUpload('uploadfile');
                $uploader->newFileName = $this->fileName;
                $uploader->sizeLimit = $this->fileSizeLimit;
                // Handle the upload
                $result = $uploader->handleUpload($upload_dir);

                if (!$result) {
                    $upload_result = json_encode(array('success' => false, 'msg' => $uploader->getErrorMsg()));
                } else {
                    $upload_result = [
                        'success' => true,
                        'filelink' => $this->fileName
                    ];
                }

                Yii::$app->response->format = Response::FORMAT_JSON;

                return $upload_result;
            }
        } else {
            throw new BadRequestHttpException(Yii::t('upload', 'ONLY_POST_REQUEST'));
        }
    }

}
