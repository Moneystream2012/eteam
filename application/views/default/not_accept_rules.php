<h1><?= Yii::t('messages','Your account is locked.');?></h1>

<p><?= Yii::t('messages','Your account has been suspended due to a refusal to accept the ');?> <a href="<?php echo Yii::app()->createUrl('/page/agreement') ?>"><?= Yii::t('messages','current user agreement of the system');?></a>.<br/><?= Yii::t('messages','Funds on the balance sheet will be paid in the usual manner.');?></p>
<p><?= Yii::t('messages','To resume work, contact the administration through');?> <a href="<?php echo Yii::app()->createUrl('/user/feedback') ?>"><?= Yii::t('messages','feedback form');?></a></p>