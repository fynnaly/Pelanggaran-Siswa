@props(['column', 'label', 'sort', 'order', 'route', 'params' => []])

@php
    // Default state = request tanpa param sort (controller fallback $sort ke kolom
    // untuk ORDER BY query, tapi UI harus tetap netral).
    $isActive = $sort === $column && request()->has('sort');
    $isAsc = $isActive && $order === 'asc';
    $isDesc = $isActive && $order === 'desc';

    // 3-state cycle: default → asc → desc → default
    if (!$isActive) {
        $nextOrder = 'asc';
        $icon = 'arrow-up-down';
        $iconColor = 'var(--on-surface-muted)';
    } elseif ($isAsc) {
        $nextOrder = 'desc';
        $icon = 'arrow-up';
        $iconColor = 'var(--primary)';
    } else {
        $nextOrder = null; // back to default
        $icon = 'arrow-down';
        $iconColor = 'var(--primary)';
    }

    $queryParams = $nextOrder
        ? array_merge($params, ['sort' => $column, 'order' => $nextOrder])
        : $params;
@endphp

<a href="{{ route($route, $queryParams) }}" {{ $attributes->merge(['style' => 'display:inline-flex;align-items:center;gap:2px;text-decoration:none;color:inherit']) }}>
    {{ $label }}
    <i data-lucide="{{ $icon }}" style="width:12px;height:12px;color:{{ $iconColor }};margin-left:2px;vertical-align:middle"></i>
</a>
