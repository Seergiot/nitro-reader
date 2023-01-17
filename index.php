<?php
require_once dirname(__FILE__).'/nitro-reader.php';

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