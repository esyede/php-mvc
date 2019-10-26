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
	</svg>
	<span class="tracy-label"><?php
        $count = $this->getQueryCount();
        echo $count.' / '.round($this->getTotalQueryTime(), 3, PHP_ROUND_HALF_UP).' ms';
        ?></span>
</span>