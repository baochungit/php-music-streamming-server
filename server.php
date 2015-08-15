<?php
require_once('bootstrap.php');

require_once('lib/StreamingServer.php');
// require_once('lib/ChatServer.php');
// require_once('lib/RadioStation.php');
require_once('lib/Speaker.php');

// $radio_station = new RadioStation(1, TRUE);
$speaker = new Speaker();

$streaming_server = new StreamingServer(TRUE);
$streaming_server->attachStreamSource($speaker);

// $chat_server = new ChatServer(TRUE);
// $chat_server->attachCommandHandler($radio_station);

while(TRUE) {
	$streaming_server->process();
	// $chat_server->process();
}
