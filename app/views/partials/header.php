<?php
/** @var string|null $pageTitle */
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle ?? 'Dashboard') ?> &middot; Fest Event Organizer</title>
<link rel="icon" href="data:,">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&amp;family=Inter:wght@400;500;600;700&amp;family=JetBrains+Mono:wght@500;700&amp;display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script>
    // Deep Teal & Charcoal -- a professional, corporate identity for this
    // project (no gradients, flat fills throughout). Amber carries prizes,
    // mint/rose carry approval state -- colour means something here, it isn't
    // decoration.
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    cyan: { DEFAULT: '#0E7490', dark: '#0B5A70', 50: '#E8F6F9' },
                    teal: { DEFAULT: '#0F4C4C', dark: '#0B3838', 50: '#EAF5F4' },
                    ink: '#16211F',
                    canvas: '#F6FAF9',
                    line: '#DCE9E7',
                    slateink: '#56675F',
                    gold: '#F59E0B',
                    mint: '#10B981',
                    rose: '#F43F5E',
                },
                fontFamily: {
                    display: ['"Space Grotesk"', 'sans-serif'],
                    body: ['"Inter"', 'sans-serif'],
                    mono: ['"JetBrains Mono"', 'ui-monospace', 'monospace'],
                },
            },
        },
    };
</script>
<link href="/assets/css/style.css" rel="stylesheet">
</head>
<body class="font-body bg-canvas text-ink min-h-screen flex flex-col">
