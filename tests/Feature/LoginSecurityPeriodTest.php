<?php

use App\Models\Accounting_Journal;
use App\Models\Customer;
use App\Models\DailyReport;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CustomerOnboarding;
use Illuminate\Foundation\Console\ServeCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;

trait UsesSqliteTransactionConstraint
{
    use RefreshDatabase;

    public function beforeRefreshingDatabase(): void
    {
        // SQLite cannot ALTER ADD CHECK; preserve its effect in this isolated test database.
        $connection = DB::connection();
        $manager = Mockery::mock(DB::getFacadeRoot());
        DB::swap($manager);
        $manager->shouldReceive('statement')
            ->with('ALTER TABLE transactions ADD CONSTRAINT chk_amount_gt_zero CHECK (amount > 0)')
            ->andReturnUsing(function () use ($connection): bool {
                foreach (['INSERT', 'UPDATE'] as $operation) {
                    $connection->unprepared("CREATE TRIGGER check_amount_{$operation} BEFORE {$operation} ON transactions WHEN NEW.amount <= 0 BEGIN SELECT RAISE(ABORT, 'amount must be positive'); END");
                }

                return true;
            });
    }
}

uses(UsesSqliteTransactionConstraint::class);

function securityTestStaff(string $role): User
{
    return User::create(['username' => strtolower($role), 'staff_name' => $role,
        'role' => $role, 'status' => 'Aktif', 'password' => Hash::make('password')]);
}

function securityTestCustomer(): Customer
{
    return Customer::create(['student_id' => '9090001', 'account_number' => 'RPL-9090',
        'student_name' => 'Nasabah Uji', 'class' => 'X RPL 1', 'pin' => Hash::make('123456'),
        'password' => Hash::make('password'), 'status' => 'Aktif', 'balance' => 0]);
}

test('staff login discards a customer intended URL', function (string $role, string $dashboard) {
    $user = securityTestStaff($role);
    $this->get('/nasabah')->assertRedirect('/login');
    $this->post('/login', ['username' => $user->username, 'password' => 'password'])
        ->assertRedirect('/')->assertSessionMissing('url.intended');
    $this->get('/')->assertRedirect($dashboard);
    $this->get('/login')->assertRedirect('/');
})->with([
    ['Administrator', '/admin/dashboard'], ['Supervisor', '/supervisor/dashboard'], ['Teller', '/teller/transaksi'],
]);

test('customer login discards a staff intended URL', function () {
    $customer = securityTestCustomer();
    $this->get('/admin/dashboard')->assertRedirect('/login');
    $this->post('/login', ['username' => $customer->student_id, 'password' => 'password'])
        ->assertRedirect('/nasabah')->assertSessionMissing('url.intended');
    $this->get('/login')->assertRedirect('/nasabah');
    $this->get('/')->assertRedirect('/nasabah');
});

test('security changes save both secrets and revoke mobile tokens', function () {
    $customer = securityTestCustomer();
    $customer->createToken('mobile');
    $this->actingAs($customer, 'nasabah')->post('/nasabah/ubah-keamanan', [
        'current_password' => 'password', 'current_pin' => '123456',
        'new_password' => 'password-baru', 'new_password_confirmation' => 'password-baru',
        'new_pin' => '654321', 'new_pin_confirmation' => '654321',
    ])->assertRedirect('/nasabah/profil')->assertSessionHasNoErrors();
    $customer->refresh();
    expect(Hash::check('password-baru', $customer->password))->toBeTrue()
        ->and(Hash::check('654321', $customer->pin))->toBeTrue()
        ->and($customer->tokens()->count())->toBe(0);
    $this->get('/nasabah/profil')->assertOk()->assertSee('Password dan PIN berhasil diubah bersama');
});

test('web sessions with the previous password hash are rejected after a reset', function () {
    $customer = securityTestCustomer();
    $oldHash = $customer->password;
    $customer->update(['password' => Hash::make('replacement-password')]);
    $this->actingAs($customer->fresh(), 'nasabah')->withSession(['password_hash_nasabah' => $oldHash])
        ->get('/nasabah/profil')->assertRedirect('/login');
});

