@php
    $baseParams = array_filter(['q' => request('q'), 'class_id' => request('class_id'), 'year_id' => request('year_id')]);
    $sortArrow = function($col) use ($sort, $order) {
        $active = $sort === $col;
        if (!$active) { $icon = 'arrow-up-down'; $color = 'var(--on-surface-muted)'; }
        elseif ($order === 'asc') { $icon = 'arrow-up'; $color = 'var(--primary)'; }
        else { $icon = 'arrow-down'; $color = 'var(--primary)'; }
        return '<i data-lucide="'.$icon.'" style="width:12px;height:12px;color:'.$color.';margin-left:2px;vertical-align:middle"></i>';
    };
    $sortLink = function($col) use ($baseParams, $sort, $order) {
        if ($sort === $col && $order === 'asc') return array_merge($baseParams, ['sort' => $col, 'order' => 'desc']);
        if ($sort === $col && $order === 'desc') return $baseParams; // back to default
        return array_merge($baseParams, ['sort' => $col, 'order' => 'asc']);
    };
@endphp
    <div class="table-wrap">
        <table>
            <thead><tr><th style="width:48px"><x-sortable-header column="id" label="No" :sort="$sort" :order="$order" route="students.index" :params="request()->except('sort','order','page')" /></th><th><x-sortable-header column="nis" label="NIS" :sort="$sort" :order="$order" route="students.index" :params="request()->except('sort','order','page')" /></th><th><x-sortable-header column="full_name" label="Nama" :sort="$sort" :order="$order" route="students.index" :params="request()->except('sort','order','page')" /></th><th><x-sortable-header column="gender" label="Jenis Kelamin" :sort="$sort" :order="$order" route="students.index" :params="request()->except('sort','order','page')" /></th><th><x-sortable-header column="grade_level" label="Tingkat" :sort="$sort" :order="$order" route="students.index" :params="request()->except('sort','order','page')" /></th><th><x-sortable-header column="class_name" label="Kelas" :sort="$sort" :order="$order" route="students.index" :params="request()->except('sort','order','page')" /></th><th><x-sortable-header column="status" label="Status" :sort="$sort" :order="$order" route="students.index" :params="request()->except('sort','order','page')" /></th><th style="width:100px">Aksi</th></tr></thead>
            <tbody>
                @forelse($students as $student)
                    @php
                        $enr = $selectedYearId
                            ? $student->enrollments->firstWhere('academic_year_id', $selectedYearId)
                            : $student->currentEnrollment;
                    @endphp
                    <tr>
                        <td class="text-sm text-muted">{{ $student->id }}</td>
                        <td class="mono">{{ $student->nis }}</td>
                        <td><strong>{{ $student->full_name }}</strong></td>
                        <td class="text-sm">{{ $student->gender==='L'?'Laki-laki':'Perempuan' }}</td>
                        <td class="mono" style="font-weight:600;white-space:nowrap">{{ $enr?->schoolClass?->tingkat ?? '-' }}</td>
                        <td class="text-sm" style="white-space:nowrap">{{ $enr?->schoolClass?->name ?? '-' }}</td>
                        <td><span class="badge {{ $student->status==='active'?'badge-success':'' }}" style="{{ $student->status!=='active'?'background:var(--surface);color:var(--on-surface-muted)':'' }}">{{ ucfirst($student->status) }}</span></td>
                        <td><div class="action-cell">
                            <a href="{{ route('students.show', $student) }}" class="action-btn" title="Detail"><i data-lucide="eye" style="width:14px;height:14px"></i></a>
                            <a href="{{ route('students.edit', $student) }}" class="action-btn" title="Edit"><i data-lucide="pencil" style="width:14px;height:14px"></i></a>
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="8" style="padding:40px;text-align:center;color:var(--on-surface-muted)"><i data-lucide="inbox" class="icon-lg" style="display:block;margin:0 auto var(--sp-sm)"></i>@if(request('q') || request('class_id'))Tidak ada siswa yang cocok.@elseBelum ada siswa.@endif</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="display:flex;flex-direction:column;align-items:center;gap:6px;flex-wrap:wrap;margin-top:var(--sp-lg)">
        <span class="text-sm text-muted">Menampilkan {{ $students->firstItem() ?? 0 }}-{{ $students->lastItem() ?? 0 }} dari {{ $students->total() }}</span>
        <div class="pagination" style="margin-top:0">{{ $students->links() }}</div>
    </div>
