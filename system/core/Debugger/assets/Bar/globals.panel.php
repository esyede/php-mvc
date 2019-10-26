<?php

namespace Debugger;

defined('BASE') or exit('No direct script access allowed');

$server = isset($GLOBALS['_SERVER']) ? $GLOBALS['_SERVER'] : null;
$request = isset($GLOBALS['_REQUEST']) ? $GLOBALS['_REQUEST'] : null;
$post = isset($GLOBALS['_POST']) ? $GLOBALS['_POST'] : null;
$get = isset($GLOBALS['_GET']) ? $GLOBALS['_GET'] : null;
$files = isset($GLOBALS['_FILES']) ? $GLOBALS['_FILES'] : null;
$env = isset($GLOBALS['_ENV']) ? $GLOBALS['_ENV'] : null;
$cookie = isset($GLOBALS['_COOKIE']) ? $GLOBALS['_COOKIE'] : null;
$session = isset($GLOBALS['_SESSION']) ? $GLOBALS['_SESSION'] : null;
?>
<style>tr.mono { font-family: monospace !important; }</style>
<h1>Globals</h1>
<div class="debugger-inner debugger-GlobalsPanel">
    <b>Cookie</b>
    <br>
    <table>
        <tr>
            <th>Key</th>
            <th>Value</th>
        </tr>
        <?php foreach ($cookie as $key => $value): ?>
            <tr class="mono">
                <td><?php echo $key; ?></td>
                <td><?php echo $value; ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>