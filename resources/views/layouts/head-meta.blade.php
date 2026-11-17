@php
    $pageMeta = config('page-meta.' . str_replace('.', '\.', '/' . trim(request()->path(), '/')));
    $metaTitle = $pageMeta['title'] ?? config('app.name').' - Sistem Informasi Tata Tertib & Prestasi Siswa';
    $metaDescription = 'Sistem Informasi Pembinaan Disiplin dan Tata Tertib Siswa '.config('app.name');
@endphp
<title>{{ $metaTitle }}</title>
<meta name="description" content="{{ $metaDescription }}">
