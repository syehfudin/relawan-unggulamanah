@extends('layouts.app')
@push('custom-css-files')
<style>
    .detail-info {
        background-color: #f8f9fc;
        border-radius: 0.5rem;
        padding: 1rem 1.25rem;
        margin-bottom: 1.25rem;
    }
    .detail-item {
        display: flex;
        flex-wrap: wrap;
        gap: 2rem;
    }
    .detail-item > div {
        min-width: 200px;
    }
    .detail-label {
        font-size: 0.8rem;
        text-transform: uppercase;
        color: #6c757d;
        font-weight: 700;
        margin-bottom: 0.25rem;
        display: block;
    }
    .detail-value {
        font-weight: 600;
        color: #1f2937;
    }
    .section-title {
        background-color: #eef2ff;
        border-left: 4px solid #4f46e5;
        padding: 0.6rem 1rem;
        font-weight: 700;
        color: #3730a3;
        border-radius: 0.25rem;
        margin-bottom: 1rem;
    }
    .deal-section .section-title {
        background-color: #fef3c7;
        border-left-color: #f59e0b;
        color: #92400e;
    }
    .program-table {
        width: 100%;
        border-collapse: collapse;
    }
    .program-table th,
    .program-table td {
        padding: 0.6rem 1rem;
        border-bottom: 1px solid #e3e6f0;
    }
    .program-table thead th {
        background-color: #f8f9fc;
        font-size: 0.85rem;
        text-transform: uppercase;
        color: #6c757d;
    }
    .program-badge {
        display: inline-flex;
        align-items: center;
        background-color: #eef2ff;
        color: #4338ca;
        border: 1px solid #c7d2fe;
        border-radius: 50rem;
        padding: 0.3rem 0.9rem;
        font-weight: 600;
        font-size: 0.875rem;
    }
</style>
@endpush
@section('content')
@php
    $dealLama = is_array($reha->deal_donatur_lama_programs) ? $reha->deal_donatur_lama_programs : [];
    $dealBaru = is_array($reha->deal_donatur_baru_programs) ? $reha->deal_donatur_baru_programs : [];
    $visitIds = is_array($reha->realisasi_donatur_lama_ids) ? $reha->realisasi_donatur_lama_ids : [];
    $visitCount = count($visitIds);
@endphp
<div class="container-fluid p-0">
    <div class="row">
        <div class="col-12 col-lg-12">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Detail {{ $title }}</h3>
                </div>
                <div class="card-body">
                    <!-- Header info -->
                    <div class="detail-info">
                        <div class="detail-item">
                            <div>
                                <span class="detail-label">Tanggal</span>
                                <span class="detail-value">
                                    @if($reha->tanggal) {{ date('d-m-Y', strtotime($reha->tanggal)) }} @else - @endif
                                </span>
                            </div>
                            <div>
                                <span class="detail-label">Nama Relawan</span>
                                <span class="detail-value">{{ $reha->nama_relawan }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- RENCANA KUNJUNGAN -->
                    <div class="section-title">RENCANA KUNJUNGAN</div>
                    <div class="detail-item mb-3">
                        <div>
                            <span class="detail-label">Donatur Lama</span>
                            <span class="detail-value">{{ $reha->renku_donatur_lama ?? 0 }}</span>
                        </div>
                        <div>
                            <span class="detail-label">Donatur Baru</span>
                            <span class="detail-value">{{ $reha->renku_donatur_baru ?? 0 }}</span>
                        </div>
                    </div>

                    <!-- REALISASI KUNJUNGAN -->
                    <div class="section-title">REALISASI KUNJUNGAN</div>
                    <div class="detail-item mb-2">
                        <div>
                            <span class="detail-label">Donatur Lama</span>
                            <span class="detail-value">{{ $reha->realisasi_donatur_lama ?? 0 }}</span>
                        </div>
                        <div>
                            <span class="detail-label">Donatur Baru</span>
                            <span class="detail-value">{{ $reha->realisasi_donatur_baru ?? 0 }}</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex align-items-center mb-2">
                            <label class="fw-bold mb-0 mr-2">Donatur Lama yang Dikunjungi</label>
                            <span class="donatur-counter ml-2">
                                Dikunjungi: <span class="count">{{ $visitCount }}</span>
                            </span>
                        </div>
                        @if($visitCount > 0)
                        <div class="trx-list" style="max-height:260px;overflow-y:auto;border:1px solid #e3e6f0;border-radius:0.5rem">
                            @foreach($visitIds as $vId)
                                @php $dn = DB::table('donatur')->where('id', $vId)->first(); @endphp
                                <div class="donatur-item">
                                    <span class="donatur-name">{{ $dn ? $dn->nama : '#' . $vId }}</span>
                                </div>
                            @endforeach
                        </div>
                        @else
                        <span class="text-muted">Tidak ada checklist</span>
                        @endif
                    </div>

                    <!-- RENCANA FOLLOW UP -->
                    <div class="section-title">RENCANA FOLLOW UP</div>
                    <div class="detail-item mb-3">
                        <div>
                            <span class="detail-label">Donatur Lama</span>
                            <span class="detail-value">{{ $reha->fu_donatur_lama ?? 0 }}</span>
                        </div>
                        <div>
                            <span class="detail-label">Donatur Baru</span>
                            <span class="detail-value">{{ $reha->fu_donatur_baru ?? 0 }}</span>
                        </div>
                    </div>

                    <!-- DEAL HARI INI -->
                    <div class="section-title deal-section">DEAL HARI INI</div>
                    <div class="detail-item mb-2">
                        <div>
                            <span class="detail-label">Donatur Lama</span>
                            <span class="detail-value">{{ $reha->deal_donatur_lama ?? 0 }}</span>
                        </div>
                        <div>
                            <span class="detail-label">Donatur Baru</span>
                            <span class="detail-value">{{ $reha->deal_donatur_baru ?? 0 }}</span>
                        </div>
                    </div>
                    <table class="program-table">
                        <thead>
                            <tr>
                                <th style="width:50px" class="text-center">No</th>
                                <th>Nama Program</th>
                                <th style="width:220px" class="text-right">Nominal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($programs as $p)
                                @php
                                    $nom = 0;
                                    foreach ($dealLama as $row) { if (($row['program_id'] ?? null) == $p->id) $nom += $row['nominal']; }
                                    foreach ($dealBaru as $row) { if (($row['program_id'] ?? null) == $p->id) $nom += $row['nominal']; }
                                @endphp
                                @if($nom > 0)
                                <tr>
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td><span class="program-badge">{{ $p->nama }}</span></td>
                                    <td class="text-right" style="font-weight:600">Rp {{ number_format($nom, 0, ',', '.') }}</td>
                                </tr>
                                @endif
                            @empty
                                <tr><td colspan="3" class="text-center text-muted">Tidak ada deal</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer">
                    <a class="btn btn-primary" href="{{ $redirectUrl }}"> <i class="fas fa-arrow-left"></i> Back</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection