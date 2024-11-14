<?php

namespace plathir\upload\behaviors;

use yii\db\ActiveRecord;
use yii\base\Behavior;
use yii\base\InvalidParamException;
use yii\helpers\FileHelper;
use yii;
use yii\validators\Validator;

class MultipleUploadBehavior extends Behavior {
    /*
     * Are available 3 indexes:
     * `path` Path where the file will be moved.
     * - `temp_path` Temporary path from where file will be moved.
     * - `url` Path URL where file will be saved.
     */

    const EVENT_AFTER_UPLOAD = 'afterUpload';

    /**
     * Are available 3 indexes:
     * - `path` Path where the file will be moved.
     * - `tempPath` Temporary path from where file will be moved.
     * - `url` Path URL where file will be saved.
     *
     * @var array Attributes array
     */
    public $attributes = [];

    /**
     * @var boolean If `true` current attribute file will be deleted
     */
    public $unlinkOnSave = true;

    /**
     * @var boolean If `true` current attribute file will be deleted after model deletion
     */
    public $unlinkOnDelete = true;
    public $keyFolder;

    /**
     * @var array Publish path cache array
     */
    protected static $_cachePublishPath = [];

    public function events() {
        return [
            //ActiveRecord::EVENT_BEFORE_INSERT => 'beforeInsert',
            ActiveRecord::EVENT_AFTER_INSERT => 'afterInsert',
            ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeUpdate',
            ActiveRecord::EVENT_BEFORE_DELETE => 'beforeDelete',
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterUpdate'
        ];
    }

    /*
     * before Insert record
     */

    public function attach($owner) { //ok
        parent::attach($owner);
        if (!is_array($this->attributes) || empty($this->attributes)) {
            throw new InvalidParamException('Invalid or empty attributes array.');
        } else {
            foreach ($this->attributes as $attribute => $config) {
                if (!isset($config['path']) || empty($config['path'])) {
                    throw new InvalidParamException('Path must be set for all attributes.');
                }
                if (!isset($config['temp_path']) || empty($config['temp_path'])) {
                    throw new InvalidParamException('Temporary path must be set for all attributes.');
                }
                if (!isset($config['url']) || empty($config['url'])) {
                    $config['url'] = $this->publish($config['path']);
                }
                $this->attributes[$attribute]['path'] = FileHelper::normalizePath(Yii::getAlias($config['path'])) . DIRECTORY_SEPARATOR;
                $this->attributes[$attribute]['temp_path'] = FileHelper::normalizePath(Yii::getAlias($config['temp_path'])) . DIRECTORY_SEPARATOR;
                $this->attributes[$attribute]['url'] = rtrim($config['url'], '/') . '/';

                $validator = Validator::createValidator('string', $this->owner, $attribute);
                $this->owner->validators[] = $validator;
                unset($validator);
            }
        }
    }

