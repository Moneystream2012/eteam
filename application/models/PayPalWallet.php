<?php
/**
 * Class PayPalWallet
 *
 * @author Tarasenko Andrey <andrey.installmonster@gmail.com>
 */
/**
 * Model for working with PayPal wallets
 *
 * @author Tarasenko Andrey <andrey.installmonster@gmail.com>
 */
class PayPalWallet extends Wallet
{
    /**
     * @inheritdoc
     */
    public static $descriptor = 'paypal';

    /**
     * @inheritdoc
     */
    public function tableName()
    {
        return 'wallet';
    }

    /**
     * @inheritdoc
     */
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return CMap::mergeArray(
            parent::rules(),
            array(
                array('value', 'email'),
                array('type', 'default', 'value' => self::$descriptor),
                array('value', 'length', 'max' => 128),
            )
        );
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return CMap::mergeArray(
            parent::attributeLabels(),
            array(
                'value' => Yii::t('messages', 'PayPal email'),
            )
        );
    }
}