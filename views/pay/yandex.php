<?php
/**
 * Created by PhpStorm.
 * User: semyonchick
 * Date: 12.01.2016
 * Time: 13:53
 */

/**
 * @var \yii\web\View $this
 * @var \app\components\YandexMerchant $merchant
 * @var \app\models\Order|\app\components\IPayable $model
 */
use yii\helpers\Html;

if (Yii::$app->request->get('autoSend'))
    $this->registerJs('$("#yandexForm").submit()');
?>

<?= Html::beginForm($merchant->getPaymentUrl(), 'post', ['id' => 'yandexForm']) ?>

<?= Html::hiddenInput('shopId', $merchant->shopId) ?>
<?= Html::hiddenInput('scId', $merchant->scId) ?>
<?= Html::hiddenInput('sum', $model->getTotal()) ?>
<?= Html::hiddenInput('shopSuccessURL', \yii\helpers\Url::to(['pay/success'], 1)) ?>
<?= Html::hiddenInput('shopFailURL', \yii\helpers\Url::to(['pay/fail'], 1)) ?>
<? //= Html::hiddenInput('customerNumber', $model->user_id) ?>

<?php if ($phone = $model->phone): ?>
    <?= Html::hiddenInput('cps_phone', $phone) ?>
<?php endif; ?>

<?php if ($email = $model->email): ?>
    <?= Html::hiddenInput('cps_email', $email) ?>
<?php endif; ?>

<?php if ($orderId = $model->id): ?>
    <?= Html::hiddenInput('orderNumber', $orderId) ?>
<?php endif; ?>

<?= Html::dropDownList('paymentType', Yii::$app->request->get('paymentType'), $merchant->getAllowedPaymentTypes(), ['prompt'=>'Выберите желаемый способ оплаты ...']) ?>

<?= Html::submitButton("Оплатить") ?>
<?= Html::endForm() ?>