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

	$class = new stdClass();
	$class->name = $className;
	$class->description = trim(preg_replace(array("/\n/", '/\s+/'), array(' ', ' '), strip_tags($description)));
	$class->link = $url;
	$class->functions = array();

	$lis = $matches['lis'];

	$matches = array();
	if (preg_match_all('/a href=\"([^"]+)\"/', $lis, $matches)) {
		$count = 0;
		$total = count($matches[1]);
		echo "Processing $total functions...\n";
		foreach ($matches[1] as $ref) {
			$count++;
			$function = new stdClass();
			$function->link = $rootUrl . $ref;
			echo "Downloading ($count/$total) $ref...";
			$html = file_get_contents($rootUrl . $ref);
			echo " Done!\n";

			$matches = array();
			if (preg_match('/
					\<p[ ]class="verinfo"\>(?<verinfo>.*?)\<\/p\>
					.*?
					\<div[ ]class="methodsynopsis[ ]dc-description"\>
					.*?
					\<span[ ]class="modifier">(?<modifier>[^<]+?)\<\/span\>
					.*?
					(?:
						\s*\<span[ ]class="type"\>(?<type>.*?)\<\/span\>
					)?
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
					(?<parameterdescs>
						.*?
					)
					\<div[ ]class="refsect1[ ]returnvalues"
					.*?
					\<p[ ]class="para"\>
					(?<returndesc>.+?)
					\<\/p\>
				/xms', $html, $matches)) {

				$verInfo = html_entity_decode($matches['verinfo']);
				$modifier = $matches['modifier'];
				$type = strip_tags($matches['type']);
				$name = $matches['name'];
				$desc = trim(preg_replace(array("/\n/", '/\s+/'), array(' ', ' '), strip_tags($matches['desc'])));
				$returnDesc = trim(preg_replace(array("/\n/", '/\s+/'), array(' ', ' '), strip_tags($matches['returndesc'])));
				$paramsString = $matches['params'];
				$paramsDescString = $matches['parameterdescs'];

				$function->verInfo = $verInfo;
				$function->modifier = $modifier;
				$function->returnType = $type;
				$function->name = $name;
				$function->description = $desc;
				$function->returnDescription = $returnDesc;
				$function->parameters = array();

				$parameterDescs = array();
				$matches = array();
				preg_match_all('/\<dd\>\s+\<p class="para"\>(.+?)\<\/p\>/ms', $paramsDescString, $matches);
				$parameterDescs = $matches[1];

				$matches = array();
				if (preg_match_all('/
						(?<optional>\[,[ ])?
						\<span[ ]class="methodparam"\>
						\<span[ ]class="type"\>(?<type>.*?)\<\/span\>
						.*?
						class="parameter(?:[ ]reference)?"\>(?<name>&?\$[a-zA-Z0-9_]+)
					/xms', $paramsString, $matches) == count($parameterDescs)) {

					$i = 0;
					foreach ($matches['name'] as $name) {
						$desc = trim(preg_replace(array("/\n/", '/\s+/'), array(' ', ' '), strip_tags($parameterDescs[$i])));
						$type = strip_tags($matches['type'][$i]);
						if ($matches['optional'][$i]) {
							$default = 'null';
						} else {
							$default = false;
						}
						$param = new stdClass();
						$param->name = $name;
						$param->description = trim(preg_replace(array("/\n/", '/\s+/'), array(' ', ' '), strip_tags($description)));
						$param->type = $type;
						$param->default = $default;
						$function->parameters[] = $param;
						$i++;
					}
				} else {
					die("Could not pair parameters from:\n$paramsString\nwith:\n" . implode("\n", $parameterDescs));
				}
			} else {
				die ("Failed to match on $ref");
			}

			$class->functions[] = $function;
		}
	}
}

echo "Done\n";

function writeOut(stdClass $c) {
	$res = "<?php
/**
 * {$c->name} class skeleton, for IDE auto completion.
 *
 * @author Jens Riisom Schultz <ibber_of_crew42@hotmail.com>
 */
/**
 * {$c->description}
 *
 * @link {$c->link}
 */
class {$c->name} {
";
	foreach ($c->functions as $f) {
		$res .= "\t/**
\t * {$f->verInfo}
\t * {$f->description}
\t *
\t * @link {$f->link}
 ";
		foreach ($f->parameters as $p) {
			$res .= "\t * @param {$p->type} {$p->name} {$p->description}\n";
		}
		$res .= "\t * @return {$f->returnType} {$f->returnDescription}\n";
		$res .= "\t */\n";
		$res .= "\t{$f->modifier} function {$f->name} (";
		$fp = array();
		foreach ($f->parameters as $p) {
			$pr = $p->name;
			if ($p->default) {
				$pr .= " = {$p->default}";
			}
			$fp[] = $pr;
		}
		$res .= implode(', ', $fp) . ") {}\n\n";
	}

	$res .= "}\n";

	file_put_contents($c->name . '.php', $res);

	return $res;
}