<?php

class CompanyController extends Controller {

    /**
     * @var string the default layout for the views. Defaults to '//layouts/column2', meaning
     * using two-column layout. See 'protected/views/layouts/column2.php'.
     */
    public $layout = '//layouts/column2';

    /**
     * @var boolean true - log behavior logged chenges in company record on update
     */
    private $logChanges = false;

    /**
     * Фильтры
     *
     * @return array
     */
    public function filters()
    {
        return CMap::mergeArray(
            parent::filters(),
            array(
                'rights',
                'ajaxOnly + getCurrencySymbolByUserId'
            )
        );
    }

    public function actions() {
        return array(
//            'fileUpload' => 'ext.yii-redactor.actions.FileUpload',
            'imageUpload' => 'ext.yii-redactor.actions.ImageUpload',
            'imageList' => 'ext.yii-redactor.actions.ImageList',
        );
    }

    public function init() {
        parent::init();

        $this->addMenuList(array(
            array('label' => Yii::t('messages', 'Создать кампанию'), 'url' => array('create')),
            array('label' => Yii::t('messages', 'Графический приоритет кампаний'), 'url' => array('showOrder')),
            array('label' => Yii::t('messages', 'Создать специальный инсталлер'), 'url' => array('createSpecialInstaller')),
            array('label' => Yii::t('messages', 'Список кампаний'), 'url' => array('index')),
            array('label' => Yii::t('messages', 'Кампании с админ приоритетом'), 'url' => array('index', 'admin_priority' => 1, 'archived' => Company::NOT_ARCHIVED)),
            array('label' => Yii::t('messages', 'Архив кампаний'), 'url' => array('index', 'admin_priority' => 0, 'archived' => Company::ARCHIVED)),
            array('label' => Yii::t('messages', 'Кампании с включеным реселлом'), 'url' => array('index', 'traffic_type' => Company::TRAFFIC_TYPE_RESELL)),
            array('label' => Yii::t('messages', 'Доверенные кампании (не блокируются по CPM)'), 'url' => array('index', 'is_trusted' => 1)),
        //    array('label' => Yii::t('messages', 'Переадресация мобильного трафика'), 'url' => array('mobileTrafRedirect')),
            array('label' => Yii::t('messages', 'Рейты кампаний'), 'url' => array('rates')),
        ));
    }

    /**
     * AJAX-привязка предпроверок из форм статистики предпроверок ПО и процессам (страница просмотра кампании)
     *
     * @param type $id - company ID
     */
    public function actionBindRules($id = null) {
        if(Yii::app()->request->isAjaxRequest){

            // устанавливается перечень поведений, которые будут использоваться
            Company::useBehaviors(array('log', 'activerecordRelationAdmin'));

            $model = Company::model()->findByPk($id);

            // инициализируется поведение activerecordRelationAdmin
            $model->activerecordRelationAdmin->init();

            $coRules = array();

            if (is_array($installedSW = Yii::app()->request->getPost('installedSW', null))) {
                $allowForInstalledSW = Yii::app()->request->getPost('allowForInstalledSW', array());

                $uniqueValues = array();
                $allowed = array();
                $denied = array();
                while (list($i, $softWare) = each($installedSW)){

                    if (in_array($softWare = trim($softWare), $uniqueValues)) {
                        continue;
                    }
                    $uniqueValues[] = $softWare;

                    $item               = array();
                    $item['mode']       = (in_array($softWare, $allowForInstalledSW)) ? 'allow' : 'deny';
                    $item['type']       = 'installed';
                    $item['path']       = '';
                    $item['value']      = $softWare;

                    $coRules[] = $item;
                }
            }

            if (is_array($processSW = Yii::app()->request->getPost('processSW', null))) {
                $allowForProcessSW = Yii::app()->request->getPost('allowForProcessSW', array());

                $uniqueValues = array();
                $allowed = array();
                $denied = array();
                while (list($i, $softWare) = each($processSW)){

                    if (in_array($softWare = trim($softWare), $uniqueValues)) {
                        continue;
                    }
                    $uniqueValues[] = $softWare;

                    $item               = array();
                    $item['mode']       = (in_array($softWare, $allowForProcessSW)) ? 'allow' : 'deny';
                    $item['type']       = 'process';
                    $item['path']       = '';
                    $item['value']      = $softWare;

                    $coRules[] = $item;
                }
            }

            if (count($coRules) > 0) {
                $model->rulesAdmin = array_merge($model->getRulesAdmin(), $coRules);
            }

            $result = array();

            $model->validate();

            if(!$model->hasErrors()){
                $model->save(false);//need for to genarate API answer
                $result['success'] = true;
            }else{
                $result['error'] = $model->errors;
            }

            echo json_encode($result);

            // disable logging
            foreach (Yii::app()->log->routes as $route) {
                $route->enabled=false;
            }

            Yii::app()->end();
        }
    }

    /**
     *
     * renderPartial таблицу результатов анализа убитых запусков установленными ПО
     *
     * @param int $id - companyId
     * @return void
     */
    public function actionProcApplAnalyseAjax($id = null) {

        if (Yii::app()->request->isAjaxRequest) {
            if ( is_array( ($InstallMissAnalizeForm = Yii::app()->request->getPost('InstallMissAnalizeForm', null)) )  ) {
                //данные из формы
                $date           = isset($InstallMissAnalizeForm['date']) ? $InstallMissAnalizeForm['date'] : null;
                $missPercent   = isset($InstallMissAnalizeForm['missPercent']) ? $InstallMissAnalizeForm['missPercent'] : null;
                $missMin       = isset($InstallMissAnalizeForm['missMin']) ? $InstallMissAnalizeForm['missMin'] : null;
            } else {
                //данные из таблицы
                $id             = Yii::app()->request->getQuery('id', null);
                $date           = Yii::app()->request->getQuery('date', null);
                $missPercent   = Yii::app()->request->getQuery('missPercent', null);
                $missMin       = Yii::app()->request->getQuery('missMin', null);
            }
        }

        if ( ($id = intval($id, 10)) > 0) {

            $model                  = new InstallMissAnalizeForm;
            $model->companyId       = $id;
            $model->date            = isset($date)          ? $date : $model->date ;
            $model->missPercent     = isset($missPercent)   ? intval($missPercent) : $model->missPercent;
            $model->missMin         = isset($missMin)       ? intval($missMin) : $model->missMin;

            $coModel = Company::model()->findByPk($id);
            $apiRules = $coModel->getApiRuleValues('installed');

            $model->software_exists = $apiRules;

            $data = $model->getProcApplAnalyse();

            $sort = new CSort();
            $sort->attributes = array(
                        'date',
                        'name',
                        'no_install_count',
                        'install_count',
                        'percent_no_install',
            );
            $sort->defaultOrder = 'name ASC';
            $sort->route = 'procApplAnalyseAjax';
            $sort->params = array(
                        'id' => $id,
                        'date' => $model->date,
                        'missPercent' => $model->missPercent,
                        'missMin' => $model->missMin
                    );

            $dataProvider = new CArrayDataProvider($data, array(
                'id'  =>'proc-appl-analyze-grid',
                'keyField'  =>'date',
                'pagination' => array(
                    'pageSize' => 100
                )
            ));
            $dataProvider->sort = $sort;

            $this->renderPartial('_proc_appl_analyse_ajax', array(
                'model' => $model,
                'dataProvider' => $dataProvider,

            ));
        }

        return false;
    }

