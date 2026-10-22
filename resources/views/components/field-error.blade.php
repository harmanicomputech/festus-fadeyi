@props(['name'])
@error($name)
    <p class="error" id="{{ $name }}-error" role="alert">{{ $message }}</p>
@enderror
