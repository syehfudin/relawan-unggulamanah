@extends('layouts.app')
@push('custom-css-files')
<style>
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
    .trx-list {
        max-height: 320px;
        overflow-y: auto;
        border: 1px solid #e3e6f0;
        border-radius: 0.5rem;
    }
    .donatur-item {
        display: flex;
        align-items: center;
        padding: 0.55rem 1rem;
        border-bottom: 1px solid #e3e6f0;
        cursor: pointer;
        transition: background-color 0.15s;
    }
    .donatur-item:hover {
        background-color: #f8f9fc;
    }
    .donatur-item.selected {
        background-color: #eef2ff;
    }
    .donatur-item:last-child {
        border-bottom: none;
    }
    .donatur-item .donatur-check {
        margin-right: 0.9rem;
        transform: scale(1.25);
        cursor: pointer;
    }
    .donatur-item .donatur-name {
        flex: 1;
        font-weight: 600;
        color: #1f2937;
    }
    .donatur-counter {
        display: inline-flex;
        align-items: center;
        background-color: #eef2ff;
        color: #4338ca;
        border: 1px solid #c7d2fe;
        border-radius: 50rem;
        padding: 0.3rem 0.9rem;
        font-weight: 700;
        font-size: 0.875rem;
    }
    .donatur-counter .count {
        font-size: 1.05rem;
    }
</style>
@endpush
@section('content')
@php
    $myPegawaiId = Auth::user()->pegawai_id;
