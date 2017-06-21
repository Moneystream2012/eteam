<?php /* @var $this Controller */ ?>
<?php $this->beginContent('//layouts/dynamic'); ?>
<div id="content" class="cr_block">
    <?php $this->widget('application.widgets.AlertWidget') ?>
	<?php echo $content; ?>
</div><!-- content -->
<?php $this->endContent(); ?>