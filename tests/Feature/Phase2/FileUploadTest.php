<?php

use App\Modules\Admin\Models\Category;
use App\Modules\Admin\Models\Group;
use App\Modules\Shared\Models\User;
use App\Modules\Tickets\Exceptions\InvalidFileException;
use App\Modules\Tickets\Livewire\CreateTicket;
use App\Modules\Tickets\Models\Ticket;
use App\Modules\Tickets\Models\TicketAttachment;
use App\Modules\Tickets\Services\FileUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('local');
    RateLimiter::clear('upload:*');
});

// ─── Helpers ─────────────────────────────────────────────────────────────────

function makeTicketForUser(User $user): Ticket
{
    $group    = Group::factory()->create();
    $category = Category::factory()->create(['group_id' => $group->id]);

    return Ticket::withoutGlobalScopes()->create([
        'display_number'  => 'TKT-0000001',
        'subject'         => 'Test ticket',
        'description'     => '<p>Test.</p>',
        'status'          => \App\Modules\Tickets\Enums\TicketStatus::AwaitingAssignment,
        'category_id'     => $category->id,
        'group_id'        => $category->group_id,
        'requester_id'    => $user->id,
        'incident_origin' => 'web',
    ]);
}

function makeCategoryForUpload(): Category
{
    $group = Group::factory()->create();
    return Category::factory()->create(['group_id' => $group->id]);
}

// ─── Happy path ───────────────────────────────────────────────────────────────

it('valid image upload creates TicketAttachment and stores file', function () {
    $user   = User::factory()->create();
    $ticket = makeTicketForUser($user);
    $image  = UploadedFile::fake()->image('photo.jpg', 400, 300);

    $attachment = app(FileUploadService::class)->store($image, $ticket, $user);

    expect($attachment)->toBeInstanceOf(TicketAttachment::class)
        ->and($attachment->mime_type)->toBe('image/jpeg')
        ->and($attachment->original_name)->toBe('photo.jpg')
        ->and($attachment->ticket_id)->toBe($ticket->id);

    expect(Storage::disk('local')->exists($attachment->file_path))->toBeTrue();
});

it('processed image dimensions do not exceed 2048px on either edge', function () {
    $user   = User::factory()->create();
    $ticket = makeTicketForUser($user);
    // Large enough to test resize
    $image  = UploadedFile::fake()->image('large.jpg', 3000, 2500);

    $attachment = app(FileUploadService::class)->store($image, $ticket, $user);

    $content           = Storage::disk('local')->get($attachment->file_path);
    [$width, $height]  = getimagesizefromstring($content);

    expect($width)->toBeLessThanOrEqual(2048)
        ->and($height)->toBeLessThanOrEqual(2048);
});

it('small image is not upscaled beyond original dimensions', function () {
    $user   = User::factory()->create();
    $ticket = makeTicketForUser($user);
    $image  = UploadedFile::fake()->image('small.jpg', 100, 80);

    $attachment = app(FileUploadService::class)->store($image, $ticket, $user);

    $content          = Storage::disk('local')->get($attachment->file_path);
    [$width, $height] = getimagesizefromstring($content);

    expect($width)->toBeLessThanOrEqual(2048)
        ->and($height)->toBeLessThanOrEqual(2048);
});

// ─── MIME validation ──────────────────────────────────────────────────────────

it('rejects a .jpg file whose magic bytes are PDF', function () {
    $user   = User::factory()->create();
    $ticket = makeTicketForUser($user);

    $tmpFile = tempnam(sys_get_temp_dir(), 'fuptest');
    file_put_contents($tmpFile, '%PDF-1.4 fake pdf bytes to confuse extension check');
    $file = new UploadedFile($tmpFile, 'photo.jpg', null, null, true);

    expect(fn () => app(FileUploadService::class)->store($file, $ticket, $user))
        ->toThrow(InvalidFileException::class);
});

it('rejects a file whose MIME is not on the allowed list', function () {
    $user   = User::factory()->create();
    $ticket = makeTicketForUser($user);

    $tmpFile = tempnam(sys_get_temp_dir(), 'fuptest');
    file_put_contents($tmpFile, str_repeat('A', 256)); // octet-stream
    $file = new UploadedFile($tmpFile, 'file.exe', null, null, true);

    expect(fn () => app(FileUploadService::class)->store($file, $ticket, $user))
        ->toThrow(InvalidFileException::class);
});

// ─── Size limit ───────────────────────────────────────────────────────────────

