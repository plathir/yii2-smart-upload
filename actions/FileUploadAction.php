<?php

namespace plathir\upload\actions;

use yii\base\Action;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\Response;
use plathir\upload\Widget;
use Yii;
use plathir\upload\lib\FileUpload;
use yii\imagine\Image;
use Imagine\Image\Box;

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
    public $thumbnail = false;
    public $thumbnail_width = 100;
    public $thumbnail_height = 100;
    public $thumbnail_mode = 'outbound';
    public $watermark = '';
    public $memory_limit = '256M';
    public $resize_max_width = 800;
    public $resize_max_height = 800;

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
                $this->fileName = \URLify::filter(pathinfo($_POST['filename'], PATHINFO_FILENAME), 255, "", true) . '_' . uniqid() . '.' . pathinfo($_POST['filename'], PATHINFO_EXTENSION);

                $upload_dir = $this->uploadDir;
                $upload_file = $this->fileName;

                $uploader = new FileUpload('uploadfile');
                $uploader->newFileName = $this->fileName;
                $uploader->sizeLimit = $this->fileSizeLimit;

                // Handle the upload
                $result = $uploader->handleUpload($upload_dir);
                unset($_POST['filename']);

                if (!$result) {
                    $upload_result = json_encode(array('success' => false, 'msg' => $uploader->getErrorMsg()));
                } else {

                    // apply watermark
                    if ( $this->isImage($upload_dir . $upload_file)) {


                        ini_set('memory_limit', $this->memory_limit);
                        $image_name = $upload_dir . $upload_file;


                        // resize with calculate aspect ratio

                        $imagine_org = Image::getImagine();
                        $image = $imagine_org->open($image_name);
                        $size = $image->getSize();

                        if ($size->getWidth() > $this->resize_max_width || $size->getHeight() > $this->resize_max_height) {
                            $ratio = $size->getWidth() / $size->getHeight();
                            $new_name = $upload_dir . uniqid() . '.jpg';

                            if ($ratio > 1) {
                                $target_width = $this->resize_max_width;
                                $target_height = $this->resize_max_width / $ratio;
                            } else {
                                $target_width = $this->resize_max_height * $ratio;
                                $target_height = $this->resize_max_height;
                            }
                            $image->resize(new Box($target_width, $target_height));
                            $size = $image->getSize();
                            
                            if ($this->watermark) {
                                $imagine = Image::getImagine();
                                $watermark = $imagine->open($this->watermark);
                                $wSize = $watermark->getSize();
                                $bottomRight = new \Imagine\Image\Point($size->getWidth() - $wSize->getWidth(), $size->getHeight() - $wSize->getHeight());
                                $image->paste($watermark, $bottomRight);
                            }

                            $image->save($new_name);
                            unlink($image_name);
                            rename($new_name, $image_name);
                        } else {
                            if ($this->watermark) {
                                $imagine = Image::getImagine();
                                $watermark = $imagine->open($this->watermark);
                                $size = $image->getSize();
                                $wSize = $watermark->getSize();
                                $bottomRight = new \Imagine\Image\Point($size->getWidth() - $wSize->getWidth(), $size->getHeight() - $wSize->getHeight());
                                $image->paste($watermark, $bottomRight);
                                $image->save($new_name);
                                unlink($image_name);
                                rename($new_name, $image_name);
                            }
                        }
                    }
                    // thumbnails create
                    if (( $this->thumbnail ) && $this->isImage($upload_dir . $upload_file) && ( $this->thumbnail_width > 0 && $this->thumbnail_height > 0 )) {
                        $image_thumb = Image::thumbnail($upload_dir . $upload_file, $this->thumbnail_width, $this->thumbnail_height);
                        $image_thumb->save($upload_dir . 'thumbs/' . $upload_file);
                    }

                    $upload_result = [
                        'success' => true,
                        'filelink' => $upload_file
                    ];
                }

                Yii::$app->response->format = Response::FORMAT_JSON;

                return $upload_result;
            }
        } else {
            throw new BadRequestHttpException(Yii::t('upload', 'ONLY_POST_REQUEST'));
        }
    }

    public function isImage($filename) {

        $mime_type = $this->getMimeType($filename);

        if (strpos($mime_type, 'image') !== false) {
            return true;
        } else {
            return false;
        }
    }

    public function getMimeType($filename) {
        return mime_content_type($filename);
    }

}