    /**
     *
     * renderPartial таблицу результатов анализа убитых запусков запущенными процессами
     *
     * @param type $id - companyId
     * @return void
     */
    public function actionProcApplProcesAnalyseAjax($id = null) {

        if (Yii::app()->request->isAjaxRequest) {
            if ( is_array( ($InstallMissAnalizeForm = Yii::app()->request->getPost('InstallMissAnalizeForm', null)) )  ) {
                //данные из формы
                $date           = isset($InstallMissAnalizeForm['date']) ? $InstallMissAnalizeForm['date'] : null;
                $missPercent   = isset($InstallMissAnalizeForm['missPercent']) ? $InstallMissAnalizeForm['missPercent'] : null;
                $missMin       = isset($InstallMissAnalizeForm['missMin']) ? $InstallMissAnalizeForm['missMin'] : null;
            } else {
                //данные из таблицы
                $id             = Yii::app()->request->getQuery('id', null);
                $date           = Yii::app()->request->getQuery('date', null);
                $missPercent   = Yii::app()->request->getQuery('missPercent', null);
                $missMin       = Yii::app()->request->getQuery('missMin', null);
            }
        }

        if ( ($id = intval($id, 10)) > 0) {

            $model                  = new InstallMissAnalizeForm;
            $model->companyId       = $id;
            $model->date            = isset($date)          ? $date : $model->date ;
            $model->missPercent     = isset($missPercent)   ? intval($missPercent) : $model->missPercent;
            $model->missMin         = isset($missMin)       ? intval($missMin) : $model->missMin;

            $coModel = Company::model()->findByPk($id);
            $apiRules = $coModel->getApiRuleValues('process');

            $model->software_exists = $apiRules;

            $data = $model->getProcApplProcesAnalyse();

            $sort = new CSort();
            $sort->attributes = array(
                        'date',
                        'name',
                        'no_install_count',
                        'install_count',
                        'percent_no_install',
            );
            $sort->defaultOrder = 'name ASC';
            $sort->route = 'procApplProcesAnalyseAjax';
            $sort->params = array(
                        'id' => $id,
                        'date' => $model->date,
                        'missPercent' => $model->missPercent,
                        'missMin' => $model->missMin
                    );

            $dataProvider = new CArrayDataProvider($data, array(
                'id'  =>'proc-appl-analyze-grid',
                'keyField'  =>'date',
                'pagination' => array(
                    'pageSize' => 100
                )
            ));
            $dataProvider->sort = $sort;

            $this->renderPartial('_proc_appl_proces_analyse_ajax', array(
                'dataProvider' => $dataProvider,
            ));

        }

        return false;
    }

