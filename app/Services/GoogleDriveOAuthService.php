<?php

namespace App\Services;

use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Google\Service\Drive\Permission;
use Illuminate\Support\Facades\Storage;

class GoogleDriveOAuthService
{
    protected Drive $drive;

    public function __construct(string $type = 'cipta')
    {
        if (!Storage::disk('local')->exists('google_drive_token.json')) {
            throw new \Exception('Akun Google belum terhubung.');
        }

        $token = json_decode(
            Storage::disk('local')->get('google_drive_token.json'),
            true
        );

        if (!$token || !is_array($token)) {
            throw new \Exception('Token Google tidak valid.');
        }

        $client = new Client();
        if ($type === 'cipta') {
            $client->setClientId(config('services.google_cipta.client_id'));
        } else {
            $client->setClientId(config('services.google_paten.client_id'));
        }
        $client->setClientSecret(config('services.google_cipta.client_secret'));
        $client->setRedirectUri(config('services.google_cipta.redirect'));
        $client->setAccessType('offline');
        $client->setAccessToken($token);
        if ($client->isAccessTokenExpired()) {
            $refreshToken = $client->getRefreshToken() ?: ($token['refresh_token'] ?? null);

            if (!$refreshToken) {
                throw new \Exception('Refresh token Google tidak tersedia. Silakan hubungkan ulang akun Google.');
            }

            $newToken = $client->fetchAccessTokenWithRefreshToken($refreshToken);

            if (isset($newToken['error'])) {
                throw new \Exception('Gagal me-refresh token Google. Silakan hubungkan ulang akun Google.');
            }

            $merged = array_merge($token, $newToken);

            if (!isset($merged['refresh_token'])) {
                $merged['refresh_token'] = $refreshToken;
            }

            Storage::disk('local')->put(
                'google_drive_token.json',
                json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );

            $client->setAccessToken($merged);
        }

        $this->drive = new Drive($client);
    }

    public function uploadFile(string $absolutePath, string $fileName): string
    {
        $fileMetadata = new DriveFile([
            'name' => $fileName,
        ]);

        $createdFile = $this->drive->files->create($fileMetadata, [
            'data' => file_get_contents($absolutePath),
            'mimeType' => mime_content_type($absolutePath),
            'uploadType' => 'multipart',
            'fields' => 'id',
        ]);

        $permission = new Permission([
            'type' => 'anyone',
            'role' => 'reader',
        ]);

        $this->drive->permissions->create($createdFile->id, $permission);

        $file = $this->drive->files->get($createdFile->id, [
            'fields' => 'webViewLink',
        ]);

        return (string) ($file->webViewLink ?? '');
    }
}