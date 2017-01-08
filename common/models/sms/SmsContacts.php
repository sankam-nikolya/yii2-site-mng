<?php

namespace common\models\sms;

use Yii;

/**
 * This is the model class for table "sms_contacts".
 *
 * @property integer $id
 * @property string $phone
 * @property string $name
 * @property string $surname
 * @property string $patronymic
 * @property integer $female
 * @property string $male
 * @property integer $control
 * @property integer $state
 *
 * @property SmsSendContacts[] $smsSendContacts
 */
class SmsContacts extends \yii\db\ActiveRecord
{
    public $genders = ['Не важно', 'Мужской', 'Женский'];
    public $gender;
    public $state = true;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'sms_contacts';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['female', 'control', 'state'], 'integer'],
            [['phone', 'name', 'surname', 'patronymic', 'male'], 'string', 'max' => 255],
            [['phone'], 'unique'],
            [['gender'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'phone' => 'Phone',
            'name' => 'Имя',
            'surname' => 'Фамилия',
            'patronymic' => 'Отчество',
            'female' => 'Female',
            'male' => 'Male',
            'control' => 'Control',
            'gender' => 'Пол',
            'state' => 'Доставлять SMS-сообщения',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSmsSendContacts()
    {
        return $this->hasMany(SmsSendContacts::className(), ['sms_contacts_id' => 'id']);
    }

    public function beforeSave($insert)
    {
        $this->phone = preg_replace('/[^0-9]/', '', $this->phone);
        if (strlen($this->phone) == 11 && substr($this->phone, 0, 1) == 8) {
            $this->phone = '7'.substr($this->phone, 1);
        }

        switch ($this->gender) {
            case 0: $this->female = 0; $this->male = 0; break;
            case 1: $this->female = 0; $this->male = 1; break;
            case 2: $this->female = 1; $this->male = 0; break;
        }

        return parent::beforeSave($insert); // TODO: Change the autogenerated stub
    }
}
