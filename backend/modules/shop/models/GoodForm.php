<?php
namespace backend\modules\shop\models;

use Yii;
use yii\helpers\ArrayHelper;
use common\models\shop\ShopGoods;
use common\models\shop\ShopGroupProperties;
use common\models\shop\ShopUnits;
use common\models\shop\ShopGoodProperties;
use common\models\shop\ShopPriceGood;
use common\models\shop\ShopPropertyValues;

class GoodForm extends ShopGoods
{
    public $propertyValues;
    public $priceValues;
    public $units;

    public function rules()
    {
        return array_merge(parent::rules(), [
            ['propertyValues', 'each', 'rule' => ['string']],
            ['priceValues', 'each', 'rule' => ['double']],
        ]);
    }

    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'shop_units_id' => 'Еденица измерения',
            'code' => 'Код товара',
        ]);
    }

    public function init()
    {
        parent::init(); // TODO: Change the autogenerated stub

        $this->units = ArrayHelper::map(ShopUnits::find()->all(), 'id', 'name');
        $this->units[] = 'Не указано';
    }

    public function afterFind()
    {
        parent::afterFind(); // TODO: Change the autogenerated stub

        /** @var ShopGoodProperties $goodProperty */
        foreach ($this->shopGoodProperties as $goodProperty) {
            $this->propertyValues[$goodProperty->shop_properties_id] = $goodProperty->shopPropertyValue->name;
        }

        /** @var ShopPriceGood $priceGood */
        foreach ($this->shopPriceGoods as $priceGood) {
            $this->priceValues[$priceGood->shop_price_types_id] = $priceGood->price;
        }
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes); // TODO: Change the autogenerated stub

        ShopGoodProperties::deleteAll(['shop_goods_id' => $this->id]);

        foreach ($this->propertyValues as $shop_properties_id => $anchor) {
            $shopPropertyValue = ShopPropertyValues::find()
                ->where(['shop_properties_id' => $shop_properties_id])
                ->andWhere(['anchor' => $anchor])
                ->one();
            if (!$shopPropertyValue) {
                $shopPropertyValue = new ShopPropertyValues();
                $shopPropertyValue->shop_properties_id = $shop_properties_id;
                $shopPropertyValue->name = $anchor;
                $shopPropertyValue->anchor = $anchor;
                $shopPropertyValue->save();
            }

            $shopGoodProperty = new ShopGoodProperties();
            $shopGoodProperty->shop_goods_id = $this->id;
            $shopGoodProperty->shop_properties_id = $shop_properties_id;
            $shopGoodProperty->shop_property_values_id = $shopPropertyValue->id;
            $shopGoodProperty->state = true;
            $shopGoodProperty->save();
        }

        self::updatePrices();
    }

    private function updatePrices()
    {
        if ($this->priceValues) {
            foreach ($this->priceValues as $shopPriceTypesId => $priceValue) {
                $priceGood = ShopPriceGood::findOne(['shop_price_types_id' => $shopPriceTypesId, 'shop_goods_id' => $this->id]);
                if (!$priceGood) {
                    $priceGood = new ShopPriceGood();
                    $priceGood->shop_price_types_id = $shopPriceTypesId;
                    $priceGood->shop_goods_id = $this->id;
                }
                $priceGood->price = $priceValue;
                $priceGood->save();
            }
        }
    }
}