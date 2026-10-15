<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Welcome to {{ $name }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="@asset('css/main.css')">
    <script src="@asset('js/my_js.js')"></script>
</head>
<body>
    <div class="wrap">
        <div class="card" role="main" aria-labelledby="welcome-heading">
            @yield("info")
            <div class="links" aria-hidden="false">
                <a class="link-item" target="_blank" href="https://github.com/Advanced-Web-Tools/Advanced-Web-Tools/wiki/Framework-Guide">
                    Docs
                    <span style="opacity:.7">→</span>
                </a>
                <a class="link-item" target="_blank" href="https://github.com/Advanced-Web-Tools/Advanced-Web-Tools/">
                    GitHub
                    <span style="opacity:.7">→</span>
                </a>
                <a class="link-item" target="_blank" href="https://github.com/ElStefanos/Quil">
                    Example package
                    <span style="opacity:.7">→</span>
                </a>
            </div>
        </div>
    </div>
</body>
</html>