test('failed security changes preserve both secrets and do not flash secrets', function (string $pin, string $confirmation) {
    $customer = securityTestCustomer();
    $this->actingAs($customer, 'nasabah')->from('/nasabah/profil')->post('/nasabah/ubah-keamanan', [
        'current_password' => 'password', 'current_pin' => $pin,
        'new_password' => 'password-baru', 'new_password_confirmation' => 'password-baru',
        'new_pin' => '654321', 'new_pin_confirmation' => $confirmation,
    ])->assertSessionHasErrors()->assertSessionMissing('_old_input.current_pin')
        ->assertSessionMissing('_old_input.new_pin')->assertSessionMissing('_old_input.new_password');
    $customer->refresh();
    expect(Hash::check('password', $customer->password))->toBeTrue()
        ->and(Hash::check('123456', $customer->pin))->toBeTrue();
})->with([['000000', '654321'], ['123456', '111111']]);

test('last weekly period covers the end of each month', function (int $year, int $month, string $end) {
    $this->actingAs(securityTestStaff('Supervisor'))->get("/supervisor/laporan-mingguan?year=$year&month=$month&week=5")
        ->assertOk()->assertViewHas('endDate', $end)->assertSee('tanggal 29 sampai akhir bulan');
})->with([[2026, 1, '2026-01-31'], [2026, 11, '2026-11-30'], [2028, 2, '2028-02-29']]);

test('non leap February and invalid periods are handled', function () {
    $this->actingAs(securityTestStaff('Supervisor'));
    $this->get('/supervisor/laporan-mingguan?year=2026&month=2&week=5')
        ->assertRedirect(route('supervisor.laporan_mingguan', ['month' => 2, 'year' => 2026, 'week' => 4]));
    $this->get('/supervisor/laporan-mingguan?month=13&week=6')->assertSessionHasErrors(['month', 'week']);
});

test('approved and rejected reports cannot be decided again', function (string $status) {
    $supervisor = securityTestStaff('Supervisor');
    $report = DailyReport::create(['teller_id' => securityTestStaff('Teller')->id,
        'report_date' => now()->toDateString(), 'total_deposit' => 10000,
        'total_withdrawal' => 0, 'approval_status' => $status, 'notes' => 'Catatan lama']);
    $this->actingAs($supervisor)->post("/supervisor/approval/{$report->id}/reject", ['notes' => 'Catatan baru'])->assertSessionHas('error');
    $this->post("/supervisor/approval/{$report->id}/approve")->assertSessionHas('error');
    expect($report->fresh()->approval_status)->toBe($status)
        ->and($report->fresh()->notes)->toBe('Catatan lama');
})->with(['Approved', 'Rejected']);

test('profile renders the combined security form', function () {
    $this->actingAs(securityTestCustomer(), 'nasabah')->get('/nasabah/profil')
        ->assertOk()->assertSee('Simpan Password dan PIN Baru');
});

test('delivered CSV imports ten customers without creating cash transactions', function () {
    $file = new UploadedFile(base_path('docs/nasabah_uji_import.csv'), 'nasabah_uji_import.csv', 'text/csv', null, true);
    $this->actingAs(securityTestStaff('Administrator'))->post('/admin/nasabah/import', ['file' => $file])
        ->assertRedirect(route('admin.nasabah.import'))->assertSessionHas('import_errors', []);
    expect(Customer::count())->toBe(10)
        ->and(Customer::where('status', Customer::STATUS_PENDING)->count())->toBe(10)
        ->and(Customer::sum('balance'))->toBe(0)
        ->and(Transaction::count())->toBe(0);
});

