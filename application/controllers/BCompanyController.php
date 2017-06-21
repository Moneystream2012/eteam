<?php

class BCompanyController extends CrudController
{
    /**
     * @inheritdoc
     */
    public $modelClass = 'BCompany';

    /**
     * @inheritdoc
     */
    public $baseModelClass = 'BaseBCampaign';

    /**
     * Displays a particular model.
     * @param integer $id the ID of the model to be displayed
     */
    public function actionView($id) 
    {
        $model = $this->loadModel($id);

        $stats = new BStatFilterForm;
        if (isset($_POST['BStatFilterForm'])) {
            $stats->attributes = $_POST['BStatFilterForm'];
        }
        $stats->company_id = 'b' . $model->id;
        $params = array(
            'company_id' => $stats->company_id,
            'start' => $stats->start,
            'end' => $stats->end,
            'b_name' => $stats->b_name,
        );

        $countBanners = Yii::app()->db->createCommand('SELECT COUNT(*) FROM b_company_banners WHERE deleted=0 and b_company_id=' . $model->id)->queryScalar();
        $bannersInfo = new CSqlDataProvider("
           SELECT '' id, id as bid, b_name, url, show_priority, image_name, created, b_company_id, banner_url, is_code, b_code, paused FROM b_company_banners 
           WHERE deleted=0 and b_company_id=" . $model->id
                , array(
            'totalItemCount' => $countBanners,
            'pagination' => array(
                'pageSize' => 10,
            ),
            'sort' => array(
                'defaultOrder' => array(
                    'show_priority' => CSort::SORT_DESC,
                )
            ),
                )
        );

        $this->render('view', array(
            'params' => $params,
            'model' => $model,
            'bannersInfo' => $bannersInfo,
            'stats' => $stats,
        ));
    }

    /**
     * Creates a new model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     */
    public function actionCreate() 
    {
        // устанавливается перечень поведений, которые будут использоваться
        BCompany::useBehaviors(array('activerecordRelation'));
        
        $model = new BCompany;
        $this->pageTitle = 'Создать кампанию';
        
        // инициализируется поведение activerecordRelation
        $model->activerecordRelation->init();
        
        if (($postData = Yii::app()->request->getPost('BCompany', null)) !== null) {

            $model->attributes = $postData;
            
            $model->setTargeting('site_categories', !empty($postData['site_categories']) ? $postData['site_categories'] : array(), 'target_site_category');
            $model->setTargeting('countries', !empty($postData['countries']) ? $postData['countries'] : array(), 'target_country');
            
//Баннеры
            $bannerData = Yii::app()->request->getPost('Banner', array());
            foreach ($bannerData as $key => $banner) {
                $bannerData[$key]['deleted'] = 0;
                $bannerData[$key]['image'] = CUploadedFile::getInstanceByName('Banner[' . $key . '][image]');
            }
            $model->banners = $bannerData;
            
            
            $model->validate();
            if (!$model->hasErrors()) {
                if ($model->save(false)) {
                    Banner::updatePriorityLine($model->id);
                    $this->redirect(array('view', 'id' => $model->id));
                }
            } 
        }

        $this->render('create', array(
            'model' => $model,
        ));
    }

    /**
     * Updates a particular model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id the ID of the model to be updated
     */
    public function actionUpdate($id) 
    {
        // устанавливается перечень поведений, которые будут использоваться
        BCompany::useBehaviors(array('activerecordRelation', 'log'));
        
        $model = $this->loadModel($id);
        $model->setScenario('adminUpdate');

        // инициализируется поведение activerecordRelation
        $model->activerecordRelation->init();
        
        if (($postData = Yii::app()->request->getPost('BCompany', null)) !== null) {

            $model->attributes = $postData;
            
            $model->setTargeting('site_categories', !empty($postData['site_categories']) ? $postData['site_categories'] : array(), 'target_site_category');
            $model->setTargeting('countries', !empty($postData['countries']) ? $postData['countries'] : array(), 'target_country');
            
//Баннеры
            $bannerData = Yii::app()->request->getPost('Banner', array());
            foreach ($bannerData as $key => $banner) {
                $bannerData[$key]['deleted'] = 0;
                $bannerData[$key]['image'] = CUploadedFile::getInstanceByName('Banner[' . $key . '][image]');
            }
            
            $model->banners = $bannerData;
            

            $model->validate();
            if (!$model->hasErrors()) {
                if ($model->save(false)) {
                    Banner::updatePriorityLine($model->id);
                    $this->redirect(array('view', 'id' => $model->id));
                }
            }
        }

        $this->render('update', array(
            'model' => $model,
        ));
    }

