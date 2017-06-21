<?php

/**
 * This is the model class for table "user_settings".
 * настройки пользователя 
 * модель (таблица), в основном, содержит необходимые одноименные поля 
 * с названиями переменных модели (таблицы) Settings
 * 
 * по определению настройка пользователя (значение в поле модели UserSettings)
 * приоритетнее глобальной настройки (значение переменной модели Settings)
 * 
 * The followings are the available columns in table 'user_settings':
 * @property integer $user_id
 * @property integer $component_quantity
 * @property string $brand
 * @property string $language
 */
class UserSettings extends CActiveRecord {
   
    /**
     * Returns the static model of the specified AR class.
     * @param string $className active record class name.
     * @return UserDomain the static model class
     */
    public static function model($className = __CLASS__){
        return parent::model($className);
    }

    /**
     * @return string the associated database table name
     */
    public function tableName() {
        return 'user_settings';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules() {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('user_id, brand, language', 'required', 'except' => 'prevalidation'),
            array('user_id, component_quantity', 'numerical', 'integerOnly' => true),
            array('brand', 'brandExists'),
            array('language', 'languageExists'),
            array('user_id, component_quantity, brand, language', 'safe', 'on' => 'search'),
        );
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->brand = Yii::app()->project->getCurrentBrand();
        $this->language = Yii::app()->language;
    }

    /**
     * Проверяет, входит ли значение атрибута в список брендов
     *
     * @param string $attribute Атрибут
     * @param array $params Параметры
     */
    public function brandExists($attribute, $params)
    {
        if (!array_key_exists($this->$attribute, Yii::app()->project->getBrands())) {
            $this->addError($attribute, Yii::t('messages', 'Бренд не найден.'));
        }
    }

    /**
     * Проверяет, входит ли значение атрибута в список языков
     *
     * @param string $attribute Атрибут
     * @param array $params Параметры
     *
     * @return boolean Прошла ли валидация
     */
    public function languageExists($attribute, $params)
    {
        if (!in_array($this->$attribute, Yii::app()->project->getLanguages())) {
            $this->addError($attribute, Yii::t('messages', 'Язык не найден.'));
        }
    }

    /**
     * @return array relational rules.
     */
    public function relations() {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
            'user' => array(self::BELONGS_TO, 'User', 'user_id'),
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels() {
        return array(
            'user_id' => Yii::t('labels','ID пользователя'),
            'component_quantity' => Yii::t('labels','Количество офферов'),
            'brand' => Yii::t('labels', 'Бренд'),
            'language' => Yii::t('labels', 'Язык интерфейса'),
        );
    }

    /**
     * Retrieves a list of models based on the current search/filter conditions.
     * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
     */
    public function search($domain_category = '') {
        // Warning: Please modify the following code to remove attributes that
        // should not be searched.

        $criteria = new CDbCriteria;
        $criteria->compare('user_id', $this->user_id);
        $criteria->compare('component_quantity', $this->component_quantity);
        $criteria->compare('brand', $this->brand, true);
        $criteria->compare('language', $this->language);

        return new CActiveDataProvider($this, array(
            'criteria' => $criteria,
            'sort' => array(
                'defaultOrder' => 't.user_id ASC'
            ),
        ));
    }

    /**
     * Сохраняет настройки пользователя в момент регистрации
     *
     * @param $userId
     * @param array $attributes
     * @return boolean
     */
    public function saveOnUserUpdate($userId, $attributes = array())
    {
        $this->user_id = $userId;

        foreach ($attributes as $attribute => $value) {
            if ($this->hasAttribute($attribute)) {
                $this->$attribute = $value;
            }
        }

        if (!$this->brand) {
            $this->brand = Yii::app()->project->getCurrentBrand();
        }

        if (!$this->language) {
            $this->language = Yii::app()->language;
        }

        return $this->save();
    }
}