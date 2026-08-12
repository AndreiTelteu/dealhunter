@props(['deal', 'mode' => 'thumbnail'])

@php
    $media = $deal->media
        ->filter(fn ($item) => $item->path && $item->downloaded_at && Storage::disk($item->disk)->exists($item->path))
        ->values();
    $galleryId = 'deal-'.$deal->id.'-'.uniqid();
@endphp

@if($media->isNotEmpty())
    @if($mode === 'thumbnail')
        @php
            $firstMedia = $media->first();
            $firstImagePath = Storage::disk($firstMedia->disk)->url($firstMedia->path);
        @endphp
        <div class="shrink-0">
            <a href="{{ $firstImagePath }}" data-fancybox="{{ $galleryId }}" data-caption="{{ $deal->title }} - imagine 1" class="focus-ring block h-16 w-16 overflow-hidden rounded-sm border border-hairline bg-[#06080a] sm:h-20 sm:w-20">
                <img src="{{ $firstImagePath }}" alt="{{ $deal->title }} - imagine 1" class="h-full w-full object-cover" onerror="this.closest('a').remove()">
            </a>
            @foreach($media->skip(1) as $item)
                @php
                    $imagePath = Storage::disk($item->disk)->url($item->path);
                @endphp
                <a href="{{ $imagePath }}" data-fancybox="{{ $galleryId }}" data-caption="{{ $deal->title }} - imagine {{ $loop->iteration + 1 }}" class="sr-only">Imagine {{ $loop->iteration + 1 }}</a>
            @endforeach
        </div>
    @else
        <div class="mt-3 grid grid-cols-2 gap-px bg-hairline">
            @foreach($media as $item)
                @php
                    $imagePath = Storage::disk($item->disk)->url($item->path);
                @endphp
                <a href="{{ $imagePath }}" data-fancybox="{{ $galleryId }}" data-caption="{{ $deal->title }} - imagine {{ $loop->iteration }}" class="focus-ring aspect-square bg-[#06080a]">
                    <img src="{{ $imagePath }}" alt="{{ $deal->title }} - imagine {{ $loop->iteration }}" class="h-full w-full object-cover" onerror="this.closest('a').remove()">
                </a>
            @endforeach
        </div>
    @endif
@endif
