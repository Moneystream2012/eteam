<?php
/**
 * Class NewsWidget
 *
 * @author Volodymyr Lalykin
 */
/**
 * Виджет для вывода блока новостей
 *
 * @author Volodymyr Lalykin
 */
class NewsWidget extends CWidget
{
    /**
     * @inheritdoc
     */
    public function run()
    {
        $models = News::model()->findAll(News::model()->getCriteria());
        if ($models) {
            $this->render('news', array('news' => $models));
        }
    }
}