<?php

/**
 * This is the model class for table "sms_queue".
 *
 * The followings are the available columns in table 'sms_queue':
 * @property integer $id
 * @property string $phone
 * @property string $message
 * @property integer $try_count
 * @property string $created
 * @property string $send_time
 */
class SmsQueue extends CActiveRecord
{
    const PARSER_MYSQL_DATETIME_FORMAT = 'yyyy-MM-dd HH:mm:ss';
    const MYSQL_DATETIME_FORMAT = 'Y-m-d H:i:s';

    /**
     * Таймаут в секундах перед отправкой sms в зависимости от номера попытки
     */
    public static $tryCountTimeout = array(
        0 => 60,
        1 => 300,
        2 => 600,
    );

    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'sms_queue';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        return array(
            array('phone, message, send_time', 'required'),
            array('phone', 'length', 'max' => 20),
            array('try_count', 'numerical', 'integerOnly' => true, 'min' => 0),
            array('send_time', 'date', 'format' => self::PARSER_MYSQL_DATETIME_FORMAT),
            array('try_count', 'default', 'value' => 0),
        );
    }

    /**
     * @return array a list of behaviors that this model should behave as.
     */
    public function behaviors()
    {
        return array(
            'CTimestampBehavior' => array(
                'class' => 'zii.behaviors.CTimestampBehavior',
                'createAttribute' => 'created',
                'updateAttribute' => null,
            )
        );
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return SmsQueue the static model class
     */
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * @param string $phone
     * @param string $message
     * @return bool whether message push to queue was successful
     */
    public static function push($phone, $message)
    {
        $model = new self();
        $model->phone = $phone;
        $model->message = $message;
        $model->send_time = date(self::MYSQL_DATETIME_FORMAT, time() + self::$tryCountTimeout[0]);

        return $model->save();
    }

    /**
     * @return bool whether try_count increase was successful
     */
    public function increaseTryCount()
    {
        $this->try_count++;

        if (!isset(self::$tryCountTimeout[$this->try_count])) {
            return false;
        }
        $this->send_time = date(self::MYSQL_DATETIME_FORMAT, time() + self::$tryCountTimeout[$this->try_count]);

        return true;
    }
}
