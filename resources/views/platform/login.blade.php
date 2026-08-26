@extends('platform.layout')

@section('title', 'Sign in')

@section('content')
    <div class="card" style="max-width: 24rem; margin: 3rem auto 0;">
        <h1>Back office</h1>
        <p class="sub">Staff sign-in. This is not a client workspace.</p>

        <form method="POST" action="{{ route('platform.login.attempt') }}">
            @csrf

            <div style="margin-bottom: .85rem;">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
            </div>

            <div style="margin-bottom: 1.1rem;">
                <label for="password">Password</label>
                <input id="password" type="password" name="password" required autocomplete="current-password">
            </div>

            <label style="display: flex; align-items: center; gap: .4rem; margin-bottom: 1.1rem;">
                <input type="checkbox" name="remember" value="1" style="width: auto;">
                <span>Stay signed in</span>
            </label>

            <button type="submit" class="primary" style="width: 100%;">Sign in</button>
        </form>
    </div>
@endsection