    /**
     * Displays a particular model.
     * @param integer $id the ID of the model to be displayed
     */
    public function actionView($id)
    {
        $model = $this->loadModel($id);

        $assetsPath = Yii::app()->assetManager->publish(Yii::getPathOfAlias('application.modules.administration.views.company.assets'));
        Yii::app()->clientScript->registerScriptFile($assetsPath.'/js/view-proc-appl-analyse.js', CClientScript::POS_END);
        Yii::app()->clientScript->registerScriptFile($assetsPath.'/js/view-proc-appl-proces-analyse.js', CClientScript::POS_END);
        Yii::app()->clientScript->registerScriptFile($assetsPath.'/js/view-proc-appl-analyse-bind-form.js', CClientScript::POS_END);
        Yii::app()->clientScript->registerScriptFile($assetsPath.'/js/view-proc-appl-proces-analyse-bind-form.js', CClientScript::POS_END);

        $stats = new StatFilterForm;

        $statisticsFormModel = new StatisticsForm();
        $statisticsFormModel->compositeCampaignId = 'p' . $id;
        if (Yii::app()->request->isPostRequest) {
            $postData = Yii::app()->request->getPost('StatisticsForm', null);
            $statisticsFormModel->attributes = $postData;
            if (is_array($postData) && Yii::app()->request->isAjaxRequest) {
                echo CActiveForm::validate($statisticsFormModel);

                // disable logging
                foreach (Yii::app()->log->routes as $route) {
                    $route->enabled=false;
                }
                Yii::app()->end();
            }
        }
        $statisticsFormModel->process();

        if (isset($_POST['StatFilterForm'])) {
            $stats->attributes = $_POST['StatFilterForm'];
        }
        $stats->company_id = 'p' . $model->id;
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
            'statisticsFormModel' => $statisticsFormModel,
            'procApplAnalyseModel' => new InstallMissAnalizeForm,
        ));
    }

    public function actionCountryDetail($ts, $params = null){
        $dt = date('Y-m-d H:i:s', $ts);
        $dt_end = date('Y-m-d H:i:s', $ts +3600);
        if (!empty($params) && $params != 'undefined') {
            $params = explode('//', $params);
        } else {
            echo 'Нет данных, попробуйте перезагрузить страницу';
            Yii::app()->end();
        }

        $sql = '
            SELECT 
            `a`.`country`, 
            SUM(`a`.`cnt`) as `count_starts` ,
            `b`.`cnt` `count_offers`,
            `c`.`cnt` `count_installs`
            FROM (
                    SELECT `country`, COUNT(ip) as `cnt`
                    FROM `offer_unique_log` oul
                    WHERE 
                            `ts` >= "' . $dt . '" AND 
                            `ts` <= "' . $dt_end . '" AND 
                            `company_id` = '.ltrim($params[0],'p').'
                    GROUP BY `country`
            ) `b`
            LEFT JOIN (
                    SELECT `country`, COUNT(ip) as "cnt"
                    FROM `start_unique_log` sul
                    WHERE 
                            `ts` >= "' . $dt . '" AND 
                            `ts` <= "' . $dt_end . '" AND 
                            `company_id` = '.ltrim($params[0],'p').'
                    GROUP BY `country`
            ) `a` ON ( `b`.`country` = `a`.`country` )
            LEFT JOIN (
                    SELECT  `country`, COUNT(*) AS `cnt`
                    FROM `done_unique_log` 
                    WHERE 
                            `ts` >= "' . $dt . '" AND 
                            `ts` <= "' . $dt_end . '" AND 
                            `company_id` = '.ltrim($params[0],'p').'
                    GROUP BY `country`
            ) `c` ON ( `a`.`country` = `c`.`country` )
            GROUP BY `a`.`country`
            ORDER BY `count_starts` DESC
        ';
//        SELECT `country`, COUNT(ip) as "cnt"
//        FROM `start_unique_log` sul
//        WHERE `ts` >= "' . $dt . '"
//        AND `ts` <= "' . $dt_end . '"
//        AND company_id = '.ltrim($params[0],'p').'
//        GROUP BY 1
//        ORDER BY 2 DESC
        $countries = Yii::app()->db->createCommand($sql)->queryAll();
        if(!empty($countries)){
            foreach ($countries as $key => $value) {
                echo '<tr class="even country_details_' . $ts . '">';
                echo '<td>' . $value['country'] . '</td>';
                echo '<td>' . $value['count_offers'] . '</td>';
                echo '<td>' . $value['count_starts'] . '</td>';
                echo '<td> </td>';
                echo '<td>' . $value['count_installs'] . '</td>';
                echo '</tr>';
            }
        }

    }

    /**
     * Action статистики почасовки кампании
     *
     * @param string $dt Дата в формате Y-m-d
     */
    public function actionStatisticsHours($dt) {
        if (Yii::app()->request->isPostRequest) {
            $model = new StatisticsForm();
            $postData = Yii::app()->request->getPost('StatisticsForm', null);
            $model->attributes = $postData;
            $model->startDate = $dt;

            if (is_array($postData) && Yii::app()->request->isAjaxRequest) {
                $statisticDataProvider = null;
                if ($model->validate()) {
                    $statistic = new StatisticsDataSource($model);

                    if ($model->campaignType) {
                        $statisticDataProvider = $statistic->getCampaignHours();
                    } else {
                        $statisticDataProvider = $statistic->getAllHours();
                    }
                }
                $this->renderPartial('statisticsHours', array(
                    'model' => $model,
                    'errors' => $model->getErrors(),
                    'statisticDataProvider' => $statisticDataProvider
                ));
            }
        }
    }

    public function actionShowLog($model = '', $pk = 0, $target_attribute = '', $limit = 0) {
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

    public function actionBrowsers($id)
    {
        $companyModel = $this->loadBaseModel($id);

        $model = new StatisticsForm;
        $model->compositeCampaignId = 'p' . $companyModel->id;
        $model->userId = Company::model()->findByAttributes(array('id' => $id))->user_id;

        $postData = Yii::app()->request->getParam('StatisticsForm', null);
        if ($postData) {
            $model->attributes = $postData;
        }

        $this->render('browsers', array(
            'model' => $model,
            'statisticsDataProvider' => $model->validate()
                        ? (new StatisticsDataSource($model))->getBrowserStatisticsByPcCampaign()
                        : null
                )
        );
    }

    public function actionOs($id)
    {
        $companyModel = $this->loadBaseModel($id);

        $model = new StatisticsForm;
        $model->compositeCampaignId = 'p' . $companyModel->id;

        $postData = Yii::app()->request->getParam('StatisticsForm', null);
        if ($postData) {
            $model->attributes = $postData;
        }

        $this->render('os', array(
            'model' => $model,
            'statisticsDataProvider' => $model->validate()
                        ? (new StatisticsDataSource($model))->getOsStatisticsByPcCampaign()
                        : null
                )
        );
    }

    /**
     * JSON-ответ. Получение всех проверок успешности установки кампаний определенной категории
     *
     * @return void
     */
    public function actionCategoryApiCheckAdmin() {
        if (Yii::app()->request->isAjaxRequest) {

            $result = array();

            try {
                if (($categoryId = intval(Yii::app()->request->getPost('categoryId', null), 10)) > 0) {
                    //текущая кампания. Проверки текущей кампании исключаются из результатов
                    $currCompanyId = intval(Yii::app()->request->getPost('currCompanyId', null), 10);
                    //ID провероверок, которые исключаются из результатов 
                    $exceptRuleIdList = explode(',', Yii::app()->request->getPost('except', ''));

                    $sql = 'SELECT DISTINCT `c`.`id`
                            FROM `company` `c`
                            JOIN `company2company_category` `cc` ON `c`.`id` = `cc`.`company_id` AND `cc`.`company_category_id` = :categoryId
                            WHERE (`c`.`status` = :status2 OR `c`.`status` = :status4 OR `c`.`status` = :status5) ';
                    $sql .= $currCompanyId > 0 ? ' AND `c`.`id` <> '.$currCompanyId : '' ;

                    $params                 = array();
                    $params[':categoryId']  = $categoryId;
                    $params[':status2']     = Company::STATUS_ACTIVE;
                    $params[':status4']     = Company::STATUS_DISABLED_BY_CPM;
                    $params[':status5']     = Company::STATUS_DISABLED_BY_IU;

                    $companyIds     = Yii::app()->db->createCommand($sql)->queryColumn($params);
                    $companyIds     = empty($companyIds) ? array(-1) : $companyIds ;

                    $criteria = new CDbCriteria();
                    $criteria->addInCondition('id', $companyIds);

                    $companyList = Company::model()->findAll($criteria);

                    //формируем полный набор правил найденых кампаний
                    $rules = array();
                    if(is_array($companyList) && count($companyList) > 0){
                        while(list($i, $companyItem) = each($companyList)){
                            $rules = array_merge($rules, $companyItem->getCheckRules());
                        }
                    }

                    //задаем уникальность правилам
                    $ruleList = array();
                    if(is_array($rules) && count($rules) > 0){
                        while(list($i, $modelRules) = each($rules)){
/*
                            //получаем экземпляр правила
                            //сериализированная строка ПОСТпроверок не содержит режима
                            //ключ необходим для поиска правила
                            if (empty($item['mode'])) {
                                $item['mode'] =  'check' ;
                            }

                            //получаем экземпляр правила
                            if (! (($modelRules = Rules::getUniqueRule($item)) instanceof  Rules)) {
                                Yii::log('Rules::getUniqueRule failed', 'warning', 'bindRules');
                                continue;
                            }
*/
                            //указанные правила исключаются
                            if(in_array($modelRules->id, $exceptRuleIdList)){
                                continue;
                            }
                            $ruleList[$modelRules->id] = $modelRules->toArray(array('id', 'type', 'mode', 'path', 'value', 'ruletext'));
                        }
                    }


                    $result['success'] = $ruleList;
                }

            } catch (Exception $e) {
                $result['error'] = $e->getMessage();
            }

            echo json_encode($result);

        }

        // disable logging
        foreach (Yii::app()->log->routes as $route){
            $route->enabled=false;
        }

        Yii::app()->end();
    }

    public function actionClone($id)
    {
        $userEmail = Yii::app()->request->getPost('userEmail');
        $campaignName = Yii::app()->request->getPost('campaignName');
        if (empty($userEmail) || empty($campaignName)) {
            Yii::app()->user->setFlash('inputError', 'Ошибка. Укажите имя для кампании-клона и email пользователя');
            $this->redirect(array('view', 'id' => $id));
            return;
        }
        $userId = User::model()->getIdByParams(array('email' => $userEmail, 'role' => Role::model()->getAdvertiserId()));
        if (!$userId) {
            Yii::app()->user->setFlash('inputError', "Email $userEmail не зарегистрирован в системе как рекламодатель");
            $this->redirect(array('view', 'id' => $id));
            return;
        }
        $campaign = BasePcCampaign::model()->findByPk($id);
        $campaign->user_id = $userId;
        $campaign->status = BasePcCampaign::STATUS_MODERATE;
        $campaign->name = $campaignName;
        $campaign->cloneCampaign(Yii::getPathOfAlias('webroot'));
        $this->redirect(array('view', 'id' => $campaign->id));
    }

    public function actionCreate()
    {
        // устанавливается перечень поведений, которые будут использоваться
        Company::useBehaviors(array('activerecordRelationAdmin'));

        $model = new Company;
        $this->pageTitle = 'Создать кампанию';

        // инициализируется поведение activerecordRelationAdmin
        $model->activerecordRelationAdmin->init();

        if (($postData = Yii::app()->request->getPost('Company', null)) !== null) {

            Yii::import('application.helpers.JsonHelper', true);

            $model->attributes = $postData;

            $model->rules = JsonHelper::getInstance()->decodeList($postData, 'rules');
            $model->rulesAdmin = JsonHelper::getInstance()->decodeList($postData, 'rulesAdmin');
            $model->rulesCheck = JsonHelper::getInstance()->decodeList($postData, 'rulesCheck');
            $model->rulesCheckAdminExcept = isset($postData['rulesCheckAdminExcept']) ? $postData['rulesCheckAdminExcept'] : array();

            $model->setTargeting('categories', !empty($postData['categories']) ? $postData['categories'] : array());
            $model->setTargeting('site_categories', !empty($postData['site_categories']) ? $postData['site_categories'] : array(), 'target_site_category');
            $model->setTargeting('countries', !empty($postData['countries']) ? $postData['countries'] : array(), 'target_country');
            $model->setTargeting('languages', !empty($postData['languages']) ? $postData['languages'] : array(), 'target_language');
            $model->setTargeting('browsers', !empty($postData['browsers']) ? $postData['browsers'] : array(), 'target_browser');
            $model->setTargeting('times', !empty($postData['times']) ? $postData['times'] : array(), 'target_time');
            $model->setTargeting('windowsVersions', !empty($postData['windowsVersions']) ? $postData['windowsVersions'] : array(), 'target_windowsVersion');

            if ($model->type == Company::TYPE_OFFERSCREEN) {
                $model->force_show_options = 1;
            }

            if ($model->save() && $model->precheckRelationSave()) {
                if (count($model->api_check) > 0) {
                    Rules::model()->generateSingleListPreCheckAdvertizeSoftwareCache();
                }
                $this->redirect(array('view', 'id' => $model->id));
            } 
        }

        $this->render('create', array(
            'modelRule' => Rules::model(),
            'model' => $model,
            'categories' => CompanyCategory::model()->findAll(array('order' => 'ordr_group')),
            'modelDomainAdvertizeDirt' => DomainAdvertizeDirt::model(),
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
        Company::useBehaviors(array('activerecordRelationAdmin', 'log'));

        $model = $this->loadModel($id);
        $this->pageTitle = 'Редактировать кампанию' . $model->name;

        // инициализируется поведение activerecordRelationAdmin
        $model->activerecordRelationAdmin->init();

        $model->setScenario('adminUpdate');

        if (($postData = Yii::app()->request->getPost('Company', null)) !== null) {

            Yii::import('application.helpers.JsonHelper', true);

            $preSaveCompanyStatus = $model->status;
            $tmpAttr = $model->attributes;

            $model->attributes = $postData;

            $model->rules = (new RuleModelHelper(JsonHelper::getInstance()->decodeList($postData, 'rules'), array('refresh' => array('path', 'value'))))->getObjectList();
            $model->rulesAdmin = (new RuleModelHelper(JsonHelper::getInstance()->decodeList($postData, 'rulesAdmin'), array('refresh' => array('path', 'value'))))->getObjectList();
            $model->rulesCheck = (new RuleModelHelper(JsonHelper::getInstance()->decodeList($postData, 'rulesCheck'), array('refresh' => array('path', 'value'))))->getObjectList();
            $model->rulesCheckAdminExcept = isset($postData['rulesCheckAdminExcept']) ? $postData['rulesCheckAdminExcept'] : array();

            $model->setTargeting('categories', !empty($postData['categories']) ? $postData['categories'] : array());
            $model->setTargeting('site_categories', !empty($postData['site_categories']) ? $postData['site_categories'] : array(), 'target_site_category');
            $model->setTargeting('countries', !empty($postData['countries']) ? $postData['countries'] : array(), 'target_country');
            $model->setTargeting('languages', !empty($postData['languages']) ? $postData['languages'] : array(), 'target_language');
            $model->setTargeting('browsers', !empty($postData['browsers']) ? $postData['browsers'] : array(), 'target_browser');
            $model->setTargeting('times', !empty($postData['times']) ? $postData['times'] : array(), 'target_time');
            $model->setTargeting('windowsVersions', !empty($postData['windowsVersions']) ? $postData['windowsVersions'] : array(), 'target_windowsVersion');

            if ($model->type == Company::TYPE_OFFERSCREEN) {
                $model->force_show_options = 1;
            }

            if ($model->save()) {
                if ($preSaveCompanyStatus == Company::STATUS_MODERATE && $model->status == Company::STATUS_ACTIVE) {
                    Mailer::send($model->user->email, 'Ваша кампания активирована', array('model' => $model), 'campaignActive');
                } elseif ($preSaveCompanyStatus == Company::STATUS_MODERATE && $model->status == Company::STATUS_REJECTED) {
                    Mailer::send($model->user->email, 'Ваша кампания отклонена', array('model' => $model), 'campaignDeactive');
                }

                if ($model->precheckRelationSave()) {
                    if (
                        ($tmpAttr['api_check'] !== $model->api_check) || 
                        ($tmpAttr['status'] !== $model->status)
                    ) {
                        Rules::model()->generateSingleListPreCheckAdvertizeSoftwareCache();
                    }
                    $this->redirect(array('view', 'id' => $model->id));
                }
            } 
        } 

        //если файл инсталятора автообновляемый - меняем урл
        if ($installer = ExternalInstaller::model()->findByAttributes(array('company_id' => $id))) {
            $model->url = $installer->url;
        }

        $this->render('update', array(
            'modelRule' => Rules::model(),
            'model' => $model,
            'categories' => CompanyCategory::model()->findAll(array('order' => 'ordr_group')),
            'modelDomainAdvertizeDirt' => DomainAdvertizeDirt::model(),
        ));
    }

    /**
     * Manages all models.
     */
    public function actionIndex($user_id = '', $admin_priority = 0 , $archived = 0, $traffic_type = 0, $is_trusted = 0)
    {
        $model = new Company('search');

        $model->unsetAttributes();  // clear any default values
        if (isset($_GET['Company']))
            $model->attributes = $_GET['Company'];

        if (!empty($user_id)) {
            $model->user_id = $user_id;
        }
        if($traffic_type){
            $model->traffic_type = $traffic_type;
        }
        if($is_trusted){
            $model->is_trusted = 1;
        }
        $model->admin_priority = $admin_priority;
        $model->is_archived = $archived;
        $this->render('index', array(
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
        set_time_limit(300);

        // устанавливается перечень поведений, которые будут использоваться
        Company::useBehaviors(array('log'));

        $model = $this->loadBaseModel($id);
        $model->is_archived = 1;
        $model->update(array('is_archived'));
        CampaignGroup::model()->deleteAllByAttributes(array('campaign_id' => $id));
        Rules::model()->generateSingleListPreCheckAdvertizeSoftwareCache();
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
        set_time_limit(300);

        // устанавливается перечень поведений, которые будут использоваться
        Company::useBehaviors(array('log'));

        $model = $this->loadBaseModel($id);
        $model->is_archived = 0;
        $model->update(array('is_archived'));

        Rules::model()->generateSingleListPreCheckAdvertizeSoftwareCache();
    }

    /**
     * Генерирует страницу статистики кампании, сгруппированную по странам
     *
     * @param integer $id id кампании, для которой рассчитывается
     * @throws CHttpException если кампания с таким id не найдена
     */
    public function actionCountries($id)
    {
        $companyModel = $this->loadBaseModel($id);

        $model = new StatisticsForm;
        $model->compositeCampaignId = 'p' . $companyModel->id;

        $postData = Yii::app()->request->getParam('StatisticsForm', null);
        if ($postData) {
            $model->attributes = $postData;
        }

        $this->render('countries', array(
            'model' => $model,
            'statisticsDataProvider' => $model->validate()
                        ? (new StatisticsDataSource($model))->getUserCountryDaily()
                        : null
                )
        );
    }

    /**
     * Returns the data model based on the primary key given in the GET variable.
     * If the data model is not found, an HTTP exception will be raised.
     * @param integer $id the ID of the model to be loaded
     * @return Company the loaded model
     * @throws CHttpException
     */
    public function loadModel($id)
    {

        //$model = Company::model()->with('user', 'site_categories', 'countries', 'languages', 'browsers', 'times', 'windowsVersions')->findByPk($id);
        $model = Company::model()->with(array('user'))->findByPk($id);

        if ($model === null)
            throw new CHttpException(404, 'The requested page does not exist.');
        return $model;
    }

    /**
     * Returns the data model based on the primary key given in the GET variable.
     * If the data model is not found, an HTTP exception will be raised.
     * @param integer $id the ID of the model to be loaded
     * @return BasePcCampaign the loaded model
     * @throws CHttpException
     */
    public function loadBaseModel($id)
    {
        if (($model = BasePcCampaign::model()->findByPk($id)) === null) {
            throw new CHttpException(404, 'The requested page does not exist.');
        }
        return $model;
    }

    /**
     * Performs the AJAX validation.
     * @param Company $model the model to be validated
     */
    protected function performAjaxValidation($model) {
        if (isset($_POST['ajax']) && $_POST['ajax'] === 'company-form') {
            echo CActiveForm::validate($model);
            Yii::app()->end();
        }
    }

    public function actionGetCheckboxesParamsForm($i) {
        $model = new Company;
        $model->attributes = $_POST['Company'];
        $this->renderPartial('_checkboxes_params', array('form' => new CActiveForm, 'model' => $model, 'i' => $i));
    }

    public function actionShowOrder()
    {
        $model = new GraphicPriority();

        if (isset($_POST[get_class($model)])) {
            $model->attributes = $_POST[get_class($model)];
            if (!$model->validate()) {
                throw new CHttpException(400, 'Ошибка ввода данных');
            }
        }
        $this->render('showOrder', array(
            'offers' => $model->getOfferSet(),
            'model' => $model,
        ));
    }

    public function actionCreateSpecialInstaller() {
        $model = new SpecialInstallerForm;

        if (isset($_POST['SpecialInstallerForm'])) {
            $model->attributes = $_POST['SpecialInstallerForm'];

            if ($model->validate()) {
                $companies = Company::model()->findAllByPk($model->companies);
                if ($companies) {
                    $download = new DownloadsLog;
                    $download->url = $model->file_url;
                    $download->name = $model->file_name;
                    $download->type = $model->file_type;
                    $download->key = 2 * mt_rand(1, 32767) + 1;
                    $download->site_id = $model->site_id;

                    if ($download->save()) {

                        // find file serving domain

                        $domain = UserDomain::getFallbackDomainModel() ? UserDomain::getFallbackDomainModel()->domain : FileServing::getDomain();
                        $domains = UserDomain::model()->findAllByAttributes(array('user_id' => 0, 'deleted' => 0, 'banned' => 0, 'active' => 1, 'type' => 0, 'disabled'=>0));
                        if ($domains) {
                            $domain = $domains[array_rand($domains)]->domain;
                        }

                        /*
                         * {"error":"","uid":"b5754491e89cb7a4aba4e62590dc2300","offers":{"8":{"type":"installer","url":"http:\/\/elx-downloader.net\/dl\/te_setup2.exe","filename":"te_setup.exe","force_show_options":false,"options":{"1":{"offer8_1_chk":"<input type=\"checkbox\" name=\"offer8_1_chk\" id=\"offer8_1_chk\" value=\"1\" checked=\"checked\"\/> \u0423\u0441\u0442\u0430\u043d\u043e\u0432\u0438\u0442\u044c TorrentExpress?"}},"params":{"1":""}},"1":{"type":"homepage","url":"http:\/\/yambler.net\/?im","force_show_options":false,"options":{"1":{"offer1_1_chk":"<input type=\"checkbox\" name=\"offer1_1_chk\" id=\"offer1_1_chk\" value=\"1\" checked=\"checked\"\/> \u0423\u0441\u0442\u0430\u043d\u043e\u0432\u0438\u0442\u044c yambler.net \u0441\u0442\u0430\u0440\u0442\u043e\u0432\u043e\u0439 \u0441\u0442\u0440\u0430\u043d\u0438\u0446\u0435\u0439?"}},"params":{"1":""}}}}
                         */
                        $uid = md5('somesal_tdlfkhgkdfhgksudygkvd' . time());
                        $response = array('error' => '', 'uid' => $uid, 'offers' => array());
                        foreach ($companies as $company) {
                            $response['offers'][$company->id] = json_decode($company->api_answer, true);

                            $response['offers'][$company->id]['url'] = str_replace('##DOMAIN##', $domain, $response['offers'][$company->id]['url']);
                            $response['offers'][$company->id]['url'] = str_replace('{@original_file_url}', urlencode($download->url), $response['offers'][$company->id]['url']);
                            $response['offers'][$company->id]['url'] = str_replace('{@original_file_name}', urlencode($download->name), $response['offers'][$company->id]['url']);
                            // {@amonetize_cmdline}
                            $cmdline = '%Downloads%';
                            if (strlen($download->name) > 5) {
                                $ext = substr($download->name, strlen($download->name) - 4);
                                if ($ext == '.exe') {
                                    $cmdline = '/VERYSILENT';
                                }
                            }
                            $response['offers'][$company->id]['url'] = str_replace('{@amonetize_cmdline}', urlencode($cmdline), $response['offers'][$company->id]['url']);

                            if (isset($response['offers'][$company->id]['params'])) {
                                foreach ($response['offers'][$company->id]['params'] as $k2 => $param) {
                                    $param = str_replace('{@site_id}', $download->site_id, $param);
                                    $param = str_replace('{@iid}', $download->id, $param);


                                    if (strpos($param, '{@mail_rfr}') !== false) {
                                        $param = str_replace('{@mail_rfr}', (new BaseLoadMoneyPartner)->getReferral(), $param);
                                    }

                                    if (strpos($param, '{@mail_dmn}') !== false) {
                                        $param = str_replace('{@mail_dmn}', (new BaseLoadMoneyPartner)->getDomain(), $param);
                                    }

                                    if (strpos($param, '{@country}') !== false) {
                                        $param = str_replace('{@country}', 'RU', $param);
                                    }

                                    if (strpos($param, '{@current_domain}') !== false) {
                                        $domainModel = UserDomain::model()->getCurrentWorkingDomain(UserType::INTERNAL);
                                        $domain = $domainModel ? $domainModel->domain : UserDomain::model()->getBackupDomain();
                                        $param = str_replace('{@current_domain}', $domain, $param);
                                    }

                                    if (strpos($param, '{@uid}') !== false) {
                                        $param = str_replace('{@uid}', $uid, $param);
                                    }

                                    $response['offers'][$company->id]['params'][$k2] = $param;
                                }
                            }
                        }



                        $installer = new SpecialInstaller;
                        $installer->id = $download->id;
                        $installer->respose = json_encode($response);
                        if ($installer->save()) {

                            $config = json_encode(array(
                                'site-id' => $download->site_id,
                                'url' => $download->url,
                                'key' => $download->key,
                                'name' => $download->name,
                                'type' => $download->type,
                                'size' => 0,
                                'generalAPI' => 'http://' . $model->domain . '/api/index',
//                                'reservedAPI' => 'http://' . $secondary_domain . '/api/index',
                                'searchUrl' => Settings::get('installer_search_url'),
                            ));



                            $sign = md5($config . self::gen_salt(time(), $download->key));
                            $config = self::data_compress($download->id . ':' . $sign . ':' . $config);

                            $start = pack('S', 1 + rand(1, 65534));
                            $start.= pack('L', mb_strlen($config));
                            $start.= pack('S', crc32($start) & 0xffff);

                            $end = pack('L', crc32($config));
                            $end.=pack('S', 1 + rand(1, 65534));
                            $end.= pack('S', crc32($end) & 0xffff);


                            $filename = empty($download->name) ? $type . '.exe' : $download->name;

                            if (!strrpos($filename, '.exe')) {
                                $filename.='.exe';
                            }

                            $version = Version::model()->find(
                                'status = :status', array(':status' => Version::STATUS_ACTIVE)
                            );

                            $loader = $version->versionsDir . $version->id . '/regular/loader.exe';
                            if (file_exists($version->versionsDir . $version->id . '/regular/' . $download->type . '.exe')) {
                                $loader = $version->versionsDir . $version->id . '/regular/' . $download->type . '.exe';
                            }

                            $config_size = mb_strlen($config) + 16;
                            $config = $start . $config . $end;



                            $tmp = array();
                            for ($i = $config_size; $i < 2048; $i++) {
                                $tmp[] = chr(rand(1, 200));
//        $tmp[] = rand(0, 9);
                            }

                            $pos = $download->key & 0xFF;
                            $pos2 = $download->key & 0xFF;
                            $out = '';
                            $out2 = '';
                            for ($i = 0; $i < 256; $i++) {

                                $byte = $tmp[$pos % count($tmp)];
                                $out .= $byte;
                                $pos += ord($byte) + 30;

                                $byte = $tmp[$pos2 % count($tmp)];
                                $out2 .= $byte;
                                $pos2 += ord($byte) + 31;
                            }

                            Yii::app()->db->createCommand('INSERT INTO installer_key_site (`iid`, `key`, `key_2`, `site_id`, `site_id_resell`) VALUES
                            (:iid, :key, :key_2, :site_id, :site_id_resell)
                            ON DUPLICATE KEY UPDATE
                             `key` = :key, `key_2` = :key_2, site_id_resell = :site_id_resell')->execute(array(
                                'site_id' => $download->site_id,
                                'site_id_resell' => $download->site_id,
                                'key' => md5(base64_encode($out)),
                                'key_2' => md5(base64_encode($out2)),
                                'iid' => $download->id
                            ));


                            header('Content-Description: File Transfer');
                            header('Content-Type: application/octet-stream');
                            header('Content-Disposition: attachment; filename="' . $filename . '"'); //<<< Note the " " surrounding the file name
                            header('Content-Transfer-Encoding: binary');
                            header('Connection: Keep-Alive');
                            header('Expires: 0');
                            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                            header('Pragma: public');
                            header('Content-Length: ' . (filesize($loader) + 2048));

                            readfile($loader);
                            echo $config;
                            //echo trash block
                            echo implode('', $tmp);

                            exit;
                        }
                    }
                }
            }
        }

        $companies_raw = Yii::app()->db->createCommand('SELECT id, name FROM company')->queryAll();
        $companies = array();
        foreach ($companies_raw as $row) {
            $companies[$row['id']] = $row['name'];
        }


        $this->render('specialInstaller', array(
            'model' => $model,
            'companies' => $companies,
        ));
    }

    public static function data_decompress($data) {
        return @gzuncompress(@substr($data, 4));
    }

    public static function data_compress($data) {
        return pack("L", strlen($data)) . gzcompress($data);
    }

    public static function gen_lcg_str($init, $step, $len) {
        $min = 33;
        $max = 126;
        $v = $init;
        $s = "";
        for ($i = 0; $i < $len; $i++) {
            $v = ($v * $step + 1) & 0xFFFF;
            $s .= Chr($min + ($v % ($max - $min)));
        }
        return $s;
    }

    public static function gen_salt($date_ts, $key) {
        return self::gen_lcg_str((int) date('Ymd', $date_ts), $key, 64);
    }

    /**
     * Ставит на паузу открутку кампании
     * @param integer $id
     */
    public function actionPause($id)
    {
        // устанавливается перечень поведений, которые будут использоваться
        Company::useBehaviors(array('log'));

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
        Company::useBehaviors(array('log'));

        $model = $this->loadBaseModel($id);
        $model->paused = 0;
        $model->update(array('paused'));
    }

    public function actionCheckRules($id = null) {
        if(($id = intval($id, 10)) === 0){
            $msg = 'Не передан обязательный параметр companyId';
            echo $msg;
            #throw new CHttpException(404, $msg);
        }
        if(Company::model()->findByPk($id) === null){
            $msg = 'There is no such company';
            echo $msg;
            #throw new Exception($msg);
        }

        $model = new CheckRulesUniqueLog();
        $model->unsetAttributes();

        if(Yii::app()->request->isPostRequest){
            $checkRulesUniqueFilter = Yii::app()->request->getPost('CheckRulesUniqueLog');
            if($checkRulesUniqueFilter == true){
                $model->dateRangeStart = $checkRulesUniqueFilter['dateRangeStart'];
                $model->dateRangeEnd = $checkRulesUniqueFilter['dateRangeEnd'];
            }
        }

        $model->company_id = $id;
        $companyModel = Company::model()->findByPk($model->company_id);

        //Предпроверки
        $dataProviderPreCheck = $model->getStatCompanyPreCheck();
        //Проверки успешности
        $dataProviderSuccesCheck = $model->getStatCompanySuccesCheck();

        $this->render('checkRules', array(
            'companyModel' => $companyModel,
            'model' => $model,
            'dataProviderPreCheck' => $dataProviderPreCheck,
            'dataProviderSuccesCheck' => $dataProviderSuccesCheck,
                ));
        #var_dump($statCompanyPreCheck);
        #var_dump($statCompanySuccessCheck);
    }

    public function actionCheckRulesDaily($id = null) {

        if(Yii::app()->request->isPostRequest){
            if(
                    ($type = Yii::app()->request->getPost('type', null)) != null &&
                    ($dateRangeStart = Yii::app()->request->getPost('dateRangeStart', null)) != null &&
                    ($dateRangeEnd = Yii::app()->request->getPost('dateRangeEnd', null)) != null
            ){
                if(($id = intval($id, 10)) === 0){
                    $msg = 'Не передан обязательный параметр companyId';
                    echo $msg;
                    Yii::app()->end();
                }
                if(Company::model()->findByPk($id) === null){
                    $msg = 'There is no such company';
                    echo $msg;
                    Yii::app()->end();
                }

                $model = new CheckRulesUniqueLog();
                $model->unsetAttributes();

                $model->company_id  = $id;
                $model->dateRangeStart        = $dateRangeStart;
                $model->dateRangeEnd        = $dateRangeEnd;

                $ruleId = Yii::app()->request->getPost('rule', null);

                if($type === 'pre_check'){
                    //Предпроверки
                    $dataProviderPreCheck = $model->getStatCompanyPreCheckDaily($ruleId);
                    echo json_encode($dataProviderPreCheck);
                }
                if($type === 'success_check'){
                    //Проверки успешности
                    $dataProviderSuccesCheck = $model->getStatCompanySuccesCheckDaily($ruleId);
                    echo json_encode($dataProviderSuccesCheck);
                }
            }
        }
        // disable logging
        foreach (Yii::app()->log->routes as $route)
            $route->enabled=false;
        Yii::app()->end();
    }
    public function actionCheckRulesHourly($id = null) {

        if(Yii::app()->request->isPostRequest){
            if(
                    ($type = Yii::app()->request->getPost('type', null)) != null &&
                    ($date = Yii::app()->request->getPost('date', null)) != null
            ){
                if(($id = intval($id, 10)) === 0){
                    $msg = 'Не передан обязательный параметр companyId';
                    echo $msg;
                    Yii::app()->end();
                }
                if(Company::model()->findByPk($id) === null){
                    $msg = 'There is no such company';
                    echo $msg;
                    Yii::app()->end();
                }

                $model = new CheckRulesUniqueLog();
                $model->unsetAttributes();

                $model->company_id  = $id;
                $model->date        = $date;

                $ruleId = Yii::app()->request->getPost('rule', null);

                if($type === 'pre_check'){
                    //Предпроверки
                    $dataProviderPreCheck = $model->getStatCompanyPreCheckHourly($ruleId);
                    echo json_encode($dataProviderPreCheck);
                }
                if($type === 'success_check'){
                    //Проверки успешности
                    $dataProviderSuccesCheck = $model->getStatCompanySuccesCheckHourly($ruleId);
                    echo json_encode($dataProviderSuccesCheck);
                }
            }
        }
        // disable logging
        foreach (Yii::app()->log->routes as $route)
            $route->enabled=false;
        Yii::app()->end();
    }

    /**
     * Отвечает списком кампаний для виджета CJuiAutoComplete
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
            $sql = 'SELECT `id`, `name` FROM `company` WHERE `name` LIKE :name';
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
            $sql = 'SELECT `id`, `name` FROM `company` WHERE `id`=:id';
            $params = array(':id' => $loadVal);
            $row = Yii::app()->db->createCommand($sql)->queryRow(true, $params);

            $res = array(
                'label'=>$row['name'],  // label for dropdown list
                'value'=>$row['name'],  // value for input field
                'id'=>$row['id'], // return value from autocomplete
              );
        }
        echo json_encode($res);
        // disable logging
        foreach (Yii::app()->log->routes as $route)
            $route->enabled=false;
        Yii::app()->end();
    }

    /**
     * Удаляется запись из таблицы постпроверок кампании
     *
     * @param type $id ИД правила
     */
    public function actionDeleteHandmakeRule($id)
    {
        $res = Yii::app()->db->createCommand()
            ->delete('company2rules_check', 'rule_id = :rule_id AND company_id = :company_id', array(':rule_id' => $id, ':company_id' => -1));
        $request = $res > 0 ? true : false;

        if ($request === true) {
            Rules::model()->generateSingleListPreCheckAdvertizeSoftwareCache();
        }

        echo json_encode(array('request' => $request));
        // disable logging
        foreach (Yii::app()->log->routes as $route)
            $route->enabled=false;
        Yii::app()->end();
    }

    /**
     * "Ручное" создание правила для ЕСПРС
     */
    public function actionAddHandmakeRule()
    {
        $model = new Rules;

        $response = array();
        if (Yii::app()->request->isAjaxRequest) {
            if (($postData = Yii::app()->request->getPost('Rules', null)) !== null) {
                $postData['mode'] = 'check';
                $model->attributes = $postData;
                if ($model->validate() === true) {
                     if (($model = $model->getUniqueRule($postData)) instanceof Rules) {
                        try {
                            $affected = Yii::app()->db->createCommand()
                                ->insert('company2rules_check', array('rule_id' => $model->id, 'company_id' => -1));
                            Rules::model()->generateSingleListPreCheckAdvertizeSoftwareCache();
                        } catch (Exception $e) {
                            $msg = $e->getMessage();
                            if (strpos($msg, '1062 Duplicate entry')) {
                                $msg = 'Правило уже находится в Едином Списке Предпроверок';
                            }
                            $response['error'] = $msg;
                        }
                    }
                    if (($model instanceof Rules) && !isset($response['error'])) {
                        $response['status'] = "success";
                        $response['rule'] = $model->toArray(array('id', 'type', 'mode', 'path', 'value', 'ruletext'));
                        $response['related_company_list'] = $model->getRelatedCompanyIdList('rules_check');
                    }
                    echo json_encode($response);
                } else {
                    echo CActiveForm::validate($model);
                }


            }

            // disable logging
            foreach (Yii::app()->log->routes as $route)
                $route->enabled=false;
            Yii::app()->end();
        }

    }

    /**
     * Обрабатывает ajax-запрос на получение символа валюты по id пользователя
     */
    public function actionGetCurrencySymbolByUserId()
    {
        echo Yii::app()->locale->getCurrencySymbol(
            Yii::app()->project->getCurrencyByUserId(
                Yii::app()->request->getPost('userId')
            )
        );
    }

    /**
     * Функционал обновления рейтов кампаний на основе внешней статистики рекламодателей
     */
    public function actionRates()
    {
        $model = new CampaignRateForm();
        $renderData = array('model' => $model);
        if (Yii::app()->getRequest()->getIsPostRequest()) {
            if (($file = CUploadedFile::getInstance($model, 'file'))) {
                $model->file = $file;
                if ($model->import()) {
                    Yii::app()->getUser()->setFlash('success', Yii::t('messages', 'Файл успешно импортирован'));
                    $this->refresh();
                }
            }
        } else {
            $model->setAttributes($_GET);
            $renderData['ratesDataProvider'] = $model->getRatesDataProvider();
            $model->validate();
        }

        $this->render('rates', $renderData);
    }

    /**
     * Функционал импорта кампании с матчингом (мэппингом) по странам для обновления рейтов на основе внешней статистики рекламодателей
     */
    public function actionForeignStatMatcher()
    {
        $model = new CampaignRateForm();
        if (Yii::app()->getRequest()->getIsPostRequest()) {
            $model->setAttributes($_POST);

            if ($model->matchCompanies()) {
                Yii::app()->getUser()->setFlash('success', Yii::t('messages', 'Кампания успешно импортирована'));
            } else {
                Yii::app()->getUser()->setFlash('error', Yii::t('messages', 'Ошибка импорта кампании'));
            }
        }

        $this->redirect(array('rates'));
    }

    /**
     * Функционал установки новых рейтов кампаний на основе внешней статистики рекламодателей
     */
    public function actionSetRates()
    {
        $redirectUrl = array('rates');
        $model = new CampaignRateForm();
        if (Yii::app()->getRequest()->getIsPostRequest()) {
            $model->setAttributes($_POST);

            if ($model->setRates()) {
                Yii::app()->getUser()->setFlash('success', Yii::t('messages', 'Новые рейты успешно установлены'));
            } else {
                Yii::app()->getUser()->setFlash('error', Yii::t('messages', 'Ошибка установки новых рейтов'));
            }

            $redirectUrl['advertiser'] = $model->advertiser;
            $redirectUrl['dateStart'] = $model->dateStart;
            $redirectUrl['dateEnd'] = $model->dateEnd;
        }

        $this->redirect($redirectUrl);
    }
}
