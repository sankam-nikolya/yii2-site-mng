<?php

namespace app\modules\sms\models;

use Yii;
use common\models\sms\SmsContent;
use common\models\sms\SmsSend;
use common\models\sms\SmsContacts;
use common\models\sms\SmsSendContacts;

class SmsContentForm extends SmsContent
{
    public $contacts = '';

    public function rules()
    {
        return array_merge(parent::rules(), [
            [['contacts'], 'string'],
        ]);
    }
    
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'contacts' => 'Контакты не включенные в список'
        ]);
    }

    public function afterFind()
    {
        parent::afterFind(); // TODO: Change the autogenerated stub

        if ($this->id) {
            $send = SmsSend::findOne(['sms_content_id' => $this->id, 'status' => false]);
            if ($send) {
                $sendContacts = SmsSendContacts::find()->innerJoinWith(['smsContact'])->where(['sms_contacts.control' => false, 'sms_send_id' => $send->id])->all();
                if ($sendContacts) {
                    $this->contacts = '';
                    foreach ($sendContacts as $i => $sendContact) {
                        $this->contacts .= '+'.$sendContact->smsContact->phone;
                        $fio = $sendContact->smsContact->name;
                        $fio .= $fio && $sendContact->smsContact->surname ? ' '.$sendContact->smsContact->surname : '';
                        $fio .= $fio && $sendContact->smsContact->patronymic ? ' '.$sendContact->smsContact->patronymic : '';
                        $this->contacts .= $fio ? ':'.$fio : '';
                        $gender = $sendContact->smsContact->male ? 'м' : '';
                        $gender = $sendContact->smsContact->female ? 'ж' : $gender;
                        $this->contacts .= $gender ? ':'.$gender : '';

                        if ($i < count($sendContacts) - 1) $this->contacts .= ', ';
                    }
                }
            }
        }
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes); // TODO: Change the autogenerated stub

        $send = SmsSend::findOne(['sms_content_id' => $this->id, 'status' => 0]);
        if (!$send) {
            $send = new SmsSend();
            $send->sms_content_id = $this->id;
            $send->save();
        }

        SmsSendContacts::deleteAll(['sms_send_id' => $send->id]);

        if ($this->contact_send) {
            $smsContacts = SmsContacts::find()->where(['control' => true, 'state' => true])->all();
            if ($smsContacts) {
                /** @var SmsContacts $smsContact */
                foreach ($smsContacts as $smsContact) {
                    $smsSendContact = new SmsSendContacts();
                    $smsSendContact->sms_send_id = $send->id;
                    $smsSendContact->sms_contacts_id = $smsContact->id;
                    $smsSendContact->save();
                }
            }
        }

        /** Не зарегистрированные контакты */
        if ($this->contacts) {
            $contacts = preg_split('/,|,\s/', $this->contacts);
            if ($contacts) {
                foreach ($contacts as $contact) {
                    $fio = '';
                    $surname = '';
                    $patronymic = '';
                    $gender = '';
                    $contactElements = preg_split('/\:|\:\s/', trim($contact));
                    if ($contactElements) {
                        $phone = $contactElements[0];
                        $fio = isset($contactElements[1]) ? $contactElements[1] : '';
                        $gender = isset($contactElements[2]) ? $contactElements[2] : '';
                    } else {
                        $phone = $contact;
                    }

                    $phone = preg_replace('/[^0-9]/', '', $phone);
                    if (strlen($phone) == 11 && substr($phone, 0, 1) == 8) {
                        $phone = '7'.substr($phone, 1);
                    }

                    $smsContact = SmsContacts::findOne(['phone' => $phone]);
                    $newContact = true;
                    $updateContact = false;
                    if ($smsContact) {
                        if (!$smsContact->control) {
                            $updateContact = true;
                        } else {
                            $newContact = false;
                        }
                    }

                    if ($newContact) {
                        if (!$updateContact) $smsContact = new SmsContacts();

                        $fioParams = preg_split('/\s/', $fio);
                        if (count($fioParams) == 1) {
                            $name = $fioParams[0];
                        } elseif (count($fioParams) == 2) {
                            $name = $fioParams[0];
                            $surname = $fioParams[1];
                        } else {
                            $name = $fioParams[0];
                            $surname = $fioParams[1];
                            $patronymic = $fioParams[2];
                        }
                        $smsContact->phone = $phone;
                        $smsContact->name = $name;
                        $smsContact->surname = $surname;
                        $smsContact->patronymic = $patronymic;
                        if (!$gender) {
                            $smsContact->gender = 0;
                        } else {
                            $smsContact->gender = $gender == 'м' ? 1 : 2;
                        }
                        $smsContact->control = 0;

                        if ($smsContact->validate()) {
                            $smsContact->save();
                        } else {
                            var_dump($smsContact);
                        }
                    }

                    $smsSendContact = new SmsSendContacts();
                    $smsSendContact->sms_send_id = $send->id;
                    $smsSendContact->sms_contacts_id = $smsContact->id;
                    $smsSendContact->save();
                }
            }
        }
    }
}