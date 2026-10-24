<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Not Found &middot; Fest Event Organizer</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="error-page">
    <div class="error-card">
        <h1><?= http_response_code() === 403 ? '403' : '404' ?></h1>
        <p class="text-slateink mt-2 mb-6">
            <?= http_response_code() === 403
                ? 'You do not have permission to view that page.'
                : 'The page you are looking for could not be found.' ?>
        </p>
        <a href="/index.php" class="btn-primary">Go home</a>
    </div>
</div>
</body>
</html>
