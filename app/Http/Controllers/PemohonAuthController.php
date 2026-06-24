<?php



namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Pemohon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PemohonAuthController extends Controller

{
    public function showLogin(Request $request)
    {
        $emailInventor1 = null;

        if ($request->has('kode')) {
            $kode = $request->kode;

            $row = DB::table('paten_verifs')->where('no_pendaftaran', $kode)->orderByDesc('id')->first()
                ?? DB::table('hak_cipta_verifs')->where('no_pendaftaran', $kode)->orderByDesc('id')->first();

            if ($row) {
                $emailInventor1 = $this->getOwnerEmailFromRow($row);
            }
        }

        return view('pemohon.login', compact('emailInventor1'));
    }
    // ========= helper: parse email string jadi array email unik =========
    private function parseEmails(?string $raw): array
    {
        if (!$raw) return [];
        $parts = preg_split('/[,\s;]+/', $raw, -1, PREG_SPLIT_NO_EMPTY);
        $emails = [];
        foreach ($parts as $e) {
            $e = strtolower(trim($e));
            if (filter_var($e, FILTER_VALIDATE_EMAIL)) {

                $emails[] = $e;

            }

        }
        return array_values(array_unique($emails));

    }

    // ========= helper: gabungkan email utama + inventors (kalau ada) =========
    private function collectAllEmailsFromRow($row): array
    {
        $emails = [];
        // 1) email utama
        $emails = array_merge($emails, $this->parseEmails($row->email ?? null));

        // 2) inventors JSON (opsional)
        $invRaw = $row->inventors ?? null;
        if (!empty($invRaw)) {
            $invArr = is_string($invRaw) ? json_decode($invRaw, true) : $invRaw;
            if (is_array($invArr)) {
                foreach ($invArr as $inv) {
                    if (!is_array($inv)) continue;
                    $emails = array_merge($emails, $this->parseEmails($inv['email'] ?? null));
                }
            }
        }
        return array_values(array_unique(array_filter($emails)));
    }

    public function claim($kode)
{
    $isPaten = DB::table('paten_verifs')->where('no_pendaftaran', $kode)->exists();
    $isCipta = DB::table('hak_cipta_verifs')->where('no_pendaftaran', $kode)->exists();

    if (!$isPaten && !$isCipta) {
        return redirect()->route('pemohon.login.form')
            ->with('error', 'Kode tidak valid.');
    }

    $row = $isPaten
        ? DB::table('paten_verifs')->where('no_pendaftaran', $kode)->orderByDesc('id')->first()
        : DB::table('hak_cipta_verifs')->where('no_pendaftaran', $kode)->orderByDesc('id')->first();

    if (!$row) {
        return redirect()->route('pemohon.login.form')
            ->with('error', 'Data pengajuan tidak ditemukan.');
    }

    $pemohon = Pemohon::where('kode_unik', $kode)->first();

    if (!$pemohon) {
        return redirect()->route('pemohon.login.form')
            ->with('error', 'Akun belum dibuat. Silakan submit verifikasi terlebih dahulu.');
    }

    return redirect()->route('pemohon.login.form')
        ->with('success', 'Silakan login menggunakan kredensial yang sudah dikirim ke email Anda.');
}

public function sendCredentialAfterSubmit(string $kode): array
{
    $isPaten = DB::table('paten_verifs')->where('no_pendaftaran', $kode)->exists();
    $isCipta = DB::table('hak_cipta_verifs')->where('no_pendaftaran', $kode)->exists();

    if (!$isPaten && !$isCipta) {
        return ['ok' => false, 'message' => 'Kode tidak valid.'];
    }

    $row = $isPaten
        ? DB::table('paten_verifs')->where('no_pendaftaran', $kode)->orderByDesc('id')->first()
        : DB::table('hak_cipta_verifs')->where('no_pendaftaran', $kode)->orderByDesc('id')->first();

    if (!$row) {
        return ['ok' => false, 'message' => 'Data pengajuan tidak ditemukan.'];
    }

    $emails = $this->collectAllEmailsFromRow($row);

    if (count($emails) === 0) {
        return ['ok' => false, 'message' => 'Email pemohon/inventor kosong atau tidak valid.'];
    }

    $pemohon = Pemohon::where('kode_unik', $kode)->first();

    if ($pemohon) {
        return ['ok' => true, 'message' => 'Akun sudah ada, email tidak dikirim ulang.'];
    }

    $plainPassword = Str::random(10);

    $pemohon = Pemohon::create([
        'kode_unik' => $kode,
        'password'  => Hash::make($plainPassword),
    ]);

    try {
        $to  = $emails[0];
        $bcc = array_slice($emails, 1);

        $mailable = new \App\Mail\PemohonCredentialMail(
            username: $kode,
            password: $plainPassword,
            kodePengajuan: $kode
        );

        $mailer = Mail::to($to);
        if (count($bcc) > 0) {
            $mailer->bcc($bcc);
        }
        $mailer->send($mailable);

        Log::info('Credential sent after submit final', [
            'kode' => $kode,
            'to'   => $to,
            'bcc'  => $bcc,
        ]);

        return ['ok' => true, 'message' => 'Username & password sudah dikirim ke email.'];
    } catch (\Throwable $e) {
        Log::error('Gagal kirim credential pemohon setelah submit final', [
            'kode'   => $kode,
            'emails' => $emails,
            'err'    => $e->getMessage(),
        ]);

        try { $pemohon->delete(); } catch (\Throwable $t) {}

        return ['ok' => false, 'message' => 'Gagal mengirim email kredensial.'];
    }
}
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);
        $pemohon = Pemohon::where('kode_unik', $request->username)->first();
        if (!$pemohon) {
            return back()->withErrors([
                'username' => 'Akun belum dibuat. Klik Login dari halaman hasil pengajuan.'
            ])->withInput();
        }

        if (Auth::guard('pemohon')->attempt([
            'kode_unik' => $request->username,
            'password'  => $request->password,
        ])) {
            $request->session()->regenerate();
            return redirect()->route('pemohon.dashboard');
        }
        return back()->withErrors([
            'password' => 'Password atau username salah'
        ])->withInput();
    }

    public function logout(Request $request)
    {
        Auth::guard('pemohon')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('pemohon.login.form');

    }

    public function storePreChangePassword(Request $request)
    {
        $request->validate([
            'username'          => ['required'],
            'owner_email'       => ['required', 'email'],
            'current_password'  => ['required'],
            'new_password'      => ['required', 'min:8', 'confirmed'],
        ]);

        $kode = trim($request->username);
        $pemohon = Pemohon::where('kode_unik', $kode)->first();
        if (!$pemohon) {
            return redirect()->route('pemohon.login.form')
                ->with('error', 'Akun tidak ditemukan.');

        }

        if (!Hash::check($request->current_password, $pemohon->password)) {
            return redirect()->route('pemohon.login.form')
                ->with('error', 'Password lama salah.');

        }

        $row = DB::table('paten_verifs')->where('no_pendaftaran', $kode)->orderByDesc('id')->first()
            ?? DB::table('hak_cipta_verifs')->where('no_pendaftaran', $kode)->orderByDesc('id')->first();

        if (!$row) {
            return redirect()->route('pemohon.login.form')
                ->with('error', 'Data pengajuan tidak ditemukan.');
        }

        $inputEmail = strtolower(trim($request->owner_email));
        $ownerEmail = $this->getOwnerEmailFromRow($row);

        if (!$ownerEmail || $inputEmail !== $ownerEmail) {
            return redirect()->route('pemohon.login.form')
                ->with('error', 'Email pemohon utama tidak sesuai. Hanya pemohon utama yang bisa mengganti password.');

        }

        // ganti password limit 1 bulan sekali
        if (!empty($pemohon->last_password_changed_at)) {
            $nextAllowed = \Carbon\Carbon::parse($pemohon->last_password_changed_at)->addMonth();
            if (now()->lt($nextAllowed)) {
                return redirect()->route('pemohon.login.form')
                    ->with('error', 'Password hanya dapat diganti 1 bulan sekali. Dapat di lakukan kembali setelah: '.$nextAllowed->format('d M Y'));

            }

        }

        // ambil semua email kontributor
        $emails = $this->collectAllEmailsFromRow($row);
        if (count($emails) === 0) {
            return redirect()->route('pemohon.login.form')
                ->with('error', 'Email kontributor tidak ditemukan.');

        }

        // update password
        $pemohon->password = Hash::make($request->new_password);
        $pemohon->last_password_changed_at = now();
        $pemohon->save();

        // kirim password baru ke semua email 
        try {
            $to  = $emails[0];
            $bcc = array_slice($emails, 1);
            $mailable = new \App\Mail\PemohonCredentialMail(
                username: $kode,
                password: $request->new_password,
                kodePengajuan: $kode,
                type: 'changed'
            );

            $mailer = Mail::to($to);
            if (!empty($bcc)) $mailer->bcc($bcc);
            $mailer->send($mailable);

        } catch (\Throwable $e) {
            Log::error('Gagal kirim email password baru', [
                'kode' => $kode,
                'emails' => $emails,
                'err' => $e->getMessage(),

            ]);

            return redirect()->route('pemohon.login.form')
                ->with('error', 'Password berhasil diubah, tapi gagal mengirim email. Coba lagi atau hubungi admin.');

        }

        return redirect()->route('pemohon.login.form')
            ->with('success', 'Password berhasil diganti & dikirim ke semua email kontributor.');

    }

    private function getOwnerEmailFromRow($row): ?string
    {
        // pemohon utama = inventor pertama
        $invRaw = $row->inventors ?? null;
        if (!empty($invRaw)) {
            $invArr = is_string($invRaw) ? json_decode($invRaw, true) : $invRaw;
            if (is_array($invArr) && isset($invArr[0]) && is_array($invArr[0])) {
                $first = strtolower(trim($invArr[0]['email'] ?? ''));
                if (filter_var($first, FILTER_VALIDATE_EMAIL)) {
                    return $first;
                }
            }
        }
        $emails = $this->parseEmails($row->email ?? null);
        return $emails[0] ?? null;

    }
    public function getOwnerEmail(Request $request)
{
    $kode = trim((string) $request->query('kode'));

    if ($kode === '') {
        return response()->json([
            'ok' => false,
            'email' => null,
            'message' => 'Kode unik kosong.'
        ], 422);
    }

    $row = DB::table('paten_verifs')
        ->where('no_pendaftaran', $kode)
        ->orderByDesc('id')
        ->first()
        ?? DB::table('hak_cipta_verifs')
            ->where('no_pendaftaran', $kode)
            ->orderByDesc('id')
            ->first();

    if (!$row) {
        return response()->json([
            'ok' => false,
            'email' => null,
            'message' => 'Data pengajuan tidak ditemukan.'
        ], 404);
    }

    return response()->json([
        'ok' => true,
        'email' => $this->getOwnerEmailFromRow($row),
    ]);
}
}