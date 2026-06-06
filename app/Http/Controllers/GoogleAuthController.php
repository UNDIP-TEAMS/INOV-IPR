<?php

namespace App\Http\Controllers;

use Google\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GoogleAuthController extends Controller
{
    protected function makeClient(): Client
    {
        $client = new Client();
        $client->setClientId(config('services.google_cipta.client_id'));
        $client->setClientSecret(config('services.google_cipta.client_secret'));
        $client->setRedirectUri(config('services.google_cipta.redirect'));
        $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        $client->setScopes([
            \Google\Service\Drive::DRIVE_FILE,
        ]);

        return $client;
    }

    public function redirect()
    {
        $client = $this->makeClient();
        return redirect()->away($client->createAuthUrl());
    }

    public function callback(Request $request)
    {
        $client = $this->makeClient();

        if (!$request->has('code')) {
            return redirect()->route('pemohon.dashboard')
                ->with('error', 'Kode otorisasi Google tidak ditemukan.');
        }

        $token = $client->fetchAccessTokenWithAuthCode($request->get('code'));

        if (isset($token['error'])) {
            return redirect()->route('pemohon.dashboard')
                ->with('error', 'Gagal login Google.');
        }

        Storage::disk('local')->put(
            'google_drive_token.json',
            json_encode($token, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        return redirect()->route('pemohon.dashboard')
            ->with('success', 'Akun Google berhasil terhubung.');
    }
}