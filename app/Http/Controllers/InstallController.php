<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Throwable;

/**
 * One-time web installer for hosting without shell access (DirectAdmin, cPanel).
 * Enabled only while INSTALL_TOKEN is set and storage/app/installed.lock is absent.
 * Delete the lock file to run it again after an update (it only applies new migrations).
 */
class InstallController extends Controller
{
    public function show(Request $request)
    {
        $token = $this->guard($request);
        [$dbOk, $dbError] = $this->database();

        return view('install', [
            'token' => $token,
            'requirements' => $this->requirements(),
            'dbOk' => $dbOk,
            'dbError' => $dbError,
            'pending' => $dbOk ? $this->pendingMigrations() : [],
            'tablesReady' => $dbOk && $this->tablesReady(),
            'admins' => $dbOk && $this->tablesReady() ? User::query()->orderBy('email')->get(['name', 'email', 'role']) : collect(),
        ]);
    }

    public function migrate(Request $request)
    {
        $token = $this->guard($request);

        try {
            Artisan::call('migrate', ['--force' => true]);
            $output = trim(Artisan::output());

            return $this->back($token)->with('status', 'Database updated.')->with('output', $output ?: 'Nothing to migrate.');
        } catch (Throwable $e) {
            report($e);

            return $this->back($token)->with('error', 'Migration failed: '.$e->getMessage());
        }
    }

    public function admin(Request $request)
    {
        $token = $this->guard($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        User::query()->updateOrCreate(['email' => strtolower($data['email'])], [
            'name' => $data['name'],
            'password' => $data['password'],
            'role' => 'admin',
        ]);

        return $this->back($token)->with('status', 'Administrator '.$data['email'].' saved.');
    }

    public function finish(Request $request)
    {
        $token = $this->guard($request);
        if (! $this->tablesReady() || $this->pendingMigrations() || ! User::query()->where('role', 'admin')->exists()) {
            return $this->back($token)->with('error', 'Run the database setup and create an administrator first.');
        }

        file_put_contents($this->lockPath(), 'Installed '.now()->toIso8601String().PHP_EOL);

        return redirect()->route('admin.login')->with('status', 'Installation complete. The installer is now disabled.');
    }

    private function guard(Request $request): string
    {
        $expected = (string) config('app.install_token');
        $given = (string) $request->input('token', $request->query('token', ''));

        abort_if(file_exists($this->lockPath()), 404);
        abort_unless(strlen($expected) >= 16 && hash_equals($expected, $given), 404);

        return $given;
    }

    private function back(string $token)
    {
        return redirect()->route('install.show', ['token' => $token]);
    }

    private function lockPath(): string
    {
        return storage_path('app/installed.lock');
    }

    /** @return array{0: bool, 1: ?string} */
    private function database(): array
    {
        try {
            DB::connection()->getPdo();

            return [true, null];
        } catch (Throwable $e) {
            return [false, $e->getMessage()];
        }
    }

    private function tablesReady(): bool
    {
        try {
            return DB::connection()->getSchemaBuilder()->hasTable('rsvps');
        } catch (Throwable) {
            return false;
        }
    }

    /** @return list<string> */
    private function pendingMigrations(): array
    {
        $migrator = app('migrator');
        $files = array_keys($migrator->getMigrationFiles(database_path('migrations')));
        $ran = $migrator->repositoryExists() ? $migrator->getRepository()->getRan() : [];

        return array_values(array_diff($files, $ran));
    }

    /** @return list<array{label: string, ok: bool, required: bool, detail?: string}> */
    private function requirements(): array
    {
        $driver = config('database.connections.'.config('database.default').'.driver');
        $pdo = $driver === 'pgsql' ? 'pdo_pgsql' : ($driver === 'sqlite' ? 'pdo_sqlite' : 'pdo_mysql');

        $checks = [['label' => 'PHP 8.3 or newer (found '.PHP_VERSION.')', 'ok' => version_compare(PHP_VERSION, '8.3.0', '>='), 'required' => true]];
        foreach ([$pdo, 'mbstring', 'openssl', 'tokenizer', 'ctype', 'fileinfo', 'xml', 'xmlwriter', 'zip', 'gd', 'curl'] as $ext) {
            $checks[] = ['label' => "PHP extension: $ext", 'ok' => extension_loaded($ext), 'required' => $ext !== 'curl'];
        }
        foreach (['storage', 'storage/app', 'storage/framework/cache', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs', 'bootstrap/cache'] as $dir) {
            $checks[] = ['label' => "Writable: $dir", 'ok' => is_writable(base_path($dir)), 'required' => true];
        }
        $checks[] = ['label' => 'APP_KEY is set', 'ok' => filled(config('app.key')), 'required' => true];
        $checks[] = ['label' => 'APP_URL uses https ('.config('app.url').')', 'ok' => str_starts_with((string) config('app.url'), 'https://'), 'required' => false];
        $checks[] = ['label' => 'APP_DEBUG is off', 'ok' => ! config('app.debug'), 'required' => false];
        $checks[] = ['label' => 'CRON_TOKEN is set (for reminder emails)', 'ok' => strlen((string) config('app.cron_token')) >= 16, 'required' => false];

        return $checks;
    }
}
