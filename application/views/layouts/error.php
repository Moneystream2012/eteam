<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title><?php echo CHtml::encode(SiteHelper::getFullTitle($this->pageTitle)); ?></title>
    <?php Yii::app()->clientScript->registerCssFile(Yii::app()->request->baseUrl . '/css/error.css'); ?>
</head>
<body>
    <div class="wrapper">
        <div class="logo">
            <?php echo CHtml::link(CHtml::image(Yii::app()->theme->baseUrl . '/images/logo.png'), Yii::app()->homeUrl) ?>
        </div>
        <div class="wrapper-content">
            <?php echo $content; ?>
        </div>
    </div>
</body>
</html>