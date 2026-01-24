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
                                <a href="{{route('data-antenna.create')}}" class="btn m-2 justify-content-end" style="background-color: #2f271d; color: #ffe4b5;">
                                    <i class="fas fa-solid fa-plus"></i> Input Data Antenna</a>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table text-center">
                                <thead>
                                    <tr>
                                        <th scope="col">Action</th>
                                        <th scope="col">No.</th>
                                        <th scope="col">Jenis Antenna</th>
                                        <th scope="col">Dari Tower</th>
                                        <th scope="col">Link ke Tower</th>
                                        <th scope="col">Sektor</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach ($datas as $no => $data)
                                    <tr>
                                        <td>
                                            <a href="{{route('data-antenna.edit', $data->id)}}" class="btn btn-warning">Edit</a>
                                            <form action="{{route('data-antenna.destroy', $data->id)}}" method="POST" style="display: inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn btn-danger btn-delete" data-id="{{ $data->id }}" data-url="{{ route('data-antenna.destroy', $data->id) }}"> Delete
                                                </button>
                                            </form>
                                        </td>

                                        <th scope="row">{{ $no + 1 }}.</th>

                                        <td>{{strlen($data['jenis_antenna']) > 100 ? substr($data['jenis_antenna'], 0, 100) . '...' : $data['jenis_antenna']}}</td>
                                        <td>{{strlen($data['id_nama_tower']) > 100 ? substr($data['id_nama_tower'], 0, 100) . '...' : $data['id_nama_tower']}}</td>
                                        <td>{{strlen($data['link_tower']) > 100 ? substr($data['link_tower'], 0, 100) . '...' : $data['link_tower']}}</td>
                                        <td>{{strlen($data['sektor']) > 100 ? substr($data['sektor'], 0, 100) . '...' : $data['sektor']}}</td>
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
</main>