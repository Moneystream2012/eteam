<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="Content-Language" content="<?php echo Yii::app()->language ?>" />
    <title><?= CHtml::encode($this->pageTitle) ?></title>
    <link rel="stylesheet" type="text/css" href="<?= $this->assetsUrl ?>/style.css"/>
    <link rel="shortcut icon" type="image/x-icon" href="<?= $this->assetsUrl ?>/landing-favicon.ico"/>
</head>
<body>
    <?= $content ?>
</body>
</html>