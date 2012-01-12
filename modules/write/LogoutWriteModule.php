<?php

class LogoutWriteModule extends BaseWriteModule{
	function process() {
		global $current_user;
		$current_user->logout();
	}
}