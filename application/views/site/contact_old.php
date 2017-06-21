<?php
/* @var $this SiteController */
/* @var $model ContactForm */
/* @var $form CActiveForm */
?>

<h1><?= Yii::t('messages', 'Контакты');?></h1>

<?php if (Yii::app()->user->hasFlash('contact')): ?>

    <div class="flash-success">
        <?php echo Yii::app()->user->getFlash('contact'); ?>
    </div>

<?php else: ?>
    <p>
        <b><?= Yii::t('messages', 'Телефон');?>:</b> +356 3550 5333<br>
        <b>email:</b> <a href="mailto:<?php echo CHtml::encode(Yii::app()->project->getAdminEmail()) ?>"><?php echo CHtml::encode(Yii::app()->project->getAdminEmail()) ?></a><br/>
        <!-- begin WebMoney Transfer : attestation label -->
        <a href="https://passport.webmoney.ru/asp/certview.asp?wmid=342002500029" target="_blank"><img src="/images/attestated<?= (Yii::app()->language == 'ru') ? ".gif" : "_en.jpg"?>" border="0"/></a>
        <!-- end WebMoney Transfer : attestation label -->
    </p>
    <hr/>
    <p>
        <?= Yii::t('messages', 'Если у вас есть бизнес-запросы или другие вопросы, пожалуйста, заполните следующую форму, чтобы связаться с нами. Спасибо.'); ?>
    </p>

    <div class="form">

        <?php
        $form = $this->beginWidget('CActiveForm', array(
            'id' => 'contact-form',
            'enableClientValidation' => true,
            'clientOptions' => array(
                'validateOnSubmit' => true,
            ),
        ));
        ?>

        <p class="note"><?= Yii::t('messages', 'Поля отмеченные звездочкой'); ?> <span class="required">*</span> <?= Yii::t('messages', 'обязательны для заполнения.');?></p>

        <?php echo $form->errorSummary($model); ?>

        <div class="row">
            <?php echo $form->labelEx($model, 'name'); ?>
            <?php echo $form->textField($model, 'name'); ?>
            <?php echo $form->error($model, 'name'); ?>
        </div>

        <div class="row">
            <?php echo $form->labelEx($model, 'email'); ?>
            <?php echo $form->textField($model, 'email'); ?>
            <?php echo $form->error($model, 'email'); ?>
        </div>

        <div class="row">
            <?php echo $form->labelEx($model, 'subject'); ?>
            <?php echo $form->textField($model, 'subject', array('size' => 60, 'maxlength' => 128)); ?>
            <?php echo $form->error($model, 'subject'); ?>
        </div>

        <div class="row">
            <?php echo $form->labelEx($model, 'body'); ?>
            <?php echo $form->textArea($model, 'body', array('rows' => 6, 'cols' => 50)); ?>
            <?php echo $form->error($model, 'body'); ?>
        </div>

        <?php if (CCaptcha::checkRequirements()): ?>
            <div class="row">
                <?php echo $form->labelEx($model, 'verifyCode'); ?>
                <div>
                    <?php $this->widget('CCaptcha'); ?>
                    <?php echo $form->textField($model, 'verifyCode'); ?>
                </div>
                <div class="hint"><?= Yii::t('messages', 'Введите буквы изображенные на картинке');?>
                    <br/><?= Yii::t('messages', 'Регистр значения не имеет.');?></div>
                <?php echo $form->error($model, 'verifyCode'); ?>
            </div>
        <?php endif; ?>

        <div class="row buttons">
            <?php echo CHtml::submitButton(Yii::t('messages', 'Отправить')); ?>
        </div>

        <?php $this->endWidget(); ?>

    </div><!-- form -->

<?php endif; ?>