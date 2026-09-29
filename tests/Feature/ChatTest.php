<?php

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\ClinicSetting;
use App\Models\Lead;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    ClinicSetting::put('chat.enabled', '1');
});

function visitorToken(): string
{
    return (string) Str::uuid();
}

function frontDesk(): User
{
    return User::where('email', 'reception@irish.test')->firstOrFail();
}

it('says the chat is off until the office turns it on', function () {
    ClinicSetting::put('chat.enabled', null);

    $this->getJson('/chat/status')
        ->assertOk()
        ->assertJson(['enabled' => false]);
});

it('greets with what the office set', function () {
    ClinicSetting::put('chat.greeting', 'Ask us anything about treatments.');

    $this->getJson('/chat/status')->assertOk()->assertJson([
        'greeting' => 'Ask us anything about treatments.',
    ]);
});

it('accepts a message from a visitor who has never signed in', function () {
    $token = visitorToken();

    $this->postJson('/chat/'.$token, ['body' => 'Does the Hydra Facial hurt?'])
        ->assertCreated()
        ->assertJsonPath('messages.0.body', 'Does the Hydra Facial hurt?');

    expect(ChatConversation::withoutGlobalScopes()->where('token', $token)->exists())->toBeTrue();
});

it('turns the conversation into an enquiry once somebody gives a name', function () {
    $token = visitorToken();

    $this->postJson('/chat/'.$token, [
        'body' => 'How much is a consultation?',
        'name' => 'Bea Santos',
        'phone' => '0917 000 1111',
    ])->assertCreated()->assertJson(['asked' => true]);

    $lead = Lead::where('phone', '0917 000 1111')->firstOrFail();

    expect($lead->first_name)->toBe('Bea')
        ->and($lead->last_name)->toBe('Santos')
        ->and($lead->source)->toBe('chat')
        ->and($lead->privacy_consent_at)->not->toBeNull();
});

it('makes one enquiry, however many messages arrive', function () {
    $token = visitorToken();

    $this->postJson('/chat/'.$token, ['body' => 'Hello', 'name' => 'Bea Santos']);
    $this->postJson('/chat/'.$token, ['body' => 'Are you open on Sunday?']);
    $this->postJson('/chat/'.$token, ['body' => 'And Saturdays?']);

    // Otherwise the desk gets a queue full of the same person.
    expect(Lead::count())->toBe(1);
});

it('will not show a conversation to a token that is not this one', function () {
    $mine = visitorToken();
    $theirs = visitorToken();

    $this->postJson('/chat/'.$mine, ['body' => 'private question', 'name' => 'Bea Santos']);
    $this->postJson('/chat/'.$theirs, ['body' => 'a different thread']);

    $this->getJson('/chat/'.$mine)
        ->assertOk()
        ->assertJsonPath('messages.0.body', 'private question');
});

it('shows the front desk a conversation and what is in it', function () {
    $token = visitorToken();
    $this->postJson('/chat/'.$token, [
        'body' => 'Is the facial safe during pregnancy?',
        'name' => 'Andrea Lim',
    ]);

    $this->actingAs(frontDesk())->get('/admin/chat')->assertOk()->assertInertia(
        fn ($page) => $page->component('admin/chat/index')
            ->where('conversations.data.0.name', 'Andrea Lim')
            ->where('unread', 1),
    );
});

it('lets the front desk reply', function () {
    $token = visitorToken();
    $this->postJson('/chat/'.$token, ['body' => 'Do you take walk-ins?']);

    $conversation = ChatConversation::withoutGlobalScopes()->where('token', $token)->firstOrFail();

    $this->actingAs(frontDesk())
        ->post('/admin/chat/'.$conversation->id.'/reply', ['body' => 'Yes, before 5pm.'])
        ->assertRedirect();

    $this->getJson('/chat/'.$token)->assertJsonPath('messages.1.body', 'Yes, before 5pm.');
});

it('keeps emoji intact through a real round trip', function () {
    $token = visitorToken();
    $blessing = 'Do you take walk-ins? 🙏😊';
    $reply = 'Yes, before 5pm ✨';

    $this->postJson('/chat/'.$token, ['body' => $blessing])->assertCreated();

    $conversation = ChatConversation::withoutGlobalScopes()->where('token', $token)->firstOrFail();
    $this->actingAs(frontDesk())
        ->post('/admin/chat/'.$conversation->id.'/reply', ['body' => $reply])
        ->assertRedirect();

    // Read back off the database rather than trusting the response, so a
    // mangled byte in the column would surface here instead of in a browser.
    $stored = ChatMessage::withoutGlobalScopes()
        ->where('chat_conversation_id', $conversation->id)
        ->orderBy('id')
        ->pluck('body')
        ->all();

    expect($stored)->toBe([$blessing, $reply]);

    // And through the API both the visitor and the desk see them unchanged.
    $this->getJson('/chat/'.$token)
        ->assertJsonPath('messages.0.body', $blessing)
        ->assertJsonPath('messages.1.body', $reply);
});

