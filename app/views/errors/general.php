<!DOCTYPE html>
<meta charset="utf-8">
<meta name="robots" content="noindex">
<title><?php echo $code.' '.$text; ?>!</title>

<style>
	#error-page { background: white; width: 500px; margin: 70px auto; padding: 10px 20px }
	#error-page h1 { font: bold 47px/1.5 sans-serif; background: none; color: #333; margin: .6em 0 }
	#error-page p { font: 21px/1.5 Georgia,serif; background: none; color: #333; margin: 1.5em 0 }
	#error-page small { font-size: 70%; color: gray }
</style>

<div id="error-page">
	<h1><?php echo $text; ?>!</h1>

	<p><?php echo $message; ?></p>

	<p><small>Code: <?php echo $code; ?></small></p>
</div>
