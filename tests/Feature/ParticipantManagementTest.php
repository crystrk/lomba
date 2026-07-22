<?php

use App\Models\Competition;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->competition = Competition::factory()->draft()->create();
});

it('admin can view participant list', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.competitions.participants.index', $this->competition))
        ->assertOk();
});

it('admin can create participant', function () {
    $this->actingAs($this->admin);

    $this->post(route('admin.competitions.participants.store', $this->competition), [
        'name' => 'Tim Garuda',
        'short_name' => 'GAR',
    ])->assertRedirect(route('admin.competitions.participants.index', $this->competition));

    $participant = Participant::where('name', 'Tim Garuda')->first();

    expect($participant)->not->toBeNull()
        ->and($participant->competition_id)->toBe($this->competition->id)
        ->and($participant->normalized_name)->toBe('tim garuda');
});

it('admin can create participant with logo', function () {
    Storage::fake('public');

    $this->actingAs($this->admin)
        ->post(route('admin.competitions.participants.store', $this->competition), [
            'name' => 'Tim Elang',
            'logo' => UploadedFile::fake()->image('logo.png'),
        ])->assertRedirect();

    $participant = Participant::where('name', 'Tim Elang')->first();

    expect($participant)->not->toBeNull();
    Storage::disk('public')->assertExists($participant->logo_path);
});

it('rejects duplicate name in same competition', function () {
    $this->actingAs($this->admin);

    $this->post(route('admin.competitions.participants.store', $this->competition), [
        'name' => 'Tim Garuda',
    ])->assertRedirect();

    $this->post(route('admin.competitions.participants.store', $this->competition), [
        'name' => 'tim garuda',
    ])->assertInvalid(['name']);
});

it('allows same name in different competitions', function () {
    $this->actingAs($this->admin);

    $other = Competition::factory()->draft()->create();

    $this->post(route('admin.competitions.participants.store', $this->competition), [
        'name' => 'Tim Garuda',
    ])->assertRedirect();

    $this->post(route('admin.competitions.participants.store', $other), [
        'name' => 'Tim Garuda',
    ])->assertRedirect();
});

it('rejects invalid logo upload', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.competitions.participants.store', $this->competition), [
            'name' => 'Tim',
            'logo' => UploadedFile::fake()->create('doc.pdf', 100),
        ])->assertInvalid(['logo']);
});

it('admin can edit participant', function () {
    $participant = Participant::factory()->create([
        'competition_id' => $this->competition->id,
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.competitions.participants.update', [$this->competition, $participant]), [
            'name' => 'Updated Name',
            'short_name' => 'UNW',
        ])->assertRedirect(route('admin.competitions.participants.index', $this->competition));

    expect($participant->fresh()->name)->toBe('Updated Name')
        ->and($participant->fresh()->short_name)->toBe('UNW');
});

it('admin can delete participant', function () {
    $participant = Participant::factory()->create([
        'competition_id' => $this->competition->id,
    ]);

    $this->actingAs($this->admin)
        ->delete(route('admin.competitions.participants.destroy', [$this->competition, $participant]))
        ->assertRedirect(route('admin.competitions.participants.index', $this->competition));

    expect(Participant::find($participant->id))->toBeNull();
});

it('cannot mutate participant on locked competition', function () {
    $locked = Competition::factory()->locked()->create();
    $participant = Participant::factory()->create([
        'competition_id' => $locked->id,
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.competitions.participants.store', $locked), [
            'name' => 'New',
        ])->assertForbidden();

    $this->actingAs($this->admin)
        ->put(route('admin.competitions.participants.update', [$locked, $participant]), [
            'name' => 'Hacked',
        ])->assertForbidden();

    $this->actingAs($this->admin)
        ->delete(route('admin.competitions.participants.destroy', [$locked, $participant]))
        ->assertForbidden();
});

it('guest cannot access participants', function () {
    $this->get(route('admin.competitions.participants.index', $this->competition))
        ->assertRedirect(route('login'));
});

it('operator cannot manage participants', function () {
    $operator = User::factory()->operator()->create();

    $this->actingAs($operator)
        ->post(route('admin.competitions.participants.store', $this->competition), [
            'name' => 'Hacked',
        ])->assertForbidden();
});

it('admin can bulk store participants per line', function () {
    $this->actingAs($this->admin);

    $raw = "FC Garuda Jakarta\nElang United Bandung\nHarimau FC Surabaya\n\nBadak Hitam FC Medan";

    $this->post(route('admin.competitions.participants.bulk-store', $this->competition), [
        'raw_names' => $raw,
    ])->assertRedirect(route('admin.competitions.participants.index', $this->competition));

    expect($this->competition->participants()->count())->toBe(4);
    expect(Participant::where('name', 'FC Garuda Jakarta')->exists())->toBeTrue();
    expect(Participant::where('name', 'Elang United Bandung')->exists())->toBeTrue();
});
