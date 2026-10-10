<?php

use App\Models\Investigation;
use App\Models\Report;
use App\Models\User;
use App\Notifications\InvestigationNotice;

beforeEach(function () {
    $this->withoutVite();
});

function notifiedScan(?User $owner = null): Report
{
    return Report::factory()->create([
        'user_id' => ($owner ?? User::factory()->create())->id,
        'url' => 'http://paypa1-secure-login.example/verify',
        'status' => 'completed',
    ]);
}

test('asking for an investigation tells the active team members and admins but not the asker, suspended staff or regular users', function () {
    $asker = User::factory()->create(['is_team_member' => false]);
    $handlerA = User::factory()->create(['is_team_member' => true]);
    $handlerB = User::factory()->create(['is_team_member' => true]);
    $admin = User::factory()->create(['role' => 'admin', 'is_team_member' => false]);
    $suspended = User::factory()->create(['is_team_member' => true, 'suspended_at' => now()]);
    $other = User::factory()->create(['is_team_member' => false]);
    $scan = notifiedScan($asker);

    $this->actingAs($asker)->post(route('investigations.request', $scan), ['notes' => 'Looks real'])->assertRedirect();

    expect($handlerA->notifications)->toHaveCount(1)
        ->and($handlerB->notifications)->toHaveCount(1)
        ->and($admin->notifications)->toHaveCount(1)
        ->and($suspended->notifications)->toHaveCount(0)
        ->and($asker->notifications)->toHaveCount(0)
        ->and($other->notifications)->toHaveCount(0);

    $data = $handlerA->notifications->first()->data;
    expect($data['kind'])->toBe(InvestigationNotice::REQUESTED)
        ->and($data['message'])->toContain('New investigation request on http://paypa1-secure-login.example/verify')
        ->and($data['report_id'])->toBe($scan->id);
});

test('a real status change tells the person who asked, and saving the same status does not', function () {
    $asker = User::factory()->create(['is_team_member' => false]);
    $handler = User::factory()->create(['is_team_member' => true]);
    $scan = notifiedScan($asker);
    $investigation = Investigation::create(['report_id' => $scan->id, 'requested_by' => $asker->id, 'status' => 'active']);

    $this->actingAs($handler)->patch(route('investigations.update', $investigation), ['status' => 'takedown_confirmed'])->assertRedirect();

    expect($asker->notifications)->toHaveCount(1)
        ->and($asker->notifications->first()->data['message'])->toContain('is now: Takedown confirmed');

    $this->actingAs($handler)->patch(route('investigations.update', $investigation), ['status' => 'takedown_confirmed', 'notes' => 'Only a note'])->assertRedirect();

    expect($asker->fresh()->notifications)->toHaveCount(1);
});

test('nobody is told about their own change, or when no one asked for the investigation', function () {
    $handler = User::factory()->create(['is_team_member' => true]);
    $scan = notifiedScan();
    $own = Investigation::create(['report_id' => $scan->id, 'requested_by' => $handler->id, 'status' => 'active']);
    $unasked = Investigation::create(['report_id' => notifiedScan()->id, 'status' => 'active']);

    $this->actingAs($handler)->patch(route('investigations.update', $own), ['status' => 'completed'])->assertRedirect();
    $this->actingAs($handler)->patch(route('investigations.update', $unasked), ['status' => 'completed'])->assertRedirect();

    expect($handler->notifications)->toHaveCount(0)
        ->and(\Illuminate\Notifications\DatabaseNotification::count())->toBe(0);
});

test('the bell shows only your own notifications and counts the unread ones', function () {
    $me = User::factory()->create();
    $someoneElse = User::factory()->create();
    $inv = Investigation::create(['report_id' => notifiedScan()->id, 'status' => 'active']);

    $me->notify(new InvestigationNotice($inv->load('report'), InvestigationNotice::REQUESTED));
    $me->notify(new InvestigationNotice($inv, InvestigationNotice::STATUS_CHANGED));
    $someoneElse->notify(new InvestigationNotice($inv, InvestigationNotice::REQUESTED));
    $me->notifications()->first()->markAsRead();

    $this->actingAs($me)->getJson(route('notifications.index'))
        ->assertOk()
        ->assertJsonPath('unread', 1)
        ->assertJsonCount(2, 'items');

});

