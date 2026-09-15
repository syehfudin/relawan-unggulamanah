<?php

namespace App\Http\Controllers;

use App\Models\Donatur;
use App\Services\GoogleSheetService;
use App\Jobs\SyncRehaToSheet;
use App\Models\Pegawai;
use DataTables;
use App\Models\Reha;
use App\Models\User;
use Auth;
use DB;
use Illuminate\Http\Request;

class RehaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct()
    {
        $this->middleware('permission:reha-list|reha-create|reha-edit|reha-delete', ['only' => ['index', 'show', 'indexData']]);
        $this->middleware('permission:reha-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:reha-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:reha-delete', ['only' => ['destroy']]);
        $this->title = 'Report Harian';
        $this->redirectUrl = route('reha.index');
    }
    public function index()
    {
        $title = $this->title;

        return view('reha.index', compact('title'));
    }

    public function indexData(Request $request)
    {
        $role = strtolower(Auth::user()->roles[0]->name);
        $pegawai_id = Auth::user()->pegawai_id;

        $query = Reha::leftJoin('pegawai as p', 'report_harian.pegawai_id', '=', 'p.id')
            ->select([
                'report_harian.id',
                'report_harian.tanggal',
                'p.nama as nama_relawan',
                'report_harian.renku_donatur_lama',
                'report_harian.renku_donatur_baru',
                'report_harian.realisasi_donatur_lama',
                'report_harian.realisasi_donatur_baru',
                'report_harian.fu_donatur_lama',
                'report_harian.fu_donatur_baru',
                'report_harian.deal_donatur_lama',
                'report_harian.deal_donatur_baru',
                'report_harian.deal_donatur_lama_nominal',
                'report_harian.deal_donatur_baru_nominal',
                'report_harian.deal_donatur_lama_programs',
                'report_harian.deal_donatur_baru_programs',
            ]);

        // Role-based access
        if ($role == 'relawan') {
            $query->where('report_harian.pegawai_id', $pegawai_id);
        } elseif ($role == 'supervisor') {
            $query->leftJoin('korel as k', function ($join) use ($pegawai_id) {
                $join->on('report_harian.pegawai_id', '=', 'k.bawahan_id');
                $join->orOn('report_harian.pegawai_id', '=', 'k.kepala_id', 'or');
            })
            ->where('k.kepala_id', $pegawai_id);
        }

        // Date filter
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        if ($dateFrom) {
            $query->where('report_harian.tanggal', '>=', date('Y-m-d', strtotime($dateFrom)));
        }
        if ($dateTo) {
            $query->where('report_harian.tanggal', '<=', date('Y-m-d', strtotime($dateTo)));
        }

        return Datatables::of($query)
            ->filter(function ($query) use ($request) {
                if ($request->has('search') && $search = $request->input('search.value')) {
                    $query->where('p.nama', 'ILIKE', "%{$search}%");
                }
            })
            ->addIndexColumn()
            ->orderColumn('DT_RowIndex', '-report_harian.id')
            ->addColumn('realisasi_summary', function ($reha) {
                return ($reha->realisasi_donatur_lama ?? 0) . ' / ' . ($reha->realisasi_donatur_baru ?? 0);
            })
            ->addColumn('donatur_baru_summary', function ($reha) {
                return 'Kunjungan: ' . ($reha->realisasi_donatur_baru ?? 0) .
                    '<br>Deal: ' . ($reha->deal_donatur_baru ?? 0) .
                    '<br>Rp ' . number_format($reha->deal_donatur_baru_nominal ?? 0, 0, ',', '.');
            })
            ->addColumn('donatur_lama_summary', function ($reha) {
                return 'Kunjungan: ' . ($reha->realisasi_donatur_lama ?? 0) .
                    '<br>Deal: ' . ($reha->deal_donatur_lama ?? 0) .
                    '<br>Rp ' . number_format($reha->deal_donatur_lama_nominal ?? 0, 0, ',', '.');
            })
            ->addColumn('deal_summary', function ($reha) {
                $dealLama = is_array($reha->deal_donatur_lama_programs) ? $reha->deal_donatur_lama_programs : [];
                $dealBaru = is_array($reha->deal_donatur_baru_programs) ? $reha->deal_donatur_baru_programs : [];

                if (empty($dealLama) && empty($dealBaru)) {
                    return '<span class="text-muted">-</span>';
                }

                $programs = DB::table('program')->orderBy('id', 'asc')->get();
                $nominalMap = [];
                foreach ($programs as $p) {
                    $nom = 0;
                    foreach ($dealLama as $row) {
                        if (($row['program_id'] ?? null) == $p->id) $nom += $row['nominal'];
                    }
                    foreach ($dealBaru as $row) {
                        if (($row['program_id'] ?? null) == $p->id) $nom += $row['nominal'];
                    }
                    if ($nom > 0) {
                        $nominalMap[] = e($p->nama) . ': Rp ' . number_format($nom, 0, ',', '.');
                    }
                }
                return $nominalMap ? implode('<br>', $nominalMap) : '<span class="text-muted">-</span>';
            })
            ->addColumn('action', function ($reha) {
                return view('reha.action', compact('reha'));
            })
            ->rawColumns(['action', 'donatur_baru_summary', 'donatur_lama_summary', 'deal_summary'])
            ->make(true);
    }

    // Helper: convert Y-m-d to dd-mm-yyyy (client-side equivalent)
    private function dateToDdMmYy($date)
    {
        return date('d-m-Y', strtotime($date));
    }

    public function create()
    {
        $title = 'Tambah ' . $this->title;
        $action = route('reha.store');
        $redirectUrl = $this->redirectUrl;
        $pegawai_id = Auth::user()->pegawai_id;
        $role = strtolower(Auth::user()->roles[0]->name);
        if ($role == 'relawan') {
            $relawan = Pegawai::where('id', $pegawai_id)->get();
        } else {
            $relawan = User::join('pegawai as p', 'users.pegawai_id', '=', 'p.id')
                ->join('model_has_roles as mhr', 'users.id', '=', 'mhr.model_id')
                ->join('roles as r', 'r.id', '=', 'mhr.role_id')
                ->where('r.name', 'Relawan')
                ->select([
                    'p.id',
                    'p.nama',
                    'p.default',
                ])
                ->get();
        }

        // Load donatur list per relawan (for realisasi donatur lama checklist)
        $donatur_by_pegawai = [];
        $donaturAll = Donatur::select(['id', 'nama', 'pegawai_id'])
            ->orderBy('nama', 'asc')
            ->get();
        foreach ($donaturAll as $dn) {
            $donatur_by_pegawai[$dn->pegawai_id][] = [
                'id' => $dn->id,
                'nama' => $dn->nama,
            ];
        }

        // Load program list for deal sections
        $program = DB::table('program')->select(['id', 'nama'])->orderBy('id', 'asc')->get();

        // Relawan with donatur only (for dropdown, relawan role sees self)
        if ($role != 'relawan') {
            $relawan = User::join('pegawai as p', 'users.pegawai_id', '=', 'p.id')
                ->join('model_has_roles as mhr', 'users.id', '=', 'mhr.model_id')
                ->join('roles as r', 'r.id', '=', 'mhr.role_id')
                ->where('r.name', 'Relawan')
                ->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('donatur')
                        ->whereColumn('donatur.pegawai_id', 'p.id');
                })
                ->select([
                    'p.id',
                    'p.nama',
                ])
                ->orderBy('p.nama', 'asc')
                ->distinct()
                ->get();
        }

        return view('reha.create', compact('title', 'action', 'redirectUrl', 'relawan', 'donatur_by_pegawai', 'role', 'program'));
    }

    public function store(Request $request)
    {
        request()->validate(
            [
                // Semua kolom boleh 0/kosong - hanya pegawai_id yang wajib
                'pegawai_id' => 'required',
            ],
            [
                'pegawai_id.required' => 'Pegawai wajib diisi',
            ]
        );
        $pegawai_id = $request->input('pegawai_id');
        $input = $request->all();
        $input['pegawai_id'] = $pegawai_id;
        $input['tanggal'] = date('Y-m-d', strtotime($request->input('tanggal')));

        // Default 0 untuk semua kolom angka (boleh kosong)
        $zeroFields = ['renku_donatur_lama', 'renku_donatur_baru', 'realisasi_donatur_baru', 'fu_donatur_lama', 'fu_donatur_baru', 'deal_donatur_lama', 'deal_donatur_baru'];
        foreach ($zeroFields as $zf) {
            $input[$zf] = (int) ($input[$zf] ?? 0);
        }

        // Checklist donatur lama yang dikunjungi (array of donatur ids)
        $checklistIds = $request->input('realisasi_donatur_lama_ids', []) ?: [];
        $input['realisasi_donatur_lama_ids'] = $checklistIds;
        // Realisasi donatur lama = jumlah dari checklist (auto)
        $input['realisasi_donatur_lama'] = count($checklistIds);

        // Deal program rows: [{program_id, nominal}, ...]
        $dealLamaPrograms = [];
        $dealLamaPids = $request->input('deal_lama_program_id', []);
        $dealLamaNominals = $request->input('deal_lama_nominal', []);
        $dealLamaTotal = 0;
        foreach ($dealLamaPids as $i => $pid) {
            $nom = (int) str_replace(',', '', $dealLamaNominals[$i] ?? 0);
            if ($pid && $nom > 0) {
                $dealLamaPrograms[] = ['program_id' => (int) $pid, 'nominal' => $nom];
                $dealLamaTotal += $nom;
            }
        }
        $input['deal_donatur_lama_programs'] = $dealLamaPrograms;
        $input['deal_donatur_lama_nominal'] = $dealLamaTotal;

        $dealBaruPrograms = [];
        $dealBaruPids = $request->input('deal_baru_program_id', []);
        $dealBaruNominals = $request->input('deal_baru_nominal', []);
        $dealBaruTotal = 0;
        foreach ($dealBaruPids as $i => $pid) {
            $nom = (int) str_replace(',', '', $dealBaruNominals[$i] ?? 0);
            if ($pid && $nom > 0) {
                $dealBaruPrograms[] = ['program_id' => (int) $pid, 'nominal' => $nom];
                $dealBaruTotal += $nom;
            }
        }
        $input['deal_donatur_baru_programs'] = $dealBaruPrograms;
        $input['deal_donatur_baru_nominal'] = $dealBaruTotal;

        // Ubah array jenis_akad menjadi JSON sebelum disimpan (optional now)
        if (isset($input['jenis_akad']) && is_array($input['jenis_akad'])) {
            $input['jenis_akad'] = json_encode($input['jenis_akad']);
        } else {
            unset($input['jenis_akad']);
        }

        $reha = Reha::create($input);

        // Sync to Google Sheet AFTER DB commit (non-blocking, error won't rollback DB)
        try {
            $googleService = new GoogleSheetService;
            $googleService->storeReha($reha->id);
        } catch (\Exception $e) {
            SyncRehaToSheet::dispatch($reha->id);
            \Log::warning('Google Sheet sync queued for retry: reha #' . $reha->id . ': ' . $e->getMessage());
        }

        return redirect()->route('reha.index')
            ->with('success', ucfirst('Tambah ' . $this->title . ' Berhasil'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function show($id)
    {
        $title = 'Detail ' . $this->title;
        $redirectUrl = $this->redirectUrl;
        $show = 'disabled';

        $reha = Reha::leftJoin('pegawai as p', 'report_harian.pegawai_id', '=', 'p.id')
            ->select(['report_harian.*', 'p.nama as nama_relawan'])
            ->where('report_harian.id', $id)
            ->first();

        $programs = DB::table('program')->orderBy('id', 'asc')->get();

        return view('reha.show', compact('title', 'redirectUrl', 'show', 'reha', 'programs'));
    }
    public function edit($id)
    {
        $reha = Reha::find($id);
        $relawan = User::join('pegawai as p', 'users.pegawai_id', '=', 'p.id')
            ->join('model_has_roles as mhr', 'users.id', '=', 'mhr.model_id')
            ->join('roles as r', 'r.id', '=', 'mhr.role_id')
            ->where('r.name', 'Relawan')
            ->select([
                'p.id',
                'p.nama',
                'p.default',
            ])
            ->get();
        $redirectUrl = $this->redirectUrl;
        $title = 'Ubah ' . $this->title;
        $action = route('reha.update', $id);


        return view('reha.edit', compact('title', 'action', 'redirectUrl', 'reha', 'relawan'));
    }

    public function update(Request $request, $id)
    {
        request()->validate(
            [
                // Semua kolom boleh 0/kosong - hanya pegawai_id yang wajib
                'pegawai_id' => 'required',
            ],
            [
                'pegawai_id.required' => 'Pegawai wajib diisi',
            ]
        );

        $pegawai_id = $request->input('pegawai_id');
        $input = $request->all();
        $input['pegawai_id'] = $pegawai_id;
        $input['tanggal'] = date('Y-m-d', strtotime($request->input('tanggal')));
        Reha::find($id)
            ->update($input);

        return redirect()->route('reha.index')
            ->with('success', ucfirst('Ubah ' . $this->title . ' Berhasil'));
    }

    public function destroy($id)
    {
        Reha::find($id)->delete();
        return redirect()->route('reha.index')
            ->with('success', ucfirst('Hapus ' . $this->title . ' berhasil'));
    }
}
