<x-layouts.dashboard>
    <div class="cg-page-head cg-hide-short cg-in mx-auto mb-4 w-full sm:mb-6">
        <h1 class="mb-1 text-xl font-bold text-white sm:text-2xl">AI Chat</h1>
        <p class="cg-hide-phone text-sm text-slate-300">Ask Cora, your safety helper, about scams, phishing and what to do next. It can't open links, so use the Scan page for those.</p>
    </div>

    <div
        x-data="safetyChat(@js(['url' => route('chat.send'), 'token' => csrf_token(), 'reportId' => null, 'page' => 'chat', 'scope' => substr(hash('sha256', session()->getId().'|'.auth()->id()), 0, 16)]))"
        x-init="focusAndScroll()"
        class="cg-glass cg-page cg-in relative mx-auto flex w-full flex-col rounded-[1.75rem]"
        style="--d: .1s"
    >
        <span class="cg-orb cg-orb-a" aria-hidden="true"></span>
        <span class="cg-orb cg-orb-b" aria-hidden="true"></span>

        <div class="cg-head flex items-center justify-between gap-2 border-b border-white/10 px-4 py-3 sm:px-6">
            <div class="flex min-w-0 items-center gap-3">
                <span class="cg-av" aria-hidden="true">
                    <x-robot-head class="rb h-[1.15rem] w-[1.15rem] text-sky-100" />
                </span>
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-white">Cora</p>
                    <p class="cg-hide-short flex items-center gap-1.5 text-xs text-slate-400"><i class="cg-live"></i><span class="truncate">AI helper &middot; can make mistakes</span></p>
                </div>
            </div>
            <button type="button" @click="reset()" x-show="messages.length > 0" style="display: none;" class="cg-ghost">New chat</button>
        </div>

        <x-chat-thread />
    </div>
</x-layouts.dashboard>