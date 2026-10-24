<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= http_response_code() === 419 ? 'Session Expired' : 'Server Error' ?> &middot; Fest Event Organizer</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="error-page">
    <div class="error-card error-card-alert">
        <h1><?= http_response_code() === 419 ? '419' : '500' ?></h1>
        <p class="text-slateink mt-2 mb-6">
            <?= http_response_code() === 419
                ? 'Your session token was missing or expired. Please go back and try again.'
                : 'Something went wrong on our end. Please try again.' ?>
        </p>
        <a href="/index.php" class="btn-primary">Go home</a>
    </div>
</div>
</body>
</html>
