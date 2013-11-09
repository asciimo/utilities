<?php
/**
 *
 * The MIT License (MIT)
 * 
 * Copyright (c) 2013 Stephen Calnan https://github.com/asciimo
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 *                
 * About this script
 * -----------------
 *
 * This script fetches and caches Mt. Gox trade data for arbitrary dates. It uses 
 * the API documented [here](https://en.bitcoin.it/wiki/MtGox/API/HTTP/v1#Multi_currency_trades).  
 * There is a newer version of the API which is not officially documented, but there is a 
 * lot of unofficial information about it [here](https://bitbucket.org/nitrous/mtgox-api/overview#markdown-header-moneytradesfetch)
 *
 * There's also an interesting possibility of querying a Google BigQuery database for 
 * this information.  More on that [here](https://bitcointalk.org/index.php?topic=218980.0)
 * 
 * ### Usage
 * 
 * date_default_timezone_set('America/Los_Angeles');
 * $B = new BtcPriceHistory();
 * echo $B->getPriceAt('2013-08-18');
 *
 * ### About the cache
 *
 * This script maintains an API cache called `apicache.dat`. The class will always
 * check for a result in this file first.  If it does not find a result it will
 * make a new API request. It will reduce the trades for the requested date into
 * a daily average and update the cache file.  This can take a little while, so
 * keep in mind that cache misses are expensive.  
 *
 */
class BtcPriceHistory {

	protected $apiCache = 'apicache.dat';
	protected $apiUrl = 'https://data.mtgox.com/api/1/BTCUSD/trades?since='; //1363330800000000 16 digits!
	protected $indexFormat = 'Ymd';

	/**
	 * @param string Date in almost any format recognized by DateTime 
	 *   (@see http://us3.php.net/manual/en/class.datetime.php)
	 * @return float Average price of Bitcoin on that date
	 */
	public function getPriceAt($date) {
		$D = new DateTime($date);
		return $this->fetch($D);
	}

	/**
	 * @param DateTime Date object representing day to fetch
	 * @return float Average price for BTC on the given date
	 */
	protected function fetch(DateTime $D) {

		$timestamp = $D->format('U') . '000000'; // Unix timestamp for API
		$index = $D->format('Ymd'); // Truncated index for local API cache

		$price = $this->fetchFromCache($index);
		if($price === false) {
			// Not found. Update the API cache
			$handle = fopen($this->apiUrl . $timestamp, 'r');
			if(empty($handle)) {
				exit('Unable to fetch data from ' . $this->apiUrl);
			}
			$data = stream_get_contents($handle);
			fclose($handle);
			if (!empty($data)) {
				$this->updateCache($data);
				$price = $this->fetchFromCache($index);
			} 
		}
		return sprintf('%01.2f', $price);
	}

	/**
	 * @param string $index Truncated Unix timestamp
	 * @return float Cached BTC value at given index
	 * @throws generic exception on file failure
	 */
	protected function fetchFromCache($index) {
		if(!file_exists($this->apiCache) || !is_readable($this->apiCache)) {
			return false;
		}
		$f = fopen($this->apiCache, 'r');
		if(empty($f)) {
			throw new Exception ('Unable to read ' . $this->apiCache);
		}

		while (($record = fgets($f)) !== false) {
			$data = explode(',', $record);
			if($data[0] == $index) {
				return $data[1];
			}
		}
		return false;
	}

	/**
	 * @param string Big chunk of JSON data from API
	 * @return boolean True if update was successful, false if not
	 */
	protected function updateCache($data) {
		$data = json_decode($data, true);
		if($data['result'] !== "success") {
			throw new Exception ('API request failed.');
		}

		$days = array();
		foreach($data['return'] as $trade) {
			if($trade['primary'] == 'Y') { // 'Y' indicates a buy
				$D = new DateTime();
				$D->setTimestamp($trade['date']);
				$days[$D->format($this->indexFormat)][] = $trade['price'];
			}
		}

		$f = fopen($this->apiCache, 'a+');

		foreach($days as $day => $trades) {
			$average = array_sum($trades) / count($trades);
			fwrite($f, $day . ',' . $average . "\n");
		}
		fclose($f);

		// Re-sort the cache records and eliminate duplicates. 
		$allDays = file($this->apiCache);
		$allDays = array_unique($allDays);
		ksort($allDays);
		file_put_contents($this->apiCache, $allDays);

		return true;
	}
}

date_default_timezone_set('America/Los_Angeles');
$B = new BtcPriceHistory();
echo $B->getPriceAt('2013-03-17') . "\n";
?>
