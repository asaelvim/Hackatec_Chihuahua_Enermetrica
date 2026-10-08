@props(['icon' => false])

<img src="{{ asset($icon ? 'favicon.png' : 'images/logo.png') }}" alt="{{ config('app.name') }}" {{ $attributes }}>

