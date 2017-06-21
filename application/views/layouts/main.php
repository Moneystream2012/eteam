<!DOCTYPE html>
<html>
<head>
    <title><?php echo CHtml::encode(SiteHelper::getFullTitle($this->pageTitle)); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="/images/favicon.ico" />
    <link href="/css/monster-styles.css" rel="stylesheet" media="screen">
    <link href="<?php echo Yii::app()->theme->baseUrl ?>/css/themed.css" rel="stylesheet" media="screen">
    <link href="/css/monster-styles-<?php echo Yii::app()->language; ?>.css" rel="stylesheet" media="screen">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta http-equiv="Content-Language" content="<?php echo Yii::app()->language; ?>">
    <?php Yii::app()->clientScript->registerCoreScript('jquery')->registerCoreScript('jquery.ui'); ?>
    <script src="/js/jquery.scrollTo-min.js" type="text/javascript"></script>
    <script src="/js/main.js" type="text/javascript"></script>
</head>
<body>
    <?php $this->widget('GoogleAnalytics') ?>
    <?php $this->widget('application.widgets.CustomNotificationWidget') ?>

    <?php echo $content ?>

    <footer>
        <section>
            <?php
            if ($this->beginCache('SiteMenuWidget')) {
                $this->widget('application.widgets.SiteMenuWidget');
                $this->endCache();
            }
            ?>
            <div class="clear"></div>

            <div class="copyrights">
                <span>© <?php echo '2013-' . date('Y') ?> <?php echo CHtml::encode(Yii::app()->project->getDefaultDomain()) ?> <?php echo Yii::t('messages', 'Все права защищены.')?></span>
            </div>
            <?php if (Yii::app()->project->getCurrentBrand() == 'im'): ?>
                <div class="webmoney-icons">
                    <?= CHtml::link(CHtml::image('http://www.interkassa.com/docs/ik_88x31_01.gif', 'www.interkassa.com', array('border' => 0)), 'http://www.interkassa.com/', array('target' => '_blank')) ?>
                    <?= CHtml::link(CHtml::image('/images/attestated.gif', '', array('border' => 0)), 'https://passport.webmoney.ru/asp/certview.asp?wmid=342002500029', array('target' => '_blank')) ?>
                    <?= CHtml::link(CHtml::image('http://www.megastock.ru/Doc/88x31_accept/grey_light_rus.gif', 'www.megastock.ru', array('border' => 0)), 'http://www.megastock.ru/', array('target' => '_blank')) ?>
                </div>
            <?php endif ?>
        </section>
        <div class="footer-border"></div>
        <div class="clear"></div>
    </footer>

    <!-- Yandex.Metrika counter -->
    <div class="google">
        <script type="text/javascript">
            (function(d, w, c) {
                (w[c] = w[c] || []).push(function() {
                    try {
                        w.yaCounter22721051 = new Ya.Metrika({id: 22721051,
                            clickmap: true,
                            trackLinks: true,
                            accurateTrackBounce: true});
                    } catch (e) {
                    }
                });

                var n = d.getElementsByTagName("script")[0],
                    s = d.createElement("script"),
                    f = function() {
                        n.parentNode.insertBefore(s, n);
                    };
                s.type = "text/javascript";
                s.async = true;
                s.src = (d.location.protocol == "https:" ? "https:" : "http:") + "//mc.yandex.ru/metrika/watch.js";

                if (w.opera == "[object Opera]") {
                    d.addEventListener("DOMContentLoaded", f, false);
                } else {
                    f();
                }
            })(document, window, "yandex_metrika_callbacks");
        </script>
        <noscript><div><img src="//mc.yandex.ru/watch/22721051" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
        <!-- /Yandex.Metrika counter -->

        <?php $this->widget('GoogleRemarketing', array('conversionId' => Yii::app()->params['googleRemarketingConversionId'])) ?>
    </div>
</body>
</html>

