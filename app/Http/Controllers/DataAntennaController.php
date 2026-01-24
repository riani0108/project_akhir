<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\DataAntenna;
use App\Models\DataTower;

class DataAntennaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('data-antenna.index', [
            'title' => 'Data Antenna',
            'menu' => 'data-antenna',
            'datas' => DataAntenna::all()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $data_tower = DataTower::all();
        return view('data-antenna.create', [
            'title' => 'Data Antenna',
            'menu' => 'data-antenna',
            'data_tower' => $data_tower,

        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $jenis_antenna = DB::table('data_antenna')->where('jenis_antenna', $request->jenis_antenna)->value('jenis_antenna');

        $simpan = DataAntenna::create($request->all());

        if ($simpan) {
            return redirect()->route('data-antenna.index')->with('success', 'Data Antenna Berhasil Disimpan');
        } else {
            return redirect()->back()->with('error', 'Gagal menyimpan Data Antenna');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $data_antenna = DataAntenna::find($id);

        if (!$data_antenna) {
            return redirect()->route('data-antenna.index')->with('error', 'Data Antenna tidak ditemukan');
        }

        $data_tower = DataTower::all();

        return view('data-antenna.edit', [
            'title' => 'Edit Data Antenna',
            'menu' => 'data-antenna',
            'data_tower' => $data_tower,
            'data' => $data_antenna
        ]);
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {

        $data = DataAntenna::find($id);

        $data->fill($request->only([
            'jenis_antenna',
            'id_nama_tower',
            'link_tower',
        ]));

        $data->save();
        return redirect()->route('data-antenna.index')->with('success', 'Data Antenna' . $request->id_nama_tower . 'Berhasil Diupdate');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        DataAntenna::find($id)->delete();

        return redirect()->route('data-antenna.index')->with('success', 'Data Antenna Berhasil Dihapus');
    }

    public function all()
    {
        $antenna = DataAntenna::with(['data_tower_from', 'data_tower_to'])->get();
        return $antenna;
    }
}
