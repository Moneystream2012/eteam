<?php
/* @var $this NewsController */
/* @var $dataProvider CActiveDataProvider */
?>

<h1><?php echo Yii::t('messages', 'Новости')?></h1>

<?php $this->widget('zii.widgets.CListView', array(
	'dataProvider'=>$dataProvider,
	'itemView'=>'_view',
        'template' => '{items} {pager}'
)); ?>
