@extends('layouts.app')

@section('title', 'Pendaftaran Berhasil')

@section('content')
<main style="
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(180deg, #f8fafc 0%, #eef3fb 100%);
    padding: 24px;
    font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
">
    <div style="
        width: 100%;
        max-width: 760px;
        background: #ffffff;
        border: 1px solid #e6edf7;
        border-radius: 24px;
        box-shadow: 0 12px 30px rgba(52, 76, 123, 0.08);
        padding: 40px 36px 32px;
    ">
        <div style="display:flex; justify-content:center; margin-bottom:24px;">
            <div style="
                width: 96px;
                height: 96px;
                border-radius: 999px;
                background: #f4f8ff;
                border: 1.5px solid #d7e3f7;
                display:flex;
                align-items:center;
                justify-content:center;
                box-shadow: 0 8px 22px rgba(52, 76, 123, 0.08);
            ">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path
                        d="M6 12.5L10 16.5L18 8.5"
                        stroke="#344c7b"
                        stroke-width="2.8"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                </svg>
            </div>
        </div>

        <h1 style="
            margin: 0;
            text-align: center;
            font-size: 34px;
            line-height: 1.2;
            font-weight: 800;
            color: #1f2a44;
            letter-spacing: -0.02em;
        ">
            Pendaftaran berhasil
        </h1>

        <p style="
            margin: 14px auto 28px;
            max-width: 560px;
            text-align: center;
            color: #5f6f8a;
            font-size: 17px;
            line-height: 1.7;
            font-weight: 500;
        ">
            Pengajuan <strong style="color:#344c7b;">{{ strtoupper($type) }}</strong> kamu sudah berhasil dikirim dan tercatat di sistem.
        </p>

        <div style="
            border: 1px solid #e4ebf5;
            border-radius: 18px;
            padding: 20px 24px;
            background: #f9fbff;
            margin-bottom: 28px;
        ">
            <div style="
                font-size: 13px;
                font-weight: 700;
                letter-spacing: .06em;
                text-transform: uppercase;
                color: #6c7c96;
                margin-bottom: 14px;
            ">
                Detail Pengajuan
            </div>

            <table style="
                width: 100%;
                border-collapse: collapse;
                font-size: 16px;
            ">
                <tbody>
                    <tr>
                        <td style="
                            width: 180px;
                            padding: 14px 0;
                            color: #44536b;
                            font-weight: 600;
                            vertical-align: top;
                        ">
                            No. Pengajuan
                        </td>
                        <td style="
                            width: 20px;
                            padding: 14px 0;
                            color: #94a3b8;
                            font-weight: 600;
                            vertical-align: top;
                        ">
                            :
                        </td>
                        <td style="
                            padding: 14px 0;
                            color: #1f2a44;
                            font-weight: 800;
                            word-break: break-word;
                        ">
                            {{ $no_pendaftaran }}
                        </td>
                    </tr>
                    <tr>
                        <td style="
                            padding: 14px 0 0;
                            color: #44536b;
                            font-weight: 600;
                            vertical-align: top;
                            border-top: 1px solid #e4ebf5;
                        ">
                            Judul
                        </td>
                        <td style="
                            padding: 14px 0 0;
                            color: #94a3b8;
                            font-weight: 600;
                            vertical-align: top;
                            border-top: 1px solid #e4ebf5;
                        ">
                            :
                        </td>
                        <td style="
                            padding: 14px 0 0;
                            color: #1f2a44;
                            font-weight: 700;
                            word-break: break-word;
                            border-top: 1px solid #e4ebf5;
                        ">
                            {{ $judul }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div style="
            display: flex;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
        ">
            <a href="{{ route('home') }}"
               style="
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    min-width: 210px;
                    height: 50px;
                    padding: 0 20px;
                    border-radius: 14px;
                    background: #344c7b;
                    color: #ffffff;
                    text-decoration: none;
                    font-weight: 700;
                    font-size: 16px;
               ">
                Ke Landing Page
            </a>

            <a href="{{ route('pemohon.dashboard') }}"
               style="
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    min-width: 210px;
                    height: 50px;
                    padding: 0 20px;
                    border-radius: 14px;
                    background: #edf3fb;
                    border: 1px solid #d7e3f7;
                    color: #344c7b;
                    text-decoration: none;
                    font-weight: 700;
                    font-size: 16px;
               ">
                Kembali ke Dashboard
            </a>
        </div>
    </div>
</main>
@endsection