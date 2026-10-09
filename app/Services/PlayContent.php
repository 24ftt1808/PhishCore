<?php

namespace App\Services;

/**
 * Practice material for the Play page. Every message and link here is an invented example
 * written for teaching. None of them is a real scam message and none of the links is a live site.
 */
class PlayContent
{
    /**
     * Messages for "Scam or Safe?" and "Inbox Rush".
     *
     * @return list<array{kind: string, from: string, text: string, context: string|null, scam: bool, why: string}>
     */
    public static function messages(): array
    {
        return self::dedupe(array_merge(self::curated(), self::generated()));
    }

    /**
     * The hand-written messages.
     *
     * @return list<array{kind: string, from: string, text: string, context: string|null, scam: bool, why: string}>
     */
    public static function curated(): array
    {
        $scam = fn (string $kind, string $from, string $text, string $why, ?string $context = null) => compact('kind', 'from', 'text', 'context', 'why') + ['scam' => true];
        $safe = fn (string $kind, string $from, string $text, string $why, ?string $context = null) => compact('kind', 'from', 'text', 'context', 'why') + ['scam' => false];

        return [
            $scam('SMS', 'BIBD-Alert', 'Your account will be blocked today. Verify now: bibd-secure-login.com/verify', 'It rushes you and sends you to a link that is not the bank\'s real website. Banks do not ask you to verify through a text link.'),
            $safe('SMS', 'BIBD', 'Your OTP is 482913. It is valid for 5 minutes. Never share this code with anyone.', 'You asked for this code and it tells you not to share it. If you did not ask for it, do not use it and call your bank on the number on your card.', 'You are paying for something online right now.'),
            $scam('SMS', 'BruneiPost', 'Your parcel is held. Pay a B$1.80 delivery fee to release it: bruneipost-track.cc/pay', 'A small fee, a link and a made-up website name. Parcel scams use small amounts so you stop thinking, and they really want your card details.'),
            $safe('SMS', 'Dr Sam\'s Dental (saved contact)', 'Reminder: your appointment is tomorrow at 9am. Please call us if you need to change it.', 'It comes from a number you know, there is no link, and it does not ask for money or codes.', 'You booked this appointment last week.'),
            $scam('Email', 'support@baiduri-online.top', 'Subject: Verify your account within 24 hours or it will be closed permanently.', 'The sender\'s address is not the bank\'s, and the 24 hour threat is there to make you panic.'),
            $safe('Email', 'no-reply@bibd.com.bn', 'Your monthly e-statement is ready. Please log in through the BIBD app or website you normally use to view it.', 'The sender is the real bank address, there is no link to click, and it tells you to use the way you already use.', 'You have e-statements switched on.'),
            $scam('Phone call', 'Unknown number', 'A caller says they are from the police. They say your name is linked to a crime and you must transfer money to a "safe account" to clear it.', 'Real police never ask you to transfer money. No safe account exists. Hang up and call 993 yourself if you are worried.'),
            $safe('Phone call', 'Your bank', 'You call the number printed on the back of your own bank card to ask about a payment you do not recognise.', 'You started the call, using a number you trust. That is exactly what you should do when something looks wrong.', 'You spotted a payment on your statement that you do not remember.'),
            $scam('Chat', 'Unknown number', 'Hi! We are hiring part-time online workers. Earn B$300 a day just by liking videos. Pay B$50 first to unlock your account.', 'A real job never asks you to pay first. Easy money for little work is the biggest sign.'),
            $scam('Chat', 'Unknown number', 'Mum, I lost my phone, this is my new number. Can you transfer B$500 urgently? I will explain later.', 'Scammers pretend to be family. Call your child on their old number or ask something only they would know before sending money.'),
            $scam('SMS', 'Promo', 'Congratulations! You won B$5,000. Claim at claim-prize-bn.com. Offer ends in 10 minutes.', 'You cannot win a contest you never entered. The countdown is there to stop you thinking.'),
            $scam('Email', 'refund@gov-bn-refund.com', 'You are eligible for a tax refund of B$420. Submit your card details to receive the money.', 'A refund never needs your card number, because the money goes in, not out. The sender is not a government address.'),
            $scam('Website', 'Online shop ad', 'The newest phone at 80% off. Pay by bank transfer to a personal account to hold your order.', 'A price that is too good to be true, and a payment to a personal account. Shops use proper checkouts.'),
            $safe('SMS', 'Delivery rider', 'Your delivery rider is arriving in 2 minutes. Order #4821. You can track it in the app you ordered from.', 'You ordered food just now, there is no link, and it points you back to the app you already use.', 'You ordered lunch ten minutes ago.'),
            $scam('Email', 'ceo.office@gmail-mail.co', 'I am in a meeting. Buy 5 gift cards and send me the codes. Keep this confidential.', 'Gift card codes work like cash and cannot be traced. A boss who asks for secrecy and speed is a classic fake.'),
            $scam('SMS', 'SIM-Care', 'Your SIM will be deactivated. Update your IC number here: sim-update-bn.xyz', 'Phone companies do not deactivate SIMs through a link, and they never need your IC number this way.'),
            $safe('Email', 'security notice from an app you use', 'Your password was changed. If this was you, you do not need to do anything. If it was not, open the app and reset your password.', 'There is nothing to click and nothing to pay. It only asks you to use the app itself if something is wrong.', 'You changed your password a minute ago.'),
            $safe('SMS', 'Telco', 'Your top-up of B$10 was successful. Your balance is B$14.20.', 'It only tells you something happened that you did. It has no link and does not ask for anything.', 'You just topped up your phone.'),
            $scam('Phone call', 'Recorded voice', 'A recorded voice says your parcel contains illegal items. Press 1 to speak to customs.', 'Real officials do not use recorded calls to accuse you. Pressing 1 connects you to the scammer.'),
            $scam('Email', 'alerts@secure-my-account-now.xyz', 'Your account was accessed from a new device in another country. Click here to secure it now.', 'Fear plus a link to an unknown website. Open your account yourself from the real app or website instead.'),
            $scam('Chat', 'Friend of a friend', 'A person you met online a week ago says they made big profits on a crypto platform and asks you to invest B$1,000 to join.', 'Investment tips from someone you have not met in person are a common trap. The platform will let you "win" for a while, then the money disappears.'),
            $scam('SMS', 'Bill-Pay', 'Your electricity bill is overdue. Pay now to avoid disconnection: bn-bill-pay.xyz', 'Threats about your power or water, and a payment link that is not an official website.'),
            $safe('Email', 'newsletter@a-site-you-subscribed-to', 'This week\'s news: five tips for saving energy at home. Unsubscribe at any time.', 'It is not asking for money, codes or passwords, and you chose to receive it.', 'You subscribed to this newsletter last month.'),
            $scam('Website', 'Pop-up', 'WARNING: your phone has 5 viruses! Call this number now to remove them.', 'A website cannot scan your phone. This is a scare tactic to make you call a scammer. Close the tab.'),
            $scam('SMS', 'e-Gov', 'Your road tax is overdue. Pay within 24 hours or face a B$300 fine: bn-roadtax-pay.top', 'Real government services do not threaten fines by text with a payment link. The website name is not an official address.'),
            $scam('SMS', 'Prize Draw', 'You are our 1,000th customer! Choose your free gift here: giftbn.cc/win', 'Free gifts for "customers" you never heard of lead to forms that steal your details or card number.'),
            $scam('SMS', 'HR-Admin', 'Hi, I got your number from a job portal. Part-time work, B$180 an hour reviewing products. Add me on Telegram to start.', 'Very high pay for easy work, from a stranger, moved to another app. That is the usual start of a job scam.'),
            $scam('Email', 'billing@streaming-support.top', 'Your subscription payment failed. Update your card within 12 hours or your account will be deleted.', 'A deadline and a threat, from an odd sender address. Open the real app yourself to check your subscription.'),
            $scam('Email', 'it-helpdesk@company-mail-support.com', 'Your mailbox is full. Click here and confirm your password to avoid losing messages.', 'Real IT teams do not ask you to type your password into a link. The sender is also not your company\'s address.'),
            $scam('Chat', 'Unknown number', 'Hello? Oh, sorry, wrong number! You seem nice though. Where are you from?', 'The "wrong number" opener is how many friendship, romance and investment scams begin. It is safest not to reply.'),
            $scam('Phone call', 'Unknown caller', 'A caller says they are from your bank\'s card centre and your card was used overseas. They ask you to read out the 6-digit code that was just sent to you.', 'The code you were just sent is the key to approving the payment. A real bank never asks for it.'),
            $scam('Website', 'Login page', 'You tapped a link in a text. The page looks like your bank\'s login, but the address bar shows bibd-login.wixsite.com.', 'That is a free website-builder address anyone can make. Close the page and open your bank\'s app or website yourself.'),
            $scam('Chat', 'Friend\'s account', 'Hey, are you free? I need a quick favour. Can you buy a B$100 top-up card and send me the code? I will pay you back tonight.', 'Hacked accounts message the owner\'s contacts. Call your friend on their number before you do anything.'),
            $scam('SMS', 'Courier', 'We tried to deliver but no one answered. Reschedule here: bn-deliver-now.xyz', 'You were not expecting a parcel, and the link is not a courier\'s website. Check in the courier\'s own app instead.'),
            $scam('Email', 'accounts@supplier-invoices.co', 'Attached are our updated bank details. Please pay the pending invoice of B$7,850 to the new account.', 'A sudden change of bank details is a common business scam. Call the supplier on a number you already have before paying.'),
            $scam('Phone call', 'Recorded voice', 'Your mobile number will be cancelled in 2 hours. Press 9 to speak to an agent.', 'A recorded threat is not how a phone company contacts you. Pressing 9 puts you through to a scammer.'),
            $scam('Website', 'Pop-up', 'Congratulations! You have been selected to win the newest phone. Answer 3 questions to claim your prize.', 'Pop-up "prizes" collect your details or sign you up to paid services. Close the tab.'),
            $scam('Chat', 'Marketplace buyer', 'I will buy your item. I sent B$300 but sent too much by mistake. Please send back B$200 and I will release the rest.', 'The payment is fake or has not arrived, and you are asked to send real money back. Check your own bank balance first.'),
            $scam('SMS', 'Loan-Fast', 'Need cash fast? Approved loan of B$5,000 in 10 minutes, no checks. Pay a B$150 admin fee first.', 'A real lender does not ask for a fee before the money arrives. You will pay the fee and never get a loan.'),
            $scam('Email', 'noreply@docs-share-view.top', 'A colleague shared a document with you. Sign in to view it.', 'The sign-in page is built to steal your password. If you expect a document, ask your colleague another way.'),
            $scam('Chat', 'Instagram DM', 'You have been chosen as a brand ambassador! Pay B$99 for your starter kit to join.', 'A real brand pays you. It does not charge you to join. Easy money plus an upfront fee is a classic scam.'),
            $scam('Phone call', 'Unknown caller', 'Someone says your child has had an accident and you must send money for the hospital right now.', 'Fear is the weapon. Hang up and call your child, or the hospital, on a number you know.'),
            $scam('SMS', 'Bank', 'Dear customer, your online banking token expires today. Renew now: bibd-token-renew.cc', 'A banking token does not expire by text link, and the website name is a lookalike. Use the bank\'s own app.'),
            $scam('Email', 'security@account-verification.work', 'We noticed an unusual sign-in. Open the attached Security_Report.pdf.exe to review it.', 'A file that ends in .exe is a program, not a PDF. Opening it can install harmful software.'),
            $scam('Chat', 'Telegram group', 'Join our VIP group. Our signals guarantee 5x your money in 3 days. Deposit to start.', 'No one can guarantee big profits. These groups show fake wins and then keep your deposit.'),
            $scam('Website', 'Shop ad', 'Flash sale: 90% off branded bags, today only. We accept payment by gift card or crypto only.', 'Real shops accept normal payments. Gift cards and crypto cannot be traced or reversed, which is why scammers ask for them.'),
            $scam('SMS', 'Ticket Seller', 'Concert tickets sold out? We have a few at half price. Send payment first and we will send the QR code.', 'Paying first for tickets from a stranger is risky. QR screenshots can be copied and sold many times.'),
            $scam('Email', 'hr@company-benefits-update.cc', 'Your salary slip has changed. Download it from this link and log in to confirm your bank details.', 'HR does not ask you to confirm bank details through a link. Ask HR in person or on the company system.'),
            $safe('SMS', 'BIBD', 'You signed in to online banking on a new device. If this was you, no action is needed.', 'It only tells you something you just did, has no link and asks for nothing. If it was not you, call your bank on the number on your card.', 'You just signed in on your new phone.'),
            $safe('Email', 'receipts@a-shop-you-use', 'Thanks for your order #5521. Your receipt is attached. You can track the order in the app.', 'It matches an order you made, has no urgent request, and sends you back to the app you already use.', 'You ordered a shirt yesterday.'),
            $safe('Phone call', 'Friend (saved contact)', 'A friend you know calls from the number you saved and asks if you are coming to lunch tomorrow.', 'It is a number and a voice you know, and nothing is asked of you but a yes or no.', 'It is their usual number and voice.'),
            $safe('SMS', 'City Library', 'Your library book is due in 3 days. You can renew it in the library app.', 'You borrowed a book, there is no link, and it points you to an app.', 'You borrowed a book last week.'),
            $safe('Email', 'Your lecturer (saved address)', 'Class is moved to Room 4 tomorrow at 10am. See you there.', 'It comes from the usual address and has no link, no money and no request for passwords.', 'It is the same address as your other class notices.'),
            $safe('SMS', 'Telco', 'Your data plan will renew on 12 October. No action is needed.', 'It tells you what already happens on your plan, and there is no link or request.', 'You are on a monthly plan.'),
            $safe('Chat', 'Family group', 'Dinner at 7 tonight at Mum\'s. Bring the dessert.', 'It is from a chat you know, with people you know, and asks only for dessert.', 'This is your family group chat.'),
            $safe('Phone call', 'Delivery driver', 'A delivery driver calls to say he is outside your gate with your parcel and asks you to come out.', 'You are expecting a delivery, and he only asks you to collect it. No payment, link or code.', 'The app shows the driver arriving now.'),
            $safe('Email', 'Password reset from an app you use', 'You asked to reset your password. Choose a new one using the button below. If you did not ask for this, ignore this email.', 'You started the reset yourself a moment ago. If you were unsure, you could also open the app directly.', 'You tapped "Forgot password" two minutes ago.'),
            $safe('SMS', 'Shopping app', 'Your code is 7391. Do not share it with anyone.', 'You asked for it, it is short and tells you not to share. Only enter it in the app you are using, and never read it out to anyone.', 'You are signing in to a shopping app right now.'),
            $safe('Website', 'Your bank', 'You type the bank\'s address into your browser yourself and see the padlock and the right website name.', 'You went there yourself instead of following a link, and the name matches. That is the safest way in.', 'You are opening your bank\'s site yourself.'),
            $safe('Email', 'Team lead (saved address)', 'The weekly team meeting has moved to Friday at 2pm. Updated calendar invite attached.', 'It comes from a known colleague about a meeting you already have. It asks for no money or passwords.', 'It is about your usual weekly meeting.'),
            $safe('SMS', 'Pharmacy', 'Your prescription is ready to collect until Saturday.', 'There is no link, payment or code. It tells you where to go and by when.', 'You left a prescription there this morning.'),
            $safe('Chat', 'Class group chat', 'Group project: please send your part by Thursday so we can put it together.', 'It is from your classmates about a task you all know about.', 'This is your class group chat.'),
        ];
    }

