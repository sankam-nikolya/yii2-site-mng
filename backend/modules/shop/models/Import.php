<?php

namespace app\modules\shop\models;

use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use yii\imagine\Image;
use mark38\galleryManager\Gallery;
use common\models\main\Contents;
use common\models\main\Links;
use common\models\shop\ShopCharacteristics;
use common\models\shop\ShopGoods;
use common\models\shop\ShopGroups;
use common\models\shop\ShopItemCharacteristics;
use common\models\shop\ShopItems;
use common\models\gallery\GalleryGroups;
use common\models\gallery\GalleryImages;
use common\models\gallery\GalleryTypes;

class Import extends Model
{
    private $import_file;

    public function parser($import_file)
    {
        //exec ("convmv -r -f cp866 -t utf-8 --notest {$unzip_dir}");

        $this->import_file = $import_file;
        $sxe = simplexml_load_file($this->import_file);

        $groups_sxe = $sxe->xpath(Yii::$app->params['shop']['start_group_path']);
        if (count($groups_sxe)) $this->parserGroups($groups_sxe);

        $goods_sxe = $sxe->xpath('/КоммерческаяИнформация/Каталог/Товары/Товар');
        if (count($goods_sxe)) $this->parserGoods($goods_sxe);
    }

    public function parserGroups($groups_sxe, $parent=null)
    {
        foreach ($groups_sxe as $item) {
            $group = ShopGroups::findOne(['code' => $item->{'Ид'}]);
            $link = $group ? Links::findOne($group->links_id) : new Links();

            $link->categories_id = Yii::$app->params['shop']['categories_id'];
            $link->parent = $parent;
            $link->anchor = strval(isset($item->{'НаименованиеНаСайте'}) && $item->{'НаименованиеНаСайте'} ? $item->{'НаименованиеНаСайте'} : $item->{'Наименование'});
            $link->name = isset($item->{'URI'}) && $item->{'URI'} ? strval($item->{'URI'}) : $link->anchor2translit($link->anchor);
            $link_name = $link->name;
            $num = 1;
            if (isset($link->id)) {
                while (Links::findByUrlForLink($link->name, $link->id, $parent)) {
                    $num += 1;
                    $link->name = $link_name.'-'.$num;
                }
            } else {
                while (Links::findByUrl($link->name, $parent)) {
                    $num += 1;
                    $link->name = $link_name.'-'.$num;
                }
            }
            $link->level = $parent !== null ? Links::findOne($parent)->level + 1 : 1;
            $link->child_exist = 1;
            $link->url = $parent !== null ? Links::findOne($parent)->url.'/'.$link->name : '/'.$link->name;
            $link->seq = isset($link->id) ? $link->seq : Links::findLastSequence(Yii::$app->params['shop']['categories_id'], $parent) + 1;
            $link->title = isset($link->id) ? $link->title : $link->anchor;
            $link->created_at = isset($link->id) ? $link->created_at : time();
            $link->updated_at = time();
            $link->state = $item->{'НеПубликуетсяНаСайте'} == 'истина' ? 0 : 1;
            $link->layouts_id = Yii::$app->params['shop']['group_layouts_id'];
            $link->save();

            if ($item->{'Картинки'} && $item->{'Картинки'}->{'Картинка'}) {
                //$this->addImage($item->{'Картинки'}->{'Картинка'}, Yii::$app->params['shop']['gallery']['group'], $link->id);
            }

            if (!Contents::findOne(['links_id' => $link->id])) {
                $content = new Contents();
                $content->links_id = $link->id;
                $content->seq = 1;
                $content->save();
            }

            if (!$group) {
                $group = new ShopGroups();
                $group->links_id = $link->id;
                $group->code = strval($item->{'Ид'});
            };
            $group->name = strval($item->{'Наименование'});
            $group->save();

            if ($item->{'Группы'}->{'Группа'}) {
                $this->parserGroups($item->{'Группы'}->{'Группа'}, $link->id);
            }
        }

        return true;
    }

