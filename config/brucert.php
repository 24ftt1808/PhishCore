<?php

/*
|--------------------------------------------------------------------------
| BruCERT
|--------------------------------------------------------------------------
|
| BruCERT handles the takedowns of phishing sites; PhishCore finds, records and reports them.
| The "Email BruCERT" button on an investigation opens a ready-written email addressed to this
| address. Leave it empty and the email opens with the To box blank, to be filled in by hand.
|
*/

return [
    'email' => env('BRUCERT_EMAIL'),
];