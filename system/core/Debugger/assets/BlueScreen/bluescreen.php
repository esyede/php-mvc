<?php

namespace Debugger;

defined('BASE') or exit('No direct script access allowed');

$code = $exception->getCode() ? ' #'.$exception->getCode() : '';

?><!DOCTYPE html><!-- "' --></script></style></pre></xmp></table></a></abbr></address></article></aside></audio></b></bdi></bdo></blockquote></button></canvas></caption></cite></code></datalist></del></details></dfn></div></dl></em></fieldset></figcaption></figure></footer></form></h1></h2></h3></h4></h5></h6></header></hgroup></i></iframe></ins></kbd></label></legend></map></mark></menu></meter></nav></noscript></object></ol></optgroup></output></progress></q></rp></rt></ruby></s></samp></section></select></small></span></strong></sub></summary></sup></textarea></time></title></tr></u></ul></var></video>
<html>
<head>
	<meta charset="utf-8">
	<meta name="robots" content="noindex">

	<title><?php echo htmlspecialchars($title.': '.$exception->getMessage().$code, ENT_IGNORE, 'UTF-8'); ?></title>

	<style type="text/css" class="debugger-debug">
	<?php echo preg_replace('#[\r\n\t ]+#', ' ', file_get_contents(__DIR__.'/bluescreen.css')); ?>
	</style>
	<script>document.documentElement.className+=' debugger-js'</script>
</head>


