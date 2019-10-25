@extends('shared.layout')
	{!!
		$form->init('login', ['action' => site('auth/login'), 'method' => 'post'])
			->label('Username')->newline()
			->input('username')->newline()
			->label('Password')->newline()
			->input('password', 'password')->newline()
			->submit('login', 'Login')
			->reset('reset', 'Reset')
			->render();
	!!}
@endsection