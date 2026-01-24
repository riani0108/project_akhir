<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InputLokasi;
use Illuminate\Support\Facades\DB;


class InputLokasiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('input-lokasi.index', [
            'title' => 'Input Lokasi',
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('input-lokasi.create');
    }

    /**
     * Simpan data lokasi tower ke database
     */
    public function store(Request $request)
    {
        $nama = DB::table('input_lokasi')->where('nama', $request->nama_tower)->value('nama');

        if ($nama) {
            return redirect()->route('input-lokasi.create')->with('error', 'Nama Tower' . $request->nama .  'Data Tower Sudah Ada')
                ->withInput();
        } else {
            $data = $request->only([
                'nama',
                'tinggi_tower',
                'alamat',
                'koordinat',
            ]);

            $simpan = InputLokasi::create($data);

            if ($simpan) {
                return redirect()->route('hitung')->with('success', 'Data Tower Berhasil Di Input');
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
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    // InputLokasiController.php
    public function all()
    {
        return InputLokasi::all();
        // return response()->json(InputLokasi::all());
    }
}