test('activation records cash once and generates separate credentials', function () {
    $customer = app(CustomerOnboarding::class)->register('9091001', 'Uji Aktivasi', 'X RPL 1');
    $teller = securityTestStaff('Teller');
    $payload = ['amount' => 10000, 'identity_verified' => 1, 'cash_received' => 1, 'verification_note' => 'Kartu pelajar sesuai daftar siswa'];
    $response = $this->actingAs($teller)->post("/teller/nasabah/{$customer->id}/activate", $payload)->assertOk();
    $password = $response->viewData('password');
    $pin = $response->viewData('pin');
    $customer->refresh();
    expect($customer->status)->toBe('Aktif')->and((int) $customer->balance)->toBe(10000)
        ->and(Hash::check($password, $customer->password))->toBeTrue()
        ->and(Hash::check($pin, $customer->pin))->toBeTrue()
        ->and(Transaction::count())->toBe(1)
        ->and(Accounting_Journal::sum('amount'))->toBe(20000);
    expect(Transaction::first()->user_id)->toBe($teller->id);
    $this->post("/teller/nasabah/{$customer->id}/activate", $payload)->assertSessionHasErrors();
    expect(Transaction::count())->toBe(1)->and((int) $customer->fresh()->balance)->toBe(10000);
});

test('activation requires identity verification and cash receipt', function () {
    $customer = app(CustomerOnboarding::class)->register('9091001', 'Uji Aktivasi', 'X RPL 1');
    $this->actingAs(securityTestStaff('Teller'))->post("/teller/nasabah/{$customer->id}/activate", ['amount' => 10000])
        ->assertSessionHasErrors(['identity_verified', 'cash_received', 'verification_note']);
    expect($customer->fresh()->status)->toBe(Customer::STATUS_PENDING)->and(Transaction::count())->toBe(0);
});

test('admin cannot bypass activation or receive opening cash', function () {
    $customer = app(CustomerOnboarding::class)->register('9091001', 'Uji Aktivasi', 'X RPL 1');
    $this->actingAs(securityTestStaff('Administrator'))->patch("/admin/nasabah/{$customer->id}/status")->assertSessionHasErrors();
    $this->post("/teller/nasabah/{$customer->id}/activate", [])->assertForbidden();
    $this->post('/admin/nasabah', ['student_id' => '9091002', 'student_name' => 'Uji', 'class' => 'X RPL 1', 'initial_deposit' => 10000])
        ->assertSessionHasErrors('initial_deposit');
    expect(Transaction::count())->toBe(0);
});

test('inactive customers cannot receive deposits or request withdrawals', function (string $status) {
    $customer = securityTestCustomer();
    $customer->update(['status' => $status, 'balance' => 50000]);
    $this->actingAs(securityTestStaff('Teller'));
    $this->post('/teller/deposit', ['customer_id' => $customer->id, 'amount' => 10000])->assertSessionHas('error');
    $this->post('/teller/withdraw', ['customer_id' => $customer->id, 'amount' => 10000])->assertSessionHas('error');
    expect((int) $customer->fresh()->balance)->toBe(50000)->and(Transaction::count())->toBe(0);
})->with(['NonAktif', Customer::STATUS_PENDING]);

test('inactive API accounts and staff tokens cannot access customer routes', function (string $status) {
    $customer = securityTestCustomer();
    $customer->update(['status' => $status]);
    $token = $customer->createToken('mobile')->plainTextToken;
    $this->withToken($token)->getJson('/api/nasabah/saldo')->assertForbidden();
})->with(['NonAktif', Customer::STATUS_PENDING]);

test('mobile security changes authenticate both old credentials', function () {
    $customer = securityTestCustomer();
    $token = $customer->createToken('mobile')->plainTextToken;
    $this->withToken($token)->postJson('/api/nasabah/security', [
        'current_password' => 'password', 'current_pin' => '123456',
        'new_password' => 'NewPassword123', 'new_password_confirmation' => 'NewPassword123',
        'new_pin' => '654321', 'new_pin_confirmation' => '654321',
    ])->assertOk()->assertJsonPath('success', true);
    $customer->refresh();
    expect(Hash::check('NewPassword123', $customer->password))->toBeTrue()
        ->and(Hash::check('654321', $customer->pin))->toBeTrue()->and($customer->tokens()->count())->toBe(0);
});

