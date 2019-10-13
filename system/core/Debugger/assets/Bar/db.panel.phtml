<?php
namespace Debugger;
defined('BASE') or exit('No direct script access allowed');
?>
<style>tr.mono { font-family: monospace !important; }</style>
<h1>DB Queries</h1>
<div class="debugger-inner debugger-DatabasePanel">
    <?php $data = $this->getQueryStatistics(); ?>
    <b>Summary</b>
    <br>
    <table>
        <tr>
            <th>Total Time (ms)</th>
            <th>Average (ms)</th>
            <th>Queries</th>
            <th>Affected Rows</th>
            <th>Changes</th>
            <th>Server Version</th>
            <th>Presistent Mode</th>
        </tr>
        <tr class="mono">
            <td><?php echo $data['total_time'] ?></td>
            <td><?php echo $data['avg_query_time'] ?></td>
            <td><?php echo $data['num_queries'] ?></td>
            <td><?php echo $data['num_rows'] ?></td>
            <td><?php echo $data['num_changes'] ?></td>
            <td><?php echo $this->getDbServerVersion() ?></td>
            <td><?php var_dump($this->getDbPresistenMode()) ?></td>
        </tr>
    </table>
    <br>
    <b>Details</b>
    <table>
        <tr>
            <th>Query</th>
            <th>Time (ms)</th>
            <th>Affected</th>
            <th>Changes</th>
        </tr>
    <?php foreach ($data['queries'] as $item): ?>
        <tr class="mono">
            <td><?php echo $item['query'] ?></td>
            <td><?php echo $item['time'] ?></td>
            <td><pre><?php echo $item['rows'] ?></pre></td>
            <td><pre><?php echo $item['changes'] ?></pre></td>
        </tr>
    <?php endforeach; ?>
    </table>
</div>