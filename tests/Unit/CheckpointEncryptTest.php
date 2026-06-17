<?php

namespace HasinHayder\TyroCheckpoint\Tests\Unit;

use HasinHayder\TyroCheckpoint\Drivers\DriverManager;
use HasinHayder\TyroCheckpoint\Exceptions\CheckpointException;
use HasinHayder\TyroCheckpoint\Services\CheckpointService;
use HasinHayder\TyroCheckpoint\TyroCheckpointServiceProvider;
use Illuminate\Encryption\Encrypter;
use Orchestra\Testbench\TestCase;
use PDO;

class CheckpointEncryptTest extends TestCase {
    private string $storagePath;

    private string $dbPath;

    private string $encryptionKey = '0123456789abcdef0123456789abcdef';

    protected function getPackageProviders($app): array {
        return [TyroCheckpointServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void {
        $this->storagePath = sys_get_temp_dir().'/tyro_encrypt_test_'.uniqid();
        $this->dbPath = $this->storagePath.'/app.sqlite';

        @mkdir($this->storagePath, 0755, true);

        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => $this->dbPath,
        ]);
        $app['config']->set('tyro-checkpoint.storage_path', $this->storagePath.'/checkpoints');
        $app['config']->set('tyro-checkpoint.encryption_key', $this->encryptionKey);
        $app['config']->set('app.cipher', 'AES-256-CBC');
    }

    protected function setUp(): void {
        parent::setUp();
        $this->createSqliteDatabase();
    }

    protected function tearDown(): void {
        parent::tearDown();
        $this->removeDirectory($this->storagePath);
    }

    private function createSqliteDatabase(): void {
        $pdo = new PDO('sqlite:'.$this->dbPath);
        $pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT)');
        $pdo->exec("INSERT INTO users (name) VALUES ('alice')");
        $pdo = null;
    }

    private function removeDirectory(string $dir): void {
        if (! is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir.'/'.$item;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    private function decryptRaw(string $payload): string {
        return (new Encrypter($this->encryptionKey, 'AES-256-CBC'))->decrypt($payload);
    }

    private function countUsers(): int {
        $pdo = new PDO('sqlite:'.$this->dbPath);

        return (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }

    public function test_encrypt_encrypts_snapshot_in_place_without_adding_a_new_entry(): void {
        $service = $this->app->make(CheckpointService::class);

        $checkpoint = $service->create('baseline', null, false);
        $originalPath = $checkpoint->path;
        $originalPlaintext = file_get_contents($originalPath);

        $this->assertFalse($checkpoint->encrypted);
        $this->assertFileExists($originalPath);

        $originalCount = $service->list()->count();

        $encrypted = $service->encrypt('baseline');

        // Metadata updated in place (same entry, encrypted flag flipped, path
        // now points at the ciphertext sidecar).
        $this->assertTrue($encrypted->encrypted);
        $this->assertSame($originalPath.'.enc', $encrypted->path);
        $this->assertGreaterThan(0, $encrypted->size);

        // No new entry created
        $this->assertSame($originalCount, $service->list()->count());

        // Original plaintext snapshot is removed; ciphertext lives at the sidecar
        $this->assertFileDoesNotExist($originalPath);
        $this->assertFileExists($encrypted->path);

        // The sidecar holds ciphertext (not the plaintext) and decrypts back
        $currentContent = file_get_contents($encrypted->path);
        $this->assertNotSame($originalPlaintext, $currentContent);
        $this->assertSame($originalPlaintext, $this->decryptRaw($currentContent));
    }

    public function test_encrypt_keeps_checkpoint_valid_when_metadata_save_fails(): void {
        $service = $this->app->make(CheckpointService::class);
        $service->create('baseline', null, false);

        $plaintextPath = $service->find('baseline')->path;
        $plaintext = file_get_contents($plaintextPath);

        // A service whose metadata write always fails.
        $failing = new class($this->app->make(DriverManager::class)) extends CheckpointService {
            protected function saveCheckpoints(array $checkpoints): void {
                throw new CheckpointException('simulated metadata save failure');
            }
        };

        try {
            $failing->encrypt('baseline');
            $this->fail('Expected CheckpointException for simulated save failure.');
        } catch (CheckpointException $e) {
            $this->assertStringContainsString('simulated metadata save failure', $e->getMessage());
        }

        // The checkpoint must remain a valid, unencrypted, restorable checkpoint.
        $reloaded = $service->find('baseline');
        $this->assertNotNull($reloaded);
        $this->assertFalse($reloaded->encrypted);
        $this->assertFileExists($reloaded->path);

        // No stray ciphertext sidecar left behind.
        $this->assertFileDoesNotExist($reloaded->path.'.enc');

        // Plaintext content is untouched.
        $this->assertSame($plaintext, file_get_contents($reloaded->path));

        // And the checkpoint is still restorable.
        $service->restore('baseline');
        $this->assertSame(1, $this->countUsers());
    }

    public function test_encrypt_throws_when_checkpoint_already_encrypted(): void {
        $service = $this->app->make(CheckpointService::class);
        $service->create('baseline', null, true);

        $this->expectException(CheckpointException::class);
        $this->expectExceptionMessage('already encrypted');

        $service->encrypt('baseline');
    }

    public function test_encrypt_throws_when_checkpoint_not_found(): void {
        $service = $this->app->make(CheckpointService::class);

        $this->expectException(CheckpointException::class);
        $this->expectExceptionMessage('not found');

        $service->encrypt('does_not_exist');
    }

    public function test_encrypt_throws_when_encryption_key_is_missing(): void {
        $service = $this->app->make(CheckpointService::class);
        $service->create('baseline', null, false);

        config(['tyro-checkpoint.encryption_key' => null]);

        $this->expectException(CheckpointException::class);
        $this->expectExceptionMessage('Encryption key not found');

        $service->encrypt('baseline');
    }

    public function test_encrypted_checkpoint_remains_restorable_via_auto_decrypt(): void {
        $service = $this->app->make(CheckpointService::class);
        $service->create('baseline', null, false);
        $service->encrypt('baseline');

        // Mutate the live database to prove the snapshot is restored afterwards
        $pdo = new PDO('sqlite:'.$this->dbPath);
        $pdo->exec('DELETE FROM users');
        $pdo = null;
        $this->assertSame(0, $this->countUsers());

        $restored = $service->restore('baseline');

        $this->assertTrue($restored->encrypted);
        $this->assertSame(1, $this->countUsers());
    }

    public function test_encrypt_command_is_idempotent_and_does_not_double_encrypt(): void {
        $service = $this->app->make(CheckpointService::class);
        $checkpoint = $service->create('baseline', null, true);
        $this->assertTrue($checkpoint->encrypted);

        $ciphertextBefore = file_get_contents($checkpoint->path);

        // Running the command on an already-encrypted checkpoint succeeds and
        // must be a no-op (no double encryption, file byte-identical).
        $this->artisan('tyro-checkpoint:encrypt', ['identifier' => (string) $checkpoint->id])
            ->assertSuccessful();

        $this->assertSame($ciphertextBefore, file_get_contents($checkpoint->path));

        $reloaded = $service->find('baseline');
        $this->assertTrue($reloaded->encrypted);
    }
}
