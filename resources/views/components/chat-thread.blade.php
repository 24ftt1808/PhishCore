@props(['compact' => false])

@php
    $chatMe = auth()->user();
    $chatMePhoto = $chatMe?->photoUrl();
    $chatMeInitials = $chatMe ? collect(explode(' ', trim($chatMe->name)))->filter()->map(fn ($part) => strtoupper(substr($part, 0, 1)))->take(2)->implode('') : '';
@endphp

{{-- Shared chat body: message list + composer. Must sit inside an element with x-data="safetyChat(...)". --}}
<div class="relative flex min-h-0 flex-1 flex-col">
    <div
        x-ref="scroll"
        @scroll.passive="onScroll()"
        aria-live="polite"
        class="cg-scroll flex flex-1 flex-col space-y-4 overflow-y-auto {{ $compact ? 'px-4 py-4' : 'px-4 py-5 sm:px-6 sm:py-6' }}"
    >
        {{-- Empty state --}}
        <template x-if="messages.length === 0">
            <div class="cg-msg flex flex-col items-center text-center {{ $compact ? 'gap-3' : 'gap-4' }}" style="margin-top: auto; margin-bottom: auto;">
                <span class="cg-av cg-av-lg cg-hide-short" aria-hidden="true">
                    <svg class="h-7 w-7 text-sky-100" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg>
                </span>
                <div>
                    <p class="font-semibold text-white {{ $compact ? 'text-base' : 'text-lg sm:text-xl' }}">How can I help you stay safe?</p>
                    <p class="mx-auto mt-1 max-w-md text-sm text-slate-300 cg-hide-short">
                        Ask about scams and online safety, in English or Malay.
                        @unless ($compact)
                            For help with one specific scan, open it from Scan History and tap the chat button.
                        @endunless
                    </p>
                </div>
                <div class="flex flex-wrap justify-center gap-2">
                    <template x-for="q in suggestions" :key="q">
                        <button type="button" @click="ask(q)" class="cg-chip" x-text="q"></button>
                    </template>
                </div>
            </div>
        </template>

        {{-- Messages --}}
        <template x-for="(m, i) in messages" :key="i">
            <div class="cg-msg flex items-start gap-2" :class="m.role === 'user' ? 'justify-end' : 'justify-start'">
                <span x-show="m.role !== 'user'" class="cg-av" aria-hidden="true">
                    <svg class="h-4 w-4 text-sky-100" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg>
                </span>
                <div class="group relative min-w-0 max-w-[calc(100%-2.6rem)] sm:max-w-[min(80%,40rem)]" :class="m.role === 'user' ? 'flex flex-col items-end' : ''">
                    <div
                        class="rounded-2xl px-4 py-2.5 text-sm leading-relaxed"
                        :class="{
                            'cg-me rounded-br-md text-white': m.role === 'user',
                            'cg-bot rounded-bl-md text-slate-100': m.role === 'assistant',
                            'cg-err rounded-bl-md text-red-100': m.role === 'error'
                        }"
                        style="white-space: pre-wrap; overflow-wrap: anywhere;"
                        x-text="m.content"
                    ></div>
                    <button
                        type="button"
                        x-show="m.role === 'assistant'"
                        @click="copy(i)"
                        class="cg-copy absolute -bottom-2.5 right-3 rounded-full border border-white/15 bg-slate-800/90 px-2.5 py-0.5 text-[11px] text-slate-300 hover:text-white"
                        x-text="copied === i ? 'Copied' : 'Copy'"
                    ></button>
                </div>
                <span x-show="m.role === 'user'" class="cg-av cg-av-me" aria-hidden="true">
                    @if ($chatMePhoto)
                        <img src="{{ $chatMePhoto }}" alt="" loading="lazy">
                    @else
                        {{ $chatMeInitials }}
                    @endif
                </span>
            </div>
        </template>

        {{-- Typing indicator --}}
        <div x-show="loading" style="display: none;" class="cg-msg flex items-start gap-2">
            <span class="cg-av" aria-hidden="true">
                <svg class="h-4 w-4 text-sky-100" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg>
            </span>
            <div class="cg-bot rounded-2xl rounded-bl-md px-4 py-3" role="status" aria-label="The adviser is typing">
                <span class="flex items-center gap-1.5"><i class="cg-dot"></i><i class="cg-dot"></i><i class="cg-dot"></i></span>
            </div>
        </div>
    </div>

    {{-- Jump to latest --}}
    <button
        type="button"
        x-show="showJump"
        x-transition.opacity
        style="display: none;"
        @click="jump()"
        aria-label="Jump to the latest message"
        class="cg-jump absolute bottom-3 left-1/2 -translate-x-1/2"
    >
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5L12 21m0 0l-7.5-7.5M12 21V3" /></svg>
    </button>
</div>

<form @submit.prevent="send()" class="cg-form border-t border-white/10 {{ $compact ? 'px-3 pt-3' : 'px-4 pt-3 sm:px-6 sm:pt-4' }}">
    <div class="cg-composer">
        <textarea
            x-model="input"
            x-ref="box"
            @input="grow()"
            @keydown.enter="if (!$event.shiftKey) { $event.preventDefault(); send(); }"
            rows="1"
            maxlength="1000"
            enterkeyhint="send"
            placeholder="Ask about scams&hellip;"
            aria-label="Your message"
        ></textarea>
        <button type="submit" :disabled="loading || input.trim() === ''" aria-label="Send" class="cg-send">
            <svg class="h-[1.1rem] w-[1.1rem]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.27 3.13a.75.75 0 011.04-.89l16.5 8.25a.75.75 0 010 1.34l-16.5 8.25a.75.75 0 01-1.04-.89L6 12zm0 0h7.5" /></svg>
        </button>
    </div>
    <div class="mt-2 flex items-start justify-between gap-3 px-1">
        <p class="text-[11px] leading-snug text-slate-400">
            Never share passwords, one-time codes or card numbers. Your messages are sent to an AI service.
            @unless ($compact)
                <span class="hidden lg:inline">Enter to send, Shift+Enter for a new line.</span>
            @endunless
        </p>
        <span x-show="input.length > 800" style="display: none;" x-text="input.length + '/1000'" class="shrink-0 text-[11px] font-medium text-amber-300"></span>
    </div>
</form>