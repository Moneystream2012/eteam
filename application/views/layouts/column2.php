<?php /* @var $this Controller */ ?>
<?php $this->beginContent('//layouts/dynamic'); ?>


<table style="width: 100%;" cellpadding="0" cellspacing="0">

    <tr>
        <td style="width: 80%;">
            <div id="content">
                <?php $this->widget('application.widgets.AlertWidget'); ?>
                <?php echo $content; ?>
            </div>
        </td>
        <td valign="top">
            <div id="sidebar">
                <?php
                $this->beginWidget('zii.widgets.CPortlet', array(
                    'title' => Yii::t('messages','Действия'),
                ));
                if ($this->beginCache('RightMenu', array('varyByParam' => array_keys($_GET)))) {
                    $this->widget('YiiSmartMenuExtended', array(
                        'items' => $this->menu,
                        'htmlOptions' => array('class' => 'operations'),
                    ));
                    $this->endCache();
                }
                $this->endWidget();
                ?>

                <?php if (!empty($this->sidebarGrid)): ?>
                    <strong><?php echo Yii::t('messages', 'Другие сайты пользователя')?></strong>
                    <?php $this->widget('zii.widgets.grid.CGridView', array(
                        'id' => 'site-grid',
                        'dataProvider' => $this->sidebarGrid,
                        'columns' => array(
                            array(
                                'name' => 'url',
                                'value' => 'CHtml::link($data->url,array("/administration/sites/view","id"=>$data->id))',
                                'type' => 'html',
                            ),
                            array(
                                'name' => 'status',
                                'value' => '$data->statusValue',
                            )
                        )
                    ));
                endif;
                ?>
            </div>
        </td>
    </tr>

</table>



<?php $this->endContent(); ?>