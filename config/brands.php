<?php

/*
|--------------------------------------------------------------------------
| Brands PhishCore knows about
|--------------------------------------------------------------------------
|
| Data, not code: to teach the engine a new brand, add it here. AnalysisEngine reads these lists and
| the test suite checks every official domain below scores 0, so a typing mistake shows up as a failing
| test and not as a real site marked as a scam. Add a domain only after opening the organisation's own
| site to confirm it.
|
*/

// Domains shared by several spellings of one brand ("Pos Laju" / "poslaju"). Each was opened to confirm:
// poslaju.com.my redirects to pos.com.my (Pos Malaysia, which runs Pos Laju); jtexpress.my is J&T Express
// Malaysia and jtexpress.com the group site; ninjavan.co is Ninja Van; tngdigital.com.my is the Touch 'n Go
// eWallet and touchngo.com.my is listed as its website on Wikipedia.
$posLaju = ['pos.com.my', 'poslaju.com.my'];
$jtExpress = ['jtexpress.com', 'jtexpress.my'];
$ninjaVan = ['ninjavan.co'];
$touchNGo = ['tngdigital.com.my', 'touchngo.com.my'];

return [
    /**
     * Brand => official domains, shared by the page-content brand check and
     * by checkUrlSyntax() so a brand's own site is never treated as imitating
     * it. Add a domain here only after confirming it against the
     * organisation's own site: a wrong entry would flag the real site.
     */
    'official_domains' => [
        'dhl' => ['dhl.com'],
        'fedex' => ['fedex.com'],
        'ups' => ['ups.com'],
        'paypal' => ['paypal.com'],
        // google.github.io, googleblog.blogspot.com and blog.google are
        // Google's own pages; microsoft/netflix/spotify .github.io are those
        // companies' official open-source pages. Each was opened and confirmed
        // before adding, because GitHub account names are first come first
        // served and other brands' same-named accounts may belong to someone else.
        'google' => ['google.com', 'gmail.com', 'blog.google', 'googleblog.blogspot.com', 'google.github.io'],
        'facebook' => ['facebook.com', 'fb.com'],
        'apple' => ['apple.com', 'icloud.com'],
        'microsoft' => ['microsoft.com', 'outlook.com', 'live.com', 'hotmail.com', 'microsoft.github.io'],
        'outlook' => ['outlook.com', 'live.com', 'hotmail.com', 'microsoft.com'],
        'amazon' => ['amazon.com'],
        'netflix' => ['netflix.com', 'netflix.github.io'],
        'maybank' => ['maybank2u.com.my', 'maybank.com', 'maybank.com.bn', 'maybank2e.com'],
        'bibd' => ['bibd.com.bn'],
        // Verified by opening each site: whatsapp.com, livelo.com.br, correios.com.br;
        // bradesco.com.br redirects to banco.bradesco.
        'bradesco' => ['bradesco.com.br', 'banco.bradesco'],
        'livelo' => ['livelo.com.br'],
        'correios' => ['correios.com.br'],
        'whatsapp' => ['whatsapp.com', 'whatsapp.net'],
        // Crypto/wallets — domains verified via web search before adding,
        // not assumed, after an earlier session mistake (a wrong domain
        // guess for politeknikbrunei.edu.bn) taught the cost of guessing.
        'trezor' => ['trezor.io'],
        'metamask' => ['metamask.io'],
        'binance' => ['binance.com'],
        'coinbase' => ['coinbase.com'],
        'kraken' => ['kraken.com'],
        'crypto.com' => ['crypto.com'],
        'blockchain.com' => ['blockchain.com'],
        'trust wallet' => ['trustwallet.com'],
        // Regional banking — baiduri.com.bn and progresif.com confirmed
        // via web search; both differ from a naive first guess.
        'baiduri' => ['baiduri.com.bn'],
        'hsbc' => ['hsbc.com', 'hsbc.com.bn'],
        'citibank' => ['citibank.com', 'citi.com'],
        'standard chartered' => ['sc.com', 'standardchartered.com'],
        // E-commerce
        // Regional sites Bruneians use: shopee.sg/.co.id/.com.br are listed as Shopee's own
        // domains (netify.ai); the Lazada country sites are on its Wikipedia page.
        'shopee' => ['shopee.com', 'shopee.com.my', 'shopee.sg', 'shopee.co.id', 'shopee.ph', 'shopee.co.th', 'shopee.vn', 'shopee.tw', 'shopee.com.br', 'shopeemobile.com'],
        'lazada' => ['lazada.com', 'lazada.com.my', 'lazada.sg', 'lazada.co.id', 'lazada.co.th', 'lazada.vn', 'lazada.com.ph'],
        'alibaba' => ['alibaba.com'],
        'ebay' => ['ebay.com'],
        // Telco
        'progresif' => ['progresif.com'],
        // Tech/social — steam and zoom deliberately do NOT use
        // "brandname.com" (steampowered.com, zoom.us), confirmed before
        // adding since a wrong domain here would flag the brand's own
        // real site as impersonating itself.
        'instagram' => ['instagram.com'],
        'whatsapp' => ['whatsapp.com'],
        'linkedin' => ['linkedin.com'],
        'tiktok' => ['tiktok.com'],
        // spotifycharts.com redirects to charts.spotify.com (confirmed Oct 2026), so it is Spotify's own.
        'spotify' => ['spotify.com', 'spotifycharts.com', 'spotify.github.io'],
        'adobe' => ['adobe.com'],
        'dropbox' => ['dropbox.com'],
        'steam' => ['steampowered.com', 'steamcommunity.com'],
        // Gaming platforms and publishers. Their own domains are brand + ".com" in most cases, but
        // each was named on the company's own site or store pages; open each once to confirm before
        // relying on it, because a wrong entry would flag the real site (and a lookalike of it).
        'roblox' => ['roblox.com'],
        'discord' => ['discord.com', 'discord.gg', 'discord.gift', 'discordapp.com'],
        'epic games' => ['epicgames.com'],
        'riot games' => ['riotgames.com'],
        // garena.sg is the main site named on Garena's Wikipedia page, and its Taiwan sites are on garena.tw;
        // the other country sites (my, ph, vn, th, id) are not listed until each one is confirmed.
        'garena' => ['garena.com', 'garena.sg', 'garena.tw'],
        'mobile legends' => ['mobilelegends.com', 'moonton.com'],
        'minecraft' => ['minecraft.net', 'mojang.com'],
        'playstation' => ['playstation.com'],
        'xbox' => ['xbox.com'],
        'nintendo' => ['nintendo.com'],
        'zoom' => ['zoom.us'],
        'twitter' => ['twitter.com', 'x.com'],
        // Shipping
        'usps' => ['usps.com'],
        'royal mail' => ['royalmail.com'],
        'singpost' => ['singpost.com'],
        // Parcel and e-wallet names that Brunei residents get scam messages about. Pos Laju and the Touch 'n Go
        // eWallet are Malaysian, J&T Express and Ninja Van are regional couriers; none has a Brunei-only site.
        'pos laju' => $posLaju,
        'poslaju' => $posLaju,
        'j&t express' => $jtExpress,
        'jtexpress' => $jtExpress,
        'ninja van' => $ninjaVan,
        'ninjavan' => $ninjaVan,
        "touch 'n go" => $touchNGo,
        'touch n go' => $touchNGo,
        'touchngo' => $touchNGo,
        'tng ewallet' => $touchNGo,
        'tngdigital' => $touchNGo,
    ],

    /**
     * Brunei-specific brands and their OFFICIAL domains. Every domain here was
     * confirmed against the organisation's own site/Wikipedia before adding —
     * a wrong domain would flag the brand's real site as impersonating itself.
     *
     * Deliberately multi-word for names that are ordinary words or surnames
     * ("imagine", "taib", "dst"): a bare match would fire on unrelated pages.
     * Merged into the page-content and email brand checks.
     */
    'brunei_brands' => [
        'baiduri' => ['baiduri.com.bn'],
        'bdcb' => ['bdcb.gov.bn'],
        'progresif' => ['progresif.com', 'progresifevents.com'],
        'datastream digital' => ['dst.com.bn'],
        'dst brunei' => ['dst.com.bn'],
        'imagine brunei' => ['imagine.com.bn'],
        'perbadanan taib' => ['taib.com.bn', 'insuranstaib.com.bn'],
        'insurans islam taib' => ['insuranstaib.com.bn'],
        'brunei customs' => ['customs.gov.bn'],
        'royal customs and excise' => ['customs.gov.bn'],
        'royal brunei airlines' => ['flyroyalbrunei.com'],
        'royal brunei police force' => ['polis.gov.bn', 'police.gov.bn'],
        'polis diraja brunei' => ['polis.gov.bn', 'police.gov.bn'],
        'immigration and national registration department' => ['immigration.gov.bn'],
        'jabatan imigresen' => ['immigration.gov.bn'],
        'land transport department' => ['jpd.gov.bn'],
        'jabatan pengangkutan darat' => ['jpd.gov.bn'],
        'department of electrical services' => ['des.gov.bn'],
        'jabatan perkhidmatan elektrik' => ['des.gov.bn'],
        'brunei post' => ['post.gov.bn'],
        'brunei postal services' => ['post.gov.bn'],
        // Added Oct 2026. BIBD's full name, Pos Brunei (the Malay name) and TAP (tap.com.bn, its
        // own site). The police list police.gov.bn as well as polis.gov.bn (Wikipedia infobox).
        'bank islam brunei darussalam' => ['bibd.com.bn'],
        'pos brunei' => ['post.gov.bn'],
        'tabung amanah pekerja' => ['tap.com.bn'],
        // Added Oct 2026, each read from the organisation's own pages: UOB Brunei's page links the UOB
        // group sites; MyDST is the DST app (dst.com.bn); tarus is the national instant-payment system
        // (tarus.com.bn); UNN is the network operator (unn.com.bn). Maybank Brunei's address and
        // Standard Chartered's business login are in official_domains above.
        'uob brunei' => ['uobgroup.com', 'uob.com.sg', 'uob.com.my'],
        'mydst' => ['dst.com.bn'],
        'tarus' => ['tarus.com.bn'],
        'unified national networks' => ['unn.com.bn'],
    ],

    /**
     * Short Brunei names that are only treated as the brand when written in capitals ("DST",
     * "JPD"), the same way "UPS" is handled: in lower case they are ordinary words or parts of
     * words ("tap the button", "dst" in a file name).
     */
    'brunei_acronyms' => [
        'DST' => ['dst.com.bn'],
        'JPD' => ['jpd.gov.bn'],
        'TAP' => ['tap.com.bn'],
        'TAIB' => ['taib.com.bn', 'insuranstaib.com.bn'],
        'UOB' => ['uobgroup.com', 'uob.com.sg', 'uob.com.my'],
        'UNN' => ['unn.com.bn'],
        'RBPF' => ['polis.gov.bn', 'police.gov.bn'],
    ],

    /**
     * Government names whose real home is any .gov.bn site (the e-Darussalam portal is
     * www.gov.bn). Kept out of brunei_brands on purpose: listing "gov.bn" there would make every
     * .gov.bn site count as an "official brand page", which switches off the redirect check for
     * a hacked ministry site that sends people elsewhere.
     */
    'brunei_gov_names' => ['e-darussalam', 'edarussalam'],

    /**
     * Brand names the engine looks for in the visible text of a web page (exact words only: a page body
     * has far more chance of an accidental match than a short message). Every name here needs an entry in
     * official_domains, otherwise the brand's own site is judged as a copy.
     */
    'page_brands' => [
        'dhl', 'fedex', 'ups', 'paypal', 'google', 'facebook',
        'apple', 'microsoft', 'outlook', 'amazon', 'netflix', 'maybank',
        'bibd', 'trezor', 'metamask', 'binance', 'coinbase', 'kraken',
        'crypto.com', 'blockchain.com', 'trust wallet', 'baiduri', 'hsbc', 'citibank',
        'standard chartered', 'shopee', 'lazada', 'alibaba', 'ebay', 'progresif',
        'instagram', 'whatsapp', 'linkedin', 'tiktok', 'spotify', 'adobe',
        'dropbox', 'steam', 'zoom', 'twitter', 'roblox', 'discord',
        'epic games', 'riot games', 'garena', 'mobile legends', 'minecraft', 'playstation',
        'xbox', 'nintendo', 'usps', 'royal mail', 'singpost', 'pos laju',
        'poslaju', 'j&t express', 'jtexpress', 'ninja van', 'ninjavan', 'touch \'n go',
        'touch n go', 'touchngo', 'tng ewallet', 'tngdigital',
    ],

    /**
     * Brand names the engine looks for in a message, email or screenshot. Brunei brands and Brunei
     * acronyms are added automatically. Same rule: each name needs an entry in official_domains.
     */
    'text_brands' => [
        'dhl', 'fedex', 'ups', 'paypal', 'google', 'facebook',
        'apple', 'microsoft', 'outlook', 'amazon', 'netflix', 'maybank',
        'bibd', 'coinbase', 'binance', 'metamask', 'bradesco', 'livelo',
        'correios', 'whatsapp', 'roblox', 'discord', 'steam', 'epic games',
        'riot games', 'garena', 'mobile legends', 'minecraft', 'playstation', 'xbox',
        'nintendo', 'pos laju', 'poslaju', 'j&t express', 'jtexpress', 'ninja van',
        'ninjavan', 'touch \'n go', 'touch n go', 'touchngo', 'tng ewallet', 'tngdigital',
    ],

    /**
     * Brand names that are also everyday words ("outlook"). The engine does not treat these as a brand
     * just because the word appears inside a web address ("economic-outlook.com").
     */
    'ordinary_word_brands' => ['outlook'],

    /**
     * Names that, written as the start of an address that belongs to another website ("outlook.example.com"),
     * are a common disguise (see AnalysisEngine::brandUsedAsSubdomain). A company can run its own mail page at
     * "outlook.theircompany.com", so the name alone proves nothing: it counts only together with a word scams use to
     * sound official in the same address. A brand's own pages are never judged as a copy: a site on an official
     * domain (official_domains above) or high in the popular-sites ranking is skipped before this list is read.
     * Add a name only when it is a whole label real scams use, not an everyday word.
     */
    'subdomain_brands' => [
        'outlook', 'office365', 'o365', 'onedrive', 'icloud', 'gmail', 'dhl', 'fedex', 'usps', 'ups',
        'adobe', 'ebay', 'hsbc', 'kraken', 'tiktok', 'shopee', 'lazada',
    ],

    /**
     * Real websites whose name happens to look like, or contain, a brand's (see App\Support\LookAlike). The look-alike
     * and brand-word checks skip these registered domains. Add one only after opening the site and confirming it is
     * genuine, for example a payment app that differs from a brand by one letter.
     */
    'known_lookalikes' => [],

    /**
     * Look-alike Watch (php artisan lookalikes:watch, daily): words searched for in new website names published in
     * the public certificate logs (crt.sh), with each brand's real websites, which are never listed.
     *
     * 'anywhere' words count as a word of their own in a name, or joined to lure words (bibd-login.top, mybibd.xyz,
     * baiduribankverify.com), but never inside another word (cantarus.my and staruss.de are not tarus).
     * 'beside_brunei' words are everyday words or short letters (DST, Pos, Polis), so they only count when "brunei"
     * is right next to them or in the same name (dst-brunei-topup.com, posbrunei.xyz). The certificate logs are
     * searched once per 'anywhere' word, and once for "brunei" to find the rest.
     */
    'lookalike_watch' => [
        'anywhere' => [
            'bibd' => ['bibd.com.bn'],
            'baiduri' => ['baiduri.com.bn'],
            'progresif' => ['progresif.com', 'progresifevents.com'],
            'taib' => ['taib.com.bn', 'insuranstaib.com.bn'],
            'bdcb' => ['bdcb.gov.bn'],
            'tarus' => ['tarus.com.bn'],
            'edarussalam' => ['gov.bn'],
        ],
        'beside_brunei' => [
            'dst' => ['dst.com.bn'],
            'imagine' => ['imagine.com.bn'],
            'unn' => ['unn.com.bn'],
            'post' => ['post.gov.bn'],
            'pos' => ['post.gov.bn'],
            'polis' => ['polis.gov.bn', 'police.gov.bn'],
            'police' => ['polis.gov.bn', 'police.gov.bn'],
            'rbpf' => ['polis.gov.bn', 'police.gov.bn'],
            'jpd' => ['jpd.gov.bn'],
            'customs' => ['customs.gov.bn'],
            'kastam' => ['customs.gov.bn'],
            'imigresen' => ['immigration.gov.bn'],
            'immigration' => ['immigration.gov.bn'],
            'royal' => ['flyroyalbrunei.com'],
            'uob' => ['uobgroup.com', 'uob.com.sg', 'uob.com.my'],
            'maybank' => ['maybank.com', 'maybank2u.com.my', 'maybank.com.bn'],
            'scb' => ['sc.com'],
            'tap' => ['tap.com.bn'],
        ],
    ],
];