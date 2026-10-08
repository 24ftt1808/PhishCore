<?php

namespace App\Http\Controllers;

use App\Services\ChatAdvisor;
use Illuminate\Contracts\View\View;

/**
 * The full-page "AI Chat" tab. It uses the same chat as the floating button.
 */
class ChatPageController extends Controller
{
    public function __invoke(ChatAdvisor $advisor): View
    {
        abort_unless($advisor->enabled(), 404);

        return view('chat.index');
    }
}