test('a guest gets nothing from the bell', function () {
    $this->getJson(route('notifications.index'))->assertRedirect(route('login'));
    $this->postJson(route('notifications.read-all'))->assertRedirect(route('login'));
});

test('opening a notification marks it read and goes to its scan, and nobody can open someone elses', function () {
    $me = User::factory()->create();
    $other = User::factory()->create();
    $scan = notifiedScan($me);
    $inv = Investigation::create(['report_id' => $scan->id, 'status' => 'active']);
    $me->notify(new InvestigationNotice($inv->load('report'), InvestigationNotice::REQUESTED));
    $id = $me->notifications()->first()->id;

    $this->actingAs($other)->get(route('notifications.open', $id))->assertNotFound();
    expect($me->fresh()->unreadNotifications)->toHaveCount(1);

    $this->actingAs($me)->get(route('notifications.open', $id))->assertRedirect(route('scan.show', $scan));
    expect($me->fresh()->unreadNotifications)->toHaveCount(0);
});

test('mark all as read clears the count', function () {
    $me = User::factory()->create();
    $inv = Investigation::create(['report_id' => notifiedScan()->id, 'status' => 'active']);
    $me->notify(new InvestigationNotice($inv->load('report'), InvestigationNotice::REQUESTED));
    $me->notify(new InvestigationNotice($inv, InvestigationNotice::STATUS_CHANGED));

    $this->actingAs($me)->postJson(route('notifications.read-all'))
        ->assertOk()
        ->assertJsonPath('unread', 0);

    expect($me->fresh()->unreadNotifications)->toHaveCount(0);
});

test('the bell is in the dashboard layout with the unread count ready for the page', function () {
    $me = User::factory()->create();
    $inv = Investigation::create(['report_id' => notifiedScan()->id, 'status' => 'active']);
    $me->notify(new InvestigationNotice($inv->load('report'), InvestigationNotice::REQUESTED));

    $this->actingAs($me)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('aria-label="Notifications"', false)
        ->assertSee('"unread":1', false);
});

test('opening an investigation and assigning it tells the person it was assigned to, but not yourself', function () {
    $opener = User::factory()->create(['is_team_member' => true]);
    $admin = User::factory()->create(['role' => 'admin', 'is_team_member' => false]);
    $colleague = User::factory()->create(['is_team_member' => true]);

    $this->actingAs($opener)->post(route('investigations.store', notifiedScan()), ['assigned_to' => $admin->id])->assertRedirect();
    $this->actingAs($opener)->post(route('investigations.store', notifiedScan()), ['assigned_to' => $opener->id])->assertRedirect();

    expect($admin->notifications)->toHaveCount(1)
        ->and($admin->notifications->first()->data['kind'])->toBe(InvestigationNotice::ASSIGNED)
        ->and($admin->notifications->first()->data['message'])->toContain('You were assigned an investigation on')
        ->and($opener->notifications)->toHaveCount(0)
        ->and($colleague->notifications)->toHaveCount(0);
});

test('handing an investigation to someone new tells them, keeping the same person does not', function () {
    $handler = User::factory()->create(['is_team_member' => true]);
    $colleague = User::factory()->create(['is_team_member' => true]);
    $investigation = Investigation::create(['report_id' => notifiedScan()->id, 'assigned_to' => $handler->id, 'status' => 'active']);

    $this->actingAs($handler)->patch(route('investigations.update', $investigation), ['status' => 'active', 'assigned_to' => $handler->id, 'notes' => 'Same person'])->assertRedirect();
    expect($colleague->fresh()->notifications)->toHaveCount(0);

    $this->actingAs($handler)->patch(route('investigations.update', $investigation), ['status' => 'active', 'assigned_to' => $colleague->id])->assertRedirect();
    expect($colleague->fresh()->notifications)->toHaveCount(1)
        ->and($colleague->fresh()->notifications->first()->data['kind'])->toBe(InvestigationNotice::ASSIGNED);

    $this->actingAs($handler)->patch(route('investigations.update', $investigation), ['status' => 'active', 'assigned_to' => $colleague->id])->assertRedirect();
    expect($colleague->fresh()->notifications)->toHaveCount(1);
});

