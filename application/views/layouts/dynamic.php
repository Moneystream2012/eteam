<?php Yii::app()->clientScript->registerScriptFile('/js/settings.js', CClientScript::POS_END) ?>

<?php $this->beginContent('//layouts/inner') ?>

    <div class="item-level-11 item-level contacts">
        <div class="head-border"></div>
        <section>
            <article>
                <div>
                    <?php
                    if ($this->beginCache('MainMenu')) {
                        $this->widget('YiiSmartMenuExtended', array(
                            'id' => 'sub_menu',
                            'encodeLabel' => false,
                            'items' => (new MenuFactory())->createMenu()->getItems(),
                            'lastItemCssClass' => 'sub_menu_last',
                            'firstItemCssClass' => 'sub_menu_first',
                            'htmlOptions' => array('class' => 'main-menu'),
                        ));
                        $this->endCache();
                    }
                    ?>

                    <?php echo $content; ?>

                </div>
            </article>
        </section>
        <div class="clear"><br></div>
    </div>

    <div class="item-level-2 item-level contacts">
        <a name="level-2"></a>
        <div class="head-border"></div>
        <div class="clear"></div>
    </div>

<?php $this->endContent() ?>