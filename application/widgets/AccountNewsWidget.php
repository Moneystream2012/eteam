<?php

class AccountNewsWidget extends CWidget {

    public function run() {

        $role_param = '';
        $category_filter = '';
        switch (Yii::app()->user->model->role) {
            case Role::model()->getWebmasterId():
            case Role::model()->getResellerId():
                $role_param = 'show_webmaster';
                $category_filter = 'AND `show_webmaster_category` LIKE "%:' . User::model()->findByAttributes(array('id' => Yii::app()->user->id))->webmasterCategory.':%"';
                break;
            case Role::model()->getAdvertiserId():
                $role_param = 'show_advertizer';
                break;
        }
        
        $rows = Yii::app()->db->createCommand('
            SELECT na.`id` , na.`description`
            FROM  `news_account` na
            LEFT JOIN  `news_account_hide` nah ON ( na.id = nah.news_id  AND nah.user_id = :user_id )
            WHERE
            (
                `isActual` = 1 OR
            	`date` >= :user_reg_date
            )
            AND `actualBefore` >= DATE(NOW())
            AND `date` <= "2014-08-06"
            AND  `' . $role_param . '` = 1
            ' . $category_filter . '
            AND nah.user_id IS NULL
            ORDER BY na.date DESC
            ')->queryAll(true, array(
                'user_id' => Yii::app()->user->id,
                'user_reg_date' => Yii::app()->user->model->created
                ));
        
        if (!empty($rows)) {
            if(Yii::app()->language != Yii::app()->sourceLanguage){
                foreach ($rows as $key => $row){
                    $description = Yii::app()->db->createCommand('SELECT `value` FROM `Multilingual` where `hash` = "'.md5('description'.'news_account'.Yii::app()->language.$row['description']).'"')->queryScalar();
                    if(!empty($description)){
                        $rows[$key]['description'] = $description;
                    }
                }
            }
            foreach ($rows as $key => $row){
                $rows[$key]['description'] = nl2br($rows[$key]['description']);
            }
            Yii::app()->clientScript->registerScript('accountNewsCloser', '
                $(".account_news_close").click(function(){
                    var news_id = $(this).attr("news_id");
                    $.get("' . CHtml::normalizeUrl(array('/user/hideNews')) . '?id="+news_id);
                    $("#account_news_"+news_id).hide("slow");
                });
                ', CClientScript::POS_READY);

            $this->render('accountNews', array(
                'rows' => $rows
            ));
        }
    }

}