<?php

namespace App\Http\Controllers;

use App\Models\DataTower;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class DataTowerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = DataTower::query();

        if ($request->has('search') && $request->search != '') {
            $query->where('nama_tower', 'LIKE', '%' . $request->search . '%');
        }

        $datas = $query->get();


        $data_tower = DataTower::all();
        return view('data-tower.index', [
            'title' => 'Data Tower',
            'menu' => 'data-tower',
            'datas' => $query->get(),
            'data_tower' => $data_tower
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('data-tower.create', [
            'title' => 'Add Data Tower',
            'menu' => 'data-tower',
            'datas' => DataTower::all(),

        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $nama_tower = DB::table('data_tower')->where('nama_tower', $request->nama_tower)->value('nama_tower');

        if ($nama_tower) {
            return redirect()->route('data-tower.create')->with('error', 'Nama Tower' . $request->nama_tower .  'Data Tower Sudah Ada')
                ->withInput();
        } else {
            $data = $request->only([
                'nama_tower',
                'alamat_tower',
                'tinggi_tower',
                'latitude',
                'longitude',
                'keterangan'
            ]);

            $simpan = DataTower::create($data);

            if ($simpan) {
                return redirect()->route('data-tower.index')->with('success', 'Data Tower Berhasil Disimpan');
            } else {
                return redirect()->back()->with('error', 'Gagal menyimpan Data Tower');
            }
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
    public function edit($id)
    {
        $nama_tower = DataTower::find($id);
        return view('data-tower.edit', compact('nama_tower'), [
            'title' => 'Edit Tower',
            'menu' => 'data-tower',
            'data' => $nama_tower
        ]);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $existingDataTower = DB::table('data_tower')
            ->where('nama_tower', '=', $request->nama_tower)
            ->where('id', '!=', $id)->exists();

        if ($existingDataTower) {
            return redirect()->route('data-tower.edit', $id)
                ->with('success', 'Nama Tower' . $request->nama_tower .  'Data Tower Sudah Ada')
                ->withInput();
        }

        $data = DataTower::find($id);

        $data->fill($request->only([
            'nama_tower',
            'alamat_tower',
            'tinggi_tower',
            'latitude',
            'longitude',
            'keterangan'
        ]));

        $data->save();
        return redirect()->route('data-tower.index')->with('success', 'Data Tower' . $request->nama_tower . 'Berhasil Diupdate');
    }

    /**
     * Remove the specifie resource from storage.
     */
    public function destroy(string $id)
    {
        DataTower::find($id)->delete();

        return redirect()->route('data-tower.index')->with('success', 'Data Tower Berhasil Dihapus');
    }

    public function all()
    {
        return DataTower::all();
    }
}
