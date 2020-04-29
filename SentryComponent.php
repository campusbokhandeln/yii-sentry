<?php
namespace Intersvyaz\YiiSentry;

use CApplicationComponent;
use CAssetManager;
use CClientScript;
use CException;
use CJavaScript;
use CWebApplication;
use CWebUser;
use IApplicationComponent;
use Raven_Client;
use Raven_ErrorHandler;
use Yii;

class SentryComponent extends CApplicationComponent
{
	/**
	 * @var string Sentry DSN.
	 * @see https://github.com/getsentry/raven-php#configuration
	 */
	public $dsn;

	/**
	 * @var array Raven_Client options.
	 * @see https://github.com/getsentry/raven-php#configuration
	 */
	public $options = array();

	/**
	 * Initialize Raven_ErrorHandler.
	 * @var bool
	 */
	public $useRavenErrorHandler = false;

	/**
	 * @var Raven_Client instance.
	 */
	protected $raven;

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		if ($this->useRavenErrorHandler) {
			$this->registerRavenErrorHandler();
		}
	}

	/**
	 * Get Raven_Client instance.
	 * @return Raven_Client
	 */
	public function getRaven()
	{
		if (!isset($this->raven)) {
			$this->registerRaven();
		}

		return $this->raven;
	}


	/**
	 * Initialize raven client.
	 */
	protected function registerRaven()
	{
		$this->raven = new Raven_Client($this->dsn, $this->options);

		if ($userContext = $this->getUserContext()) {
			$this->raven->user_context($userContext);
		}
	}

	/**
	 * Get get context (id, name).
	 * @return array|null
	 */
	protected function getUserContext()
	{
		/** @var CWebUser $user */
		$user = $this->getComponent('user');
		if ($user && !$user->isGuest) {
			return array(
				'id' => $user->getId(),
			);
		}
		return null;
	}

	/**
	 * Get Yii component if exists and available.
	 * @param string $component
	 * @return IApplicationComponent|null
	 */
	protected function getComponent($component)
	{
		if (!Yii::app() instanceof CWebApplication) {
			return null;
		}

		if ($instance = Yii::app()->getComponent($component)) {
			return $instance;
		}

		return null;
	}

	/**
	 * Register Raven Error Handlers for exceptions and errors.
	 * @return bool
	 */
	protected function registerRavenErrorHandler()
	{
		$raven = $this->getRaven();
		if ($raven) {
			$handler = new Raven_ErrorHandler($raven);
			$handler->registerExceptionHandler();
			$handler->registerErrorHandler();
			$handler->registerShutdownFunction();

			return true;
		}

		return false;
	}
}