    /**
     * Fresh messages built from templates, so every visit to the page has a different mix. The amounts, names and
     * made-up website addresses change each time, and more safe messages are added than scams so that tapping
     * "Scam" every time is not a winning plan. Every address is invented and none points to a live site.
     *
     * @return list<array{kind: string, from: string, text: string, context: string|null, scam: bool, why: string}>
     */
    public static function generated(int $scams = 14, int $safes = 26): array
    {
        $pick = fn (array $list) => $list[array_rand($list)];
        $fake = function (string $slug) use ($pick): string {
            $words = ['secure', 'verify', 'login', 'update', 'confirm', 'pay', 'claim', 'support', 'track', 'refund'];
            $ends = ['xyz', 'top', 'cc', 'work', 'click', 'icu', 'site'];

            return $slug.'-'.$pick($words).'.'.$pick($ends).'/'.$pick($words);
        };
        $banks = ['BIBD', 'Baiduri', 'Standard Chartered', 'TAIB'];
        $couriers = ['BruneiPost', 'J&T', 'DHL', 'Courier'];
        $telcos = ['DST', 'Progresif', 'imagine'];
        $shops = ['Shopee', 'Lazada', 'Grab', 'foodpanda'];
        $amount = fn (int $lo, int $hi, int $step = 10) => random_int((int) ($lo / $step), (int) ($hi / $step)) * $step;

        $scamTemplates = [
            fn () => ['SMS', ($b = $pick($banks)).'-Alert', "Your {$b} account will be blocked ".$pick(['today', 'in 2 hours', 'tonight']).'. Verify now: '.$fake(strtolower(str_replace(' ', '', $b))), 'It rushes you and sends you to a link that is not the bank\'s real website. Banks do not ask you to verify through a text link.'],
            fn () => ['SMS', $pick($couriers), 'Your parcel is held. Pay a B$'.number_format($amount(100, 400, 10) / 100, 2).' fee to release it: '.$fake('parcel'), 'A small fee, a link and a made-up website name. Parcel scams use small amounts so you stop thinking, and then they take your card details.'],
            fn () => ['SMS', $pick($telcos), 'Your '.$pick($telcos).' number will be cancelled in '.$pick([2, 3, 6]).' hours. Update your IC details: '.$fake('telco'), 'Phone companies do not cancel numbers through a link, and they never need your IC number by text.'],
            fn () => ['Chat', 'Unknown number', 'Hi! We are hiring part-time online workers. Earn B$'.$amount(150, 500, 50).' a day just by '.$pick(['liking videos', 'rating products', 'boosting shop orders']).'. Pay B$'.$amount(30, 100, 10).' first to unlock your account.', 'A real job never asks you to pay first. Task scams pay a little at the start, then ask for bigger deposits.'],
            fn () => ['Chat', 'Unknown number', 'Mum, I lost my phone, this is my new number. Can you transfer B$'.$amount(200, 800, 50).' urgently? I will explain later.', 'Scammers pretend to be family. Call your child on their old number or ask something only they would know before sending money.'],
            fn () => ['SMS', 'Promo', 'Congratulations! You won B$'.number_format($amount(2000, 9000, 500)).'. Claim at '.$fake('prize').'. Offer ends in '.$pick([10, 15, 30]).' minutes.', 'You cannot win a contest you never entered. The countdown is there to stop you thinking.'],
            fn () => ['Email', 'refund@'.$pick(['gov', 'tax', 'revenue']).'-bn-'.$pick(['refund', 'returns']).'.com', 'You are eligible for a refund of B$'.$amount(100, 600, 10).'. Submit your card details to receive the money.', 'A refund never needs your card number, because the money goes in, not out. The sender address is not an official one.'],
            fn () => ['Website', $pick($shops).' look-alike', 'The newest phone at '.$pick([70, 80, 90]).'% off. Pay by bank transfer to a personal account to hold your order.', 'A price that is too good to be true, and a payment to a personal account. Real shops use proper checkouts.'],
            fn () => ['SMS', 'e-Gov', 'Your '.$pick(['road tax', 'licence', 'bill']).' is overdue. Pay within 24 hours or face a B$'.$amount(100, 500, 50).' fine: '.$fake('gov'), 'Real government offices do not threaten fines by text or send payment links.'],
            fn () => ['Chat', 'Marketplace buyer', 'I will buy your item. I sent B$'.$amount(200, 400, 50).' but sent too much by mistake. Please send back B$'.$amount(50, 150, 50).' and I will pay again.', 'The "payment" is fake. Check your own bank balance, never a screenshot, before sending anything back.'],
            fn () => ['Email', 'billing@'.$pick(['streaming', 'cloud', 'music']).'-support.'.$pick(['top', 'cc', 'work']), 'Your subscription payment failed. Update your card within '.$pick([12, 24]).' hours or your account will be closed.', 'The sender is not the real company, and the deadline is there to rush you. Open the real app yourself instead.'],
            fn () => ['Phone call', 'Unknown number', 'A caller says they are from '.$pick(['the police', 'customs', 'the court']).' and that your name is linked to a crime. They say you must move your money to a "safe account".', 'Real officials never ask you to move money to a safe account. Hang up and call the real office yourself.'],
        ];

        $safeTemplates = [
            fn () => ['SMS', $pick($banks), 'Your OTP is '.random_int(1000, 9999).'. It is valid for 5 minutes. Never share this code with anyone.', 'You asked for this code and it tells you not to share it. If you did not ask for it, call your bank on the number on your card.', 'You had just tried to log in or pay.'],
            fn () => ['SMS', $pick(['Dr Sam\'s Dental', 'Klinik Aman', 'City Clinic']).' (saved contact)', 'Reminder: your appointment is '.$pick(['tomorrow', 'on Friday', 'on Monday']).' at '.$pick(['9am', '10:30am', '2pm', '4pm']).'. Please call us if you need to change it.', 'It comes from a number you know, there is no link and it asks for nothing.', 'You booked an appointment last week.'],
            fn () => ['SMS', 'Delivery rider', 'Your delivery rider is arriving in '.$pick([2, 3, 5]).' minutes. Order #'.random_int(1000, 9999).'. You can track it in the app you ordered from.', 'You ordered food just now, there is no link, and it points you to the app you already use.', 'You ordered from '.$pick($shops).' a few minutes ago.'],
            fn () => ['Email', 'no-reply@bibd.com.bn', 'Your monthly e-statement is ready. Please log in through the BIBD app or website you normally use to view it.', 'The sender is the real bank address, and it sends you to log in the way you normally do instead of giving a link.', 'You have an account with this bank.'],
            fn () => ['SMS', $pick($telcos), 'Your top-up of B$'.$pick([5, 10, 20]).' was successful. Your balance is B$'.$amount(500, 3000, 10) / 100 .'.', 'It only tells you something happened that you did. It has no link and does not ask for anything.', 'You topped up a minute ago.'],
            fn () => ['SMS', 'City Library', 'Your library book is due in '.$pick([2, 3, 5]).' days. You can renew it in the library app.', 'You borrowed a book, there is no link, and it points to the app you already use.', 'You borrowed a book last month.'],
            fn () => ['Email', 'Your lecturer (saved address)', 'Class is moved to Room '.random_int(1, 9).' '.$pick(['tomorrow', 'on Thursday', 'next week']).' at '.$pick(['10am', '1pm', '3pm']).'. See you there.', 'It comes from the address you know, it is about something real, and it asks for nothing.', 'You are in this lecturer\'s class.'],
            fn () => ['Chat', 'Family group', $pick(['Dinner at 7 tonight at Mum\'s. Bring the dessert.', 'Who is picking up Grandma on Saturday?', 'Happy birthday Dad! Cake at 8 tonight.']), 'It is from a chat you know, with people you know, and nobody is asking for money or codes.', 'You are in this family chat.'],
            fn () => ['SMS', 'Pharmacy', 'Your prescription is ready to collect until '.$pick(['Friday', 'Saturday', 'Sunday']).'.', 'There is no link, payment or code. It tells you something you were waiting for.', 'You dropped off a prescription yesterday.'],
            fn () => ['Email', 'receipts@a-shop-you-use', 'Thanks for your order #'.random_int(1000, 9999).'. Your receipt is attached. You can track the order in the app.', 'It matches an order you made, and it sends you to the app instead of asking for details.', 'You bought something from this shop today.'],
            fn () => ['Email', 'Password reset from an app you use', 'You asked to reset your password. Choose a new one using the button below.', 'You asked for it a minute ago. If you had not, you would ignore it and change your password in the app.', 'You just pressed "forgot password" yourself.'],
            fn () => ['Phone call', 'Your bank', 'You call the number printed on the back of your own bank card to ask about a payment you do not recognise.', 'You started the call, using a number you trust. The danger is when they call you first.', 'You noticed a payment you did not know.'],
            fn () => ['Chat', 'Class group chat', 'Group project: please send your part by '.$pick(['Thursday', 'Friday', 'Monday']).' so we can put it together.', 'It is from your own class group and asks only for schoolwork.', 'You are in this group for a project.'],
        ];

        $build = function (array $templates, int $count, bool $isScam): array {
            $out = [];
            for ($tries = 0; count($out) < $count && $tries < $count * 6; $tries++) {
                $t = $templates[array_rand($templates)]();
                $out[$t[2]] = ['kind' => $t[0], 'from' => $t[1], 'text' => $t[2], 'context' => $t[4] ?? null, 'why' => $t[3], 'scam' => $isScam];
            }

            return array_values($out);
        };

        return array_merge($build($scamTemplates, $scams, true), $build($safeTemplates, $safes, false));
    }

