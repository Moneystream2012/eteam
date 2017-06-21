<?php

class FlowController extends CrudController {

    /**
     * @inheritdoc
     */
    public $modelClass = 'Flow';

    /**
     * Управляет созданием потока
     *
     * @return void
     */
    public function actionCreate()
    {
        $model = new Flow();

        if (isset($_POST[get_class($model)])) {
            $model->setAttributes($_POST[get_class($model)]);
            if ($userId = User::getIdByEmail($model->user_id)) {
                $model->user_id = $userId;
                if ($model->save()) {
                    $this->redirect(array('index'));
                }
            } else {
                $model->addError('user_id', Yii::t('messages', 'Пользователь не найден'));
            }
        }

        $this->render('create', compact('model'));
    }

    /**
     * Выводит страницу просмотра потока
     *
     * @param integer $id
     */
    public function actionView($id)
    {
        $model = $this->loadModel($id);
        $stats = new StatFilterForm;
        $countries = new CountriesFilterForm;
        $countries->site_id = $id;
        $countries->start = $stats->start;
        $countries->end = $stats->end;
        if (isset($_POST['StatFilterForm'])) {
            $stats->attributes = $_POST['StatFilterForm'];
            $countries->start = $_POST['StatFilterForm']['start'];
            $countries->end = $_POST['StatFilterForm']['end'];
        }
        $stats->site_id = $model->id;
        $stats->validate();

        $params = array(
            'company_id' => $stats->company_id,
            'start' => $stats->start,
            'end' => $stats->end,
            'site_id' => $stats->site_id
        );
        $this->render('view', array(
            'params' => $params,
            'model' => $model,
            'stats' => $stats,
            'rows' => $countries->SiteStats,
            'countries' => $countries
        ));
    }

    /**
     * Удаляет поток
     *
     * @param integer $id id потока
     * @throws CHttpException если не POST-запрос
     */
    public function actionDelete($id)
    {
        if (Yii::app()->getRequest()->isPostRequest) {
            $model = $this->loadModel($id);
            $model->delete();

            if (!isset($_GET['ajax'])) {
                $this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('index'));
            }
        } else {
            throw new CHttpException(400, 'Bad Request');
        }
    }

    /**
     * Обрабатывает ajax-запрос на получение данных при клике на дату в таблице общей статистики
     *
     * @param string $dt дата в формате Y-m-d
     * @param array $params массив передаваемых параметров
     */
    public function actionDateDetail($dt, $params = array())
    {
        $dateDetail = new StatFilterForm();

        if (empty($params) || $params == 'undefined') {
            echo 'Нет данных, попробуйте перезагрузить страницу';
            Yii::app()->end();
        } else {
            $params = explode(',', $params);
        }

        $columns = Yii::app()->request->getPost('columns', array());

        if ($params[0] !== 'webmasterStats') {
            $dateDetail->company_id = $params[0];
            $dateDetail->start = $params[1];
            $dateDetail->end = $params[2];
            $dateDetail->site_id = $params[3];

            if ($dateDetail->validate()) {
                $this->renderPartial(
                    'application.modules.administration.views.sites._hourly_stat_rows',
                    array(
                        'rows' => $dateDetail->getAdminStatsHours($dt),
                        'columns' => empty($columns) ? $dateDetail->getColumnsForSitesStats() : $columns,
                        'dt' => $dt
                    )
                );
            }
        } else {
            if ($dateDetail->validate()) {
                $this->renderPartial(
                    'application.modules.administration.views.sites._webmaster_hourly_stat_rows',
                    array(
                        'rows' => $dateDetail->getAdminWebmasterStatsHours($dt, $params[1]),
                        'columns' => empty($columns) ? $dateDetail->getColumnsForWebmasterStats() : $columns,
                        'dt' => $dt
                    )
                );
            }
        }
    }
}
