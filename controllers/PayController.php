<?php

namespace app\controllers;

use Merchant;
use Yii;
use yii\web\Controller;
use yii\web\HttpException;

/**
 * Class PayController
 *
 * @property Merchant $merchant
 * @property Merchant $_merchant
 */
class PayController extends Controller
{
    public $enableCsrfValidation = false;
    private $_merchant = false;

    public function actionIndex($id)
    {
        return $this->getMerchant()->action('index');
    }

    public function getMerchant($type = null)
    {
        if ($this->_merchant === false) {
            $this->_merchant = Yii::$app->merchants->get(Yii::$app->request->get('type', $type));
            if (!$this->_merchant || !$this->_merchant->enabled)
                throw new HttpException(400, "Payment method is not allowed");
        }
        return $this->_merchant;
    }

    public function actionShow($action)
    {
        return $this->getMerchant()->action($action);
    }

    public function actionSuccess()
    {
        return $this->render('success');
    }

    public function actionFail()
    {
        return $this->render('fail');
    }
}