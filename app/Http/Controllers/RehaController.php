<?php

namespace App\Http\Controllers;

use App\Models\Donatur;
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

    public function indexData()
    {
        // $data = Reha::get();
        $query = Reha::leftJoin('pegawai as p', 'report_harian.pegawai_id', '=', 'p.id')
            ->from('report_harian')
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
                'report_harian.jenis_akad',
            ]);

        $data = $query->get();
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('action', function ($reha) {
                return view('reha.action', compact('reha'));
            })
            ->rawColumns(['action'])
            ->make(true);
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

        return view('reha.create', compact('title', 'action', 'redirectUrl', 'relawan', 'donatur_by_pegawai', 'role'));
    }

    public function store(Request $request)
    {
        request()->validate(
            [

                'pegawai_id' => 'required',
                'renku_donatur_lama' => 'required',
                'renku_donatur_baru' => 'required',
                'realisasi_donatur_lama' => 'required',
                'realisasi_donatur_baru' => 'required',
                'fu_donatur_lama' => 'required',
                'deal_donatur_lama' => 'required',
                'deal_donatur_baru' => 'required',
                'jenis_akad' => 'required',
            ],
            [
                'pegawai_id.required' => 'Pegawai wajib diisi',
                'renku_donatur_lama.required' => 'Renku Donatur Lama wajib diisi',
                'renku_donatur_baru.required' => 'Renku Donatur Baru wajib diisi',
                'realisasi_donatur_lama.required' => 'Realisasi Donatur Lama wajib diisi',
                'realisasi_donatur_baru.required' => 'Realisasi Donatur Baru wajib diisi',
                'fu_donatur_lama.required' => 'FU Donatur Lama wajib diisi',
                'deal_donatur_lama.required' => 'Deal Donatur Lama wajib diisi',
                'deal_donatur_baru.required' => 'Deal Donatur Baru wajib diisi',
                'jenis_akad.required' => 'Jenis Akad wajib diisi',
            ]
        );
        $pegawai_id = $request->input('pegawai_id');
        $input = $request->all();
        $input['pegawai_id'] = $pegawai_id;
        $input['tanggal'] = date('Y-m-d', strtotime($request->input('tanggal')));

        // Checklist donatur lama yang dikunjungi (array of donatur ids)
        $input['realisasi_donatur_lama_ids'] = json_encode($request->input('realisasi_donatur_lama_ids', []) ?: []);

        // Nominal deal hari ini
        $input['deal_donatur_lama_nominal'] = (int) str_replace(',', '', $request->input('deal_donatur_lama_nominal', 0) ?: 0);
        $input['deal_donatur_baru_nominal'] = (int) str_replace(',', '', $request->input('deal_donatur_baru_nominal', 0) ?: 0);

        // Ubah array jenis_akad menjadi JSON sebelum disimpan (optional now)
        if (isset($input['jenis_akad']) && is_array($input['jenis_akad'])) {
            $input['jenis_akad'] = json_encode($input['jenis_akad']);
        } else {
            unset($input['jenis_akad']);
        }

        Reha::create($input);

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

        $title = 'Show ' . $this->title;
        $action = '#';
        $show = 'disabled';
        $redirectUrl = $this->redirectUrl;

        $reha = Reha::get();
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
       

        return view('reha.show', compact('title', 'action', 'redirectUrl', 'show', 'relawan', 'reha'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
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

                'pegawai_id' => 'required',
                'renku_donatur_lama' => 'required',
                'renku_donatur_baru' => 'required',
                'realisasi_donatur_lama' => 'required',
                'realisasi_donatur_baru' => 'required',
                'fu_donatur_lama' => 'required',
                'deal_donatur_lama' => 'required',
                'deal_donatur_baru' => 'required',
                'jenis_akad' => 'required',
            ],
            [
                'pegawai_id.required' => 'Pegawai wajib diisi',
                'renku_donatur_lama.required' => 'Renku Donatur Lama wajib diisi',
                'renku_donatur_baru.required' => 'Renku Donatur Baru wajib diisi',
                'realisasi_donatur_lama.required' => 'Realisasi Donatur Lama wajib diisi',
                'realisasi_donatur_baru.required' => 'Realisasi Donatur Baru wajib diisi',
                'fu_donatur_lama.required' => 'FU Donatur Lama wajib diisi',
                'deal_donatur_lama.required' => 'Deal Donatur Lama wajib diisi',
                'deal_donatur_baru.required' => 'Deal Donatur Baru wajib diisi',
                'jenis_akad.required' => 'Jenis Akad wajib diisi',
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
