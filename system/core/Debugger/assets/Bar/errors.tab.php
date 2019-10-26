<?php

namespace Debugger;

defined('BASE') or exit('No direct script access allowed');

if (empty($data)) {
    return;
}
?>
<svg version="1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" enable-background="new 0 0 48 48">
    <path fill="#D50000" d="M24,6C14.1,6,6,14.1,6,24s8.1,18,18,18s18-8.1,18-18S33.9,6,24,6z M24,10c3.1,0,6,1.1,8.4,2.8L12.8,32.4 C11.1,30,10,27.1,10,24C10,16.3,16.3,10,24,10z M24,38c-3.1,0-6-1.1-8.4-2.8l19.6-19.6C36.9,18,38,20.9,38,24C38,31.7,31.7,38,24,38 z"/>
</svg>
<span style="
	display: inline-block;
	background: #D51616;
	color: white;
	font-weight: bold;
	margin: -1px -.4em;
	padding: 1px .4em;
" title="Severity errors">
	<?php echo $sum = array_sum($data), $sum > 1 ? ' errors' : ' error'; ?>
</span>
<!-- </span> -->
