<?php
namespace Debugger;
defined('BASE') or exit('No direct script access allowed');
?>
<span class="debugger-label" title="Database queries">
	<svg version="1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" enable-background="new 0 0 48 48">
	    <g fill="#D1C4E9">
	        <path d="M38,7H10C8.9,7,8,7.9,8,9v6c0,1.1,0.9,2,2,2h28c1.1,0,2-0.9,2-2V9C40,7.9,39.1,7,38,7z"/>
	        <path d="M38,19H10c-1.1,0-2,0.9-2,2v6c0,1.1,0.9,2,2,2h28c1.1,0,2-0.9,2-2v-6C40,19.9,39.1,19,38,19z"/>
	        <path d="M38,31H10c-1.1,0-2,0.9-2,2v6c0,1.1,0.9,2,2,2h28c1.1,0,2-0.9,2-2v-6C40,31.9,39.1,31,38,31z"/>
	    </g>
	    <g fill="#2196F3">
	        <polygon points="31,30 38,35.6 38,24.4"/>
	        <path d="M38,28c-0.3,0-0.7,0-1,0.1v4c0.3-0.1,0.7-0.1,1-0.1c3.3,0,6,2.7,6,6s-2.7,6-6,6s-6-2.7-6-6 c0-0.3,0-0.6,0.1-0.9l-3.4-2.7C28.3,35.5,28,36.7,28,38c0,5.5,4.5,10,10,10s10-4.5,10-10S43.5,28,38,28z"/>
	    </g>
	</svg>
	<span class="tracy-label"><?php
		$count = 77;
		$time = 211.002;
		echo $count.' / '.$time.' ms'
		?></span>
</span>