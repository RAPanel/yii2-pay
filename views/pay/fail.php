<?php
/**
 * Created by PhpStorm.
 * User: semyonchick
 * Date: 13.01.2016
 * Time: 18:22
 */

/**
 * @var \yii\web\View $this
 */

$this->title = 'В процесе оплаты возникла ошибка';

$id = null;
if (isset($_GET['orderNumber'])) $id = $_GET['orderNumber'];

?>

<h1><?= $this->title ?></h1>

<p>
    Для повторной оплаты заказа
    перейдите <?= \yii\helpers\Html::a('по ссылке', ['pay/index', 'type' => 'yandex', 'id' => $_GET['orderNumber']]) ?>
</p>