<body>
<div id="debugger-bs">
	<a id="debugger-bs-toggle" href="#" class="debugger-toggle"></a>
	<div>
		<div id="debugger-bs-error" class="panel">
			<?php if ($exception->getMessage()): ?><p><?php echo htmlspecialchars($title.$code, ENT_IGNORE, 'UTF-8'); ?></p><?php endif; ?>


			<h1><?php echo htmlspecialchars($exception->getMessage() ?: $title.$code, ENT_IGNORE, 'UTF-8'); ?>
			<a href="https://www.google.com/search?sourceid=debugger&amp;q=<?php echo urlencode($title.' '.preg_replace('#\'.*\'|".*"#Us', '', $exception->getMessage())); ?>" target="_blank" rel="noreferrer noopener">search&#x25ba;</a>
			<?php if ($skipError): ?><a href="<?php echo htmlspecialchars($skipError, ENT_IGNORE | ENT_QUOTES, 'UTF-8'); ?>">skip error&#x25ba;</a><?php endif; ?></h1>
		</div>

		<?php if ($prev = $exception->getPrevious()): ?>
		<div class="caused">
			<a href="#debuggerCaused">Caused by <?php echo htmlspecialchars(Helpers::getClass($prev), ENT_NOQUOTES, 'UTF-8'); ?></a>
		</div>
		<?php endif; ?>


		<?php $ex = $exception; $level = 0; ?>
		<?php do { ?>

			<?php if ($level++): ?>
			<div class="panel"<?php if (2 === $level) {
    echo ' id="debuggerCaused"';
} ?>>
			<h2><a data-debugger-ref="^+" class="debugger-toggle<?php echo ($collapsed = $level > 2) ? ' debugger-collapsed' : ''; ?>">Caused by</a></h2>

			<div class="<?php echo $collapsed ? 'debugger-collapsed ' : ''; ?>inner">
				<div class="panel">
					<h2><?php echo htmlspecialchars(Helpers::getClass($ex).($ex->getCode() ? ' #'.$ex->getCode() : ''), ENT_NOQUOTES, 'UTF-8'); ?></h2>

					<h2><?php echo htmlspecialchars($ex->getMessage(), ENT_IGNORE, 'UTF-8'); ?></h2>
				</div>
			<?php endif; ?>


			<?php foreach ($panels as $panel): ?>
			<?php $panel = call_user_func($panel, $ex); if (empty($panel['tab']) || empty($panel['panel'])) {
    continue;
} ?>
			<?php if (! empty($panel['bottom'])) {
    continue;
} ?>
			<div class="panel">
				<h2><a data-debugger-ref="^+" class="debugger-toggle"><?php echo htmlspecialchars($panel['tab'], ENT_NOQUOTES, 'UTF-8'); ?></a></h2>

				<div class="inner">
				<?php echo $panel['panel']; ?>
			</div></div>
			<?php endforeach; ?>


			<?php $stack = $ex->getTrace(); $expanded = null; ?>
			<?php if ((! $exception instanceof \ErrorException || in_array($exception->getSeverity(), [E_USER_NOTICE, E_USER_WARNING, E_USER_DEPRECATED])) && $this->isCollapsed($ex->getFile())) {
    foreach ($stack as $key => $row) {
        if (isset($row['file']) && ! $this->isCollapsed($row['file'])) {
            $expanded = $key;
            break;
        }
    }
} ?>

			<div class="panel">
			<h2><a data-debugger-ref="^+" class="debugger-toggle<?php echo ($collapsed = null !== $expanded) ? ' debugger-collapsed' : ''; ?>">Source file</a></h2>

			<div class="<?php echo $collapsed ? 'debugger-collapsed ' : ''; ?>inner">
				<p><b>File:</b> <?php echo Helpers::editorLink($ex->getFile(), $ex->getLine()); ?></p>
				<?php if (is_file($ex->getFile())): ?><?php echo self::highlightFile($ex->getFile(), $ex->getLine(), 15, $ex instanceof \ErrorException && isset($ex->context) ? $ex->context : null); ?><?php endif; ?>
			</div></div>


			<?php if (isset($stack[0]['class']) && 'Debugger\Debugger' === $stack[0]['class'] && ('shutdownHandler' === $stack[0]['function'] || 'errorHandler' === $stack[0]['function'])) {
    unset($stack[0]);
} ?>
			<?php if ($stack): ?>
			<div class="panel">
				<h2><a data-debugger-ref="^+" class="debugger-toggle">Call stack</a></h2>

				<div class="inner">
				<ol>
					<?php foreach ($stack as $key => $row): ?>
					<li><p>

					<?php if (isset($row['file']) && is_file($row['file'])): ?>
						<?php echo Helpers::editorLink($row['file'], $row['line']); ?>
					<?php else: ?>
						<i>inner-code</i><?php if (isset($row['line'])) {
    echo ':', $row['line'];
} ?>
					<?php endif; ?>

					<?php if (isset($row['file']) && is_file($row['file'])): ?><a data-debugger-ref="^p + .file" class="debugger-toggle<?php if ($expanded !== $key) {
    echo ' debugger-collapsed';
} ?>">source</a>&nbsp; <?php endif; ?>

					<?php
                        if (isset($row['object'])) {
                            echo "<a data-debugger-ref='^p + .object' class='debugger-toggle debugger-collapsed'>";
                        }
                        if (isset($row['class'])) {
                            echo htmlspecialchars($row['class'].$row['type'], ENT_NOQUOTES, 'UTF-8');
                        }
                        if (isset($row['object'])) {
                            echo '</a>';
                        }
                        echo htmlspecialchars($row['function'], ENT_NOQUOTES, 'UTF-8'), '(';
                        if (! empty($row['args'])): ?><a data-debugger-ref="^p + .args" class="debugger-toggle debugger-collapsed">arguments</a><?php endif; ?>)
					</p>

					<?php if (isset($row['file']) && is_file($row['file'])): ?>
						<div class="<?php if ($expanded !== $key) {
                            echo 'debugger-collapsed ';
                        } ?>file"><?php echo self::highlightFile($row['file'], $row['line']); ?></div>
					<?php endif; ?>

					<?php if (isset($row['object'])): ?>
						<div class="debugger-collapsed outer object"><?php echo Dumper::toHtml($row['object'], [Dumper::LIVE => true]); ?></div>
					<?php endif; ?>

					<?php if (! empty($row['args'])): ?>
						<div class="debugger-collapsed outer args">
						<table>
						<?php
                        try {
                            $r = isset($row['class']) ? new \ReflectionMethod($row['class'], $row['function']) : new \ReflectionFunction($row['function']);
                            $params = $r->getParameters();
                        } catch (\Exception $e) {
                            $params = [];
                        }
                        foreach ($row['args'] as $k => $v) {
                            echo '<tr><th>', htmlspecialchars(isset($params[$k]) ? '$'.$params[$k]->name : "#$k", ENT_IGNORE, 'UTF-8'), '</th><td>';
                            echo Dumper::toHtml($v, [Dumper::LOCATION => Dumper::LOCATION_CLASS, Dumper::LIVE => true]);
                            echo "</td></tr>\n";
                        }
                        ?>
						</table>
						</div>
					<?php endif; ?>
					</li>
					<?php endforeach; ?>
				</ol>
			</div></div>
			<?php endif; ?>


			<?php if ($ex instanceof \ErrorException && isset($ex->context) && is_array($ex->context)):?>
			<div class="panel">
			<h2><a data-debugger-ref="^+" class="debugger-toggle debugger-collapsed">Variables</a></h2>

			<div class="debugger-collapsed inner">
			<div class="outer">
			<table>
			<?php
            foreach ($ex->context as $k => $v) {
                echo '<tr><th>$', htmlspecialchars($k, ENT_IGNORE, 'UTF-8'), '</th><td>', Dumper::toHtml($v, [Dumper::LOCATION => Dumper::LOCATION_CLASS, Dumper::LIVE => true]), "</td></tr>\n";
            }
            ?>
			</table>
			</div>
			</div></div>
			<?php endif; ?>

		<?php } while ($ex = $ex->getPrevious()); ?>
		<?php while (--$level) {
                echo '</div></div>';
            } ?>


		<?php if (count((array) $exception) > count((array) new \Exception())):?>
		<div class="panel">
		<h2><a data-debugger-ref="^+" class="debugger-toggle debugger-collapsed">Exception</a></h2>
		<div class="debugger-collapsed inner">
		<?php echo Dumper::toHtml($exception, [Dumper::LOCATION => Dumper::LOCATION_CLASS, Dumper::LIVE => true]); ?>
		</div></div>
		<?php endif; ?>


		<?php $bottomPanels = []; ?>
		<?php foreach ($panels as $panel): ?>
		<?php $panel = call_user_func($panel, null); if (empty($panel['tab']) || empty($panel['panel'])) {
                continue;
            } ?>
		<?php if (! empty($panel['bottom'])) {
                $bottomPanels[] = $panel;
                continue;
            } ?>
		<div class="panel">
			<h2><a data-debugger-ref="^+" class="debugger-toggle debugger-collapsed"><?php echo htmlspecialchars($panel['tab'], ENT_NOQUOTES, 'UTF-8'); ?></a></h2>

			<div class="debugger-collapsed inner">
			<?php echo $panel['panel']; ?>
		</div></div>
		<?php endforeach; ?>


		<div class="panel">
		<h2><a data-debugger-ref="^+" class="debugger-toggle debugger-collapsed">Environment</a></h2>

		<div class="debugger-collapsed inner">
			<h3><a data-debugger-ref="^+" class="debugger-toggle">$_SERVER</a></h3>
			<div class="outer">
			<table>
			<?php
            foreach ($_SERVER as $k => $v) {
                echo '<tr><th>', htmlspecialchars($k, ENT_IGNORE, 'UTF-8'), '</th><td>', Dumper::toHtml($v), "</td></tr>\n";
            }
            ?>
			</table>
			</div>


			<h3><a data-debugger-ref="^+" class="debugger-toggle">$_SESSION</a></h3>
			<div class="outer">
			<?php if (empty($_SESSION)):?>
			<p><i>empty</i></p>
			<?php else: ?>
			<table>
			<?php
            foreach ($_SESSION as $k => $v) {
                echo '<tr><th>', htmlspecialchars($k, ENT_IGNORE, 'UTF-8'), '</th><td>', '__DEBUGGER' === $k ? '<i>App Session</i>' : Dumper::toHtml($v, [Dumper::LOCATION => Dumper::LOCATION_CLASS, Dumper::LIVE => true]), "</td></tr>\n";
            }
            ?>
			</table>
			<?php endif; ?>
			</div>


			<?php if (! empty($_SESSION['__DEBUGGER']['DATA'])):?>
			<h3><a data-debugger-ref="^+" class="debugger-toggle">App Session</a></h3>
			<div class="outer">
			<table>
			<?php
            foreach ($_SESSION['__DEBUGGER']['DATA'] as $k => $v) {
                echo '<tr><th>', htmlspecialchars($k, ENT_IGNORE, 'UTF-8'), '</th><td>', Dumper::toHtml($v, [Dumper::LOCATION => Dumper::LOCATION_CLASS, Dumper::LIVE => true]), "</td></tr>\n";
            }
            ?>
			</table>
			</div>
			<?php endif; ?>


			<?php
            $list = get_defined_constants(true);
            if (! empty($list['user'])):?>
			<h3><a data-debugger-ref="^+" class="debugger-toggle debugger-collapsed">Constants</a></h3>
			<div class="outer debugger-collapsed">
			<table>
			<?php
            foreach ($list['user'] as $k => $v) {
                echo '<tr><th>', htmlspecialchars($k, ENT_IGNORE, 'UTF-8'), '</th>';
                echo '<td>', Dumper::toHtml($v), "</td></tr>\n";
            }
            ?>
			</table>
			</div>
			<?php endif; ?>


			<h3><a data-debugger-ref="^+" class="debugger-toggle debugger-collapsed">Included files</a> (<?php echo count(get_included_files()); ?>)</h3>
			<div class="outer debugger-collapsed">
			<table>
			<?php
            foreach (get_included_files() as $v) {
                echo '<tr><td>', htmlspecialchars($v, ENT_IGNORE, 'UTF-8'), "</td></tr>\n";
            }
            ?>
			</table>
			</div>


			<h3><a data-debugger-ref="^+" class="debugger-toggle debugger-collapsed">Configuration options</a></h3>
			<div class="outer debugger-collapsed">
			<?php ob_start(); @phpinfo(INFO_CONFIGURATION | INFO_MODULES); echo preg_replace('#^.+<body>|</body>.+\z#s', '', ob_get_clean()); // @ phpinfo can be disabled?>
			</div>
		</div></div>


		<div class="panel">
		<h2><a data-debugger-ref="^+" class="debugger-toggle debugger-collapsed">HTTP request</a></h2>

		<div class="debugger-collapsed inner">
			<?php if (function_exists('apache_request_headers')): ?>
			<h3>Headers</h3>
			<div class="outer">
			<table>
			<?php
            foreach (apache_request_headers() as $k => $v) {
                echo '<tr><th>', htmlspecialchars($k, ENT_IGNORE, 'UTF-8'), '</th><td>', htmlspecialchars($v, ENT_IGNORE, 'UTF-8'), "</td></tr>\n";
            }
            ?>
			</table>
			</div>
			<?php endif; ?>


			<?php foreach (['_GET', '_POST', '_COOKIE'] as $name): ?>
			<h3>$<?php echo htmlspecialchars($name, ENT_NOQUOTES, 'UTF-8'); ?></h3>
			<?php if (empty($GLOBALS[$name])):?>
			<p><i>empty</i></p>
			<?php else: ?>
			<div class="outer">
			<table>
			<?php
            foreach ($GLOBALS[$name] as $k => $v) {
                echo '<tr><th>', htmlspecialchars($k, ENT_IGNORE, 'UTF-8'), '</th><td>', Dumper::toHtml($v), "</td></tr>\n";
            }
            ?>
			</table>
			</div>
			<?php endif; ?>
			<?php endforeach; ?>
		</div></div>


		<div class="panel">
		<h2><a data-debugger-ref="^+" class="debugger-toggle debugger-collapsed">HTTP response</a></h2>

		<div class="debugger-collapsed inner">
			<h3>Headers</h3>
			<?php if (headers_list()): ?>
			<pre><?php
            foreach (headers_list() as $s) {
                echo htmlspecialchars($s, ENT_IGNORE, 'UTF-8'), '<br>';
            }
            ?></pre>
			<?php else: ?>
			<p><i>no headers</i></p>
			<?php endif; ?>
		</div></div>


		<?php foreach ($bottomPanels as $panel): ?>
		<div class="panel">
			<h2><a data-debugger-ref="^+" class="debugger-toggle"><?php echo htmlspecialchars($panel['tab'], ENT_NOQUOTES, 'UTF-8'); ?></a></h2>

			<div class="inner">
			<?php echo $panel['panel']; ?>
		</div></div>
		<?php endforeach; ?>


		<ul>
			<li>Report generated at <?php echo @date('Y/m/d H:i:s'); // @ timezone may not be set?></li>
			<li><?php if ($sourceIsUrl): ?><a href="<?php echo htmlspecialchars($source, ENT_IGNORE | ENT_QUOTES, 'UTF-8'); ?>"><?php endif; ?><?php echo htmlspecialchars($source, ENT_IGNORE, 'UTF-8'); ?><?php if ($sourceIsUrl): ?></a><?php endif; ?></li>
			<?php foreach ($info as $item): ?><li><?php echo htmlspecialchars($item, ENT_NOQUOTES, 'UTF-8'); ?></li><?php endforeach; ?>
		</ul>
	</div>
</div>

<script>
(function() {
	if (!document.documentElement.classList) {
		document.getElementById('debugger-bs-error').innerHTML += '<div id=debugger-bs-ie-warning>Warning: Debugger requires IE 10+<\/div>';
		return;
	}
	<?php readfile(__DIR__.'/../Dumper/dumper.js'); ?>
	<?php readfile(__DIR__.'/bluescreen.js'); ?>
})();
</script>
<script>
Debugger && Debugger.Dumper.init(<?php echo json_encode(Dumper::fetchLiveData()); ?>);
</script>
</body>
</html>
