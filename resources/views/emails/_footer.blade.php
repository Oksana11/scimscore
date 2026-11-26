@foreach ($tpl->footer as $line)
    <p style="max-width:{{ $width ?? 480 }}px;margin:{{ $loop->first ? '12px' : '4px' }} auto 0;font-size:12px;color:#64748b">{{ $line }}</p>
@endforeach