it('counts the 2000 limit in characters, so emoji do not eat the budget', function () {
    $token = visitorToken();

    // Each emoji is four bytes but one character. Counting bytes would reject
    // this at around a third of the way through; counting characters accepts it.
    $body = str_repeat('🙏', 2000);

    $this->postJson('/chat/'.$token, ['body' => $body])->assertCreated();

    expect(ChatMessage::withoutGlobalScopes()->firstOrFail()->body)
        ->toHaveLength(2000)
        ->toBe($body);
});

it('stops the badge once the desk has opened the thread', function () {
    $token = visitorToken();
    $this->postJson('/chat/'.$token, ['body' => 'Are you open?'], ['name' => 'Paolo D']);

    $conversation = ChatConversation::withoutGlobalScopes()->where('token', $token)->firstOrFail();

    $this->actingAs(frontDesk())->get('/admin/chat/'.$conversation->id)->assertOk();

    $this->actingAs(frontDesk())->get('/admin/chat')->assertInertia(
        fn ($page) => $page->where('unread', 0),
    );
});
it('hands the sidebar a count without a full page load', function () {
    $token = visitorToken();
    $this->postJson('/chat/'.$token, [
        'body' => 'Is there parking?',
        'name' => 'Ana R',
    ]);

    $this->actingAs(frontDesk())
        ->getJson('/admin/chat-feed')
        ->assertOk()
        ->assertJsonPath('unread', 1)
        ->assertJsonPath('rows.0.name', 'Ana R')
        // The list labels who spoke last, so the desk can tell a waiting
        // conversation from one they have already answered.
        ->assertJsonPath('rows.0.last_from', 'visitor');
});

it('shows a new conversation on the feed before anybody refreshes', function () {
    $this->actingAs(frontDesk())
        ->getJson('/admin/chat-feed')
        ->assertOk()
        ->assertJsonPath('rows', []);

    $token = visitorToken();
    $this->postJson('/chat/'.$token, [
        'body' => 'Hello?',
        'name' => 'Grace T',
    ]);

    $this->actingAs(frontDesk())
        ->getJson('/admin/chat-feed')
        ->assertJsonCount(1, 'rows')
        ->assertJsonPath('rows.0.name', 'Grace T');
});

it('picks up a reply arriving in an open thread', function () {
    $token = visitorToken();
    $this->postJson('/chat/'.$token, [
        'body' => 'Do you do HIFU?',
        'name' => 'Mara S',
    ]);

    $conversation = ChatConversation::withoutGlobalScopes()->where('token', $token)->firstOrFail();
    $desk = frontDesk();

    expect(
        $this->actingAs($desk)
            ->getJson('/admin/chat/'.$conversation->id.'/messages')
            ->assertOk()
            ->json('messages'),
    )->toHaveCount(1);

    // A second visitor message, with nobody reloading the page.
    $this->postJson('/chat/'.$token, ['body' => 'And for the jawline?']);

    $this->actingAs($desk)
        ->getJson('/admin/chat/'.$conversation->id.'/messages')
        ->assertJsonCount(2, 'messages')
        ->assertJsonPath('messages.1.body', 'And for the jawline?');
});

it('keeps the live feed behind the same permission as the inbox', function () {
    $token = visitorToken();
    $this->postJson('/chat/'.$token, [
        'body' => 'Anyone there?',
        'name' => 'Jo R',
    ]);

    // Someone who cannot see leads has no business polling for them, and the
    // feed is a second way to read the same conversations.
    $this->actingAs(User::factory()->create())
        ->getJson('/admin/chat-feed')
        ->assertForbidden();

    $conversation = ChatConversation::withoutGlobalScopes()->where('token', $token)->firstOrFail();

    $this->actingAs(User::factory()->create())
        ->getJson('/admin/chat/'.$conversation->id.'/messages')
        ->assertForbidden();
});


it('closes a conversation without losing the record', function () {
    $token = visitorToken();
    $this->postJson('/chat/'.$token, ['body' => 'Thanks, bye']);

    $conversation = ChatConversation::withoutGlobalScopes()->where('token', $token)->firstOrFail();

    $this->actingAs(frontDesk())->post('/admin/chat/'.$conversation->id.'/close')->assertRedirect();

    $conversation->refresh();
    expect($conversation->status)->toBe('closed')
        ->and($conversation->messages()->count())->toBe(1);
});

it('keeps the chat behind the leads permission', function () {
    // Even a Receptionist cannot open somebody else's clinic, but the point is
    // the gate: a role without leads.view must not reach the inbox.
    $this->actingAs(User::factory()->create())->get('/admin/chat')->assertForbidden();
});
