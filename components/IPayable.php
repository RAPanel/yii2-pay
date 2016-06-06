<?php

namespace app\components;

/**
 * Interface IPayable
 */
interface IPayable
{
	/**
	 * Событие успешной оплаты
	 */
	public function onPay();

	/**
	 * Можно ли произвести оплату
	 * @return bool
	 */
	public function getCanPay();

	/**
	 * Сумма платежа
	 * @return float
	 */
	public function getId();

	/**
	 * Сумма платежа
	 * @return float
	 */
	public function getTotal();

	/**
	 * Описание платежа
	 * @return string
	 */
	public function getDescription();


}