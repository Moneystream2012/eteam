<?php
/* @var $this NewsController */
/* @var $model News */
?>

<div class="view">
    <div>
        <h1><?= CHtml::encode($model->content->title) ?></h1>
        <span><?= Yii::app()->dateFormatter->format('yyyy.MM.dd HH:mm', $model->create_time) ?></span>
        <p><?php echo $model->content->text ?></p>
    </div>
</div>