it('rejects a file over 10 MB', function () {
    $user   = User::factory()->create();
    $ticket = makeTicketForUser($user);

    $tmpFile = tempnam(sys_get_temp_dir(), 'fuptest');
    // Write just over 10 MB (finfo will see application/octet-stream but size check fires first)
    $handle  = fopen($tmpFile, 'wb');
    fwrite($handle, str_repeat("\0", 10 * 1024 * 1024 + 1));
    fclose($handle);
    $file = new UploadedFile($tmpFile, 'bigfile.bin', null, null, true);

    expect(fn () => app(FileUploadService::class)->store($file, $ticket, $user))
        ->toThrow(InvalidFileException::class, 'File exceeds the 10 MB maximum.');
});

// ─── Count limit ─────────────────────────────────────────────────────────────

it('rejects the 6th attachment on the same ticket', function () {
    $user   = User::factory()->create();
    $ticket = makeTicketForUser($user);

    // Create 5 existing attachments via factory
    TicketAttachment::factory()->count(5)->create([
        'ticket_id'  => $ticket->id,
        'uploaded_by' => $user->id,
    ]);

    $image = UploadedFile::fake()->image('extra.jpg', 100, 100);

    expect(fn () => app(FileUploadService::class)->store($image, $ticket, $user))
        ->toThrow(InvalidFileException::class, 'Maximum of 5 attachments per ticket.');
});

// ─── Serve route — authorization ─────────────────────────────────────────────

it('unauthorized user gets 403 when accessing an attachment', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    $ticket     = makeTicketForUser($owner);
    $attachment = TicketAttachment::factory()->create([
        'ticket_id'   => $ticket->id,
        'file_path'   => "tickets/{$ticket->id}/somefile",
        'mime_type'   => 'image/jpeg',
        'uploaded_by' => $owner->id,
    ]);
    Storage::disk('local')->put("tickets/{$ticket->id}/somefile", 'content');

    $this->actingAs($other)
        ->get(route('tickets.attachments.show', [$ticket->id, $attachment->id]))
        ->assertStatus(403);
});

it('ticket requester can download their own attachment', function () {
    $user = User::factory()->create();

    $ticket     = makeTicketForUser($user);
    $attachment = TicketAttachment::factory()->create([
        'ticket_id'   => $ticket->id,
        'file_path'   => "tickets/{$ticket->id}/somefile",
        'mime_type'   => 'image/jpeg',
        'uploaded_by' => $user->id,
    ]);
    Storage::disk('local')->put("tickets/{$ticket->id}/somefile", 'fake jpeg content');

    $this->actingAs($user)
        ->get(route('tickets.attachments.show', [$ticket->id, $attachment->id]))
        ->assertStatus(200)
        ->assertHeader('Content-Type', 'image/jpeg');
});

it('unauthenticated user is redirected from the serve route', function () {
    $owner = User::factory()->create();
    $ticket = makeTicketForUser($owner);
    $attachment = TicketAttachment::factory()->create([
        'ticket_id'   => $ticket->id,
        'file_path'   => "tickets/{$ticket->id}/file",
        'mime_type'   => 'image/jpeg',
        'uploaded_by' => $owner->id,
    ]);

    $this->get(route('tickets.attachments.show', [$ticket->id, $attachment->id]))
        ->assertRedirect('/login');
});

// ─── Upload rate limit ────────────────────────────────────────────────────────

it('returns 429 on the 21st upload attempt within an hour', function () {
    $user     = User::factory()->create();
    $category = makeCategoryForUpload();
    $key      = 'upload:' . $user->id;

    for ($i = 0; $i < 20; $i++) {
        RateLimiter::hit($key, 3600);
    }

    $image = UploadedFile::fake()->image('photo.jpg', 100, 100);

    Livewire::actingAs($user)
        ->test(CreateTicket::class)
        ->set('subject', 'Upload rate limit test')
        ->set('description', '<p>Test.</p>')
        ->set('category_id', $category->id)
        ->set('attachments', [$image])
        ->call('submit')
        ->assertStatus(429);
});

// ─── Livewire end-to-end ─────────────────────────────────────────────────────

it('ticket created via form with image attachment stores the attachment', function () {
    $user     = User::factory()->create();
    $category = makeCategoryForUpload();
    $image    = UploadedFile::fake()->image('ticket_photo.jpg', 200, 150);

    Livewire::actingAs($user)
        ->test(CreateTicket::class)
        ->set('subject', 'Ticket with attachment')
        ->set('description', '<p>Has an image.</p>')
        ->set('category_id', $category->id)
        ->set('attachments', [$image])
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect();

    $ticket = Ticket::withoutGlobalScopes()->first();

    expect($ticket->attachments)->toHaveCount(1);
    expect(Storage::disk('local')->exists($ticket->attachments->first()->file_path))->toBeTrue();
});
