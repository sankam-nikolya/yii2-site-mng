<?php
namespace backend\modules\shop\models;

use common\models\helpers\Translit;
use Yii;
use common\models\main\Links;

class LinkGoodForm extends Links
{
    public $state = true;

    public function init()
    {
        parent::init(); // TODO: Change the autogenerated stub

        $this->parent = Yii::$app->request->get('parent');
        $this->categories_id = Yii::$app->params['shop']['categoriesId'];
    }

    public function rules()
    {
        return array_merge(parent::rules(),[
//            [['text'], 'string'],
        ]);
    }

    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'state' => 'Активная (опубликованная) группа',
            'anchor' => 'Наименование',
        ]);
    }

    public function beforeSave($insert)
    {
        $translit = new Translit();
        if (!$this->name) $this->name = isset($this->id) ? $translit->slugify($this->anchor, $this->tableName(), 'name', '-', $this->id) : $translit->slugify($this->anchor, $this->tableName(), 'name', '-', null);
        if (!$this->title) $this->title = $this->anchor;
        if (!$this->seq) $this->seq = isset($this->id) ? $this->seq : Links::findLastSequence(Yii::$app->params['shop']['categoriesId'], $this->parent) + 1;
        $this->created_at = isset($this->id) ? $this->created_at : time();
        $this->updated_at = time();


        return parent::beforeSave($insert); // TODO: Change the autogenerated stub
    }
}