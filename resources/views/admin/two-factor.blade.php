<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="robots" content="noindex"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Administrator verification</title></head><body>
<main><h1>Two-factor verification</h1>
@if ($errors->any())<p role="alert">Invalid authentication code.</p>@endif
<form method="POST" action="">@csrf
<label>Authentication code <input name="code" required inputmode="numeric" autocomplete="one-time-code"></label>
<button type="submit">Verify</button></form></main></body></html>