    public function parserGoods($goods_sxe)
    {
        $goods = array();
        foreach ($goods_sxe as $item) {
            $item_code = false;
            if (preg_match('/(.+)#(.+)/', $item->{'Ид'}, $matches)) {
                $good_code = strval($matches[1]);
                $item_code = strval($matches[2]);
            } else {
                $good_code = strval($item->{'Ид'});
            }

            if (!isset($goods[$good_code])) {
                $goods[$good_code] = array();
                $goods[$good_code] = $this->addGood($item, $good_code);
            }

            if ($item_code) {
                $goods[$good_code]['items'][$item_code] = $this->addItem($goods[$good_code]['id'], $item_code, $item);
            }
        }

        foreach ($goods as $good_code => $good) {
            $items = ShopItems::findAll(['shop_goods_id' => $good['id']]);
            if (isset($good['items'])) {
                foreach ($items as $item) {
                    if (!isset($good['items'][$item->code])) {
                        $item->state = 0;
                        $item->save();
                    }
                }
            } else {
                foreach ($items as $item) {
                    $item->state = 0;
                    $item->save();
                }
            }
        }
    }

    private function addGood($item, $code)
    {
        $group = ShopGroups::findOne(['code' => strval($item->{'Группы'}->{'Ид'})]);
        $good = ShopGoods::findOne(['code' => $code]);
        $link = $good ? Links::findOne($good->links_id) : new Links();

        $link->categories_id = Yii::$app->params['shop']['categories_id'];
        $link->parent = $group->links_id;
        $link->anchor = strval(isset($item->{'НаименованиеНаСайте'}) && $item->{'НаименованиеНаСайте'} ? $item->{'НаименованиеНаСайте'} : $item->{'Наименование'});
        $link->name = isset($item->{'URI'}) && $item->{'URI'} ? strval($item->{'URI'}) : $link->anchor2translit($link->anchor);
        $link_name = $link->name;
        $num = 1;
        if (isset($link->id)) {
            while (Links::findByUrlForLink($link->name, $link->id, $link->parent)) {
                $num += 1;
                $link->name = $link_name.'-'.$num;
            }
        } else {
            while (Links::findByUrl($link->name, $link->parent)) {
                $num += 1;
                $link->name = $link_name.'-'.$num;
            }
        }
        $link->level = $group->link->level + 1;
        $link->child_exist = 0;
        $link->url = $group->link->url.'/'.$link->name;
        $link->seq = isset($link->id) ? $link->seq : Links::findLastSequence(Yii::$app->params['shop']['categories_id'], $link->parent) + 1;
        $link->title = isset($link->id) ? $link->title : $link->anchor;
        $link->created_at = isset($link->id) ? $link->created_at : time();
        $link->updated_at = time();
        $link->state = $item->{'НеПубликуетсяНаСайте'} == 'истина' ? 0 : 1;
        $link->layouts_id = Yii::$app->params['shop']['good_layouts_id'];
        $link->save();

        $content = Contents::findOne(['links_id' => $link->id]);
        if (!$content) {
            $content = new Contents();
            $content->links_id = $link->id;
            $content->seq = 1;
        }
        $content->text = strval($item->{'Описание'});
        $content->save();

        if (!$good) {
            $good = new ShopGoods();
            $good->links_id = $link->id;
            $good->shop_groups_id = $group->id;
            $good->code = $code;
        }
        $good->name = strval($item->{'Наименование'});
        $good->save();

        if ($item->{'Картинки'} && $item->{'Картинки'}->{'Картинка'}) {
            $this->addImage($item->{'Картинки'}->{'Картинка'}, Yii::$app->params['shop']['gallery']['good'], $link->id, $good->id);
        }

        return [
            'id' => $good->id,
            'links_id' => $link->id,
        ];
    }

    private function addItem($goods_id, $code, $item_sxe)
    {
        $item = ShopItems::findOne(['code' => $code]);
        if (!$item) {
            $item = new ShopItems();
            $item->shop_goods_id = $goods_id;
            $item->code = $code;
        }
        $item->state = 1;
        $item->save();

        foreach (ShopItemCharacteristics::findAll(['shop_items_id' => $item->id]) as $item_characteristic) {
            $item_characteristic->state = 0;
            $item_characteristic->save();
        }

        foreach ($item_sxe->{'ХарактеристикиТовара'}->{'ХарактеристикаТовара'} as $characteristic_sxe) {
            $characteristic = ShopCharacteristics::findOne(['name' => strval($characteristic_sxe->{'Наименование'})]);
            if (!$characteristic) {
                $characteristic = new ShopCharacteristics();
                $characteristic->name = strval($characteristic_sxe->{'Наименование'});
                $characteristic->save();
            }

            $item_characteristic = ShopItemCharacteristics::findOne(['shop_items_id' => $item->id, 'shop_characteristics_id' => $characteristic->id]);
            if (!$item_characteristic) {
                $item_characteristic = new ShopItemCharacteristics();
                $item_characteristic->shop_items_id = $item->id;
                $item_characteristic->shop_characteristics_id = $characteristic->id;
            }
            $item_characteristic->name = strval($characteristic_sxe->{'Значение'});
            $item_characteristic->state = 1;
            $item_characteristic->save();
        }

        return [
            'id' => $item->id,
        ];
    }

