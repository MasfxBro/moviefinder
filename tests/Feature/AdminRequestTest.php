<?php

use App\Enums\RequestStatus;
use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\TitleRequest;
use App\Models\User;
use App\Notifications\RequestResolved;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['admin.path' => 'private-ops-test']);
    $this->admin = User::factory()->create(['role' => Role::Admin, 'email_verified_at' => now()]);

    $this->withSession(['admin_2fa_verified' => true])
        ->actingAs($this->admin, 'admin');
});

test('approve request logs audit and notifies user', function () {
    Notification::fake();
    $user = User::factory()->create(['email_verified_at' => now()]);
    $titleReq = TitleRequest::factory()->create([
        'user_id' => $user->id,
        'status' => RequestStatus::Pending,
    ]);

    $response = $this->post("/private-ops-test/requests/{$titleReq->id}/approve", [
        'tmdb_id' => 0,
        'media_type' => 'movie',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('title_requests', [
        'id' => $titleReq->id,
        'status' => RequestStatus::Approved->value,
    ]);
    $this->assertTrue(AuditLog::where('action', 'request.approve')->where('subject_id', $titleReq->id)->exists());
    Notification::assertSentTo($user, RequestResolved::class);
});

test('merge requests logs audit', function () {
    $primary = TitleRequest::factory()->create(['status' => RequestStatus::Pending]);
    $dup1 = TitleRequest::factory()->create(['status' => RequestStatus::Pending]);
    $dup2 = TitleRequest::factory()->create(['status' => RequestStatus::Pending]);

    $response = $this->post('/private-ops-test/requests/merge', [
        'primary_id' => $primary->id,
        'duplicate_ids' => [$dup1->id, $dup2->id],
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('title_requests', ['id' => $primary->id, 'status' => RequestStatus::Pending->value]);
    $this->assertDatabaseHas('title_requests', ['id' => $dup1->id, 'status' => RequestStatus::Rejected->value]);
    $this->assertDatabaseHas('title_requests', ['id' => $dup2->id, 'status' => RequestStatus::Rejected->value]);
    $this->assertTrue(AuditLog::where('action', 'request.merge')->where('subject_id', $primary->id)->exists());
});
