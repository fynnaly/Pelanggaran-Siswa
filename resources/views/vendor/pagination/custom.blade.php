@if ($paginator->hasPages())
    <nav>
        <span>
            {{-- Previous --}}
            @if ($paginator->onFirstPage())
                <span class="disabled" aria-disabled="true"><i data-lucide="chevron-left" style="width:16px;height:16px"></i></span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Halaman sebelumnya"><i data-lucide="chevron-left" style="width:16px;height:16px"></i></a>
            @endif

            {{-- Pages --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="disabled">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="active" aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" aria-label="Halaman {{ $page }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Halaman berikutnya"><i data-lucide="chevron-right" style="width:16px;height:16px"></i></a>
            @else
                <span class="disabled" aria-disabled="true"><i data-lucide="chevron-right" style="width:16px;height:16px"></i></span>
            @endif
        </span>
    </nav>
    <script>document.addEventListener('DOMContentLoaded',()=>{lucide.createIcons()})</script>
@endif
