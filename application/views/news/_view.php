<?php
/* @var $this NewsController */
/* @var $data News */
?>

<div class="view">
    <div>
        <h2><?= CHtml::link(CHtml::encode($data->content->title), array('news/view', 'id' => $data->id)) ?></h2>
        <span><?= Yii::app()->dateFormatter->format('yyyy.MM.dd HH:mm', $data->create_time) ?></span>
        <p><?php echo StringHelper::cutString($data->content->text, 600) ?></p>
    </div>
</div>