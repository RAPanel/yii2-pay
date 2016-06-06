<?php

namespace app\components;

use Yii;
use yii\web\Response;

/**
 * Class YandexMerchant
 *
 * @property array $paymentTypes
 * @property string $paymentUrl
 */
class YandexMerchant extends \yii\base\Component
{

    public $shopId;
    public $scId;
    public $shopPassword;
    public $securityType = 'MD5';
    public $modelClass = 'app\models\Order';
    public $allowedPaymentTypes = array();

    public $demo = false;
    public $enabled = true;
    private $action;

    public function init()
    {
        if (empty($this->allowedPaymentTypes))
            $this->allowedPaymentTypes = array_keys($this->getPaymentTypes());
    }

    public function getPaymentTypes()
    {
        return array(
            'PC' => "Оплата из кошелька в Яндекс.Деньгах",
            'AC' => "Оплата с произвольной банковской карты",
            'MC' => "Платеж со счета мобильного телефона",
            'GP' => "Оплата наличными через кассы и терминалы",
            'WM' => "Оплата из кошелька в системе WebMoney",
            'SB' => "Оплата через Сбербанк Онлайн",
            'MP' => "Оплата через мобильный терминал (mPOS)",
            'AB' => "Оплата через Альфа-Клик",
            'MA' => "Оплата через MasterPass",
            'PB' => "Оплата через Промсвязьбанк",
            'QW' => "Оплата через QIWI Wallet",
            'KV' => "Оплата через КупиВкредит (Тинькофф Банк)",
            'QP' => "Оплата через Доверительный платеж («Куппи.ру»)",
        );
    }

    public function getPaymentUrl()
    {
        return 'https://' . ($this->demo ? 'demo' : '') . 'money.yandex.ru/eshop.xml';
    }

    public function getAllowedPaymentTypes()
    {
        return array_intersect_key($this->getPaymentTypes(), array_fill_keys($this->allowedPaymentTypes, ''));
    }

    /**
     * @param $name
     * @return mixed
     */
    public function action($name)
    {
        Yii::$app->response->format = Response::FORMAT_RAW;

        $this->action = isset($this->requestData['action']) ? $this->requestData['action'] : $name;

        switch ($this->action):
            case 'index':
                return Yii::$app->controller->render('//pay/yandex', ['merchant'=>$this, 'model'=>$this->model(Yii::$app->request->get('id'))]);
            default:
                return $this->processRequest($this->requestData);
        endswitch;
    }

    /**
     * Handles "checkOrder" and "paymentAviso" requests.
     * @param array $request payment parameters
     */
    public function processRequest($request)
    {
        Yii::trace("Start " . $this->action);
        Yii::trace("Security type " . $this->securityType);
//        if ($this->securityType == "MD5") {
        Yii::trace("Request: " . print_r($request, true));
        // If the MD5 checking fails, respond with "1" error code
        if (!$this->checkMD5($request)) {
            $response = $this->buildResponse($this->action, $request['invoiceId'], 1);
            return $this->sendResponse($response);
        }
        /*} else if ($this->securityType == "PKCS7") {
            // Checking for a certificate sign. If the checking fails, respond with "200" error code.
            if (($request = $this->verifySign()) == null) {
                $response = $this->buildResponse($this->action, null, 200);
                $this->sendResponse($response);
            }
            Yii::trace("Request: " . print_r($request, true));
        }*/
        $response = null;
        if ($this->action == 'checkOrder') {
            $response = $this->checkOrder($request);
        } else {
            $response = $this->paymentAviso($request);
        }
        return $this->sendResponse($response);
    }

    /**
     * Checking the MD5 sign.
     * @param  array $request payment parameters
     * @return bool true if MD5 hash is correct
     */
    private function checkMD5($request)
    {
        $str = $request['action'] . ";" .
            $request['orderSumAmount'] . ";" . $request['orderSumCurrencyPaycash'] . ";" .
            $request['orderSumBankPaycash'] . ";" . $request['shopId'] . ";" .
            $request['invoiceId'] . ";" . $request['customerNumber'] . ";" . $this->shopPassword;
        Yii::trace("String to md5: " . $str);
        $md5 = strtoupper(md5($str));
        if ($md5 != strtoupper($request['md5'])) {
            Yii::error("Wait for md5:" . $md5 . ", recieved md5: " . $request['md5']);
            return false;
        }
        return true;
    }

    /**
     * Building XML response.
     * @param  string $functionName "checkOrder" or "paymentAviso" string
     * @param  string $invoiceId transaction number
     * @param  string $result_code result code
     * @param  string $message error message. May be null.
     * @return string                prepared XML response
     */
    private function buildResponse($functionName, $invoiceId, $result_code, $message = null)
    {
        if($result_code) Yii::error($message);
        try {
            $performedDatetime = date('c');
            $response = '<?xml version="1.0" encoding="UTF-8"?><' . $functionName . 'Response performedDatetime="' . $performedDatetime .
                '" code="' . $result_code . '" ' . ($message != null ? 'message="' . $message . '"' : "") . ' invoiceId="' . $invoiceId . '" shopId="' . $this->shopId . '"/>';
            return $response;
        } catch (\Exception $e) {
            Yii::error($e);
        }
        return null;
    }

    public function sendResponse($response)
    {
        return $response;
    }

    /**
     * CheckOrder request processing. We suppose there are no item with price less
     * than 100 rubles in the shop.
     * @param  array $request payment parameters
     * @return string         prepared XML response
     */
    private function checkOrder($request)
    {
        $response = null;
        $model = $this->model($request['orderNumber']);
        if (!$model) {
            $response = $this->buildResponse($this->action, $request['invoiceId'], 100, "The order is not fount");
        } elseif (!($model instanceof IPayable)) {
            $response = $this->buildResponse($this->action, $request['invoiceId'], 100, "Order class do not use PayableInterface");
        } elseif (!$model->getCanPay()) {
            $response = $this->buildResponse($this->action, $request['invoiceId'], 100, "Order is not for pay");
        } elseif ((float)$model->getTotal() != (float)$request['orderSumAmount']) {
            $response = $this->buildResponse($this->action, $request['invoiceId'], 100, "The order sum is invalid");
        } else {
            $response = $this->buildResponse($this->action, $request['invoiceId'], 0);
        }
        return $response;
    }

    public function model($id)
    {
        /** @var \ra\admin\models\Order $model */
        $model = (new $this->modelClass);
        /** @var \ra\admin\models\Order|IPayable $model */
        $model = $model::findOne($id);
        return $model;
    }

    /**
     * PaymentAviso request processing.
     * @param  array $request payment parameters
     * @return string prepared response in XML format
     */
    private function paymentAviso($request)
    {
        $model = $this->model($request['orderNumber']);
        $model->onPay();
        return $this->buildResponse($this->action, $request['invoiceId'], 0);
    }

    public function getRequestData()
    {
        $inputAttributes = ['requestDatetime', 'action', 'md5', 'shopId', 'shopArticleId', 'invoiceId', 'orderNumber', 'customerNumber', 'orderCreatedDatetime', 'orderSumAmount', 'orderSumCurrencyPaycash', 'orderSumBankPaycash', 'paymentPayerCode', 'paymentType',];
        $attributes = array();
        foreach ($inputAttributes as $attribute) {
            if (isset($_REQUEST[$attribute]))
                $attributes[$attribute] = $_REQUEST[$attribute];
        }
        return $attributes;
    }
}