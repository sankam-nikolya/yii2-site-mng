<?php

namespace app\modules\map\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\Url;
use yii\web\Controller;
use common\models\main\Categories;
use common\models\main\Links;
use common\models\main\Contents;
use mark38\galleryManager\GalleryManagerAction;

class DefaultController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'actions' => ['error'],
                        'allow' => true,
                    ],
                    [
                        'actions' => ['index', 'links', 'link-del', 'content', 'save-content', 'gallery-manager'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    public function actions()
    {
        return [
            'gallery-manager' => [
                'class' => GalleryManagerAction::className(),
            ],
        ];
    }

    public function actionIndex()
    {
        return $this->render('index');
    }

    public function actionLinks($categories_id=null)
    {
        $category = Categories::findOne($categories_id);
        $link = new Links();
        $link->categories_id = $categories_id;

        if (Yii::$app->request->get('mng_link') == 'ch') {
            $link = Links::findOne(Yii::$app->request->get('links_id'));
        }

        if ($link->load(Yii::$app->request->post()) && $link->save()) {
            if (!$link->contents) {
                $content = new Contents();
                $content->links_id = $link->id;
                $content->seq = 1;
                $content->save();
            }
            Yii::$app->getSession()->setFlash('success', 'Изменения приняты');
        }

        return $this->render('links', [
            'category' => $category,
            'link' => $link,
        ]);
    }

    public function actionLinkDel($links_id, $categories_id=null)
    {
        $link = Links::findOne($links_id);
        if ($link) {
            Yii::$app->getSession()->setFlash('success', 'Ссылка удалена');
            $link->delete();
        }

        return $this->redirect(['/map/links', 'categories_id' => $categories_id]);
    }

    public function actionContent($links_id, $id=null)
    {
        $link = Links::findOne($links_id);

        if ( Yii::$app->request->get('action') ) {
            switch (Yii::$app->request->get('action')) {
                case "add":
                    $content = new Contents();
                    $content->links_id = $link->id;
                    $content->seq = $content->findLastSequence($link->id, $id) + 1;
                    $content->save();
                    break;
                case "del":
                    Contents::deleteAll(['id' => $id]);
                    (new Contents())->reSort($link->id);
                    break;
            }

            return $this->redirect(Url::current(['action' => null, 'id' => null]));
        }

        $contents = Contents::find()->where(['links_id' => $links_id])->orderBy(['seq' => SORT_ASC])->all();

        if (Yii::$app->request->post()) {
            foreach ($contents as $index => $content) {
                if (Yii::$app->request->post('content-'.$index)) {
                    $contents[$index]->load(Yii::$app->request->post());
                    $contents[$index]->text = Yii::$app->request->post('content-'.$index);
                    $contents[$index]->save();
                }
            }
        }

        return $this->render('content', [
            'link' => $link,
            'contents' => $contents
        ]);
    }

    public function actionSaveContent($links_id, $categories_id=null)
    {
        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

            $contents = Contents::find()->where(['links_id' => $links_id])->orderBy(['seq' => SORT_ASC])->all();
            if (Yii::$app->request->post()) {
                foreach ($contents as $index => $content) {
                    if (Yii::$app->request->post('content-'.$index)) {
                        $contents[$index]->load(Yii::$app->request->post());
                        $contents[$index]->text = Yii::$app->request->post('content-'.$index);
                        $contents[$index]->save();
                    }
                }
            }

            return [
                'flash' => 'success',
                'message' => 'Изменения приняты'
            ];
        }
    }
}
