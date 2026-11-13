<?php

use Illuminate\Support\Facades\Storage;

test('attachment storage is private and outside the public directory', function () {
    $attachmentDisk = config('filesystems.disks.attachments');

    expect($attachmentDisk)
        ->toMatchArray([
            'driver' => 'local',
            'root' => storage_path('app/private/attachments'),
            'serve' => false,
            'visibility' => 'private',
        ]);
    expect($attachmentDisk['root'])->not->toStartWith(public_path());
});

test('attachment files are not exposed through the public storage path', function () {
    Storage::fake('attachments');
    Storage::disk('attachments')->put('evidence/proof.txt', 'private evidence');

    $response = $this->get('/storage/attachments/evidence/proof.txt');

    Storage::disk('attachments')->assertExists('evidence/proof.txt');
    $response->assertForbidden();
});

test('attachment limits accept only the initial safe file set', function () {
    expect(config('sitop.attachments.max_size_kilobytes'))->toBe(10240);
    expect(config('sitop.attachments.allowed_extensions'))
        ->toContain('pdf', 'png', 'docx', 'xlsx')
        ->not->toContain('html', 'svg', 'php', 'exe', 'zip');
});
