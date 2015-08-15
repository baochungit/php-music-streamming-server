<?php
set_time_limit(0);

require_once('vendors/meekrodb.2.3.class.php');
$db = new MeekroDB('localhost', '[username]', '[password]', '[database name]', NULL, 'utf8');
