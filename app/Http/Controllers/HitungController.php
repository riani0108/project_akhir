<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;

class HitungController extends Controller
{
    public function index()
    {
        $pelangganId = auth('pelanggan')->id();

        $project = Project::where('pelanggan_id', $pelangganId)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('hitung.index', [
            'title'   => 'Hitung',
            'menu'    => 'hitung',
            'project' => $project
        ]);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
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
}