    protected function saveFile($attribute, $insert = true) {
        $newFiles = [];
        $oldFiles = [];

        if (empty($this->owner->$attribute)) {
            if ($insert !== true) {
                if ($this->oldFiles($attribute)) {
                    foreach ($this->oldFiles($attribute) as $oldfile) {
//                        echo $oldfile;
                        $this->deleteFile($this->file($attribute, $oldfile));
                        $this->deleteFile($this->fileThumb($attribute, $oldfile));
                    }
                }
            }
        } else {


            $newFiles = json_decode($this->owner->$attribute);
            $oldFiles[] = $this->oldFiles($attribute);

            $delFiles = [];
            $moveFiles = [];
            if ($insert !== true) {
                if (count($oldFiles) > 0) {
                    $delFiles = $this->getDelFiles($oldFiles, $newFiles);
                    $moveFiles = $this->getMoveFiles($oldFiles, $newFiles);
                } else {
                    $moveFiles = $newFiles;
                }
            } else {
                $moveFiles = $newFiles;
            }

            $filesMoved = true;
            if ($moveFiles) {

                foreach ($moveFiles as $filename) {

                    $tempFile = $this->tempFile($attribute, $filename);
                    $file = $this->file($attribute, $filename);

                    $tempFile_thumb = $this->tempThumbFile($attribute, $filename);
                    $file_thumb = $this->fileThumb($attribute, $filename);

                    if (is_file($tempFile) && FileHelper::createDirectory($this->path($attribute))) {
                        if (rename($tempFile, $file) === false) {
                            $filesMoved = false;
                        }
                    }

                    if (is_file($tempFile_thumb) && FileHelper::createDirectory($this->pathThumbs($attribute))) {
                        if (rename($tempFile_thumb, $file_thumb) === false) {
                            $filesMoved = false;
                        }
                    }
                }
                if (!$filesMoved) {
                    unset($this->owner->$attribute);
                }
                $this->triggerEventAfterUpload();
            }

            if ($insert === false && $filesMoved) {
                // delete old Files
                if ($delFiles) {
                    foreach ($delFiles as $oldfile) {
                        $this->deleteFile($this->file($attribute, $oldfile));
                        $this->deleteFile($this->fileThumb($attribute, $oldfile));
                    }
                }
            } else {
                
            }
        }
    }

    /**
     * 
     * @param type $oldFiles
     * @param type $newFiles
     * @return type
     */
    protected function getDelFiles($oldFiles, $newFiles) {
        $delFiles = [];
        foreach ($oldFiles as &$value) {
            if (array_search($value, $newFiles) === false) {
                $delFiles[] = $value;
            }
        }
        return $delFiles;
    }

    /**
     * 
     * @param type $oldFiles
     * @param type $newFiles
     * @return type
     */
    protected function getMoveFiles($oldFiles, $newFiles) {
        $moveFiles = [];
        foreach ($newFiles as &$value) {
            if (array_search($value, $oldFiles) === false) {
                $moveFiles[] = $value;
            }
        }
        return $moveFiles;
    }

    /**
     * 
     * @param type $file
     * @return boolean
     */
    protected function deleteFile($file) {
        if (is_file($file)) {
            return unlink($file);
        }
        return false;
    }

    /**
     * 
     * @param type $path
     * @return boolean
     */
    protected function deletePath($path) {
        if (is_dir($path)) {
            if (count(glob($path . DIRECTORY_SEPARATOR . "*")) === 0) {
                return rmdir($path);
            }
            return true;
        }
        return false;
    }

    /**
     * @param string $attribute Attribute name
     *
     * @return string Old file path
     */
    public function oldFile($attribute) {
        return $this->path($attribute) . $this->owner->getOldAttribute($attribute);
    }

    public function oldThumbFile($attribute) {
        return $this->pathThumbs($attribute) . $this->owner->getOldAttribute($attribute);
    }

    public function oldFiles($attribute) {
        return json_decode($this->owner->getOldAttribute($attribute));
    }

    /**
     * @param string $attribute Attribute name
     *
     * @return string Path to file
     */
    public function path($attribute) {
        if ($this->folderID($attribute) == null) {
            return FileHelper::normalizePath($this->attributes[$attribute]['path']) . DIRECTORY_SEPARATOR;
        } else {
            return FileHelper::normalizePath($this->attributes[$attribute]['path'] . $this->folderID($attribute)) . DIRECTORY_SEPARATOR;
        }
    }

    public function pathThumbs($attribute) {
        if ($this->folderID($attribute) == null) {
            return FileHelper::normalizePath($this->attributes[$attribute]['path']) . DIRECTORY_SEPARATOR . 'thumbs' . DIRECTORY_SEPARATOR;
        } else {
            return FileHelper::normalizePath($this->attributes[$attribute]['path'] . $this->folderID($attribute)) . DIRECTORY_SEPARATOR . 'thumbs' . DIRECTORY_SEPARATOR;
        }
    }

    public function tempFile($attribute, $filename) {
        return $this->tempPath($attribute) . $filename;
    }

