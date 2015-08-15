<?php
class BaseTCPServer {
	const STATE_STOPPED = 0;
	const STATE_STARTED = 1;

	protected $server = NULL;
	protected $clients = NULL;

	protected $host = '127.0.0.1';
	protected $port = '8002';

	protected $state = STATE_STOPPED;

	public function __construct($start = FALSE) {
		if ($start == TRUE) $this->start();
	}

	public function start() {
		if ($this->state == STATE_STARTED) return FALSE;
		//Create TCP/IP sream socket
		$this->server = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		//reuseable port
		socket_set_option($this->server, SOL_SOCKET, SO_REUSEADDR, 1);
		socket_set_nonblock($this->server);
		//bind socket to specified host
		socket_bind($this->server, $this->host, $this->port);
		socket_listen($this->server);
		$this->clients = array();
		$this->state = STATE_STARTED;
	}

	public function stop() {
		if ($this->state == STATE_STOPPED) return FALSE;
		if (!empty($this->server)) {
			socket_close($this->server);
		}
		$this->state = STATE_STOPPED;
	}

	public function process() {
		$null = NULL;
		$changed = array_merge(array($this->server), $this->clients);

		//returns the socket resources in $changed array
		socket_select($changed, $null, $null, 0, 10);
		foreach ($changed as $changed_socket) {
			if ($this->server == $changed_socket) {
				$client = socket_accept($this->server);
				if ($client !== FALSE) {
					$this->handShake($client);
					$this->clients[] = $client;
				}
			}
			else {
				$this->dealWithClient($changed_socket);
				// check disconnected client
				$buf = $this->recieve($changed_socket);
				if ($buf === FALSE) {
					$found_socket = array_search($changed_socket, $this->clients);
					unset($this->clients[$found_socket]);
				}
			}
		}
	}

	public function send($client, $buffer) {
		if ($this->state != STATE_STARTED) return FALSE;
		$ret = socket_write($client, $buffer);
		return $ret;
	}

	public function recieve($client, $length = 1024) {
		if ($this->state != STATE_STARTED) return FALSE;
		$buffer = socket_read($client, $length);
		return $buffer;
	}

	public function broadcast($buffer) {
		foreach ($this->clients as $j => $client) {
			$this->send($client, $buffer);
		}
	}

	protected function handShake($client) {}
	protected function dealWithClient($client) {}
}