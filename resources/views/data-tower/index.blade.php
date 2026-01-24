@extends ('be.master')
@section ('sidebar')
@include ('be.sidebar')
@endsection

@section ('navbar')
@include ('be.navbar')
@endsection

@section ('content')
<main class="content-wrapper">
    <div class="container-fluid py-4">
        <div class="row g-4">
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-header pb-0">
                        <div class="row mb-5">
                            <div class="col-auto me-auto mb-4 font-weight-bold h4">
                                {{$title}}
                            </div>

                            <div class="col-auto">
                                <a href="{{route('data-tower.create')}}" class="btn m-2 justify-content-end" style="background-color: #2f271d; color: #ffe4b5;">
                                    <i class="fas fa-solid fa-plus"></i> Add New Data</a>
                            </div>
                        </div>

                        <form action="{{ route('data-tower.index') }}" method="GET" class="mb-4">
                            <div class="input-group">
                                <input type="text" name="search" class="form-control"
                                    placeholder="Cari nama tower..."
                                    value="{{ request('search') }}">
                                <button type="submit" class="btn">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </form>



                        <div class="table-responsive">
                            <table class="table text-center">
                                <thead>
                                    <tr>
                                        <th scope="col">Action</th>
                                        <th scope="col">No.</th>
                                        <th scope="col">Nama Tower</th>
                                        <th scope="col">Alamat Tower</th>
                                        <th scope="col">Tinggi Tower (m)</th>
                                        <th scope="col">Latitude</th>
                                        <th scope="col">longitude</th>
                                        <th scope="col">keterangan</th>
                                    </tr>
                                </thead>

                                <tbody id="data-tower-body">
                                    @if($datas->isEmpty())
                                    <tr>
                                        <td colspan="8" class="text-center text-danger">
                                            Data tidak ditemukan.
                                        </td>
                                    </tr>
                                    @endif

                                    @foreach ($datas as $no => $data)
                                    <tr>
                                        <td>
                                            <a href="{{route('data-tower.edit', $data->id)}}" class="btn btn-warning">Edit</a>
                                            <form action="{{route('data-tower.destroy', $data->id)}}" method="POST" style="display: inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn btn-danger btn-delete" data-id="{{ $data->id }}" data-url="{{ route('data-tower.destroy', $data->id) }}"> Delete
                                                </button>
                                            </form>
                                        </td>

                                        <th scope="row">{{ $no + 1 }}.</th>

                                        <td>{{strlen($data['nama_tower']) > 100 ? substr($data['nama_tower'], 0, 100) . '...' : $data['nama_tower']}}</td>
                                        <td>{{strlen($data['alamat_tower']) > 100 ? substr($data['alamat_tower'], 0, 100) . '...' : $data['alamat_tower']}}</td>
                                        <td>{{strlen($data['tinggi_tower']) > 100 ? substr($data['tinggi_tower'], 0, 100) . '...' : $data['tinggi_tower']}}</td>
                                        <td>{{strlen($data['latitude']) > 100 ? substr($data['latitude'], 0, 100) . '...' : $data['latitude']}}</td>
                                        <td>{{strlen($data['longitude']) > 100 ? substr($data['longitude'], 0, 100) . '...' : $data['longitude']}}</td>
                                        <td>{{strlen($data['keterangan']) > 100 ? substr($data['keterangan'], 0, 100) . '...' : $data['keterangan']}}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('search-tower').addEventListener('keyup', function() {
            let query = this.value;

            fetch("{{ route('data-tower.index') }}?search=" + query)
                .then(response => response.text())
                .then(html => {
                    let parser = new DOMParser();
                    let doc = parser.parseFromString(html, "text/html");

                    let newTbody = doc.querySelector("#data-tower-body");

                    if (newTbody) {
                        document.querySelector("#data-tower-body").innerHTML = newTbody.innerHTML;
                    }
                });
        });
    </script>


    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const deleteButtons = document.querySelectorAll('.btn-delete');

            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const url = this.dataset.url;

                    Swal.fire({
                        title: 'Yakin ingin menghapus?',
                        text: 'Data akan hilang permanen!',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Ya, hapus!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const form = document.createElement('form');
                            form.method = 'POST';
                            form.action = url;

                            const csrf = document.createElement('input');
                            csrf.type = 'hidden';
                            csrf.name = '_token';
                            csrf.value = '{{ csrf_token() }}';

                            const method = document.createElement('input');
                            method.type = 'hidden';
                            method.name = '_method';
                            method.value = 'DELETE';

                            form.appendChild(csrf);
                            form.appendChild(method);
                            document.body.appendChild(form);
                            form.submit();
                        }
                    });
                });
            });
        });
    </script>

    @if(session('success'))
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: '{{ session("success") }}',
            confirmButtonColor: '#3085d6',
            confirmButtonText: 'OK'
        });
    </script>
    @endif

    <!-- Table End -->
    @endsection