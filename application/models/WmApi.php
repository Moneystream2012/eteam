<?php

class WmApi extends CFormModel {

    /**
     * @var User 
     */
    public $user;

    /**
     * @var integer User ID
     */
    public $user_id;

    /**
     * @var string Date Y-m-d format
     */
    public $start;

    /**
     * @var string Date Y-m-d format
     */
    public $end;

    /**
     * @var integer Site ID 
     */
    public $site_id;

    /**
     * @var string Параметр stat_mode должен иметь значения "user" либо "site"
     */
    public $stat_mode;

    /**
     *
     * @var string Wm Api-key 
     */
    public $apiKey;

    public function rules() {
        return array(
            array('user_id', 'getUser_id'),
            array('user_id, site_id', 'numerical', 'integerOnly' => true),
            array('start, end, apiKey', 'required'),
            array('apiKey', 'length', 'is' => 32),
            array('start, end', 'date', 'format' => 'yyyy-MM-dd'),
            array('start, end', 'lessThanEnd', 'skipOnError' => true),
            array('user_id', 'onlySitesThatIOwn'),
            array('stat_mode', 'stat_mode_valid_values'),
        );
    }

    public function getUser_id($attribute) {
        if ($this->user = User::model()->findByAttributes(array('apiKey' => $this->apiKey))) {
            $this->user_id = $this->user->id;
        } else {
            $this->addError($attribute, Yii::t('errors','Пользователь не найден'));
        }
    }

    public function stat_mode_valid_values($attribute) {
        if ($this->stat_mode != 'site' && $this->stat_mode != 'user') {
            $this->addError($attribute, Yii::t('errors','Параметр stat_mode должен иметь значения "user" либо "site"'));
        }
    }

    public function onlySitesThatIOwn($attribute) {
        if ($this->site_id) {
            foreach (Site::model()->findAllByAttributes(array('user_id' => $this->user_id)) as $site) {
                if ($site->id == $this->site_id)
                    return;
            }
            $this->addError($attribute, Yii::t('errors','Данный пользователь не является владельцем указанного сайта'));
        }
    }

    public function lessThanEnd() {
        $start = strtotime($this->start);
        $end = strtotime($this->end);
        if ($start > $end) {
            $this->addError('start', Yii::t('errors','Начало интервала должно быть не больше окончания'));
        }
        if (($end - $start) / (60 * 60 * 24) > 31) {
            $this->addError('end', Yii::t('errors','Нельзя выбрать интервал больше {days} дней', array('{days}' => 30)));
        }
    }

    public function init() {
        parent::init();
        $this->start = date('Y-m-d', time() - (60 * 60 * 24 * 7));
        $this->end = date('Y-m-d');
    }

    public function getSiteStat() {

        $base_table = '';
        $start_ts = strtotime($this->start);
        $end_ts = strtotime($this->end);

        for ($i = $end_ts; $i >= $start_ts; $i-=60 * 60 * 24) {
            $base_table .= empty($base_table) ? '' : ' UNION ';
            $base_table .= 'SELECT "' . date('Y-m-d', $i) . '" as "dt" ';
        }

        $sql =      "SELECT dt,
                    site_id,
                    downloads,
                    unique_downloads,
                    inits,
                    starts,
                    installs,
                    ROUND( inits / (unique_downloads + 0.001) * 100, 2 ) AS  'download_conversion',
                    IFNULL(ROUND( installs / inits *100, 2 ),0) AS  'conversion',
                    IFNULL(ROUND( price_webmaster / inits * 1000, 2), 0) AS 'cpm',
                    ROUND( price_webmaster + 0.001, 2 ) AS  'price'
                    FROM
                    (
                    select  
                    a.dt,a.site_id,
                    IFNULL(b.downloads,0) AS  'downloads',
                    IFNULL(b.unique_downloads,0) AS  'unique_downloads',
                    IFNULL(b.inits,0) AS  'inits',
                    IFNULL(b.starts,0) AS  'starts',
                    IFNULL(b.installs,0) AS  'installs',
                    IFNULL(b.price_webmaster,0) AS  'price_webmaster'
                    from 
                    (
                    select base.dt,ggg.site_id FROM
                        (" . $base_table . ") base
                    left join (SELECT distinct id as site_id FROM site s WHERE user_id = " . $this->user_id . ") ggg on 1=1
                    ) a

                    left join 
                    (
                    SELECT st.`date` AS  'dt', -1 as 'h',
                    st.site_id,
                        SUM( st.downloads ) AS  'downloads',
                        SUM( st.unique_downloads ) AS  'unique_downloads',
                        SUM( st.inits ) AS  'inits',
                        SUM( st.starts ) AS  'starts',
                        SUM( st.installs ) AS  'installs',
                        SUM( st.webmaster_price ) AS  'price_webmaster'
                        FROM `statistics_dayly` st
                        WHERE st.`date` >= '" . $this->start . "'
                        AND st.`date` <= '" . $this->end . "'
                    and site_id in (SELECT id FROM site s WHERE user_id = " . $this->user_id . ")
                    -- and site_id >= 1
                    -- and site_id <= 5
                        GROUP BY 1,st.site_id
                    ) b on b.dt = a.dt and b.site_id = a.site_id
                    ) vvv
                    ORDER BY dt ASC
                    ";
        $ret = Yii::app()->cache->get(__CLASS__ . __FUNCTION__ . $this->user_id . serialize($this->attributes));
        if (!$ret) {
            $ret = Yii::app()->db->createCommand($sql)->queryAll();
            Yii::app()->cache->set(__CLASS__ . __FUNCTION__ . $this->user_id . serialize($this->attributes), $ret, 300);
        }
        return $ret;
    }

