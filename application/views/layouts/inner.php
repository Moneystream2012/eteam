<?php
/**
 * @var $this CController
 */
?>
<?php $this->beginContent('//layouts/main') ?>
    <div class="item-level-0 item-level"><a name="level-0"></a></div>
    <header class="city_small" style="background-image:url(/images/city-smoll-1.png)">
        <div id="menu-vertical2">
            <nav>
                <?php
                if ($this->beginCache('SiteMenuWidget')) {
                    $this->widget('application.widgets.SiteMenuWidget');
                    $this->endCache();
                }
                ?>
                <div class="logosmogo">
                    <a href="http://<?php echo CHtml::encode(Yii::app()->project->getDefaultDomain()) ?>/" > 	 <img src="<?php echo CHtml::encode(Yii::app()->theme->baseUrl . '/images/logo.png') ?>" class="cloud-1" /></a>
                </div>
                <div class="auth-form2">
                    <div>
                        <?php $this->widget('LoginWidget') ?>
                    </div>
                </div>
                <div class="clear"></div>
            </nav>
        </div>
        <?php
        if ($this->beginCache('I18nWidget')) {
            $this->widget('application.widgets.I18nWidget.I18nWidget');
            $this->endCache();
        }
        ?>
        <div id="clouds">
            <div>
                <img src="/images/cloud-1.png" class="cloud-1" />
                <img src="/images/cloud-2.png" class="cloud-2" />
                <img src="/images/cloud-3.png" class="cloud-3" />
                <img src="/images/cloud-4.png" class="cloud-4" />
            </div>
        </div>
    </header>

    <?php echo $content ?>

<?php $this->endContent() ?>