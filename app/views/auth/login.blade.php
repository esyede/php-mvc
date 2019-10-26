@extends('shared.layout')

@section('login-form')
	{!!
		$form->init('login', ['action' => site('auth/login'), 'method' => 'post'])
			->label('username', 'Your username')->newline()
			->input('username')->newline()->newline()
			->label('password', 'Your password')->newline()
			->input('password', 'password')->newline()->newline()
			->submit('login', 'Login')
			->reset('reset', 'Reset')
			->render();
	!!}
@endsection