@if ($paginator->hasPages())
    <nav class="pg-nav" role="navigation" aria-label="Navigasi halaman">
            {{-- Previous --}}
            @if ($paginator->onFirstPage())
                <span class="pg-btn is-disabled" aria-disabled="true"><i data-lucide="chevron-left" style="width:16px;height:16px"></i></span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Halaman sebelumnya" class="pg-btn"><i data-lucide="chevron-left" style="width:16px;height:16px"></i></a>
            @endif

            {{-- Pages --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="pg-gap">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @php $near = abs($page - $paginator->currentPage()) <= 1; @endphp
                        @if ($page == $paginator->currentPage())
                            <span class="pg-btn pg-num is-active" aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" aria-label="Halaman {{ $page }}" class="pg-btn pg-num{{ $near ? ' is-near' : '' }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Halaman berikutnya" class="pg-btn"><i data-lucide="chevron-right" style="width:16px;height:16px"></i></a>
            @else
                <span class="pg-btn is-disabled" aria-disabled="true"><i data-lucide="chevron-right" style="width:16px;height:16px"></i></span>
            @endif
    </nav>
    <script>document.addEventListener('DOMContentLoaded',()=>{lucide.createIcons()})</script>
@endif
