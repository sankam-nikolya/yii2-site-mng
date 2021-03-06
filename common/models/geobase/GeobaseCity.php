<?php

namespace common\models\geobase;

use Yii;

/**
 * This is the model class for table "geobase_city".
 *
 * @property integer $id
 * @property string $name
 * @property integer $region_id
 * @property double $latitude
 * @property double $longitude
 *
 * @property GeobaseContact[] $geobaseContacts
 */
class GeobaseCity extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'geobase_city';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'name', 'region_id', 'latitude', 'longitude'], 'required'],
            [['id', 'region_id'], 'integer'],
            [['latitude', 'longitude'], 'number'],
            [['name'], 'string', 'max' => 50],
            [['id'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'region_id' => 'Region ID',
            'latitude' => 'Latitude',
            'longitude' => 'Longitude',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGeobaseContacts()
    {
        return $this->hasMany(GeobaseContact::className(), ['geobase_city_id' => 'id']);
    }
}