test('a screenshot scan has no address, so the message shows its scan reference instead', function () {
    $team = User::factory()->create(['is_team_member' => true]);
    $asker = User::factory()->create();
    $shot = Report::factory()->create(['user_id' => $asker->id, 'type' => 'screenshot', 'url' => null, 'sender_email' => null, 'phone_number' => null, 'status' => 'completed']);

    $this->actingAs($asker)->post(route('investigations.request', $shot))->assertRedirect();

    expect($team->notifications->first()->data['message'])->toContain('a screenshot scan (PG-');
});

test('you can delete one of your own notifications, and not someone elses', function () {
    $me = User::factory()->create();
    $other = User::factory()->create();
    $inv = Investigation::create(['report_id' => notifiedScan()->id, 'status' => 'active']);
    $me->notify(new InvestigationNotice($inv->load('report'), InvestigationNotice::REQUESTED));
    $me->notify(new InvestigationNotice($inv, InvestigationNotice::STATUS_CHANGED));
    $id = $me->notifications()->first()->id;

    $this->actingAs($other)->deleteJson(route('notifications.destroy', $id))->assertNotFound();
    expect($me->notifications()->count())->toBe(2);

    $this->actingAs($me)->deleteJson(route('notifications.destroy', $id))
        ->assertOk()
        ->assertJsonCount(1, 'items');
    expect($me->notifications()->count())->toBe(1);
});

test('clear all removes only your own notifications', function () {
    $me = User::factory()->create();
    $other = User::factory()->create();
    $inv = Investigation::create(['report_id' => notifiedScan()->id, 'status' => 'active']);
    $me->notify(new InvestigationNotice($inv->load('report'), InvestigationNotice::REQUESTED));
    $me->notify(new InvestigationNotice($inv, InvestigationNotice::STATUS_CHANGED));
    $other->notify(new InvestigationNotice($inv, InvestigationNotice::REQUESTED));

    $this->actingAs($me)->deleteJson(route('notifications.clear'))
        ->assertOk()
        ->assertJsonPath('unread', 0)
        ->assertJsonCount(0, 'items');

    expect($me->notifications()->count())->toBe(0)
        ->and($other->notifications()->count())->toBe(1);
});

test('notifications older than 30 days are removed when the bell refreshes', function () {
    $me = User::factory()->create();
    $inv = Investigation::create(['report_id' => notifiedScan()->id, 'status' => 'active']);
    $me->notify(new InvestigationNotice($inv->load('report'), InvestigationNotice::REQUESTED));
    $me->notify(new InvestigationNotice($inv, InvestigationNotice::STATUS_CHANGED));
    $me->notifications()->first()->forceFill(['created_at' => now()->subDays(31)])->save();

    $this->actingAs($me)->getJson(route('notifications.index'))
        ->assertOk()
        ->assertJsonCount(1, 'items');

    expect($me->notifications()->count())->toBe(1);
});

test('opening an investigation with nobody assigned tells the rest of the team and the admins', function () {
    $opener = User::factory()->create(['is_team_member' => true]);
    $colleague = User::factory()->create(['is_team_member' => true]);
    $admin = User::factory()->create(['role' => 'admin', 'is_team_member' => false]);
    $regular = User::factory()->create(['is_team_member' => false]);

    $this->actingAs($opener)->post(route('investigations.store', notifiedScan()), ['notes' => 'Needs an owner'])->assertRedirect();

    expect($colleague->notifications)->toHaveCount(1)
        ->and($admin->notifications)->toHaveCount(1)
        ->and($opener->notifications)->toHaveCount(0)
        ->and($regular->notifications)->toHaveCount(0)
        ->and($colleague->notifications->first()->data['kind'])->toBe(InvestigationNotice::UNASSIGNED)
        ->and($colleague->notifications->first()->data['message'])->toContain('New unassigned investigation on');
});