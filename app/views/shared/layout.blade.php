@include('shared.header')
<body>
	<div id="container">
		<h3>Welcome to <span class="reddish">{{ $title }}</span></h3>
		<p>{{ $content }}</p>
		@if(! is_null($this->block('login-form')))
			@yield('login-form')
		@endif
	</div>
	@include('shared.footer')
</body>
</html>