    private function addImage($item, $src_gallery_types_id, $links_id, $shop_goods_id=null)
    {
        $gallery_groups = array();
        foreach ($item as $image_sxe) {
            $basename_src = basename(strval($image_sxe->{'ПутьКИзображению'}));
            if (!$basename_src) continue;

            $src_image = pathinfo($this->import_file)['dirname'].'/'.strval($image_sxe->{'ПутьКИзображению'});
            if (!is_file($src_image)) continue;

            preg_match('/_(.+)\s\[(.+)\]/', $basename_src, $matches);
            if ($matches) {
                $gallery_types_id = isset(Yii::$app->params['shop']['gallery'][$matches[1]]) ? Yii::$app->params['shop']['gallery'][$matches[1]] : $src_gallery_types_id;
                $alt = $matches[2];
            } else {
                $gallery_types_id = $src_gallery_types_id;
                $alt = '';
            }

            $gallery_type = GalleryTypes::findOne($gallery_types_id);
            $this_image_link = false;

            if (in_array($gallery_types_id, Yii::$app->params['shop']['gallery_link'])) {
                $link = Links::findOne($links_id);
                $gallery_groups[$gallery_types_id] = $link->galleryGroup;
                $this_image_link = true;
            }

            if (!GalleryImages::findOne(['name' => $basename_src])) {
                if (!isset($gallery_groups[$gallery_types_id])) {
                    $gallery_group = new GalleryGroups();
                    $gallery_group->gallery_types_id = $gallery_type->id;
                    $gallery_group->save();
                    $gallery_groups[$gallery_types_id] = $gallery_group;
                }

                $image = $this->saveImage($src_image, $gallery_types_id, $gallery_groups[$gallery_types_id]->id);
                if (isset($image['small']) && isset($image['large'])) {
                    $gallery_image = new GalleryImages();
                    $gallery_image->gallery_groups_id = $gallery_groups[$gallery_types_id]->id;
                    $gallery_image->small = $image['small'];
                    $gallery_image->large = $image['large'];
                    $gallery_image->name = $basename_src;
                    $gallery_image->alt = $alt;
                    $gallery_image->seq = $image['seq'];
                    $gallery_image->save();
                }
            } else {
                $gallery_image = GalleryImages::findOne(['name' => $basename_src]);
                $gallery_groups[$gallery_types_id] = GalleryGroups::findOne($gallery_image->gallery_groups_id);
            }

            if ($image_sxe->{'ОсновноеИзображение'} == 1) {
                if ($gallery_groups[$gallery_types_id]->gallery_images_id != $gallery_image->id) {
                    $gallery_groups[$gallery_types_id]->gallery_images_id = $gallery_image->id;
                    $gallery_groups[$gallery_types_id]->save();
                }

                if ($this_image_link && $link->gallery_images_id != $gallery_image->id) {
                    $link->gallery_images_id = $gallery_image->id;
                    $link->save();
                }
            }
        }
    }

    public function saveImage($src_image, $gallery_types_id, $gallery_groups_id)
    {
        $type = GalleryTypes::find()->where(['id' => $gallery_types_id])->asArray()->one();

        $gallery = new Gallery();
        $size = $gallery->getSize($src_image, $type);
        $path = Yii::getAlias('@frontend/web').$type['destination'];
        $file_info = new \SplFileInfo($src_image);

        $image_small = $gallery->renderFilename($path, $file_info->getExtension());
        $image_large = $gallery->renderFilename($path, $file_info->getExtension());

        Image::thumbnail($src_image, $size['small_width'], $size['small_height'])->save($path.'/'.$image_small.'.'.$file_info->getExtension(), ['quality' => $type['quality']]);
        Image::thumbnail($src_image, $size['large_width'], $size['large_height'])->save($path.'/'.$image_large.'.'.$file_info->getExtension(), ['quality' => $type['quality']]);

        return [
            'small' => $type['destination'].'/'.$image_small.'.'.$file_info->getExtension(),
            'large' => $type['destination'].'/'.$image_large.'.'.$file_info->getExtension(),
            'seq' => ($gallery->getLastSequence($gallery_groups_id) + 1)
        ];
    }
}