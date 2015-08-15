<?php
require_once('lib/interfaces/StreamSource.php');
require_once('lib/interfaces/CommandHandler.php');

class RadioStation implements StreamSource, CommandHandler {
	const STATE_NOTREADY = 0;
	const STATE_READY = 200;
	const STATE_PLAYING = 201;
	const STATE_PAUSED = 202;
	const STATE_STOPPED = 203;
	
	const ERROR_DB = 100;
	const ERROR_WRONG_STATION_ID = 101;
	const ERROR_LOADING_STATION_INFO = 102;
	const ERROR_LOADING_PLAYLIST = 103;

	protected $info = NULL;
	protected $music_directory = "music/";
	protected $buffer_size = 16384;

	protected $playlist = NULL;

	protected $state = STATE_NOTREADY;
	protected $error = FALSE;
	protected $error_no = 0;
	protected $db = NULL;

	protected $buffers = NULL;
	protected $old_buffer = '';
	protected $song_index = 0;
	protected $buffer_index = 0;
	protected $start_time = 0;

	protected $station_id = 0;
	protected $mspf = 0.0; // time-spend for a frame
	protected $last_buffer_at = 0;
	protected $next_song_start_at = 0;

	public function __construct($station_id = 0, $play = FALSE) {
		global $db;
		if (empty($db)) {
			$this->error = TRUE;
			$this->error_no = ERROR_DB;
		}
		else {
			$this->db = $db;
			if ($station_id != 0) {
				if ($this->init($station_id) && $play == TRUE) $this->play();
			}
		}
	}

	public function init($station_id) {
		if ($this->error_no == 100) return FALSE;
		$this->error = FALSE;
		$this->error_no = 0;
		
		if (!is_numeric($station_id) || empty($station_id)) {
			$this->error = TRUE;
			$this->error_no = ERROR_WRONG_STATION_ID;
			return FALSE;
		}
		$this->station_id = $station_id;

		if (!$this->loadStationInfo()) {
			$this->error = TRUE;
			$this->error_no = ERROR_LOADING_STATION_INFO;
			return FALSE;
		}
		
		if (!$this->loadPlaylist()) {
			$this->error = TRUE;
			$this->error_no = ERROR_LOADING_PLAYLIST;
			return FALSE;
		}
		$this->state = STATE_READY;
		return TRUE;
	}

	public function play() {
		if ($this->error || in_array($this->state, array(STATE_PLAYING, STATE_NOTREADY))) return FALSE;
		if ($this->state != STATE_PAUSED) {
			$this->start_time = microtime(TRUE);
			$this->last_buffer_at = microtime(TRUE);
			$this->next_song_start_at = 0;
			$this->buffers = array();
			$this->buffer_index = 0;
			$this->song_index = 0;
			$this->old_buffer = '';
			$this->state = STATE_PLAYING;
		}
		else {
			$this->state = STATE_PLAYING;
		}
		return TRUE;
	}

	public function pause() {
		if ($this->error || $this->state != STATE_PLAYING) return FALSE;
		$this->state = STATE_PAUSED;
		return TRUE;
	}

	public function stop() {
		if ($this->error || $this->state != STATE_PLAYING) return FALSE;
		$this->state = STATE_STOPPED;
		return TRUE;
	}

	public function buffering() {
		if ($this->error || $this->state != STATE_PLAYING) return FALSE;
		if ($this->buffer_index >= count($this->buffers)) {
			if ($this->song_index >= count($this->playlist)) return FALSE; // only get new song's buffers if having one
			if ((microtime(TRUE) - $this->start_time) < $this->next_song_start_at) return FALSE; // only get new song's buffers if it's right time

			$song_content = $this->old_buffer . substr(file_get_contents($this->music_directory . $this->playlist[$this->song_index]["filename"]), $this->playlist[$this->song_index]["audiostart"], $this->playlist[$this->song_index]["audiolength"]);
			for($j = 0; $j < floor(strlen($song_content) / $this->buffer_size); $j++) {
				$this->buffers[] = substr($song_content, $j * $this->buffer_size, $this->buffer_size);
			}
			$this->old_buffer = substr($song_content, $j * $this->buffer_size);

			$this->mspf = $this->playlist[$this->song_index]["playtime"] / $j;
			$this->next_song_start_at += $this->playlist[$this->song_index]["playtime"];
			$this->song_index++;
		}
		if ($this->buffer_index >= count($this->buffers)) return FALSE; // check again to see if having buffer

		$new_time = microtime(TRUE);
		if ($new_time - $this->last_buffer_at < ($this->mspf - 0.3)) return FALSE; // only return buffer if it's right time
		$this->last_buffer_at = $new_time;
		return $this->buffers[$this->buffer_index++];
	}

	public function cmd($command) {
		echo 'command: ' . $command . PHP_EOL;
	}

	protected function loadStationInfo() {
		if ($this->error) return FALSE;
		$this->info = $this->db->queryFirstRow("SELECT * FROM stations WHERE id = %i", $this->station_id);
		if ($this->info === FALSE) return FALSE;
		return TRUE;
	}

	protected function loadPlaylist() {
		if ($this->error) return FALSE;
		$this->playlist = $this->db->query("SELECT s.* FROM songs_stations a, songs s WHERE a.played IS NULL AND a.song_id = s.id AND a.station_id = %i", $this->station_id);
		if ($this->playlist === FALSE) return FALSE;
		return TRUE;
	}

	protected function searchSong($keyword) {
		if ($this->error || $this->state == STATE_NOTREADY) return FALSE;
		$songs = $this->db->query("SELECT * FROM songs WHERE artist LIKE %ss OR title LIKE %ss", $keyword, $keyword);
		return $songs;
	}

	protected function addSong($song_id) {
		if ($this->error || $this->state == STATE_NOTREADY) return FALSE;
		$this->db->insert('songs_stations', array('song_id' => $song_id, 'station_id' => $this->station_id, 'created' => $this->db->sqleval('NOW()')));
	}

	protected function skipSong($song_id) {
		if ($this->error || $this->state == STATE_NOTREADY) return FALSE;
		$this->db->delete('songs_stations', 'song_id = %i AND station_id = %i', $song_id, $station_id);
	}
}
