<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 40px 20px; }
        .card { background: #fff; max-width: 500px; margin: auto; border-radius: 10px; padding: 40px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .logo { font-size: 22px; font-weight: bold; margin-bottom: 24px; }
        .logo span { color: #FFB400; }
        h2 { font-size: 20px; color: #111; margin: 0 0 12px; }
        p { color: #555; line-height: 1.6; margin: 0 0 24px; }
        .btn { display: inline-block; background: #FFB400; color: #000; padding: 14px 28px; border-radius: 8px; text-decoration: none; font-weight: bold; }
        .expire { font-size: 13px; color: #999; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo"><span>Y</span>AMS Drive</div>
        <h2>Un document a été partagé avec vous</h2>
        <p>Cliquez sur le bouton ci-dessous pour accéder au document. Ce lien est temporaire et expirera le <strong>{{ $expiresAt->format('d/m/Y à H:i') }}</strong>.</p>
        <a href="{{ $url }}" class="btn">Accéder au document</a>
        <p class="expire">Si vous n'attendiez pas ce partage, ignorez cet email.</p>
    </div>
</body>
</html>