    /**
     * @param  list<array<string, mixed>>  $messages
     * @return list<array<string, mixed>>
     */
    private static function dedupe(array $messages): array
    {
        $seen = [];
        foreach ($messages as $m) {
            $seen[$m['text']] ??= $m;
        }

        return array_values($seen);
    }

    /**
     * Branching chat stories for "Scam Survivor". A node either has "say" (what the scammer writes) and "choices",
     * or "end". A choice has the reply text, the next node and an optional [kind, text] tip, where kind is "flag" or "good".
     *
     * @return list<array<string, mixed>>
     */
    public static function stories(): array
    {
        // What the player types in the chat for each choice. null means they just go quiet.
        $says = [
            'Accept the ID and carry on' => 'Alright, your ID looks fine to me. What is next?',
            'Ask a question only my child would know' => 'First tell me something only my child would know. Where did we go on your last birthday?',
            'Ask for a video call anyway' => 'Humour me. One minute on video and I will believe you.',
            'Ask for the company name and website' => 'Which company is this, and what is the website? I would like to read about it first.',
            'Ask for the seat numbers and proof' => 'Can you send the seat numbers and a photo of the tickets? Then I will decide.',
            'Ask how it works' => 'Sounds interesting. How does it actually work?',
            'Ask them to prove who they are' => 'I need to know who I am talking to. Can you show me some proof?',
            'Ask them to send proof on WhatsApp' => 'Send me some proof on WhatsApp and I will take a look.',
            'Ask to meet and check it in person first' => 'Why not meet up? I would like to see it in person before anything else.',
            'Ask which regulator licenses it, and check' => 'Which regulator gave you a licence? Send me the number and I will check it.',
            'Ask: "What is my dog\'s name?"' => 'Quick question first: what is my dog\'s name?',
            'Believe it and pay' => 'Oh no, I do not want any trouble. I will pay now.',
            'Believe it, the letter looks real' => 'The letter has a logo and everything. It must be real. What do I do?',
            'Believe the screenshots' => 'Those screenshots are impressive. Tell me more about joining.',
            'Block the card and change my online banking password' => 'I am freezing my card in the app and changing my password right now.',
            'Block the number' => 'Please lose my number. I am blocking you now.',
            'Buy it and send the code' => 'Got the card. Here is the code from the back.',
            'Buy it but hold the code until they call' => 'I will buy the card, but you only get the code after your boss calls me.',
            'Buy the card' => 'Fine, I will go and buy the card.',
            'Call my child\'s old number anyway' => 'I am going to ring your old number anyway, just in case.',
            'Call my child\'s old number' => 'Wait a moment, let me ring your old number first.',
            'Call my friend on their number to check' => 'Hang on, I am calling your usual number to make sure it is you.',
            'Call the number on the back of my card' => 'I will end this chat and ring the number on the back of my card.',
            'Call the supplier on the number I already have' => 'Let me confirm by phone. I will use the number I already have for you.',
            'Call the supplier on the number from an old invoice' => 'I will ring the number on your last invoice to confirm the change.',
            'Call them anyway' => 'I am going to call them myself to check.',
            'Chat with her, she seems nice' => 'Hi Sarah, lovely to meet you too. Tell me about yourself!',
            'Check my own bank account first' => 'Give me a minute. I want to look at my own bank account first.',
            'Close the app, uninstall it and call my bank' => 'I am deleting this app and calling my bank right now.',
            'Close the page' => null,
            'Confirm my details' => 'Yes, that is all correct.',
            'Delete the message and move on' => null,
            'Deposit B$200 to earn more' => 'Bigger returns for B$200 more? Alright, depositing now.',
            'Do the tasks' => 'Easy money. Send me the first task.',
            'Enter my card details' => 'Fine, typing my card details in now.',
            'Enter the OTP' => 'The code just arrived. Here it is.',
            'Feel bad and believe them' => 'I am so sorry, I did not mean to cause a problem. What should I do?',
            'Feel guilty and apologise' => 'Sorry about that, I did not know. How can I fix it?',
            'Hang up and call 993 myself' => 'I am hanging up. If this is real, I will call 993 myself.',
            'Hang up and call the bank on the number on my card' => 'Goodbye. I will ring my bank on the number printed on my card.',
            'I will take it. How do I pay?' => 'Great, I will take it. Where do I send the money?',
            'Ignore it. It is probably a scam' => null,
            'Ignore the message' => null,
            'Install the app and deposit B$300' => 'App installed. I am putting in B$300 now.',
            'Install the app' => 'Let me download the app now.',
            'Invest B$2,000 more for bigger profits' => 'Those profits look great. I will put in another B$2,000.',
            'Keep the B$15 and leave' => 'I will keep my B$15 where it is and leave. Goodbye.',
            'Log in while they watch' => 'Alright, I am signing in now.',
            'Move to Telegram' => 'Sure, add me on Telegram and we can talk there.',
            'Not interested, block' => 'Not for me, thanks. Please stop messaging.',
            'Only buy from the official seller' => 'No thanks. I will buy from the official seller only.',
            'Open the courier\'s real app or website myself' => 'I will track my parcel in the courier\'s own app instead.',
            'Panic: "Please cancel it!"' => 'Wait, please cancel it right now!',
            'Panic: "What should I do?"' => 'Oh no, what should I do?',
            'Pause and check with my boss first' => 'Hold on. I need to speak to my boss before I do anything.',
            'Pay B$800 to get my money out' => 'B$800 is worth it to get my money back. Paying now.',
            'Pay again to clear my name' => 'I do not want trouble. I will pay again to clear this up.',
            'Pay half and ask for confirmation tomorrow' => 'I will send half today, and the rest once you confirm tomorrow.',
            'Pay it now, they confirmed' => 'You said it is confirmed, so I am paying now.',
            'Pay the B$1,500 to get the gift' => 'B$1,500 for a gift that big? Alright, paying now.',
            'Pay the extra B$50' => 'Alright, here is the extra B$50.',
            'Pay the full amount now' => 'Sounds fair. Paying the full amount now.',
            'Pay the tax to get my money out' => 'If the tax is the last step, I will pay it.',
            'Pay the tax to get my prize' => 'Fine, I will pay the tax, and then the prize is mine, right?',
            'Pay the {fee} so the phone ships' => 'It is only {fee}. Paying so the phone can ship.',
            'Refuse and ask for my B$200 back' => 'No more. Send my B$200 back, please.',
            'Refuse and hang up' => 'I am not doing that. Goodbye.',
            'Refuse and report it' => 'I will not do that, and I am reporting this.',
            'Refuse. I will not pay to receive a prize' => 'A real prize does not cost money to receive. I am not paying.',
            'Refuse: banks do not ask for this' => 'Banks never ask for that. I am not giving it to you.',
            'Reply to the email and ask if it is true' => 'Is this email really true? Can you explain?',
            'Reply with my card number to dispute it' => 'Please fix it. My card number is below.',
            'Reply with my name and IC number' => 'Sure, my name and IC number are below.',
            'Reply: "I never joined a draw"' => 'I do not remember joining any draw.',
            'Reply: "Who is this?"' => 'Sorry, who is this?',
            'Save it and reply "OK"' => 'OK',
            'Say I am too busy and hang up' => 'I am busy at the moment, sorry. Goodbye.',
            'Say I love you too' => 'I have fallen for you too.',
            'Say I will call to confirm first' => 'Let me phone first and confirm. I will be back.',
            'Say I will look it up first' => 'I will look into this myself first.',
            'Say I will only pay through the platform or in cash when I see it' => 'I will pay through the platform, or in cash when I see the item.',
            'Say I will ship only when the money is in my account' => 'I ship once the money shows up in my account, not before.',
            'Say no and stop talking to her' => 'Sorry Sarah, I cannot send you money. Please do not ask again.',
            'Say you will hang up and call the police on 993 yourself' => 'I am hanging up now, and I will call 993 myself.',
            'Search for the company yourself and find nothing' => 'I looked for your company online and found nothing. Why is that?',
            'Search her photo online first' => 'One moment, I am going to look up your photo.',
            'Send back {extra}, then ship the item' => 'I have sent {extra} back. Shipping the item now.',
            'Send it, they said it is the last time' => 'You promised this is the last time. Sending it.',
            'Send payment now so I do not miss out' => 'I do not want to lose it. Sending payment now.',
            'Send the B$900' => 'Alright, B$900 is on its way.',
            'Send {extra} anyway' => 'Fine, I will send {extra} anyway.',
            'Ship the item' => 'Alright, packing it up and shipping it now.',
            'Skip it and leave the chat' => 'Not for me. I am leaving this chat.',
            'Stop and report it to my bank' => 'I am stopping here and telling my bank about this.',
            'Stop replying and delete the message' => null,
            'Stop, and warn my friend another way that their account may be hacked' => 'Wait, this does not feel like you. I think you are hacked. I will reach you another way.',
            'Stop. Call 16993 and tell the bank' => 'I am stopping. I will call 16993 and tell my bank.',
            'Stop. Call my bank on the number on my card' => 'Stop. I am ringing my bank on the number on my card.',
            'Stop. Call my child\'s old number now' => 'Stop. I am calling your old number right now.',
            'Stop here and report her' => 'I am stopping here, Sarah, and reporting this.',
            'Sure, I will buy it now' => 'Sure thing. I am buying it now.',
            'Tap the link' => 'Opening the link now.',
            'Tap the link, it is only {fee}' => 'Only {fee}? Fine, opening the link.',
            'Tell a family member or friend what is happening' => 'Wait. I want to tell my family about this first.',
            'Tell a friend about her and ask what they think' => 'Let me tell my friends about us and hear what they think.',
            'Tell them: no money, no item, and I will refund nothing' => 'No payment, no item. I am not refunding anything.',
            'The invoice is real, so I will pay it' => 'This invoice looks right to me. Paying it now.',
            'Too cheap to be true. Skip it.' => 'That price is too good to be true. I will pass.',
            'Transfer my savings to the safe account' => 'If that keeps my money safe, I will move my savings now.',
            'Transfer the money right away' => 'Transferring the money right now.',
            'Transfer {price} to the personal account' => 'Sending {price} to that account now.',
            'Wait for the real payment to arrive first' => 'I will wait until the money has really arrived.',
            'Walk away' => null,
            'Withdraw everything and stop' => 'I am taking all my money out and stopping here.',
            'Yes, tell me more!' => 'Yes please, tell me more!',
        ];
        // The fifth argument is the line the player types. Older stories look it up in $says by the choice text.
        // Pass a string to write a line in place, or null for a silent choice.
        $c = fn (string $text, string $next, ?string $kind = null, ?string $tip = null, string|false|null $line = false) => ['text' => $text, 'say' => $line === false ? $says[$text] : $line, 'next' => $next, 'tip' => $kind ? [$kind, $tip] : null];
        $end = fn (string $kind, string $title, string $text) => ['end' => compact('kind', 'title', 'text')];

        return [
            [
                'id' => 'parcel',
                'tag' => 'SMS scam',
                'hard' => 1,
                'title' => 'The Parcel Fee',
                'blurb' => 'A text says your parcel is stuck. It only costs B$1.80 to fix. What could go wrong?',
                'who' => 'Parcel Desk',
                'start' => 'a',
                'vars' => ['fee' => ['B$1.80', 'B$2.50', 'B$3.20'], 'loss' => ['B$850', 'B$1,200', 'B$640']],
                'flags' => ['A tiny fee used as bait', 'A website name that is not the courier\'s', 'A rush, "your parcel will be sent back in 1 hour"', 'Asking for a one-time code (OTP)'],
                'nodes' => [
                    'a' => ['say' => ['Parcel Desk: We could not deliver your parcel. Pay {fee} to book a new delivery.', 'Pay here: bn-parcel-fee.xyz/pay'], 'choices' => [
                        $c('Tap the link, it is only {fee}', 'page', 'flag', 'A tiny fee and an unknown website. Small amounts make people stop thinking.'),
                        $c('Reply: "Who is this?"', 'reply', null, null),
                        $c('Open the courier\'s real app or website myself', 'win_check', 'good', 'Going to the real app yourself is the safest move.'),
                    ]],
                    'reply' => ['say' => ['This is the parcel desk. Hurry, your parcel will be sent back in 1 hour.', 'Just tap the link and enter your card number.'], 'choices' => [
                        $c('Tap the link', 'page', 'flag', 'A rush plus a link. The deadline is made up to hurry you.'),
                        $c('Stop replying and delete the message', 'win_ignore', 'good', 'Scammers live off replies. Silence ends it.'),
                    ]],
                    'page' => ['say' => ['The page asks for your name, card number and expiry date to pay {fee}.'], 'choices' => [
                        $c('Enter my card details', 'otp', 'flag', 'The real goal was never {fee}. They want your whole card.'),
                        $c('Close the page', 'win_close', 'good', 'Closing the page was the right call.'),
                    ]],
                    'otp' => ['say' => ['Payment pending. A code was sent to your phone. Enter the OTP to finish.'], 'choices' => [
                        $c('Enter the OTP', 'lose_otp', 'flag', 'That code lets them approve a payment, not a fee.'),
                        $c('Stop. Call my bank on the number on my card', 'win_bank', 'good', 'Calling the bank on the card number is exactly right.'),
                    ]],
                    'win_check' => $end('win', 'You checked it yourself', 'There was no parcel waiting. Checking in the real app took 30 seconds and kept your money safe.'),
                    'win_ignore' => $end('win', 'You walked away', 'Deleting the message cost you nothing. You can also forward it to a friend or family member to warn them.'),
                    'win_close' => $end('win', 'You closed the page', 'No money lost. Never type your card details on a page you reached from a message.'),
                    'win_bank' => $end('meh', 'You stopped just in time', 'You gave your card details but kept the code. Call the bank on your card number straight away so they can cancel the card.'),
                    'lose_otp' => $end('lose', 'You lost {loss}', 'With your card number and the code, the scammer approved a {loss} payment. Never share an OTP with anyone. If this happens, call your bank at once, then the anti-scam helpline 16993.'),
                ],
            ],
            [
                'id' => 'police',
                'tag' => 'Phone call',
                'hard' => 3,
                'title' => 'The Police Call',
                'blurb' => 'A serious voice says you are under investigation. They say not to tell anyone.',
                'who' => 'Unknown number',
                'start' => 'a',
                'flags' => ['Pretending to be police or an official', 'Fear and secrecy, "tell no one"', 'A "safe account" to move your money into', 'Asking for more payments again and again'],
                'nodes' => [
                    'a' => ['say' => ['This is Officer Rahim from the police commercial crime unit.', 'Your IC was used to open an account linked to money laundering. You are under investigation.'], 'choices' => [
                        $c('Panic: "What should I do?"', 'secret', 'flag', 'Fear is the tool. Anyone who scares you into acting quickly is a red flag.'),
                        $c('Ask them to send proof on WhatsApp', 'proof'),
                        $c('Say you will hang up and call the police on 993 yourself', 'win_call', 'good', 'Hanging up and calling the real number is the strongest answer.'),
                    ]],
                    'proof' => ['say' => ['[Sends a photo of an official-looking letter with a stamp]', 'You see? Follow my instructions or officers will come to your house.'], 'choices' => [
                        $c('Believe it, the letter looks real', 'secret', 'flag', 'Anyone can fake a letter or stamp. A photo proves nothing.'),
                        $c('Hang up and call 993 myself', 'win_call', 'good', 'Call the police yourself, on a number you looked up.'),
                    ]],
                    'secret' => ['say' => ['Do not tell anyone, not even your family. This is a secret case.', 'To prove you are innocent, move your savings into a "safe account" for checking. You will get it back.'], 'choices' => [
                        $c('Transfer my savings to the safe account', 'again', 'flag', 'There is no "safe account". Real police never ask you to move money.'),
                        $c('Tell a family member or friend what is happening', 'win_family', 'good', 'Scammers want you alone. Telling someone breaks the spell.'),
                        $c('Refuse and hang up', 'win_refuse', 'good', 'You said no. That is allowed.'),
                    ]],
                    'again' => ['say' => ['Good. Now you must pay B$3,000 more as a "case clearance fee", or it will not be cleared.'], 'choices' => [
                        $c('Pay again to clear my name', 'lose_pay', 'flag', 'The demands never stop. That is the trap.'),
                        $c('Stop. Call 16993 and tell the bank', 'meh_stop', 'good', 'Stopping now saves the rest of your money.'),
                    ]],
                    'win_call' => $end('win', 'You called the police yourself', 'The real police confirmed no case against you. Hanging up and calling 993 is always safe.'),
                    'win_family' => $end('win', 'You told someone', 'Your family helped you see the trick. Secrecy is the scammer\'s main weapon, so telling someone defeats it.'),
                    'win_refuse' => $end('win', 'You said no', 'No money lost. Real police do not demand transfers over the phone.'),
                    'meh_stop' => $end('meh', 'You stopped, but money is gone', 'The first transfer may be lost. Call your bank and the anti-scam helpline 16993 straight away, and make a police report.'),
                    'lose_pay' => $end('lose', 'You lost your savings and more', 'Every payment led to a new demand. If this ever happens to you or someone you know, call your bank at once, then 16993 and the police on 993.'),
                ],
            ],
            [
                'id' => 'job',
                'tag' => 'Chat scam',
                'hard' => 2,
                'title' => 'Easy Money Job',
                'blurb' => 'A message offers B$300 a day for liking videos. It even pays you first. Suspicious?',
                'who' => 'Recruiter',
                'start' => 'a',
                'flags' => ['Big pay for almost no work', 'A small payout first to build trust', 'Moving the chat to another app', 'Asking you to deposit money to "unlock" earnings'],
                'nodes' => [
                    'a' => ['say' => ['Hi! Part-time online job: like videos and earn B$300 a day. No experience needed.', 'Interested?'], 'choices' => [
                        $c('Yes, tell me more!', 'move', 'flag', 'B$300 a day for liking videos is not a real job.'),
                        $c('Ask for the company name and website', 'company'),
                        $c('Block the number', 'win_block', 'good', 'Blocking is quick and costs nothing.'),
                    ]],
                    'company' => ['say' => ['We are a global marketing company. Our manager will explain on Telegram. Quick, only 3 spots left!'], 'choices' => [
                        $c('Move to Telegram', 'move', 'flag', 'Taking you to another app hides the chat from the first one.'),
                        $c('Search for the company yourself and find nothing', 'win_search', 'good', 'No trace online is a big warning sign.'),
                    ]],
                    'move' => ['say' => ['Great. Do 3 simple tasks and I will pay you B$15 right away.', '[Sends a link to like three videos]'], 'choices' => [
                        $c('Do the tasks', 'paid', null, null),
                        $c('Skip it and leave the chat', 'win_skip', 'good', 'Walking away early is the safest way.'),
                    ]],
                    'paid' => ['say' => ['You received B$15 in your bank! See, it is real. Now join the VIP group: deposit B$200 to unlock tasks worth B$600.'], 'choices' => [
                        $c('Deposit B$200 to earn more', 'more', 'flag', 'The small payout was bait so you would trust them with more.'),
                        $c('Keep the B$15 and leave', 'win_leave', 'good', 'You left before paying anything.'),
                    ]],
                    'more' => ['say' => ['Deposit received. To withdraw your earnings, you must now deposit B$800 for the tax fee. Hurry!'], 'choices' => [
                        $c('Pay B$800 to get my money out', 'lose_pay', 'flag', 'There is always one more fee. Your money is not coming back.'),
                        $c('Refuse and ask for my B$200 back', 'meh_refuse', 'good', 'Refusing now stops a bigger loss.'),
                    ]],
                    'win_block' => $end('win', 'You blocked it', 'Nothing lost. Real jobs do not message you out of the blue with huge pay.'),
                    'win_search' => $end('win', 'You did your homework', 'Searching a company before replying is a great habit. Scam "companies" usually have no real trace.'),
                    'win_skip' => $end('win', 'You skipped it', 'No money lost. Be careful with any job that pays well for almost no work.'),
                    'win_leave' => $end('win', 'You kept the bait and left', 'You came out ahead, but only because you left in time. Many people stay for the "VIP" step and lose a lot.'),
                    'meh_refuse' => $end('meh', 'You lost B$200 but stopped there', 'They will not give the money back. Report it to your bank and the anti-scam helpline 16993 right away.'),
                    'lose_pay' => $end('lose', 'You lost B$1,000', 'The "tax fee" never ends. A real job never asks you to pay before you can be paid. Call your bank, then 16993.'),
                ],
            ],
            [
                'id' => 'mum',
                'tag' => 'Chat scam',
                'hard' => 1,
                'title' => 'Hi Mum',
                'blurb' => 'A message from a new number says it is your child. They need help with money.',
                'who' => 'New number',
                'start' => 'a',
                'vars' => ['ask' => ['B$600', 'B$450', 'B$800'], 'more' => ['B$400', 'B$300', 'B$500']],
                'flags' => ['"My phone is broken, this is my new number"', 'Urgent money with an excuse', 'Getting angry when you ask questions', 'Asking for more after the first transfer'],
                'nodes' => [
                    'a' => ['say' => ['Hi Mum, my phone fell in the water. This is my new number, please save it.'], 'choices' => [
                        $c('Save it and reply "OK"', 'ask', 'flag', 'A new number from a "child" is the classic opening. Always check first.'),
                        $c('Call my child\'s old number', 'win_call', 'good', 'Calling the old number is the best check.'),
                        $c('Ask a question only my child would know', 'angry'),
                    ]],
                    'angry' => ['say' => ['Mum, I cannot answer now, my phone is broken, I am outside. Stop asking!'], 'choices' => [
                        $c('Feel bad and believe them', 'ask', 'flag', 'Getting upset when you check is a way to make you feel guilty.'),
                        $c('Call my child\'s old number anyway', 'win_call', 'good', 'Right. A real child would not mind you checking.'),
                    ]],
                    'ask' => ['say' => ['I need to pay a supplier today but my banking app is locked. Can you transfer {ask}? I will pay you back tomorrow.'], 'choices' => [
                        $c('Transfer the money right away', 'more', 'flag', 'Urgent money plus an excuse for not using their own banking is the trap.'),
                        $c('Say I will call to confirm first', 'win_confirm', 'good', 'Confirming before sending is always fine.'),
                    ]],
                    'more' => ['say' => ['Thanks Mum! One more thing: {more} for the deposit. I promise it is the last time.'], 'choices' => [
                        $c('Send it, they said it is the last time', 'lose_pay', 'flag', 'The "last time" is rarely the last.'),
                        $c('Stop. Call my child\'s old number now', 'meh_stop', 'good', 'You stopped the second transfer.'),
                    ]],
                    'win_call' => $end('win', 'You called the old number', 'Your child picked up, safe and sound. A quick call to a number you already trust beats every excuse.'),
                    'win_confirm' => $end('win', 'You confirmed first', 'You asked to call before sending money, and the scammer disappeared. Confirming never hurts.'),
                    'meh_stop' => $end('meh', 'You stopped the second transfer', 'The first {ask} may be gone. Tell your bank right now, then call 16993, and warn your family about this trick.'),
                    'lose_pay' => $end('lose', 'You sent money twice', 'Scammers copy family voices and names from social media. Agree on a family code word and always call back before sending money.'),
                ],
            ],
            [
                'id' => 'invest',
                'tag' => 'Chat scam',
                'hard' => 2,
                'title' => 'The Sure Thing',
                'blurb' => 'A stranger says a friend made B$2,000 in a week on a crypto app. No risk, they say.',
                'who' => 'Alex',
                'start' => 'a',
                'flags' => ['"Guaranteed" or "no risk" profits', 'Advice from someone you have never met', 'A small early win to hook you', 'A "tax" or fee before you can withdraw'],
                'nodes' => [
                    'a' => ['say' => ['Hi! A friend of mine made B$2,000 in one week on a crypto platform. I can show you how. There is no risk.'], 'choices' => [
                        $c('Ask how it works', 'how', null, null),
                        $c('Ask which regulator licenses it, and check', 'win_check', 'good', 'Checking the licence first is the smartest step.'),
                        $c('Not interested, block', 'win_block', 'good', 'Blocking is quick and costs nothing.'),
                    ]],
                    'how' => ['say' => ['Just deposit B$300 on our app. Our expert trades for you. We guarantee 20% every week.', '[Sends a link to download the app]'], 'choices' => [
                        $c('Install the app and deposit B$300', 'win_small', 'flag', 'No real investment can guarantee profit. "Guaranteed" is a lie.'),
                        $c('Say I will look it up first', 'win_look', 'good', 'Taking time to research is never rude.'),
                    ]],
                    'win_small' => ['say' => ['Look, your balance is already B$420! Try withdrawing B$100 to see it is real.', '[Your withdrawal of B$100 arrives]'], 'choices' => [
                        $c('Invest B$2,000 more for bigger profits', 'tax', 'flag', 'The small early win is bait so you will put in much more.'),
                        $c('Withdraw everything and stop', 'win_out', 'good', 'Leaving while you can is the smartest move.'),
                    ]],
                    'tax' => ['say' => ['Great choice. Your account is upgraded. To withdraw, you first pay a 15% release tax of B$330.'], 'choices' => [
                        $c('Pay the tax to get my money out', 'lose_tax', 'flag', 'A fee before withdrawing is how they keep taking money.'),
                        $c('Refuse and report it', 'meh_report', 'good', 'Refusing now stops further loss.'),
                    ]],
                    'win_check' => $end('win', 'You checked the licence', 'The platform was not licensed by any regulator. A real investment firm is always happy to show its licence.'),
                    'win_block' => $end('win', 'You blocked it', 'Nothing lost. Be wary of any stranger who brings up investing out of the blue.'),
                    'win_look' => $end('win', 'You looked it up', 'A search quickly showed warnings about the platform. Researching first saved you a lot.'),
                    'win_out' => $end('win', 'You left with your money', 'You walked away with what you put in. Most people keep investing and lose everything, so do not go back.'),
                    'meh_report' => $end('meh', 'You lost B$2,300 but stopped there', 'Report it to your bank and the anti-scam helpline 16993 at once. They may be able to help, and your report helps protect others.'),
                    'lose_tax' => $end('lose', 'You lost B$2,630', 'There was never any profit, only a screen showing numbers. Call your bank, then 16993, and report to the police on 993.'),
                ],
            ],
            [
                'id' => 'romance',
                'tag' => 'Chat scam',
                'hard' => 3,
                'title' => 'Online Love',
                'blurb' => 'Someone kind and charming messages you. After three weeks, they say "I love you". And then they need help.',
                'who' => 'Sarah',
                'start' => 'a',
                'flags' => ['Declaring love very fast', 'Always an excuse not to video call', 'Working "overseas" and unreachable', 'Asking for money, again and again'],
                'nodes' => [
                    'a' => ['say' => ['Hi, I am Sarah, an engineer on an oil rig overseas. I saw your profile and felt a connection. Can we talk?'], 'choices' => [
                        $c('Chat with her, she seems nice', 'love', null, null),
                        $c('Search her photo online first', 'win_search', 'good', 'A reverse image search is a smart first step.'),
                        $c('Ignore the message', 'win_ignore', 'good', 'You do not owe a stranger a reply.'),
                    ]],
                    'love' => ['say' => ['After three weeks of chatting: I love you. I wish we could video call, but my camera is broken on the rig.', 'I want to come and meet you in Brunei.'], 'choices' => [
                        $c('Say I love you too', 'ask', 'flag', 'Love this fast, with no video call, is a classic sign.'),
                        $c('Ask for a video call anyway', 'push'),
                    ]],
                    'push' => ['say' => ['I told you my camera is broken! Why do you not trust me? After everything I have told you?'], 'choices' => [
                        $c('Feel guilty and apologise', 'ask', 'flag', 'Making you feel guilty for checking is a manipulation tactic.'),
                        $c('Tell a friend about her and ask what they think', 'win_friend', 'good', 'An outside opinion is the best protection.'),
                    ]],
                    'ask' => ['say' => ['My bank is frozen on the rig. Can you cover my flight to Brunei? B$900. I will pay you back as soon as I land.'], 'choices' => [
                        $c('Send the B$900', 'more', 'flag', 'Money requests from someone you have never met are always a red flag.'),
                        $c('Say no and stop talking to her', 'win_no', 'good', 'No is a full answer.'),
                    ]],
                    'more' => ['say' => ['Thank you my love! One problem: customs wants B$1,500 to release the gift I sent you. Please help me.'], 'choices' => [
                        $c('Pay the B$1,500 to get the gift', 'lose_pay', 'flag', 'There is no gift. There is always another fee.'),
                        $c('Stop here and report her', 'meh_stop', 'good', 'You stopped the bigger loss.'),
                    ]],
                    'win_search' => $end('win', 'You searched her photo', 'The photos belonged to someone else and appeared on many scam reports. Checking images takes a minute.'),
                    'win_ignore' => $end('win', 'You ignored it', 'Nothing lost. Be careful with friendly strangers who message you first.'),
                    'win_friend' => $end('win', 'You asked a friend', 'Your friend saw the red flags straight away. Talking to someone you trust breaks the spell.'),
                    'win_no' => $end('win', 'You said no', 'No money lost. A person who truly cares about you will not ask for money before you have met.'),
                    'meh_stop' => $end('meh', 'You lost B$900 but stopped there', 'The first transfer is probably gone. Report it to your bank, 16993 and the police. You are not alone, and it is not your fault.'),
                    'lose_pay' => $end('lose', 'You lost B$2,400', 'Romance scammers build trust for weeks before asking. If this happens, tell your bank, then 16993, and talk to someone you trust.'),
                ],
            ],
            [
                'id' => 'bank',
                'tag' => 'Phone call',
                'hard' => 3,
                'title' => 'The Bank Fraud Call',
                'blurb' => 'The "bank" calls about a suspicious transfer. They sound professional and the clock is ticking.',
                'who' => 'Fraud Dept',
                'start' => 'a',
                'vars' => ['amount' => ['B$2,400', 'B$3,100', 'B$1,800'], 'loss' => ['B$12,000', 'B$8,500', 'B$15,000']],
                'flags' => ['A call you did not expect, with a deadline', 'Asking for your one-time code', 'Asking you to install a remote-control app', 'Watching you log in on your own screen'],
                'nodes' => [
                    'a' => ['say' => ['Good afternoon, this is the fraud department at your bank. We detected a suspicious transfer of {amount} from your account.', 'To cancel it, we need to verify you quickly.'], 'choices' => [
                        $c('Panic: "Please cancel it!"', 'app', 'flag', 'Panic is what they want. Deadlines are used to stop you thinking.'),
                        $c('Ask them to prove who they are', 'prove'),
                        $c('Hang up and call the bank on the number on my card', 'win_call', 'good', 'Calling the number on your card is the safest check.'),
                    ]],
                    'prove' => ['say' => ['I understand your concern. My staff ID is 48213. But hurry, the money leaves in 5 minutes!'], 'choices' => [
                        $c('Accept the ID and carry on', 'app', 'flag', 'A staff ID is easy to make up. It proves nothing.'),
                        $c('Hang up and call the bank on the number on my card', 'win_call', 'good', 'Right. A real bank is fine with you calling back.'),
                    ]],
                    'app' => ['say' => ['Please install our security app so I can protect your account.', '[Sends a link to a remote-control app]'], 'choices' => [
                        $c('Install the app', 'screen', 'flag', 'Banks never ask you to install apps over the phone.'),
                        $c('Refuse: banks do not ask for this', 'win_refuse', 'good', 'You saw through it.'),
                    ]],
                    'screen' => ['say' => ['Thank you. Now I can see your screen. Please open your banking app and log in so I can cancel the transfer.'], 'choices' => [
                        $c('Log in while they watch', 'lose_screen', 'flag', 'They can see everything you type.'),
                        $c('Close the app, uninstall it and call my bank', 'meh_stop', 'good', 'Cutting the connection was the right call.'),
                    ]],
                    'win_call' => $end('win', 'You called your bank back', 'Your bank confirmed there was no suspicious transfer and no one from the fraud team had called. Always call back on a number you trust.'),
                    'win_refuse' => $end('win', 'You refused the app', 'No remote access, no loss. A real bank will never ask you to install software on a call.'),
                    'meh_stop' => $end('meh', 'You stopped, but they saw something', 'Change your password straight away and call your bank on the number on your card. Tell them what happened so they can protect your account.'),
                    'lose_screen' => $end('lose', 'You lost {loss}', 'With your screen shared and your password typed, they emptied your account. Never log in with a stranger watching. Call your bank, then 16993, right away.'),
                ],
            ],
            [
                'id' => 'shop',
                'tag' => 'Marketplace',
                'hard' => 1,
                'title' => 'Too Good a Deal',
                'blurb' => 'A seller on a marketplace is selling a brand-new phone for a third of the price.',
                'who' => 'Seller',
                'start' => 'a',
                'vars' => ['price' => ['B$400', 'B$350', 'B$450'], 'fee' => ['B$80', 'B$60', 'B$100']],
                'flags' => ['A price far below normal', 'A story that rushes you, like "leaving the country"', 'Paying a personal account outside the platform', 'A surprise extra fee after you have paid'],
                'nodes' => [
                    'a' => ['say' => ['Brand-new phone for only {price} (usually B$1,200). Selling fast because I am leaving the country!'], 'choices' => [
                        $c('I will take it. How do I pay?', 'pay', 'flag', 'A price this low is bait.'),
                        $c('Ask to meet and check it in person first', 'meet'),
                        $c('Too cheap to be true. Skip it.', 'win_skip', 'good', 'Trust your gut when a price is too good.'),
                    ]],
                    'meet' => ['say' => ['I cannot meet, I am at the airport. I will ship it once you pay. Many happy buyers!', '[Sends screenshots of five-star reviews]'], 'choices' => [
                        $c('Believe the screenshots', 'pay', 'flag', 'Screenshots are easy to fake.'),
                        $c('Walk away', 'win_walk', 'good', 'No way to inspect it means no deal.'),
                    ]],
                    'pay' => ['say' => ['Pay by bank transfer to my personal account. I do not use the platform because of its fees.'], 'choices' => [
                        $c('Transfer {price} to the personal account', 'fee', 'flag', 'Paying outside the platform removes all your protection.'),
                        $c('Say I will only pay through the platform or in cash when I see it', 'win_platform', 'good', 'Insisting on safe payment is smart.'),
                    ]],
                    'fee' => ['say' => ['Payment received, thanks! One more thing: I need {fee} for shipping insurance, or the parcel cannot leave.'], 'choices' => [
                        $c('Pay the {fee} so the phone ships', 'lose_fee', 'flag', 'There is always one more fee. There is no phone.'),
                        $c('Stop and report it to my bank', 'meh_report', 'good', 'Acting now gives your bank the best chance to help.'),
                    ]],
                    'win_skip' => $end('win', 'You skipped it', 'Nothing lost. If a deal looks too good to be true, it almost always is.'),
                    'win_walk' => $end('win', 'You walked away', 'The seller disappeared once you asked to see the phone. Always check items in person for big purchases.'),
                    'win_platform' => $end('win', 'You insisted on safe payment', 'The seller went quiet straight away. Platforms and cash on delivery protect you, and scammers avoid them.'),
                    'meh_report' => $end('meh', 'You lost {price} but stopped there', 'Report it to your bank and the anti-scam helpline 16993. Keep screenshots of the chat and the payment.'),
                    'lose_fee' => $end('lose', 'You lost money and got nothing', 'No phone ever existed. Pay on trusted platforms only, and call your bank and 16993 if it happens.'),
                ],
            ],
            [
                'id' => 'prize',
                'title' => 'Lucky Draw',
                'tag' => 'SMS scam',
                'hard' => 1,
                'blurb' => 'A text says you won a big prize in a draw you never entered. All you need to do is claim it.',
                'who' => 'Prize Team',
                'start' => 'a',
                'vars' => ['prize' => ['B$5,000', 'B$8,000', 'B$3,500'], 'fee' => ['B$120', 'B$200', 'B$150']],
                'flags' => ['A prize from a draw you never joined', 'Asking for your IC number', 'A "tax" or fee to get your prize', 'Pressure to act today'],
                'nodes' => [
                    'a' => ['say' => ['Congratulations! Your number won {prize} in our lucky draw!', 'Claim now: luckydraw-bn.cc. Reply with your full name and IC number.'], 'choices' => [
                        $c('Reply with my name and IC number', 'tax', 'flag', 'Never share your IC details with a stranger. It can be used to pretend to be you.'),
                        $c('Reply: "I never joined a draw"', 'picked'),
                        $c('Delete the message and move on', 'win_delete', 'good', 'Deleting costs you nothing.'),
                    ]],
                    'picked' => ['say' => ['Our system picks numbers automatically! Do not miss out. Just confirm your details to claim.'], 'choices' => [
                        $c('Confirm my details', 'tax', 'flag', 'You cannot win a draw you never joined.'),
                        $c('Block the number', 'win_block', 'good', 'Blocking is a great answer.'),
                    ]],
                    'tax' => ['say' => ['Thank you! To release your prize, you must first pay a {fee} tax. Send it to this account today.'], 'choices' => [
                        $c('Pay the tax to get my prize', 'lose_tax', 'flag', 'A prize that asks you to pay is not a prize.'),
                        $c('Refuse. I will not pay to receive a prize', 'meh_refuse', 'good', 'Refusing stops the money loss.'),
                    ]],
                    'win_delete' => $end('win', 'You deleted it', 'No details shared and no money lost. A prize you did not enter for is never real.'),
                    'win_block' => $end('win', 'You blocked it', 'The scammer lost your number. You can also warn a friend about this trick.'),
                    'meh_refuse' => $end('meh', 'You shared your details, but not money', 'Your name and IC could be misused. Tell your bank, watch for strange calls and report it to the anti-scam helpline 16993.'),
                    'lose_tax' => $end('lose', 'You lost {fee}', 'There was never a prize. If this happens, tell your bank, then 16993, and watch for misuse of the IC details you shared.'),
                ],
            ],
            [
                'id' => 'invoice',
                'title' => 'The Changed Account',
                'tag' => 'Email scam',
                'hard' => 3,
                'blurb' => 'At work, a supplier emails new bank details for an invoice. It looks normal, but is it?',
                'who' => 'Mr Tan (Supplier)',
                'start' => 'a',
                'vars' => ['amount' => ['B$7,850', 'B$9,200', 'B$6,400']],
                'flags' => ['A sudden change of bank details', 'Pressure because "the payment is late"', 'Replying to the same email instead of calling', 'Skipping a second person\'s approval'],
                'nodes' => [
                    'a' => ['say' => ['Hi, please note our bank has changed. Please pay the pending invoice of {amount} to the new account below.', '[Attached: Updated_Bank_Details.pdf]'], 'choices' => [
                        $c('The invoice is real, so I will pay it', 'confirm', 'flag', 'The invoice may be real, but the new account is the trick.'),
                        $c('Call the supplier on the number I already have', 'win_call', 'good', 'Calling a number you already trust is the safest check.'),
                        $c('Reply to the email and ask if it is true', 'same'),
                    ]],
                    'same' => ['say' => ['Yes, it is correct. We are in a hurry, the payment is already late. Please send it today.'], 'choices' => [
                        $c('Pay it now, they confirmed', 'confirm', 'flag', 'The scammer wrote that reply. Asking the same sender proves nothing.'),
                        $c('Call the supplier on the number from an old invoice', 'win_call', 'good', 'Right. Use a number from before the email.'),
                    ]],
                    'confirm' => ['say' => ['Please confirm once the payment is sent and send us the proof of payment.'], 'choices' => [
                        $c('Pay the full amount now', 'lose_pay', 'flag', 'Big payments to a new account deserve a second check.'),
                        $c('Pause and check with my boss first', 'win_boss', 'good', 'A second pair of eyes catches what one person misses.'),
                        $c('Pay half and ask for confirmation tomorrow', 'meh_half', 'flag', 'Half is still money gone.'),
                    ]],
                    'win_call' => $end('win', 'You called the supplier', 'The real supplier said their bank had not changed. The email was a scam. Always verify bank detail changes by phone.'),
                    'win_boss' => $end('win', 'You asked your boss', 'Your boss called the supplier and found the email was fake. A second check is a company\'s best defence.'),
                    'meh_half' => $end('meh', 'You lost half the invoice', 'Call your bank straight away and ask them to recall the payment. Report to the police and the anti-scam helpline 16993.'),
                    'lose_pay' => $end('lose', 'You paid {amount} to a scammer', 'The money went to the criminal\'s account. Contact your bank at once to try to recall it, then call 16993 and report to the police on 993.'),
                ],
            ],
            [
                'id' => 'hacked',
                'title' => 'Hacked Friend',
                'tag' => 'Chat scam',
                'hard' => 2,
                'blurb' => 'A close friend messages you for a favour. It looks exactly like them. Is it really them?',
                'who' => 'Aiman (friend)',
                'start' => 'a',
                'vars' => ['card' => ['B$100', 'B$150', 'B$200']],
                'flags' => ['A friend suddenly asking for money or codes', 'An excuse why they cannot talk', 'Asking for gift or top-up card codes', 'A link to buy the card'],
                'nodes' => [
                    'a' => ['say' => ['Hey, are you free? I need a quick favour.', 'Can you buy a {card} top-up card and send me the code? I will pay you back tonight.'], 'choices' => [
                        $c('Sure, I will buy it now', 'link', 'flag', 'Top-up card codes work like cash and cannot be traced.'),
                        $c('Call my friend on their number to check', 'win_call', 'good', 'A quick call settles it.'),
                        $c('Ask: "What is my dog\'s name?"', 'excuse'),
                    ]],
                    'excuse' => ['say' => ['lol just do it please, it is urgent. I am in a meeting and cannot talk.'], 'choices' => [
                        $c('Buy the card', 'link', 'flag', 'A real friend can answer a simple question.'),
                        $c('Call them anyway', 'win_call', 'good', 'Right. A real friend will not mind.'),
                    ]],
                    'link' => ['say' => ['Thanks!! Buy it here: gift-cards-bn.top. Send me the code as soon as you have it.'], 'choices' => [
                        $c('Buy it and send the code', 'lose_code', 'flag', 'Once the code is sent, the money is gone.'),
                        $c('Buy it but hold the code until they call', 'meh_hold', 'flag', 'You paid, but at least the code did not leave.'),
                        $c('Stop, and warn my friend another way that their account may be hacked', 'win_tell', 'good', 'Telling them through another channel is the kindest move.'),
                    ]],
                    'win_call' => $end('win', 'You called your friend', 'Your friend picked up and said their account had been hacked. You helped them find out.'),
                    'win_tell' => $end('win', 'You warned your friend', 'You saved your money and helped them take back their account. Tell other friends too, as the scammer is messaging them all.'),
                    'meh_hold' => $end('meh', 'You lost the card money', 'You never sent the code, but you did buy the card. Ask the shop if it can be refunded and warn your friend their account is hacked.'),
                    'lose_code' => $end('lose', 'You lost {card}', 'Gift and top-up codes cannot be traced or reversed. Tell your friend, report to 16993 and keep the chat as proof.'),
                ],
            ],
            [
                'id' => 'realalert',
                'title' => 'The Real Alert',
                'tag' => 'Real message!',
                'hard' => 2,
                'blurb' => 'Not every message is a scam. This one is real. Can you tell what to do with a real alert?',
                'who' => 'BIBD',
                'start' => 'a',
                'vars' => ['amt' => ['B$120', 'B$85', 'B$240']],
                'flags' => ['It has no link', 'It tells you to call the number on your card', 'It never asks for a code, password or card number', 'Real alerts exist: check them yourself, do not just ignore them'],
                'nodes' => [
                    'a' => ['say' => ['BIBD: A payment of {amt} was made with your card at an online shop. If this was not you, call the number on the back of your card.'], 'choices' => [
                        $c('Call the number on the back of my card', 'call', 'good', 'This is the safest way to check a real alert.'),
                        $c('Ignore it. It is probably a scam', 'win_ignore', 'flag', 'Some alerts are real. Checking costs only a call.'),
                        $c('Reply with my card number to dispute it', 'lose_reply', 'flag', 'A real bank never needs your card number by reply.'),
                    ]],
                    'call' => ['say' => ['[You call the bank. The agent confirms the payment was not yours.]', 'Agent: We will block the card and send you a new one. We will never ask for your PIN or full card number.'], 'choices' => [
                        $c('Block the card and change my online banking password', 'win_block', 'good', 'Acting right away limits any damage.'),
                        $c('Say I am too busy and hang up', 'meh_busy', 'flag', 'The card is still active and could be used again.'),
                    ]],
                    'win_block' => $end('win', 'You checked it, and it was real', 'The alert was genuine. By calling the number on your card you stopped further payments. Not every message is a scam, so check, do not ignore.'),
                    'win_ignore' => $end('meh', 'You ignored a real alert', 'The payment was real fraud. Because you ignored it, the card stayed active. Next time, check with your bank on the number on your card.'),
                    'meh_busy' => $end('meh', 'The card is still open', 'The bank confirmed the fraud, but you did not block the card. Call back and block it now.'),
                    'lose_reply' => $end('lose', 'You gave your card number to a stranger', 'The reply was picked up by a scammer pretending to be the bank. Call your bank at once to block the card, then report to 16993.'),
                ],
            ],
            [
                'id' => 'sell',
                'title' => 'Overpaid Refund',
                'tag' => 'Marketplace',
                'hard' => 2,
                'blurb' => 'You are selling an item online. The buyer says they paid, and paid too much.',
                'who' => 'Buyer',
                'start' => 'a',
                'vars' => ['price' => ['B$300', 'B$250', 'B$400'], 'extra' => ['B$150', 'B$100', 'B$200']],
                'flags' => ['A screenshot instead of real money', '"I sent too much, send some back"', 'Pressure to hurry', 'Shipping before the money is in your account'],
                'nodes' => [
                    'a' => ['say' => ['I will take it for {price}. I have paid, see the screenshot.', 'Oops, I sent too much by mistake. Please send back the extra {extra} first.'], 'choices' => [
                        $c('Send back {extra}, then ship the item', 'ship', 'flag', 'A screenshot is not money. Check your own account first.'),
                        $c('Check my own bank account first', 'check', 'good', 'Always check your own balance, never a screenshot.'),
                        $c('Say I will ship only when the money is in my account', 'win_wait', 'good', 'That is the rule for every online sale.'),
                    ]],
                    'check' => ['say' => ['[Your account shows no payment.]', 'Buyer: It takes time, banks are slow today! Just send the {extra} now, I am in a hurry.'], 'choices' => [
                        $c('Send {extra} anyway', 'ship', 'flag', 'A rush plus "banks are slow" is a stalling trick.'),
                        $c('Tell them: no money, no item, and I will refund nothing', 'win_firm', 'good', 'Holding firm ends the scam.'),
                    ]],
                    'ship' => ['say' => ['Thanks! Now please ship the item today. My payment will arrive soon, I promise.'], 'choices' => [
                        $c('Ship the item', 'lose_ship', 'flag', 'You are now out the money and the item.'),
                        $c('Wait for the real payment to arrive first', 'meh_wait', 'good', 'You lost the extra you sent, but the item is safe.'),
                    ]],
                    'win_wait' => $end('win', 'You waited for the money', 'No payment ever arrived, and the buyer disappeared. Ship only when the money is in your account.'),
                    'win_firm' => $end('win', 'You held firm', 'The buyer stopped answering. You lost nothing. Your bank balance is the only proof of payment.'),
                    'meh_wait' => $end('meh', 'You lost the refund money', 'You sent back the "extra" before checking, but you did not ship the item. Report to your bank and 16993 and keep the chat.'),
                    'lose_ship' => $end('lose', 'You lost the money and the item', 'You sent back {extra} and shipped the item for a payment that never came. Keep the chat, report to your bank and 16993.'),
                ],
            ],
            [
                'id' => 'ticket',
                'title' => 'Sold-Out Tickets',
                'tag' => 'Social media',
                'hard' => 1,
                'blurb' => 'The show you want is sold out. A seller on social media has tickets for half price.',
                'who' => 'TicketsBN',
                'start' => 'a',
                'vars' => ['price' => ['B$150', 'B$200', 'B$120']],
                'flags' => ['Sold-out tickets at a low price', 'Pay first, tickets later', 'A screenshot instead of a real ticket', 'A surprise fee after you paid'],
                'nodes' => [
                    'a' => ['say' => ['I have 2 tickets for the sold-out show, half price! {price} each. DM me fast, many are waiting.'], 'choices' => [
                        $c('Ask for the seat numbers and proof', 'proof'),
                        $c('Send payment now so I do not miss out', 'fee', 'flag', 'Rushing you is part of the trick.'),
                        $c('Only buy from the official seller', 'win_official', 'good', 'Official sellers are the only safe source.'),
                    ]],
                    'proof' => ['say' => ['[Sends a screenshot of a QR code]', 'Pay first, then I will send the originals. I am in a hurry.'], 'choices' => [
                        $c('Believe it and pay', 'fee', 'flag', 'A screenshot can be copied and sold to many people.'),
                        $c('Walk away', 'win_walk', 'good', 'Walking away is the best move.'),
                    ]],
                    'fee' => ['say' => ['Paid? Great! One small thing: the system needs a B$50 fee to transfer the tickets to your name. Please send again.'], 'choices' => [
                        $c('Pay the extra B$50', 'lose_fee', 'flag', 'There is always another fee.'),
                        $c('Stop and report it to my bank', 'meh_report', 'good', 'Acting quickly gives your bank the best chance to help.'),
                    ]],
                    'win_official' => $end('win', 'You used the official seller', 'You found a safe way to get tickets or decided to skip it. Official resale pages are the only safe place to buy.'),
                    'win_walk' => $end('win', 'You walked away', 'The seller stopped replying as soon as you stopped paying. Nothing lost.'),
                    'meh_report' => $end('meh', 'You lost the first payment', 'Report to your bank and 16993 right away and keep the chat. The sooner you report, the better the chance of help.'),
                    'lose_fee' => $end('lose', 'You lost money and got no tickets', 'The tickets never existed. Report to your bank and 16993, and keep the screenshots and payment proof.'),
                ],
            ],
            [
                'id' => 'rent',
                'tag' => 'Marketplace',
                'hard' => 2,
                'title' => 'The Room for Rent',
                'blurb' => 'A nice room in Gadong, cheap, and the landlord is "overseas". Do you hold it with a deposit?',
                'who' => 'Landlord Aziz',
                'start' => 'a',
                'vars' => ['dep' => ['B$700', 'B$840', 'B$960'], 'rent' => ['B$350', 'B$420', 'B$480']],
                'flags' => ['A great price for the area', '"I am overseas", so no viewing', 'A deposit asked for before you see anything', 'Payment to a personal account with no agreement'],
                'nodes' => [
                    'a' => ['say' => ['Hi! Yes, the room in Gadong is still free. {rent} a month, water and wifi included.', 'I am working overseas, so I cannot show you around. Send a {dep} deposit today to keep it. Others are asking.'], 'choices' => [
                        $c('Ask to see the room first, with a friend', 'visit', 'good', 'Asking to view it is exactly right. A real landlord can arrange a viewing.', 'Could I come this weekend to view it? I would bring a friend.'),
                        $c('Send the deposit before someone else takes it', 'pay', 'flag', 'Being rushed with "others are asking" is a classic pressure trick.', 'I really want it, so I will send the deposit now.'),
                        $c('Search for the room photos online', 'win_photos', 'good', 'A quick image search can show that a listing is stolen.', null),
                    ]],
                    'visit' => ['say' => ['My cousin has the keys but he is away this week. I can send you a video of the room instead.', 'Honestly, I have three people ready to pay today. Do not miss out.'], 'choices' => [
                        $c('Pay only after I have seen the room', 'push', 'good', 'Never pay for something you have not seen.', 'I am only paying after I have seen the room and met the owner. Is that okay?'),
                        $c('The video looks fine, I will pay to hold it', 'pay', 'flag', 'A video can be copied from any listing. It proves nothing.', 'The video looks good. How do I pay?'),
                    ]],
                    'push' => ['say' => ['Then I cannot hold it. Last chance, or I give it to the next person.'], 'choices' => [
                        $c('Walk away from this room', 'win_walk', 'good', 'Good. Pressure is the real thing they are selling.', 'No worries, I will pass. Take care.'),
                        $c('Pay half just to be safe', 'meh_half', 'flag', 'Half is still money gone for a room that may not exist.', 'Fine, half now and the other half when I get the keys.'),
                    ]],
                    'pay' => ['say' => ['Transfer {dep} to this account, it is in my cousin\'s name. Send me the receipt after.'], 'choices' => [
                        $c('Transfer the deposit', 'lose_dep', 'flag', 'A personal account and no agreement means no way back.', 'Done. Here is the receipt.'),
                        $c('Ask for a signed tenancy agreement first', 'paper', 'good', 'A real landlord signs an agreement before taking a deposit.', 'Before I pay, could you send a signed tenancy agreement?'),
                    ]],
                    'paper' => ['say' => ['Sure, here it is.', '[A blurry PDF arrives. It has no address, and the name on it does not match the bank account.]'], 'choices' => [
                        $c('It is signed, so I will pay now', 'lose_dep', 'flag', 'A signed paper means little when the details do not match.', 'Alright, it is signed. Paying now.'),
                        $c('The names do not match, so I stop here', 'win_walk', 'good', 'Well spotted. The name on the paper did not match the account.', 'The name on this is not the same as the account. I am stopping here.'),
                    ]],
                    'win_photos' => $end('win', 'You found the fake', 'The same photos were on a hotel website in another country. A quick search kept your {dep} safe.'),
                    'win_walk' => $end('win', 'You walked away', 'There was no room. Always view a place and meet the owner before paying anything.'),
                    'meh_half' => $end('meh', 'You lost half the deposit', 'You sent half of {dep} and the room never existed. Contact your bank at once, then call 16993 and report to the police on 993.'),
                    'lose_dep' => $end('lose', 'You lost {dep}', 'The money went to a stranger and the room was never real. Call your bank at once, then the anti-scam helpline 16993, and report to the police on 993. Keep the chat as proof.'),
                ],
            ],
            [
                'id' => 'remote',
                'tag' => 'Social media',
                'hard' => 3,
                'title' => 'The Helpful Support',
                'blurb' => 'You moaned online about a failed top-up and "support" replies in minutes. They only need to see your screen.',
                'who' => 'Wallet Help Centre',
                'start' => 'a',
                'vars' => ['amt' => ['B$500', 'B$900', 'B$1,300']],
                'flags' => ['Support that messages you first', 'A new account pretending to be the company', 'Asking you to install a screen-sharing app', 'Asking you to send money back after a "mistake"'],
                'nodes' => [
                    'a' => ['say' => ['Hello! We saw your post about the failed top-up. We are so sorry about that.', 'We can refund you today. Please confirm your full name and phone number first.'], 'choices' => [
                        $c('Give my name and number, finally some help', 'tool', 'flag', 'They found you, you did not find them. Real support rarely starts a chat out of the blue.', 'Thank goodness. I am Hakim and this is the number you are messaging.'),
                        $c('Check if this is the real, verified account', 'check', 'good', 'Checking the account first was smart.', 'Hold on, is this your verified account? I only see a new profile.'),
                        $c('Ignore it and contact support inside the app', 'win_app', 'good', 'Contacting support through the official app is the safest way.', null),
                    ]],
                    'check' => ['say' => ['Yes, we are the real team. Our main account is busy so we use this one.', 'Just follow the steps and you will get your money today.'], 'choices' => [
                        $c('Report the account and use the app instead', 'win_report', 'good', 'Reporting helps protect the next person too.', 'I will report this account and use the app. Thanks anyway.'),
                        $c('Fine, I will follow your steps', 'tool', 'flag', 'A real company does not use a spare account to fix refunds.', 'Fine, let us do it your way.'),
                    ]],
                    'tool' => ['say' => ['To fix it quickly, please install our support app QuickHelp so our agent can see your screen.'], 'choices' => [
                        $c('Install the app', 'screen', 'flag', 'Screen-sharing apps let a stranger watch everything on your phone, including codes.', 'Downloading QuickHelp now.'),
                        $c('Say no, a refund never needs my screen', 'win_no', 'good', 'Correct. A refund never needs control of your phone.', 'No, I am not installing anything. A refund does not need that.'),
                    ]],
                    'screen' => ['say' => ['Great. Now open your banking app so we can see where the refund should go.', 'Please do not close the app.'], 'choices' => [
                        $c('Open my banking app', 'amount', 'flag', 'They can now watch you log in.', 'Opening my banking app.'),
                        $c('Uninstall the app and call my bank', 'meh_uninstall', 'good', 'Stopping here limits the damage.', 'Something feels off. I am removing this app and calling my bank.'),
                    ]],
                    'amount' => ['say' => ['Oh no, we refunded you {amt} too much by mistake.', 'Please send the extra {amt} back right now or our team will be in trouble.'], 'choices' => [
                        $c('Send the money back', 'lose_amt', 'flag', 'There was no extra refund. They used your screen to fake the balance and want you to send real money.', 'Oh no, sorry about that! Sending {amt} back now.'),
                        $c('Cut the connection, uninstall and call my bank', 'meh_cut', 'good', 'Cutting the connection quickly was the right call.', null),
                    ]],
                    'win_app' => $end('win', 'You used the real app', 'The real support team had no record of a message to you. Using the official app kept you safe. Report the fake account so others are not caught.'),
                    'win_report' => $end('win', 'You reported the fake', 'You stopped the scam before it started. Reporting the account helps protect other people who post complaints.'),
                    'win_no' => $end('win', 'You said no', 'No real company needs control of your phone to give a refund. You kept your money and your banking app safe.'),
                    'meh_uninstall' => $end('meh', 'You stopped in time', 'The agent saw your screen for a short while. Change your banking password, check your accounts and call your bank to be safe.'),
                    'meh_cut' => $end('meh', 'You cut it off', 'The scammer had already seen your banking app. Change all your passwords and tell your bank straight away.'),
                    'lose_amt' => $end('lose', 'You sent {amt} to a scammer', 'Nothing was ever refunded. Call your bank at once, then the anti-scam helpline 16993, and report to the police on 993.'),
                ],
            ],
            [
                'id' => 'scholar',
                'tag' => 'Email scam',
                'hard' => 2,
                'title' => 'The Lucky Scholarship',
                'blurb' => 'An email says you won a scholarship you never applied for. All you have to do is reply.',
                'who' => 'Scholarship Office',
                'start' => 'a',
                'vars' => ['award' => ['B$15,000', 'B$20,000', 'B$30,000'], 'fee' => ['B$120', 'B$180', 'B$250']],
                'flags' => ['A prize you never applied for', 'Asking for your IC and bank details by email', 'A fee to release the money', 'A short deadline'],
                'nodes' => [
                    'a' => ['say' => ['Congratulations! You have been chosen for the Global Youth Scholarship, worth {award}.', 'Reply with your full name, IC number and bank details within 48 hours to claim it.'], 'choices' => [
                        $c('Send my details, I could really use this', 'fee', 'flag', 'Your IC and bank details are all a scammer needs to take over your identity.', 'This is amazing news! My full name, IC number and bank details are below.'),
                        $c('Ask which scholarship it is and when I applied', 'ask', 'good', 'Right. You cannot win something you never applied for.', 'Sorry, which scholarship is this? I do not remember applying for one.'),
                        $c('Check with my college student office', 'win_check', 'good', 'The student office will know if a scholarship is real.', 'Thank you. I will check with my college student office and get back to you.'),
                    ]],
                    'ask' => ['say' => ['It is a lucky draw scholarship, so no application was needed.', 'Just pay the {fee} processing fee and we will send the money.'], 'choices' => [
                        $c('Pay the fee, it is small for {award}', 'lose_fee', 'flag', 'A small fee for a big prize is the oldest trick there is.', 'A small fee for {award} is worth it. Paying now.'),
                        $c('Say real scholarships never charge a fee', 'win_nofee', 'good', 'True. Real scholarships pay you, you never pay them.', 'Real scholarships never ask students to pay. I am not sending anything.'),
                        $c('Ask for an official letter', 'letter', 'good', 'Asking for proof was a good move. Now check it carefully.', 'Please send an official letter that I can verify.'),
                    ]],
                    'letter' => ['say' => ['Here is the letter.', '[It uses a free email address, has no phone number for the office and a spelling mistake in the title.]', 'Pay {fee} today, the offer is closing.'], 'choices' => [
                        $c('It looks official, I will pay the fee', 'lose_fee', 'flag', 'A free email address and spelling mistakes are not what an official letter looks like.', 'It looks official enough. Okay, paying the fee.'),
                        $c('A free email address and typos, I am out', 'win_nofee', 'good', 'Well spotted. Official letters have proper addresses and are well written.', 'This uses a free email and has typos. I am out.'),
                    ]],
                    'fee' => ['say' => ['Thank you! One last step. Pay {fee} for tax so the money can be released.'], 'choices' => [
                        $c('Pay the tax fee', 'lose_fee', 'flag', 'Tax is taken out of real prizes. You do not pay it first.', 'Alright, paying it now.'),
                        $c('Stop and tell my bank I shared my details', 'meh_shared', 'good', 'Telling your bank early lets them watch for misuse.', 'I think I made a mistake sharing my details. I am stopping here and speaking to my bank.'),
                    ]],
                    'win_check' => $end('win', 'You checked first', 'Your student office had never heard of it. Checking with someone you trust kept your details safe.'),
                    'win_nofee' => $end('win', 'You did not pay', 'There was no scholarship. Real ones never ask you to pay, and never ask for your IC and bank details in an email.'),
                    'meh_shared' => $end('meh', 'You shared your details, then stopped', 'Your IC and bank details are with a scammer. Tell your bank, watch for strange activity and call 16993. Never send them by email or chat.'),
                    'lose_fee' => $end('lose', 'You paid {fee} for nothing', 'There was no scholarship. If you shared any details, call your bank at once, then report to 16993 and the police on 993.'),
                ],
            ],
            [
                'id' => 'charity',
                'tag' => 'Social media',
                'hard' => 2,
                'title' => 'The Urgent Donation',
                'blurb' => 'A stranger shares a heartbreaking story and asks for help right now. How do you help safely?',
                'who' => 'Hana Rahman',
                'start' => 'a',
                'vars' => ['amt' => ['B$50', 'B$100', 'B$150']],
                'flags' => ['A stranger asking for money in a private chat', 'Photos you cannot check', 'Pressure to give right now', 'A personal bank account instead of a registered charity'],
                'nodes' => [
                    'a' => ['say' => ['Salam. My little nephew needs an operation and the family has run out of money.', 'Please donate {amt} to my personal account today. Every dollar saves him.'], 'choices' => [
                        $c('Donate right away, it is for a child', 'more', 'flag', 'Scammers use sad stories because they make you act before you think.', 'Poor boy. I am sending {amt} right now.'),
                        $c('Ask which hospital and for the doctor\'s letter', 'proof', 'good', 'Asking for proof is fair. Real families can show something.', 'I am so sorry to hear that. Which hospital is he in, and could you send the doctor\'s letter?'),
                        $c('Look up her photos online', 'win_reverse', 'good', 'A quick image search can show that a story is stolen.', null),
                    ]],
                    'more' => ['say' => ['Thank you so much. But the operation is tomorrow, can you give {amt} more?', 'And please share my post with all your friends!'], 'choices' => [
                        $c('Send more, I do not want to be too late', 'lose_don', 'flag', 'The ask keeps growing. That is how they take more.', 'I do not want to be too late. Sending more now.'),
                        $c('That is enough, I will not send any more', 'meh_once', 'good', 'Stopping was right. Giving once is a loss, giving twice is worse.', 'I have given what I can. I will not be sending any more.'),
                    ]],
                    'proof' => ['say' => ['I cannot say the hospital, the family wants privacy.', 'Here is a photo of him. Please, there is not much time.'], 'choices' => [
                        $c('Send it anyway, the photo broke my heart', 'lose_don', 'flag', 'A photo proves nothing. Anyone can copy a picture.', 'Okay, I believe you. Sending {amt} now.'),
                        $c('Give a small amount just in case', 'meh_small', 'flag', 'You still gave to someone you could not check.', 'I cannot give much, but here is B$10 just in case.'),
                        $c('Offer to give through a registered charity', 'win_charity', 'good', 'A real family will not mind you giving through a charity.', 'I would like to help through a registered charity. Which one is supporting you?'),
                    ]],
                    'win_reverse' => $end('win', 'You found the stolen photos', 'The pictures came from a news story in another country. A quick search saved you from a fake appeal. Report the account.'),
                    'win_charity' => $end('win', 'You chose a registered charity', 'The messages stopped as soon as you asked. If you want to help, give to a registered charity that you can check yourself.'),
                    'meh_once' => $end('meh', 'You gave once and stopped', 'You lost {amt} but did not give more. Report the account, and next time check before giving.'),
                    'meh_small' => $end('meh', 'You lost B$10', 'A small loss, but you still gave to a stranger you could not check. Next time ask for proof or give through a registered charity.'),
                    'lose_don' => $end('lose', 'You lost {amt} and more', 'There was no child and no operation. Call your bank at once, then 16993 and report to the police on 993. Keep the chat and the account name as proof.'),
                ],
            ],
            [
                'id' => 'onboard',
                'tag' => 'Email scam',
                'hard' => 3,
                'title' => 'The Instant Job Offer',
                'blurb' => 'You got the job with no interview. They just need your IC, your bank card and one small favour.',
                'who' => 'HR Manager Lena',
                'start' => 'a',
                'vars' => ['laptop' => ['B$1,450', 'B$1,800', 'B$2,100']],
                'flags' => ['A job offer with no interview', 'Asking for photos of your IC and bank card', 'A "company laptop" you must buy first', '"Paid back later" with a countdown'],
                'nodes' => [
                    'a' => ['say' => ['Congratulations Danial! You got the job. No interview needed, your CV is perfect.', 'Please send photos of the front and back of your IC and your bank card so we can set up your salary.'], 'choices' => [
                        $c('Send the photos, I want this job', 'laptop', 'flag', 'IC and card photos are what a thief wants most.', 'Wow, thank you! Sending the photos now.'),
                        $c('Ask where the office is and offer to bring my IC there', 'office', 'good', 'Asking to do it in person is smart.', 'Thank you so much! Where is the office? I can bring my IC on the first day.'),
                        $c('Search the company and the HR name online', 'win_search', 'good', 'A short search can show that a company does not exist.', null),
                    ]],
                    'office' => ['say' => ['We are fully remote, there is no office.', 'And the offer closes in 24 hours, so please send the photos.'], 'choices' => [
                        $c('Send the photos to keep the job', 'laptop', 'flag', 'A countdown is there to stop you thinking.', 'Okay, I do not want to lose it. Sending them.'),
                        $c('Walk away, real jobs have interviews', 'win_walk', 'good', 'Right. Real companies interview people and check them properly.', 'Real jobs have interviews. I am going to pass, thank you.'),
                    ]],
                    'laptop' => ['say' => ['Great! You start Monday. First, buy your work laptop from our supplier for {laptop}.', 'We will pay you back with your first salary.'], 'choices' => [
                        $c('Pay the supplier', 'lose_laptop', 'flag', 'A real employer gives you the equipment. You do not pay for it.', 'Sure, I will pay the supplier now.'),
                        $c('Ask the company to pay the supplier directly', 'direct', 'good', 'Right. A real company would pay for its own equipment.', 'I do not have that much. Could the company pay the supplier directly?'),
                    ]],
                    'direct' => ['say' => ['That is not our policy. Pay now or we give the job to the next person.'], 'choices' => [
                        $c('Give in and pay', 'lose_laptop', 'flag', 'The pressure was the trap.', 'Fine. I will pay so I do not lose it.'),
                        $c('Walk away and tell my bank about my IC photos', 'meh_id', 'good', 'Telling your bank early helps them watch for misuse.', 'No. I am done here, and I will tell my bank about the photos I sent.'),
                    ]],
                    'win_search' => $end('win', 'You searched first', 'No such company was registered and the HR name had no profile anywhere. A quick search kept your IC safe.'),
                    'win_walk' => $end('win', 'You walked away', 'No interview, no office and a countdown. You were right to pass.'),
                    'meh_id' => $end('meh', 'You stopped, but they have your IC', 'Your IC and card photos are with a scammer. Tell your bank, change your passwords and report to 16993.'),
                    'lose_laptop' => $end('lose', 'You lost {laptop}', 'There was no job and no supplier. Call your bank at once, then 16993 and report to the police on 993. Your IC and card photos are also with the scammer, so ask the bank to protect your account.'),
                ],
            ],
            [
                'id' => 'qr',
                'tag' => 'QR code',
                'hard' => 1,
                'title' => 'The Free Fuel QR',
                'blurb' => 'A poster in a group chat offers free fuel if you scan a QR code. What is hiding behind it?',
                'who' => 'Fuel Promo',
                'start' => 'a',
                'vars' => ['reward' => ['B$20', 'B$30', 'B$50'], 'loss' => ['B$680', 'B$940', 'B$1,150']],
                'flags' => ['A free reward for a quick scan', 'A QR code you cannot read before scanning', 'A page asking for your banking login', 'A countdown, "only 200 left"'],
                'nodes' => [
                    'a' => ['say' => ['FREE {reward} fuel voucher! Scan the QR code and log in with your banking app to claim.', 'Only 200 vouchers left today!'], 'choices' => [
                        $c('Scan the QR, free fuel is free fuel', 'page', 'flag', 'You cannot see where a QR code goes before you scan it.', 'Free fuel? Yes please! Scanning it now.'),
                        $c('Ask who made the poster and where the QR goes', 'ask', 'good', 'Asking first is a good habit.', 'Who is behind this offer, and where does the QR code take me?'),
                        $c('Check the fuel company\'s own page first', 'win_check', 'good', 'Real offers are on the company\'s own page and app.', 'Let me check the fuel company\'s own page first.'),
                    ]],
                    'ask' => ['say' => ['It is from the fuel company itself, no need to check. Hurry, it is going fast!'], 'choices' => [
                        $c('Scan it anyway', 'page', 'flag', 'A rush and no answers is a bad sign.', 'Okay, okay. Scanning it.'),
                        $c('Say a real offer would be on the official page', 'win_skip', 'good', 'Exactly. If it is real, it will be on the official page.', 'If it is real it will be on your official page. I will skip it.'),
                        $c('Post it in the group and ask if it is real', 'win_group', 'good', 'Asking others can reveal a scam fast.', 'Does anyone know if this is real? I am posting it in the group.'),
                    ]],
                    'page' => ['say' => ['The page looks like your bank\'s login. It asks for your username, password and the code just sent to your phone.'], 'choices' => [
                        $c('Enter my login and the code', 'lose_login', 'flag', 'That code lets the scammer log in as you.', 'Alright, typing my login and the code now.'),
                        $c('Enter only my name and phone number', 'meh_info', 'flag', 'Even a name and number can be used to target you.', 'I will only put my name and number, nothing else.'),
                        $c('The address looks odd, so I close the page', 'win_close', 'good', 'The web address did not match the bank. Closing it was the right call.', null),
                    ]],
                    'win_check' => $end('win', 'You checked the real page', 'The fuel company had no such offer. Checking the official source took a minute and kept you safe.'),
                    'win_skip' => $end('win', 'You skipped it', 'There was no voucher. If an offer is real, you can find it on the company\'s own page or app.'),
                    'win_group' => $end('win', 'You asked the group', 'Three friends said the same poster had drained their accounts. Asking others can save you.'),
                    'win_close' => $end('win', 'You closed the page', 'The web address did not match the bank. Never log in to your bank from a page you reached through a QR code.'),
                    'meh_info' => $end('meh', 'You gave your name and number', 'No money lost, but expect scam calls and texts now. Do not share more, and block unknown numbers.'),
                    'lose_login' => $end('lose', 'You lost {loss}', 'The page was a fake and the scammer logged in with your code. Call your bank at once, then 16993 and report to the police on 993. Never log in to your bank from a QR code.'),
                ],
            ],
        ];
    }
}