@endphp
<div class="container-fluid p-0">
    <div class="row">
        <div class="col-12 col-lg-12">
            <form action="{{ $action }}" method="POST" autocomplete="off" id="form-reha">
                @csrf
                @method('POST')
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Form {{ $title }}</h3>
                    </div>
                    <div class="card-body">
                        <!-- Tanggal + Relawan -->
                        <div class="row mb-3">
                            <div class="col-12 col-md-4">
                                <label class="fs-6 fw-bold mb-2">Tanggal</label>
                                <input type="text" class="form-control" id="datepicker" name="tanggal"
                                    value="{{ date('d-m-Y') }}" autocomplete="off">
                            </div>
                            @if ($role != 'relawan')
                            <div class="col-12 col-md-8">
                                <label class="fs-6 fw-bold mb-2">
                                    <span class="required">Nama Relawan</span>
                                </label>
                                <select class="form-control" name="pegawai_id" id="pegawai_id">
                                    @foreach ($relawan as $item)
                                        <option value="{{ $item->id }}">{{ $item->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @else
                                <input type="hidden" name="pegawai_id" id="pegawai_id" value="{{ $pegawai_id }}">
                            @endif
                        </div>

                        <!-- 1. RENCANA KUNJUNGAN -->
                        <div class="section-title">RENCANA KUNJUNGAN</div>
                        <div class="row mb-3">
                            <div class="col-12 col-md-6">
                                <label>Donatur Lama</label>
                                <input type="number" min="0" class="form-control" name="renku_donatur_lama" value="{{ @$reha->renku_donatur_lama ?? 0 }}">
                            </div>
                            <div class="col-12 col-md-6">
                                <label>Donatur Baru</label>
                                <input type="number" min="0" class="form-control" name="renku_donatur_baru" value="{{ @$reha->renku_donatur_baru ?? 0 }}">
                            </div>
                        </div>

                        <!-- 2. REALISASI KUNJUNGAN -->
                        <div class="section-title">REALISASI KUNJUNGAN</div>
                        <div class="row mb-3">
                            <div class="col-12 col-md-6">
                                <label>Donatur Lama <span class="text-muted fw-normal">(otomatis dari checklist)</span></label>
                                <input type="number" min="0" class="form-control" name="realisasi_donatur_lama" id="realisasi_donatur_lama" value="{{ @$reha->realisasi_donatur_lama ?? 0 }}" readonly style="background-color:#eef2ff;font-weight:600">
                            </div>
                            <div class="col-12 col-md-6">
                                <label>Donatur Baru</label>
                                <input type="number" min="0" class="form-control" name="realisasi_donatur_baru" value="{{ @$reha->realisasi_donatur_baru ?? 0 }}">
                            </div>
                        </div>
                        <!-- Donatur Lama checklist -->
                        <div class="mb-3">
                            <div class="d-flex align-items-center mb-2">
                                <label class="fw-bold mb-0 mr-2">Checklist Donatur Lama yang Dikunjungi</label>
                                <span class="donatur-counter ml-2">
                                    Dikunjungi: <span class="count" id="visit_counter">0</span>
                                </span>
                            </div>
                            <div class="mb-2">
                                <input type="text" class="form-control" id="donatur_search" placeholder="Cari nama donatur..." autocomplete="off">
                            </div>
                            <div class="trx-list" id="donatur-list">
                                <div class="donatur-item text-muted">Pilih Relawan dahulu...</div>
                            </div>
                        </div>

                        <!-- 3. RENCANA FOLLOW UP -->
                        <div class="section-title">RENCANA FOLLOW UP</div>
                        <div class="row mb-3">
                            <div class="col-12 col-md-6">
                                <label>Donatur Lama</label>
                                <input type="number" min="0" class="form-control" name="fu_donatur_lama" value="{{ @$reha->fu_donatur_lama ?? 0 }}">
                            </div>
                            <div class="col-12 col-md-6">
                                <label>Donatur Baru</label>
                                <input type="number" min="0" class="form-control" name="fu_donatur_baru" value="{{ @$reha->fu_donatur_baru ?? 0 }}">
                            </div>
                        </div>

                        <div class="row mb-2 deal-section">
                            <div class="col-12 col-md-6">
                                <label class="fw-bold">Donatur Lama - Jumlah Deal</label>
                                <input type="number" min="0" class="form-control" name="deal_donatur_lama" id="deal_donatur_lama" value="{{ @$reha->deal_donatur_lama ?? 0 }}">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="fw-bold">Donatur Baru - Jumlah Deal</label>
                                <input type="number" min="0" class="form-control" name="deal_donatur_baru" id="deal_donatur_baru" value="{{ @$reha->deal_donatur_baru ?? 0 }}">
                            </div>
                        </div>

                        <!-- Donatur Lama: program rows -->
                        <div class="mb-3">
                            <div class="d-flex align-items-center mb-2">
                                <label class="fw-bold mb-0 mr-2">Donatur Lama - Detail Program & Nominal</label>
                                <span class="donatur-counter ml-2">Total: <span class="count" id="deal_lama_total">Rp 0</span></span>
                            </div>
                            <div id="deal_lama_rows"></div>
                            <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="add_deal_lama">
                                <i class="fas fa-plus"></i> Tambahkan Program
                            </button>
                        </div>

                        <!-- Donatur Baru: program rows -->
                        <div class="mb-3">
                            <div class="d-flex align-items-center mb-2">
                                <label class="fw-bold mb-0 mr-2">Donatur Baru - Detail Program & Nominal</label>
                                <span class="donatur-counter ml-2">Total: <span class="count" id="deal_baru_total">Rp 0</span></span>
                            </div>
                            <div id="deal_baru_rows"></div>
                            <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="add_deal_baru">
                                <i class="fas fa-plus"></i> Tambahkan Program
                            </button>
                        </div>

                    <div class="card-footer">
                        <div class="float-right">
                            <button type="submit" class="btn btn-primary" id="btn_submit">Simpan</button>
                        </div>
                        <a class="btn btn-primary" href="{{ $redirectUrl }}"> <i class="fas fa-arrow-left"></i> Back</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Deal Hari Ini > 0 -->
<div class="modal fade" id="dealModal" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title"><i class="fas fa-exclamation-circle"></i> Deal Hari Ini!</h5>
            </div>
            <div class="modal-body text-center">
                <p class="h5 font-weight-bold">Segera Inputkan Hasil Deal Hari Ini</p>
                <p class="text-muted">Catat transaksi deal melalui menu <strong>Transaksi</strong> agar nominal tercatat di sistem.</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-primary" data-dismiss="modal" id="btn_deal_go">Saya Sudah Paham</button>
            </div>
        </div>
    </div>
</div>
@endsection
@push('custom-js-files')
<script type="text/javascript">
    $(document).ready(function() {
        // Server-rendered donatur per relawan
        let donaturByPegawai = {!! $donatur_by_pegawai ? json_encode($donatur_by_pegawai) : '{}' !!};

        $("#datepicker").datepicker({ dateFormat: 'dd-mm-yy' });

        let formatNum = (num) => {
            return new Intl.NumberFormat('id-ID').format(num);
        };

        // Render donatur checklist for selected relawan
        let renderDonaturList = function (pegawaiId) {
            let $container = $("#donatur-list");
            let items = donaturByPegawai[pegawaiId] || [];

            $container.empty();

            if (items.length === 0) {
                $container.append('<div class="donatur-item text-muted">Tidak ada donatur untuk relawan ini.</div>');
                $("#visit_counter").text(0);
                return;
            }

            $.each(items, function (i, item) {
                let row = $(
                    '<div class="donatur-item" data-name="' + item.nama.toLowerCase() + '">' +
                        '<input type="checkbox" name="realisasi_donatur_lama_ids[]" class="donatur-check" value="' + item.id + '" id="dntr_' + item.id + '">' +
                        '<label class="d-flex align-items-center flex-grow-1 mb-0" for="dntr_' + item.id + '" style="cursor:pointer">' +
                            '<span class="donatur-name">' + item.nama + '</span>' +
                        '</label>' +
                    '</div>'
                );
                $container.append(row);
            });

            updateVisitCounter();
        };

        // Update counter
        let updateVisitCounter = function () {
            let count = $("#donatur-list .donatur-check:checked").length;
            $("#visit_counter").text(count);
            // Auto-sync realisasi donatur lama = checklist count
            $("#realisasi_donatur_lama").val(count);
        };

        // Checklist toggle (delegated)
        $(document).on('change', '.donatur-check', function () {
            $(this).closest('.donatur-item').toggleClass('selected', $(this).is(':checked'));
            updateVisitCounter();
        });

        // Search donatur (filters visible items)
        $("#donatur_search").on('keyup', function () {
            let term = $(this).val().toLowerCase();
            $("#donatur-list .donatur-item").each(function () {
                let name = $(this).data('name') || '';
                $(this).toggle(name.indexOf(term) > -1);
            });
        });

        // Relawan switch: render list + clear checklist
        $("#pegawai_id").on('change', function () {
            renderDonaturList($(this).val());
        });

        // Initial render
        let initPegawai = $("#pegawai_id").val();
        if (initPegawai) {
            renderDonaturList(initPegawai);
        }

        // Deal modal: popup when deal donatur lama/baru > 0
        let checkDeal = function () {
            let dealLama = parseInt($("#deal_donatur_lama").val()) || 0;
            let dealBaru = parseInt($("#deal_donatur_baru").val()) || 0;
            if (dealLama > 0 || dealBaru > 0) {
                $("#dealModal").modal('show');
            }
        };

        $("#deal_donatur_lama, #deal_donatur_baru").on('change', checkDeal);

        // ===== Deal Program Rows (max 10 per type) =====
        const MAX_DEAL_ROWS = 10;

        const PROGRAM_OPTIONS = '{!! $program->map(function ($p) { return "<option value='" . $p->id . "'>" . e($p->nama) . "</option>"; })->implode("") !!}';

        let addDealRow = function (type) {
            let containerId = '#deal_' + type + '_rows';
            let rows = $(containerId + ' .deal-row').length;
            if (rows >= MAX_DEAL_ROWS) {
                alert('Maksimal 10 program untuk deal hari ini.');
                return;
            }

            let html =
                '<div class="deal-row row align-items-center mb-2">' +
                    '<div class="col-5">' +
                        '<select class="form-control" name="deal_' + type + '_program_id[]">' +
                            '<option value="">Pilih Program...</option>' +
                            PROGRAM_OPTIONS +
                        '</select>' +
                    '</div>' +
                    '<div class="col-5">' +
                        '<input type="number" min="0" class="form-control" placeholder="Nominal (Rp)" name="deal_' + type + '_nominal[]" oninput="updateDealTotal(\'' + type + '\')">' +
                    '</div>' +
                    '<div class="col-2">' +
                        '<button type="button" class="btn btn-outline-danger btn-sm remove-deal-row">X</button>' +
                    '</div>' +
                '</div>';
            $(containerId).append(html);
        };

        $(document).on('click', '#add_deal_lama', function () { addDealRow('lama'); });
        $(document).on('click', '#add_deal_baru', function () { addDealRow('baru'); });

        $(document).on('click', '.remove-deal-row', function () {
            let type = $(this).closest('.deal-row').data('type');
            $(this).closest('.deal-row').remove();
            updateDealTotal(type);
        });

        let updateDealTotal = function (type) {
            let total = 0;
            $("#deal_" + type + "_rows .deal-row").each(function () {
                let nom = parseInt($(this).find('input[name="deal_' + type + '_nominal[]"]').val()) || 0;
                total += nom;
            });
            $("#deal_" + type + "_total").text(formatNum(total));
        };

        window.updateDealTotal = updateDealTotal;

        // Prevent double submit
        $("#form-reha, #form-setoran").on('submit', function () {
            $("#btn_submit").prop('disabled', true).text('Menyimpan...');
        });
    });
</script>
@endpush