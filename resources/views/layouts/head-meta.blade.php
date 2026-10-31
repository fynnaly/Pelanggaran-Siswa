@php
    $pageMeta = config('page-meta.' . str_replace('.', '\.', '/' . trim(request()->path(), '/')));
    $metaTitle = $pageMeta['title'] ?? 'Pelanggaran Siswa - SMK Baktinusantara 666';
    $metaDescription = 'Sistem Informasi Pembinaan Disiplin dan Tata Tertib Siswa SMK Baktinusantara 666';
@endphp
<title>{{ $metaTitle }}</title>
<meta name="description" content="{{ $metaDescription }}">
