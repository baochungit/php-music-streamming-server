<?php
require_once('lib/BaseTCPServer.php');

class ChatServer extends BaseTCPServer {
	protected $host = '127.0.0.1';
	protected $port = '8002';

	protected $command_handlers = NULL;

	public function start() {
		parent::start();
		echo 'Chat Server started!' . PHP_EOL;
	}

	public function wsSend($client, $package) {
		if (is_array($package)) $package = json_encode($package);
		$buffer = $this->mask($package);
		$ret = $this->send($client, $buffer);
		return $ret;
	}

	public function wsRecieve($client) {
		$buffer = $this->recieve($client, 1024);
		$package = $this->unmask($buffer); // unmask data
		$tmp_package = $package;
		$package = json_decode($package);
		if (json_last_error() != JSON_ERROR_NONE) $package = $tmp_package;
		return $package;
	}

	public function wsBroadcast($package) {
		foreach ($this->clients as $j => $client) {
			$this->wsSend($client, $package);
		}
	}

	public function attachCommandHandler($command_handler) {
		if ($command_handler instanceof CommandHandler === FALSE) return FALSE;
		$this->command_handlers[] = $command_handler;
		return TRUE;
	}

	protected function handShake($client) {
		$client_header = $this->recieve($client);

		// hand shaking
		$headers = array();
		$lines = preg_split("/\r\n/", $client_header);
		foreach($lines as $line) {
			$line = rtrim($line);
			if (preg_match('/\A(\S+): (.*)\z/', $line, $matches)) {
				$headers[$matches[1]] = $matches[2];
			}
		}

		$secKey = $headers['Sec-WebSocket-Key'];
		$secAccept = base64_encode(pack('H*', sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));

		$this->send($client, 
			"HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
			"Upgrade: websocket\r\n" .
			"Connection: Upgrade\r\n" .
			"WebSocket-Origin: {$this->host}\r\n" .
			"WebSocket-Location: ws://{$this->host}:{$this->port}\r\n".
			"Sec-WebSocket-Accept: $secAccept\r\n\r\n"
		);
	}

	protected function dealWithClient($client) {
		if ($tst_msg = $this->wsRecieve($client)) {
			$user_name = $tst_msg->name; //sender name
			$user_message = $tst_msg->message; //message text
			$user_color = $tst_msg->color; //color
			
			$is_command = FALSE;
			foreach ($this->command_handlers as $command_handler) {
				if ($command_handler->cmd($user_message)) {
					$is_command = TRUE;
					break;
				}
			}
			if (!$is_command) $this->wsBroadcast(array('type'=>'usermsg', 'name'=>$user_name, 'message'=>$user_message, 'color'=>$user_color));
		}
	}

	// Unmask incoming framed message
	protected function unmask($text) {
		$length = ord($text[1]) & 127;
		if ($length == 126) {
			$masks = substr($text, 4, 4);
			$data = substr($text, 8);
		}
		else if ($length == 127) {
			$masks = substr($text, 10, 4);
			$data = substr($text, 14);
		}
		else {
			$masks = substr($text, 2, 4);
			$data = substr($text, 6);
		}
		$text = "";
		for ($i = 0; $i < strlen($data); ++$i) {
			$text .= $data[$i] ^ $masks[$i % 4];
		}
		return $text;
	}

	// Encode message for transfer to client.
	protected function mask($text) {
		$b1 = 0x80 | (0x1 & 0x0f);
		$length = strlen($text);
		
		if ($length <= 125)
			$header = pack('CC', $b1, $length);
		else if ($length > 125 && $length < 65536)
			$header = pack('CCn', $b1, 126, $length);
		else if ($length >= 65536)
			$header = pack('CCNN', $b1, 127, $length);
		return $header . $text;
	}
}