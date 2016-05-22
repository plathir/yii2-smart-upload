<?php

use yii\helpers\Html;
use yii\helpers\Url;
?>

<div class="listfiles_widget">

    <div class="flist">
        <?php
        if ($widget->attribute) {
            $listFiles = json_decode($model->{$widget->attribute});
            if ($listFiles) {
                foreach ($listFiles as $file) {
                    echo '<span class="glyphicon glyphicon-paperclip" aria-hidden="true"></span> ' . Html::a($file, Url::to($widget->previewUrl . $file), ['target' => "_blank",  'rel' =>"popover"]) . '<br>';
                }
            }
        }
        ?>                      
    </div>

</div>
