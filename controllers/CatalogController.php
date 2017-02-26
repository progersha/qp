<?php

namespace app\controllers;

use app\models\Good\GoodProperty;
use app\models\Good\Menu;
use app\models\Good\Good;
use app\models\Bookmark;
use app\models\Good\PropertyValue;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;

use Yii;
use yii\caching\TagDependency;

class CatalogController extends \yii\web\Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['add-bookmark', 'delete-bookmark'],
                'denyCallback' => function($role, $action) {
                    Yii::$app->session->setFlash('warning', 'Необходимо авторизоваться.');
                    $this->redirect('/site/login');
                },
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['user'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'add-bookmark' => ['POST'],
                    'delete-bookmark' => ['POST'],
                    'search-data' => ['POST'],
                ],
            ],
        ];
    }
    public $defaultAction = 'view';

    public function actionView($id = null)
    {

        $catalog = isset($id) ? Menu::findOneOr404($id) : Menu::getRoot();
        if(Yii::$app->db->cache(function ($db) use($catalog)
        {
            return $catalog->children(1)->all();
        }, null, new TagDependency(['tags' => 'cache_table_' . Menu::tableName()]))) {
            return $this->render('view', [ 'catalog' => $catalog ]);
        }
        //$this->layout = "products";
        return $this->actionProducts($catalog->id);
    }

    public function actionProducts($cid)
    {
        $products = Yii::$app->db->cache(function ($db) use($cid)
        {
            return Good::find()->joinWith('bookmark')->where([ 'category_id' => $cid ])->all();
        }, null, new TagDependency([
            'tags'=> [
                'cache_table_' . Good::tableName(),
                'cache_table_' . Bookmark::tableName(),
            ]]));
        $products_copy = $products;

        $get = Yii::$app->request->get();
        if(isset($get['f'])) {
            $fs = $get['f'];
            $fs = explode(';', $fs);
            $fs = array_filter($fs, function ($f) { return $f !== ''; });

            foreach($fs as $f) {
                list($prop, $values) = explode(':', $f);
                if ($prop == 'p') {
                    list($min, $max) = explode('-', $values);
                    $products = array_filter($products, function ($prod) use ($min, $max) {
                        return (int)$min <= $prod->price && $prod->price <= (int)$max;
                    });
                }
                else {
                    $products = array_filter($products, function ($prod) use ($prop, $values) {
                        $values = explode(',', $values);
                        return isset($prod->properties[$prop]) and in_array($prod->properties[$prop], $values);
                    });
                }
            }
            if (isset($get['ajax'])) {
                foreach ($products as $product) {
                    echo \app\components\catalog\ProductWidget::widget([
                        'product' => $product,
                    ]);
                }
                return null;
            }
        }

        $filters = [];
        $prices = [];
        if ($products) {

            $fst_prod = array_shift($products_copy);
            $common_props = $fst_prod->properties;
            foreach ($fst_prod->properties as $name => $pr) {
                $common_props[$name] = [ $common_props[$name] ];
                $prices = [$fst_prod->price];
            }

            foreach ($products_copy as $prod) {
                foreach ($common_props as $name => &$pr) {
                    if (isset($prod->properties[$name])) {
                        array_push($pr, $prod->properties[$name]);
                    }
                    else {
                        unset($common_props[$name]);
                    }
                }
                $prices[] = $prod->price;
            }
            foreach ($common_props as $prop => $value) {
                $prop_model = GoodProperty::cachedFindOne($prop);
                $filters[] = [
                    'prop_id' => $prop,
                    'prop_name' => $prop_model->name,
                    'values' => array_map(function ($x) {
                       return [
                           'value_id' => $x,
                           'value_name' => PropertyValue::cachedFindOne($x)->value
                       ];
                    }, $value)
                ];
            }
        }

        $category = Menu::findOneOr404($cid);

        return $this->render('/product/index', [
            'products' => $products,
            'category' => $category,
            'filters' => $filters,
            'prices' => $prices,
        ]);
    }

    public function actionSearchData() {
        $get = Yii::$app->request->post();

        $selector = function($p) { return [ 'id' => $p->id, 'label' => $p->name ]; };
        if (Yii::$app->request->isAjax) {
            $data = [
                'products' => array_map($selector, Good::cachedFindAll()),
                'categories' => array_map($selector, Menu::cachedFindAll()),
            ];
            echo json_encode($data);
        }
    }

    public function actionAddBookmark()
    {
        $get = Yii::$app->request->post();
        if (Yii::$app->request->isAjax) {
            $model = new Bookmark([
                'user_id' => Yii::$app->user->getId(),
                'product_id' => $get['product_id']
            ]);
            if($model->save()) {
                return Bookmark::cachedGetCount(['product_id' => $get['product_id']]) ;
            }
            return "Error: ".$get['product_id'];
        }

        return "Error";
    }

    public function actionDeleteBookmark()
    {
        $get = Yii::$app->request->post();
        if (Yii::$app->request->isAjax) {
            if (($model = Bookmark::cachedFindOne([
                    'user_id' => Yii::$app->user->id,
                    'product_id' => $get['product_id'],
                ]))
                && $model->delete()) {
                return "Delete";
            }
            return "Error";
        }

        return "Error";
    }
}