test('teller resets only the selected credential after identity verification', function () {
    $customer = securityTestCustomer();
    $customer->createToken('mobile');
    $this->actingAs(securityTestStaff('Teller'));
    $this->post("/teller/nasabah/{$customer->id}/reset", ['reset_type' => 'password'])->assertSessionHasErrors();
    $response = $this->post("/teller/nasabah/{$customer->id}/reset", ['reset_type' => 'password',
        'identity_verified' => 1, 'verification_note' => 'Identitas cocok dengan kartu pelajar'])->assertOk();
    $customer->refresh();
    expect(Hash::check($response->viewData('password'), $customer->password))->toBeTrue()
        ->and(Hash::check('123456', $customer->pin))->toBeTrue()->and($customer->tokens()->count())->toBe(0);
});

test('public account identifiers cannot reset credentials', function () {
    $customer = securityTestCustomer();
    $payload = ['student_id' => $customer->student_id, 'account_number' => $customer->account_number,
        'reset_type' => 'password', 'new_password' => 'attacker-password', 'new_password_confirmation' => 'attacker-password'];
    $this->post('/nasabah/lupa-password', $payload)->assertSessionHasErrors();
    $this->postJson('/api/nasabah/forgot-password', $payload)->assertForbidden();
    expect(Hash::check('password', $customer->fresh()->password))->toBeTrue();
});

test('CSV cannot introduce cash before activation', function (string $amount) {
    $file = UploadedFile::fake()->createWithContent('pending.csv', "nis,nama_siswa,kelas,pin,setoran_awal\n9099991,Uji Kas,X RPL 1,123456,{$amount}\n");
    $this->actingAs(securityTestStaff('Administrator'))->post('/admin/nasabah/import', ['file' => $file])->assertSessionHas('import_errors');
    expect(Customer::count())->toBe(0)->and(Transaction::count())->toBe(0);
})->with(['50000', '-50000', 'Rp50000']);

test('an activated account can log in using its generated password on mobile', function () {
    $customer = app(CustomerOnboarding::class)->register('9091001', 'Uji Aktivasi', 'X RPL 1');
    $this->postJson('/api/nasabah/login', ['login_id' => $customer->student_id, 'password' => '123456'])->assertForbidden();
    $response = $this->actingAs(securityTestStaff('Teller'))->post("/teller/nasabah/{$customer->id}/activate", [
        'amount' => 10000, 'identity_verified' => 1, 'cash_received' => 1, 'verification_note' => 'Identitas diperiksa dengan kartu pelajar',
    ])->assertOk();
    $this->postJson('/api/nasabah/login', ['login_id' => $customer->student_id, 'password' => $response->viewData('password')])
        ->assertOk()->assertJsonPath('success', true)->assertJsonStructure(['token']);
    $this->postJson('/api/nasabah/login', ['login_id' => $customer->student_id, 'password' => $response->viewData('pin')])->assertUnauthorized();
});

test('legacy imported accounts can be marked pending only without money or transactions', function () {
    $customer = securityTestCustomer();
    $customer->createToken('legacy');
    $this->actingAs(securityTestStaff('Administrator'))->patch("/admin/nasabah/{$customer->id}/pending")->assertSessionHasNoErrors();
    expect($customer->fresh()->status)->toBe(Customer::STATUS_PENDING)->and($customer->tokens()->count())->toBe(0);
    $customer->refresh();
    $customer->update(['status' => 'Aktif', 'balance' => 10000]);
    $this->patch("/admin/nasabah/{$customer->id}/pending")->assertSessionHasErrors();
    expect($customer->fresh()->status)->toBe('Aktif')->and((int) $customer->fresh()->balance)->toBe(10000);
});

test('staff tokens cannot impersonate customers with matching numeric ids', function () {
    securityTestCustomer();
    $token = securityTestStaff('Teller')->createToken('staff')->plainTextToken;
    $this->withToken($token)->getJson('/api/nasabah/saldo')->assertForbidden();
});

