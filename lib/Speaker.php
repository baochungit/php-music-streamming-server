<?php
require_once('lib/interfaces/StreamSource.php');

class Speaker implements StreamSource {
	public function __construct() {}

	public function buffering() {
	}
}
