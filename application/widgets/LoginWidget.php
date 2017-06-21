<?php

class LoginWidget extends CWidget
{
    public function run()
    {
        if (Yii::app()->user->isGuest) {
            $model = new LoginForm;
            $this->render('loginWidgetGuest', array('model' => $model));
        } else {
            $this->render('loginWidgetAuthorized');
        }
    }
}