test('withdrawal confirmation cannot debit the same request twice', function () {
    $customer = securityTestCustomer();
    $customer->update(['balance' => 50000]);
    $teller = securityTestStaff('Teller');
    $transaction = Transaction::create(['transaction_code' => 'WDR-TEST', 'customer_id' => $customer->id,
        'user_id' => $teller->id, 'transaction_date' => now(), 'transaction_type' => 'Penarikan', 'status' => 'pending', 'amount' => 10000]);
    $token = $customer->createToken('mobile')->plainTextToken;
    $this->withToken($token)->postJson('/api/nasabah/confirm-withdrawal', ['transaction_id' => $transaction->id, 'pin' => '123456'])->assertOk();
    $this->postJson('/api/nasabah/confirm-withdrawal', ['transaction_id' => $transaction->id, 'pin' => '123456'])->assertNotFound();
    expect((int) $customer->fresh()->balance)->toBe(40000)->and(Accounting_Journal::count())->toBe(2);
});

test('activation rolls back all cash records if journal creation fails', function () {
    $customer = app(CustomerOnboarding::class)->register('9091001', 'Uji Aktivasi', 'X RPL 1');
    Event::listen('eloquent.creating: '.Accounting_Journal::class, function () {
        throw new RuntimeException('Simulated journal failure');
    });
    try {
        $this->actingAs(securityTestStaff('Teller'))->post("/teller/nasabah/{$customer->id}/activate", [
            'amount' => 10000, 'identity_verified' => 1, 'cash_received' => 1, 'verification_note' => 'Identitas sesuai kartu pelajar',
        ])->assertStatus(500);
        expect((int) $customer->fresh()->balance)->toBe(0)->and($customer->fresh()->status)->toBe(Customer::STATUS_PENDING)
            ->and(Transaction::count())->toBe(0)->and(Accounting_Journal::count())->toBe(0);
    } finally {
        Event::forget('eloquent.creating: '.Accounting_Journal::class);
    }
});

test('new lifecycle forms render for admin and teller', function () {
    $customer = app(CustomerOnboarding::class)->register('9091001', 'Uji Aktivasi', 'X RPL 1');
    $this->actingAs(securityTestStaff('Administrator'))->get('/admin/nasabah')->assertOk()->assertSee('Nonaktif');
    $this->get('/admin/nasabah/tambah')->assertOk()->assertSee('saldo Rp0');
    $this->get('/admin/nasabah/import')->assertOk()->assertSee('setoran');
    $this->actingAs(securityTestStaff('Teller'))->get('/teller/transaksi?search='.$customer->student_id)
        ->assertOk()->assertSee('Aktifkan dan Catat Setoran Awal');
});

test('PHP upload failures explain the cause without importing customers', function (int $code, string $message) {
    $file = new UploadedFile('', 'nasabah.csv', 'text/csv', $code, true);
    $this->actingAs(securityTestStaff('Administrator'))->from('/admin/nasabah/import')
        ->post('/admin/nasabah/import', ['file' => $file])
        ->assertRedirect('/admin/nasabah/import')
        ->assertSessionHasErrors(['file' => $message.' (Kode upload: '.$code.')']);
    expect(Customer::count())->toBe(0);
})->with([
    [UPLOAD_ERR_NO_TMP_DIR, 'Folder sementara upload PHP tidak tersedia. Jalankan ulang server Antigravity setelah perbaikan TEMP/TMP, lalu pilih ulang CSV.'],
    [UPLOAD_ERR_CANT_WRITE, 'PHP tidak dapat menulis berkas sementara. Periksa izin tulis folder TEMP/TMP pada server.'],
    [UPLOAD_ERR_PARTIAL, 'Upload CSV terputus. Pilih ulang berkas dan coba lagi.'],
]);

test('Windows development server preserves temporary directory environment variables', function () {
    if (PHP_OS_FAMILY !== 'Windows') {
        $this->markTestSkipped('Windows development server only.');
    }
    expect(ServeCommand::$passthroughVariables)
        ->toContain('TEMP', 'TMP', 'TMPDIR');
});
