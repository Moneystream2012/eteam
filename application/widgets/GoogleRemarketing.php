<?php
/**
  * @link http://installmonster.ru/
  * @copyright (c) 2015 InstallMonster
  */
 
 /**
  * Добавляет код Google-ремаркетинга
  *
  * @property integer $conversionId Идентификатор конверсии
  *
  * @author Oleksandr Roslov <tr.installmonster@tr.biz.ua>
  */
class GoogleRemarketing extends CWidget
{
	/**
	 * @var integer Идентификатор конверсии
	 */
    public $conversionId = null;

    /**
     * Inserts GA code
     */
    public function run()
    {
        if (!$this->conversionId) {
            return;
        }

        $this->render('googleRemarketing', array(
            'conversionId' => $this->conversionId,
        ));
    }
}