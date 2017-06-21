<?php
/* @var $this SiteController */
/* @var $model ContactForm */
/* @var $form CActiveForm */

$this->pageTitle = Yii::t('messages', 'Контакты');
?>

<h1><?php echo CHtml::encode($this->pageTitle)?></h1>

<?php if (Yii::app()->user->hasFlash('contact')): ?>

    <div class="flash-success">
        <?php echo Yii::app()->user->getFlash('contact'); ?>
    </div>

<?php else: ?>
    <p>
        <b>ICQ:</b> 622288<br/>
        <b>Skype:</b> wappoff<br/>
        <b>Email:</b> <a href="mailto:wappoff@<?php echo CHtml::encode(Yii::app()->project->getDefaultDomain()) ?>">wappoff@<?php echo CHtml::encode(Yii::app()->project->getDefaultDomain()) ?></a><br/><br/>
        <b>Skype:</b> dot.dm<br/>
        <b>Email:</b> <a href="mailto:dima@<?php echo CHtml::encode(Yii::app()->project->getDefaultDomain()) ?>">dima@<?php echo CHtml::encode(Yii::app()->project->getDefaultDomain()) ?></a><br/>
        <br/>
        <!-- begin WebMoney Transfer : attestation label -->
        <a href="https://passport.webmoney.ru/asp/certview.asp?wmid=342002500029" target="_blank"><img src="/images/attestated<?= (Yii::app()->language == 'ru') ? ".gif" : "_en.jpg"?>" border="0"/></a>
        <!-- end WebMoney Transfer : attestation label -->
    </p>
     <p>
        <?= Yii::t('messages', 'Если у вас есть бизнес-запросы или другие вопросы, пожалуйста, заполните следующую форму, чтобы связаться с нами. Спасибо.'); ?>
    </p>

    <div class="form">

        <?php
        $form = $this->beginWidget('CaptchaActiveForm', array(
            'id' => 'contact-form',
            'enableClientValidation' => true,
            'clientOptions' => array(
                'validateOnSubmit' => true,
            ),
        ));
        ?>

        <p class="note"><?= Yii::t('messages', 'Поля отмеченные звездочкой'); ?> <span class="required">*</span> <?= Yii::t('messages', 'обязательны для заполнения.');?></p>

        <?php echo $form->errorSummary($model); ?>

        <div class="row" style="display:inline-block;">
             <?php echo $form->textField($model, 'name', array('placeholder' => Yii::t('messages', 'Имя') . '*')); ?>
            <?php echo $form->error($model, 'name'); ?>
        </div>

        <div class="row" style="display:inline-block;">
             <?php echo $form->textField($model, 'email', array('placeholder' => 'E-mail' . '*')); ?>
            <?php echo $form->error($model, 'email'); ?>
        </div>

        <div class="row" style="display:inline-block;">
             <?php echo $form->textField($model, 'subject', array('size' => 60, 'maxlength' => 128, 'placeholder' => Yii::t('messages', 'Тема') . '*')); ?>
            <?php echo $form->error($model, 'subject'); ?>
        </div>

        <div class="row" >
             <?php echo $form->textArea($model, 'body', array('rows' => 6, 'cols' => 50, 'placeholder' => Yii::t('messages', 'Сообщение') . '*')); ?>
            <?php echo $form->error($model, 'body'); ?>
        </div>

        <?= $form->captcha($model) ?>

        <div class="row buttons">
            <?php echo CHtml::submitButton(Yii::t('messages', 'Отправить')); ?>
        </div>

        <?php $this->endWidget(); ?>

    </div><!-- form -->

<?php endif; ?>