    /**
     * Меняет состояние кампании на "в архиве", проставляя в бд is_archived = 1.
     * В дальнейшем кампания не будет откручиваться
     * 
     * @param integer $id кампании из списка, 
     * которая будет перенесена в архив - is_archived = 1
     */
    public function actionArchive($id) 
    {
        // устанавливается перечень поведений, которые будут использоваться
        BCompany::useBehaviors(array('log'));
        $model = $this->loadBaseModel($id);
        $model->is_archived = 1;
        $model->update(array('is_archived'));
        // if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
        if (!isset($_GET['ajax']))
            $this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('index'));
    }

    /**
     * Меняет состояние кампании на "стандартное", выводя из статуса "В Архиве" и проставляя в бд is_archived = 0.
     * В дальнейшем кампания сможет откручиваться
     * 
     * @param integer $id кампании из списка,
     * которая будет восстановлена из архива - is_archived = 0
     */
    public function actionRestore($id) 
    {
        // устанавливается перечень поведений, которые будут использоваться
        BCompany::useBehaviors(array('log'));
        
        $model = $this->loadBaseModel($id);
        $model->is_archived = 0;
        $model->update(array('is_archived'));
    }

    /**
     * Lists all models.
     */
    public function actionIndex($user_id = '', $admin_priority = 0, $archived = 0, $is_trusted = 0) 
    {
        $model = new BCompany('search');
        $model->unsetAttributes();  // clear any default values
        if (isset($_GET['BCompany']))
            $model->attributes = $_GET['BCompany'];
        $model->is_archived = $archived;
        if (!empty($user_id)) {
            $model->user_id = $user_id;
        }
        if ($is_trusted) {
            $model->is_trusted = 1;
        }
        $model->admin_priority = $admin_priority;
        $this->render('index', array(
            'model' => $model,
        ));
        /*
          $dataProvider=new CActiveDataProvider('BCompany');
          $this->render('index',array(
          'dataProvider'=>$dataProvider,
          ));
         */
    }

    /**
     * Отображает лог выбранной сущности кампания/поле в модальном окне
     * @param class $model
     * @param integer $pk - id кампании из таблицы кампаний
     * @param type $target_attribute
     * @param type $limit
     */    
    public function actionShowLog($model = '', $pk = 0, $target_attribute = '', $limit = 0) 
    {
        Yii::app()->clientScript->scriptMap = array(
            'jquery.js' => false,
            'jquery.min.js' => false,
            'jquery-ui.min.js' => false,
                //    'jquery-ui-i18n.min.js' => false,
        );
        $script_include = true;
        if ($limit != 0) {
            $script_include = false;
        }
        $log = new EChangesLog();
        $log->model = $model;
        $log->pk = $pk;
        $log->target_attribute = $target_attribute;
        $log->limit = $limit;
        $this->renderPartial('_log', array(
            'log' => $log
                ), false, $script_include);
    }

    /**
     * Обрабатывает ajax запрос на получение почасовой статистики при клике на дату в таблице статистики
     * баннерной кампании
     * 
     * @param $dt дата в формате yyyy-mm-dd
     * @param array $params массив параметров
     */
    public function actionDateDetail($dt, $params = null) 
    {
        $dateDetail = new BStatFilterForm();
        if (!empty($params) && $params != 'undefined') {
            $params = explode(',', $params);
            $dateDetail->company_id = $params[0];
            $dateDetail->start = $params[1];
            $dateDetail->end = $params[2];
            $dateDetail->b_name = $params[3];
        } else {
            echo 'Нет данных, попробуйте перезагрузить страницу';
        }
        
        $columns = Yii::app()->request->getPost('columns', array());
        
        if ($dateDetail->validate()) {
            $this->renderPartial(
                '_hourly_stat_rows',
                array(
                    'rows' => $dateDetail->getAdminStatsDtBanners($dt),
                    'columns' => empty($columns) ? $dateDetail->getColumnsForAdminBannerCampaignStats() : $columns,
                    'dt' => $dt,
                    'params' => $params
                )
            );
        }
    }

    /**
     * Обрабатывает ajax запрос на получение часовой статистики по баннерам при клике на часы в таблице статистики
     * баннерной кампании
     *
     * @param $dt дата в формате yyyy-mm-dd
     * @param $hr часы
     * @param array $params массив параметров
     */
    public function actionHourDetail($dt, $hr, $params = null) 
    {

        $dateDetail = new BStatFilterForm();
        if (!empty($params) && $params != 'undefined') {
            $params = explode(',', $params);
            $dateDetail->company_id = $params[0];
            $dateDetail->start = $params[1];
            $dateDetail->end = $params[2];
            $dateDetail->b_name = $params[3];
        } else {
            echo 'Нет данных, попробуйте перезагрузить страницу';
        }
        
        $columns = Yii::app()->request->getPost('columns', array());
        
        if ($dateDetail->validate()) {
            $this->renderPartial(
                '_hourly_stat_rows_by_banners',
                array(
                    'rows' => $dateDetail->getAdminStatsHrBanners($dt, $hr),
                    'columns' => empty($columns) ? $dateDetail->getColumnsForAdminBannerCampaignStats() : $columns,
                    'dt' => $dt,
                    'hr' => $hr
                )
            );
        }
    }

    /**
     * Ставит на паузу открутку кампании
     * @param integer $id
     */
    public function actionPause($id) 
    {
        // устанавливается перечень поведений, которые будут использоваться
        BCompany::useBehaviors(array('log'));
        
        $model = $this->loadBaseModel($id);
        $model->paused = 1;
        $model->update(array('paused'));
    }

    /**
     * Снимает с паузы открутку кампании
     * @param integer $id
     */
    public function actionUnpause($id) 
    {
        // устанавливается перечень поведений, которые будут использоваться
        BCompany::useBehaviors(array('log'));
        
        $model = $this->loadBaseModel($id);
        $model->paused = 0;
        $model->update(array('paused'));
    }

    /**
     * Ставит открутку баннера на паузу
     * @param integer $id
     */
    public function actionPauseBanner($id) 
    {
        $modelBanner = Banner::model()->findByPk($id);
        $modelBanner->paused = 1;
        $modelBanner->update(array('paused'));
    }

    /**
     * Снимает с паузы открутку баннера
     * @param integer $id
     */
    public function actionUnpauseBanner($id) 
    {
        $modelBanner = Banner::model()->findByPk($id);
        $modelBanner->paused = 0;
        $modelBanner->update(array('paused'));
    }

    /**
     * Returns the data model based on the primary key given in the GET variable.
     * If the data model is not found, an HTTP exception will be raised.
     * @param integer $id the ID of the model to be loaded
     * @return BCompany the loaded model
     * @throws CHttpException
     */
    public function loadModel($id) 
    {
        //$model = BCompany::model()->findByPk($id);
        $model = BCompany::model()->findByPk($id);
        if ($model === null)
            throw new CHttpException(404, 'The requested page does not exist.');
        return $model;
    }
    
    /**
     * Returns the data model based on the primary key given in the GET variable.
     * If the data model is not found, an HTTP exception will be raised.
     * @param integer $id the ID of the model to be loaded
     * @return BaseBCampaign the loaded model
     * @throws CHttpException
     */
    public function loadBaseModel($id) 
    {
        //$model = BCompany::model()->findByPk($id);
        if (($model = BaseBCampaign::model()->findByPk($id)) === null) {
            throw new CHttpException(404, 'The requested page does not exist.');
        }
        
        return $model;
    }

    /**
     * Performs the AJAX validation.
     * @param BCompany $model the model to be validated
     */
    protected function performAjaxValidation($model) 
    {
        if (isset($_POST['ajax']) && $_POST['ajax'] === 'bcompany-form') {
            echo CActiveForm::validate($model);
            Yii::app()->end();
        }
    }

    /**
     * Отвечает списком баннерных кампаний для виджета CJuiAutoComplete
     * Поиск по названию кампании
     * 
     * @throws CHttpException если не аякс-запрос
     * @return void
     */
    public function actionAutoCompliteListByName()
    {
        if (!Yii::app()->request->isAjaxRequest) {
            throw new CHttpException('500', 'Bad Request');
        }

        $res = null;
        $term = Yii::app()->request->getQuery('term', null);
        if ($term !== null) {
            $res = array();
            $sql = 'SELECT `id`, `name` FROM `b_company` WHERE `name` LIKE :name';
            $term = addcslashes($term, '%_');
            $params = array(':name' => '%'.$term.'%');
            $rows = Yii::app()->db->createCommand($sql)->queryAll(true, $params);
            
            foreach($rows as $row)
            {
              $res[] = array(
                'label'=>$row['name'],  // label for dropdown list
                'value'=>$row['name'],  // value for input field
                'id'=>$row['id'], // return value from autocomplete
              );
            }
        }
        
        //получить обратное значение, когда страница отображается с заполненной формой
        $loadVal = Yii::app()->request->getQuery('loadVal', null);
        if ($loadVal !== null) {
            $res = array();
            $sql = 'SELECT `id`, `name` FROM `b_company` WHERE `id`=:id';
            $params = array(':id' => $loadVal);
            $row = Yii::app()->db->createCommand($sql)->queryRow(true, $params);
            
            $res = array(
                'label'=>$row['name'],  // label for dropdown list
                'value'=>$row['name'],  // value for input field
                'id'=>$row['id'], // return value from autocomplete
              );
        }
        echo json_encode($res);
    }
}
