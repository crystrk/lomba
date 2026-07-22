<?php

use App\Models\Competition;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('database seeder does not seed demo data by default', function (): void {
    $this->seed(DatabaseSeeder::class);

    expect(User::where('email', 'demo.wasit@cryst.web.id')->exists())->toBeFalse();
    expect(Competition::count())->toBe(0);
});

test('demo data seeder seeds demo wasit account and 5 competitions', function (): void {
    $this->seed(DemoDataSeeder::class);

    // Verify user account
    $wasit = User::where('email', 'demo.wasit@cryst.web.id')->first();
    expect($wasit)->not()->toBeNull()
        ->and($wasit->isOperator())->toBeTrue()
        ->and($wasit->is_active)->toBeTrue();

    // Verify the 5 requested competitions
    expect(Competition::count())->toBe(5);

    $competitionSlugs = [
        'liga-mini-soccer-indonesia-2026',
        'kejuaraan-voli-putra-open-2026',
        'turnamen-tenis-lapangan-master-2026',
        'kejuaraan-tenis-meja-tunggal-2026',
        'turnamen-catur-cepat-rapid-chess-2026',
    ];

    foreach ($competitionSlugs as $slug) {
        $competition = Competition::where('slug', $slug)->first();
        expect($competition)->not()->toBeNull();
        expect($competition->participants()->count())->toBeGreaterThan(0);
        expect($competition->operators()->where('users.id', $wasit->id)->exists())->toBeTrue();
    }
});
