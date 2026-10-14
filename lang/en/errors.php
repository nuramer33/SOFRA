<?php

return [
    '403' => ['title' => 'This area isn’t for you', 'text' => 'You don’t have permission to open this page.'],
    '419' => ['title' => 'This page expired', 'text' => 'It stayed open for too long. Reload the page and try again.', 'retry' => 'Reload'],
    '429' => ['title' => 'Too many attempts', 'text' => 'Please wait a minute and try again.', 'retry' => 'Try again'],
    '500' => ['title' => 'Something went wrong on our side', 'text' => 'We’re sorry. The problem has been recorded and we’ll look into it. Please try again in a moment.', 'retry' => 'Try again'],
    '503' => ['title' => 'We’ll be right back', 'text' => 'Sofra is being updated. It only takes a few minutes. Please come back shortly.', 'retry' => 'Reload'],
];