    public function getUserStat() {

        $filter_user_site = '';

        if (!empty($this->site_id)) {
            $filter_user_site.=' AND site_id=' . $this->site_id;
        } else {
            $filter_user_site.='AND site_id IN(SELECT id FROM site WHERE user_id =' . $this->user_id . ')';
        }

        $base_table = '';
        $start_ts = strtotime($this->start);
        $end_ts = strtotime($this->end);

        for ($i = $end_ts; $i >= $start_ts; $i-=60 * 60 * 24) {
            $base_table .= empty($base_table) ? '' : ' UNION ';
            $base_table .= 'SELECT "' . date('Y-m-d', $i) . '" as "dt" ';
        }

        $sql = "
                    SELECT dt,
                        downloads,
                        unique_downloads,
                        inits,
                        starts,
                        installs,
                        ROUND( inits / unique_downloads * 100, 2 ) AS  'download_conversion',
                        IFNULL(ROUND( installs / inits *100, 2 ),0) AS  'conversion',
                        IFNULL(ROUND( price_webmaster / inits * 1000, 2), 0) AS 'cpm',
                        ROUND( price_webmaster + 0.001, 2 ) AS  'price'
                    FROM
                    (
                    SELECT base.dt,
                        IFNULL(st.downloads,0) AS  'downloads',
                        IFNULL(st.unique_downloads,0) AS  'unique_downloads',
                        IFNULL(st.inits,0) AS  'inits',
                        IFNULL(st.starts,0) AS  'starts',
                        IFNULL(st.installs,0) AS  'installs',
                        IFNULL(st.price_webmaster,0) AS  'price_webmaster'
                    FROM
                    (" . $base_table . ") base
                        LEFT JOIN
                    (SELECT st.`date` AS  'dt', -1 as 'h',
                        SUM( st.downloads ) AS  'downloads',
                        SUM( st.unique_downloads ) AS  'unique_downloads',
                        SUM( st.inits ) AS  'inits',
                        SUM( st.starts ) AS  'starts',
                        SUM( st.installs ) AS  'installs',
                        SUM( st.webmaster_price ) AS  'price_webmaster'
                        FROM `statistics_dayly` st
                        WHERE st.`date` >=  '" . $this->start . "'
                        AND st.`date` <=  '" . $this->end . "'
                        " . $filter_user_site . "
                        GROUP BY 1
                        ) st ON (base.dt = st.dt)


                        GROUP BY 1
                    ) a
                    ORDER BY dt ASC
                    ";
        $ret = Yii::app()->cache->get(__CLASS__ . __FUNCTION__ . $this->user_id . serialize($this->attributes));
        if (!$ret) {
            $ret = Yii::app()->db->createCommand($sql)->queryAll();
            Yii::app()->cache->set(__CLASS__ . __FUNCTION__ . $this->user_id . serialize($this->attributes), $ret, 300);
        }
        return $ret;
    }

    public function getUserSites($user_id = 0) {
        if (empty($user_id)) {
            $user_id = $this->user_id;
        }
        return Site::model()->findAllByAttributes(array('user_id' => $user_id));
    }

    public function sitesToXML() {
        $ret = Yii::app()->cache->get(__CLASS__ . __FUNCTION__ . $this->user_id . serialize($this->attributes));
        if (!$ret) {
            $xml = new SimpleXMLElement('<sites></sites>');
            $array = $this->userSites;
            foreach ($array as $obj) {
                $xml->addChild('site', $obj->url)->addAttribute('id', $obj->id);
            }

            $ret = $xml->asXML();
            Yii::app()->cache->set(__CLASS__ . __FUNCTION__ . $this->user_id . serialize($this->attributes), $ret, 300);
        }
        return $ret;
    }

    public function errorToXML() {
        $xml = new SimpleXMLElement('<statistics></statistics>');
        foreach ($this->getErrors() as $key => $error) {
            foreach ($error as $val) {
                $xml_error = $xml->addChild('error', $val);
                if ($key == 'user_id' || $key = 'apiKey')
                    $xml_error->addAttribute('code', '401');
                else
                    $xml_error->addAttribute('code', '400');
            }
        }
        return($xml->asXML());
    }

    public function statToXML() {
        $array = $this->{$this->stat_mode . 'Stat'};
        $xml = new SimpleXMLElement('<statistics></statistics>');
        if ($this->stat_mode != 'user' && empty($this->site_id)) {
            $repeat = '';
            foreach ($array as $key => $cur_day) {
                if ($repeat != $cur_day['dt']) {
                    $repeat = $cur_day['dt'];
                    $xml_day = $xml->addChild('day');
                    $xml_day->addAttribute('date', $cur_day['dt']);
                }
                $site = $xml_day->addChild('site');
                $site->addAttribute('id', $cur_day['site_id']);
                foreach ($cur_day as $key => $attr) {
                    if ($key != 'site_id' && $key != 'dt') {
                        $site->addChild($key, $attr);
                    }
                }
            }
        } else {
            foreach ($array as $key => $cur_day) {
                $xml_day = $xml->addChild('day');
                $xml_day->addAttribute('date', $cur_day['dt']);
                if ($this->stat_mode == 'user' && empty($this->site_id)) {
                    foreach ($cur_day as $key => $attr) {
                        $xml_day->addChild($key, $attr);
                    }
                } else {
                    if (!empty($this->site_id)) {
                        $site = $xml_day->addChild('site');
                        $site->addAttribute('id', $this->site_id);
                        foreach ($cur_day as $key => $attr) {
                            $site->addChild($key, $attr);
                        }
                    }
                }
            }
        }
        return($xml->asXML());
    }
}
