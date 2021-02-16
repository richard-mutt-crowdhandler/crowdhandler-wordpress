<?php

use CrowdHandler\GateKeeper;
use CrowdHandler\PublicClient;

class CrowdHandlerGateKeeper
{
	/**
	 * @var GateKeeper
	 */
	private $gateKeeper;

	/**
	 * @var array
	 */
	private $options;

	/**
	 * @param array $options
	 */
	public function __construct($options = null)
	{
		if (is_array($options)) {
			$this->options = $options;
		} elseif (function_exists('get_option')) {
			$this->options = get_option('crowdhandler_settings');
		}
	}

	public function checkRequest()
	{
		if (!$this->isEnabled()) {
			return $this;
		}

		$api = new PublicClient($this->options['crowdhandler_settings_field_public_key']);
		$this->gateKeeper = new GateKeeper($api);

		$this->gateKeeper->setIgnoreUrls(
			"/^(.*\.(ico|css|js|json|pdf|xml|eot|ott|ttf|woff|woff2|gif|jpg|png|svg|avi|mov|mp4|mpeg|mpg|wmv|ogg|ogv)$)|(\/wp-admin)|(\/wp-content)|(\/wp-includes)/"
		);
		$this->gateKeeper->checkRequest();
		$this->gateKeeper->redirectIfNotPromoted();
		$this->gateKeeper->setCookie();

		return $this;
	}

	public function recordPerformance($code)
	{
		if (!$this->isEnabled()) {
			return false;
		}

		if (!$this->gateKeeper instanceof GateKeeper) {
			return false;
		}

		try {
			$this->gateKeeper->recordPerformance($code);
		} catch (\Exception $e) {
			return false;
		}

		return true;
	}

	/**
	 * @return bool
	 */
	public function isEnabled()
	{
		return
			isset($this->options['crowdhandler_settings_field_is_enabled']) &&
			$this->options['crowdhandler_settings_field_is_enabled'] == 'on' &&
			isset($this->options['crowdhandler_settings_field_public_key']) &&
			$this->options['crowdhandler_settings_field_public_key']
		;
	}

}