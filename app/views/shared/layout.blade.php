@include('shared.header')
<body>
	<div id="container">
		<h3>Welcome to <span class="reddish">{{ $title }}</span></h3>
		<p>{{ $content }}</p>
	</div>
	@include('shared.footer')
</body>
</html>