<?php

/**
 * This is the model class for table "working_cpm_price".
 *
 * The followings are the available columns in table 'working_cpm_price':
 * @property integer $id
 * @property integer $company_id
 * @property double $cpm
 * @property integer $price
 * @property integer $category
 * @property string $country_id
 * @property string $platform_id
 */
class WorkingCpmPrice extends CActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'working_cpm_price';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('company_id, cpm, price, category, country_id, platform_id', 'required'),
			array('company_id, price, category, platform_id', 'numerical', 'integerOnly'=>true),
			array('cpm', 'numerical'),
			array('country_id', 'length', 'max'=>2),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, company_id, cpm, price, category, country_id, platform_id', 'safe', 'on'=>'search'),
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
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'company_id' => 'Company',
			'cpm' => 'Cpm',
			'price' => 'Price',
			'category' => 'Category',
			'country_id' => 'Country',
			'platform_id' => 'OS',
		);
	}

    /**
     * Кампании, отсортированные по весу CPM с учетом таргетинга 
     * 
     * @param type $countryId Targeting by country
     * @param type $platformId Targeting by windows version
     * @return array
     */
    public function getCpmTargetCampaignsOrder($countryId, $platformId)
    {
        
        return Yii::app()->db->createCommand()
                ->select('`company_id`, MAX(`cpm`) `max_cpm`')
                ->from('{{working_cpm_price}}')
                ->where(
                        '(country_id=:_countryId OR country_id=:_countryNoTarget) AND (platform_id=:_platformId OR platform_id=:_platformNoTarget)', 
                        array( 
                            ':_countryId' => $countryId ? $countryId : (Settings::get('old_ranking_enabled') ? '--' : 0),
                            ':_countryNoTarget' => Settings::get('old_ranking_enabled') ? '--' : 0,
                            ':_platformId' => $platformId ? $platformId : 0,
                            ':_platformNoTarget' => 0,
                        ))
                ->group('company_id')
                ->order('max_cpm DESC')
                ->queryAll();
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

		$criteria=new CDbCriteria;

		$criteria->compare('id',$this->id);
		$criteria->compare('company_id',$this->company_id);
		$criteria->compare('cpm',$this->cpm);
		$criteria->compare('price',$this->price);
		$criteria->compare('category',$this->category);
		$criteria->compare('country_id',$this->country_id,true);
		$criteria->compare('platform_id', $this->platform_id);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return WorkingCpmPrice the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
}
