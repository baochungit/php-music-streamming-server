<?php
require_once('lib/BaseTCPServer.php');

class StreamingServer extends BaseTCPServer {
	protected $host = '127.0.0.1';
	protected $port = '8001';

	protected $stream_source = NULL;

	public function start() {
		parent::start();
		echo 'Streaming Server started!' . PHP_EOL;
	}

	public function process() {
		parent::process();
		if (!empty($this->stream_source)) {
			$buffer = $this->stream_source->buffering();
			if (!empty($buffer)) $this->broadcast($buffer);
		}
	}

	public function attachStreamSource($stream_source) {
		if ($stream_source instanceof StreamSource === FALSE) return FALSE;
		$this->stream_source = $stream_source;
		return TRUE;
	}

	protected function handShake($client) {
		$client_header = $this->recieve($client);

		// send header to client
		$this->send($client, 
			"HTTP/1.1 200 OK\r\n" . 
			"Content-Type: audio/mpeg\r\n" . 
			"Cache-Control: no-cache\r\n" . 
			"Pragma: no-cache\r\n" . 
			"Server: Chung Xa\r\n" . 
			"Date: " . gmdate('D, d M Y H:i:s T') . "\r\n\r\n"
		);
	}
}