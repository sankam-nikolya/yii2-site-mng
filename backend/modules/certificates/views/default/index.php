<?php
use kartik\grid\GridView;
use yii\helpers\Html;
use yii\bootstrap\Modal;
use yii\bootstrap\Dropdown;
use yii\helpers\Url;

$this->title = 'Список задач';

/**
 * @var $tasks \common\models\certificates\Tasks;
 * @var $request \common\models\certificates\Requests;
 * @var $companies \common\models\main\Companies;
 * @var $new boolean;
 */
?>

<div class="row">
    <div class="col-sm-6">
        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title"><a href="<?= Url::to(['/certificates/requests', 'tasks_id' => $current_task ? $current_task->id : null, 'new' => true]) ?>" class="btn btn-default btn-sm btn-flat"><i class="fa fa-plus"></i> Добавить запрос</a></h3>
            </div>
            <div class="box-body">
                <?php
                $columns = [
                    [
                        'attribute' => 'created_at',
                        'format' =>  ['date', 'php:d.m.Y H:i']
                    ],
                    [
                        'attribute' => 'Количество запросов',
                        'value' => function($data) {
                            return $data->requestAmount;
                        }
                    ],
                    [
                        'attribute' => 'state',
                        'value' => function($data) {
                            return $data->state ? 'Активна' : 'Неактивна';
                        }
                    ],
                    [
                        'label' => '',
                        'format' => 'raw',
                        'contentOptions' =>['class' => 'menu-col skip-export'],
                        'value' => function ($data) {
                            $items[] = ['label' => 'Подробнее', 'url' => ['/certificates/requests', 'tasks_id' => $data->id]];
                            $items[] = ['label' =>'Удалить', 'url' => ['#'], 'linkOptions' =>
                                [
                                    'data-toggle' => 'modal',
                                    'data-target' => '#myModal',
                                    'onclick' => '$("#del-certificate").attr("href", "del-certificate?id='.$data->id.'");',
                                ]
                            ];

                            Modal::begin([
                                'header' => '<h2>Действительно удалить справку из списка?</h2>',
                                'id' => 'myModal'
                            ]);
                            echo '<div class="panel">Также будут удалены все задачи, связанные с данной справкой.</div>';
                            echo '<div class="text-center"><ul class="list-inline">' .
                                '<li>'.Html::a('<span class="fa fa-times"></span> Удалить', '', ['id' => 'del-certificate', 'class' => 'btn btn-sm btn-flat btn-danger']).'</li>' .
                                '<li>'.Html::a('Отменить', '', ['class' => 'btn btn-sm btn-flat btn-default ', 'data-dismiss' => "modal", 'aria-hidden' => true]).'</li>' .
                                '</ul></div>';
                            Modal::end();

                            return '<div class="dropdown"><span class="btn btn-flat menu-button dropdown-toggle" data-toggle="dropdown" ><i class="fa fa-ellipsis-v"></i></span>' .
                            Dropdown::widget([
                                'items' => $items,
                                'options' => [
                                    'class' => 'wagon-control-ul'
                                ]
                            ]).
                            '</div>';
                        },
                    ],
                ];

                echo GridView::widget([
                    'dataProvider' => $tasks,
                    'columns' => $columns,
                    'layout' => '<div class="pull-left">{summary}</div><div class="pull-right">{export}</div>{items}{pager}',

                    'toolbar' => [
                        '{export}'

                    ],
                    'export' => [
                        'options' => ['class' => 'btn-default btn-xs btn-flat'],
                    ],

                    'bordered' => true,
                    'striped' => false,
                    'condensed' => true,
                    'responsive' => false,
                    'hover' => true,

                    'exportConfig' => [
                        GridView::TEXT => [
                            'config' => [
                                'colDelimiter' => " | ",
                                'rowDelimiter' => "\r\n",
                            ]
                        ],
                        GridView::PDF => ['config' => [
                            'mode' => 'utf-8'
                        ]],
                        GridView::EXCEL => [],
                    ]
                ]);
                ?>
            </div>
        </div>
    </div>
    <div class="col-sm-6">

    </div>
</div>