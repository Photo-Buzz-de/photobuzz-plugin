<?php
namespace PhotoBuzz;

require_once __DIR__ . '/../vendor/autoload.php';



class JsonDateTime extends \DateTime implements \JsonSerializable
{
	public function jsonSerialize(): mixed
	{
		return $this->format("c");
	}
}

class Event_Images
{
	private $directory;
	private $table_name;

	const img_dir = "wp-content/photobuzz-images";
	const table_name = "wp_photobuzz_event_images";

	function __construct($directory)
	{
		global $wpdb;

		$this->directory = $directory;
		$this->table_name = 'wp_photobuzz_event_images'; //$wpdb->prefix . 'photobuzz_event_images';
	}
	public function dirExists()
	{
		if (!function_exists('get_home_path')) {
			require_once(dirname(__FILE__) . '/../../../../wp-admin/includes/file.php');
		}
		$dir = get_home_path() . self::img_dir . "/" . $this->directory;
		return is_dir($dir);
	}
	public function mkdir()
	{

		if (!function_exists('get_home_path')) {
			require_once(dirname(__FILE__) . '/../../../../wp-admin/includes/file.php');
		}
		$dir = get_home_path() . self::img_dir . "/" . $this->directory;
		return is_dir($dir) || mkdir($dir);
	}
	private function makeAbsolute($file)
	{
		if (!function_exists('get_home_path')) {
			require_once(dirname(__FILE__) . '/../../../../wp-admin/includes/file.php');
		}
		return get_home_path() . self::img_dir . "/" . $this->directory . '/' . $file;
	}
	public function make_thumbnail($img)
	{
		$thumbpath = "thumb/" . $img;
		if (!function_exists('get_home_path')) {
			require_once(dirname(__FILE__) . '/../../../../wp-admin/includes/file.php');
		}
		$fullthumbpath = get_home_path() . self::img_dir . "/" . $this->directory . "/" . substr($thumbpath, 0, strrpos($thumbpath, ".")) . strtolower(substr($img, strrpos($img, ".")));
		if (!is_dir(get_home_path() . self::img_dir . "/" . $this->directory . "/thumb"))
			mkdir(get_home_path() . self::img_dir . "/" . $this->directory . "/thumb");
		if (!file_exists($fullthumbpath)) {
			if (strtolower(substr($img, strrpos($img, "."))) == ".jpg") {
				$editor = wp_get_image_editor(get_home_path() . "/" . self::img_dir . "/" . $this->directory . "/" . $img);
				$editor->resize(300, null);
				$editor->save($fullthumbpath);
			} else {
				$ffmpeg = \FFMpeg\FFMpeg::create();
				$video = $ffmpeg->open(get_home_path() . "/" . self::img_dir . "/" . $this->directory . "/" . $img);
				$video->filters()->resize(new \FFMpeg\Coordinate\Dimension(300, 450));
				$format = new \FFMpeg\Format\Video\X264();
				$format->setKiloBitrate(100);
				$video->save($format, $fullthumbpath);
			}
		}
	}

