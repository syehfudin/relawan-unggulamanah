@extends('layouts.app')
@push('custom-css-files')
<style>
    .detail-info {
        background-color: #f8f9fc;
        border-radius: 0.5rem;
        padding: 1rem 1.25rem;
        margin-bottom: 1.25rem;
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
    .trx-list {
        max-height: 420px;
        overflow-y: auto;
        border: 1px solid #e3e6f0;
        border-radius: 0.5rem;
    }
    .trx-item {
        display: flex;
        align-items: center;
        padding: 0.65rem 1rem;
        border-bottom: 1px solid #e3e6f0;
        cursor: pointer;
        transition: background-color 0.15s;
    }
    .trx-item:hover {
        background-color: #f8f9fc;
    }
    .trx-item.selected {
        background-color: #eef2ff;
    }
    .trx-item:last-child {
        border-bottom: none;
    }
    .trx-item .trx-check {
        margin-right: 0.9rem;
        transform: scale(1.3);
        cursor: pointer;
    }
    .trx-item .trx-date {
        min-width: 100px;
        color: #6c757d;
        font-weight: 600;
    }
    .trx-item .trx-name {
        flex: 1;
        font-weight: 600;
        color: #1f2937;
    }
    .trx-item .trx-nominal {
        min-width: 130px;
        text-align: right;
        font-weight: 600;
        font-variant-numeric: tabular-nums;
        color: #4f46e5;
    }
    .total-box {
        background-color: #eef2ff;
        border: 2px solid #4f46e5;
        border-radius: 0.5rem;
        padding: 0.75rem 1.25rem;
        display: inline-flex;
        align-items: center;
        gap: 1rem;
    }
    .total-box .label {
        font-size: 0.8rem;
        text-transform: uppercase;
        color: #6c757d;
        font-weight: 700;
    }
    .total-box .value {
        font-weight: 700;
        font-size: 1.25rem;
        color: #4f46e5;
        font-variant-numeric: tabular-nums;
    }
    .trx-empty {
        padding: 1.5rem;
        text-align: center;
        color: #9ca3af;
    }
</style>
@endpush
@section('content')
<div class="container-fluid p-0">
    <div class="row">
        <div class="col-12 col-lg-12">
            <form action="{{ $action }}" method="POST" autocomplete="off" enctype="multipart/form-data" id="form-setoran">
                @csrf
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Form {{ $title }}</h3>
                    </div>
                    <div class="card-body">
                        <!-- 1. Nama Relawan -->
                        @if(strtolower($role) != 'relawan')
                        <div class="mb-3">
                            <label class="fs-6 fw-bold mb-2">
                                <span class="required">Nama Relawan</span>
                            </label>
                            <select class="form-control" name="pegawai_id" id="pegawai_id">
                                @foreach($relawan as $item)
                                    <option value="{{ $item->id }}">{{ $item->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        @else
                            <input type="hidden" name="pegawai_id" id="pegawai_id" value="{{ $pegawai_id }}">
                        @endif

                        <!-- 2. List transaksi belum setoran (checkboxes) -->
                        <div class="mb-3">
                            <label class="fs-6 fw-bold mb-2">
                                <span class="required">Transaksi Belum Disetor</span>
                                <span class="text-muted fw-normal">(pilih transaksi yang disetorkan)</span>
                            </label>
                            <div class="trx-list" id="trx-list">
                                <div class="trx-empty">Pilih Relawan dahulu...</div>
                            </div>
                        </div>

                        <!-- 3. Jumlah (Total Setor) -->
                        <div class="mb-3">
                            <span class="detail-label">Jumlah (Total Setor)</span>
                            <input type="hidden" name="total_setor" id="total_setor_hidden" value="0">
                            <div class="total-box" id="total_box">
                                <span class="label">Total</span>
                                <span class="value" id="total_setor_display">Rp 0</span>
                            </div>
                        </div>

                        <!-- 4. Upload bukti -->
                        <div class="mb-3">
                            <label class="fs-6 fw-bold mb-2">
                                <span class="required">Upload File Bukti Setoran</span>
                            </label>
                            <input
                                type="file"
                                name="file"
                                id="inputImage"
                                accept="image/*"
                                class="form-control @error('file') is-invalid @enderror">
                            @error('file')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
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
@endsection
@push('custom-js-files')
<script type="text/javascript">
    // Server-rendered per-relawan transaksi map (no AJAX needed)
    let transaksiByPegawai = {!! $transaksi_by_pegawai ? json_encode($transaksi_by_pegawai) : '{}' !!};
    let selectedPegawai = null;

    let formatRupiah = (num) => {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(num);
    };

    // Render transaksi list for selected relawan
    let renderTransaksiList = function (pegawaiId) {
        let $container = $("#trx-list");
        let items = transaksiByPegawai[pegawaiId] || [];

        $container.empty();

        if (items.length === 0) {
            $container.append('<div class="trx-empty">Tidak ada transaksi yang belum disetor untuk relawan ini.</div>');
            $("#total_setor_hidden").val(0);
            $("#total_setor_display").text(formatRupiah(0));
            return;
        }

        $.each(items, function (i, item) {
            let row = $(
                '<div class="trx-item" data-id="' + item.id + '" data-nominal="' + item.total_donasi + '">' +
                    '<input type="checkbox" name="transaksi_id[]" class="trx-check" value="' + item.id + '" data-nominal="' + item.total_donasi + '" id="trx_' + item.id + '">' +
                    '<label class="trx-check-label d-flex align-items-center flex-grow-1 mb-0" for="trx_' + item.id + '" style="cursor:pointer">' +
                        '<span class="trx-date">' + item.tanggal + '</span>' +
                        '<span class="trx-name">' + item.nama_donatur + '</span>' +
                        '<span class="trx-nominal">' + formatRupiah(item.total_donasi) + '</span>' +
                    '</label>' +
                '</div>'
            );
            $container.append(row);

            // Direct binding on this row's checkbox (in addition to delegated)
            row.find('.trx-check').on('change', function () {
                $(this).closest('.trx-item').toggleClass('selected', $(this).is(':checked'));
                updateTotal();
            });
        });

        updateTotal();
    };

    // Update total from checked items
    let updateTotal = function () {
        let total = 0;
        $(".trx-check:checked").each(function () {
            total += parseInt($(this).data('nominal')) || 0;
        });

        $("#total_setor_hidden").val(total);
        $("#total_setor_display").text(formatRupiah(total));
    };

    // Checkbox change (delegated)
    $(document).on('change', '.trx-check', function () {
        console.log('Checkbox changed:', this.id, 'checked:', $(this).is(':checked'));
        $(this).closest('.trx-item').toggleClass('selected', $(this).is(':checked'));
        updateTotal();
    });

    // Relawan switch: render list
    $("#pegawai_id").on('change', function () {
        selectedPegawai = $(this).val();
        renderTransaksiList(selectedPegawai);
    });

    // Initial render
    let initPegawai = $("#pegawai_id").val();
    if (initPegawai) {
        renderTransaksiList(initPegawai);
    }

    // Submit guard
    $("#form-setoran").on('submit', function (e) {
        let total = parseInt($("#total_setor_hidden").val()) || 0;
        let hasFile = $("#inputImage").val();
        if (total === 0) {
            e.preventDefault();
            alert('Pilih minimal satu transaksi untuk disetor.');
            return false;
        }
        if (!hasFile) {
            e.preventDefault();
            alert('Upload file bukti setoran terlebih dahulu.');
            return false;
        }
        // Disable submit to prevent double submit
        $("#btn_submit").prop('disabled', true).text('Menyimpan...');
        // Show total as formatted number for backend parse
        $("#total_setor_hidden").val(total);
        return true;
    });
</script>
@endpush