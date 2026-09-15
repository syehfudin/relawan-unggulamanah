<?php

namespace App\Services;

use App\Models\Program;
use App\Models\Setoran;
use App\Models\SetoranDetail;
use App\Models\Transaksi;
use App\Models\Reha;
use App\Models\User;
use DB;
use Sheets;
use Log;

class GoogleSheetService
{
    /**
     * Safely append data to Google Sheet with error logging.
     * Re-throws exception so caller can handle (controller wraps in try/catch).
     */
    private function safeAppend(string $sheetName, array $data): void
    {
        try {
            Sheets::spreadsheet(config('google.spread_sheet_id'))->sheet($sheetName)->append($data, 'USER_ENTERED');
        } catch (\Exception $e) {
            Log::error('GoogleSheet append failed', [
                'sheet' => $sheetName,
                'rows' => count($data),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Append Report Harian (Reha) to Google Sheet 'report_harian' sheet.
     * Columns: Tanggal | Nama Relawan | Renku Lama | Renku Baru | Realisasi Lama | Realisasi Baru | FU Lama | FU Baru | Deal Lama | Deal Baru | [per-program nominals]
     */
    public function storeReha($rehaId)
    {
        $reha = Reha::findOrFail($rehaId);
        $relawan = DB::table('pegawai')->where('id', $reha->pegawai_id)->value('nama');
        $programs = Program::orderBy('id', 'asc')->get();

        // Build program nominal map from deal programs JSONB
        $dealLama = $reha->deal_donatur_lama_programs ?? [];
        $dealBaru = $reha->deal_donatur_baru_programs ?? [];

        $nominalByProgram = [];
        foreach ($programs as $p) {
            $nominalLama = 0;
            $nominalBaru = 0;
            foreach ($dealLama as $row) {
                if ($row['program_id'] == $p->id) {
                    $nominalLama += $row['nominal'];
                }
            }
            foreach ($dealBaru as $row) {
                $row['program_id'] = $row['program_id'] ?? $row['program_id'] ?? null;
            }
            foreach ($dealBaru as $row) {
                if ($row['program_id'] == $p->id) {
                    $nominalBaru += $row['nominal'];
                }
            }
            $nominalByPegawai = null;
            // One column per program: "lama_nominal | baru_nominal" combined? No - single nominal column per program (sum both)
            $nominalByProgram[$p->id] = $nominalLama + $nominalBaru;
        }

        $row = [];
        $row[] = date('d-m-Y', strtotime($reha->tanggal));
        $row[] = $relawan;
        $row[] = $reha->renku_donatur_lama ?? 0;
        $row[] = $reha->renku_donatur_baru ?? 0;
        $row[] = $reha->realisasi_donatur_lama ?? 0;
        $row[] = $reha->realisasi_donatur_baru ?? 0;
        $row[] = $reha->fu_donatur_lama ?? 0;
        $row[] = $reha->fu_donatur_baru ?? 0;
        $row[] = $reha->deal_donatur_lama ?? 0;
        $row[] = $reha->deal_donatur_baru ?? 0;
        foreach ($nominalByProgram as $nominal) {
            $row[] = $nominal;
        }

        $this->safeAppend('report_harian', [$row]);
    }

    public function storeSheet()
    {
        $transaksi = Transaksi::leftJoin('transaksi_detail as td', 'transaksi.id', '=', 'td.transaksi_id')
            ->leftJoin('pegawai as p', 'transaksi.pegawai_id', '=', 'p.id')
            ->leftJoin('donatur as d', 'transaksi.donatur_id', '=', 'd.id')
            ->select([
                'transaksi.tanggal',
                'p.nama as relawan',
                'd.nama as donatur',
                'd.alamat',
                'd.no_telepon',
                'transaksi.jenis_transaksi',
                'transaksi.keterangan',
                DB::raw('json_agg(td.*) as donasi'),
            ])
            ->groupBy([
                'transaksi.tanggal',
                'p.nama',
                'd.nama',
                'd.alamat',
                'd.no_telepon',
                'transaksi.keterangan',
                'transaksi.jenis_transaksi',
            ])
            ->orderBy('transaksi.tanggal', 'asc')
            ->get();

        $program = Program::orderBy('id', 'asc')->get();
        $data = [];
        $header = [];
        $header[] = 'Tanggal';
        $header[] = 'Relawan';
        $header[] = 'Donatur';
        $header[] = 'Alamat Donatur';
        $header[] = 'Nomor HP Donatur';
        foreach ($program as $row) {
            $header[] = $row->nama;
        }
        $header[] = 'Keterangan';
        $header[] = 'Jenis Pembayaran';
        $data[] = $header;

        foreach ($transaksi as $item) {
            $list = [];
            $list[] = date('m-d-Y', strtotime($item->tanggal));
            $list[] = $item->relawan;
            $list[] = $item->donatur;
            $list[] = isset($item->alamat) ? $item->alamat : '';
            $list[] = isset($item->no_telepon) ? $item->no_telepon : '';
            $donasi = json_decode($item->donasi);
            $donasiProgram = array_reduce($donasi, function ($acc, $d) {
                $acc[$d->program_id] = $d;

                return $acc;
            }, []);
            foreach ($program as $row) {
                $list[] = isset($donasiProgram[$row->id]) ? $donasiProgram[$row->id]->nominal_donasi : 0;
            }
            $list[] = isset($item->keterangan) ? $item->keterangan : '';
            $jenis_pembayaran = $item->jenis_transaksi;
            if ($jenis_pembayaran == 'cash') {
                $list[] = 'Titip di Relawan';
            } else {
                $list[] = 'Transfer ke Rek ULAMA';
            }
            $data[] = $list;
        }

        $this->safeAppend('report', $data);
    }

    public function storeTransaksi($idTransaksi, $donasi)
    {
        $transaksi = Transaksi::leftJoin('pegawai as p', 'transaksi.pegawai_id', '=', 'p.id')
            ->leftJoin('donatur as d', 'transaksi.donatur_id', '=', 'd.id')
            ->select([
                'transaksi.tanggal',
                'p.nama as relawan',
                'd.nama as donatur',
                'd.alamat',
                'd.no_telepon',
                'transaksi.keterangan',
                'transaksi.jenis_transaksi',
            ])
            ->where('transaksi.id', $idTransaksi)
            ->first();

        $program = Program::orderBy('id', 'asc')->get();

        $list[] = date('d-m-Y', strtotime($transaksi->tanggal));
        $list[] = $transaksi->relawan;
        $list[] = $transaksi->donatur;
        $list[] = isset($transaksi->alamat) ? $transaksi->alamat : '';
        $list[] = isset($transaksi->no_telepon) ? $transaksi->no_telepon : '';

        foreach ($program as $row) {
            $list[] = isset($donasi[$row->id]) ? $donasi[$row->id] : 0;
        }
        $list[] = isset($transaksi->keterangan) ? $transaksi->keterangan : '';
        $jenis_pembayaran = $transaksi->jenis_transaksi;
        if ($jenis_pembayaran == 'cash') {
            $list[] = 'Titip di Relawan';
        } else {
            $list[] = 'Transfer ke Rek ULAMA';
        }
        $data[] = $list;
        $this->safeAppend('report', $data);
    }

    public function firstStore()
    {
        $setor = Setoran::leftJoin('file as f', 'setoran.file_id', '=', 'f.id')
            ->leftJoin('pegawai as p', 'setoran.pegawai_id', '=', 'p.id')
            ->select([
                'setoran.created_at',
                'setoran.id',
                'p.nama as relawan',
                'f.path',
                'f.nama as nama_file',
                'setoran.total_setoran',
            ])
            ->get();
        $data = [];
        foreach ($setor as $item) {
            $header = ['Tanggal', 'Nama Relawan', 'Total Setoran', 'Bukti Setoran'];
            $dataSetor = [];
            $dataSetor[] = date('d-m-Y', strtotime($item->created_at));
            $dataSetor[] = $item->relawan;
            $dataSetor[] = $item->total_setoran;
            $dataSetor[] = asset($item->path.$item->nama_file);
            $data[] = $header;
            $data[] = $dataSetor;
            $detail = SetoranDetail::leftJoin('transaksi as t', 'setoran_detail.transaksi_id', '=', 't.id')
                ->leftJoin('transaksi_detail as td', 't.id', '=', 'td.transaksi_id')
                ->leftJoin('donatur as d', 't.donatur_id', '=', 'd.id')
                ->where('setoran_detail.setoran_id', $item->id)
                ->select([
                    't.tanggal',
                    'd.nama as donatur',
                    'd.alamat',
                    'd.no_telepon',
                    DB::raw('sum(nominal_donasi) as total_donasi'),
                ])
                ->groupBy([
                    't.tanggal',
                    'd.nama',
                    'd.alamat',
                    'd.no_telepon',
                ])->get();
            foreach ($detail as $row) {
                $detailSetor = [];
                $detailSetor[] = date('d-m-Y', strtotime($row->tanggal));
                $detailSetor[] = $item->relawan;
                $detailSetor[] = $row->donatur;
                $detailSetor[] = $row->alamat;
                $detailSetor[] = $row->no_telepon;
                $detailSetor[] = $row->total_donasi;
                $data[] = $detailSetor;
            }
        }
        $this->safeAppend('setoran', $data);
    }

    public function storeSetoran($setor_id)
    {
        $setor = Setoran::leftJoin('file as f', 'setoran.file_id', '=', 'f.id')
            ->leftJoin('pegawai as p', 'setoran.pegawai_id', '=', 'p.id')
            ->where('setoran.id', $setor_id)
            ->select([
                'p.nama as relawan',
                'f.path',
                'f.nama as nama_file',
                'setoran.total_setoran',
            ])
            ->first();
        $detail = SetoranDetail::leftJoin('transaksi as t', 'setoran_detail.transaksi_id', '=', 't.id')
            ->leftJoin('transaksi_detail as td', 't.id', '=', 'td.transaksi_id')
            ->leftJoin('donatur as d', 't.donatur_id', '=', 'd.id')
            ->where('setoran_detail.setoran_id', $setor_id)
            ->select([
                't.tanggal',
                'd.nama as donatur',
                'd.alamat',
                'd.no_telepon',
                DB::raw('sum(nominal_donasi) as total_donasi'),
            ])
            ->groupBy([
                't.tanggal',
                'd.nama',
                'd.alamat',
                'd.no_telepon',
            ])->get();
        $data = [];
        $header = [];
        $header[] = 'Tanggal';
        $header[] = 'Nama Relawan';
        $header[] = 'Total Setoran';
        $header[] = 'Bukti Setoran';
        $data[] = $header;
        $dataSetor = [];
        $dataSetor[] = date('d-m-Y');
        $dataSetor[] = $setor->relawan;
        $dataSetor[] = $setor->total_setoran;
        $dataSetor[] = asset($setor->path.$setor->nama_file);
        $data[] = $dataSetor;
        foreach ($detail as $item) {
            $detailSetor = [];
            $detailSetor[] = date('d-m-Y', strtotime($item->tanggal));
            $detailSetor[] = $setor->relawan;
            $detailSetor[] = $item->donatur;
            $detailSetor[] = isset($item->alamat) ? $item->alamat : '';
            $detailSetor[] = isset($item->no_telepon) ? $item->no_telepon : '';
            $detailSetor[] = $item->total_donasi;
            $data[] = $detailSetor;
        }
        $this->safeAppend('setoran', $data);
    }
}