	public function scan()
	{
		global $wpdb;
		if ($this->dirExists()) {
			$dir = get_home_path() . self::img_dir . "/" . $this->directory;
			$imgs = scandir($dir);
			set_time_limit(0);
			foreach ($imgs as $img) {
				if ($img !== "." && $img !== ".." && is_file($dir . '/' . $img)) {
					if (substr($img, 0, 3) == "IMG") {
						$this->insertImage("", $img, true);
					} else if (substr($img, 0, 2) == "PB") {
						try {
							$this->insertImage2("", $img, true);
						} catch (\InvalidArgumentException $e) {
							error_log("scan: Datei existiert");
						}
					}
				}
			}
		}
	}
	public function getImages($start = 0, $num = 100000)
	{
		global $wpdb;
		if ($this->dirExists()) {
			if (!function_exists('get_home_path')) {
				require_once(dirname(__FILE__) . '/../../../../wp-admin/includes/file.php');
			}
			$startclause = "";
			if ($start != 0) {
				$startclause = " date < " . esc_sql($start) . " AND";
			}
			$results = $wpdb->get_results('SELECT * FROM ' . $this->table_name . ' WHERE' . $startclause . ' directory="' . $this->directory . '" AND deleted=0 ORDER BY date DESC LIMIT ' . esc_sql($num), ARRAY_A);

			$out = array();


			foreach ($results as $result) {

				$out[] = array(
					'filename' => $result['name'],
					'extension' => substr($result['name'], strrpos($result['name'], ".") + 1),
					'thumbnail_url' => get_site_url() . "/" . self::img_dir . "/" . $this->directory . '/thumb/' . substr($result['name'], 0, strrpos($result['name'], ".")) . strtolower(substr($result['name'], strrpos($result['name'], "."))),
					'image_url' => get_site_url() . "/" . self::img_dir . "/" . $this->directory . '/' . $result['name'],
					'path' => get_home_path() . self::img_dir . "/" . $this->directory . '/' . $result['name'],
					'date' => new JsonDateTime("@" . $result['date']),
					'width' => $result['width'],
					'height' => $result['height'],
					'code' => $result['random_key']
				);
			}

			return $out;
		}
	}
	public function getImageByCode($code)
	{
		global $wpdb;
		if ($this->dirExists()) {
			if (!function_exists('get_home_path')) {
				require_once(dirname(__FILE__) . '/../../../../wp-admin/includes/file.php');
			}

			$query = $wpdb->prepare('SELECT * FROM ' . $this->table_name . ' WHERE directory="%s" AND random_key="%s" AND deleted=0 ORDER BY date DESC', $this->directory, $code);
			$results = $wpdb->get_results($query, ARRAY_A);

			$out = array();


			foreach ($results as $result) {

				$out[] = array(
					'filename' => $result['name'],
					'extension' => substr($result['name'], strrpos($result['name'], ".") + 1),
					'thumbnail_url' => get_site_url() . "/" . self::img_dir . "/" . $this->directory . '/thumb/' . substr($result['name'], 0, strrpos($result['name'], ".")) . strtolower(substr($result['name'], strrpos($result['name'], "."))),
					'image_url' => get_site_url() . "/" . self::img_dir . "/" . $this->directory . '/' . $result['name'],
					'path' => get_home_path() . self::img_dir . "/" . $this->directory . '/' . $result['name'],
					'date' => new JsonDateTime("@" . $result['date']),
					'width' => $result['width'],
					'height' => $result['height']
				);
			}
			if (count($results)) {
				if (!$results[0]["visited"]) {
					$wpdb->update($this->table_name, ["visited" => true], ["random_key" => $code]);
				}
			}

			return $out;
		}
	}
	public static function getImageDetailsByCode($code)
	{
		global $wpdb;

		if (!function_exists('get_home_path')) {
			require_once(dirname(__FILE__) . '/../../../../wp-admin/includes/file.php');
		}

		$query = $wpdb->prepare('SELECT * FROM wp_photobuzz_event_images WHERE random_key="%s" ORDER BY date ASC LIMIT 2',  $code);
		$results = $wpdb->get_results($query, ARRAY_A);

		if ($results && !empty($results)) {
			$strparts = explode("_", $results[0]["name"], 5);
			$out = array();
			$out["box_id"] = $strparts[1];
			$out["date"] = \DateTime::createFromFormat("U", $results[0]["date"]);
			return $out;
		} else return NULL;
	}
	public static function getImagesByBoxID($id, $limit = null)
	{
		global $wpdb;

		if (!function_exists('get_home_path')) {
			require_once(dirname(__FILE__) . '/../../../../wp-admin/includes/file.php');
		}

		$query = $wpdb->prepare('SELECT * FROM ' . self::table_name . ' WHERE name LIKE "PB_' . $wpdb->escape($id) . '_%%" ORDER BY date DESC');
		if (!is_null($limit)) {
			$query = $wpdb->prepare($query . ' LIMIT %d', $limit);
		}
		$results = $wpdb->get_results($query, ARRAY_A);

		$out = array();


		foreach ($results as $result) {

			$out[] = array(
				'filename' => $result['name'],
				'extension' => substr($result['name'], strrpos($result['name'], ".") + 1),
				'thumbnail_url' => get_site_url() . "/" . self::img_dir . "/" . $result["directory"] . '/thumb/' . substr($result['name'], 0, strrpos($result['name'], ".")) . strtolower(substr($result['name'], strrpos($result['name'], "."))),
				'image_url' => get_site_url() . "/" . self::img_dir . "/" . $result["directory"] . '/' . $result['name'],
				'path' => get_home_path() . self::img_dir . "/" . $result["directory"] . '/' . $result['name'],
				'date' => new JsonDateTime("@" . $result['date']),
				'width' => $result['width'],
				'height' => $result['height']
			);
		}

		return $out;
	}
	public function insertImage($tmpurl, $name, $scan = false)
	{
		global $wpdb;
		if ($this->dirExists()) {
			if (!function_exists('get_home_path')) {
				require_once(dirname(__FILE__) . '/../../../../wp-admin/includes/file.php');
			}
			$dir = get_home_path() . self::img_dir . "/" . $this->directory;
			//rename duplicates

			$imgs = scandir($dir);
			$ctr = 1;
			if (!$scan && in_array($name, $imgs)) {
				$img = substr($name, 0, strrpos($name, '.'));
				while (in_array($img . '-' . $ctr . '.jpg', $imgs) || in_array($img . '-' . $ctr . '.mp4', $imgs))
					$ctr++;
				$img .= '-' . $ctr . substr($img, strrpos($img, "."));
			} else
				$img = $name;
			if (!$scan) move_uploaded_file($tmpurl, $dir . '/' . $img);

			$this->make_thumbnail($img);


			if (substr($img, strrpos($img, ".")) == ".jpg") {
				$timestr = substr($img, strpos($img, '_') + 1, strrpos($img, '.') - strpos($img, '_') - 1);
				$date = \DateTime::createFromFormat('Y-m-d_H-i-s', $timestr);
				if (!$date) {
					$exif = exif_read_data($dir . '/' . $img);
					$date = new \Datetime($exif["DateTimeOriginal"]);
				}
				$dimensions = getimagesize($dir . '/' . $img);


				$width = $dimensions[0];
				$height = $dimensions[1];
			} else {
				$ffprobe = \FFMpeg\FFProbe::create();
				$timestr = substr($img, strpos($img, '_') + 1, strrpos($img, '.') - strpos($img, '_') - 1);
				$date = \DateTime::createFromFormat('Y-m-d_H-i-s', $timestr);
				if (!$date) {
					$date = new \Datetime($ffprobe
						->format($dir . '/' . $img)
						->get("tags")["title"]);
				}
				$strinfo = $ffprobe->streams($dir . '/' . $img)->videos()->first();
				$width = $strinfo->get("width");
				$height = $strinfo->get("height");
			}
			$query = "INSERT INTO " . $this->table_name . " (name, directory, date, width, height) VALUES (%s,%s,%d,%d,%d) ON DUPLICATE KEY UPDATE date=%d, width=%d, height=%d";

			$query = $wpdb->prepare($query, $img, $this->directory, $date->format("U"), $width, $height, $date->format("U"), $width, $height);
			$wpdb->query($query);
			return $img; // return possibly altered filename 
		}
	}

