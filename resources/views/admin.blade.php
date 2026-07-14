{{--
    POS Admin — Vue SPA entry point.
    All frontend routes handled by Vue Router.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="icon" type="image/x-icon" href="/favicon.ico" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <title>POS Admin</title>
    @vite(['resources/css/app.css', 'resources/js/admin/main.js'])
</head>
<body>
    <div id="app"></div>
</body>
</html>
