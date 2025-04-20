<?php

namespace moemorox\qaip;

class ext extends \phpbb\extension\base
{
	public function is_enableable()
	{
		$valid_phpbb = phpbb_version_compare(PHPBB_VERSION, '3.3.0', '>=') && phpbb_version_compare(PHPBB_VERSION, '4.0-dev', '<');
		$valid_php = phpbb_version_compare(PHP_VERSION, '7.2.0', '>=') && phpbb_version_compare(PHP_VERSION, '8.5.0-dev', '<');

		return $valid_phpbb && $valid_php;
	}
}
