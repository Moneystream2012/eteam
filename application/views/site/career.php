<?php
/* @var $this JobListController */
/* @var $dataProvider CActiveDataProvider */

$this->pageTitle = Yii::t('messages', 'Карьера'); ?>

<h1><?php echo CHtml::encode($this->pageTitle)?></h1>

<?php
$this->widget('zii.widgets.CListView', array(
    'dataProvider' => $dataProvider,
    'itemView' => '_careerview',
    'template' => ' {items} {pager}'
));
?>