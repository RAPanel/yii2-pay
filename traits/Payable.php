<?php
/**
 * Created by PhpStorm.
 * User: semyonchick
 * Date: 12.01.2016
 * Time: 13:57
 */

namespace app\traits;


use Yii;

/**
 * Class Payable
 * @package app\traits
 */
trait Payable
{
    public function onPay()
    {
        if ($this->getCanPay()) {
            $this->is_payed = true;
            $this->update(false, ['is_payed']);
        }

        Yii::$app->mailer->compose()
            ->setTo(explode(',', Yii::$app->params['adminEmail']))
            ->setFrom([Yii::$app->params['fromEmail'] ?: 'no-reply@' . Yii::$app->request->hostInfo => Yii::$app->name])
            ->setSubject("Заказ №{$this->id} оплачен")
            ->setHtmlBody($this->getBody())
            ->send();

    }

    public function getCanPay()
    {
        return !$this->is_payed;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getTotal()
    {
        return $this->getItems()->select('SUM(price*quantity)')->scalar();
    }

    public function getDescription()
    {
        return 'Оплата заказа №' . $this->id;
    }

}