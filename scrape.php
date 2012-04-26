<?php

$url = 'http://www.php.net/manual/en/class.gearmanclient.php';
echo "Downloading: $url...";
$html = file_get_contents($url);
echo " Done!\n";
$rootUrl = preg_replace('/[^\/]+$/', '', $url);
echo "Root URL: $rootUrl\n";

$matches = array();
if (preg_match('/
		\<div[ ]class="section"
		.*
		\<p[ ]class="para"\>
		(?<desc>.*?)
		\<\/p\>
		.*
		\<div[ ]class="classsynopsis"\>
		.*
		\<strong[ ]class="classname"\>(?<class>[^\<]+)<\/strong\>
		.*?
		(?<lis>
			(?:
				\<li\>(?:.+?)\<\/li\>
			)+
		)
	/xms', $html, $matches)) {

	$className = $matches['class'];
	$description = $matches['desc'];
	echo "Class: $className\n";
	echo "Description: $description\n";

	$lis = $matches['lis'];

	$matches = array();
	if (preg_match_all('/a href=\"([^"]+)\"/', $lis, $matches)) {
		foreach ($matches[1] as $ref) {
			echo "Downloading: $ref...";
			$html = file_get_contents($rootUrl . 'gearmanclient.construct.php');
			echo " Done!\n";

			$matches = array();
			if (preg_match('/
					\<p[ ]class="verinfo"\>(?<verinfo>.*?)\<\/p\>
					.*?
					\<div[ ]class="methodsynopsis[ ]dc-description"\>
					.*?
					\<span[ ]class="modifier">(?<modifier>[^<]+?)\<\/span\>
					.*?
					\<span[ ]class="type">(?<type>.*?)\<\/span\>
					.*?
					\<span[ ]class="methodname">
					.*?
					\<strong\>
					.*?
					::(?<name>.+?)
					\<\/strong\>
					.*?\(
					(?<params>.+?)
					\)
					.*?
					\<p[ ]class="para[ ]rdfs-comment"\>
					(?<desc>.+?)
					\<\/p\>
					.*?
					\<div[ ]class="refsect1[ ]parameters"
					.*?
					(?:
						\<dl\>
						(?<parameterdescs>
							.*?
						)
						\<\/dl\>
					)?
					.*?
					\<div[ ]class="refsect1[ ]returnvalues"
					.*?
					\<p[ ]class="para"\>
					(?<returndesc>.+?)
					\<\/p\>
				/xms', $html, $matches)) {

				$verInfo = $matches['verinfo'];
				$modifier = $matches['modifier'];
				$type = $matches['type'];
				$name = $matches['name'];
				$desc = $matches['desc'];

				echo "Verinfo:  $verInfo\n";
				echo "Modifier: $modifier\n";
				echo "Type:     $type\n";
				echo "Name:     $name\n";
				echo "Desc:     $desc\n";

				$matches = array();

			}
		}
	}
}


/*
					\<div[ ]class="methodsynopsis[ ]dc-description"\>
					.*?
					\<span[ ]class="modifier">(?<modifier>.*+)\<\/span\>
					.*?
					\<span[ ]class="type">(?<type>.*+)\<\/spa\n>
					.*?
					\<span[ ]class="methodname">
					.*?
					\<strong\>
					.*?
					::(?<name>.*+)
					\<\/strong\>
					.*?\(
					(?<params>.*+)
					\)

 */