<?php

use yii\helpers\Html;
use yii\helpers\Url;
?>

<div class="upload_widget">

    <div id="myUplPanelclass" class="panel panel-info">
        <!-- Default panel contents -->
        <div class="panel-heading"><span class="glyphicon glyphicon-paperclip" aria-hidden="true"></span><?= $widget->galleryType ? ' Gallery' : ' Attachments' ?></div>
        <div class="panel-body">


            <?= Html::activeHiddenInput($model, $widget->attribute, ['class' => 'attachments']); ?>    

            <div class="upload_box">
                <div class="upl_thumbnail"  id ="dragbox">
                    <div class="uploader_label">
                        <div class="image_preview">

                        </div>  
                        <div class="spanDiv">
                            <span>
                                click here for Upload Files
                            </span>
                        </div>

                    </div>

                </div>
                <div class="upload_buttons hidden">
                    <button type="button" id="SubmitBtn" class="btn btn-primary upload_files btn-sm" aria-label="Upload Files">
                        <span class="glyphicon glyphicon-upload" aria-hidden="true"></span> Upload Files
                    </button>
                    <button type="button" id="ClearBtn" class="btn btn-danger clear_files btn-sm" aria-label="Upload another photo">
                        <span class="glyphicon glyphicon-trash" aria-hidden="true"></span> Clear
                    </button>
                </div>


                <div class="row" style="padding-top:10px;">
                    <div>
                        <div id="progressOuter" class="progress progress-striped active hidden" >
                            <div id="progressBar" class="progress-bar progress-bar-success"  role="progressbar" aria-valuenow="45" aria-valuemin="0" aria-valuemax="100" style="width: 0%">
                            </div>
                        </div>
                        <div id="progressBox" class="progress progress-striped active hidden">

                        </div>
                    </div>
                </div>


                <div class="row-fluid" style="padding-top:10px;">
                    <div class="col-xs-10">
                        <div id="msgBox">
                        </div>
                        <div id="errBox">
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <div id="attachments_list">
            <table id="tfileLinks" class="table table-hover table-condensed">
                <thead class="info"></thead>
                <tr class="info">
                    <th>File Name</th>
                    <th></th>
                    <th>Action</th>
                </tr>
                <tbody id="bodyfileLinks">

                    <?php
                    if ($widget->attribute) {
                        $listFiles = json_decode($model->{$widget->attribute});
                        if ($listFiles) {
                            foreach ($listFiles as $file) {
                                ?>
                                <tr>
                                    <td class="col-lg-8 col-md-8 col-sm-8 col-xs-8">

                                        <?= $widget->galleryType ? Html::img($widget->previewUrl . '/' . $file, ['style' => 'width:50px']) : ''; ?>
                                        <?= Html::a($file, Url::to($widget->previewUrl . '/' . $file), ['class' => 'glyphicon glyphicon-paperclip', 'target' => "_blank", 'rel' => 'popover']); ?></td>
                                    <td class="col-lg-2 col-md-2 col-sm-2 col-xs-2">
                                        <span class="label label-success"><span class="glyphicon glyphicon-floppy-saved" aria-hidden="true"></span> Stored</span>
                                    </td>
                                    <td class="col-lg-2 col-md-2 col-sm-2 col-xs-2">
                                        <button type="button" id ="delete_file_<?= $file ?>" class="btn btn-danger btn-xs">
                                            <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
                                        </button>    
                                    </td>                                 
                                </tr>
                                <?php
                            }
                        }
                    }
                    ?>                      
                </tbody>
            </table>
        </div>
    </div>
</div>
