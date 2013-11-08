<?php
class BtcUsdHistory {

	protected $apiCache = 'bitcoincharts.dat';
	protected $apiUrl= 'http://api.bitcoincharts.com/v1/trades.csv?symbol=btcexYAD&start=';
	protected $indexFormat = 'Ymd';

	/**
	 * @param string Date in almost any format recognized by DateTime 
	 *   (@see http://us3.php.net/manual/en/class.datetime.php)
	 * @return float Average USD value of Bitcoin on that date
	 */
	public function getPriceAt($date) {
		echo __FUNCTION__ . "\n"; //debug
		// Check date validity
		$D = new DateTime($date);
		return $this->fetch($D);
	}

	/**
	 * @param DateTime Date object representing day to fetch
	 * @return float Average USD value for BTC on the given date
	 */
	protected function fetch(DateTime $D) {
		echo __FUNCTION__ . "\n"; //debug

		$timestamp = $D->format('U'); // Unix timestamp for API
		$index = $D->format('Ymd'); // Truncated index for API cache

		$usd = $this->fetchFromCache($index);
		if($usd === false) {
			echo 'NOT FOUND IN CACHE' . "\n"; //debug
			// Not found. Update the API cache
			echo 'Going to fetch ' . $this->apiUrl . $timestamp; //debug
			$handle = fopen($this->apiUrl . $timestamp, 'r');
			print_r($handle);exit(); //debug
			$data = stream_get_contents($handle);
			print_r($data);exit();//debug
			fclose($handle);
			if (!empty($data)) {
				$this->updateCache($data);
				$usd = $this->fetchFromCache($index);
			} 
		}
		return sprintf('%01.2f', $usd);
	}

	/**
	 * 
	 * @param string $index Truncated Unix timestamp
	 * @return float Cached BTC value at given index
	 */
	protected function fetchFromCache($index) {
		echo __FUNCTION__ . "\n"; //debug
		$f = fopen($this->apiCache, 'r') or die ('Could not open ' . $this->apiCache);
		while (($record = fgets($f)) !== false) {
			$data = explode(',', $record);
			print_r($data); //debug
			if($data[0] == $index) {
				return $data[1];
			}
		}
		return false;
	}

	/**
	 * @param array CSV data from API
	 * @return boolean True if reduction was successful, false if not
	 */
	protected function updateCache($data) {
		echo __FUNCTION__ . "\n"; //debug
		$days = array();
		foreach($data as $record) {
			list($timestamp, $usd, $quantity) = split(',', $record);
			$D = new DateTime($timestamp);
			$days[$D->format($this->indexFormat)] = array(
				'dollars' => $usd, 
				'coins' => $quantity
			);
		}
		print_r($days, true); // debug

		$f = fopen($this->ApiCache, 'a');

		// The average is the total USD spent divided by the total coins traded
		foreach($days as $day => $trades) {
			$dollars = 0;
			$coins = 0;
			foreach($trades as $trade) {
				$dollars += $trade['dollars'];
				$quantity += $trade['coins'];
			}
			fwrite($f, $day . ',' . $dollars / $quantity);
		}
		fclose($f);
	}
}

date_default_timezone_set('America/Los_Angeles');
$B = new BtcUsdHistory();
echo $B->getPriceAt('2013-03-18');

?>
