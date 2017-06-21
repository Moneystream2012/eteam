<?php
/**
 * @link http://installmonster.ru/
 */

Yii::import('application.components.Logger.ExtendedLogger');
/**
 * Processes internal API
 */
class ApiController extends FastController
{
    public $request;
    public $requestJson;
    public $iid;
    public $sign;
    public $errors;
    public $key = 777;
    public $key2 = 777;
    public $uid;
    public $site_id;
    public $site_id_resell;
    public $user_id;
    public $response = array('error' => '');
    private $allow_form_mode = false;
    private $form_mode = false;
    private $_country;
    private $_log;
    public $secondMenu = array();
    private $noCheckSign = false;
    private $countryFromRequest = false;
    private $siteIdFromRequest = false;
    private $is_silent = false;
    private $silentCompanyOffer = '';
    private $browsers = null;

    /**
     * @access private
     * @var ExtendedLogger Singleton of ExtendedLogger
     */
    private $_extendedLogger;
    
//    public function actionGenSign() {
//        $signs = array();
//
//        if (isset($_POST['data']) && isset($_POST['key'])) {
//            $ts_today = time();
//            foreach (array($ts_today, $ts_today - 86400, $ts_today + 86400,) as $ts) {
//                $salt = self::gen_salt($ts, $_POST['key']);
//                $sign = md5($_POST['data'] . $salt);
//                $signs[date('Y-m-d', $ts)] = array(
//                    'salt' => $salt,
//                    'sign' => $sign,
//                );
//            }
//        }
//
//        $this->render('gensign', array(
//            'signs' => $signs,
//        ));
//    }

    /**
     * List correspondences lowcase processes/file names with browser names
     *
     * @var array
     */
    protected static $knownBrowsers = Array(
        'opera.exe' => 'Opera',
        'launcher.exe' => 'Opera',
        'firefox.exe' => 'FireFox',
        'chrome.exe' => 'Chrome',
        'iexplore.exe' => 'MSIE',
        'safari.exe' => 'Safari',
        'avant.exe' => 'Avant',
        'amaya.exe' => 'Amaya',
        'arora.exe' => 'Arora',
        'leechcraft.exe' => 'Leechcraft',
        'links-g.exe' => 'Links-g',
        'luna.exe' => 'Lunascape',
        'lynx.exe' => 'Lynx',
        'k-meleon.exe' => 'K-Meleon',
        'konqueror.exe' => 'Konqueror',
        'mosaic.exe' => 'Mosaic',
        'maxthon.exe' => 'Maxthon',
        'midori.exe' => 'Midori',
        'mozilla.exe' => 'Mozilla',
        'netscape.exe' => 'Netscape',
        'rockmelt.exe' => 'RockMelt',
        'seamonkey.exe' => 'SeaMonkey',
        'srware.exe' => 'SRWare',
        'browser.exe' => 'YaBrowser',
        'amigo.exe' => 'Amigo',
        'microsoftedge.exe' => 'Edge',
    );


    /**
     * Log of request params
     *
     * @var object ApiRequestLog
     */
    private $requestLog;

    /**
     * Constructor
     *
     * @param string $id
     * @param null $module
     */
    public function __construct($id, $module=null)
    {
        parent::__construct($id, $module);
        $this->_extendedLogger = ExtendedLogger::getInstance(Yii::app()->getRuntimePath(), 'installer_components.log');
    }

