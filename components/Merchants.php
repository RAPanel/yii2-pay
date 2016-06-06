<?php
namespace app\components;

use Yii;
use yii\base\Exception;

class Merchants extends \yii\base\Component
{

    public $config;

    public function get($id)
    {
        if (isset($this->config[$id])) {
            if (is_array($this->config[$id])) {
                if (!isset($this->config[$id]['class']))
                    throw new Exception("Wrong merchant configuration ({$id})");
                return Yii::createObject($this->config[$id]);
            }
            return new $this->config[$id];
        }
        return false;
    }

}