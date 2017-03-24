<?php

namespace app\controllers;

use app\models\OrderProduct;
use Yii;
use app\models\Order;
use yii\caching\TagDependency;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class ManagerController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['manager'],
                    ],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => Order::find()->joinWith('user'),
        ]);
        Yii::$app->db->cache(function ($db) use ($dataProvider) {
            $dataProvider->prepare();
        }, null, new TagDependency(['tags' => 'cache_table_' . Order::tableName()]));

        if ($pass = Yii::$app->request->post('password')) {
            if ($order = Order::findOneOr404([
                    'password' => $pass,
                    'status' => Order::STATUS_PAID,
                ])
                ) {
                Yii::$app->session->setFlash('success', 'Заказ ' . $order->public_id  . ' выдан.');
                return $this->redirect([ 'view-order', 'id' => $order->id ]);
            }
            Yii::$app->session->setFlash('error', 'Неверный код подтверждения.');
        }

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionViewOrder($id) {
        $order = Order::findOneOr404($id);
        $products = Yii::$app->db->cache(function ($db) use ($order) {
            return $order->orderProducts;
        }, null, new TagDependency(['tags' => 'cache_table_' . OrderProduct::tableName()]));

        return $this->render('view-order', [
            'products' => $products,
            'order' => $order,
        ]);
    }
}