	public function deleteImage($name)
	{
		global $wpdb;
		$query = $wpdb->prepare("UPDATE " . $this->table_name . " SET deleted=1 WHERE directory=%s AND name=%s", $this->directory, $name);
		$wpdb->query($query);
	}


	public static function check_filename($name)
	{
		//Check Filename format
		if (!preg_match('/PB_[a-z0-9\-]*_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}_.*\.(jpg|mp4)/', $name)) {
			throw new \InvalidArgumentException("Invalid Filename! Must be PB_id_Y-m-d_H-i-s_random.ext");
		} else return true;
	}

	/**
	 * V2 Insert function
	 * Format has to be PB_id_Y-m-d_H-i-s_random.ext 
	 */
	public function insertImage2($tmpurl, $name, $scan = false)
	{
		global $wpdb;

		//Check Filename format
		self::check_filename($name);


		if ($this->dirExists()) {
			if (!function_exists('get_home_path')) {
				require_once(dirname(__FILE__) . '/../../../../wp-admin/includes/file.php');
			}
			$dir = get_home_path() . self::img_dir . "/" . $this->directory;

			move_uploaded_file($tmpurl, $dir . '/' . $name);

			$this->make_thumbnail($name);

			$extension = substr($name, strrpos($name, "."));

			$seperated = explode("_", $name, 5);
			$timestr = $seperated[2] . "T" . $seperated[3];
			$random_key = substr($seperated[4], 0, strrpos($seperated[4], '.'));
			$date = \DateTime::createFromFormat('Y-m-d\TH-i-s', $timestr);

			if ($extension == ".jpg") {
				$dimensions = getimagesize($dir . '/' . $name);
				$exif = exif_read_data($dir . '/' . $name);

				$width = $dimensions[0];
				$height = $dimensions[1];
				if ($exif && array_key_exists("Orientation", $exif) && $exif["Orientation"] != 1) {
					$width = $dimensions[1];
					$height = $dimensions[0];
				}
			} else if ($extension == ".mp4") {
				$ffprobe = \FFMpeg\FFProbe::create();
				$strinfo = $ffprobe->streams($dir . '/' . $name)->videos()->first();
				$width = $strinfo->get("width");
				$height = $strinfo->get("height");
			}
			$query = "INSERT INTO " . $this->table_name . " (name, directory, date, width, height, random_key) VALUES (%s,%s,%d,%d,%d,%s)";
			$wpdb->hide_errors();
			$query = $wpdb->prepare($query, $name, $this->directory, $date->format("U"), $width, $height,  $random_key);
			$wpdb->query($query);
			if ($wpdb->last_error !== '') {
				throw new \InvalidArgumentException("Database Error! May already exist! " . $wpdb->last_error);
			}
			return $name; // return possibly altered filename 
		} else {
			throw new \InvalidArgumentException("Invalid Dir! " . $this->directory);
		}
	}


	public function checkImage($timestamp, $name)
	{
		global $wpdb;

		$date = new \DateTime('@' . $timestamp);

		if ($this->dirExists()) {
			$name = substr($name, 0, strrpos($name, '.'));
			$results = $wpdb->get_results('SELECT * FROM ' . $this->table_name . ' WHERE directory="' . $this->directory . '" AND name LIKE "' . $name . '%"', ARRAY_A);

			return (sizeof($results) > 0);
		}
	}

	public static function checkImages($names)
	{
		global $wpdb;


		$repl_str = "(" . implode(",", array_fill(0, count($names), "%s")) . ")";


		$query = $wpdb->prepare('SELECT DISTINCT(name) FROM ' . $wpdb->prefix . 'photobuzz_event_images' . ' WHERE name IN ' . $repl_str . '', $names);

		$result = $wpdb->get_col($query);
		error_log("LÄNGE: " . count($result));
		return $result;
	}

	public function getDirectory()
	{
		return $this->directory;
	}
}
