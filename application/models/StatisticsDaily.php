<?php

/**
 * This is the model class for table "statistics_dayly".
 *
 * The followings are the available columns in table 'statistics_dayly':
 * @property string $date
 * @property integer $company_id
 * @property integer $m_company_id
 * @property integer $site_id
 * @property integer $sub_id
 * @property integer $downloads
 * @property integer $unique_downloads
 * @property integer $preinits
 * @property integer $inits
 * @property integer $inits_empty
 * @property integer $offers
 * @property integer $starts
 * @property integer $installs
 * @property integer $installs_internal
 * @property double $advertizer_price
 * @property double $webmaster_price
 * @property double $reseller_price
 */
class StatisticsDaily extends CActiveRecord
{
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'statistics_dayly';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('company_id, m_company_id, site_id, sub_id, downloads, unique_downloads, preinits, inits, inits_empty, offers, starts, installs, installs_internal', 'numerical', 'integerOnly' => true),
            array('advertizer_price, webmaster_price, reseller_price', 'numerical'),
            // The following rule is used by search().
            // @todo Please remove those attributes that should not be searched.
            array('date, company_id, m_company_id, site_id, sub_id, downloads, unique_downloads, preinits, inits, inits_empty, offers, starts, installs, installs_internal, advertizer_price, webmaster_price, reseller_price', 'safe', 'on' => 'search'),
        );
    }

    /**
     * @return array relational rules.
     */
    public function relations()
    {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
            'campaign' => array(self::BELONGS_TO, 'BasePcCampaign', 'company_id'),
            'm_campaign' => array(self::BELONGS_TO, 'BaseMCampaign', 'm_company_id'),
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return array(
            'date' => 'Date',
            'company_id' => 'Company',
            'm_company_id' => 'M Company',
            'site_id' => 'Site',
            'sub_id' => 'Sub',
            'downloads' => 'Downloads',
            'unique_downloads' => 'Unique Downloads',
            'preinits' => 'Preinits',
            'inits' => 'Inits',
            'inits_empty' => 'Inits Empty',
            'offers' => 'Offers',
            'starts' => 'Starts',
            'installs' => 'Installs',
            'installs_internal' => 'Installs Internal',
            'advertizer_price' => 'Advertizer Price',
            'webmaster_price' => 'Webmaster Price',
            'reseller_price' => 'Reseller Price',
        );
    }

    /**
     * Retrieves a list of models based on the current search/filter conditions.
     *
     * Typical usecase:
     * - Initialize the model fields with values from filter form.
     * - Execute this method to get CActiveDataProvider instance which will filter
     * models according to data in model fields.
     * - Pass data provider to CGridView, CListView or any similar widget.
     *
     * @return CActiveDataProvider the data provider that can return the models
     * based on the search/filter conditions.
     */
    public function search()
    {
        // @todo Please modify the following code to remove attributes that should not be searched.

        $criteria = new CDbCriteria;

        $criteria->compare('date', $this->date, true);
        $criteria->compare('company_id', $this->company_id);
        $criteria->compare('m_company_id', $this->m_company_id);
        $criteria->compare('site_id', $this->site_id);
        $criteria->compare('sub_id', $this->sub_id);
        $criteria->compare('downloads', $this->downloads);
        $criteria->compare('unique_downloads', $this->unique_downloads);
        $criteria->compare('preinits', $this->preinits);
        $criteria->compare('inits', $this->inits);
        $criteria->compare('inits_empty', $this->inits_empty);
        $criteria->compare('offers', $this->offers);
        $criteria->compare('starts', $this->starts);
        $criteria->compare('installs', $this->installs);
        $criteria->compare('installs_internal', $this->installs_internal);
        $criteria->compare('advertizer_price', $this->advertizer_price);
        $criteria->compare('webmaster_price', $this->webmaster_price);
        $criteria->compare('reseller_price', $this->reseller_price);

        return new CActiveDataProvider($this, array(
            'criteria' => $criteria,
        ));
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return StatisticsDaily the static model class
     */
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }
}