    public function tempThumbFile($attribute, $filename) {
        return $this->tempThumbPath($attribute) . $filename;
    }

    public function tempPath($attribute) {
        return $this->attributes[$attribute]['temp_path'];
    }

    public function tempThumbPath($attribute) {
        return $this->attributes[$attribute]['temp_path'] . 'thumbs' . DIRECTORY_SEPARATOR;
    }

    public function folderID($attribute) {
        // check if exist key_folder 
        if (isset($this->attributes[$attribute]['key_folder'])) {
            $this->keyFolder = true;
        } else {
            $this->keyFolder = false;
        }

        if ($this->keyFolder) {
            // return key_folder value from model field
            $key_folder = $this->owner->getAttributes([$this->attributes[$attribute]['key_folder']]);
            return $key_folder[$this->attributes[$attribute]['key_folder']];
        } else {
            return null;
        }
    }

    public function file($attribute, $filename) {
        return $this->path($attribute) . $filename;
    }

    public function fileThumb($attribute, $filename) {
        return $this->pathThumbs($attribute) . $filename;
    }

    public function publish($path) {
        if (!isset(static::$_cachePublishPath[$path])) {
            static::$_cachePublishPath[$path] = Yii::$app->assetManager->publish($path)[1];
        }
        return static::$_cachePublishPath[$path];
    }

    /**
     * Trigger [[EVENT_AFTER_UPLOAD]] event.
     */
    protected function triggerEventAfterUpload() {
        $this->owner->trigger(self::EVENT_AFTER_UPLOAD);
    }

    public function removeAttribute($attribute) {
        if (isset($this->attributes[$attribute])) {
            foreach ($this->oldFiles($attribute) as $filename) {
                $this->deleteFile($this->file($attribute), $filename);
                $this->deleteFile($this->fileThumb($attribute), $filename);
            }
            return $this->owner->updateAttributes([$attribute => null]);
        }
        return false;
    }

    /**
     * @param string $attribute Attribute name
     *
     * @return null|string Full attribute URL
     */
    public function urlAttribute($attribute) {
        if (isset($this->attributes[$attribute]) && $this->owner->$attribute) {
            return $this->attributes[$attribute]['url'] . $this->owner->$attribute;
        }
        return null;
    }

    /**
     * @param string $attribute Attribute name
     *
     * @return string Attribute mime-type
     */
    public function getMimeType($attribute) {
        return FileHelper::getMimeType($this->file($attribute));
    }

    /**
     * 
     */
    public function afterInsert() {
//        echo 'afterInsert :';

        foreach ($this->attributes as $attribute => $config) {
            if ($this->owner->$attribute) {
                $this->saveFile($attribute);
            }
        }
    }

    /*
     * Before Update Record
     */

    public function beforeUpdate() {
        foreach ($this->attributes as $attribute => $config) {
            if ($this->owner->isAttributeChanged($attribute)) {
                $this->saveFile($attribute, false);
            }
        }
    }

    /*
     * Before Delete Record
     */

    public function beforeDelete() {
        if ($this->unlinkOnDelete) {
            foreach ($this->attributes as $attribute => $config) {
                if ($this->owner->$attribute) {
                    foreach ($this->oldFiles($attribute) as $filename) {
                        $this->deleteFile($this->file($attribute, $filename));
                        $this->deleteFile($this->fileThumb($attribute, $filename));
                    }
                }
            }

            foreach ($this->attributes as $attribute => $config) {
                if ($this->owner->$attribute) {
                    if (!$this->folderID($attribute) == null) {
                        $this->deletePath($this->pathThumbs($attribute));
                        $this->deletePath($this->path($attribute));
                    }
                }
            }
        }
    }

    public function afterUpdate() {
        foreach ($this->attributes as $attribute => $config) {
            if ($this->owner->isAttributeChanged($attribute)) {
                $this->saveFile($attribute, false);
            }
        }
    }

    public function fileExists($attribute, $filename) {
        return file_exists($this->file($attribute, $filename));
    }

}
