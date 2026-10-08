<x-layouts.dashboard>
    <div class="cg-page-head cg-hide-short mx-auto mb-4 w-full sm:mb-6">
        <h1 class="mb-1 text-xl font-bold text-white sm:text-2xl">AI Chat</h1>
        <p class="cg-hide-phone text-sm text-slate-300">Ask the Safety Adviser about scams, phishing and what to do next. It can't open links, so use the Scan page for those.</p>
    </div>

    <div
        x-data="safetyChat(@js(['url' => route('chat.send'), 'token' => csrf_token(), 'reportId' => null, 'scope' => substr(hash('sha256', session()->getId().'|'.auth()->id()), 0, 16)]))"
        x-init="focusAndScroll()"
        class="cg-glass cg-page relative mx-auto flex w-full flex-col rounded-[1.75rem]"
    >
        <span class="cg-orb cg-orb-a" aria-hidden="true"></span>
        <span class="cg-orb cg-orb-b" aria-hidden="true"></span>

        <div class="cg-head flex items-center justify-between gap-2 border-b border-white/10 px-4 py-3 sm:px-6">
            <div class="flex min-w-0 items-center gap-3">
                <span class="cg-av" aria-hidden="true">
                    <svg class="h-4 w-4 text-sky-100" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg>
                </span>
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-white">Safety Adviser</p>
                    <p class="cg-hide-short flex items-center gap-1.5 text-xs text-slate-400"><i class="cg-live"></i><span class="truncate">AI helper &middot; can make mistakes</span></p>
                </div>
            </div>
            <button type="button" @click="reset()" x-show="messages.length > 0" style="display: none;" class="cg-ghost">New chat</button>
        </div>

        <x-chat-thread />
    </div>
</x-layouts.dashboard>