    /**
     * Подключение лога, проверка на бан, определение режима тестирования
     */
    public function init()
    {
        parent::init();
        $this->allow_form_mode = YII_DEBUG;
        $this->requestLog = new ApiRequestLog;

        if (
            Yii::app()->db->createCommand('SELECT COUNT(*) FROM `ip_date_ban` WHERE  `date` = CURDATE() AND ip = INET_ATON(:ip)')
                ->queryScalar(array('ip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : ''))
        ) {
            Yii::app()->end();
        }
    }

    /**
     * Simplified algorithm for special installer
     *
     * @param string $method
     * @param array $params
     *
     * @return bool
     */
    private function specialInstaller($method, $params = array())
    {
        if ($special = SpecialInstaller::model()->findByPk($this->iid)) {
            Yii::log('Found special installer');
            if (strpos($special->respose, '{@country}') !== false) {
                $special->respose = str_replace('{@country}', strtolower($this->getCountryId()), $special->respose);
            }
            Yii::log($special->respose);

            $sign = md5($special->respose . self::gen_salt(time(), $this->key));

            $this->requestLog->response = $special->respose;
            $this->requestLog->save();

            echo self::data_compress($this->iid . ':' . $sign . ':' . $special->respose);
            Yii::log('<<');
            Postback::model()->sendPostbacks($method, $this->site_id, $params);
            return true;
        } else {
            return false;
        }
    }

    public function actionIndex() {
        Yii::beginProfile('InstallerApi');
        Yii::log('>>');
        Yii::log('###actionIndex');
        if ($this->parseRequest()) {
            
            $method = $this->getMethod();

            $postbackParams = array(
                'userIp' => $_SERVER['REMOTE_ADDR'],
                'campaignId' => isset($this->request['cid']) ? $this->request['cid'] : null,
                'subId' => ResellSiteMatcher::model()->getSubId($this->site_id_resell, $this->site_id),
                'subId2' => IidSubId2Matcher::model()->getSubId2($this->iid),
            );

            Yii::log('###Method:'.$method);
            Yii::log('###IID:'.$this->iid);
            switch ($method) {
                case IApiMethod::PREINIT:
                    Yii::beginProfile('InstallerApi.PreInit');
                    Yii::log('Processing PreInit');

                    if ($this->specialInstaller($method, $postbackParams)) {
                        Yii::endProfile('InstallerApi.PreInit');
                        return;
                    }

                    //сохраняем время первого преинита, мониторим ав
                    if ($this->savePreInitTs()) {
                        $this->applicationAvLog();
                    }

                    $this->_log = new PreinitLog();
                    $this->_log->iid = $this->iid;
//                    $this->_log->request = json_encode($this->request);
                    $this->_log->ip = $_SERVER['REMOTE_ADDR'];
                    $this->_log->country = $this->getCountryId();
                    $this->_log->ukey = Api::generateUkey();
                    $this->_log->site_id = $this->site_id;

                    $browser = $this->getBrowser();

                    $commonData = new CommonLogData();
                    $commonData->ukey = $this->_log->ukey;
                    $commonData->country_id = $this->_log->country === '-' ? '' : $this->_log->country;
                    $commonData->browser_id = $browser === '-' ? -1 : Browser::getIdByName($browser);
                    $commonData->os_id = $this->getWindowsVersion();
                    $commonData->save();

                    if ($this->generateUid()) {
                        $this->_log->uid = $this->uid;
                        // отдаются только те офферы, которые требуют проверку
                        // т.е. те, у которых has_api_check = true
                        // и все офферы из категории "Рекламный Софт", т.к. к этим
                        // офферам добавляется в обязательном порядке все правила 
                        // из "Единого Списка Предпроверок Реклавного Софта"
                        $this->getPreinstallChecksCache();
                        Yii::log('PreinstallChecks generated');
                    }
                    
                    $this->processShortcutList();
                    
                    Yii::endProfile('InstallerApi.PreInit');
                    Postback::model()->sendPostbacks($method, $this->site_id, $postbackParams);
                    break;
                case IApiMethod::INIT:
                    Yii::beginProfile('InstallerApi.Init');
                    Yii::log('Processing init');

                    if ($this->specialInstaller($method, $postbackParams)) {
                        Yii::endProfile('InstallerApi.Init');
                        return;
                    }
                    $this->_log = new InitLog();
                    $this->_log->iid = $this->iid;
//                    $this->_log->request = json_encode($this->request);
                    $this->_log->ip = $_SERVER['REMOTE_ADDR'];
                    $this->_log->country = $this->getCountryId();
                    $this->_log->ukey = $this->request['ukey'];
                    $this->_log->site_id = $this->site_id;
//                    $this->_log->save();

                    if ($this->generateUid()) {
                        $this->_log->uid = $this->uid;

                        if ($this->checkBot()) {
                            $this->response['offers'] = array();
                        } else {
                            $this->getOffersCache();
                        }

                        if (!$this->is_silent) {
                            $this->getBanner();
                        }
                        $this->_log->browser = $this->getBrowser();
                        $this->_log->os = $this->getWindowsVersion();
//Yii::log('Offers generated');
                        $this->_goodmailjobPostBack();
                    }
                    Yii::endProfile('InstallerApi.Init');
                    Postback::model()->sendPostbacks($method, $this->site_id, $postbackParams);
                    break;
                case IApiMethod::START:
                    Yii::beginProfile('InstallerApi.Start');
                    Yii::log('Processing start');

                    if ($this->specialInstaller($method, $postbackParams)) {
                        $this->_loadmoneyPostBack();
                        Yii::endProfile('InstallerApi.Start');
                        return;
                    }

                    $this->insertStart();

                    Yii::endProfile('InstallerApi.Start');
                    Postback::model()->sendPostbacks($method, $this->site_id, $postbackParams);
                    break;
                case IApiMethod::DONE:
                    Yii::beginProfile('InstallerApi.Done');
                    Yii::log('Processing done');

                    if ($this->specialInstaller($method, $postbackParams)) {
                        Yii::endProfile('InstallerApi.Done');
                        return;
                    }

                    $this->insertDone();

                    Yii::endProfile('InstallerApi.Done');
                    $postbackParams['ukey'] = $this->request['ukey'];
                    Postback::model()->sendPostbacks($method, $this->site_id, $postbackParams);
                    break;
                case IApiMethod::BANNER_SHOW:
                    /*
                      method banerShow
                      bannerId: 1234
                      uid: jksdfg,kjbgajsf,ksajdf
                     *
                     * {"v":"1","iv":"2","m":"banerShow","uid":"0791d92bf46ab0de748521ada37e6e5f","bannerId":"451"}
                     */

                    if (empty($this->request['uid'])) {
                        $this->generateUid();
                        $this->request['uid'] = $this->uid;
                    }

                    if (!empty($this->request['bannerId'])) {
                        Yii::app()->db->createCommand('INSERT INTO banner_show_log (uid,ukey,banner_id,country, site_id,iid) VALUES(:uid,:ukey,:banner_id,:country, :site_id, :iid)')->execute(array(
                            'uid' => $this->request['uid'],
                            'ukey' => $this->request['ukey'],
                            'banner_id' => intval($this->request['bannerId']),
                            'country' => $this->getCountryId(),
                            'site_id' => $this->site_id,
                            'iid' => $this->iid,
                        ));
                    }
                    Postback::model()->sendPostbacks($method, $this->site_id, $postbackParams);
                    break;
                case 'p':
                case 'i':
                case 'e':
                case 'n':

//                    Yii::app()->db->createCommand('
//                    INSERT INTO `aol_test_log`(`uid`, `iid`, `keyword`, `ip`)
//                    VALUES (:uid, :iid, :keyword, INET_ATON(:ip))
//                        ')->execute(array(
//                        'uid' => $this->request['uid'],
//                        'iid' => $this->iid,
//                        'keyword' => $this->request['m'],
//                        'ip' => $_SERVER['REMOTE_ADDR'],
//                    ));

                    break;
                case IApiMethod::CHECK:
                    Yii::beginProfile('InstallerApi.Check');
                    Yii::log('Processing check');
                    if (!empty($this->request['uid'])) {
                        $uid = $this->request['uid'];
                        
                        if(isset($this->request['check_result']) && is_array($this->request['check_result'])){
                            
                            $sqlStr = '';
                            $comma = '';
                            $i = 0;
                            $params = array();
                            while (list($key, $value) = each($this->request['check_result'])){
                                
                                $value = intval($value, 10); //Значение должно быть 0/1
                                
                                if( ($delimiterPos = strpos($key, '_') ) == false ){ //если false или 0
                                    $this->errors[] = 'Check. insufficient data to process request. Has no delimiter...';
                                    continue;
                                }
                                if(($companyId = intval(substr($key, 0, $delimiterPos), 10)) === 0){
                                    $this->errors[] = 'Check. wrong ruleId';
                                    continue;
                                }
                                if(($ruleId = intval(substr($key, ($delimiterPos+1), strlen($key)), 10)) === 0){
                                    $this->errors[] = 'Check. wrong companyId';
                                    continue;
                                }
                                
                                //$sqlStr .= $comma.'("'.$uid.'", "'.$companyId.'", "'.$ruleId.'", "'.$value.'")';
                                $sqlStr .= $comma.'(:ts_partition_'.$i.', :ts_'.$i.', :uid_'.$i.', :companyId_'.$i.', :ruleId_'.$i.', :value_'.$i.')';

                                $params['ts_partition_'.$i] = $params['ts_'.$i] = date('Y-m-d H:i:s');
                                $params['uid_'.$i]          = $uid;
                                $params['companyId_'.$i]    = $companyId;
                                $params['ruleId_'.$i]       = $ruleId;
                                $params['value_'.$i]        = $value;

                                $comma = ',';
                                $i++;
                            }
                            
                            $sql = 'INSERT INTO `check_rules_log` (`ts_partition`, `ts`, `uid`, `company_id`, `rule_id`, `hit`) VALUES ' . $sqlStr ;
                            #Yii::log('sql - '.$sql);

                            if ($sqlStr) {
                                try {
                                    Yii::app()->db->createCommand($sql)->execute($params);
                                } catch (Exception $ex) {
                                    $this->errors[] = 'Check. Exception Err. Code: '.$ex->getCode();
                                }
                            }
                            
                        }
                    }else{
                        $this->errors[] = 'Check. insufficient data to process request';
                    }
                    
                    Yii::endProfile('InstallerApi.Check');
                    Postback::model()->sendPostbacks($method, $this->site_id, $postbackParams);
                    break;
                default:

                    if (empty($this->request['uid'])) {
                        $this->generateUid();
                        $this->request['uid'] = $this->uid;
                    }

                    if (!empty($this->request['m'])) {
//                        Yii::app()->db->createCommand('
//                    INSERT INTO `unknown_method_log`(`uid`, `iid`, `keyword`, `ip`, `company_id`)
//                    VALUES (:uid, :iid, :keyword, INET_ATON(:ip), :company_id)
//                        ')->execute(array(
//                            'uid' => $this->request['uid'],
//                            'iid' => $this->iid,
//                            'keyword' => $this->request['m'],
//                            'ip' => $_SERVER['REMOTE_ADDR'],
//                            'company_id' => empty($this->request['cid']) ? 0 : intval($this->request['cid'])
//                        ));
                    }
                    break;
            }
        }
        $this->writeResponse();
        Yii::log('<<');
        Yii::endProfile('InstallerApi');
    }
    
    /**
     * Check user applications and if establish correspondence with check list
     * applications_monitor_list(id, application, is_archived = 0)
     * save it to applications_monitor_log(ts, iid, application),
     */
    public function applicationAvLog() 
    {
        $currentDownload = Yii::app()->db->createCommand('SELECT ts FROM downloads_log WHERE id = :id AND ts >= NOW() - INTERVAL 30 MINUTE')->queryScalar(array(
            ':id' => $this->iid,
        )); 
        if (!empty($this->request['system']['applications']) && !empty($currentDownload)) {
            $params_arr = array();
            $date = date('Y-m-d H:i:s');
            $monitoring_arr = ApplicationsMonitorList::getApplicationsArrayByType();
            if (is_array($this->request['system']['applications'])) {
                foreach ($this->request['system']['applications'] as $app) {
                    if ($app_id = array_search($app, $monitoring_arr)) {
                        $params_arr[] = '("' . $date . '", ' . $this->iid . ', ' . $app_id . ')';
                    }
                }
            }
            if (count($params_arr)) {
                $params = implode(',', $params_arr);
                Yii::app()->db->createCommand('INSERT INTO applications_monitor_log (`ts`, `iid`, `application_id`) VALUES '.$params)->execute();
            }
        }
    }

    /**
     * Compare user software list with bot criteria. If exactly matched, set user type to bot.
     *
     * @return boolean
     */
    public function checkBot()
    {
        $applicationCount = 0;
        if (!empty($this->request['system']['applications']) && is_array($this->request['system']['applications'])) {
            $applicationCount = count($this->request['system']['applications']);
        }

        if ($applicationCount == 0) {
            return true;
        }

        foreach ($this->getBotApplicationSets() as $set) {
            if ($applicationCount == count($set)) {
                $applications = $this->request['system']['applications'];
                sort($applications);
                if ($applications == $set) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Возвращает наборы ботского ПО для неотдачи офферов
     *
     * @return array
     */
    public function getBotApplicationSets()
    {
        $sets = Yii::app()->cache->get('botApplicationSets');
        if ($sets === false) {
            $botApplications = Yii::app()->db->createCommand()
                ->select('sw_set')
                ->from('{{bot_application_set}}')
                ->where('is_deleted = :notDeleted', array(':notDeleted' => 0))
            ->queryColumn();

            $sets = array();
            foreach ($botApplications as $k => $setString) {
                $sets[$k] = explode(';', $setString);
                $sets[$k] = array_diff($sets[$k], array(''));
                sort($sets[$k]);
            }
            Yii::app()->cache->set('botApplicationSets', $sets, 60 * 60);
        }
        return $sets;
    }

    public function actionZip() {
//        if ($_SERVER['REMOTE_ADDR'] == 'Славын айпишник'){
        $this->noCheckSign = true;
        $this->countryFromRequest = true;
        $this->siteIdFromRequest = true;

        if ($this->parseRequestZip()) {
            switch ($this->getMethod()) {
                case IApiMethod::PREINIT:
                    Yii::beginProfile('InstallerApi.PreInit');
                    Yii::log('Processing PreInit');

                    if ($this->generateUid()) {
                        $this->getPreinstallChecksCache();
                        Yii::log('PreinstallChecks generated');
                    }
                    Yii::endProfile('InstallerApi.PreInit');
                    break;
                case IApiMethod::INIT:
                    Yii::beginProfile('InstallerApi.Init');
                    Yii::log('Processing init');


//                    $this->_log->save();

                    if ($this->generateUid()) {
                        $this->getOffersCache(true);
                    }
                    Yii::endProfile('InstallerApi.Init');
                    break;
            }
//        }
        }
        // write response

        if (!empty($this->errors)) {
            $this->response['error'] = implode('; ', $this->errors);
//Yii::log($this->response['error']);
            $this->requestLog->error = $this->response['error'];
        } else {
            $this->requestLog->hasError = 0;
        }

        $res_str = json_encode($this->response);

        $this->requestLog->response = $res_str;
        $this->requestLog->save();

        if (!empty($this->_log) && !empty($this->response['offers'])) {
//            $this->_log->response = $res_str;
            $this->_log->save();
            foreach ($this->response['offers'] as $keyOffer => $offer) {
                foreach($offer['params'] as $keyParam => $param) {
                    if (strpos($param, '{@country}') !== false) {
                        $param = str_replace('{@country}', strtolower($this->getCountryId()), $param);
                        $this->response['offers'][$keyOffer]['params'][$keyParam] = $param;
                    }
                }
            }
        }

//        echo $res_str;die;
        Yii::log($res_str);
//какоето говно вместо подписи.. пусть реверсеры поебутся
        $sign = md5($res_str . time() . rand());

        if ($this->allow_form_mode && $this->form_mode) {
//            echo 111;
            $this->render('form', array(
                'response' => $this->response,
                'iid' => $this->iid,
                'sign' => $sign,
                'string' => $res_str,
            ));
        } else {
            echo $res_str;
        }
    }
    
    
    /**
     * 
     * На преинит в request['system'] приходит массив shortcuts.
     * Для каждой строки, если она не пустая (""), выдираем список содержащихся в ней URLов (их может быть 0+ штук).
     */
    private function processShortcutList()
    {
        if (!empty($this->request['system']['shortcuts']) && is_array($this->request['system']['shortcuts']) ) {
            
            $pattern = '/(\/\/|https?:\/\/)([^"\s]+)/i';
            while (list($i, $shortcut) = each($this->request['system']['shortcuts'])) {
                
                if (preg_match_all($pattern, $shortcut, $matches, PREG_SET_ORDER)) {
                    while (list($j, $match) = each($matches)) {
                        
                        $url = $match[0];
                        //По найденным урлам пытаемся найти ID соответствующей кампании (пока просто сверяем с company.url, потом, возможно, будет отдельная таблица с историей урлов кампании).
                        $sql = 'SELECT `id` FROM `company` WHERE `url` LIKE :url';
                        $companyId = intval(Yii::app()->db->createCommand($sql)->queryScalar(array(':url' => '%' . addcslashes($url, '%_') . '%')));

                        //Для каждого выдранного урла вставляем в shortcuts_log запись. iid, uid, ukey как обычно, shortcut_full - полная строка, shortcut_url - выдранный урл, found_company_id - найденный id кампании, или 0.
                        $sql = 'INSERT INTO `shortcuts_log` (`ukey`, `iid`, `shortcut_full`, `shortcut_url`, `found_company_id`) VALUES (:ukey, :iid, :shortcut_full, :shortcut_url, :found_company_id)';
                        $params                         = array();
                        $params[':ukey']                = $this->_log->ukey;    //on preinit, there is no $request['ukey'], only new generated value in PreinitLog.
                        $params[':iid']                 = $this->iid;
                        $params[':shortcut_full']       = $shortcut;
                        $params[':shortcut_url']        = $url;
                        $params[':found_company_id']    = $companyId;

                        Yii::app()->db->createCommand($sql)->execute($params);
                    }
                }
            }
        }
    }

    private function insertDone() {
        if (!empty($this->request['uid']) && !empty($this->request['cid'])) {
            $this->requestLog->uid = $this->request['uid'];
//Company not in archive
            $ca = Yii::app()->db->createCommand('SELECT id, statistics_mode FROM company WHERE id = ' . intval($this->request['cid']) . ' AND is_archived = 0')->queryRow();
            if ($ca) {
                $model = new DoneLog;
                $model->iid = $this->iid;
                $model->ukey = $this->request['ukey'];
                $model->company_id = $this->request['cid'];
                $model->uid = $this->request['uid'];
                $model->option = empty($this->request['opt']) ? '' : $this->request['opt'];
                $model->ip = $_SERVER['REMOTE_ADDR'];
                $model->country = $this->getCountryId();
                $model->site_id = $this->site_id;
                if (!$model->save()) {
                    $this->errors = $this->errors + $model->errors;
                }
            }

            // Installs postbacks
            if ($ca['statistics_mode'] == 0) { // Company on internal statisitcs
                $site_id = $this->getSiteId();
                if ($site_id == 5506) { //installs.pro
                    $sub_id2 = Yii::app()->db->createCommand('SELECT sub_id2 FROM iid_sub_id2_matcher WHERE iid = ' . $this->iid)
                        ->queryScalar();
                    $this->post_with_curl('http://api.installs.pro/API/postback.php?cid=' . $this->request['cid'] . '&site_id=' . $site_id . '&sub_id2=' . $sub_id2 . '&uid=' . $this->request['uid']);
                }
                if (
                    $site_id == 5545
                    && isset($this->request['installcube_exec_id'])
                    && !empty($this->request['installcube_exec_id'])
                ) { //installcube (Саша обещал, что это временное решение. И мы от таких костылей откажемся.)
                    $url = str_replace(
                            array('{@api_key}', '{@data_exec_id}', '{@data_install_result}'), 
                            array(Settings::get('cubbbe_api_key'), urlencode($this->request['installcube_exec_id']), 'true'), 
                            Settings::get('cubbbe_api_url'));
                    Yii::app()->curl->post($url, array());
                }
            }
        } else {
            $this->errors[] = 'insufficient data to process request';
        }
    }

    private function insertStart() {
        if (!empty($this->request['uid']) && !empty($this->request['cid'])) {
            $this->requestLog->uid = $this->request['uid'];
//Company not in archive
            $ca = Yii::app()->db->createCommand('SELECT id FROM company WHERE id = ' . intval($this->request['cid']) . ' AND is_archived = 0')->queryScalar();
            if ($ca) {
                $model = new StartLog;
                $model->iid = $this->iid;
                $model->ukey = $this->request['ukey'];
                $model->company_id = $this->request['cid'];
                $model->uid = $this->request['uid'];
                $model->option = empty($this->request['opt']) ? '' : $this->request['opt'];
                $model->ip = $_SERVER['REMOTE_ADDR'];
                $model->country = $this->getCountryId();
                $model->site_id = $this->site_id;
                if (!$model->save()) {
                    $this->errors = $this->errors + $model->errors;
                }
                $this->_loadmoneyPostBack();
            }
        } else {
            $this->errors[] = 'insufficient data to process request';
        }
    }

    private function _loadmoneyPostBack() {
        if (in_array($this->request['cid'], explode(',', Settings::get('mailru_campaign_ids')))) { // LoadMoney campaigns
            Yii::beginProfile('InstallerApi.Start.loadmoneyPostBack');
            $download = DownloadsLog::model()->findByAttributes(array('id' => $this->iid));

            if ($download) {
                $partner = new BaseLoadMoneyPartner;

                $url = 'http://' . $partner->getDomain() . '/get_vendor_file';
                $data = array(
                    'partner_id' => $partner->getPartnerId(), // идентификатор InstallMonster в LoadMoney ( 7718 )
                    'ua' => @$download->user_agent, // юзер//агент пользователя, скачавшего загрузчик
                    'ip' => @$download->ip, // IP пользователя, скачавшего загрузчик
//                    'referrer', // referrer пользователя, скачавшего загрузчик
                    'url' => @$download->url, // url файла, за которым пришел пользователь
                    'site_id' => $this->site_id, // идентификатор сайта в InstallMonster
                    'download_id' => $this->iid, // идентификатор скачки в InstallMonster
                    'vendor_site_id' => $partner->getSiteId(), // идентификатор сайта в LoadMoney
                );

                Yii::log('LoadMoney postback to ' . $url . ' with ' . serialize($data));
                Yii::app()->curl->post($url, $data);

                $url = 'http://' . $partner->getDomain() . '/get_download_xml_3';
                $data = array(
                    'partner_id' => $partner->getPartnerId(), // идентификатор InstallMonster в LoadMoney ( 7718 )
                    'ip' => $_SERVER['REMOTE_ADDR'], // IP пользователя, запустившего загрузчик InstallMonster
                    'site_id' => $this->site_id, // идентификатор сайта в InstallMonster
                    'download_id' => $this->iid, // идентификатор скачки в InstallMonster, которая
                    'vendor_site_id' => $partner->getSiteId(), // идентификатор сайта в LoadMoney
                );

                Yii::log('LoadMoney postback to ' . $url . ' with ' . serialize($data));
                Yii::app()->curl->post($url, $data);
            }
            Yii::endProfile('InstallerApi.Start.loadmoneyPostBack');
        }
    }

    private function _goodmailjobPostBack() {
        if ($this->user_id == 4589) { // goodmailjob@gmail.com
            Yii::beginProfile('InstallerApi.Init.goodmailjobPostBack');

            $sub_id2 = Yii::app()->db->createCommand('SELECT sub_id2 FROM iid_sub_id2_matcher WHERE iid = ' . $this->iid)
                    ->queryScalar();

            //fix postback - only inits with offers
            if ($sub_id2 && !empty($this->response['offers'])) {
                $this->post_with_curl('http://api.tds-admin.com/api_install.php?subid=' . intval($sub_id2) . '&uid=' . $this->uid);
            }

            Yii::endProfile('InstallerApi.Init.goodmailjobPostBack');
        }
    }

    /**
     * Возвращает массив доменов рэклэкзе по одному каждого типа [DIRT_LIGHT = 1, DIRT_MEDIUM = 2, DIRT_HARD = 3]
     * 
     * @return array
     */
    private function _cachedAdvertizeDomainQuery()
    {
        $advertizeDomainTypeList = Yii::app()->db
            ->createCommand('SELECT DISTINCT `type` FROM `domain_advertize_dirt`')
            ->queryColumn();
        
        $params                 = array();
        $params[':active']      = 1;
        $params[':disabled']    = 0;
        $params[':processed']    = UserDomain::IM_PROCESSED_BIT;

        $list = array();
        while (list($i, $typeId) = each($advertizeDomainTypeList)) {
            $sql = 'SELECT `type`, `domain`
                    FROM `domain_advertize_dirt` 
                    WHERE `active` = :active AND `disabled` = :disabled AND (`processed` & :processed=:processed) AND `type` = :type
                    ORDER BY `priority` ASC, `id` ASC
                    LIMIT 1';
            $params[':type']     = $typeId;
            $res = Yii::app()->db->createCommand($sql)->queryRow(true, $params);
            if (is_array($res) && count($res) > 0) {
                $list[$res['type']] = $res['domain'];
            }
        }
        return $list;
    }

    /**
     * Проверяет, не является ли инсталлер сайлентом по iid, если да, подгружает список кампаний в silentCompanyOffer.
     *
     * @param $mode "метка" для профилирования
     * @return bool
     * @throws CHttpException
     */
    private function isSilentInstaller($mode)
    {
        Yii::beginProfile('InstallerApi.'.$mode.'.OffersSQL.checkSilent');

        $silent = Yii::app()->db->createCommand(
            'SELECT * FROM resell_company_offer WHERE iid = :iid'
        )->queryRow(true, array(':iid' => $this->iid));

        if ($silent) {
            $this->is_silent = true;
            if (strtotime($silent['ts'] < (time() - 3600))) {
                throw new CHttpException(408, 'Request Timeout');
            }
            $this->silentCompanyOffer = $silent['company_offer'];
        }

        Yii::endProfile('InstallerApi.'.$mode.'.OffersSQL.checkSilent');

        return $this->is_silent;
    }

    /**
     * Генерирует список офферов по параметрам запроса, и помещает его в response['offers']
     *
     * @param bool $resell - флаг "реселла", используется в actionZip для реселла по схеме зипмонстра
     * @return void
     */
    private function getOffersCache($resell = false)
    {
        $this->_extendedLogger->insert('getOffersCache >>>');
        
        $this->isSilentInstaller('Init');

        // получаем домены раздачи реклаэкзе по одному каждого типа array([(int) typeId] => (string) domain)
        $advDomainList = $this->_cachedAdvertizeDomainQuery();
        #Yii::log('### $advDomainList: ' . var_export($advDomainList, true));

        $this->response['offers'] = (new Offers(array(
                                        'mode' => 'Init',
                                        'iid' => $this->iid,
                                        'uid' => $this->uid,
                                        'silent' => $this->is_silent,
                                        'silent_company_offer' => $this->silentCompanyOffer,
                                        'request' => $this->request,
                                        'resell' => $resell,
                                        'resell_no_offerscreen' => $resell,
                                        'resell_zip' => $resell,
                                        'site_id' => $this->getSiteId(),
                                        'country_id' => $this->getCountryId(),
                                        'language' => $this->getLanguage(),
                                        'browser' => $this->getBrowser(),
                                        'time_id' => $this->getTimeId(),
                                        'windows_version' => $this->getWindowsVersion(),
                                        'ip' => $this->getIp(),
                                        'only_campaign_ids' => false,
                                        'excludedOffers' => $this->getExcludedOffers(),

                                        'adv_domain_list' => $advDomainList,
                                    )))
                ->getOffers();
    }

    /**
     * Генерирует список офферов с предпроверками для преинита, помещает его в response['offers']
     */
    private function getPreinstallChecksCache() {

        $mode = 'PreInit';
        $resell = false;

        //$cacheKey = $this->iid . $this->uid;
        //$this->is_silent = Yii::app()->cache->get($cacheKey . 'is_silent');

        $this->isSilentInstaller($mode);

        //Yii::app()->cache->add($cacheKey . 'is_silent', $this->is_silent, 100);

        $this->response['offers'] = (new Offers(array(
                                        'mode' => $mode,
                                        'iid' => $this->iid,
                                        'uid' => $this->uid,
                                        'silent' => $this->is_silent,
                                        'silent_company_offer' => $this->silentCompanyOffer,
                                        'request' => $this->request,
                                        'resell' => $resell,
                                        'resell_no_offerscreen' => $resell,
                                        'resell_zip' => $resell,
                                        'site_id' => $this->getSiteId(),
                                        'country_id' => $this->getCountryId(),
                                        'language' => $this->getLanguage(),
                                        'browser' => $this->getBrowser(),
                                        'time_id' => $this->getTimeId(),
                                        'windows_version' => $this->getWindowsVersion(),
                                        'ip' => $this->getIp(),
                                        'only_campaign_ids' => false,
                                    )))
                ->getOffersRulesList();
    }

    private function getIp() {
        $ip = @trim($_SERVER['REMOTE_ADDR']);
        return empty($ip) ? '0.0.0.0' : $ip;
    }

    private function getCountryId() {
        if (!empty($this->_country))
            return $this->_country;
        $ret = '-';
        if (!$this->countryFromRequest) {
            if (APPLICATION_ENV == ENV_PROD) {
                $ret = @geoip_country_code_by_name($_SERVER['REMOTE_ADDR']);
            } elseif (APPLICATION_ENV == ENV_LOCAL) {
                $ret = 'RU';
            }
        } else {
            if (isset($this->request['country'])) {
                $ret = $this->request['country'];
            }
        }
//Yii::log('Detected country: ' . $ret);
        $this->_country = $ret;
        return $ret;
    }

    private function getTimeId() {
        if (!empty($this->request['system']['time'])) {
            $this->requestLog->time = $this->request['system']['time'];
        }
        $ret = intval(date('H'));
//Yii::log('Time interval: ' . $ret);
        return $ret;
    }

    private function getSiteId() {
        $ret = '-';
        if (!$this->siteIdFromRequest) {
            if (!empty($this->site_id)) {
                $ret = $this->site_id;
            }
        } else {
            if (!empty($this->request['site-id'])) {
                $ret = intval($this->request['site-id']);
            }
        }
//Yii::log('Site id: ' . $ret);
        return $ret;
    }

    private function getLanguage() {
        $ret = '-';
        if (!empty($this->request['system']['langid'])) {
            $this->requestLog->langid = $this->request['system']['langid'];
            $ret = substr($this->request['system']['langid'], 0, 2);
        }
//Yii::log('Language: ' . $ret);
        return $ret;
    }

    /**
     * Определяем браузер всеми доступными способами
     * Процессы -> чем качали -> хэндлер хттп -> хэндлер http сами -> "браузер" из загрузчика -> пусто
     *
     * @return string
     */
    protected function getBrowser() {

        $ret = '-';

        //detect all browsers in processes list:
        if (
            (count($proc_browsers = $this->detectBrowsers()) === 1)
            && $this->isValidTargetingBrowser($proc_browsers[0])
           )
        {
            $browser = $proc_browsers[0];
        }
        elseif ($browser = $this->downloadBrowserToTargetingBrowser(@$this->originalFileData['browser']))
        {
            /*
            $http_handler = !empty($this->request['system']['httphandler']) ? $this->request['system']['httphandler'] : '-';
            if ($browser != $http_handler)
            {
                //ситуация, которую мы пока не знаем как обрабатывать, поэтому считаем
                //что лучше просто взять браузер из "скачивания". Вероятность ошибки - около 10%
                //при этом самих подобных случаев - в районе 20% (итого, 2% ошибки).
            }
            */
        }
        //ну, если мы и про скачивание ничего не знаем, или там реселл/невалидный браузер...
        elseif (
            !empty($this->request['system']['httphandler'])
            && $this->isValidTargetingBrowser($this->request['system']['httphandler'])
            )
        {
            $browser = $this->request['system']['httphandler'];
        }
        //загрузчик не смог? Ну, попробуем сами:
        elseif ($browser = $this->browserFromFullHttpHandler())
        {
            //ничего не надо делать, да.
        }
        else {
            //остаётся только одно!
            $browser = '-';
            if (
                !empty($this->request['system']['browser'])
                && $this->isValidTargetingBrowser($this->request['system']['browser'])
               )
            {
                $browser = $this->request['system']['browser'];
            }
        }

        $this->requestLog->browser = $browser;

        //выше уже все варианты были проверены на валидность, так что это нам теперь не надо
        #if ($this->isValidTargetingBrowser($browser))
            $ret = $browser;

        return $ret;
    }

    /**
     * Проверка, входит ли браузер в список "доступных" для таргетинга
     */
    private function isValidTargetingBrowser($browser)
    {
        $browsers = Yii::app()->cache->get(__CLASS__ . __FUNCTION__);
        if (!$browsers)
        {
            $browsers = Yii::app()->db->createCommand('select `name` from browser')->queryColumn();
            Yii::app()->cache->set(__CLASS__ . __FUNCTION__, $browsers, 3600);
        }

        return in_array($browser, $browsers);
    }

    /**
     * Получения версии ОС дя таргетинга по информации из запроса
     *
     * @return int
     */
    private function getWindowsVersion()
    {
        $ret = -1;
        /*
          1: Windows 8 x64  : ver = 6.2, type = 1, x64 = true
          2: Windows 8   : ver = 6.2, type = 1, x64 = false
          3: Windows 7 x64  : ver = 6.1, type = 1, x64 = true
          4: Windows 7   : ver = 6.1, type = 1, x64 = false
          5: Windows Vista x64 : ver = 6.0, type = 1, x64 = true
          6: Windows Vista  : ver = 6.0, type = 1, x64 = false
          7: Windows XP sp 3  : ver = 5.1, sp = 3.0
          8: Windows XP sp 2  : ver = 5.1, sp = 2.0
         */

        $this->requestLog->os_version = @$this->request['system']['os']['version'];
        $this->requestLog->os_build = @$this->request['system']['os']['build'];
        $this->requestLog->os_sp = @$this->request['system']['os']['sp'];
        $this->requestLog->os_x64 = @$this->request['system']['os']['x64'] ? 1 : 0;
        $this->requestLog->os_type = @$this->request['system']['os']['type'];
        $this->requestLog->os_r2 = @$this->request['system']['os']['r2'] ? 1 : 0;
        $this->requestLog->os_suite = @$this->request['system']['os']['suite'];
        $this->requestLog->os_arch = @$this->request['system']['os']['arch'];

        if (
            isset($this->request['system']['os']['version']) &&
            isset($this->request['system']['os']['type']) &&
            isset($this->request['system']['os']['x64']) &&
            isset($this->request['system']['os']['sp'])
            ) {

            if ($this->request['system']['os']['version'] == '10.0' || $this->request['system']['os']['version'] == '6.4') {
                if ($this->request['system']['os']['x64']) {
                    $ret = IWindowsVersion::WIN10_0_64;
                } else {
                    $ret = IWindowsVersion::WIN10_0_32;
                }
            } elseif ($this->request['system']['os']['version'] == '6.3') {
                if ($this->request['system']['os']['x64']) {
                    $ret = IWindowsVersion::WIN8_1_64;
                } else {
                    $ret = IWindowsVersion::WIN8_1_32;
                }
            } elseif ($this->request['system']['os']['version'] == '6.2') {
                if ($this->request['system']['os']['x64']) {
                    $ret = IWindowsVersion::WIN8_64;
                } else {
                    $ret = IWindowsVersion::WIN8_32;
                }
            } elseif ($this->request['system']['os']['version'] == '6.1') {
                if ($this->request['system']['os']['x64']) {
                    $ret = IWindowsVersion::WIN7_64;
                } else {
                    $ret = IWindowsVersion::WIN7_32;
                }
            } elseif ($this->request['system']['os']['version'] == '6.0') {
                if ($this->request['system']['os']['x64']) {
                    $ret = IWindowsVersion::WINVISTA_64;
                } else {
                    $ret = IWindowsVersion::WINVISTA_32;
                }
            } elseif ($this->request['system']['os']['version'] == '5.1') {
                if ($this->request['system']['os']['sp'] == '3.0') {
                    $ret = IWindowsVersion::WINXP_SP3;
                } elseif ($this->request['system']['os']['sp'] == '2.0') {
                    $ret = IWindowsVersion::WINXP_SP2;
                }
            }
        }

//Yii::log('Windows version: ' . $ret);
        return $ret;
    }
    
    /**
     * Возвращает массив ID исключенных офферов
     *
     * @return array
     */
    private function getExcludedOffers()
    {
        return 
            array_key_exists('exclude', $this->request)
            ? $this->request['exclude']
            : array();
    }

    /**
     * Получение имени метода из запроса с проверкой, что метод входит в список "разрешённых"
     *
     * @return bool
     */
    private function getMethod()
    {
        if (!empty($this->request['m']))
        {
            $this->requestLog->method = $this->request['m'];
            if (YII_DEBUG && in_array($this->request['m'], array('Quit', 'Cancel', 'ErrorLoad', 'ErrorExecute',))) {
                $msg = 'DEBUG request[m]: '.$this->request['m']."\r\n";
                $msg.= 'request[m]: '.var_export($this->request, true);
                Yii::log($msg);
            }
            
            if (!in_array($this->request['m'], array(
                IApiMethod::INIT,
                IApiMethod::START,
                IApiMethod::DONE,
                IApiMethod::PREINIT,
                IApiMethod::BANNER_SHOW,
                IApiMethod::CHECK,
            )))
            {
                $this->errors[] = 'Unsupported method';
            }
            return $this->request['m'];
        }
        else
        {
            $this->errors[] = 'Method not definned';
        }
        return false;
    }

    private $_originalFileData = array();

    /**
     * Возвращает информацию о скачанном файле по IID загрузчика
     *
     * @return array|false
     */
    public function getOriginalFileData()
    {
        if (empty($this->_originalFileData))
        {
            if (isset($this->iid))
            {
                $this->_originalFileData = Yii::app()->db->createCommand(
                    'SELECT `url`, `name`, `type`, `size`, `browser`, `user_agent` FROM `downloads_log` WHERE id = :iid'
                )->queryRow(true, array('iid' => $this->iid));
            }
        }
        return $this->_originalFileData;
    }

    /**
     * Преобразует браузер из информации о скачивании в браузер для таргетинга
     *
     * @param $browser браузер из downloads_log
     * @return string браузер для таргетинга
     */
    private function downloadBrowserToTargetingBrowser($browser)
    {
        //MRCHROME -> Amigo, IE -> MSIE, Yandex Browser -> YaBrowser

        if ($browser == 'MRCHROME')
        {
            $browser = 'Amigo';
        }
        else if ($browser == 'IE')
        {
            $browser = 'MSIE';
        }
        else if ($browser == 'Yandex Browser')
        {
            $browser = 'YaBrowser';
        }
        else if ($browser == 'Firefox')
        {
            //fix "Fox" letter case...
            $browser = 'FireFox';
        }
        else if ($browser == 'Iron')
        {
            $browser = 'SRWare';
        }

        return $this->isValidTargetingBrowser($browser) ? $browser : '';
    }

    /**
     * Пытается определить браузер по строке обработчика протокола http, который загрузчик присылает в httphandler_full
     * Если не получается - возвращает пустую строку (а не "-") !
     *
     * @return string
     */
    private function browserFromFullHttpHandler()
    {
        if (empty($this->request['system']['httphandler_full']))
            return '';

        $full_handler = strtolower($this->request['system']['httphandler_full']);

        foreach(self::$knownBrowsers as $browserFile => $browserName)
        {
            if (
                (strpos($full_handler, '\\' . $browserFile) !== FALSE)
                && ($this->isValidTargetingBrowser($browserName))
            )
                return $browserName;
        }

        return '';
    }

    /**
     * Определение запущенных браузеров по списку процессов
     *
     * Проходит по списку процессов из $this->request['system']['processes'], и составляет список названий известных браузеров
     *
     * @return array список браузеров
     */
    private function detectBrowsers()
    {
        if (!is_array($this->browsers))
        {
            if (!empty($this->request['system']['processes']))
            {
                $result = Array();

                if (is_array($this->request['system']['processes'])) {
                    foreach($this->request['system']['processes'] as $process)
                    {
                        $p = strtolower($process);
                        if ($browser = @self::$knownBrowsers[$p]) {
                            $result[$browser] = 1;
                        }
                    }
                }

                $this->browsers = array_keys($result);
            }
            else
            {
                $this->browsers = array();
            }
        }

        return $this->browsers;
    }

    /**
     * Собирает логи по детекту браузеров
     */
    private function storeBrowserStat()
    {
        //detected by installer
        $detected_browser = isset($this->request['system']['browser']) ? $this->request['system']['browser'] : '-';

        //system http handler:
        $http_handler = isset($this->request['system']['httphandler']) ? $this->request['system']['httphandler'] : '-';

        //system http handler command:
        $http_handler_full = isset($this->request['system']['httphandler_full']) ? $this->request['system']['httphandler_full'] : '-';

        //detect all browsers in processes list:
        if (!empty($this->request['system']['processes']) && is_array($this->request['system']['processes']))
        {
            $proc_browsers = implode(",", $this->detectBrowsers());
        }
        else
        {
            $proc_browsers = '-';
        }

        //download data:
        if ($this->originalFileData) {
            $dl_browser = $this->originalFileData['browser'];
            $dl_useragent = $this->originalFileData['user_agent'];
        }
        else {
            $dl_browser = "-";
            $dl_useragent = "-";
        }
    }

    /**
     * Запись временной отметки первого преинита по данному IID.
     * @return integer количество вставленных строк
     */
    private function savePreInitTs()
    {
        $rowCount = Yii::app()->db->createCommand('INSERT IGNORE INTO preinits_ts (iid) VALUES (:iid)')->execute(array(
            ':iid' => $this->iid,
        ));
        
        return !empty($rowCount) ? (int)$rowCount : 0;
    }

    /**
     * Логирование запроса
     */
    private function writeResponse() {
        if (!empty($this->errors)) {
            $this->response['error'] = implode('; ', $this->errors);
//Yii::log($this->response['error']);
            $this->requestLog->error = $this->response['error'];
        } else {
            $this->requestLog->hasError = 0;
        }

        if (!empty($this->_log) && $this->_log instanceof InitLog && isset($this->response['offers'])) {
// log offers only for init response
            foreach ($this->response['offers'] as $k => $offer) {
                $log_offer = new OfferLog();
                $log_offer->iid = $this->iid;
                $log_offer->ukey = $this->request['ukey'];
                $log_offer->uid = $this->uid;
                $log_offer->company_id = $k;
                $log_offer->country = $this->getCountryId();
                $log_offer->ip = $_SERVER['REMOTE_ADDR'];
                $log_offer->site_id = $this->site_id;
                $log_offer->save();

                if (isset($offer['url'])) {
                    $this->response['offers'][$k]['url'] = str_replace('{@site_id}', $this->site_id_resell, $this->response['offers'][$k]['url']);
                    $this->response['offers'][$k]['url'] = str_replace('{@uid}', $this->uid, $this->response['offers'][$k]['url']);
                    $this->response['offers'][$k]['url'] = str_replace('{@iid}', $this->iid, $this->response['offers'][$k]['url']);

                    if (strpos($this->response['offers'][$k]['url'], '{@original_file_url}') !== false) {
                        $this->response['offers'][$k]['url'] = str_replace('{@original_file_url}', urlencode(@$this->originalFileData['url']), $this->response['offers'][$k]['url']);
                    }

                    if (strpos($this->response['offers'][$k]['url'], '{@original_file_name}') !== false) {
                        $this->response['offers'][$k]['url'] = str_replace('{@original_file_name}', urlencode(@$this->originalFileData['name']), $this->response['offers'][$k]['url']);
                    }

                    if (strpos($this->response['offers'][$k]['url'], '{@original_file_type}')) {
                        $this->response['offers'][$k]['url'] = str_replace('{@original_file_type}', urlencode(@$this->originalFileData['type']), $this->response['offers'][$k]['url']);
                    }


                // {@amonetize_cmdline}
                    if (strpos($this->response['offers'][$k]['url'], '{@amonetize_cmdline}') !== false) {
                        $name = @$this->originalFileData['name'];
                        $cmdline = '%Downloads%';
                        if (strlen($name) > 5) {
                            $ext = substr($name, strlen($name) - 4);
                            if ($ext == '.exe') {
                                $cmdline = '/VERYSILENT';
                            }
                        }
                        $this->response['offers'][$k]['url'] = str_replace('{@amonetize_cmdline}', urlencode($cmdline), $this->response['offers'][$k]['url']);
                    }

                //{@installcore_data}
                    if (strpos($this->response['offers'][$k]['url'], '{@installcore_data}')) {
                        $url = @$this->originalFileData['url'];
                        $name = @$this->originalFileData['name'];
                        $size = @$this->originalFileData['size'];

                        $installcore_data = InstallCoreLinkGenerator::createInstallCoreData(Settings::get('installcore_key_data'), $url, $name, $size);

                        $this->response['offers'][$k]['url'] = str_replace('{@installcore_data}', $installcore_data, $this->response['offers'][$k]['url']);
                    }

                        $this->response['offers'][$k]['url'] = str_replace(
                            '{@browser}',
                            $this->getBrowserPlaceholderReplacement(),
                            $this->response['offers'][$k]['url']
                        );
                }

                if (isset($offer['filename'])) {
                    $this->response['offers'][$k]['filename'] = str_replace('{@site_id}', $this->site_id_resell, $this->response['offers'][$k]['filename']);
                }
                if (isset($offer['params'])) {
                    foreach ($offer['params'] as $k2 => $param) {
                        $param = str_replace('{@site_id}', $this->site_id_resell, $param);
                        $param = str_replace('{@iid}', $this->iid, $param);

                        if (strpos($param, '{@mail_rfr}') !== false) {
                            $param = str_replace('{@mail_rfr}', (new BaseLoadMoneyPartner)->getReferral(), $param);
                        }

                        if (strpos($param, '{@mail_dmn}') !== false) {
                            $param = str_replace('{@mail_dmn}', (new BaseLoadMoneyPartner)->getDomain(), $param);
                        }

                        // *** Amonetize DM company params ***
                        if (strpos($param, '{@original_file_url}') !== false) {
                            $param = str_replace('{@original_file_url}', @$this->originalFileData['url'], $param);
                        }

                        if (strpos($param, '{@original_file_name}') !== false) {
                            $param = str_replace('{@original_file_name}', @$this->originalFileData['name'], $param);
                        }

                        if (strpos($param, '{@country}') !== false) {
                            $param = str_replace('{@country}', strtolower($this->getCountryId()), $param);
                        }

                        if (strpos($param, '{@current_domain}') !== false) {
                            $domainType = $this->is_silent ? UserType::RESELLER : UserType::INTERNAL;
                            $domainModel = UserDomain::model()->getCurrentWorkingDomain($domainType);
                            $domain = $domainModel ? $domainModel->domain : UserDomain::model()->getBackupDomain();
                            $param = str_replace('{@current_domain}', $domain, $param);
                        }

                        if (strpos($param, '{@uid}') !== false) {
                            $param = str_replace('{@uid}', $this->uid, $param);
                        }

                        // *** Amonetize DM company params ***
                        if (strpos($param, '{@amonetize_cmdline}') !== false) {
                            $name = @$this->originalFileData['name'];
                            $cmdline = '%Downloads%';
                            if (strlen($name) > 5) {
                                $ext = substr($name, strlen($name) - 4);
                                if ($ext == '.exe') {
                                    $cmdline = '/VERYSILENT';
                                }
                            }
                            $param = str_replace('{@amonetize_cmdline}', $cmdline, $param);
                        }

                        $param = str_replace(
                            '{@browser}',
                            $this->getBrowserPlaceholderReplacement(),
                            $param
                        );

                        $this->response['offers'][$k]['params'][$k2] = $param;
                    }
                }
            }
        }


        //собираем статистику по детекту браузеров:
        if (!empty($this->_log) && $this->_log instanceof PreinitLog)
            $this->storeBrowserStat();

        if (!empty($this->_log) && $this->_log instanceof PreinitLog)
            $this->response['ukey'] = $this->_log->ukey;

        $res_str = json_encode($this->response);

        $this->requestLog->response = $res_str;
        $this->requestLog->save();

        if (!empty($this->_log)) {
//            $this->_log->response = $res_str;

            $date = date('Y-m-d H:i:s');

            $is_empty_offer = empty($this->response['offers']) ? 1 : 0;

            $this->_log->ts = $date;

            $this->_log->is_empty_offer = $is_empty_offer;
            $this->_log->save();

            
            

            if ($this->_log instanceof InitLog) {
                if (!empty($this->request['system']['processes']) && is_array($this->request['system']['processes'])) {
                    $params_arr = array();

                    foreach ($this->request['system']['processes'] as $process) {
                        $params_arr[] = '("' . $date . '", "' . $date . '", "' . $this->request['ukey'] . '", ' . $this->iid . ',  "' . $this->uid . '",  ' . $this->site_id . ',  ' . $this->user_id . ', ' . Yii::app()->db->quoteValue($process) . ')';
                    }
                    $params = implode(',', $params_arr);
                    Yii::app()->db->createCommand('INSERT INTO processes_list (`ts`, `ts_partition`, `ukey`, `iid`, `uid`, `site_id`, `user_id`, `name`) VALUES '.$params)->execute();
                }
                
                if (!empty($this->request['system']['applications']) && is_array($this->request['system']['applications'])) {
                    $params_arr = array();

                    foreach ($this->request['system']['applications'] as $process) {
                        $params_arr[] = '("' . $date . '", "' . $date . '", "' . $this->request['ukey'] . '", ' . $this->iid . ',  "' . $this->uid . '",  ' . $this->site_id . ',  ' . $this->user_id . ', ' . Yii::app()->db->quoteValue($process) . ')';
                    }
                    
                    $params = implode(',', $params_arr);
                    Yii::app()->db->createCommand('INSERT INTO applications_list (`ts`, `ts_partition`, `ukey`, `iid`, `uid`, `site_id`, `user_id`, `name`) VALUES '.$params)->execute();
                }
            }

           
            
            
        }

//        echo $res_str;die;
        Yii::log($res_str);
//какоето говно вместо подписи.. пусть реверсеры поебутся
        $sign = md5($res_str . time() . rand());

        if ($this->allow_form_mode && $this->form_mode) {
//            echo 111;
            $this->render('form', array(
                'response' => $this->response,
                'iid' => $this->iid,
                'sign' => $sign,
                'string' => $this->iid . ':' . $sign . ':' . $res_str,
            ));
        } else {
            echo self::data_compress($this->iid . ':' . $sign . ':' . $res_str);
        }
    }

    private function parseRequest() {

        if (empty($_POST['request'])) {
            $request = file_get_contents('php://input');
            $this->requestLog->request_b64 = base64_encode($request);
            $this->requestLog->ip = sprintf('%u', ip2long($_SERVER['REMOTE_ADDR']));
            $this->requestLog->save();
            $request = self::data_decompress($request);
        } else {
            $request = $_POST['request'];
            $this->form_mode = true;
            
            $this->requestLog->request_b64 = base64_encode($request);
            $this->requestLog->ip = sprintf('%u', ip2long($_SERVER['REMOTE_ADDR']));
            
        }

        if ($request) {
            
            @list($this->iid, $this->sign, $this->request) = explode(':', $request, 3);
            Yii::log($this->request);

            $this->requestLog->request = $this->request;
            $this->requestLog->sign = $this->sign;
            $this->requestLog->iid = $this->iid;
            $this->requestLog->save();

            if (!empty($this->iid) && !empty($this->sign) && !empty($this->request)) {

                $this->sign = strtolower($this->sign);
                #Yii::log($this->sign);

                $row = Yii::app()->db->createCommand('
                            SELECT `key`, `key_2`, `site_id`, s.user_id, site_id_resell
                            FROM `installer_key_site` iks
                            JOIN `site` s ON (iks.site_id = s.id)
                            JOIN `user` u ON s.user_id = u.id
                            WHERE iks.`iid`=:_iid
                            /*Do not work with rejected or paused sites*/
                            AND s.status=:_siteStatus
                            /*Do not work with bad users*/
                            AND u.status IN (:_userStatusRegistered, :_userStatusPaymentRestricted)')->queryRow(true, array(
                                    ':_iid' => $this->iid, 
                                    ':_siteStatus' => Site::STATUS_ACTIVE, 
                                    ':_userStatusRegistered' => User::STATUS_REGISTERED,
                                    ':_userStatusPaymentRestricted' => User::STATUS_PAYMENT_RESTRICTED,
                            ));

                if (!empty($row)) {
                    $this->key = $row['key'];
                    $this->key2 = $row['key_2'];
                    $this->site_id = $row['site_id'];
                    $this->site_id_resell = $row['site_id_resell'];
                    $this->user_id = $row['user_id'];
                    $this->requestLog->site_id = $row['site_id'];

                    $this->requestJson = $this->request;
                    
                    if ($this->request = json_decode($this->request, true)) {
                        $this->requestLog->site_id_request = @$this->request['site-id'];

                        #Yii::log('JSON DECODED');

                        $this->request['ukey'] = empty($this->request['ukey']) ? 0 : $this->request['ukey'];

                        if (isset($this->request['iv'])) {
                            $this->requestLog->iv = @$this->request['iv'];

                            $iv = intval($this->request['iv']);


                            $correct = false;

// DEBUG ONLY REMOVE IN PRODUCTION
                            $correct = YII_DEBUG;
// DEBUG ONLY REMOVE IN PRODUCTION
// checking today, yesterday and tomorrow sign
                            $ts_today = time();

                            if (!$this->noCheckSign) {
                                #Yii::log('iv: ' . $iv);
                                foreach (array($ts_today, $ts_today - 86400, $ts_today + 86400,) as $ts) {
                                    if ($iv === 1) {
                                        if ($this->sign == $this->_sign_v1($ts)) {
                                            $correct = true;
                                            $this->requestLog->key = 1;
                                            break;
                                        }
                                    } elseif ($iv === 2 || $iv === 3 || $iv === 4) {
                                        if ($this->sign == $this->_sign_v2($ts)) {
                                            $correct = true;
                                            $this->requestLog->key = 1;
                                            break;
                                        }
                                    }
                                }
                            } else {
                                $correct = true;
                            }


// Check fake signature
                            if (!$correct && ($iv === 2 || $iv === 3 || $iv === 4)) {
                                foreach (array($ts_today, $ts_today - 86400, $ts_today + 86400,) as $ts) {

                                    if ($this->sign == $this->_sign_v2_fake($ts)) {
                                        $correct = true;
                                        $this->requestLog->key = 2;
                                        break;
                                    }
                                }
                            }

                            if ($correct) {
                                Yii::log('SIGNATURE VALID');

                                //подставляются данные для тестирования
                                if (YII_DEBUG) {
                                    if (isset($this->request['countryFromRequest'])) {
                                        $this->countryFromRequest = true;
                                    }
                                }
                                
                                $this->requestLog->failed = 0;

                                return true;

//                    CVarDumper::dump($request, 10, true);
                            } else {
                                $this->errors[] = 'Incorrect Sign';
                            }
                        } else {
                            $this->errors[] = 'Incorrect request Cx';
                        }
                    }
                    else {
                        $this->errors[] = 'Cannot decode JSON';
                    }
                } else
                {
// banned site
                    $this->errors[] = 'Cannot parse request b';
                }
            } else {
                $this->errors[] = 'Cannot parse request c';
            }
        }
        else {
            $this->errors[] = 'Cannot uncompres request d';
            $this->form_mode = true;
        }

        return false;
    }

    private function parseRequestZip() {

        if (empty($_POST['request'])) {
            $request = file_get_contents('php://input');
//            $this->requestLog->request_b64 = base64_encode($request);
            $this->requestLog->ip = sprintf('%u', ip2long($_SERVER['REMOTE_ADDR']));
            $this->requestLog->save();
//            $request = self::data_decompress($request);
        } else {
            $request = $_POST['request'];
            $this->form_mode = true;
        }

        if ($request) {

            $this->request = $request;
            Yii::log($this->request);

            $this->requestLog->request = $this->request;
            $this->requestLog->sign = 0;
            $this->requestLog->iid = 0;
            $this->iid = 0;
            $this->requestLog->save();


            $this->requestJson = $this->request;
            if ($this->request = json_decode($this->requestJson, true)) {
                $this->requestLog->site_id_request = @$this->request['site-id'];

                Yii::log('JSON DECODED');

                if (isset($this->request['iv'])) {
                    $this->requestLog->iv = @$this->request['iv'];

                    $this->requestLog->failed = 0;


                    return true;
                } else {
                    $this->errors[] = 'Incorrect request Cx';
                }
            } else {
                $this->errors[] = 'Cannot decode JSON';
            }
        } else {
            $this->errors[] = 'Cannot uncompres request d';
            $this->form_mode = true;
        }

        return false;
    }

    private function _sign_v1($ts) {
        $sign = md5($this->requestJson . self::gen_salt($ts, $this->key));
        Yii::log('Sign v1: TS: ' . date('Y-m-d', $ts) . ' => ' . $sign);
        return $sign;
    }

    private function _sign_v2($ts) {
        $sign = md5($this->requestJson . md5($this->key . date('Ymd', $ts)));
        #Yii::log('Sign v2: TS: ' . date('Ymd', $ts) . ' => ' . $sign);
        return $sign;
    }

    private function _sign_v2_fake($ts) {
        $sign = md5($this->requestJson . md5($this->key2 . date('Ymd', $ts)));
        #Yii::log('Sign v2 fake: TS: ' . date('Ymd', $ts) . ' => ' . $sign);
        return $sign;
    }

    private function generateUid() {

//(%username%/%userdomain%/sid/mac/серийник первого HDD
        if (
                isset($this->request['system']['uname']) &&
                isset($this->request['system']['dname']) &&
                isset($this->request['system']['sid'])
//   isset($this->request['system']['mac'][0]) &&
//   isset($this->request['system']['hw']['hdd'])
        ) {
            if (empty($this->request['system']['mac'][0]))
                $this->request['system']['mac'][0] = '';

            if (empty($this->request['system']['hw']['hdd']))
                $this->request['system']['hw']['hdd'] = '';

            $this->requestLog->uname = $this->request['system']['uname'];
            $this->requestLog->dname = $this->request['system']['dname'];
            $this->requestLog->sid = $this->request['system']['sid'];

            $this->requestLog->rid = @substr($this->request['system']['sid'], @strrpos($this->request['system']['sid'], '-') + 1);
            $this->requestLog->mac0 = $this->request['system']['mac'][0];
            $this->requestLog->hdd = $this->request['system']['hw']['hdd'];


            $this->uid = md5($this->request['system']['uname'] . $this->request['system']['dname'] . $this->request['system']['sid'] . $this->request['system']['mac'][0] . $this->request['system']['hw']['hdd']);
            $this->response['uid'] = $this->uid;
            $this->requestLog->uid = $this->uid;
            Yii::log('UID: ' . $this->uid);
            return true;
        } else {
            $this->errors[] = 'insufficient data to generate uid';
            return false;
        }
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
     * Return array with prepared sql condition 
     * of already visited banner companies and banners 
     * which should not be displayed today
     * @return array $dataArray keys(out_b_companies, out_banners)
     */
    private function forBannersTodayVisitedCond()
    {
        $dataArray = array('out_b_companies' => '', 'out_banners' => '', 'companies_for_check' => '');
        $conditionOutBanners = array();
        $conditionOutBCompanies = array();
        $conditionOutBCfromBanners = array();
        $bannerCompaniesCount = array();
        
        $sql = "SELECT bcb.b_company_id, bcb.id banner_id
            FROM b_company_banners bcb, banner_click_log bcl 
            WHERE bcl.`banner_id` = bcb.`id` AND bcl.iid = " . $this->iid;
        
        $res = Yii::app()->db->createCommand($sql)->queryAll();
        if (is_array($res) && count($res) > 0) {
            while (list($i, $data) = each($res))
            {
                $conditionOutBanners[] = $data['banner_id'];
                //skip error for keep it simple
                @$bannerCompaniesCount[$data['b_company_id']] += 1;
                $conditionOutBCfromBanners[] = $data['b_company_id'];
            }
            $dataArray['companies_for_check'] = ' AND b_company_id IN (' . implode(', ', $conditionOutBCfromBanners) . ') ';
            $dataArray['out_banners'] = ' AND b.id NOT IN (' . implode(', ', $conditionOutBanners) . ') ';        
        
            $sql = "SELECT COUNT(*) cnt, b_company_id
                FROM b_company_banners 
                WHERE paused = 0 AND deleted = 0 " . $dataArray['companies_for_check'] . "
                GROUP BY b_company_id";

            $res = Yii::app()->db->createCommand($sql)->queryAll();
            if (is_array($res) && count($res) > 0)
            {
                while (list($i, $data) = each($res))
                {
                    //skip error for keep it simple
                    if ((int)@$bannerCompaniesCount[$data['b_company_id']] >= $data['cnt'])
                    {
                        $conditionOutBCompanies[] = $data['b_company_id'];
                    }
                }
            }
        }
        
        $sql = "SELECT bc.id, bc.`person_daily_maxclicks` pdm, COUNT(*) cnt 
            FROM b_company bc, b_company_banners bcb, banner_click_log bcl 
            WHERE bcb.`b_company_id` = bc.id AND bcl.`banner_id` = bcb.`id` AND bcl.ts >= CURDATE() 
            AND bcl.uid = '" . $this->uid . "' GROUP BY bc.id;";
        
        $res = Yii::app()->db->createCommand($sql)->queryAll();
        if (is_array($res) && count($res) > 0) {
            while (list($i, $data) = each($res)) {
                if ($data['cnt'] >= $data['pdm']) {
                    $conditionOutBCompanies[] = $data['id'];
                }
            }
            $dataArray['out_b_companies'] = ' AND c.id NOT IN (' . implode(', ', $conditionOutBCompanies) . ') ';
        }  
        
        return $dataArray;
    }
    
    /**
     * Select banner for display in installer 
     * @return response ['banners'][$banner['id']]['banner']['html'] with banner html info
     */
    private function getBanner() 
    {
        Yii::beginProfile('InstallerApi.Init.GetBanner');
        
        //$todayVisitedCond = $this->forBannersTodayVisitedCond();
        $todayVisitedCond = array();
        $todayVisitedCond['out_b_companies'] = '';
        $todayVisitedCond['out_banners'] = '';
        
        $rand = rand(0, 999) / 1000;
        $companyRangPobabilityTable = array(75, 7, 6, 4, 2, 2, 2, 1, 1);
        $qty = $i = count($companyRangPobabilityTable);
        $companyRangPobability = rand(0, 100);

        $i = 0;
        $rang = 0;
        $companyRang = null;
        while ($i < $qty) {
            $rang += $companyRangPobabilityTable[$i];
            if ($companyRangPobability <= $rang) {
                $companyRang = $i;
                break;
            }
            $i++;
        }
        $companyRang = $companyRang === null ? 0 : $companyRang;

        $sql = 'SELECT `c`.`id`, `c`.`banner_priority_sum`, `c`.`statistics_mode`
                FROM `b_company` `c`
                WHERE  
                    `c`.`is_archived` = :_bCampaignNotArchived
                    AND `c`.`limit_daily` > `c`.`count_daily` 
                    AND `c`.`paused`=:_bCampaignNotPaused
                    AND `c`.`payed`=:_bCampaignPaid
                    AND `c`.`status`=:_bCampaignStatusActive
                    ' . $todayVisitedCond['out_b_companies'] . '
                    AND 
                        (
                            `c`.`target_site_category` = 0
                            OR `c`.id IN (SELECT b_company_id FROM `b_company2site_category` c2sc JOIN `site` s ON (c2sc.site_category_id = s.category) WHERE s.id = "' . $this->getSiteId() . '")
                        )
                    AND 
                        (
                            `c`.`target_country` = 0
                            OR `c`.id IN (SELECT b_company_id FROM `b_company2country` WHERE country_id = "' . $this->getCountryId() . '")
                        )
                ORDER BY `c`.`admin_ordr`,`c`.`ordr`
                LIMIT ' . $qty;
        $companies = Yii::app()->db
            ->createCommand($sql)
            ->queryAll(true, array(
                ':_bCampaignStatusActive' => BaseBCampaign::STATUS_ACTIVE,
                ':_bCampaignPaid' => 1,
                ':_bCampaignNotPaused' => 0,
                ':_bCampaignNotArchived' => 0,
            ));

        $company = null;
        if (!empty($companies[$companyRang])) {
            $company = $companies[$companyRang];
        } else {
            if (!empty($companies[0])) {
                $company = $companies[0];
            }
        }

        if ($company && ($companyId = intval($company['id'], 10)) > 0) {
            $sql = 'SELECT `b`.`id`, `b`.`banner_url`, `b`.`is_code`, `b`.`b_code`
                    FROM `b_company_banners` `b`
                    WHERE 
                        `b`.`b_company_id` = ' . $companyId . ' 
                        ' . $todayVisitedCond['out_banners'] . '    
                        AND `b`.`deleted` = 0 
                        AND `b`.`paused`=0
                        AND `b`.`priority_line` >= FLOOR(1 + (' . $rand . ' * ' . $company['banner_priority_sum'] . '))
                    ORDER BY `b`.`id` ASC
                    LIMIT 1';
            $banner = Yii::app()->db->createCommand($sql)->queryRow();

            if ($banner) {
                $sign = md5($this->site_id . $this->iid . Settings::get('banner_sign_salt') . $banner['id'] . $this->uid);
                
                $scriptName = 'setStat.php';
                if (intval($company['statistics_mode']) === BCompany::MODE_EXTERNAL) {
                    $scriptName = 'externalBanners.php';
                }
                $statUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/externalScripts/' . $scriptName . '?uid=' . $this->uid . '&ukey=' . $this->request['ukey'] . '&banner_id=' . $banner['id'] . '&iid=' . $this->iid . '&sid=' . $this->site_id . '&sign=' . $sign;
                    
                if (!$banner['is_code']) {
                    $bannerInfo = '<a href="' . $statUrl . '"><img src="http://' . $_SERVER['HTTP_HOST'] . '' . $banner['banner_url'] . '"  border=0></a>';
                } else {
                    $bannerInfo = str_replace('{@link}', $statUrl, $banner['b_code']);
                    //protocol hotfix
                    $bannerInfo = str_replace(array('href="//', 'src="//'), array('href="http://', 'src="http://'), $bannerInfo);
                }

                $this->response['banners'][$banner['id']]['banner']['html'] = $bannerInfo;
            }
        }

        Yii::endProfile('InstallerApi.Init.GetBanner');
    }


    private function is_potential_shit() {
        /*
 - uname/dname/sid НЕ пустые.
 - uname [1?-256], udomain [1-15]
 - sid должен пройти валидацию (хоть регэксп ^S-1-5-21(-\d+)+$ )
 - составить допустимые комбинации os_version, os_build, os_sp, os_x64, os_type, os_r2, os_suite, os_arch
   и заодно langid, browser, time

 - прямо сейчас глушим (просто говорим, что нет офферов):
	пустые uname/dname/sid
	os_version = os_sp && os_build == os_suite
	os_type = 2 (это вообще песня, на контроллере домена они запускают его, да..)

	При этом в обязательном порядке собираем айпи, маки и потом тянем туда же информацию из download_log

	Но для начала выяснить, не может ли это быть глюком инсталлера.
         */

        if (!in_array($this->request['m'], array(IApiMethod::INIT, IApiMethod::PREINIT))) return false;

        $uname_len = strlen(@$this->request['system']['uname']);
        $dname_len = strlen(@$this->request['system']['dname']);
        $good_sid = preg_match("/^S-1-5(-\d+)+$/", @$this->request['system']['sid']);

        $app_count = count(@$this->request['system']['applications']);
        $proc_count = count(@$this->request['system']['processes']);

        $valid_os_info = array(
            '5.1' => array(
                'builds' => array('2600'),
                'sps' => array('2.0', '3.0'),
            ),
            /*  нахуй висту
            '6.0' => array(
                'builds' => array(),
                'sp' => array(),
            ),
            */
            '6.1' => array(
                'builds' => array('7600', '7601'),
                'sps' => array('0.0', '1.0'),
            ),
            '6.2' => array(
                'builds' => array('9200'),
                'sps' => array('0.0'),
            ),
            '6.3' => array(
                'builds' => array('9600'),
                'sps' => array('0.0'),
            ),
        );

        $is_valid_os = false;
        if ($_os = @$valid_os_info[@$this->request['system']['os']['version']]) {
            $is_valid_os = in_array(@$this->request['system']['os']['build'], $_os['builds'])
                        && in_array(@$this->request['system']['os']['sp'], $_os['sps']);
        }

        $valid_os_type = (1 == @$this->request['system']['os']['type']);

        $is_valid = $uname_len > 0 && $uname_len <= 256
            && $dname_len > 0 && $dname_len <= 15
            && $good_sid
            && $is_valid_os
            && $valid_os_type;

        //log
        /*
        Yii::app()->db->createCommand('INSERT INTO `_sys_params_log` (`proc_count`, `app_count`, `valid_os`, `site_id`, `ip`)
VALUES (:proc_count, :app_count, :is_valid, :site_id, INET_ATON(:ip))')->execute(array(
                'proc_count' => $proc_count,
                'app_count' => $app_count,
                'is_valid' => $is_valid ? 1 : 0,
                'site_id' => $this->site_id,
                'ip' => $_SERVER['REMOTE_ADDR'],
        ));
        */

        //invert is_valid value
        return $is_valid ? 0 : 1;
    }
    
    /**
     * Предназначен для тестирования АПИ ответов и, при необходимости, другого функционала
     * 
     * @param type $do - задекларированное действие
     * @return void
     */
    public function _test($do) 
    {
        if (Yii::app()->user->checkAccess('testApiAnswer')) {
            switch ($do) {
                case '_cachedAdvertizeDomainQuery':
                        return $this->_cachedAdvertizeDomainQuery();
                    break;
                case 'getOffersCache':
                        $this->init();
                        return $this->actionIndex();
                    break;
                default:
                    header("HTTP/1.0 404 Not Found");
                    die();
                    break;
            }
        }
    }

    /**
     * Возращает сокращённое название основного браузера пользователя
     *
     * @return string Сокращённое название браузера (например, Google Chrome будет возвращён, как chrome).
     * Если браузер неопределён, то вернётся other.
     */
    public function getBrowserPlaceholderReplacement()
    {
        $browser = $this->getBrowser();
        $browser = Browser::model()->getCorrectBrowserName($browser);
        $browser = $browser ?: 'other';
        $browser = strtolower($browser);
        return $browser;
    }
}
