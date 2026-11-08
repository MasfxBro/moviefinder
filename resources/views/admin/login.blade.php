<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="robots" content="noindex"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Administrator sign in</title></head><body>
<main><h1>Administrator sign in</h1>
@if ($errors->any())<p role="alert">Invalid credentials.</p>@endif
<form method="POST" action="">@csrf
<label>Email <input type="email" name="email" required autocomplete="username"></label>
<label>Password <input type="password" name="password" required autocomplete="current-password"></label>
<button type="submit">Continue</button></form></main></body></html>
