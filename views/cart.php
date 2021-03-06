<?php

use app\components\Html;
use yii\grid\GridView;

/** @var $this yii\web\View */
/** @var $dataProvider yii\data\ArrayDataProvider */
/** @var $cart \yz\shoppingcart\ShoppingCart */

$this->title = 'Корзина';
?>
<main class="cart" id="app" data-stage="cart">
<?php if($dataProvider->getTotalCount()) : ?>
    <h1><?= Html::encode($this->title) ?></h1>
    <div class="product__table">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'columns' => [
                [
                    'class' => 'yii\grid\SerialColumn'
                ], [
                    'format' => 'html',
                    'contentOptions' => ['class' => 'cell-img'],
                    'value' => function ($cart_position) {
                        /* @var $cart_position app\models\Good\ProductCartPosition */
                        return  Html::img([ $cart_position->getProduct()->getImgPath() ],
                            [ 'height'=>100, 'width'=>100, 'class'=>'img-responsive' ]
                        );
                    }
                ],
                [
                    'attribute' => 'Название',
                    'format' => 'raw',
                    'value' => function ($cart_position) {
                        /* @var $cart_position app\models\Good\ProductCartPosition */
                        return  $cart_position->getProduct()->name;
                    }
                ],
                [
                    'attribute' => 'Количество',
                    'format' => 'raw',
                    'value' => function ($cart_position) {
                        /* @var $cart_position app\models\Good\ProductCartPosition */
                        return  Html::stepper($cart_position->id, $cart_position->getQuantity());
                    }
                ], [
                    'attribute' => 'Цена',
                    'format' => 'raw',
                    'value' => function ($cart_position) {
                        /* @var $cart_position app\models\Good\ProductCartPosition */
                        return $this->render('/order/_itemPrice', ['cart_position' => $cart_position]);
                    }
                ], [
                    'class' => 'yii\grid\ActionColumn',
                    'visibleButtons' => [ 'update' => false, 'view' => false],
                    'visibleButtons' => [ 'update' => false, 'view' => false],
                    'template' => '{delete}',
                    'buttons' => [
                        'delete' => function ($url,$model) {
                            return Html::a(
                                '<i class="fa fa-close"></i>',
                                $url, ['class' => 'remove', 'data-method' => 'post',
                                    'title' => 'Удалить', 'aria-label' => 'Удалить']);
                        },
                    ],
                ],
            ]
        ]) ?>
        <div class="cart-total">
            <span class="label">
                Итого:
            </span>
            <span id="total" class="price">
                <?=Html::price($cart->getCost())?>
            </span>
        </div>
    </div>
    <div class="cart-btn">
        <?= Html::a('Оформить заказ', ['order'], [
            'class' => 'btn btn-success btn-lg ',
        ]) ?>
    </div>

<?php else: ?>
    <div class="cart-empty row">
        <div class="cart-empty__icon col-sm-3">
            <?=Html::img('@web/img/components/cart-empty.gif');?>
        </div>
        <div class="col-sm-9">
            <h2>Корзина пуста :(</h2>
            <br/>
            <p><b>В корзине нет ни одного товара или услуги</b></p>
            <p>Если вы считаете, что это ошибка, обратитесь в IT-отдел компании: <?=Yii::$app->params['phone.it']?></p>

        </div>
    </div>
<?php endif; ?>

</main>
