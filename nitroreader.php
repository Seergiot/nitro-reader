<?php
class NitroReader
{
	private $_uniqueName;
	private $_file;
	
	public function __construct($_file)
	{
		$this->_file = file_get_contents($_file);
		$dirParts = explode('/', $_file);
		$this->_uniqueName = str_replace('.nitro', '', explode('/', $_file)[count($dirParts)-1]);
	}

	public function Read()
	{
		$nitroFile = $this->ReadNitro();

		if (!$nitroFile) {
			echo 'Invalid format ' . $this->_uniqueName;
			return;
		}
		
		return $nitroFile;
	}

	public function ReadNitro()
	{
		$br = new BinaryRead($this->_file);

		$fileCount = $br->ReadInt16();

		$jsonFile = null;
		$mainImage = null;

		while ($fileCount > 0) {
			$fileNameLenght = $br->ReadInt16();
			$fileName = $br->ReadStringL($fileNameLenght);

			$fileLenght = $br->ReadInt32();
			$buffer = $br->ReadStringL($fileLenght);
			if ($this->Contains($fileName, '.json')) {
				$decompressed = zlib_decode(($buffer));
				$jsonFile = $decompressed;
			} else {
				$decompressed = zlib_decode(($buffer));
				$mainImage = $decompressed;
			}


			$fileCount--;
		}


		$jsonFile = json_decode($jsonFile, true);

		if (!$jsonFile) return false;

		return ['json' => $jsonFile, 'image' => $mainImage];
	}

	private function Contains($stack, $needle)
	{
		return str_replace($needle, '', $stack) != $stack;
	}
}

class BinaryRead
{
	private $rawData;
	private $currentByte;


	public function __construct($rawData)
	{
		$this->rawData = $rawData;
		$this->currentByte = 0;
	}

	public function ReadStringL($length)
	{

		$result = $this->plainReadBytes($length);
		$this->currentByte += $length;
		return $result;
	}

	public function remainingBytes()
	{
		return strlen($this->rawData) - $this->currentByte;
	}

	public function plainReadBytes($count)
	{
		if ($count > $this->remainingBytes())
			return null;

		return substr($this->rawData, $this->currentByte, $count);
	}

	public function ReadInt16()
	{
		$result = static::DecodeBit16($this->plainReadBytes(2));
		$this->currentByte += 2;
		return $result;
	}

	public function ReadInt32()
	{
		$result = static::DecodeBit32($this->plainReadBytes(4));
		$this->currentByte += 4;
		return $result;
	}

	public static function DecodeBit16($v)
	{
		$v = str_split($v);
		if (!isset($v[0]) || !isset($v[1])) {
			return -1;
		}
		if ((ord($v[0]) | ord($v[1])) < 0)
			return -1;
		return ((ord($v[0]) << 8) + (ord($v[1]) << 0));
	}
	public static function DecodeBit32($v)
	{
		$v = str_split($v);
		if (!isset($v[0]) || !isset($v[1]) || !isset($v[2]) || !isset($v[3])) {
			return -1;
		}
		if ((ord($v[0]) | ord($v[1]) | ord($v[2]) | ord($v[3])) < 0)
			return -1;
		return ((ord($v[0]) << 24) + (ord($v[1]) << 16) + (ord($v[2]) << 8) + (ord($v[3]) << 0));
	}
}


$files = array_diff(scandir('./files'), array('.', '..'));
foreach ($files  as $file) {
	if (!endsWith($file, '.nitro')) {
		continue;
	}

	$f = new NitroReader('./files/' .$file);
	$nitroData = $f->Read(); // returns ['json' => $jsonFile, 'image' => $mainImage]
	print_r( $nitroData );
}

function endsWith($haystack, $needle)
{
	$length = strlen($needle);
	if (!$length) {
		return true;
	}
	return substr($haystack, -$length) === $needle;
}
